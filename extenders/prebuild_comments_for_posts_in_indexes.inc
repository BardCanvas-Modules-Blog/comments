<?php
/**
 * Posts extender: prebuild comments for posts in indexes - CALLED WITHIN A FUNCTION!
 * Called within posts_repository, all indexing methods
 * Sets the list of all comments for all posts listed in an index
 * as a template variable for further handling by the specific extensions
 *
 * @package    BardCanvas
 * @subpackage comments
 * @author     Alejandro Caballero - lava.caballero@gmail.com
 *
 * Trailing vars:
 * @var module     $this_module self (commments)
 *
 * Imported globals:
 * @var posts_data $posts_data
 * @var template   $template
 */

use hng2_base\module;
use hng2_base\template;
use hng2_modules\comments\comments_repository;
use hng2_modules\posts\posts_data;

global $config, $settings, $template, $mem_cache;

if( $settings->get("modules:comments.show_in_indexes") != "true" ) return;

$posts_data = $config->globals["posts_data"];
unset( $config->globals["posts_data"] );

$all_post_ids = array();
foreach($posts_data->posts as $post) $all_post_ids[] = $post->id_post;

$mem_key = "comments_for_posts_in_indexes/" . md5(implode(",", $all_post_ids));
$cached  = $mem_cache->get($mem_key);
if( is_array($cached) )
{
    foreach($cached as $post_id => $comments)
        $template->set("comments_for_listed_post[{$post_id}]", $comments);
    
    return;
}
if( $cached == "none" ) return;

$repository = new comments_repository();
$res        = $repository->get_for_multiple_posts($all_post_ids);

if( empty($res) )
{
    $mem_cache->set($mem_key, "none", 0, 60*5);
    
    return;
}

$mem_cache->set($mem_key, $res, 0, 60*5);
foreach($res as $post_id => $comments)
    $template->set("comments_for_listed_post[{$post_id}]", $comments);
