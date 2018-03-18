<?php
/*
	Dragonfly™ CMS, Copyright © since 2004
	http://dragonflycms.org

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/
if (!class_exists('Dragonfly', false)) { exit; }

use Dragonfly\Modules\Private_Messages\Message as Message;

# Check to see if the recipient has a full savebox
$counts = $db->uFetchRow("SELECT
	(SELECT COUNT(pm_id) FROM {$db->TBL->privatemessages} WHERE user_id = {$userinfo->id} AND pm_status = ".Message::STATUS_SAVED."),
	(SELECT COUNT(pm_id) FROM {$db->TBL->privatemessages_recipients} WHERE user_id = {$userinfo->id} AND pmr_status = ".Message::STATUS_SAVED.")
");
$count = $counts[0] + $counts[1];
if ($count + count($mark_list) >= $PMCFG->sentbox_max) {
	cpg_error('Not enough space in savebox to store messages');
}

$saved_sql_id = implode(',',  array_map('intval', $mark_list));

switch ($folder) {
	case 'inbox':
		$db->TBL->privatemessages_recipients->update(
			array('pmr_status'=>Message::STATUS_SAVED),
			"pm_id IN ({$saved_sql_id}) AND user_id = {$userinfo->id}"
		);
		// Decrement read/new counters if appropriate
		$unread_privmsg = $db->TBL->privatemessages_recipients->count("user_id = {$userinfo->id} AND pmr_status = ".Message::STATUS_UNREAD);
		if ($unread_privmsg != $userinfo->unread_privmsg) {
			$userinfo->unread_privmsg = $unread_privmsg;
			$db->TBL->users->update(array('user_unread_privmsg' => $unread_privmsg), "user_id = {$userinfo->id}");
		}
		break;

	case 'sentbox':
		$db->TBL->privatemessages->update(
			array('pm_status'=>Message::STATUS_SAVED),
			"pm_id IN ({$saved_sql_id}) AND user_id = {$userinfo->id}"
		);
		break;
}

URL::redirect(URL::index("&folder={$folder}"));
