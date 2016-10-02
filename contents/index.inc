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

<!-- Multi-record actions -->
<script type="text/javascript">
    
    function toggle_record_selections(trigger)
    {
        var $trigger = $(trigger);
        var $targets = $trigger.closest('table').find('input.record_selector');
        
        if( $trigger.is(':checked') ) $targets.prop('checked', true);
        else                          $targets.prop('checked', false);
    }
    
    var total_multirecord_action_threads     = 0;
    var processed_multirecord_action_threads = 0;
    var multirecord_action_threads_interval  = null;
    
    function launch_multirecord_actions(trigger, action)
    {
        var $trigger = $(trigger);
        var $targets = $trigger.closest('.table_wrapper').find('input.record_selector:checked');
        
        var targets_count = $targets.length;
        if( targets_count == 0 )
        {
            var message = $('#multirecord_actions_none_selected').text();
            alert(message);
            return;
        }
        
        var action_caption = $trigger.text().trim();
        
        var confirmation = $('#multirecord_actions_confirmation').text();
        confirmation = confirmation.replace(/\{\$amount\}/g,   targets_count);
        confirmation = confirmation.replace(/\{\$new_flag\}/g, action_caption);
        
        if( ! confirm(confirmation) ) return;
        
        total_multirecord_action_threads     = targets_count;
        processed_multirecord_action_threads = 0;
        $trigger.closest('.multi_record_actions').block(blockUI_smallest_params);
        
        multirecord_action_threads_interval = setInterval('check_multirecord_threads()', 500);
        
        $targets.each(function()
        {
            var $this      = $(this);
            var $container = $this.closest('tr');
            var id         = $this.val();
            
            launch_multirecord_action(action, id, $container);
        });
    }
    
    function check_multirecord_threads()
    {
        console.log( 'threads: ', processed_multirecord_action_threads, ' / ',  total_multirecord_action_threads);
        
        if( processed_multirecord_action_threads < total_multirecord_action_threads ) return;
        
        clearInterval(multirecord_action_threads_interval);
        
        var message = $('#multirecord_actions_all_done').text();
        alert(message);
        
        $('.multirecord_actions_table_refresher').click();
    }
    
    function launch_multirecord_action(action, id, $container)
    {
        var url    = $_FULL_ROOT_PATH + '/comments/scripts/toolbox.php';
        var params = {
            action:     'change_status',
            id_comment: id,
            new_status: action,
            wasuuup:    wasuuup()
        };
        
        $container.block(blockUI_smaller_params);
        $.get(url, params, function(response)
        {
            if( response != 'OK' )
            {
                throw_notification(response, 'error');
                $container.unblock();
                processed_multirecord_action_threads++;
                
                return;
            }
            
            $container.unblock().fadeOut('fast', function()
            {
                $(this).remove();
                processed_multirecord_action_threads++;
            });
        });
    }
    
</script>
<div id="multirecord_actions_confirmation"  style="display: none;"><?= unindent($current_module->language->multi_actions->confirmation) ?></div>
<div id="multirecord_actions_none_selected" style="display: none;"><?= unindent($current_module->language->multi_actions->none_selected) ?></div>
<div id="multirecord_actions_all_done"      style="display: none;"><?= unindent($current_module->language->multi_actions->done) ?></div>
