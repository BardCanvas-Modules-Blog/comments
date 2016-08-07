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
 */

use hng2_base\config;
use hng2_base\template;
use hng2_modules\comments\comments_repository;
use hng2_modules\posts\post_record;

header("Content-Type: text/html; charset=utf-8");
include "../../config.php";
include "../../includes/bootstrap.inc";

$post = new post_record();

if( ! empty( $_REQUEST["parent_id"] ) )
{
    $repository  = new comments_repository();
    $parent      = $repository->get($_REQUEST["parent_id"]);
    
    if( ! is_null($parent) )
    {
        //$author      = $parent->get_author();
        //$author_link = $author->_exists
        //    ? "<a href='{$config->full_root_url}/user/{$author->user_name}/'>{$author->get_processed_display_name()}</a>"
        //    : $parent->author_display_name; 
        
        $content     = "<p></p>";
        //$content    .= "<p>[{$parent->creation_date}] {$author_link}:</p>";
        $content    .= "<blockquote class='comment_quote'>{$parent->content}</blockquote>";
        $template->set("prefilled_comment_content", $content);
    }
}

$this_module  = $current_module;
$template->set("ajax_calling", true);
include ABSPATH . "/comments/extenders/post_form.inc";
