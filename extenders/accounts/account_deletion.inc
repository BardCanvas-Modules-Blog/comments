<?php
/**
 * Accounts extender - deletion
 *
 * @package    BardCanvas
 * @subpackage comments
 * @author     Alejandro Caballero - lava.caballero@gmail.com
 *
 * Globals:
 * @var module $current_module accounts
 * @var module $this_module    self
 * @var object $row
 */


use hng2_base\module;

global $database, $account;

$id_account = $config->globals["deleting_id_account"];
if( empty($id_account) ) return;

if( empty($config->globals["deletions_log"]) ) $config->globals["deletions_log"] = array();
$config->globals["deletions_log"][] = "<b>Starting Comments extender for account deletion</b>";

#
# Delete comment mentions (where user is mentioned)
#

$count = $database->exec("delete from comment_mentions where id_account = '$id_account'");
if( $count > 0 ) $config->globals["deletions_log"][] = "$count mentions of the user from <b>comment_mentions</b> table deleted.";

#
# Delete comment mentions (from comments of the user)
#

$count = $database->exec("
    delete from comment_mentions where id_comment in (
      select id_comment from comments where id_author = '$id_account'
    )
");
if( $count > 0 ) $config->globals["deletions_log"][] = "$count records owned by the user in <b>comment_mentions</b> table deleted.";

#
# Delete comment tags
#

$count = $database->exec("
    delete from comment_tags where id_comment in (
      select id_comment from comments where id_author = '$id_account'
    )
");
if( $count > 0 ) $config->globals["deletions_log"][] = "$count records from <b>comment_tags</b> table deleted.";

#
# Delete comment media attachments
#

$count = $database->exec("
    delete from comment_media where id_comment in (
      select id_comment from comments where id_author = '$id_account'
    )
");
if( $count > 0 ) $config->globals["deletions_log"][] = "$count <b>comment media attachment</b> relationships deleted.<br><b>Note:</b> media records and files not deleted at this time.";

#
# Comment deletions
#

$count = $database->exec("delete from comments where id_author = '$id_account'");
if( $count > 0 ) $config->globals["deletions_log"][] = "$count <b>comments</b> deleted.";

$config->globals["deletions_log"][] = "<b>Finished Comments extender for account deletion</b>";
if( $config->globals["notify_deletion_progress"] )
{
    send_notification(
        $account->id_account, "information", "Finished Comments extender for account deletion"
    );
    sleep(1);
}
