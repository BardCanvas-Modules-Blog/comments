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

use hng2_base\account;
use hng2_base\config;
use hng2_media\media_repository;
use hng2_modules\comments\comment_record;
use hng2_modules\comments\comments_repository;
use hng2_tools\record_browser;

$browser          = new record_browser("{$config->website_key}_{$current_module->name}");
$repository       = new comments_repository();
$media_repository = new media_repository();

/**
 * @var int    $offset
 * @var int    $limit
 * @var int    $order
 * @var string $search_for
 * @var string $search_status
 */
#region Nav filters

$built_vars = $browser->build_vars(20, 2);
foreach($built_vars as $key => $val) $$key = $val;

# 0: status
# 1: author
$where = array();

if( $account->level >= config::MODERATOR_USER_LEVEL )
{
    $where[0] = "true";
    $where[1] = "true";
    
    if( ! empty($search_status) ) $where[0] = "status = '$search_status'";
    
    if( ! empty($_GET["author"]) ) $where[1] = "(
        id_author = '{$_GET["author"]}' or
        id_author in (select c2.id_author from comments as c2 where c2.id_comment = comments.parent_comment)
    )";
}
else
{
    if( $search_status == "hidden" ) $search_status = "";
    
    if( empty($search_status) )
        $where[0] = "status not in ('trashed', 'hidden')";
    else
        $where[0] = "status = '$search_status'";
    
    $where[1] = "(
        id_author = '{$account->id_account}' or
        id_author in (select c2.id_author from comments as c2 where c2.id_comment = comments.parent_comment)
    )";
}

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
                $where_line .= "content             like '%{$this_term}%' or ";
            }
        }
        $where_line = preg_replace('/ or $/', "", $where_line);
        $where[]    = "({$where_line})";
    }
    else
    {
        $search_for = trim($search_for);
        $where[] = "("
            . "id_post             =     '{$search_for}'  or "
            . "id_comment          =     '{$search_for}'  or "
            . "id_author           =     '{$search_for}'  or "
            . "author_display_name like '%{$search_for}%' or "
            . "author_email        like '%{$search_for}%' or "
            . "creation_ip         like '{$search_for}%'  or "
            . "content             like '%{$search_for}%'    )"
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
$query   = $repository->get_last_query();
$authors = $repository->get_all_authors($records);
#endregion
?>

<? if( ! empty($_GET["flag_as_spam"]) ): ?>
    <script type="text/javascript"> $(document).ready(function() {
        spam_comment('<?= $_GET["flag_as_spam"] ?>', function() { $('#refresh_commments_browser').click(); })
    }); </script>
<? endif; ?>
<? if( ! empty($_GET["approve"]) ): ?>
    <script type="text/javascript"> $(document).ready(function() {
        publish_comment('<?= $_GET["approve"] ?>', function() { $('#refresh_commments_browser').click(); })
    }); </script>
<? endif; ?>
<? if( ! empty($_GET["reject"]) ): ?>
    <script type="text/javascript"> $(document).ready(function() {
        reject_comment('<?= $_GET["reject"] ?>', function() { $('#refresh_commments_browser').click(); })
    }); </script>
<? endif; ?>
<? if( ! empty($_GET["delete"]) ): ?>
    <script type="text/javascript"> $(document).ready(function() {
        delete_comment('<?= $_GET["delete"] ?>', function() { $('#refresh_commments_browser').click(); })
    }); </script>
<? endif; ?>
<?
if( ! empty($_GET["edit"]) ):
    $row = $repository->get($_GET["edit"]);
    if( ! is_null($row) ):
        if( is_comment_editable($row) ):
            $post         = $row->get_post();
            $dialog_title = replace_escaped_vars(
                $current_module->language->form->edit,
                array('{$id}', '{$post_title}'),
                array($row->id_comment, $post->title)
            ); ?>
            <script type="text/javascript">
                $(document).ready(function() {
                    edit_comment('<?= $row->id_comment ?>', '<?= htmlspecialchars($dialog_title) ?>');
                });
            </script>
        <? endif; ?>
    <? endif; ?>
<? endif; ?>

<h1 class="clearfix">
    <button id="refresh_commments_browser" class="pull-right" onclick="paginate(<?= $offset ?>)">
        <span class="fa fa-refresh"></span>
        <?= $language->refresh ?>
    </button>
    
    <?= $current_module->language->index->title; ?>
    
    <?
    if( ! empty($_GET["author"]) )
    {
        $author = new account($_GET["author"]);
        if( ! $author->_exists ) echo " ~ #{$_GET["author"]}";
        else                     echo " ~ " . convert_emojis($author->display_name);
    }
    ?>
    
</h1>

<div class="filtering clearfix">
    <form name="filter_form" id="filter_form" action="<?= $_SERVER["PHP_SELF"] ?>?wasuuup=<? echo mt_rand(1, 65535); ?>"
          method="get">
        
        <input type="hidden" name="mode"          value="set_filter">
        <input type="hidden" name="order"         value="<?= $order ?>">
        <input type="hidden" name="offset"        value="0">
        <input type="hidden" name="author"        value="<?= $_GET["author"] ?>">
        <input type="hidden" name="search_status" value="<?= $search_status ?>">
        
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
    
    <script type="text/javascript">
        function set_filter_var(name, val, send)
        {
            if( typeof send == 'undefined' ) send = true;
            
            var $form = $('#filter_form');
            $form.find('input[name="' + name + '"]').val(val);
            if( send ) $form.submit();
        }
    </script>
    
    <div class="records_browser filter_links" style="margin-top: 10px;">
        
        <? if($account->level >= config::MODERATOR_USER_LEVEL): ?>
            <span class="framed_content <? if($_GET["author"] == $account->id_account) echo "state_active" ?> pseudo_link"
                  onclick="set_filter_var('search_status', '', false); set_filter_var('author', '<?= $account->id_account ?>')">
                <?= $language->mine_sm ?>
                (<?= number_format($repository->get_record_count(array("
                    (
                        id_author = '{$account->id_account}' or
                        id_author in (select c2.id_author from comments as c2 where c2.id_comment = comments.parent_comment)
                    )"))) ?>)
            </span>
            <? $where[1] = "true"; ?>
        <? endif; ?>
        
        <? $where_copy = $where; $where_copy[0] = "status = 'published'"; ?>
        <span class="framed_content <? if($search_status == "published") echo "state_active" ?> pseudo_link"
              onclick="set_filter_var('author', '', false); set_filter_var('search_status', 'published')">
            <?= $current_module->language->statuses->published ?>
            (<?= number_format($repository->get_record_count($where_copy)) ?>)
        </span>
        
        <? $where_copy = $where; $where_copy[0] = "status = 'reviewing'"; 
           $reviewing = $repository->get_record_count($where_copy); ?>
        <span class="framed_content <? if($search_status == "reviewing") echo "state_active" ?> <? if($reviewing) echo "state_ko"; ?> pseudo_link"
              onclick="set_filter_var('author', '', false); set_filter_var('search_status', 'reviewing')">
            <?= $current_module->language->statuses->reviewing ?>
            (<?= number_format($reviewing) ?>)
        </span>
        
        <? if($account->level >= config::MODERATOR_USER_LEVEL): ?>
            <? $where_copy = $where; $where_copy[0] = "status = 'rejected'"; 
               $rejected = $repository->get_record_count($where_copy); ?>
            <span class="framed_content <? if($search_status == "rejected") echo "state_active" ?> pseudo_link"
                  onclick="set_filter_var('author', '', false); set_filter_var('search_status', 'rejected')">
                <?= $current_module->language->statuses->rejected ?>
                (<?= number_format($rejected) ?>)
            </span>
        <? endif; ?>
        
        <? if($account->level >= config::MODERATOR_USER_LEVEL): ?>
            <? $where_copy = $where; $where_copy[0] = "status = 'spam'"; 
               $spam = $repository->get_record_count($where_copy); ?>
            <span class="framed_content <? if($search_status == "spam") echo "state_active" ?> <? if($spam) echo "state_ko"; ?> pseudo_link"
                  onclick="set_filter_var('author', '', false); set_filter_var('search_status', 'spam')">
                <?= $current_module->language->statuses->spam ?>
                (<?= number_format($spam) ?>)
            </span>
        <? endif; ?>
        
        <? $where_copy = $where; $where_copy[0] = "status = 'trashed'"; ?>
        <span class="framed_content <? if($search_status == "trashed") echo "state_active" ?> pseudo_link"
              onclick="set_filter_var('author', '', false); set_filter_var('search_status', 'trashed')">
            <?= $current_module->language->statuses->trashed ?>
            (<?= number_format($repository->get_record_count($where_copy)) ?>)
        </span>
        
        <? if($account->level >= config::MODERATOR_USER_LEVEL): ?>
            <? $where_copy = $where; $where_copy[0] = "status = 'hidden'"; ?>
            <span class="framed_content <? if($search_status == "hidden") echo "state_active" ?> pseudo_link"
                  onclick="set_filter_var('author', '', false); set_filter_var('search_status', 'hidden')">
                <?= $current_module->language->statuses->hidden ?>
                (<?= number_format($repository->get_record_count($where_copy)) ?>)
            </span>
        <? endif; ?>
        
        <span class="framed_content pseudo_link" onclick="set_filter_var('author', '', false); set_filter_var('search_status', '')">
            <?= $language->all_m ?>
        </span>
        
    </div>
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
        <?
        foreach( $records as $row ):
            
            $author       = $authors[$row->id_author];
            $author_name  = empty($row->id_author) ? $row->author_display_name : $author->display_name;
            $author_email = empty($row->id_author) ? $row->author_email        : $author->email;
            
            $post       = $row->get_post();
            $post_url   = $post->get_permalink();
            $post_title = $post->get_processed_title(false);
            ?>
            <tr class="comment_item comment_entry" data-record-id="<?= $row->id_comment ?>"
                data-id-comment="<?= $row->id_comment ?>" data-post-id="<?= $row->id_post ?>">
                <td data-for="sent_date">
                    <?= time_elapsed_string($row->creation_date) ?>
                </td>
                <td data-for="sent_by" nowrap>
                    <? if( ! empty($author) ): ?>
                        
                        <a class="user_display_name" data-user-level="<?= $author->level ?>"
                           href="<?= $config->full_root_url ?>/user/<?= $author->user_name ?>/" target="_blank">
                            <span class="fa fa-user fa-fw"></span>
                            <?= $author->get_processed_display_name() ?>
                        </a><br>
                        <? if( $account->id_account != $author->id_account ): ?>
                            <span class="pseudo_link" onclick="send_pm(this, '<?= $author->user_name ?>', '<?= replace_escaped_vars($language->contact->pm->send_to, '{$recipient}', htmlspecialchars($author->display_name)) ?>')"
                                  title="<?= replace_escaped_vars( $language->contact->pm->title, '{$website_name}', $settings->get("engine.website_name") ) ?>">
                                <span class="fa fa-inbox fa-fw"></span>
                                <?= $language->contact->pm->caption ?>
                            </span>
                        <? endif; ?>
                        
                    <? else: ?>
                        
                        <div class="principal_data"><?= convert_emojis($author_name) ?></div>
                        
                        <? if( $account->level >= config::MODERATOR_USER_LEVEL ): ?>
                            <span class="clipboard-copy pseudo_link" data-clipboard-text="<?= $author_email ?>">
                                <span class="caption">
                                    <?= $author_email ?>
                                </span>
                                <span class="button">
                                    <span class="fa fa-copy fa-fw"></span>
                                </span>
                            </span>
                        <? endif; ?>
                        
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
                    
                    <? render_comment_tags($row); ?>
                    
                    <? render_comment_attached_media($row); ?>
                    
                    <hr>
                    <div class="actions">
                        
                        <? if( ! in_array($row->status, array("rejected", "spam", "trashed", "hidden")) ): ?> 
                            <a href="<?= $row->get_permalink() ?>" target="_blank">
                                <span class="fa fa-link"></span>
                                <?= $language->permalink ?>
                            </a>
                        <? endif; ?>
                        
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
                        <span class="action pseudo_link" onclick="show_ajax_dialog('<?= htmlspecialchars($dialog_title) ?>', '<?= $url ?>', false)">
                            <span class="fa fa-search"></span>
                            <?= $current_module->language->record_nav->actions->preview ?>
                        </span>
                        
                        <? if( ! in_array($row->status, array("rejected", "reviewing", "spam", "trashed")) ): ?>
                            <? if($account->level >= config::MODERATOR_USER_LEVEL || $row->_can_be_replied ):
                                $dialog_title = replace_escaped_vars(
                                    $current_module->language->messages->replying,
                                    array('{$id}', '{$post_title}'),
                                    array($row->id_comment, $post->title)
                                ); ?>
                                <span class="comment_reply">
                                    <span class="trigger" data-dialog-title="<?= htmlspecialchars($dialog_title) ?>">
                                        <span class="action pseudo_link" onclick="prepare_comment_reply(this)">
                                            <span class="fa fa-reply"></span>
                                            <?= $current_module->language->record_nav->actions->reply ?>
                                        </span>
                                        <span class="action pseudo_link" onclick="prepare_comment_reply(this, true)">
                                            <span class="fa fa-quote-right"></span>
                                            <?= $current_module->language->record_nav->actions->quote ?>
                                        </span>
                                    </span>
                                </span>
                            <? endif; ?>
                        <? endif; ?>
                        
                        <? if( is_comment_editable($row) && ! in_array($row->status, array("rejected", "spam", "trashed")) ):
                            $dialog_title = replace_escaped_vars(
                                $current_module->language->form->edit,
                                array('{$id}', '{$post_title}'),
                                array($row->id_comment, $post->title)
                            ); ?>
                            <span class="action pseudo_link" onclick="edit_comment('<?= $row->id_comment ?>', '<?= htmlspecialchars($dialog_title) ?>')">
                                <span class="fa fa-pencil"></span>
                                <?= $current_module->language->record_nav->actions->edit ?>
                            </span>
                        <? endif; ?>
                        
                        <? if( $row->id_author != $account->id_account ): ?>
                            <? if( $account->level >= config::MODERATOR_USER_LEVEL
                                   && in_array($row->status, array("reviewing", "spam")) ): ?>
                                <span class="action pseudo_link greengo" onclick="publish_comment('<?= $row->id_comment ?>', function() { $('#refresh_commments_browser').click(); })">
                                    <span class="fa fa-check"></span>
                                    <?= $current_module->language->record_nav->actions->approve ?>
                                </span>
                            <? endif; ?>
                            
                            <? if( $account->level >= config::MODERATOR_USER_LEVEL
                                   && in_array($row->status, array("published", "reviewing", "spam")) ): ?>
                                <span class="action pseudo_link important" onclick="reject_comment('<?= $row->id_comment ?>', function() { $('#refresh_commments_browser').click(); })">
                                    <span class="fa fa-times"></span>
                                    <?= $current_module->language->record_nav->actions->reject ?>
                                </span>
                            <? endif; ?>
                        <? endif; ?>
                        
                        <? if($row->id_author != $account->id_account && ! in_array($row->status, array("rejected", "spam", "trashed")) ): ?>
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
                    <? render_in_reply_to_link($row); ?>
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

<?
function render_in_reply_to_link($row)
{
    global $post_url, $post_title, $repository, $current_module;
    
    $post_link  = "
        <a href='$post_url' target='_blank'>
            <span class='fa fa-newspaper-o'></span>
            $post_title
        </a>
        ";
    
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
}

function render_comment_tags(comment_record $row)
{
    global $account, $current_module;
    
    $remove_tag_link = "";
    //TODO: add remove_tag_from_comment JS function
    //if( is_comment_editable($row) ) $remove_tag_link = "
    //    <span class='pseudo_link fa fa-times fa-border' title='{$current_module->language->record_nav->extras->remove_tag}'
    //          onclick='remove_tag_from_comment(\$(this).closest(\".hashtag\"), \"{$row->id_comment}\", \"\{\$tag}\")'></span>
    //";
    if( $account->level < config::MODERATOR_USER_LEVEL )
    {
        foreach( $row->get_filtered_tags_list() as $tag)
        {
            $link = str_replace('{$tag}', $tag, $remove_tag_link);
            echo "<span class='hashtag'><span class='fa fa-hashtag fa-fw'></span>{$tag}{$link}</span> ";
        }
    }
    else
    {
        if( is_array($row->tags_list) )
        {
            echo "<hr>";
            foreach( $row->tags_list as $tag)
            {
                $link = str_replace('{$tag}', $tag, $remove_tag_link);
                echo "<span class='hashtag'><span class='fa fa-hashtag fa-fw'></span>{$tag}{$link}</span> ";
            }
        }
    }
}

function render_comment_attached_media(comment_record $row)
{
    global $media_repository, $config;
    
    if( empty($row->media_list) ) return;
    
    echo "\n<hr>\n";
    
    $media_records = $media_repository->get_multiple($row->media_list);
    foreach($media_records as $media_record):
        $icon = $media_record->type == "image" ? "fa-picture-o" : "fa-film"; ?>
        <div class="media_bullet">
            <span class="bullet fa <?= $icon ?> fa-fw"></span>
            <a href="<?= $config->full_root_path ?>/user/<?= $media_record->author_user_name ?>"
               class="user_display_name" data-user-level="<?= $media_record->author_level ?>">
                <span class="fa fa-user fa-fw"></span>
                <?= $media_record->get_processed_author_display_name() ?></a>
            [<?= time_elapsed_string($media_record->publishing_date) ?>]
            <a class="prettyPhoto" href="<?= $media_record->get_thumbnail_url() ?>"><?= $media_record->title ?></a>
            <a href="<?= $media_record->get_page_url() ?>" target="_blank"><span class="fa fa-external-link fa-fw"></span></a>
        </div>
        <?
    endforeach;
}
