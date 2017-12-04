<?php
/**
 * Contact extender: redirects to triklet if it is enabled, allowed to override contents
 * and receiving via GET with the next arguments: action=report & type=comment & id=xxxxxxx
 *
 * @package    BardCanvas
 * @subpackage comments
 * @author     Alejandro Caballero - lava.caballero@gmail.com
 *
 * Trailing vars:
 * @var module  $current_module contact
 * @var module  $this_module    self (comments)
 */

use hng2_base\module;

global $config, $modules, $settings, $account, $language;

if( empty($_GET["action"]) ) return;
if( empty($_GET["type"]) ) return;
if( $_GET["type"] != "comment" ) return;
if( ! $account->_exists ) return;
if( ! isset($modules["triklet"]) ) return;
if( $settings->get("modules:comments.use_triklet_for_reporting") != "true" ) return;
if( $config->globals["prevent_triklet_pre_start_redirections"] ) return;

$url = "{$modules["triklet"]->get_url()}/?action={$_GET["action"]}&type={$_GET["type"]}&id={$_GET["id"]}&hide_welcome=true&show_form=true#triklet_ticket_form";
header("Location: $url");
die("<a href='$url'>{$language->click_here_to_continue}</a>");
