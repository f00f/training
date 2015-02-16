<?php
require_once 'config-site.inc.php'; // @uses ON_TEST_SERVER

// 1) enter your DB config
$dbHost	= '';
$dbDB	= '';
$dbUser	= '';
$dbPass	= '';

// 2) table names
$table	= 'training';
$tables = array(
	'practice_sessions' => 'train_psessions',
	'replies'   => 'train_replies',
	'players_conf'   => 'train_players',
	'practices_conf' => 'train_practices',
	);

// 3) if you have a development server you may want to use a different DB config there.
//    you may want to edit the test for ON_TEST_SERVER in lib.inc.php
if (@ON_TEST_SERVER)
	{
	$dbHost = '';
	$dbDB	= '';
	$dbUser	= '';
	$dbPass	= '';
	}
?>