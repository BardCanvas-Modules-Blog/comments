<?php
/**
 * Comments toolbox
 *
 * @package    BardCanvas
 * @subpackage comments
 * @author     Alejandro Caballero - lava.caballero@gmail.com
 *
 * @var account           $account
 * @var settings          $settings
 * @var \SimpleXMLElement $language
 * 
 * $_GET params:
 * @param string "action"     change_status|preview|untrash_for_review|empty_trash
 * @param string "new_status" trashed|published|rejected|spam|hidden
 * @param string "id_comment"
 */

use hng2_base\account;
use hng2_base\config;
use hng2_base\settings;
use hng2_media\media_repository;
use hng2_modules\comments\comments_repository;
use hng2_modules\posts\posts_repository;

header("Content-Type: text/plain; charset=utf-8");
include "../../config.php";
include "../../includes/bootstrap.inc";

if( ! in_array($_GET["action"], array("change_status", "preview", "untrash_for_review", "empty_trash")) )
    die($current_module->language->messages->toolbox->invalid_action);

if( $_GET["action"] != "empty_trash" && empty($_GET["id_comment"]) )
    die($current_module->language->messages->missing_comment_id);

$media_repository = new media_repository();
$posts_repository = new posts_repository();
$repository       = new comments_repository();

if($_GET["action"] == "empty_trash")
{
    if( ! $account->_is_admin ) die($current_module->language->messages->toolbox->action_not_allowed);
    $repository->empty_trash();
    die("OK");
}

$comment = $repository->get($_GET["id_comment"]);
if( is_null($comment) ) die($current_module->language->messages->comment_not_found);

