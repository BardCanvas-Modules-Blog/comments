<?php
/**
 * Accounts extender - browser data columns
 *
 * @package    BardCanvas
 * @subpackage comments
 * @author     Alejandro Caballero - lava.caballero@gmail.com
 *
 * Globals:
 * @var module $current_module accounts
 * @var module $this_module    self
 * @var object $row
 */

use hng2_base\module;

global $row;
?>
<td nowrap align="right">
    <?= empty($row->_comments_count) ? "&mdash;" : number_format($row->_comments_count) ?>
</td>
