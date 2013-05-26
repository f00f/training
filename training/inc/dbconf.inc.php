<?php
// 1) enter your DB config
$dbHost	= '';
$dbDB	= '';
$dbUser	= '';
$dbPass	= '';

// 2) maybe add a suffix like _teamname
$table	= 'training';

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