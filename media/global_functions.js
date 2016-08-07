
function prepare_comment_reply(trigger)
{
    var $trigger  = $(trigger).closest('.trigger');
    var parent_id = $trigger.closest('.comment_entry').attr('data-id-comment');
    var $target   = $trigger.closest('.comment_reply').find('.target');
    var url       = $_FULL_ROOT_PATH + '/comments/scripts/render_reply_form.php';
    var id_post   = $('#post_comments').attr('data-post-id');
    var form_id   = 'comment_reply_' + parseInt(Math.random() * 1000000000000000);
    
    var params = {
        parent_id: parent_id,
        wasuuup: parseInt(Math.random() * 1000000000000000)
    };
    
    $target.load(url, params, function()
    {
        var $form = $target.find('form');
        
        $form.attr('name', form_id);
        $form.attr('id',   form_id);
        $form.find('input[name="id_post"]').val(id_post);
        $form.find('input[name="parent_comment"]').val(parent_id);
        
        var recaptcha_id = $form.find('.recaptcha_target').attr('id');
        var public_key   = $form.find('.recaptcha_target').attr('data-public-key');
        
        Recaptcha.create(public_key, recaptcha_id);
        
        tinymce.init(tinymce_defaults);
        
        $trigger.hide();
        $('#post_new_comment_form').hide();
        
        $form.ajaxForm({
            target:          '#post_comment_target',
            beforeSerialize: prepare_comment_form_serialization,
            beforeSubmit:    prepare_comment_submission,
            success:         process_comment_submission
        })
    });
}

function discard_comment_reply(trigger)
{
    var $source = $(trigger).closest('.comment_reply');
    $source.find('.target').html('');
    $source.find('.trigger').show();
    
    var $form        = $('#post_new_comment_form');
    var recaptcha_id = $form.find('.recaptcha_target').attr('id');
    var public_key   = $form.find('.recaptcha_target').attr('data-public-key');
    Recaptcha.create(public_key, recaptcha_id);
    $form.show();
}

function prepare_comment_form_serialization($form)
{
    $form.find('textarea[class*="tinymce"]').each(function()
    {
        var id      = $(this).attr('id');
        var editor  = tinymce.get(id);
        var content = editor.getContent();
        $(this).val( content );
    });
}

function prepare_comment_submission(data, $form)
{
    $form.block(blockUI_default_params);
}

function process_comment_submission(response, status, xhr, $form)
{
    if( response.indexOf('OK') < 0 )
    {
        alert( response );
        $form.unblock();
    
        Recaptcha.reload();
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
    
    if( href.indexOf('?') < 0 ) href = href + '?wasuuup=' + parseInt(Math.random() * 1000000000000000) + '@';
    else                        href = href + '&wasuuup=' + parseInt(Math.random() * 1000000000000000) + '@';
    
    href = href + '#comment_' + comment_id;
    
    location.href = href;
}

$(document).ready(function()
{
    $('#post_comment').ajaxForm({
        target:          '#post_comment_target',
        beforeSerialize: prepare_comment_form_serialization,
        beforeSubmit:    prepare_comment_submission,
        success:         process_comment_submission
    })
});
