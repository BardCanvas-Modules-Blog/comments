<?php
/**
 * Comments adder (for users)
 *
 * @package    BardCanvas
 * @subpackage comments
 * @author     Alejandro Caballero - lava.caballero@gmail.com
 * 
 * @var module            $current_module
 * @var account           $account
 * @var settings          $settings
 * @var \SimpleXMLElement $language
 */

use hng2_base\account;
use hng2_base\accounts_repository;
use hng2_base\config;
use hng2_base\module;
use hng2_base\settings;
use hng2_modules\comments\comment_record;
use hng2_modules\comments\comments_repository;
use hng2_modules\comments\toolbox;
use hng2_modules\posts\posts_repository;

header("Content-Type: text/plain; charset=utf-8");
include "../../config.php";
include "../../includes/bootstrap.inc";
include "../../lib/recaptcha-php-1.11/recaptchalib.php";

if( empty($_POST["id_post"]) ) die($current_module->language->messages->empty_post_id);

$posts_repository = new posts_repository();
$post = $posts_repository->get($_POST["id_post"]);
$toolbox = new toolbox();

if( is_null($post) ) die($current_module->language->messages->post_not_found);
if( $post->status != "published" ) die($current_module->language->messages->post_unavailable);
if( $post->visibility == "private" && $account->id_account != $post->id_author )
    die($current_module->language->messages->unable_to_comment);
if( $post->visibility == "level_based" && $account->level < $post->author_level )
    die($current_module->language->messages->unable_to_comment);

if( empty($_POST["content"]) ) die($current_module->language->messages->empty_message);

if( $settings->get("modules:comments.avoid_anonymous") == "true" && $account->level < config::NEWCOMER_USER_LEVEL )
    die($current_module->language->messages->anonymous_cant_comment);

if( ! $account->_exists )
{
    if( empty($_POST["author_display_name"]) ) die($current_module->language->messages->empty_name);
    if( empty($_POST["author_email"]) )        die($current_module->language->messages->invalid_email);
    
    if( ! filter_var($_POST["author_email"], FILTER_VALIDATE_EMAIL) )
        die($current_module->language->messages->invalid_email);
    
    if( $_POST["save_details"] == "true" )
    {
        setcookie("{$config->website_key}_comments_dn", encrypt($_POST["author_display_name"], $config->encryption_key), time()+(86400 * 30), "/", $config->cookies_domain);
        setcookie("{$config->website_key}_comments_ae", encrypt($_POST["author_email"],        $config->encryption_key), time()+(86400 * 30), "/", $config->cookies_domain);
        if( ! empty($_POST["author_url"]) )
            setcookie("{$config->website_key}_comments_au", encrypt($_POST["author_url"],      $config->encryption_key), time()+(86400 * 30), "/", $config->cookies_domain);
    }
    else
    {
        setcookie("{$config->website_key}_comments_dn", "", 0, "/", $config->cookies_domain);
        setcookie("{$config->website_key}_comments_ae", "", 0, "/", $config->cookies_domain);
        setcookie("{$config->website_key}_comments_au", "", 0, "/", $config->cookies_domain);
    }
}

$repository = new comments_repository();

$comment = new comment_record();
$comment->set_from_post();

$parent = $repository->get($comment->parent_comment);
if( ! empty($comment->parent_comment) && is_null($parent) )
    die($current_module->language->messages->parent_not_found);
$comment->indent_level = $parent->indent_level + 1;
$comment->parent_author = $parent->id_author;

if( $account->_exists )
{
    $comment->id_author           = $account->id_account;
    $comment->author_display_name = "";
    $comment->author_email        = "";
    $comment->author_url          = "";
}

// Recaptcha check
if( ! $account->_exists )
{
    $accounts_repository = new accounts_repository();
    if( $accounts_repository->get_record_count(array("user_name" => $comment->author_display_name)) > 0 )
        die($current_module->language->messages->impersonation->user_name_exists);
    if( $accounts_repository->get_record_count(array("display_name" => $comment->author_display_name)) > 0 )
        die($current_module->language->messages->impersonation->display_name_exists);
    if( $accounts_repository->get_record_count(array("email" => $comment->author_email)) > 0 )
        die($current_module->language->messages->impersonation->email_exists);
    if( $accounts_repository->get_record_count(array("alt_email" => $comment->author_email)) > 0 )
        die($current_module->language->messages->impersonation->email_exists);
    
    if( $settings->get("engine.recaptcha_private_key") != "" && $settings->get("engine.recaptcha_public_key") )
    {
        $res = recaptcha_check_answer(
            $settings->get("engine.recaptcha_private_key"),
            get_remote_address(),
            $_POST["recaptcha_challenge_field"],
            $_POST["recaptcha_response_field"]
        );
        if( ! $res->is_valid ) die( $current_module->language->messages->invalid_captcha );
    }
}

