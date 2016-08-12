<?
/**
 * Comments browser
 *
 * @package    BardCanvas
 * @subpackage comments
 * @author     Alejandro Caballero - lava.caballero@gmail.com
 *             
 * @var config $config
 */

use hng2_base\config;
use hng2_modules\comments\comments_repository;
use hng2_tools\record_browser;

$browser    = new record_browser("{$config->website_key}_{$current_module->name}");
$repository = new comments_repository();

/**
 * @var int    $offset
 * @var int    $limit
 * @var int    $order
 * @var string $search_state
 * @var string $search_for
 */
#region Nav filters
$built_vars = $browser->build_vars(20, 2);
foreach($built_vars as $key => $val) $$key = $val;

$where = array();
if( $account->level < config::MODERATOR_USER_LEVEL ) $where[] = "status <> 'trashed'";
if( $account->level < config::MODERATOR_USER_LEVEL ) $where[] = "status <> 'hidden'";
if( $account->level < config::MODERATOR_USER_LEVEL ) $where[] = "
    (
        id_author = '{$account->id_account}' or
        id_author in (select c2.id_author from comments as c2 where c2.id_comment = comments.parent_comment)
    )
    ";

$search_for = empty($search_for) ? "" : trim($search_for);
if( ! empty($search_for) )
{
    if( strpos($search_for, ",") !== false )
    {
        $search_for_exploded = explode(",", $search_for);
        $where_line          = "";
        foreach( $search_for_exploded as $this_term )
        {
            $this_term = trim($this_term);
            if( ! empty($this_term) )
            {
                $where_line .= "id_post             =     '{$this_term}'  or ";
                $where_line .= "id_comment          =     '{$this_term}'  or ";
                $where_line .= "id_author           =     '{$this_term}'  or ";
                $where_line .= "author_display_name like '%{$this_term}%' or ";
                $where_line .= "author_email        like '%{$this_term}%' or ";
                $where_line .= "creation_ip         like '{$this_term}%'  or ";
                $where_line .= "content             like '%{$this_term}%'    ";
            }
        }
        $where[] = $where_line;
    }
    else
    {
        $search_for = trim($search_for);
        $where[] 
            = "id_post             =     '{$search_for}'  or "
            . "id_comment          =     '{$search_for}'  or "
            . "id_author           =     '{$search_for}'  or "
            . "author_display_name like '%{$search_for}%' or "
            . "author_email        like '%{$search_for}%' or "
            . "creation_ip         like '{$search_for}%'  or "
            . "content             like '%{$search_for}%'    "
        ;
    }
}
#endregion

#region Nav pointers
$record_count = $repository->get_record_count($where);
$pagination   = $browser->build_pagination($record_count, $limit, $offset);
#endregion

#region Data grabbing
switch( $order )
{
    case  1: $sqlorder = "creation_date asc";        break;
    case  2: $sqlorder = "creation_date desc";       break;
    case  3: $sqlorder = "author_display_name asc";  break;
    case  4: $sqlorder = "author_display_name desc"; break;
    case  5: $sqlorder = "karma asc";                break;
    case  6: $sqlorder = "karma desc";               break;
}

$records = $repository->find($where, $limit, $offset, $sqlorder);
$authors = $repository->get_all_authors($records);
#endregion
?>

<h1 class="clearfix">
    <button id="refresh_commments_browser" class="pull-right" onclick="paginate(<?= $offset ?>)">
        <span class="fa fa-refresh"></span>
        <?= $language->refresh ?>
    </button>
    
    <?= $current_module->language->index->title; ?>
</h1>

