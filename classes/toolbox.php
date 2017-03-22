<?php
namespace hng2_modules\comments;

use hng2_base\account;
use hng2_base\account_record;
use hng2_base\config;

class toolbox
{
    private $repository;
    
    public function __construct()
    {
        $this->repository = new comments_repository();
    }
    
    public function trigger_notifications_after_saving($calling_mode, comment_record $comment)
    {
        global $old_comment, $post;
        
        $mem_ttl = 60*60;
        if( time() > strtotime("$comment->creation_date + $mem_ttl seconds") ) return;
        
        if( empty($post) ) $post = $comment->get_post();
        
        $post_author    = $post->get_author();
        $comment_author = $comment->get_author();
        
        # Notification to the POST author on post comments
        if(
            empty($comment->parent_comment)
            && $comment->status == "published"
            && ( $calling_mode == "add" || ($calling_mode == "save" && $old_comment->status != "published") )
        ) {
            # To the post author
            if( $post_author->get_engine_pref("@comments:email_on_post_comments", "true") != "false" )
                $this->notify_post_author_on_comment_submission($post_author, $comment_author);
            
            # To mods/admins
            if( $comment_author->level < config::MODERATOR_USER_LEVEL )
                $this->notify_mods_on_comment_submission($post_author, $comment_author);
            
            return;
        }
        
        # Notification to the author on comment replies
        if(
            ! empty($comment->parent_comment)
            && $comment->status == "published"
            && ( $calling_mode == "add" || ($calling_mode == "save" && $old_comment->status != "published") )
        ) {
            $parent_comment = $this->repository->get($comment->parent_comment);
            $parent_author  = $parent_comment->get_author();
            
            # Notification to the author of the parent
            if( ! is_null($parent_author) )
                if( $parent_author->get_engine_pref("@comments:email_on_comment_replies", "true") != "false" )
                    $this->notify_parent_author_on_comment_reply($parent_comment, $parent_author, $post_author, $comment_author);
            
            # To mods/admins
            if( $comment_author->level < config::MODERATOR_USER_LEVEL )
                $this->notify_mods_on_comment_reply($parent_comment, $parent_author, $post_author, $comment_author);
        }
    }
    
    public function trigger_notifications_after_saving_for_review(comment_record $comment)
    {
        global $post;
        
        $mem_ttl = 60*60;
        if( time() > strtotime("$comment->creation_date + $mem_ttl seconds") ) return;
        
        $post_author    = $post->get_author();
        $comment_author = $comment->get_author();
        
        # Notification to mods on post comments
        if( empty($comment->parent_comment) )
        {
            # To mods/admins
            if( $comment_author->level < config::MODERATOR_USER_LEVEL )
                $this->notify_mods_on_comment_for_review($post_author, $comment_author);
            
            return;
        }
        
        # Notification to mods on comment replies
        $parent_comment = $this->repository->get($comment->parent_comment);
        $parent_author  = $parent_comment->get_author();
        
        # To mods/admins
        if( $comment_author->level < config::MODERATOR_USER_LEVEL )
            $this->notify_mods_on_reply_for_review($parent_comment, $parent_author, $post_author, $comment_author);
    }
    
