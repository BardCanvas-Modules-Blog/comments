<?php
/**
 * Posts extender: contents to add after post excerpt on indexes
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
use hng2_modules\comments\comments_repository;
use hng2_modules\posts\post_record;

global $post, $template, $language, $settings, $account;

if( $settings->get("modules:comments.show_in_indexes") != "true" ) return;

$repository  = new comments_repository();
$find_params = $repository->build_find_params_for_post($post->id_post);
$comments    = $repository->find($find_params->where, $find_params->limit, $find_params->offset, $find_params->order);

if( count($comments) == 0 ) return;

$comments = array_reverse($comments);
include "{$template->abspath}/segments/comments_for_index_post.inc";
