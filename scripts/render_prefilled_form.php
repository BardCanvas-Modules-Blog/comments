<?php
/**
 * Comments reply form
 * Called via ajax to avoid huge DOM trees for post replies
 *
 * @package    BardCanvas
 * @subpackage categories
 * @author     Alejandro Caballero - lava.caballero@gmail.com
 * 
 * @var template $template
 * @var config   $config
 * 
 * $_REQUEST params:
 * @param parent_id
 * @param edit_comment
 * @param quote_parent
 */

use hng2_base\config;
use hng2_base\template;
use hng2_modules\comments\comments_repository;
use hng2_modules\posts\post_record;
use hng2_modules\posts\posts_repository;

header("Content-Type: text/html; charset=utf-8");
include "../../config.php";
include "../../includes/bootstrap.inc";

$repository       = new comments_repository();
$posts_repository = new posts_repository();
$post             = new post_record();

if( ! empty( $_REQUEST["parent_id"] ) )
{
    $parent = $repository->get($_REQUEST["parent_id"]);
    $post   = $posts_repository->get($parent->id_post);
    
    if( ! is_null($parent) )
    {
        $content = "";
        
        if($_REQUEST["quote"] == "true")
        {
            $author      = $parent->get_author();
            $author_link = $author->_exists
                         ? "<a href='{$config->full_root_url}/user/{$author->user_name}/'>{$author->get_processed_display_name()}</a>"
                         : $parent->author_display_name;
            
            $content  = "<p></p>";
            $content .= "<blockquote class='comment_quote'>";
            $content .= "<p>[{$parent->creation_date}] {$author_link}:</p>";
            $content .= $parent->content;
            $content .= "</blockquote>";
        }
        
        $template->set("prefilled_comment_content", $content);
        $template->set("parent_comment",            $parent->id_comment);
    }
}

if( empty($_REQUEST["edit_comment"])  )
{
    $template->set("id_comment",        "");
    $template->set("comment_form_mode", "reply");
}
else
{
    $comment = $repository->get($_REQUEST["edit_comment"]);
    $post    = $posts_repository->get($comment->id_post);
    
    if( is_null($comment) ) die($current_module->language->messages->comment_not_found);
    
    $template->set("prefilled_comment_content", $comment->content);
    $template->set("parent_comment",            $comment->parent_comment);
    $template->set("id_comment",                $comment->id_comment);
    $template->set("comment_form_mode",         "edit");
}

$this_module  = $current_module;
$template->set("ajax_calling", true);
include ABSPATH . "/comments/extenders/post_form.inc";
die();
