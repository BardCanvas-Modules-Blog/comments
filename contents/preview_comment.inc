<?php
/**
 * Single comment preview
 *
 * @package    BardCanvas
 * @subpackage comments
 * @author     Alejandro Caballero - lava.caballero@gmail.com
 *
 * @var config   $config
 * @var settings $settings
 * @var template $template
 * @var module   $this_module    self (comments)
 * @var module   $current_module posts
 *
 * Trailing vars:
 * @var comment_record $comment
 */

use hng2_base\config;
use hng2_base\module;
use hng2_base\settings;
use hng2_base\template;
use hng2_modules\comments\comment_record;

$author     = $comment->get_author();
$avatar_url = $author->get_avatar_url();
$level      = 1;
?>

<div id="post_comments" data-post-id="<?= $comment->id_post ?>">
    
    <div class="comment_entry single_preview" id="comment_<?= $comment->id_comment ?>" data-id-comment="<?= $comment->id_comment ?>"
         data-parent-comment="<?= $comment->parent_comment ?>" data-indent-level="<?= $level ?>">
        
        <div class="comment_wrapper">
            <div class="meta_box clearfix">
                <img class="user_avatar" src="<?= $avatar_url ?>">
                
                <div class="meta_section upper">
                    <? if($author->_exists): ?>
                        <a class="meta_field" href="<?= $config->full_root_url ?>/user/<?= $author->user_name ?>/">
                            <span class="fa fa-user fa-fw"></span>
                            <?= $author->get_processed_display_name() ?>
                        </a>
                    <? else: ?>
                        <span class="meta_field">
                            <span class="fa fa-user fa-fw"></span>
                            <?= $comment->author_display_name ?>
                        </span>
                    <? endif; ?>
                </div>
                <div class="meta_section">
                    <span class="meta_field dimmed">
                        <span class="fa fa-calendar fa-fw"></span>
                        <?= time_elapsed_string($comment->creation_date) ?>
                    </span>
                </div>
            </div>
            
            <div class="content">
                <?= $comment->get_processed_content() ?>
            </div>
        </div>
        
    </div>
    
</div>
