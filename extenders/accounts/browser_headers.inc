<?php
/**
 * Accounts extender - browser header columns
 *
 * @package    BardCanvas
 * @subpackage comments
 * @author     Alejandro Caballero - lava.caballero@gmail.com
 *
 * Globals:
 * @var module         $current_module accounts
 * @var module         $this_module    self
 * @var record_browser $browser
 */

use hng2_base\module;
use hng2_tools\record_browser;

global $browser;

$headers = $config->globals["accounts:nav/additional_headers"];

/** @var \SimpleXMLElement $columns */
$columns = $this_module->language->accounts_nav_columns;
$added   = $browser->build_table_header($columns);

$config->globals["accounts:nav/additional_headers"] = array_merge($headers, $added);
