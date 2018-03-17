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

$action = "add.php";

$form_mode = $template->get("comment_form_mode");
if( $form_mode == "edit" ) $action = "save.php";

$author_display_name = "";
if( ! empty($_COOKIE["{$config->website_key}_comments_dn"]) )
    $author_display_name = decrypt($_COOKIE["{$config->website_key}_comments_dn"], $config->encryption_key);

$author_email = "";
if( ! empty($_COOKIE["{$config->website_key}_comments_ae"]) )
    $author_email = decrypt($_COOKIE["{$config->website_key}_comments_ae"], $config->encryption_key);

$author_url = "";
if( ! empty($_COOKIE["{$config->website_key}_comments_au"]) )
    $author_url = decrypt($_COOKIE["{$config->website_key}_comments_au"], $config->encryption_key);
?>

<form name="post_comment" id="post_comment" method="post"
      action="<?= $config->full_root_path ?>/comments/scripts/<?= $action ?>">
    
    <!-- Autofilled on main pages -->
    <input type="hidden" name="id_comment"     value="<?= $template->get("id_comment") ?>">
    <input type="hidden" name="id_post"        value="<?= $post->id_post ?>">
    <input type="hidden" name="parent_comment" value="<?= $template->get("parent_comment") ?>">
    <input type="hidden" name="prevent_reopen" value="true">
    
    <div class="field tinymce_container">
        <textarea name="content" class="tinymce"><?= $template->get("prefilled_comment_content") ?></textarea>
    </div>
    
    <? if( ! $account->_exists ): ?>
        
        <div class="field">
            <div class="caption"><?= $this_module->language->form->fields->name->caption ?></div>
            <div class="input"><input type="text" name="author_display_name" value="<?= $author_display_name ?>"></div>
        </div>
        
        <div class="field">
            <div class="caption"><?= $this_module->language->form->fields->email->caption ?></div>
            <div class="input"><input type="email" name="author_email" value="<?= $author_email ?>"></div>
        </div>
        
        <div class="field">
            <div class="caption"><?= $this_module->language->form->fields->author_url->caption ?></div>
            <div class="input"><input type="text" name="author_url" value="<?= $author_url ?>"></div>
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
        
        <? if( $settings->get("engine.recaptcha_private_key") != "" && $settings->get("engine.recaptcha_public_key") ): ?>
            <div class="field recaptcha_target" id="recaptcha_<?= uniqid() ?>"
                  data-public-key="<?= $settings->get("engine.recaptcha_public_key") ?>">
                <div class='g-recaptcha' data-sitekey='<?= $settings->get("engine.recaptcha_public_key") ?>'></div>
            </div>
        <? endif; ?>
        
    <? endif; ?>
    
    <p>
        <button type="submit">
            <span class="fa fa-play"></span>
            <?= $language->submit ?>
        </button>
        
        <? if($template->get("ajax_calling")): ?>
            <? if( $template->get("comment_form_mode") == "reply" ): ?>
                <button onclick="discard_comment_reply(this); return false">
                    <span class="fa fa-ban"></span>
                    <?= $language->cancel ?>
                </button>
            <? elseif( $template->get("comment_form_mode") == "edit" ): ?>
                <button onclick="discard_comment_edit(); return false">
                    <span class="fa fa-ban"></span>
                    <?= $language->cancel ?>
                </button>
            <? endif; ?>
        <? endif; ?>
    </p>
</form>
