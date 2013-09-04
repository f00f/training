<?php
////////////////////////////////////////////////////////////////////////////////
//// Players ////
////////////////////////////////////////////////////////////////////////////////

$PlayerModel = new stdClass();

$PlayerModel->fields = array(
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


function LoadPlayer($playerUID, $cid = 'use $club_id') {
	global $tables;

	$q = "SELECT `uid`, `club_id`, `player_name`, `player_data` FROM `{$tables['players_conf']}` "
		. "WHERE `uid` = '{$playerUID}'";
	if ($cid !== false) {
		$q .= " AND `club_id` = '{$cid}'";
	}
	$result = DbQuery($q);
	if (mysql_num_rows($result) != 1) {
		return false;
	}

	$row = mysql_fetch_assoc($result);
	$player = unserialize($row['player_data']);
	$player['uid'] = $row['uid'];
	$player['club_id'] = $row['club_id'];
	$player['nameLC'] = $row['player_name'];

	return $player;
}

function SavePlayer($player) {
	global $PlayerModel;
	global $tables;

	$warnings = array();
	$errors = array();
	$success = ValidateInstance($player, $PlayerModel->fields, $warnings, $errors);
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
	$data = serialize(GetOptionalData($player, $PlayerModel->fields));

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

// load data of configured players from DB
function LoadConfiguredPlayers($cid) {
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
	if (mysql_num_rows($result) > 0) {
		while ($row = mysql_fetch_assoc($result)) {
			$pData = unserialize($row['player_data']);
			$pData['uid'] = $row['uid'];
			$CACHE->configuredPlayers[ $row['player_name'] ] = $pData;
		}
	}

	return $CACHE->configuredPlayers;
}
// load _all_ players (configured and not) from DB
function LoadAllPlayers($cid) {
	global $CACHE;
	if (isset($CACHE->allPlayers) && false !== @$CACHE->allPlayers) {
		return $CACHE->allPlayers;
	}

	$CACHE->allPlayers = array();

	global $tables;
	global $playerAliases;

	// TODO: try $CACHE->allPlayers = ...
	$players = LoadConfiguredPlayers($cid);

	// do not consider players from the config file, but only those from the DB
	foreach ($players as $s => $v) {
		$nameLC = mb_strtolower($v['name'], 'utf8');
		$CACHE->allPlayers[$nameLC] = $v;
	}

	// load additional player names
	$someMonthsAgo = strtotime('- 2 months'); // only those who replied within the last 2 months
	$result = DbQuery("SELECT DISTINCT `name` FROM `{$tables['replies']}` "
		. "WHERE `club_id` = '{$cid}' "
		. "AND `when` > {$someMonthsAgo}");
	if (mysql_num_rows($result) > 0) {
		while ($row = mysql_fetch_assoc($result)) {
			$name = trim($row['name']);
			$nameLC = mb_strtolower($name, 'utf8');
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
			$CACHE->allPlayers[ $nameLC ] = array('name' => $name);
		}
	}

	ksort($CACHE->allPlayers);

	return $CACHE->allPlayers;
}
