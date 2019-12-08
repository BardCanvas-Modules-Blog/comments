<?php
/**
 * Comments browser
 *
 * @package    BardCanvas
 * @subpackage comments
 * @author     Alejandro Caballero - lava.caballero@gmail.com
 *
 * @var template $template
 * @var account  $account
 */

use hng2_base\account;
use hng2_base\template;

include "../config.php";
include "../includes/bootstrap.inc";

if( ! $account->_exists )
{
    $template->page_contents_include = "contents/request_login.inc";
    $template->set_page_title($current_module->language->index->title);
    include "{$template->abspath}/admin.php";
    die();
}

$template->page_contents_include = "contents/index.inc";
$template->set_page_title($current_module->language->index->title);
include "{$template->abspath}/admin.php";
