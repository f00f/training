<?php
require_once 'inc/lib.inc.php';
require_once 'inc/spieler.inc.php';
require_once 'inc/trainingszeiten.inc.php';
require_once 'inc/dbconf.inc.php';
require_once 'inc/model_player.inc.php';
require_once 'inc/model_practice_time.inc.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);

# Gather information about remote host
# and parse parameters
$ip     = $_SERVER['REMOTE_ADDR'];
$host   = gethostbyaddr($ip);
$action = GetAction();
$f_text = @trim($_REQUEST['text']);
$f_player = FirstWord($f_text.' ');
$sendMails = false;

if (('add' == $action OR 'remove' == $action) AND !$f_text) {
	trigger_error('Kein Spieler ausgewählt.', E_USER_ERROR);
}


# connect to db
mysql_connect($dbHost, $dbUser, $dbPass);
mysql_select_db($dbDB);

# load players
$allPlayers = array();
$spieler = LoadAllPlayers($teamId);
foreach ($spieler as $nameLC => $s) {
	$allPlayers[$nameLC] = $s['name'];
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
		$statusChanged = Player::StoreReply('ja', $f_player, $f_text, $teamId);
		break;
	case 'remove':
//		SpamCheck($f_player, $ip);
		$statusChanged = Player::StoreReply('nein', $f_player, $f_text, $teamId);
		break;
	case 'reset':
		InsertRow('RESET', '', '');
		$statusChanged = false;
		break;
}
$sendMails = false;

$zugesagt    = array();
$abgesagt    = array();
$nixgesagtLc = $allPlayers;
$lastUpdate  = 0;

# gather all replies
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

$nextTraining = FindNextPracticeTime($teamId);
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