if($_GET["action"] == "change_status")
{
    if( ! in_array($_GET["new_status"], array("trashed", "published", "rejected", "spam", "hidden")) )
        die($current_module->language->messages->toolbox->invalid_status);
    
    switch( $_GET["new_status"] )
    {
        case "published":
        {
            if($account->level < config::MODERATOR_USER_LEVEL)
                die($current_module->language->messages->toolbox->action_not_allowed);
            
            if( $comment->status == "published" ) die("OK");
            
            $res = $repository->change_status($comment->id_comment, "published");
            
            if( empty($res) ) die("OK");
            
            $posts_repository->update_comments_count($comment->id_post);
            
            $cuser_link   = $account->display_name; # "<a href='{$config->full_root_url}/user/{$account->user_name}'>{$account->display_name}</a>";
            $post         = $comment->get_post();
            $post_title   = is_null($post) ? "N/A" : $post->title;
            $comment_link = is_null($post) ? "" : ($post->get_permalink(true) . "#comment_" . $comment->id_comment);
            
            $author       = $comment->get_author();
            $author_link  = empty($comment->id_author)
                          ? $comment->author_display_name
                          : $author->display_name; # "<a href='{$config->full_root_url}/user/{$author->user_name}'>{$author->display_name}</a>";
            
            send_notification($account->id_account, "success", replace_escaped_vars(
                $current_module->language->messages->toolbox->published_ok,
                array('{$id}', '{$author}', '{$link}'),
                array($comment->id_comment, $author_link, $comment_link)
            ));
            
            if( ! empty($comment->id_author) && $comment->id_author != $account->id_account )
                send_notification($comment->id_author, "information", replace_escaped_vars(
                    $current_module->language->notifications->published_ok,
                    array('{$user}', '{$post_title}', '{$link}'),
                    array($cuser_link, $post_title, $comment_link)
                ));
            
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
            
            $repository->set_tags($tags, $comment->id_comment);
            $deletions = $repository->set_media_items($media_items, $comment->id_comment);
            
            die("OK");
            break;
        }
        case "rejected":
        {
            if($account->level < config::MODERATOR_USER_LEVEL)
                die($current_module->language->messages->toolbox->action_not_allowed);
            
            if( $comment->status == "rejected" ) die("OK");
            
            $res = $repository->change_status($comment->id_comment, "rejected");
            if( empty($res) ) die("OK");
            
            $posts_repository->update_comments_count($comment->id_post);
    
            $cuser_link   = $account->display_name; # "<a href='{$config->full_root_url}/user/{$account->user_name}'>{$account->display_name}</a>";
            $post         = $comment->get_post();
            $post_title   = is_null($post) ? "N/A" : $post->title;
            $comment_link = is_null($post) ? "" : ($post->get_permalink(true) . "#comment_" . $comment->id_comment);
            
            $author       = $comment->get_author();
            $author_link  = empty($comment->id_author)
                          ? $comment->author_display_name
                          : $author->display_name; # "<a href='{$config->full_root_url}/user/{$author->user_name}'>{$author->display_name}</a>";
            
            send_notification($account->id_account, "success", replace_escaped_vars(
                $current_module->language->messages->toolbox->rejected_ok,
                array('{$id}', '{$author}', '{$link}'),
                array($comment->id_comment, $author_link, $comment_link)
            ));
            
            if( ! empty($comment->id_author) && $comment->id_author != $account->id_account )
                send_notification($comment->id_author, "information", replace_escaped_vars(
                    $current_module->language->notifications->rejected_ok,
                    array('{$user}', '{$post_title}', '{$link}'),
                    array($cuser_link, $post_title, $comment_link)
                ));
            
            $repository->set_tags(array(), $comment->id_comment);
            $repository->set_media_items(array(), $comment->id_comment);
            
            die("OK");
            break;
        }
        case "trashed":
        {
            if($account->id_account != $comment->id_author && $account->level < config::MODERATOR_USER_LEVEL)
                die($current_module->language->messages->toolbox->action_not_allowed);
            
            if( $comment->status == "trashed" ) die("OK");
            
            $res = $repository->change_status($comment->id_comment, "trashed");
            if( empty($res) ) die("OK");
            
            $posts_repository->update_comments_count($comment->id_post);
    
            $cuser_link   = $account->display_name; # "<a href='{$config->full_root_url}/user/{$account->user_name}'>{$account->display_name}</a>";
            $post         = $comment->get_post();
            $post_title   = is_null($post) ? "N/A" : $post->title;
            $comment_link = is_null($post) ? "" : ($post->get_permalink(true) . "#comment_" . $comment->id_comment);
            
            $author       = $comment->get_author();
            $author_link  = empty($comment->id_author)
                          ? $comment->author_display_name
                          : $author->display_name; # "<a href='{$config->full_root_url}/user/{$author->user_name}'>{$author->display_name}</a>";
            
            if($comment->id_author != $account->id_account)
                # Notification to mod/admin deleting the comment
                send_notification($account->id_account, "success", replace_escaped_vars(
                    $current_module->language->messages->toolbox->deleted_from_others,
                    array('{$id}', '{$author}', '{$link}'),
                    array($comment->id_comment, $author_link, $comment_link)
                ));
            
            if( $comment->id_author == $account->id_account && $account->level < config::MODERATOR_USER_LEVEL )
                # Notification to moderators about a user deleting a comment
                broadcast_to_moderators("information", replace_escaped_vars(
                    $current_module->language->notifications->deleted_by_self,
                    array('{$author}',    '{$id}',                '{$user}',     '{$post_title}', '{$link}'),
                    array(  $author_link,   $comment->id_comment,   $cuser_link,   $post_title,     $comment_link)
                ));
            elseif( $comment->id_author != $account->id_account && ! empty($comment->id_author) )
                # Notification to the author of the comment about the deletion by a mod
                send_notification($comment->id_author, "information", replace_escaped_vars(
                    $current_module->language->notifications->deleted_by_others,
                    array('{$user}',     '{$id}',                '{$post_title}', '{$link}'),
                    array(  $cuser_link,   $comment->id_comment,   $post_title,     $comment_link)
                ));
            
            $repository->set_tags(array(), $comment->id_comment);
            $deletions = $repository->set_media_items(array(), $comment->id_comment);
            //if( is_array($media_deletions) && ! empty($media_deletions) )
            //    $media_repository->delete_multiple_if_unused($media_deletions);
            
            die("OK");
            break;
        }
        case "spam":
        {
            if( $comment->status == "spam" ) die("OK");
            
            $res = $repository->change_status($comment->id_comment, "spam");
            if( empty($res) ) die("OK");
            
            $posts_repository->update_comments_count($comment->id_post);
            
            $cuser_link   = $account->display_name; # "<a href='{$config->full_root_url}/user/{$account->user_name}'>{$account->display_name}</a>";
            $post         = $comment->get_post();
            $post_title   = is_null($post) ? "N/A" : $post->title;
            $comment_link = is_null($post) ? "" : ($post->get_permalink(true) . "#comment_" . $comment->id_comment);
            
            $author       = $comment->get_author();
            $author_link  = empty($comment->id_author)
                          ? $comment->author_display_name
                          : $author->display_name; # "<a href='{$config->full_root_url}/user/{$author->user_name}'>{$author->display_name}</a>";
            
            if( $comment->id_author != $account->id_account )
            {
                send_notification($account->id_account, "success", replace_escaped_vars(
                    $current_module->language->messages->toolbox->spammed_ok,
                    array('{$id}', '{$author}', '{$link}'),
                    array($comment->id_comment, $author_link, $comment_link)
                ));
                
                send_notification($comment->id_author, "warning", replace_escaped_vars(
                    $current_module->language->messages->toolbox->spammed_for_author,
                    array('{$link}', '{$id}', '{$post_title}', '{$reporter}'),
                    array($comment_link, $comment->id_comment, $post_title, $account->display_name)
                ));
            }
            
            if( $account->level < config::MODERATOR_USER_LEVEL )
                broadcast_to_moderators("information", replace_escaped_vars(
                    $current_module->language->notifications->spammed,
                    array('{$author}', '{$id}', '{$user}', '{$post_title}', '{$link}'),
                    array($author_link, $comment->id_comment, $cuser_link, $post_title, $comment_link)
                ));
            
            $repository->set_tags(array(), $comment->id_comment);
            $repository->set_media_items(array(), $comment->id_comment);
            
            die("OK");
            break;
        }
        case "hidden":
        {
            if($account->level < config::MODERATOR_USER_LEVEL)
                die($current_module->language->messages->toolbox->action_not_allowed);
            
            if( $comment->status == "hidden" ) die("OK");
            
            $res = $repository->change_status($comment->id_comment, "hidden");
            if( empty($res) ) die("OK");
            
            $posts_repository->update_comments_count($comment->id_post);
            
            //$repository->set_tags(array(), $comment->id_comment);
            //$deletions = $repository->set_media_items(array(), $comment->id_comment);
            //if( is_array($media_deletions) && ! empty($media_deletions) )
            //    $media_repository->delete_multiple_if_unused($media_deletions);
            
            die("OK");
            break;
        }
    }
}

if($_GET["action"] == "untrash_for_review")
{
    if($account->level < config::MODERATOR_USER_LEVEL)
        die($current_module->language->messages->toolbox->action_not_allowed);
    
    if( $comment->status == "published" ) die("OK");
    if( $comment->status == "reviewing" ) die("OK");
    
    $res = $repository->change_status($comment->id_comment, "reviewing");
    
    die("OK");
}

if($_GET["action"] == "preview")
{
    $template->page_contents_include = "contents/preview_comment.inc";
    include "{$template->abspath}/embeddable.php";
    die();
}

die($language->errors->invalid_call);