    /**
     * @param account_record|account $post_author
     * @param account_record $comment_author
     */
    private function notify_post_author_on_comment_submission($post_author, $comment_author)
    {
        global $config, $current_module, $settings, $post, $comment, $modules;
        
        if( $comment_author->id_account == $post_author->id_account ) return;
        
        $subject = replace_escaped_vars(
            $current_module->language->email_templates->comment_added->for_author->subject,
            array(
                '{$title}',
                '{$website_name}',
            ),
            array(
                $post->title,
                $settings->get("engine.website_name"),
            )
        );
        
        $blacklist_email_link = "";
        if( $modules["security"]->enabled )
            $blacklist_email_link = replace_escaped_vars(
                $current_module->language->email_templates->blacklist_email_link,
                '{$url}',
                "{$config->full_root_url}/security/scripts/blacklist_email.php?address="
                . urlencode(encrypt($post_author->email, $config->encryption_key))
            );
        
        $body = replace_escaped_vars(
            $current_module->language->email_templates->comment_added->for_author->body,
            array(
                '{$author}',
                '{$comment_sender}',
                '{$post_title}',
                '{$comment}',
                '{$reply_url}',
                '{$report_url}',
                '{$blacklist_email_link}',
                '{$preferences}',
                '{$website_name}',
                '{$post_link}',
            ),
            array(
                $post_author->display_name,
                empty($comment->id_author)
                    ? $comment->author_display_name
                    : "<a href='{$config->full_root_url}/user/{$comment_author->user_name}'>$comment_author->display_name</a>",
                $post->title,
                $comment->content,
                "{$config->full_root_url}/{$post->id_post}?reply_to={$comment->id_comment}#comment_{$comment->id_comment}",
                "{$config->full_root_url}/contact/?action=report&type=comment&id={$comment->id_comment}",
                $blacklist_email_link,
                "{$config->full_root_url}/accounts/preferences.php",
                $settings->get("engine.website_name"),
                "{$config->full_root_url}/{$post->id_post}",
            )
        );
        
        $body       = unindent($body);
        $recipients = array($post_author->display_name => $post_author->email);
        send_mail($subject, $body, $recipients);
    }
    
    /**
     * @param account_record|account $post_author
     * @param account_record $comment_author
     */
    private function notify_mods_on_comment_submission($post_author, $comment_author)
    {
        global $config, $current_module, $settings, $post, $comment;
        
        $subject = replace_escaped_vars(
            $current_module->language->email_templates->comment_added->for_mods->subject,
            array(
                '{$website_name}',
                '{$author}',
                '{$title}',
            ),
            array(
                $settings->get("engine.website_name"),
                $post_author->display_name,
                $post->title,
            )
        );
        
        $body = replace_escaped_vars(
            $current_module->language->email_templates->comment_added->for_mods->body,
            array(
                '{$comment_sender}',
                '{$author}',
                '{$post_title}',
                '{$comment}',
                '{$reply_url}',
                '{$flag_url}',
                '{$reject_url}',
                '{$trash_url}',
                '{$preferences}',
                '{$website_name}',
                '{$post_link}',
            ),
            array(
                empty($comment->id_author)
                    ? $comment->author_display_name
                    : "<a href='{$config->full_root_url}/user/{$comment_author->user_name}'>$comment_author->display_name</a>",
                $post_author->display_name,
                $post->title,
                $comment->content,
                "{$config->full_root_url}/{$post->id_post}?reply_to={$comment->id_comment}#comment_{$comment->id_comment}",
                "{$config->full_root_url}/comments/?flag_as_spam={$comment->id_comment}",
                "{$config->full_root_url}/comments/?reject={$comment->id_comment}",
                "{$config->full_root_url}/comments/?delete={$comment->id_comment}",
                "{$config->full_root_url}/accounts/preferences.php",
                $settings->get("engine.website_name"),
                "{$config->full_root_url}/{$post->id_post}",
            )
        );
        
        $body = unindent($body);
        broadcast_mail_to_moderators(
            $subject, $body, "@comments:moderator_emails_for_comments", array($comment_author->id_account)
        );
    }
    
