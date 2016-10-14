<?php
/**
 * Media repository class extender: empty trash action
 *
 * @package    BardCanvas
 * @subpackage comments
 * @author     Alejandro Caballero - lava.caballero@gmail.com
 */

global $database;

$boundary = date("Y-m-d 00:00:00", strtotime("today - 7 days"));

$database->exec("
  delete from comment_media where id_media in (
    select id_media from media where status = 'trashed'
    and creation_date < '$boundary'
  )
");
