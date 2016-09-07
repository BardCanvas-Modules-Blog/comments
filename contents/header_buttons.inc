<?php
/**
 * Comments header buttons
 *
 * @package    BardCanvas
 * @subpackage commets
 * @author     Alejandro Caballero - lava.caballero@gmail.com
 * 
 * @var config   $config
 * @var account  $account
 * @var template $template
 */

use hng2_base\account;
use hng2_base\config;
use hng2_base\template;

if( ! $account->_exists ) return;

$added_class = $current_module->name == $this_module->name ? "current" : "";

$icon = empty($this_module->language->icon) ? "" : ("<span class='fa " . trim($this_module->language->icon) . "'></span>");

$template->add_menu_item(
    $this_module->language->record_nav->menu_caption,
    "
    <a class='main_menu_item {$added_class}' data-module='comments' href='{$config->full_root_path}/comments/'>
        $icon {$this_module->language->record_nav->menu_caption}
    </a>
    "
);
