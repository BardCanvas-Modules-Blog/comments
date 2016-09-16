<?php
/**
 * Gallery extender - principal data addons
 *
 * @package    BardCanvas
 * @subpackage comments
 * @author     Alejandro Caballero - lava.caballero@gmail.com
 *
 * Globals:
 * @var module       $current_module accounts
 * @var module       $this_module    self
 * @var media_record $row
 */

use hng2_base\config;
use hng2_base\module;
use hng2_media\media_record;
use hng2_modules\comments\comment_record;
use hng2_modules\comments\comments_repository;

global $row, $principal_data_addons, $modules, $account;

if( $account->level < config::MODERATOR_USER_LEVEL ) return;

$repository = new comments_repository();

$count = $repository->get_record_count(array(
    "id_comment in (select id_comment from comment_media where id_media = '$row->id_media')",
));
if( $count == 0 ) return;

$records = $repository->find(
    array("id_comment in (select id_comment from comment_media where id_media = '$row->id_media')"),
    0, 0, ""
);

/** @var comment_record $record */
foreach($records as $record)
{
    $author = "";
    if( empty($record->id_author) )
    {
        $author = convert_emojis($record->author_display_name);
    }
    else
    {
        $author_account = $record->get_author();
        $name           = convert_emojis($author_account->display_name);
        $url            = "{$config->full_root_path}/user/$author_account->user_name";
        $author         = "<a href='{$url}' target='_blank'>{$name}</a>";
    }
    
    $principal_data_addons[] = replace_escaped_vars(
        $this_module->language->gallery_addons->principal_data,
        array('{$author}', '{$url}', '{$title}', '{$time}', '{$status}'),
        array(
            $author,
            $record->get_permalink(),
            make_excerpt_of($record->content),
            $record->creation_date == "0000-00-00 00:00:00" ? "&mdash;" : get_minimized_date($record->creation_date),
            $this_module->language->statuses->{$record->status},
        )
    );
}
