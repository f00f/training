<?php
////////////////////////////////////////////////////////////////////////////////
//// Players ////
////////////////////////////////////////////////////////////////////////////////

class Player {
	public static $fields = array(
		'uid' => array(
			'is_column' => true,
			'type' => 'hidden',
			'values' => 'numeric',
		),
		'club_id' => array(
			'is_column' => true,
			'type' => 'hidden',
		),
		'name' => array(
			'required' => true,
			'help' => 'Der Name darf nur Buchstaben enthalten.',
		),
		'email' => array(
			'label' => 'E-Mail',
			'help' => 'Die E-Mail-Adresse wird nur für Benachrichtigungs-E-Mails benutzt.',
		),
		'freq' => array(
			'label' => 'Frequenz',
			'values' => 'numeric',
			'help' => 'Häufigkeit der Benachrichtigungs-E-Mails.<br>x &lt; 0: bis zur x-ten Meldung.<br>x = 0: garkeine Mails.<br>x &gt; 0: bei jeder x-ten Meldung.',
		),
		'keineSelbstMail' => array(
			'values' => 'bool',
			'help' => 'Keine E-Mail für die eigene An-/Abmeldung schicken.',
		),
		'tendenz' => array(
			'values' => array('ja', 'nein', ''),
			'help' => 'Dieser Spieler kommt tendenziell eher (<em>ja</em>) oder eher nicht (<em>nein</em>) zum Training.<br>Mögliche Werte für das Feld: <em>ja</em>, <em>nein</em> oder <em>leer lassen</em>.',
		),
		'grund' => array(
			'help' => '"Grund für die Tendenz" &ndash; Ein Standard-Kommentar, der in der nichts-gesagt-Liste in Klammern hinter dem Namen angezeigt wird.',
		),
	);


	public static function Load($playerUID, $cid = 'use $club_id') {
		global $tables;

		$q = "SELECT `uid`, `club_id`, `player_name`, `player_data` FROM `{$tables['players_conf']}` "
			. "WHERE `uid` = '{$playerUID}'";
		if ($cid !== false) {
			$q .= " AND `club_id` = '{$cid}'";
		}
		$result = DbQuery($q);
		if (mysqli_num_rows($result) != 1) {
			return false;
		}

		$row = mysqli_fetch_assoc($result);
		$player = unserialize($row['player_data']);
		foreach ($row as $k => $v) {
			if ('player_data' == $k) {
				continue;
			}
			$player[$k] = $v;
		}
		$player['nameLC'] = $row['player_name'];

		return $player;
	}

	public static function Save($player) {
		global $tables;

		$warnings = array();
		$errors = array();
		$success = ValidateInstance($player, self::$fields, $warnings, $errors);
		if (!$success) {
			if (count($errors) > 0) {
				$error_msg = 'Folgende nicht-optionale Felder haben fehlerhafte Eingaben: <em>' . implode('</em>, <em>', $errors) . '</em>.';
				$_SESSION['error'] = $error_msg;
			}
			if (count($warnings) > 0) {
				$warn_msg = 'Folgende optionale Felder haben fehlerhafte Eingaben: <em>' . implode('</em>, <em>', $warnings) . '</em>.';
				$_SESSION['warning'] = $warn_msg;
			}
			// TODO: show add/edit page, but do not re-load data from DB.
			return false;
		}
		$data = serialize(GetOptionalData($player, self::$fields));

		$uid = 0;
		if (isset($player['uid'])) { $uid = intval($player['uid']); }
		if ($uid < 1) { $uid = 0; }
		$cid = 'n/a';
		if ($player['club_id']) { $cid = $player['club_id']; }
		$player_name = '';
		if ($player['name']) { $player_name = strtolower(FirstWord($player['name'])); }

		// store to DB
		$q = "REPLACE INTO `{$tables['players_conf']}` "
			. "(`uid`, `club_id`, `player_name`, `player_data`) "
			. "VALUES "
			. "({$uid}, '{$cid}', '{$player_name}', '{$data}')";
			//. "WHERE `uid` = '{$playerUID}'";
		$result = DbQuery($q);

		return true;
	}

	# Get status of a player for the upcoming practice session of a certain team
	public static function GetStatus($name, $cid) {
		global $tables;
		global $lastReset, $nextReset;

		$status = null;
		$session_id = PracticeSession::GetNextId($cid);
		if (false === $session_id) {
			PracticeTime::GetNext($cid);// implicitely create session record
			$session_id = PracticeSession::GetNextId($cid);
		}
		if (false !== $session_id) {
			$status = PracticeSession::GetPlayerStatus($name, $cid, $session_id);
		}

		return $status;
	}

