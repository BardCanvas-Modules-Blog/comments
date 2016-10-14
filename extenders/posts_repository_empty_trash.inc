<?php
/**
 * Posts repository class extender: empty trash action
 *
 * @package    BardCanvas
 * @subpackage comments
 * @author     Alejandro Caballero - lava.caballero@gmail.com
 */

global $database;

$boundary = date("Y-m-d 00:00:00", strtotime("today - 7 days"));

$database->exec("
  delete from comment_mentions where id_comment in (
    select id_comment from comments where id_post in (
      select id_post from posts where status = 'trashed'
      and creation_date < '$boundary'
    )
  )
");

$database->exec("
  delete from comment_tags where id_comment in (
    select id_comment from comments where id_post in (
      select id_post from posts where status = 'trashed'
      and creation_date < '$boundary'
    )
  )
");

$database->exec("
  delete from comment_media where id_comment in (
    select id_comment from comments where id_post in (
      select id_post from posts where status = 'trashed'
      and creation_date < '$boundary'
    )
  )
");

$database->exec("
  delete from comments where id_post in (
    select id_post from posts where status = 'trashed'
    and creation_date < '$boundary'
  )
");
