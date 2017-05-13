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

use hng2_base\module;
use hng2_modules\posts\post_record;

global $post, $template, $language, $settings, $account;

$allow_new_comments = true;
if( $settings->get("modules:comments.avoid_anonymous") == "true" )
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
?>

<? include "{$template->abspath}/segments/comments_for_single_post.inc"; ?>

<? if( $settings->get("engine.recaptcha_public_key") == "" ): ?>
    
    <div class="content_frame">
        <div class="framed_content state_ko">
            <span class="fa fa-warning"></span>
            <?= $language->captcha_not_configured ?>
        </div>
    </div>
    
    <? return; ?>
<? endif; ?>

<? if( $post->allow_comments == 0 || ! $allow_new_comments ) return; ?>

<div id="post_new_comment_form" class="content_frame">
    <h1><?= $this_module->language->leave_a_comment ?></h1>
    <? include __DIR__ . "/post_form.inc"; ?>
</div>

<div id="post_comment_target" style="display: none;"></div>
