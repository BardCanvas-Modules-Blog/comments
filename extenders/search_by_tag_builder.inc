<?php
/**
 * Search extender: search by tag collection builder
 * Adds to the collection so it is properly displayed to the user
 *
 * @package    BardCanvas
 * @subpackage comments
 * @author     Alejandro Caballero - lava.caballero@gmail.com
 *
 * Trailing globals:
 * @var module[] $modules
 * @var module   $current_module search
 * @var module   $this_module    self
 */

use hng2_base\module;
use hng2_modules\comments\comments_repository;
use hng2_modules\posts\posts_repository;
use hng2_modules\search\search_by_tag_item;

global $config, $template;

$tag        = addslashes($template->get("current_tag"));
$repository = new comments_repository();
$count      = $repository->get_record_count(array(
    "id_comment in (select id_comment from comment_tags where comment_tags.tag = '$tag')"
));

if( $count == 0 ) return;

$comments = $repository->lookup(array(
    "id_comment in (select id_comment from comment_tags where comment_tags.tag = '$tag')"
), 30);

$collection = array();

$post_ids = array();
foreach($comments as $comment) $post_ids[] = $comment->id_post;
$post_ids = array_unique($post_ids);
$posts_repository = new posts_repository();
$posts = $posts_repository->get_multiple($post_ids);

foreach($comments as $comment)
{
    if( ! isset($posts[$comment->id_post]) ) continue;
    
    $post = $posts[$comment->id_post];
    
    $excerpt = make_excerpt_of($comment->content, $excerpt_length);
    if( empty($excerpt) ) $excerpt = "<span class='ellipsis'>...</span>";
    else                  $excerpt = convert_emojis($excerpt);
    
    $title = $post->title;
    
    $collection[] = new search_by_tag_item(array(
        "type"      => "",
        "url"       => "{$config->full_root_url}/{$post->id_post}#comment_{$comment->id_comment}",
        "title"     => $title,
        "excerpt"   => "[".time_today_string($comment->creation_date)."] " . $excerpt,
        "thumbnail" => ""
    ));
}

$key = trim($this_module->language->extenders->search_by_tag_builder->collection_heading);
$config->globals["search_by_tag_collections"][$key] = $collection;
$config->globals["search_by_tag_counts"][$key]      = $count;
