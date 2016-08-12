<?php
/**
 * Comments adder (for users)
 *
 * @package    BardCanvas
 * @subpackage categories
 * @author     Alejandro Caballero - lava.caballero@gmail.com
 *
 * @var account           $account
 * @var settings          $settings
 * @var \SimpleXMLElement $language
 * 
 * $_GET params:
 * @param string "action"     change_status|preview
 * @param string "new_status" trashed|published|rejected
 * @param string "id_comment"
 */

use hng2_base\account;
use hng2_base\config;
use hng2_base\settings;
use hng2_modules\comments\comments_repository;

header("Content-Type: text/plain; charset=utf-8");
include "../../config.php";
include "../../includes/bootstrap.inc";

if( ! in_array($_GET["action"], array("change_status", "preview")) ) die($current_module->language->messages->toolbox->invalid_action);

if( empty($_GET["id_comment"]) ) die($current_module->language->messages->missing_comment_id);

$repository = new comments_repository();
$comment = $repository->get($_GET["id_comment"]);
if( is_null($comment) ) die($current_module->language->messages->comment_not_found);

if($_GET["action"] == "change_status")
{
    if( ! in_array($_GET["new_status"], array("trashed", "published", "rejected", "spam")) )
        die($current_module->language->messages->toolbox->invalid_status);
    
    switch( $_GET["new_status"] )
    {
        case "published":
            
            if($account->level < config::MODERATOR_USER_LEVEL)
                die($current_module->language->messages->toolbox->action_not_allowed);
            
            if( $comment->status == "published" ) die("OK");
            
            $res = $repository->change_status($comment->id_comment, "published");
            if( empty($res) ) die("OK"); 
            
            $cuser_link   = "<a href='{$config->full_root_url}/user/{$account->user_name}'>{$account->display_name}</a>";
            $post         = $comment->get_post();
            $post_title   = $post->title;
            $comment_link = $post->get_permalink(true) . "#comment_" . $comment->id_comment;
            
            $author       = $comment->get_author();
            $author_link  = empty($comment->id_author)
                          ? $comment->author_display_name
                          : "<a href='{$config->full_root_url}/user/{$author->user_name}'>{$author->display_name}</a>";
            
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
            
            die("OK");
            break;
        
        case "rejected":
            
            if($account->level < config::MODERATOR_USER_LEVEL)
                die($current_module->language->messages->toolbox->action_not_allowed);
            
            if( $comment->status == "rejected" ) die("OK");
            
            $res = $repository->change_status($comment->id_comment, "rejected");
            if( empty($res) ) die("OK");
            
            $cuser_link   = "<a href='{$config->full_root_url}/user/{$account->user_name}'>{$account->display_name}</a>";
            $post         = $comment->get_post();
            $post_title   = $post->title;
            $comment_link = $post->get_permalink(true) . "#comment_" . $comment->id_comment;
            
            $author       = $comment->get_author();
            $author_link  = empty($comment->id_author)
                          ? $comment->author_display_name
                          : "<a href='{$config->full_root_url}/user/{$author->user_name}'>{$author->display_name}</a>";
            
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
            
            die("OK");
            break;
        
        case "trashed":
            
            if($account->id_account != $comment->id_author && $account->level < config::MODERATOR_USER_LEVEL)
                die($current_module->language->messages->toolbox->action_not_allowed);
            
            if( $comment->status == "trashed" ) die("OK");
            
            $res = $repository->change_status($comment->id_comment, "trashed");
            if( empty($res) ) die("OK");
            
            $cuser_link   = "<a href='{$config->full_root_url}/user/{$account->user_name}'>{$account->display_name}</a>";
            $post         = $comment->get_post();
            $post_title   = $post->title;
            $comment_link = $post->get_permalink(true) . "#comment_" . $comment->id_comment;
            
            $author       = $comment->get_author();
            $author_link  = empty($comment->id_author)
                ? $comment->author_display_name
                : "<a href='{$config->full_root_url}/user/{$author->user_name}'>{$author->display_name}</a>";
            
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
            
            die("OK");
            break;
        
        case "spam":
            
            if( $comment->status == "spam" ) die("OK");
            
            $res = $repository->change_status($comment->id_comment, "spam");
            if( empty($res) ) die("OK");
            
            $cuser_link   = "<a href='{$config->full_root_url}/user/{$account->user_name}'>{$account->display_name}</a>";
            $post         = $comment->get_post();
            $post_title   = $post->title;
            $comment_link = $post->get_permalink(true) . "#comment_" . $comment->id_comment;
            
            $author       = $comment->get_author();
            $author_link  = empty($comment->id_author)
                ? $comment->author_display_name
                : "<a href='{$config->full_root_url}/user/{$author->user_name}'>{$author->display_name}</a>";
            
            if( $comment->id_author != $account->id_account )
                send_notification($account->id_account, "success", replace_escaped_vars(
                    $current_module->language->messages->toolbox->spammed_ok,
                    array('{$id}', '{$author}', '{$link}'),
                    array($comment->id_comment, $author_link, $comment_link)
                ));
            
            if( $account->level < config::MODERATOR_USER_LEVEL )
                broadcast_to_moderators("information", replace_escaped_vars(
                    $current_module->language->notifications->spammed,
                    array('{$author}', '{$id}', '{$user}', '{$post_title}', '{$link}'),
                    array($author_link, $comment->id, $cuser_link, $post_title, $comment_link)
                ));
            
            die("OK");
            break;
        
        # end cases
    }
}

if($_GET["action"] == "preview")
{
    $template->page_contents_include = "contents/preview_comment.inc";
    include "{$template->abspath}/embeddable.php";
    die();
}

die($language->errors->invalid_call);
