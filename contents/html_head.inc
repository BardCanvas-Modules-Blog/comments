<?php
/**
 * Styles and scripts for the module
 *
 * @package    BardCanvas
 * @subpackage comments
 * @author     Alejandro Caballero - lava.caballero@gmail.com
 *
 * @var module   $this_module
 * @var template $template;
 */

use hng2_base\module;
use hng2_base\template;

if( $template->layout != "main" ) return;
?>

<!-- comments global functions -->

<script type="text/javascript" src="<?= $config->full_root_path ?>/<?= $this_module->name ?>/media/global_functions~v<?=$config->scripts_version?>.js"></script>
<script type="text/javascript" src="//www.google.com/recaptcha/api/js/recaptcha_ajax.js"></script>
