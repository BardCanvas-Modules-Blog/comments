<?php
/**
 * Media repository class extender: query builder for method delete_multiple_if_unused
 *
 * @package    BardCanvas
 * @subpackage comments
 * @author     Alejandro Caballero - lava.caballero@gmail.com
 */

global $config;
global $settings;

$prepared_ids = $config->globals["media_repository/delete_multiple_if_unused:ids"];

$config->globals["media_repository/delete_multiple_if_unused:query"]
    .= " and id_media not in ( select id_media from comment_media where id_media in({$prepared_ids}) ) ";
