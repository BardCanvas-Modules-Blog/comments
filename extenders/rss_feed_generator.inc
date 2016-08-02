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
use hng2_rss\channel;
use hng2_rss\item;

$channel->title       = "Título del canal";
$channel->description = "Descripción del canal";
$channel->link        = "http://www.google.com";

$item              = new item();
$item->title       = "El título";
$item->link        = "http://www.google.com";
$item->description = "Descripción";
$item->guid        = "http://www.google.com";

$channel->items[] = $item;
