<?php

use hng2_base\config;
use hng2_modules\comments\comment_record;

function is_comment_editable(comment_record $comment)
{
    global $settings, $account;
    
    if($account->level >= config::MODERATOR_USER_LEVEL ) return true;
    
    $time = $settings->get("modules:comments.time_allowed_for_editing_after_submission");
    if( empty($time) ) return false;
    
    $now      = time();
    $boundary = strtotime("{$comment->creation_date} + $time minutes");
    
    return $now < $boundary;
}
