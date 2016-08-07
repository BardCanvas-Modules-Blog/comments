<?php
/**
 * RSS feed generator extender
 *
 * @package    BardCanvas
 * @subpackage comments
 * @author     Alejandro Caballero - lava.caballero@gmail.com
 *
 * Globals:
 * @var module   $current_module self
 * @var module   $this_module    self
 * 
 * Trailing vars (significant):
 * @var channel  $channel
 * @var template $template
 * @var string   $handle         Handle coming via GET
 * @var string   $handle_pattern The pattern as specified in the module_info.xml declaration
 * @var array    $handle_matches Matches of the handle in the pattern
 */

use hng2_base\module;
use hng2_base\template;
use hng2_modules\comments\comments_repository;
use hng2_rss\channel;
use hng2_rss\item;

$channel->title       = $this_module->language->rss_feed_generator->title;
$channel->description = $this_module->language->rss_feed_generator->description;
$channel->link        = "{$config->full_root_url}/" . ($handle == "_none_" ? "" : "{$handle}/") . "feed";

$channel->title = replace_escaped_vars(
    $channel->title,
    array('{$website_name}'),
    array($settings->get("engine.website_name"))
);

$channel->description = replace_escaped_vars(
    $channel->description,
    array('{$website_name}'),
    array($settings->get("engine.website_name"))
);

$repository = new comments_repository();
$records    = $repository->lookup(array(), 0, 0, "");

if( count($records) == 0 ) return;

foreach($records as $row)
{
    $item              = new item();
    $item->title       = make_excerpt_of(html_entity_decode($row->content, null, "UTF-8"));
    $item->link        = "{$config->full_root_url}/{$row->id_post}#comment_{$row->id_comment}";
    $item->description = strip_tags(html_entity_decode($row->content, null, "UTF-8"));
    $item->guid        = "{$config->full_root_url}/{$row->id_post}#comment_{$row->id_comment}";
    
    $channel->items[] = $item;
}
