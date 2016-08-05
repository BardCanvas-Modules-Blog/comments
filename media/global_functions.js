
// Important: these need to be redefined once the document is ready.
var tinymce_comments_defaults = {};

function prepare_comment_reply(trigger)
{
    var $trigger  = $(trigger).closest('.trigger');
    var parent_id = $trigger.closest('.comment_entry').attr('data-id-comment');
    var $target   = $trigger.closest('.comment_reply').find('.target');
    var url       = $_FULL_ROOT_PATH + '/comments/scripts/render_reply_form.php';
    var id_post   = $('#post_comments').attr('data-post-id');
    var form_id   = 'comment_reply_' + parseInt(Math.random() * 1000000000000000);
    
    var params = {
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
        
        tinymce.init(tinymce_comments_defaults);
        
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
    
    if( href.indexOf('#') >= 0 )
    {
        parts = href.split('#');
        href  = parts[0];
    }
    
    if( href.indexOf('?') < 0 ) href = href + '?wasuuup=' + parseInt(Math.random() * 1000000000000000);
    else                        href = href + '&wasuuup=' + parseInt(Math.random() * 1000000000000000);
    
    href = href + '#comment_' + comment_id;
    
    location.href = href;
}

$(document).ready(function()
{
    tinymce_comments_defaults = $.extend({}, tinymce_defaults);
    
    tinymce_comments_defaults.toolbar  = 'bold italic strikethrough forecolor fontsizeselect removeformat | outdent indent | link';
    tinymce_comments_defaults.selector = '.tinymce_comments';
    
    if( tinymce_custom_toolbar_buttons.length > 0 )
        tinymce_comments_defaults.toolbar = tinymce_comments_defaults.toolbar + ' ' + tinymce_custom_toolbar_buttons.join(' ');
    tinymce_comments_defaults.toolbar = tinymce_comments_defaults.toolbar  + ' | fullscreen';
    
    if( $_CURRENT_USER_IS_ADMIN )
        tinymce_comments_defaults.toolbar = tinymce_comments_defaults.toolbar + ' | code';
    
    if( $_CURRENT_USER_LANGUAGE != "en" && $_CURRENT_USER_LANGUAGE != "en_US" )
        tinymce_comments_defaults.language = $_CURRENT_USER_LANGUAGE;
    
    tinymce.init(tinymce_comments_defaults);
    
    $('#post_comment').ajaxForm({
        target:          '#post_comment_target',
        beforeSerialize: prepare_comment_form_serialization,
        beforeSubmit:    prepare_comment_submission,
        success:         process_comment_submission
    })
});
