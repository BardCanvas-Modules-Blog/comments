<?php
/**
 * Search extender: add to tags cloud counts
 *
 * @package    BardCanvas
 * @subpackage comments
 * @author     Alejandro Caballero - lava.caballero@gmail.com
 *
 * Trailing vars:
 * @var module[]          $modules
 * @var module            $this_module    comments
 * @var template          $template       The caller
 * @var \SimpleXMLElement $widget
 * @var string            $seed
 * @var string            $data_key
 */

use hng2_base\module;
use hng2_base\template;
use hng2_modules\comments\comments_repository;

global $settings, $config;

$repository = new comments_repository();

$data = $repository->get_grouped_tag_counts(
    $config->globals["search_tags_cloud:boundary"],
    $settings->get("modules:search.tags_cloud_min_hits")
);

foreach( $data as $key => $count )
    $config->globals["search_tags_cloud:all_tags"][$key] += $count;
