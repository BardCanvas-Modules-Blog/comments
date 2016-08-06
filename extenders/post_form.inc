<?php
/**
 * Posts extender: contents to add after post contents // posting form template!!!
 * It may be called from within a function or straight as an include from scripts/render_reply_form.php
 *
 * @package    BardCanvas
 * @subpackage comments
 * @author     Alejandro Caballero - lava.caballero@gmail.com
 *
 * Imported globals:
 * @var post_record $post
 * @var config      $config
 *
 * Trailing vars:
 * @var module $this_module    self (comments)
 * @var module $current_module posts|comments
 *                             
 * @var bool   $ajax_calling
 */

use hng2_base\config;
use hng2_base\module;
use hng2_modules\posts\post_record;

global $post, $template, $language, $settings, $account;

if( ! function_exists("recaptcha_get_html") ) include_once ABSPATH . "/lib/recaptcha-php-1.11/recaptchalib.php";
?>

<form name="post_comment" id="post_comment" method="post"
      action="<?= $config->full_root_path ?>/comments/scripts/add.php">
    
    <input type="hidden" name="id_post" value="<?= $post->id_post ?>">
    <input type="hidden" name="parent_comment" value="">
    
    <div class="tinymce_container">
        <textarea name="content" class="tinymce_comments"></textarea>
    </div>
    
    <? if( ! $account->_exists ): ?>
        
        <div class="field">
            <div class="caption"><?= $this_module->language->form->fields->name->caption ?></div>
            <div class="input"><input type="text" name="author_display_name"></div>
        </div>
        
        <div class="field">
            <div class="caption"><?= $this_module->language->form->fields->email->caption ?></div>
            <div class="input"><input type="email" name="author_email"></div>
        </div>
        
        <div class="field">
            <div class="caption"><?= $this_module->language->form->fields->author_url->caption ?></div>
            <div class="input"><input type="text" name="author_url"></div>
        </div>
        
        <div class="field">
            <div class="caption">
                    <span class='fa-pseudo-switch' data-input-name='save_details' data-value-on='true' data-value-off=''
                          onclick='toggle_fa_pseudo_switch(this);'>
                        <input type='hidden' name='save_details' value='true'>
                        <span class='toggler toggle-on  fa fa-toggle-on'  style=''></span>
                        <span class='toggler toggle-off fa fa-toggle-off' style='display: none'></span>
                    </span>
                <?= $this_module->language->form->fields->save->caption ?>
            </div>
        </div>
        
        <div class="field recaptcha_target" id="recaptcha_<?= uniqid() ?>"
              data-public-key="<?= $settings->get("engine.recaptcha_public_key") ?>">
            <? if( ! $ajax_calling ) echo recaptcha_get_html($settings->get("engine.recaptcha_public_key")); ?>
        </div>
    
    <? endif; ?>
    
    <p>
        <button type="submit">
            <span class="fa fa-play"></span>
            <?= $language->submit ?>
        </button>
        
        <? if($ajax_calling): ?>
            <button onclick="discard_comment_reply(this); return false">
                <span class="fa fa-ban"></span>
                <?= $language->cancel ?>
            </button>
        <? endif; ?>
    </p>
</form>
