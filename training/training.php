<?php
require_once 'inc/lib.inc.php';
require_once 'inc/spieler.inc.php';
require_once 'inc/trainingszeiten.inc.php';
require_once 'inc/dbconf.inc.php';
require_once 'inc/model_player.inc.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);

$ip     = $_SERVER['REMOTE_ADDR'];
$host   = gethostbyaddr($ip);
$action = GetAction();
$f_text = @trim($_REQUEST['text']);
$f_player = FirstWord($f_text.' ');
$sendMails = false;

if (('add' == $action OR 'remove' == $action) AND !$f_text) {
	//@@@ DEBUG
//	print '$f_text: "'.$f_text.'"<br>';
//	print '$f_player: "'.$f_player.'"<br>';
	trigger_error('Kein Spieler ausgewählt.', E_USER_ERROR);
}


# connect to db
mysql_connect($dbHost, $dbUser, $dbPass);
mysql_select_db($dbDB);

# load players
$allPlayers = array();
$spieler = loadPlayerDataFromDB($teamId);
foreach ($spieler as $s) {
	$allPlayers[strtolower($s['name'])] = $s['name'];
}

# load additional player names
$sixMonthsAgo = strtotime('- 6 months');
$result = DbQuery("SELECT DISTINCT `name` FROM `{$table}`"
	. " WHERE `name` != 'RESET'"
	. " AND `when` > {$sixMonthsAgo}");
if (mysql_num_rows($result) > 0) {
	while ($row = mysql_fetch_assoc($result)) {
		$allPlayers[strtolower($row['name'])] = $row['name'];
	}
}

# find last reset before today
$result = DbQuery("SELECT MAX(`when`) AS `LAST_RESET`"
	. "FROM `{$table}` "
	. "WHERE `name` = 'RESET' "
	. "AND `when` < '".time()."'");
$row = mysql_fetch_assoc($result);
$lastReset = $row['LAST_RESET'];
if (!$lastReset) {
	$lastReset = time();
}

# find next reset after today
$nextReset = false;
$result = DbQuery("SELECT MIN(`when`) AS `NEXT_RESET`"
	. "FROM `{$table}` "
	. "WHERE `name` = 'RESET' "
	. "AND `when` > '".time()."'");
if (mysql_num_rows($result) > 0) {
	$row = mysql_fetch_assoc($result);
	$nextReset = $row['NEXT_RESET'];
}
if (!$nextReset) {
	// assume at least one train per week
	$nextReset = strtotime('+ 1 week');
}

# load current status for current player
$currentPlayerStatus = false;
$result = DbQuery("SELECT `status` FROM `{$table}`"
	. " WHERE `name` = '{$f_player}'"
	. " AND `when` >= {$lastReset}"
	. " AND `when` <= {$nextReset}"
	. " ORDER BY `when` DESC"
	. " LIMIT 1");
$row = mysql_fetch_assoc($result);
if (mysql_num_rows($result) > 0) {
	$currentPlayerStatus = $row['status'];
}

$f_playerLC = strtolower($f_player);
if (!empty($allPlayers[$f_playerLC])) {
	$playerNormalized = $allPlayers[$f_playerLC];
	$f_text = str_replace($f_player, $playerNormalized, $f_text);
	$f_player = $playerNormalized; 
} else {
	if (@$f_player) {
		$allPlayers[$f_playerLC] = $f_player;
	}
}
setcookie('spieler', $f_player, time()+2419200, '/');

# update db
switch ($action) {
	case 'add':
//		SpamCheck($f_player, $ip);
		InsertRow($f_player, $f_text, 'ja');
		$sendMails = ('ja' != $currentPlayerStatus);
		break;
	case 'remove':
//		SpamCheck($f_player, $ip);
		InsertRow($f_player, $f_text, 'nein');
		$sendMails = ('nein' != $currentPlayerStatus);
		break;
	case 'reset':
		InsertRow('RESET', '', '');
		$sendMails = false;
		break;
}

$zugesagt    = array();
$abgesagt    = array();
$nixgesagtLc = $allPlayers;
$lastUpdate  = 0;

# gather data of all players
$result = DbQuery("SELECT `name`, `text`, `status`, `when`"
	. " FROM `{$table}`"
	. " WHERE `when` >= '{$lastReset}' AND `when` <= '{$nextReset}' AND `name` != 'RESET'"
	. " ORDER BY `when` DESC");

while ($row = mysql_fetch_assoc($result)) {
	$lastUpdate = max($lastUpdate, $row['when']);
	$lcName = strtolower($row['name']);
	if (empty($nixgesagtLc[$lcName])) {
		continue;
	}
/*
	if (isset($allPlayers[$lcName])) {
		$nameNormalized = $allPlayers[$lcName];
	}
*/
	if ('ja' == $row['status']) {
		$zugesagt[] = $row['text'];
	}
	if ('nein' == $row['status']) {
		$abgesagt[] = $row['text'];
	}
	unset($nixgesagtLc[$lcName]);
}
if (0 == $lastUpdate) {
	$lastUpdate = $lastReset;
}

$nixgesagtTendenzJa = array();
$nixgesagtKeineTendenz = array();
$nixgesagtTendenzNein = array();
foreach ($nixgesagtLc as $nameLc => $name) {
	$tendenz = @$spieler[$nameLc]['tendenz'];
	if (!$tendenz) {
		$nixgesagtKeineTendenz[$nameLc] = "<span>{$name}</span>";
	} else {
		$tendenzNein = ('nein' == $tendenz);
		$class = ' class="' . ($tendenzNein ? 'absage' : 'zusage') . '"';
		$grund = @$spieler[$nameLc]['grund'];
		if ($grund) {
			$grund = ' ('.$grund.')';
		}
		$nameSpan = "<span{$class}>{$name}{$grund}</span>";
		if ($tendenzNein) {
			$nixgesagtTendenzNein[$nameLc] = $nameSpan;
		} else {
			$nixgesagtTendenzJa[$nameLc] = $nameSpan;
		}
	}
}
$nixgesagt = array_values($nixgesagtLc);

$anzahlZugesagt = count($zugesagt);
$anzahlAbgesagt = count($abgesagt);

sort($zugesagt, SORT_STRING);
sort($abgesagt, SORT_STRING);
ksort($nixgesagtTendenzJa, SORT_STRING);
ksort($nixgesagtKeineTendenz, SORT_STRING);
ksort($nixgesagtTendenzNein, SORT_STRING);

$nextTraining = FindNextTraining();
list($ntHour, $ntMin) = explode(':', $nextTraining['zeit']);
$ntMin = (int) $ntMin; // kludge: cut the tail
$ntDay	= date('d', $nextTraining['datum']);
$ntMon	= date('m', $nextTraining['datum']);
$ntYear	= date('Y', $nextTraining['datum']);
$nextTraining['when'] = mktime($ntHour, $ntMin, 0, $ntMon, $ntDay, $ntYear);
# write a RESET with the training's date into the db 
$result	= DbQuery("REPLACE INTO `{$table}` "
	. "(`name`, `text`, `when`, `status`, `ip`, `host`) "
	. "VALUES "
	. "('RESET', '', '{$nextTraining['when']}', '', '', '---')");

mysql_close();

UpdateFiles();
if (true === $sendMails) {
	SendMail($action, $f_player, $anzahlZugesagt, $anzahlAbgesagt, $nextTraining);
}
Redirect();
?>