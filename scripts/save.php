<?php
/**
 * Comments saver (for users or mods)
 *
 * @package    BardCanvas
 * @subpackage categories
 * @author     Alejandro Caballero - lava.caballero@gmail.com
 * 
 * @var account           $account
 * @var settings          $settings
 * @var \SimpleXMLElement $language
 * 
 * $_POST fields:
 * @param id_post
 * @param content
 */

use hng2_base\account;
use hng2_base\config;
use hng2_base\settings;
use hng2_modules\comments\comments_repository;
use hng2_modules\posts\posts_repository;

header("Content-Type: text/plain; charset=utf-8");
include "../../config.php";
include "../../includes/bootstrap.inc";
include "../../lib/recaptcha-php-1.11/recaptchalib.php";

if( empty($_POST["id_comment"]) ) die($current_module->language->messages->missing_comment_id);
if( empty($_POST["content"])    ) die($current_module->language->messages->message_cannot_be_empty);

$repository = new comments_repository();
$comment    = $repository->get($_POST["id_comment"]);

if( is_null($comment) ) die($current_module->language->messages->comment_not_found);

$old_comment = clone $comment;

if( $account->level < config::MODERATOR_USER_LEVEL && ! is_comment_editable($comment) )
{
    if( (int) $settings->get("modules:comments.time_allowed_for_editing_after_submission") > 0 )
        die( unindent($current_module->language->messages->comment_cannot_be_edited->with_timing) );
    else
        die( unindent($current_module->language->messages->comment_cannot_be_edited->without_timing) );
}

$comment->content = stripslashes($_POST["content"]);

$min_level = $settings->get("modules:comments.privileged_user_level");
if( empty($min_level) ) $min_level = config::MODERATOR_USER_LEVEL;
if( $account->level < $min_level )
{
    // Spam filters: links
    $links = $settings->get("module:comments.flag_for_review_on_link_amount");
    if( empty($links) ) $links = 2;
    $pattern = '@(https?://([-\w\.]+[-\w])+(:\d+)?(/([\w/_\.#-]*(\?\S+)?[^\.\s])?)?)@i';
    preg_match_all($pattern, $comment->content, $matches);
    if( ! empty($matches) )
    {
        $matches = $matches[0];
        foreach($matches as $index => $match)
            if( stristr($match, $config->full_root_url) !== false )
                unset($matches[$index]);
    
        if( count($matches) >= $links ) $comment->status = "reviewing";
    }
}

$tags = extract_hash_tags($comment->content);
$featured_posts_tag = $settings->get("modules:posts.featured_posts_tag");
if(
    $account->level < config::MODERATOR_USER_LEVEL
    && $settings->get("modules:posts.show_featured_posts_tag_everywhere") != "true"
    && ! empty($featured_posts_tag)
    && in_array($featured_posts_tag, $tags)
) {
    unset($tags[array_search($featured_posts_tag, $tags)]);
    $comment->content = str_replace("#$featured_posts_tag", $featured_posts_tag, $comment->content);
}

$media_items = array();
if( function_exists("extract_media_items") )
{
    $images = extract_media_items("image", $comment->content);
    $videos = extract_media_items("video", $comment->content);
    $media_items = array_merge($images, $videos);
}

$current_module->load_extensions("save_comment", "before_saving");
$repository->save($comment);
$current_module->load_extensions("save_comment", "after_saving");

if( $comment->status == "published" )
{
    $repository->set_media_items($media_items, $comment->id_comment);
    $repository->set_tags($tags, $comment->id_comment);
}

if( $comment->status == $old_comment->status )
    send_notification($account->id_account, "success", replace_escaped_vars(
        $current_module->language->notifications->saved_ok,
        '{$status}',
        $current_module->language->statuses->{$comment->status}
    ));
else
    send_notification($account->id_account, "success", replace_escaped_vars(
        $current_module->language->notifications->saved_with_status_change,
        array('{$old_status}', '{$new_status}'),
        array(
            $current_module->language->statuses->{$old_comment->status},
            $current_module->language->statuses->{$comment->status}
        )
    ));

if( $old_comment->status != $comment->status )
{
    $posts_repository = new posts_repository();
    $posts_repository->update_comments_count($comment->id_post);
}

echo "OK:{$comment->id_comment}";
