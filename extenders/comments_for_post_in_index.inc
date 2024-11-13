<?php
/**
 * Posts extender: contents to add after post excerpt on indexes
 * Called within a function, so no globals behind
 * IMPORTANT: requires "post_comments" template variable to be prebuilt!
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
use hng2_modules\comments\comment_record;
use hng2_modules\posts\post_record;

global $post, $template, $language, $settings, $account;

if( $settings->get("modules:comments.show_in_indexes") != "true" ) return;

/** @var comment_record[] $comments */
$comments = $template->get("comments_for_listed_post[{$post->id_post}]");

if( ! is_array($comments) ) return;
if( count($comments) == 0 ) return;

$comments = array_reverse($comments);
include "{$template->abspath}/segments/comments_for_post_in_index.inc";
