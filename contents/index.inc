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

<div id="comments_edit_form" style="display: none;" data-title-template="<?= $current_module->language->form->edit ?>"></div>
<div id="comments_edit_target" style="display: none;"></div>
