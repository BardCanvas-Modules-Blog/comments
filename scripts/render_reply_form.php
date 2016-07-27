<?php
/**
 * Comments reply form
 * Called via ajax to avoid huge DOM trees for post replies
 *
 * @package    BardCanvas
 * @subpackage categories
 * @author     Alejandro Caballero - lava.caballero@gmail.com
 */

use hng2_modules\posts\post_record;

header("Content-Type: text/html; charset=utf-8");
include "../../config.php";
include "../../includes/bootstrap.inc";

$post = new post_record();

$this_module  = $current_module;
$ajax_calling = true;
include ABSPATH . "/comments/extenders/post_form.inc";
