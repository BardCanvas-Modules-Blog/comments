
function edit_comment(id_comment, dialog_title)
{
    var url       = $_FULL_ROOT_PATH + '/comments/scripts/render_prefilled_form.php';
    var form_id   = 'comment_edit_' + wasuuup();
    var $target = $('#comments_edit_form');
    $target.dialog('option', 'title', dialog_title);
    
    var params = {
        edit_comment: id_comment,
        wasuuup:      wasuuup()
    };
    
    $target.load(url, params, function()
    {
        var $form = $target.find('form');
        
        $form.attr('name', form_id);
        $form.attr('id',   form_id);
        $form.find('input[name="id_comment"]').val(id_comment);
        
        tinymce.init(tinymce_defaults);
        
        $form.ajaxForm({
            target:          '#post_comment_target',
            beforeSerialize: prepare_comment_edit_serialization,
            beforeSubmit:    prepare_comment_edit_submission,
            success:         process_comment_edit_submission
        });
        
        $target.dialog('open');
    });
}

function prepare_comment_edit_serialization($form)
{
    $form.find('textarea[class*="tinymce"]').each(function()
    {
        var id      = $(this).attr('id');
        var editor  = tinymce.get(id);
        var content = editor.getContent();
        $(this).val( content );
    });
}

function prepare_comment_edit_submission(data, $form)
{
    $form.block(blockUI_default_params);
}

function process_comment_edit_submission(response, status, xhr, $form)
{
    if( response.indexOf('OK') < 0 )
    {
        alert( response );
        $form.unblock();
        
        return;
    }
    
    if( typeof comment_edit_dialog_callback == 'function' )
    {
        $('#comments_edit_form').dialog('close');
        
        comment_edit_dialog_callback();
        
        return;
    }
    
    var parts;
    
    parts = response.split(':');
    
    var comment_id = parts[1];
    var href       = location.href;
    
    href = href.replace(/\?wasuuup=\d+@/g, '');
    href = href.replace(/&wasuuup=\d+@/g, '');
    
    if( href.indexOf('#') >= 0 )
    {
        parts = href.split('#');
        href  = parts[0];
    }
    
    if( href.indexOf('?') < 0 ) href = href + '?wasuuup=' + wasuuup() + '@';
    else                        href = href + '&wasuuup=' + wasuuup() + '@';
    
    href = href + '#comment_' + comment_id;
    
    location.href = href;
}

function spam_comment(id_comment, callback, trigger)
{
    change_comment_status(id_comment, 'spam', callback, trigger)
}

function delete_comment(id_comment, callback, trigger)
{
    change_comment_status(id_comment, 'trashed', callback, trigger)
}

function change_comment_status(id_comment, new_state, callback, trigger)
{
    var $trigger;
    if( trigger ) $trigger = $(trigger);
    else          $trigger = $('.comment_actions_container .comment_item[data-record-id="' + id_comment +'"]');
    
    var url = $_FULL_ROOT_PATH + '/comments/scripts/toolbox.php'
            + '?action=change_status'
            + '&id_comment=' + id_comment
            + '&new_status=' + new_state
            + '&wasuuup='    + wasuuup()
        ;
    
    $trigger.block(blockUI_smallest_params);
    $.get(url, function(response)
    {
        if( response != 'OK' )
        {
            alert(response);
            
            $trigger.unblock();
            return;
        }
        
        if( typeof callback == 'function' ) callback();
    });
}

function discard_comment_edit(trigger)
{
    $('#comments_edit_form').dialog('close');
}

$(document).ready(function()
{
    var $form = $('#comments_edit_form');
    if( $form.length > 0 )
    {
        var height = $(window).height();
        var width  = $(window).width();
        
        if( width  > 700 ) width  = 700;
        if( width  < 320 ) width  = 320;
        if( height > 590 ) height = 590;
        if( height < 320 ) height = 320;
        
        $form.dialog({
            modal:     true,
            autoOpen:  false,
            width:     width  - 20,
            height:    height - 20
        });
    }
});
