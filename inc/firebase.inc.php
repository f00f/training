<?php
require_once 'firebaseconf.inc.php';
require_once __DIR__ . '/vendor/autoload.php';


use Firebase\Token\TokenException;
use Firebase\Token\TokenGenerator;

function FB_GenerateToken()
{
	try {
		$generator = new TokenGenerator(FIREBASE_SECRET);
		$token = $generator
			->setData(array('uid' => '*****'))
			->create();
	} catch (TokenException $e) {
		echo "Error: ".$e->getMessage();
		return false;
	}
	return $token;
}

function FB_UpdateData($team_id, $allPlayers, &$training)
{
	$token = FB_GenerateToken();
	if (!$token)
	{
		return;
	}

	$firebase = new \Firebase\FirebaseLib(FIREBASE_URL, $token);
	$firebase->set(FIREBASE_PATH . '/' . $team_id . '/all-players', $allPlayers);
	$firebase->set(FIREBASE_PATH . '/' . $team_id . '/training', $training);
}
