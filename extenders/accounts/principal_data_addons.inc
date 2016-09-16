<?php
/**
 * Accounts extender - principal data addons
 *
 * @package    BardCanvas
 * @subpackage comments
 * @author     Alejandro Caballero - lava.caballero@gmail.com
 *
 * Globals:
 * @var module $current_module accounts
 * @var module $this_module    self
 */

use hng2_base\module;

global $row, $principal_data_addons, $modules;

if( empty($row->_last_comment_data) ) return;

list($post_id, $comment_id, $title, $date, $status) = explode("\t", $row->_last_comment_data);

$principal_data_addons[] = replace_escaped_vars(
    $this_module->language->accounts_nav_columns->principal_data,
    array('{$url}', '{$title}', '{$time}', '{$status}'),
    array(
        "{$config->full_root_path}/{$post_id}#comment_{$comment_id}",
        convert_emojis(make_excerpt_of($title)),
        $date == "0000-00-00 00:00:00" ? "&mdash;" : get_minimized_date($date),
        $this_module->language->statuses->{$status},
    )
);
