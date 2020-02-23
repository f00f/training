<?php
require_once 'config-site.inc.php'; // @uses ON_TEST_SERVER

// Create emtpy config array
$conf = array();

// Create configs for clubs
create_default_config('Demo');

// Load current club's config
$club_id = get_club_id();
load_config($club_id);

function get_club_id() {
	global $club_id, $conf;
	if (!isset($_GET['club_id'])) {
		die('CLUB ID MISSING');
	}
	$myClubId = strtolower($_GET['club_id']);
	if (!isset($conf[$myClubId])) {
		die('INVALID CLUB ID');
	}
	$club_id = $myClubId;
	return $club_id;
}

function load_config($team_id) {
	global $conf;
	global $rootUrl, $teamNameShort, $emailFrom, $forgetPlayersAfter, $forgetConfiguredPlayers, $teamId, $copyJsonFiles, $KontaktName, $KontaktEmail;

	if (!isset($conf[$team_id])) {
		die('Config not found.');
	}

	extract($conf[$team_id], EXTR_IF_EXISTS);
}

function set_config_value($team_name, $key, $val) {
	global $conf;

	$team_id = strtolower($team_name);

	if (!isset($conf[$team_id])) {
		die('Config for '.$team_id.' not found.');
	}
	
	if (isset($conf[$team_id][$key])) {
		die('Config for '.$team_id.' already contains key '.$key.'.');
	}

	$conf[$team_id][$key] = $val;
}

function create_default_config($team_name) {
	global $conf;

	$team_id = strtolower($team_name);

	$conf[$team_id] = array();

	//rootUrl: used to build links in notification emails
	// should point to the folder of the training website, with trailing slash.
	//$conf[$team_id]['rootUrl'] = 'http://ba.uwr1.de/training/';
	//$conf[$team_id]['rootUrl'] = 'http://git.uwr1.test/training/';
	if (@ON_TEST_SERVER) {
		$conf[$team_id]['rootUrl'] = 'http://training.uwr1.test/'.$team_id.'/';
	} else {
		$conf[$team_id]['rootUrl'] = 'http://training.uwr1.de/'.$team_id.'/';
	}

	//teamNameShort: used in email sender name of notification emails.
	$conf[$team_id]['teamNameShort'] = 'UWR ' . $team_name;

	//emailFrom: used to build the sender username of notification emails.
	// the actual sender will be "training-{$emailFrom}@uwr1.de"
	$conf[$team_id]['emailFrom'] = $team_id;

	// Non-configured players will disapear from the page N months after their last reply.
	$conf[$team_id]['forgetPlayersAfter'] = 2;

	// Whether configured players will also disapear from the page N months after their last reply.
	// Note: this is only a question of displaying the name on the page.
	$conf[$team_id]['forgetConfiguredPlayers'] = false;

	//teamId: probably used on several occasions.
	$conf[$team_id]['teamId'] = $team_id;

	//copyJsonFiles: copy json output files to old location so that the app can find them.
	// Enable only for clubs which use the new site.
	$conf[$team_id]['copyJsonFiles'] = false;

	//plugins: list of plugin names which should be active on a club's page.
	$conf[$team_id]['plugins'] = array();
}