    /**
     * @param account_record|account $post_author
     * @param account_record $comment_author
     */
    private function notify_mods_on_comment_for_review($post_author, $comment_author)
    {
        global $config, $current_module, $settings, $post, $comment;
        
        $subject = replace_escaped_vars(
            $current_module->language->email_templates->comment_added->for_review->subject,
            array(
                '{$website_name}',
                '{$author}',
                '{$title}',
            ),
            array(
                $settings->get("engine.website_name"),
                $post_author->display_name,
                $post->title,
            )
        );
        
        $body = replace_escaped_vars(
            $current_module->language->email_templates->comment_added->for_review->body,
            array(
                '{$comment_sender}',
                '{$author}',
                '{$post_title}',
                '{$comment}',
                '{$reply_url}',
                '{$flag_url}',
                '{$approve_url}',
                '{$reject_url}',
                '{$trash_url}',
                '{$preferences}',
                '{$website_name}',
                '{$post_link}',
            ),
            array(
                empty($comment->id_author)
                    ? $comment->author_display_name
                    : "<a href='{$config->full_root_url}/user/{$comment_author->user_name}'>$comment_author->display_name</a>",
                $post_author->display_name,
                $post->title,
                $comment->content,
                "{$config->full_root_url}/{$post->id_post}?reply_to={$comment->id_comment}#comment_{$comment->id_comment}",
                "{$config->full_root_url}/comments/?flag_as_spam={$comment->id_comment}",
                "{$config->full_root_url}/comments/?approve={$comment->id_comment}",
                "{$config->full_root_url}/comments/?reject={$comment->id_comment}",
                "{$config->full_root_url}/comments/?delete={$comment->id_comment}",
                "{$config->full_root_url}/accounts/preferences.php",
                $settings->get("engine.website_name"),
                "{$config->full_root_url}/{$post->id_post}",
            )
        );
        
        $body = unindent($body);
        broadcast_mail_to_moderators(
            $subject, $body, "@comments:moderator_emails_for_comments", array($comment_author->id_account)
        );
    }
    
    /**
     * @param comment_record $parent_comment
     * @param account_record $parent_author
     * @param account_record|account $post_author
     * @param account_record $comment_author
     */
    private function notify_parent_author_on_comment_reply($parent_comment, $parent_author, $post_author, $comment_author)
    {
        global $config, $current_module, $settings, $post, $comment, $modules;
        
        if( $comment_author->id_account == $parent_author->id_account ) return;
        
        $subject = replace_escaped_vars(
            $current_module->language->email_templates->comment_replied->for_parent_author->subject,
            array(
                '{$post_author}',
                '{$post_title}',
            ),
            array(
                $post_author->display_name,
                $post->title,
            )
        );
        
        $blacklist_email_link = "";
        if( $modules["security"]->enabled )
            $blacklist_email_link = replace_escaped_vars(
                $current_module->language->email_templates->blacklist_email_link,
                '{$url}',
                "{$config->full_root_url}/security/scripts/blacklist_email.php?address="
                . urlencode(encrypt($post_author->email, $config->encryption_key))
            );
        
        $body = replace_escaped_vars(
            $current_module->language->email_templates->comment_replied->for_parent_author->body,
            array(
                '{$parent_author}',
                '{$comment_sender}',
                '{$post_author}',
                '{$post_link}',
                '{$post_title}',
                '{$parent_excerpt}',
                '{$comment}',
                '{$reply_url}',
                '{$report_url}',
                '{$blacklist_email_link}',
                '{$preferences}',
                '{$website_name}',
            ),
            array(
                $parent_author->display_name,
                empty($comment->id_author)
                    ? $comment->author_display_name
                    : "<a href='{$config->full_root_url}/user/{$comment_author->user_name}'>$comment_author->display_name</a>",
                $post_author->display_name,
                "{$config->full_root_url}/{$post->id_post}",
                $post->title,
                make_excerpt_of($parent_comment->content, 255),
                $comment->content,
                "{$config->full_root_url}/{$post->id_post}?reply_to={$comment->id_comment}#comment_{$comment->id_comment}",
                "{$config->full_root_url}/contact/?action=report&type=comment&id={$comment->id_comment}",
                $blacklist_email_link,
                "{$config->full_root_url}/accounts/preferences.php",
                $settings->get("engine.website_name"),
            )
        );
        
        $body       = unindent($body);
        $recipients = array($parent_author->display_name => $parent_author->email);
        send_mail($subject, $body, $recipients);
    }
    