<div class="filtering clearfix">
    <form name="filter_form" id="filter_form" action="<?= $_SERVER["PHP_SELF"] ?>?wasuuup=<? echo mt_rand(1, 65535); ?>"
          method="get">
        
        <input type="hidden" name="mode"   value="set_filter">
        <input type="hidden" name="order"  value="<?= $order ?>">
        <input type="hidden" name="offset" value="0">
        
        <span style="float: right;">
            <?= $browser->get_pagination_button("previous", "paginate", $pagination) ?>
            <?= $language->record_nav->page ?>
            <?= $pagination["this_page_number"]; ?>/<?= $pagination["total_pages"]; ?>
            (<?= $pagination["total_records"]; ?> <?= $language->record_nav->entries ?>)
            <?= $browser->get_pagination_button("next", "paginate", $pagination) ?>
        </span>
        
        <?= $language->record_nav->search ?>
        <input type="text" name="search_for"
               value="<?= htmlspecialchars($search_for) ?>" size="46"
               placeholder="<?= $current_module->language->record_nav->search_placeholder ?>">
        
        <?= $language->record_nav->show ?>
        <select name="limit">
            <? foreach(array(5, 10, 20, 30, 40, 50, 100, 200, 300, 400, 500) as $recs): ?>
                <option <? if($limit == $recs) echo "selected" ?> value="<?= $recs ?>"><?= $recs ?></option>
            <? endforeach; ?>
        </select>
        <?= $language->record_nav->recs_per_page ?>
        
        <button type="submit"><?= $language->record_nav->buttons->apply ?></button>
        <button type="submit" onclick="reset_filter();"><?= $language->record_nav->buttons->reset ?></button>
    </form>
</div>

<? if( $record_count == 0 ): ?>
    <div class="framed_content state_ko">
        <span class="fa fa-info-circle"></span>
        <?= $language->record_nav->no_records_found ?>
    </div>
    <? return; ?>
<? endif;  ?>

