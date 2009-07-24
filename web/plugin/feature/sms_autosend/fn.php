<?php
/*
 * Created on May 2, 2008
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */
function sms_autosend_hook_playsmsd() {
	$timenow = mktime();
	$db_query = "SELECT uid,time_id," . _DB_PREF_ . "_featureAutosend.autosend_id, autosend_message,autosend_number,autosend_time
							FROM " . _DB_PREF_ . "_featureAutosend
							INNER JOIN " . _DB_PREF_ . "_featureAutosend_time
							ON " . _DB_PREF_ . "_featureAutosend.autosend_id =  " . _DB_PREF_ . "_featureAutosend_time.autosend_id
							WHERE UNIX_TIMESTAMP(" . _DB_PREF_ . "_featureAutosend_time.autosend_time) <= '$timenow'
							AND " . _DB_PREF_ . "_featureAutosend_time.sent='0'
							AND autosend_time != ''
							AND " . _DB_PREF_ . "_featureAutosend.autosend_enable='1'";	
	$db_result = dba_query($db_query);
	while ($db_row = dba_fetch_array($db_result)) {
		$message = $db_row[autosend_message];
		$c_uid = $db_row[uid];
		$username = uid2username($c_uid);
		$mobile_sender = username2mobile($username); //our sender number
		$sms_sender = username2sender($username); //our sender id	
		$sms_to = $db_row[autosend_number]; // we are sending to this number
		$autosend_id = $db_row[autosend_id];
		$time_id = $db_row[time_id];
		$ret = sendsms($mobile_sender, $sms_sender, $sms_to, $message, $c_uid);
		if ($ret) {
			$db_query = "UPDATE " . _DB_PREF_ . "_featureAutosend_time SET sent='1' WHERE time_id = '$time_id'";
			$db_result = dba_affected_rows($db_query);
		}
	}
}
?>