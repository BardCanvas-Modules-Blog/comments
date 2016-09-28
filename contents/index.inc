<?php
/**
 * Comments index
 *
 * @package    BardCanvas
 * @subpackage comments
 * @author     Alejandro Caballero - lava.caballero@gmail.com
 *             
 * @var config            $config
 * @var \SimpleXMLElement $language
 */

use hng2_base\config;
?>

<script type="text/javascript">
    // These are declared on html_head.inc and overriden here
    comment_reply_management_type = 'dialog';
    comment_reply_dialog_callback = function() { $('#refresh_commments_browser').click(); };
    comment_edit_dialog_callback  = function() { $('#refresh_commments_browser').click(); };
    
    function reset_filter()
    {
        var $form = $('#filter_form');
        $form.find('input[name="search_for"]').val('');
        $form.find('select[name="limit"] option[value="20"]').prop('selected', true);
        $form.find('input[name="offset"]').val('0');
        $form.find('input[name="order"]').val('2');
        
        $select = $form.find('select[name="search_author"]');
        if( $select.length > 0 ) $select.find('option:first').prop('selected', true);
        
        $form.find('select[name="search_status"] option:first').prop('selected', true);
    }
    
    function untrash_for_review(id_comment)
    {
        var message = $('#untrash_for_review_confirmation').text();
        if( ! confirm(message) ) return;
        
        var url = $_FULL_ROOT_PATH + '/comments/scripts/toolbox.php';
        var params = {
            action:     'untrash_for_review',
            id_comment: id_comment,
            wasuuup:    wasuuup()
        };
        
        $.blockUI(blockUI_default_params);
        $.get(url, params, function(response)
        {
            if( response != 'OK' )
            {
                alert(response);
                $.unblockUI();
        
                return;
            }
            
            location.href = $_FULL_ROOT_PATH + '/comments/?search_for=' + id_comment + '&edit=' + id_comment;
        });
    }
    
    //noinspection JSUnusedGlobalSymbols
    function paginate(value)
    {
        var $form = $('#filter_form');
        $form.find('input[name="offset"]').val(value);
        $form.submit();
    }
</script>

<div id="main_workarea">
    
    <?
    $url = "browser.php?wasuuup=" . md5(mt_rand(1, 65535));
    if( ! empty($_GET["flag_as_spam"]) ) $url .= "&flag_as_spam={$_GET["flag_as_spam"]}";
    if( ! empty($_GET["approve"]) )      $url .= "&approve={$_GET["approve"]}";
    if( ! empty($_GET["reject"]) )       $url .= "&reject={$_GET["reject"]}";
    if( ! empty($_GET["delete"]) )       $url .= "&delete={$_GET["delete"]}";
    if( ! empty($_GET["edit"]) )         $url .= "&edit={$_GET["edit"]}";
    if( ! empty($_GET["author"]) )       $url .= "&search_author={$_GET["author"]}";
    if( ! empty($_GET["search_for"]) )   $url .= "&search_for=" . urlencode(stripslashes($_GET["search_for"]));
    ?>
    <div id="comments_browser" class="ajax_record_browser" data-src="<?= $url ?>">
        <div class="framed_content state_highlight" style="padding: 100px 0; text-align: center;">
            <span class="fa fa-spinner fa-pulse"></span>
            <?= $language->wait ?>
        </div>
    </div>
    
</div>

<div id="reply_target" style="display: none;"></div>
<div id="post_comment_target" style="display: none;"></div>

<div id="comments_edit_form" style="display: none;" data-title-template="<?= htmlspecialchars($current_module->language->form->edit) ?>"></div>
<div id="comments_edit_target" style="display: none;"></div>

<div id="untrash_for_review_confirmation" style="display: none;"><?= unindent($current_module->language->messages->untrash_for_review_confirmation) ?></div>