	# Side-effect: Updates record of corresponding practice session.
	public static function StoreReply($reply, $name, $text, $cid, $app, $app_ver) {
		global $tables;

		$when = time();
		$ip = $_SERVER['REMOTE_ADDR'];
		$host = gethostbyaddr($ip);

		$sid = PracticeSession::GetNextId($cid);

		$currentPlayerStatus = self::GetStatus($name, $cid);
		$statusChanged = ($reply != $currentPlayerStatus);

		$q = "INSERT INTO `{$tables['replies']}` "
			."(`club_id`, `session_id`, `name`, `text`, `when`, `status`, `ip`, `host`, `app`, `app_ver`) "
			."VALUES "
			."('{$cid}', '{$sid}', '{$name}', '{$text}', {$when}, '{$reply}', '{$ip}', '{$host}', '{$app}', '{$app_ver}')";
		DbQuery($q);

		// update practice session record
		PracticeSession::Update($reply, $statusChanged, $name, $text, $cid, $sid);

		// TODO: redirect user
		$location = false;
		if (!$location && !empty($_SERVER['HTTP_REFERER'])) {
			// TODO: check (validate) referer string
			$location = $_SERVER['HTTP_REFERER'];
		}
		
		return $statusChanged;
	}

	// load data of configured players from DB
	public static function LoadConfigured($cid) {
		global $CACHE;
		global $tables;

		if (isset($CACHE->configuredPlayers) && false !== @$CACHE->configuredPlayers) {
			return $CACHE->configuredPlayers;
		}

		$CACHE->configuredPlayers = array();

		$q = "SELECT `uid`, `player_name`, `player_data` FROM `{$tables['players_conf']}` "
			. "WHERE `club_id` = '{$cid}' "
			. "ORDER BY `player_name` ASC";//TODO: add deleted
		$result = DbQuery($q);
		if (mysqli_num_rows($result) > 0) {
			while ($row = mysqli_fetch_assoc($result)) {
				$pData = unserialize($row['player_data']);
				$pData['uid'] = $row['uid'];
				$CACHE->configuredPlayers[ $row['player_name'] ] = $pData;
			}
		}

		return $CACHE->configuredPlayers;
	}

	// load _all_ players (configured and not) from DB
	public static function LoadAll($cid) {
		global $CACHE;
		if (isset($CACHE->allPlayers) && false !== @$CACHE->allPlayers) {
			return $CACHE->allPlayers;
		}

		$CACHE->allPlayers = array();

		global $tables;
		global $forgetPlayersAfter, $forgetConfiguredPlayers;
		global $playerAliases;

		if (!$forgetPlayersAfter || $forgetPlayersAfter < 0) {
			$forgetPlayersAfter = 1;
		}

		// TODO: try $CACHE->allPlayers = ...
		$players = self::LoadConfigured($cid);

		// do not consider players from the config file, but only those from the DB
		foreach ($players as $s => $v) {
			$nameLC = mb_strtolower($v['name'], 'utf8');
			$CACHE->allPlayers[$nameLC] = $v;
		}

		// load additional player names
		$recentPlayers = array();
		$someMonthsAgo = strtotime("- {$forgetPlayersAfter} months"); // find only players who replied within the last N months
		$sql = "SELECT DISTINCT `name` FROM `{$tables['replies']}` "
			. "WHERE `club_id` = '{$cid}' "
			. "AND `when` > {$someMonthsAgo}";
		$result = DbQuery($sql);
		if (mysqli_num_rows($result) > 0) {
			while ($row = mysqli_fetch_assoc($result)) {
				$name = trim($row['name']);
				$nameLC = mb_strtolower($name, 'utf8');
				$recentPlayers[] = $nameLC;
				if (isset($players[ $nameLC ])) {
					// player is a configured player
					continue;
				}
				$aliasLC = strtolower(@$playerAliases[ $nameLC ]);
				if ($aliasLC) {
					if (isset($players[$aliasLC])) {
						// name is an alias for a configured player
						continue;
					}
				}
				// add unknown player to list
				$CACHE->allPlayers[ $nameLC ] = array('name' => $name);
			}
		}

		ksort($CACHE->allPlayers);

		if ($forgetConfiguredPlayers) {
			foreach (array_keys($players) as $nameLC) {
				if (!in_array($nameLC, $recentPlayers)) {
					unset($CACHE->allPlayers[$nameLC]);
				}
			}
		}
		unset($players);
		unset($recentPlayers);

		return $CACHE->allPlayers;
	}

	// load names of non-configured players from DB
	public static function LoadUnknown($cid) {
		print 'LoadUnknown: Not implemented.';
		return false;
	}
}

////////////////////////////////////////////////////////////////////////////////
//// DEPRECATED ////
////////////////////////////////////////////////////////////////////////////////
$PlayerModel = new stdClass();
$PlayerModel->fields =& Player::$fields;

function LoadPlayer($playerUID, $cid = 'use $club_id') {
	die('deprecated :'.__FUNCTION__);
	return Player::Load($playerUID, $cid);
}

function SavePlayer($player) {
	die('deprecated :'.__FUNCTION__);
	return Player::Save($player);
}

function GetCurrentPlayerStatus($name, $cid) {
	die('deprecated :'.__FUNCTION__);
	return Player::GetCurrentStatus($name, $cid);
}

function StoreReply($reply, $name, $text, $cid) {
	die('deprecated :'.__FUNCTION__);
	return Player::StoreReply($reply, $name, $text, $cid);
}

// load data of configured players from DB
function LoadConfiguredPlayers($cid) {
	die('deprecated :'.__FUNCTION__);
	return Player::LoadConfigured($cid);
}
// load _all_ players (configured and not) from DB
function LoadAllPlayers($cid) {
	die('deprecated :'.__FUNCTION__);
	return Player::LoadAll($cid);
}
