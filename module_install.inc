<?php
/**
 * Module installation file
 *
 * @package    BardCanvas
 * @subpackage comments
 * @author     Alejandro Caballero - lava.caballero@gmail.com
 * 
 * Trailing vars:
 * @var string $module_install_action install, uninstall, enable, disable
 * @var array  $messages
 * @var array  $errors
 * @var module $this_module
 * @var module $current_module
 */

use hng2_base\module;

$tables = array(
    "comments" => "
        create table if not exists comments (
            
            id_post             bigint unsigned not null default 0,
            id_comment          bigint unsigned not null default 0,
            parent_comment      bigint unsigned not null default 0,
            parent_author       bigint unsigned not null default 0,
            indent_level        tinyint unsigned not null default 0,
            
            id_author           bigint unsigned not null default 0,
            author_display_name varchar(100) not null default '',
            author_email        varchar(100) not null default '',
            author_url          varchar(100) not null default '',
            
            content             longtext,
            status              enum('published', 'reviewing', 'rejected', 'spam', 'hidden', 'trashed') not null default 'published',
            
            creation_date       datetime default null,
            creation_ip         varchar(15) not null default '',
            creation_host       varchar(255) not null default '',
            creation_location   varchar(255) not null default '',
            
            karma               int not null default 0,
            last_update         datetime default null,
            
            primary key              ( id_comment ),
            index   comments_tree    ( id_comment, parent_comment ),
            index   by_author        ( id_author ),
            index   by_post          ( id_post, status ),
            index   by_ip            ( creation_ip(7) ),
            index   by_parent_author ( parent_author )
            
        ) engine=InnoDB default charset=utf8mb4 collate='utf8mb4_unicode_ci'
    ",
    "comment_media" => "
        create table if not exists comment_media (
            
            id_comment     bigint unsigned not null default 0,
            id_media       bigint unsigned not null default 0,
            date_attached  datetime,
            order_attached double unsigned not null default 0,
            
            primary key ( id_comment, id_media )
            
        ) engine=InnoDB default charset=utf8mb4 collate='utf8mb4_unicode_ci'
    ",
    "comment_tags" => "
        create table if not exists comment_tags (
            
            id_comment     bigint unsigned not null default 0,
            tag            varchar(100) not null default '',
            date_attached  datetime,
            order_attached double unsigned not null default 0,
            
            primary key ( id_comment, tag )
            
        ) engine=InnoDB default charset=utf8mb4 collate='utf8mb4_unicode_ci'
    ",
    "comment_mentions" => "
        create table if not exists comment_mentions (
            
            id_comment     bigint unsigned not null default 0,
            id_account     bigint unsigned not null default 0,
            date_attached  datetime,
            order_attached double unsigned not null default 0,
            
            primary key ( id_comment, id_account )
            
        ) engine=InnoDB default charset=utf8mb4 collate='utf8mb4_unicode_ci'
    ",
);

$init_settings = array(
    "modules:comments.items_per_page"        => 20,
    "modules:comments.show_in_indexes"       => 'true',
    "modules:comments.items_per_index_entry" => 10,
    "modules:comments.disable_new_after"     => 90,
);

if( $module_install_action == "install" )
{
    foreach($tables as $table_name => $query)
    {
        try
        {
            $database->exec($query);
            $messages[] = replace_escaped_vars($language->install_messages->table_created_ok, '{$table_name}', $table_name);
        }
        catch( \Exception $e )
        {
            $errors[] = replace_escaped_vars(
                $language->install_messages->table_created_ko,
                array( '{$table_name}', '{$error}' ),
                array(   $table_name,     $e->getMessage()  )
            );
            
            return;
        }
    }
    
    foreach($init_settings as $key => $val) $settings->set($key, $val);
    
    return;
}

if( $module_install_action == "uninstall" )
{
    foreach($tables as $table_name => $query)
    {
        try
        {
            $database->exec("DROP TABLE if exists {$table_name}");
            $messages[] = replace_escaped_vars($language->install_messages->table_deleted_ok, '{$table_name}', $table_name);
        }
        catch( \Exception $e )
        {
            $errors[] = replace_escaped_vars(
                $language->install_messages->table_deleted_ko,
                array( '{$table_name}', '{$error}' ),
                array(   $table_name,     $e->getMessage() )
            );
        }
    }
    
    foreach($init_settings as $key => $val) $settings->set($key, "");
    
    return;
}
