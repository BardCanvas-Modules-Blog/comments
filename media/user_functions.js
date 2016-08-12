
function reply_comment(id_comment)
{
    
}

function edit_comment(id_comment)
{
    
}

function spam_comment(id_comment, callback)
{
    
}

function delete_comment(id_comment, callback)
{
    change_comment_status(id_comment, 'trashed', callback)
}

function change_comment_status(id_comment, new_state, callback)
{
    var $trigger = $('.comment_actions_container .comment_item[data-record-id="' + id_comment +'"]');
    var url      = $_FULL_ROOT_PATH + '/comments/scripts/toolbox.php'
            + '?action=change_status'
            + '&id_comment=' + id_comment
            + '&new_status=' + new_state
            + '&wasuuup='    + parseInt(Math.random() * 1000000000000000)
        ;
    
    $trigger.block(blockUI_medium_params);
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
