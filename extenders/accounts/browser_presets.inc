<?php
/**
 * Accounts extender - browser presets
 *
 * @package    BardCanvas
 * @subpackage comments
 * @author     Alejandro Caballero - lava.caballero@gmail.com
 *
 * Globals:
 * @var module $current_module accounts
 * @var module $this_module    self
 * @var mixed  $order
 */

use hng2_base\module;

global $order;

list($sqlorder, $additional_select_columns) = $config->globals["accounts:nav/presettings_data"];

switch( $order )
{
    case "comments_count_asc":  $sqlorder = "_comments_count asc";  break;
    case "comments_count_desc": $sqlorder = "_comments_count desc"; break;
}

$additional_select_columns .= "
    , (select count(id_comment) from comments where comments.id_author = account.id_account ) as _comments_count
";

$config->globals["accounts:nav/presettings_data"] = array( $sqlorder, $additional_select_columns);
