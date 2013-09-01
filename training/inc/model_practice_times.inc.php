<?php
////////////////////////////////////////////////////////////////////////////////
//// Practice Times ////
////////////////////////////////////////////////////////////////////////////////

$PracticeTimesModel = new stdClass();

$PracticeTimesModel->fields = array(
	'uid' => array(
		'type' => 'hidden',
		'values' => 'numeric',
	),
	'club_id' => array(
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

$PracticeTimesModel->dow2int = array(
	'Mo' => 0,
	'Di' => 1,
	'Mi' => 2,
	'Do' => 3,
	'Fr' => 4,
	'Sa' => 5,
	'So' => 6,
);


function LoadPracticeTime($playerUID) {
	global $tables;

	$q = "SELECT `uid`, `club_id`, `player_name`, `player_data` FROM `{$tables['players_conf']}` "
		. "WHERE `uid` = '{$playerUID}'";
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

function SavePracticeTime($pt) {
	global $PracticeTimeModel;
	global $tables;

	$data = ValidateInstance($pt, $PracticeTimeModel->fields);
	$data = serialize($data);

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

// load all practice times from DB
function LoadAllPracticeTimes($cid) {
	global $CACHE;
	//global $PracticeTimesModel;
	global $tag2Int;
	global $tables;

	if (isset($CACHE->allPracticeTimes) && false !== @$CACHE->allPracticeTimes) {
		return $CACHE->allPracticeTimes;
	}

	$CACHE->allPracticeTimes = array();
	$CACHE->activePracticeTimes = array();
	
	$dow2date = array();
	for ($i = 0; $i < 7; $i++) {
		$nowPlusXDays = strtotime("+ {$i} days");
		$dowThen = date('w', $nowPlusXDays);
		$dateThen = date('Y-m-d', $nowPlusXDays);
		$dow2date[$dowThen] = $dateThen;
	}

	$q = "SELECT `practice_id`, `dow`, `begin`, `end`, `first`, `last`, `data` FROM `{$tables['practices_conf']}` "
		. "WHERE `club_id` = '{$cid}' "
		. "ORDER BY `last` DESC, `first` DESC";//TODO: add deleted
	$result = DbQuery($q);
	$now = date('Y-m-d');
	if (mysql_num_rows($result) > 0) {
		while ($row = mysql_fetch_assoc($result)) {
			$row['uid'] = $row['practice_id'];
			$row['data'] = unserialize($row['data']);
			list($h, $m, $s) = explode(':', $row['begin']);
			$row['begin'] = "{$h}:{$m}";
			list($h, $m, $s) = explode(':', $row['end']);
			$row['end'] = "{$h}:{$m}";
			$dowIdx = $tag2Int[$row['dow']];
			$row['dowIdx'] = ($dowIdx - 1) % 7;
			$row['has-started'] = $row['first'] <= $now;
			$row['has-ended'] = $row['last'] < $now;
			$row['active'] = $dow2date[$dowIdx] >= $row['first'] && $dow2date[$dowIdx] <= $row['last'];
			$CACHE->allPracticeTimes[] = $row;
			if ($row['active']) {
				$CACHE->activePracticeTimes[] = $row;
			}
		}
	}
	//asort($CACHE->allPracticeTimes);// TODO: Sort by dow, meaningfully

	return $CACHE->allPracticeTimes;
}