    /**
     * @param comment_record $parent_comment
     * @param account_record $parent_author
     * @param account_record|account $post_author
     * @param account_record $comment_author
     */
    private function notify_mods_on_comment_reply($parent_comment, $parent_author, $post_author, $comment_author)
    {
        global $config, $current_module, $settings, $post, $comment;
        
        $subject = replace_escaped_vars(
            $current_module->language->email_templates->comment_replied->for_mods->subject,
            array(
                '{$website_name}',
                '{$post_author}',
                '{$post_title}',
            ),
            array(
                $settings->get("engine.website_name"),
                $post_author->display_name,
                $post->title,
            )
        );
        
        $body = replace_escaped_vars(
            $current_module->language->email_templates->comment_replied->for_mods->body,
            array(
                '{$comment_sender}',
                '{$parent_author}',
                '{$post_author}',
                '{$post_link}',
                '{$post_title}',
                '{$parent_excerpt}',
                '{$comment}',
                '{$reply_url}',
                '{$reject_url}',
                '{$trash_url}',
                '{$flag_url}',
                '{$preferences}',
                '{$website_name}',
            ),
            array(
                empty($comment->id_author)
                    ? $comment->author_display_name
                    : "<a href='{$config->full_root_url}/user/{$comment_author->user_name}'>$comment_author->display_name</a>",
                $parent_author->display_name,
                $post_author->display_name,
                "{$config->full_root_url}/{$post->id_post}",
                $post->title,
                make_excerpt_of($parent_comment->content, 255),
                $comment->content,
                "{$config->full_root_url}/{$post->id_post}?reply_to={$comment->id_comment}#comment_{$comment->id_comment}",
                "{$config->full_root_url}/comments/?flag_as_spam={$comment->id_comment}",
                "{$config->full_root_url}/comments/?reject={$comment->id_comment}",
                "{$config->full_root_url}/comments/?delete={$comment->id_comment}",
                "{$config->full_root_url}/accounts/preferences.php",
                $settings->get("engine.website_name"),
            )
        );
        
        $body = unindent($body);
        broadcast_mail_to_moderators(
            $subject, $body, "@comments:moderator_emails_for_comments", array($comment_author->id_account)
        );
    }
    
    /**
     * @param comment_record $parent_comment
     * @param account_record $parent_author
     * @param account_record|account $post_author
     * @param account_record $comment_author
     */
    private function notify_mods_on_reply_for_review($parent_comment, $parent_author, $post_author, $comment_author)
    {
        global $config, $current_module, $settings, $post, $comment;
        
        $subject = replace_escaped_vars(
            $current_module->language->email_templates->comment_replied->for_review->subject,
            array(
                '{$website_name}',
                '{$post_author}',
                '{$post_title}',
            ),
            array(
                $settings->get("engine.website_name"),
                $post_author->display_name,
                $post->title,
            )
        );
        
        $body = replace_escaped_vars(
            $current_module->language->email_templates->comment_replied->for_review->body,
            array(
                '{$comment_sender}',
                '{$parent_author}',
                '{$post_author}',
                '{$post_link}',
                '{$post_title}',
                '{$parent_excerpt}',
                '{$comment}',
                '{$reply_url}',
                '{$flag_url}',
                '{$approve_url}',
                '{$reject_url}',
                '{$trash_url}',
                '{$preferences}',
                '{$website_name}',
            ),
            array(
                empty($comment->id_author)
                    ? $comment->author_display_name
                    : "<a href='{$config->full_root_url}/user/{$comment_author->user_name}'>$comment_author->display_name</a>",
                $parent_author->display_name,
                $post_author->display_name,
                "{$config->full_root_url}/{$post->id_post}",
                $post->title,
                make_excerpt_of($parent_comment->content, 255),
                $comment->content,
                "{$config->full_root_url}/{$post->id_post}?reply_to={$comment->id_comment}#comment_{$comment->id_comment}",
                "{$config->full_root_url}/comments/?flag_as_spam={$comment->id_comment}",
                "{$config->full_root_url}/comments/?approve={$comment->id_comment}",
                "{$config->full_root_url}/comments/?reject={$comment->id_comment}",
                "{$config->full_root_url}/comments/?delete={$comment->id_comment}",
                "{$config->full_root_url}/accounts/preferences.php",
                $settings->get("engine.website_name"),
            )
        );
        
        $body = unindent($body);
        broadcast_mail_to_moderators(
            $subject, $body, "@comments:moderator_emails_for_comments", array($comment_author->id_account)
        );
    }
}
