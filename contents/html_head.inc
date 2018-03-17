<?php
/**
 * Styles and scripts for the module
 *
 * @package    BardCanvas
 * @subpackage comments
 * @author     Alejandro Caballero - lava.caballero@gmail.com
 *
 * @var config   $config
 * @var module   $this_module
 * @var template $template;
 */

use hng2_base\config;
use hng2_base\module;
use hng2_base\template;
?>

<!-- comments global functions -->

<script type="text/javascript">
    var comment_reply_management_type = 'inline';
    var comment_reply_dialog_callback = null;
    var comment_edit_dialog_callback  = null;
</script>
<script type="text/javascript" src="<?= $config->full_root_path ?>/<?= $this_module->name ?>/media/global_functions~v1.12.0-1.js"></script>
<script src='https://www.google.com/recaptcha/api.js'></script>

<? if( $account->_exists ): ?>
    <script type="text/javascript" src="<?= $config->full_root_path ?>/<?= $this_module->name ?>/media/user_functions~v1.12.0-1.js"></script>
<? endif; ?>

<? if( $account->level >= config::MODERATOR_USER_LEVEL ): ?>
    <script type="text/javascript" src="<?= $config->full_root_path ?>/<?= $this_module->name ?>/media/mod_functions~v1.12.0-1.js"></script>
<? endif; ?>
