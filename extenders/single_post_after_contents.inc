<?php
/**
 * Posts extender: contents to add after post contents
 * Called within a function, so no globals behind
 *
 * @package    BardCanvas
 * @subpackage comments
 * @author     Alejandro Caballero - lava.caballero@gmail.com
 * 
 * Imported globals:
 * @var post_record $post
 *
 * Trailing vars:
 * @var module $this_module    self (comments)
 * @var module $current_module posts
 */

use hng2_base\config;
use hng2_base\module;
use hng2_modules\posts\post_record;

global $post, $template, $language, $settings, $account, $config;

# If someone is overriding us...
if( $config->globals["modules:comments.avoid_extension:posts/front/single_post_after_contents"] ) return;

if( $post->status != "published" && $post->comments_count == 0 ) return;

$allow_new_comments = true;
if( $post->status != "published" )
{
    $allow_new_comments = false;
}
elseif( $settings->get("modules:comments.avoid_anonymous") == "true" && $account->level < config::NEWCOMER_USER_LEVEL )
{
    $allow_new_comments = false;
}
else
{
    $days = $settings->get("modules:comments.disable_new_after");
    if( ! empty($days) && ! empty($post->publishing_date) && $post->publishing_date != "0000-00-00 00:00:00" )
    {
        $boundary = date("Y-m-d H:i:s", strtotime("$post->publishing_date + $days days"));
        if( date("Y-m-d H:i:s") > $boundary ) $allow_new_comments = false;
    }
}

include "{$template->abspath}/segments/comments_for_single_post.inc";


if( $post->allow_comments == 0 || ! $allow_new_comments )
{
    if( ! $account->_exists
        && $settings->get("modules:comments.avoid_anonymous") == "true"
        && $settings->get("modules:accounts.register_enabled") == "true" ) echo "
        <div class='content_frame framed_content state_highlight aligncenter' style='padding: 20px'>
            <i class='fa fa-info-circle'></i>
            {$this_module->language->messages->register_to_comment}
        </div>
    ";
    
    return;
}
?>

<div id="post_new_comment_form" class="content_frame">
    <h1><?= $this_module->language->leave_a_comment ?></h1>
    <? include __DIR__ . "/post_form.inc"; ?>
</div>

<div id="post_comment_target" style="display: none;"></div>
