<?php
/**
 * Triklet extender: sets subject and body when the new ticket form is invoked
 * via GET with the next arguments: action=report & type=comment & id=xxxxxxx
 * Note: this will only work when invoking the Triklet main page for logged in users
 *
 * @package    BardCanvas
 * @subpackage comments
 * @author     Alejandro Caballero - lava.caballero@gmail.com
 *
 * Trailing vars:
 * @var module     $this_module self (commments)
 *
 * Imported config globals:
 * • string @triklet:subject
 * • string @triklet:content
 * • array  @triklet:show_errors
 */

use hng2_base\module;
use hng2_modules\comments\comments_repository;

global $config;

if( empty($_GET["action"]) ) return;
if( empty($_GET["type"]) ) return;
if( $_GET["type"] != "comment" ) return;

if( empty($_GET["id"]) )
{
    $config->globals["@triklet:show_errors"][] = $this_module->language->reporting->id_not_set;
    
    return;
}

$repository = new comments_repository();
$comment    = $repository->get($_GET["id"]);

if( is_null($comment) )
{
    $config->globals["@triklet:show_errors"][] = $this_module->language->reporting->not_found;
    
    return;
}

if( in_array($comment->status, array("rejected", "spam", "trashed")) )
{
    $config->globals["@triklet:show_errors"][] = $this_module->language->reporting->already_processed;
    
    return;
}

if( in_array($comment->status, array("reviewing")) )
{
    $config->globals["@triklet:show_errors"][] = $this_module->language->reporting->in_review;
    
    return;
}

$post = $comment->get_post();

if( is_null($post) )
{
    $config->globals["@triklet:show_errors"][] = $this_module->language->reporting->post_deleted;
    
    return;
}

$config->globals["@triklet:subject"] = replace_escaped_vars(
    $this_module->language->reporting->subject,
    array('{$comment_id}', '{$post_title}', '{$post_author}'),
    array($comment->id_comment, $post->title, $post->author_display_name)
);

$config->globals["@triklet:content"] = replace_escaped_vars(
    $this_module->language->reporting->content,
    array('{$comment_author}', '{$date}', '{$comment}', '{$permalink}'),
    array(
        empty($comment->author_display_name)
            ? $comment->get_author()->get_processed_display_name()
            : $comment->author_display_name,
        $comment->creation_date . " (" . time_elapsed_string($comment->creation_date). ")",
        $comment->content,
        $comment->get_permalink(true)
    )
);
