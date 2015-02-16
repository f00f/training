<?php
require_once 'config-site.inc.php'; // @uses ON_TEST_SERVER, @uses RESET_DELAY
require_once 'dbconf.inc.php';

//! Executes a db query and returns result
// Displays error, if any.
// ON_TEST_SERVER: Also displays query.
function DbQuery($query) {
	$result	= mysql_query($query);
	if (mysql_errno() != 0) {
		$msg = mysql_error();
		if (@ON_TEST_SERVER) {
			$msg += '<br />Query was: ' . $query;
		}
		die($msg);
	}
	return $result;
}

//! Insert a new row into the replies table
// @deprecated
// @obsolete
function InsertRow($p_name, $p_text, $p_status, $p_app = 'web', $p_app_version = 'unknown', $p_club_id = 'unknown') {
	return; // Deprecated
	global $table, $aliases;
	global $ip, $host;

	$p_nameLC = strtolower($p_name);
	$p_name = isset($aliases[$p_nameLC]) ? $aliases[$p_nameLC] : $p_name;

	// insert one second later if not a reset
	// this avoids problems with automatic inserts from EvaluateFollowUps
	//
	// insert RESET_DELAY later if a reset
	// this allows to still show player list even after training has started
	$timeOffset = ('RESET' == $p_name) ? RESET_DELAY : 1;

	DbQuery("INSERT INTO `{$table}` "
		. "(`name`, `text`, `when`, `status`, `ip`, `host`, `app`, `app_ver`, `club_id`) "
		. "VALUES "
		. "('{$p_name}', '{$p_text}', '".(time()+$timeOffset)."', '{$p_status}', '{$ip}', '{$host}', '{$p_app}', '{$p_app_version}', '{$p_club_id}')");
}
