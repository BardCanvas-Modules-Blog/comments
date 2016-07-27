<?php
/**
 * Posts extender: prebuild comments for home posts - CALLED WITHIN A FUNCTION!
 * Called within posts_repository::get_for_home()
 * Sets the list of all comments for all posts listed in the home page
 * as a template variable for further handling by the specific extensions
 *
 * @package    BardCanvas
 * @subpackage comments
 * @author     Alejandro Caballero - lava.caballero@gmail.com
 *
 * Trailing vars:
 * @var module          $this_module self (commments)
 *
 * Imported globals:
 * @var home_posts_data $home_posts
 * @var template        $template
 */

use hng2_base\module;
use hng2_base\template;
use hng2_modules\comments\comments_repository;
use hng2_modules\posts\home_posts_data;

global $config, $settings, $template;

if( $settings->get("modules:comments.show_in_indexes") != "true" ) return;

$home_posts = $config->globals["home_posts"];
unset( $config->globals["home_posts"] );

$all_post_ids = array();
foreach($home_posts->posts as $post) $all_post_ids[] = $post->id_post;

$repository = new comments_repository();
$res        = $repository->get_for_multiple_posts($all_post_ids);

foreach($res as $post_id => $comments)
    $template->set("comments_for_listed_post[{$post_id}]", $comments);