// Double submission check
$interval = $settings->get("modules:comments.repeated_interval");
if( $interval == "" ) $interval = 1;
if( $account->level < config::MODERATOR_USER_LEVEL && $interval > 0 )
{
    $boundary = date("Y-m-d H:i:s", strtotime("now - $interval minutes"));
    $params = array(
        "id_author" => $comment->id_author,
        "content"   => addslashes($comment->content),
        "creation_date >= '{$boundary}'"
    );
    
    if($repository->get_record_count($params) > 0)
        die($current_module->language->messages->already_sent);
}

// Speed check
$interval = $settings->get("modules:comments.sending_interval");
if( $interval == "" ) $interval = 30;
if( $account->level < config::MODERATOR_USER_LEVEL && $interval > 0 )
{
    $boundary = date("Y-m-d H:i:s", strtotime("now - $interval seconds"));
    $params = array(
        "id_author"           => $comment->id_author,
        "author_display_name" => $comment->author_display_name,
        "author_email"        => $comment->author_email,
        "creation_date >= '{$boundary}'"
    );
    
    $res = $repository->find($params, 1, 0, "creation_date desc");
    if( count($res) )
    {
        /** @var comment_record $record */
        $record = current($res);
        $remaining = time_remaining_string($record->creation_date);
        die(replace_escaped_vars($current_module->language->messages->sending_too_fast, '{$time}', $remaining));
    }
}

$comment->status = "published";

$comment->set_new_id();
$comment->creation_ip       = get_user_ip();
$comment->creation_host     = gethostbyaddr($comment->creation_ip);
$comment->creation_location = forge_geoip_location($comment->creation_ip);
$comment->creation_date     = date("Y-m-d H:i:s");

$tags = extract_hash_tags($comment->content);
$featured_posts_tag = $settings->get("modules:posts.featured_posts_tag");
if(
    $account->level < config::MODERATOR_USER_LEVEL
    && $settings->get("modules:posts.show_featured_posts_tag_everywhere") != "true"
    && ! empty($featured_posts_tag)
    && in_array($featured_posts_tag, $tags)
) {
    unset($tags[array_search($featured_posts_tag, $tags)]);
    $comment->content = str_replace("#$featured_posts_tag", $featured_posts_tag, $comment->content);
}

$media_items = array();
if( function_exists("extract_media_items") )
{
    $images = extract_media_items("image", $comment->content);
    $videos = extract_media_items("video", $comment->content);
    $media_items = array_merge($images, $videos);
}

$min_level = $settings->get("modules:comments.privileged_user_level");
if( empty($min_level) ) $min_level = config::MODERATOR_USER_LEVEL;
if( $account->level < $min_level )
{
    // Spam filters: links
    $links = $settings->get("modules:comments.flag_for_review_on_link_amount");
    if( empty($links) ) $links = 2;
    $pattern = '@(https?://([-\w\.]+[-\w])+(:\d+)?(/([\w/_\.#-]*(\?\S+)?[^\.\s])?)?)@i';
    preg_match_all($pattern, $comment->content, $matches);
    if( ! empty($matches) )
    {
        $matches = $matches[0];
        foreach($matches as $index => $match)
            if( stristr($match, $config->full_root_url) !== false )
                unset($matches[$index]);
        
        if( count($matches) >= $links )
        {
            $comment->status = "reviewing";
            # if( count($media_items) ) $repository->set_media_items($media_items, $comment->id_comment);
            # if( ! empty($tags) ) $repository->set_tags($tags, $comment->id_comment);
            $repository->save($comment);
            $current_module->load_extensions("add_comment", "after_saving_for_review");
            $toolbox->trigger_notifications_after_saving_for_review($comment);
            
            if( $account->_exists )
            {
                send_notification(
                    $account->id_account, "warning", $current_module->language->messages->links_exceeded
                );
                die("OK:{$comment->id_comment}");
            }
            else
            {
                die( unindent(strip_tags($current_module->language->messages->links_exceeded)) );
            }
        }
    }
}

$current_module->load_extensions("add_comment", "before_saving");
if( count($media_items) ) $repository->set_media_items($media_items, $comment->id_comment);
if( ! empty($tags) ) $repository->set_tags($tags, $comment->id_comment);
$repository->save($comment);
$current_module->load_extensions("add_comment", "after_saving");
$toolbox->trigger_notifications_after_saving("add", $comment);
$posts_repository->update_comments_count($comment->id_post);

if($_REQUEST["raw_success_confirmation"] == "true") die("OK");
echo "OK:{$comment->id_comment}";
