<?php
// Besides this config file, make sure to also change the files
// training/root.shtml
// training/inc/dbconf.inc.php
// training/inc/spieler.inc.php
// training/inc/trainingszeiten.inc.php


// TODO
// * auto-load club config

require_once 'config-site.inc.php'; // @uses ON_TEST_SERVER


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

function load_config($club_id) {
	global $conf;
	global $rootUrl, $teamNameShort, $emailFrom, $forgetPlayersAfter, $forgetConfiguredPlayers, $teamId, $copyJsonFiles;

	if (!isset($conf[$club_id])) {
		die('Config not found.');
	}

	extract($conf[$club_id], EXTR_IF_EXISTS);
}

// Create emtpy config array
$conf = array();

function create_default_config($team_name) {
	global $conf;

	$club_id = strtolower($team_name);

	$conf[$club_id] = array();

	//rootUrl: used to build links in notification emails
	// should point to the folder of the training website, with trailing slash.
	if (@ON_TEST_SERVER) {
		$conf[$club_id]['rootUrl'] = 'http://training.uwr1.test/'.$club_id.'/';
	} else {
		$conf[$club_id]['rootUrl'] = 'http://training.uwr1.de/'.$club_id.'/';
	}

	//teamNameShort: used in email sender name of notification emails.
	$conf[$club_id]['teamNameShort'] = 'UWR ' . $team_name;

	//emailFrom: used to build the sender username of notification emails.
	// the actual sender will be "training-{$emailFrom}@uwr1.de"
	$conf[$club_id]['emailFrom'] = $club_id;

	// Non-configured players will disapear from the page N months after their last reply.
	$conf[$club_id]['forgetPlayersAfter'] = 2;

	// Whether configured players will also disapear from the page N months after their last reply.
	// Note: this is only a question of displaying the name on the page.
	$conf[$club_id]['forgetConfiguredPlayers'] = false;

	//teamId: probably used on several occasions.
	$conf[$club_id]['teamId'] = $club_id;

	//copyJsonFiles: copy json output files to old location so that the app can find them.
	// Enable only for clubs which use the new site.
	$conf[$club_id]['copyJsonFiles'] = false;
}

create_default_config('Demo');
