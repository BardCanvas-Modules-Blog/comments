<?php
/**
 * Comments adder (for users)
 *
 * @package    BardCanvas
 * @subpackage categories
 * @author     Alejandro Caballero - lava.caballero@gmail.com
 *             
 * @var settings          $settings
 * @var \SimpleXMLElement $language
 */

use hng2_base\config;
use hng2_base\settings;
use hng2_modules\comments\comment_record;
use hng2_modules\comments\comments_repository;
use hng2_modules\posts\posts_repository;

header("Content-Type: text/plain; charset=utf-8");
include "../../config.php";
include "../../includes/bootstrap.inc";
include "../../lib/recaptcha-php-1.11/recaptchalib.php";

if( empty($_POST["id_post"]) ) die($current_module->language->messages->empty_post_id);

$posts_repository = new posts_repository();
$post = $posts_repository->get($_POST["id_post"]);

if( is_null($post) ) die($current_module->language->messages->post_not_found);
if( $post->status != "published" ) die($current_module->language->messages->post_unavailable);
if( $post->visibility == "private" && $account->id_account != $post->id_author )
    die($current_module->language->messages->unable_to_comment);
if( $post->visibility == "level_based" && $account->level < $post->author_level )
    die($current_module->language->messages->unable_to_comment);

if( empty($_POST["content"]) ) die($current_module->language->messages->empty_message);

if( ! $account->_exists )
{
    if( empty($_POST["author_display_name"]) ) die($current_module->language->messages->empty_name);
    if( empty($_POST["author_email"]) )        die($current_module->language->messages->invalid_email);
    
    if( ! filter_var($_POST["author_email"], FILTER_VALIDATE_EMAIL) )
        die($current_module->language->messages->invalid_email);
}

$repository = new comments_repository();

$comment = new comment_record();
$comment->set_from_post();

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
    if( $settings->get("engine.recaptcha_private_key") == "" ) die( $language->captcha_not_configured );
    
    $res = recaptcha_check_answer(
        $settings->get("engine.recaptcha_private_key"),
        get_remote_address(),
        $_POST["recaptcha_challenge_field"],
        $_POST["recaptcha_response_field"]
    );
    if( ! $res->is_valid ) die( $current_module->language->messages->invalid_captcha );
}

// Double submission check
$interval = $settings->get("modules:comments.repeated_interval");
if( $interval == "" ) $interval = 1;
if( $account->level < config::MODERATOR_USER_LEVEL && $interval > 0 )
{
    $boundary = date("Y-m-d H:i:s", strtotime("now - $interval minutes"));
    $params = array(
        "id_author" => $comment->id_author,
        "content"   => $comment->content,
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

if( $account->level < config::MODERATOR_USER_LEVEL )
{
    // Spam filters: links
    $links = $settings->get("module:comments.flag_for_review_on_link_amount");
    if( empty($links) ) $links = 2;
    $pattern = '@(https?://([-\w\.]+[-\w])+(:\d+)?(/([\w/_\.#-]*(\?\S+)?[^\.\s])?)?)@i';
    $matches = preg_match_all($pattern, $comment->content, $nothing);
    if( $matches >= $links ) $comment->status = "reviewing";
    
    //TODO: Spam filters: check in grey list
    
    //TODO: Spam filters: check in black list
}

$comment->set_new_id();
$comment->creation_ip       = get_user_ip();
$comment->creation_host     = gethostbyaddr($comment->creation_ip);
$comment->creation_location = forge_geoip_location($comment->creation_ip);
$comment->creation_date     = date("Y-m-d H:i:s");
$repository->save($comment);
echo "OK:{$comment->id_comment}";