<div class="table_wrapper">
    <table id="posts_browser_table" class="nav_table evened comment_actions_container">
        
        <thead>
        <tr>
            <? foreach( $browser->build_table_header($current_module->language->record_nav->columns) as $this_cell ): ?>
                <th align="<?= $this_cell->xalign ?>" <? if( $this_cell->xnowrap == true ) echo "nowrap "; ?>
                    <? if( ! empty($this_cell->xwidth) ) echo "width='$this_cell->xwidth' "; ?> class="<?= $this_cell->xclass ?>"><?
                    if( $this_cell->sort_asc->enabled == true ) echo "<img
                    src='{$config->full_root_path}/media/icons/order_asc" . ($order == $this_cell->sort_asc->order ? "_this" : "") . ".gif' 
                    align='absmiddle' width='7' height='7'  style='cursor: pointer;'
                    alt='{$this_cell->sort_asc->alt}' title='{$this_cell->sort_asc->alt}'
                    onclick=\"document.filter_form.order.value='{$this_cell->sort_asc->order}'; $('#filter_form').submit();\">&nbsp;";
                    ?><?= $this_cell->content ?><?
                    if( $this_cell->sort_desc->enabled == true ) echo "&nbsp;<img
                    src='{$config->full_root_path}/media/icons/order_desc" . ($order == $this_cell->sort_desc->order ? "_this" : "") . ".gif' 
                    align='absmiddle' width='7' height='7'  style='cursor: pointer;'
                    alt='{$this_cell->sort_desc->alt}' title='{$this_cell->sort_desc->alt}'
                    onclick=\"document.filter_form.order.value='{$this_cell->sort_desc->order}'; $('#filter_form').submit();\">";
                    ?></th>
            <? endforeach; ?>
        </tr>
        </thead>
        
        <tbody>
        <? foreach( $records as $row ): ?>
            <?
            $author       = $authors[$row->id_author];
            $author_name  = empty($row->id_author) ? $row->author_display_name : $author->display_name;
            $author_email = empty($row->id_author) ? $row->author_email        : $author->email;
            
            $post       = $row->get_post();
            $post_url   = $post->get_permalink();
            $post_title = $post->get_processed_title(false);
            $post_link  = "<a href='$post_url' target='_blank'>
                               <span class='fa fa-newspaper-o'></span>
                               $post_title
                           </a>";
            ?>
            <tr class="comment_item" data-record-id="<?= $row->id_comment ?>">
                <td data-for="sent_date">
                    <?= time_elapsed_string($row->creation_date) ?>
                </td>
                <td data-for="sent_by">
                    <div class="principal_data"><?= convert_emojis($author_name) ?></div>
                    <? if( $account->level >= config::MODERATOR_USER_LEVEL ): ?>
                        <a href="mailto:<?= $author_email ?>">
                            <span class="fa fa-envelope-o"></span>
                            <?= $author_email ?>
                        </a>
                    <? endif; ?>
                </td>
                <td data-for="status"><?= $current_module->language->statuses->{$row->status} ?></td>
                <td data-for="details">
                    <? if( $row->_replies_count > 0): ?>
                        <span class="framed_content inlined state_highlight pull-right"><?= replace_escaped_vars(
                                $current_module->language->record_nav->columns->details->replies,
                                '{$replies}',
                                $row->_replies_count
                            ) ?></span>
                    <? endif; ?>
                    
                    <?= make_excerpt_of($row->content, 100) ?>
                    
                    <?
                    $dialog_title = replace_escaped_vars(
                        $current_module->language->messages->previewing,
                        array('{$id}', '{$post_title}'),
                        array($row->id_comment, $post->title)
                    );
                    
                    $url = "{$config->full_root_path}/comments/scripts/toolbox.php"
                         . "?action=preview"
                         . "&id_comment={$row->id_comment}"
                         . "&wasuuup=" . md5(mt_rand(1, 65535))
                         ;
                    ?>
                    <div class="actions">
                        <span class="action pseudo_link" onclick="show_ajax_dialog('<?= htmlspecialchars($dialog_title) ?>', '<?= $url ?>', false)">
                            <span class="fa fa-search"></span>
                            <?= $current_module->language->record_nav->actions->preview ?>
                        </span>
                        
                        <? if($account->level >= config::MODERATOR_USER_LEVEL && in_array($row->status, array("reviewing", "spam")) ): ?>
                            <span class="action pseudo_link greengo" onclick="publish_comment('<?= $row->id_comment ?>', function() { $('#refresh_commments_browser').click(); })">
                                <span class="fa fa-check"></span>
                                <?= $current_module->language->record_nav->actions->approve ?>
                            </span>
                            <span class="action pseudo_link important" onclick="reject_comment('<?= $row->id_comment ?>', function() { $('#refresh_commments_browser').click(); })">
                                <span class="fa fa-times"></span>
                                <?= $current_module->language->record_nav->actions->reject ?>
                            </span>
                        <? endif; ?>
                        
                        <? if($account->level >= config::MODERATOR_USER_LEVEL || $row->_can_be_replied ): ?>
                            <span class="action pseudo_link disabled" onclick="reply_comment('<?= $row->id_comment ?>')">
                                <span class="fa fa-reply"></span>
                                <?= $current_module->language->record_nav->actions->reply ?>
                            </span>
                        <? endif; ?>
                        
                        <? if( is_comment_editable($row) ): ?>
                            <span class="action pseudo_link disabled" onclick="edit_comment('<?= $row->id_comment ?>')">
                                <span class="fa fa-pencil"></span>
                                <?= $current_module->language->record_nav->actions->edit ?>
                            </span>
                        <? endif; ?>
                        
                        <? if($row->id_author != $account->id_account && $row->status != "spam"): ?>
                            <span class="action pseudo_link critical" onclick="spam_comment('<?= $row->id_comment ?>', function() { $('#refresh_commments_browser').click(); })">
                                <span class="fa fa-warning"></span>
                                <?= $current_module->language->record_nav->actions->spam ?>
                            </span>
                        <? endif; ?>
                        
                        <? if($row->status != "trashed"): ?>
                            <? if($row->id_author == $account->id_account || $account->level >= config::MODERATOR_USER_LEVEL): ?>
                                <span class="action pseudo_link critical" onclick="delete_comment('<?= $row->id_comment ?>', function() { $('#refresh_commments_browser').click(); })">
                                    <span class="fa fa-trash"></span>
                                    <?= $current_module->language->record_nav->actions->trash ?>
                                </span>
                            <? endif; ?>
                        <? endif; ?>
                    </div>
                </td>
                <td data-for="in_reply_to">
                    <?
                    if( empty($row->parent_comment) )
                    {
                        echo $post_link;
                    }
                    else
                    {
                        $parent = $repository->get($row->parent_comment);
                        if( is_null($parent) )
                            echo $current_module->language->messages->parent_not_found;
                        else
                            echo  "{$post_link}<br>"
                                . "<a href='$post_url#comment_{$parent->id_comment}' target='_blank'>" 
                                . "<span class='fa fa-comment-o'></span> " 
                                . make_excerpt_of($parent->content, 100) 
                                . "</a>";
                    }
                    ?>
                </td>
                <td data-for="sent_from">
                    <?= $row->creation_ip ?><br>
                    <?= $row->creation_location ?><br>
                </td>
            </tr>
        <? endforeach; ?>
        </tbody>
    </table>
</div>

<div class="pagination">
    <? $browser->render_pagination_controls("paginate", $pagination); ?>
</div>
