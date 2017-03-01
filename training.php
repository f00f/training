<?php
require_once 'config/conf.inc.php';
require_once 'inc/lib.inc.php';
require_once 'inc/model_player.inc.php';
require_once 'inc/model_practice_time.inc.php';

if (@ON_TEST_SERVER) {
	ini_set('display_errors', 1);
	error_reporting(E_ALL);
}

// Which app did the reply come from?
// Current values: Web/Android/null
$f_app = @trim($_REQUEST['app']);
if (!$f_app) { $f_app = 'web'; }
// Which version of the app was used?
// Currently only send by recent versions of the Android app.
// May be null, the version name (e.g. 1.5.1), or the version ID (e.g. 1, 2, 3, ...)
$f_app_version = @trim($_REQUEST['app_ver']);
if (!$f_app_version) { $f_app_version = '-1'; }
// Gather information about remote host
// and parse parameters
$ip     = $_SERVER['REMOTE_ADDR'];
$host   = gethostbyaddr($ip);
$action = GetAction();
if ('android' == $f_app && $f_app_version < 16) {
	// Old android versions send text as iso-8859-1
	$f_text = @trim($_REQUEST['text']);
} else {
	$f_text = urldecode(@trim($_REQUEST['text']));
}
$f_player = FirstWord($f_text.' ');
// The club id the reply was meant for.
// Currently only send by recent versions of the Android app.
// E.g. ba, stc, ...
/*
$f_club_id = @trim($_REQUEST['club_id']);
if (!$f_club_id) {
	list($clubIdFromHost, $uwr1de) = explode('.', $_SERVER['HTTP_HOST'], 2);
	if ('uwr1.de' == $uwr1de) {
		$f_club_id = $clubIdFromHost;
	} else {
		$f_club_id = 'unknown';
	}
}
*/
$sendMails = false;

// BA: known bot - move to hook and plugin
if ('ba' == $club_id) {
	if ('150.70.97.43' === $ip) {
		Redirect();
	}
}

if (('add' == $action OR 'remove' == $action) AND !$f_text) {
	trigger_error('Kein Spieler ausgewählt.', E_USER_ERROR);
}


# connect to db
$mysqli = new mysqli($dbHost, $dbUser, $dbPass, $dbDB);
/* check connection */
if ($mysqli->connect_errno) {
    die("Connect failed: " . $mysqli->connect_errno);
}

# load players
$allPlayers = array();
$spieler = Player::LoadAll($club_id);
foreach ($spieler as $nameLC => $s) {
	$allPlayers[$nameLC] = $s['name'];
}

// Flag to indicate when the stats were reset
// either because a training passed, or b/c someone called the 'reset' action
$isReset = false;

# make sure that a record for the next practice session is created
$foo = PracticeTime::GetNext($club_id);

# find next session after now
# offest by RESET_DELAY
// TODO: replace this: sessions which are currently in progress should also be considered.
$trainHorizon = time() - RESET_DELAY;
$nextSession = false;
$sql = "SELECT `session_id` AS `NEXT_SESSION` "
	. "FROM `{$tables['practice_sessions']}` "
	. "WHERE `club_id` = ? "
	. "AND `session_id` > ? "
	. "ORDER BY `session_id` ASC "
	. "LIMIT 1";
$dt = date('Y-m-d H:i:s', $trainHorizon);
$result = DbQueryP($sql, 'ss', $club_id, $dt);
if (mysqli_num_rows($result) > 0) {
	$row = mysqli_fetch_assoc($result);
	$nextSession = $row['NEXT_SESSION'];
}
if (!$nextSession) {
	print 'Fehler: Keine zukünftige Trainingszeit gefunden.<br>';
	die();
}

# load current status for current player
$currentPlayerStatus = false;
$sql = "SELECT `status` "
	. "FROM `{$tables['replies']}` "
	. "WHERE `club_id` = ? "
	. "AND `name` = ? "
	. "AND `session_id` = ? "
	. "ORDER BY `when` DESC "
	. "LIMIT 1";
$result = DbQueryP($sql, 'sss', $club_id, $f_player, $nextSession);
$row = mysqli_fetch_assoc($result);
if (mysqli_num_rows($result) > 0) {
	$currentPlayerStatus = $row['status'];
}

# normalize player name
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
setcookie('spieler', utf8_decode(str_replace(' ', '%20', $f_player)), time()+2419200, '/');

# update db
$statusChanged = false;
switch ($action) {
	case 'add':
//		SpamCheck($f_player, $ip);
		$statusChanged = Player::StoreReply('ja', $f_player, $f_text, $club_id, $f_app, $f_app_version);
		break;
	case 'remove':
//		SpamCheck($f_player, $ip);
		$statusChanged = Player::StoreReply('nein', $f_player, $f_text, $club_id, $f_app, $f_app_version);
		break;
	case 'reset':
		//InsertRow('RESET', '', '', $f_app, $f_app_version, $club_id);// deprecated
		$statusChanged = false;
		$isReset = true;
		break;
}
$sendMails = $statusChanged;
if (@ON_TEST_SERVER) {
	$sendMails = false;
}

$zugesagt      = array();
$zugesagtNamen = array();
$abgesagt      = array();
$nixgesagtLc   = $allPlayers;
$lastUpdate    = 0;

# gather all replies
// TODO: move to PlayerModel or PracticeTimeModel?
$sql = "SELECT `name`, `text`, `status`, `when`"
	. "FROM `{$tables['replies']}` "
	. "WHERE `club_id` = ? "
	. "AND `session_id` = ? "
	. "ORDER BY `when` DESC";
$result = DbQueryP($sql, 'ss', $club_id, $nextSession);

while ($row = mysqli_fetch_assoc($result)) {
	$lastUpdate = max($lastUpdate, $row['when']);
	$lcName = strtolower($row['name']);
	if (empty($nixgesagtLc[$lcName])) {
		continue;
	}

	// this normalization should not be neccessary, since name and text are normalized before inserting them into the DB
	if (!empty($allPlayers[$lcName])) {
		$nameNormalized = $allPlayers[$lcName];
		$txt = str_replace($row['name'], $nameNormalized, $row['text']);
		$name = $nameNormalized;
	} else {
		$txt = $row['text'];
		$name = $row['name'];
	}

	if ('ja' == $row['status']) {
		$zugesagt[] = $row['text'];
		$zugesagtNamen[] = $row['name'];
	}
	if ('nein' == $row['status']) {
		$abgesagt[] = $row['text'];
	}
	//if ($isReset) {
	//	EvaluateFollowUp($row['name'], $row['text']);
	//}
	unset($nixgesagtLc[$lcName]);
}
if (0 == $lastUpdate) {
	$lastUpdate = -1;
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

$nextTraining = PracticeTime::GetNext($club_id);
list($ntZeitBegin, $ntZeitEnd) = explode('-', $nextTraining['zeit']);
list($ntHourBegin, $ntMinBegin) = explode(':', $ntZeitBegin);
list($ntHourEnd, $ntMinEnd) = explode(':', $ntZeitEnd);
$ntMinBegin = (int) $ntMinBegin; // kludge: cut the tail
$ntDay	= date('d', $nextTraining['datum']);
$ntMon	= date('m', $nextTraining['datum']);
$ntYear	= date('Y', $nextTraining['datum']);
$nextTraining['begin'] = mktime($ntHourBegin, $ntMinBegin, 0, $ntMon, $ntDay, $ntYear);
$nextTraining['end']   = mktime($ntHourEnd, $ntMinEnd, 0, $ntMon, $ntDay, $ntYear);
$nextTraining['when']  = $nextTraining['begin'];


$mysqli->close();

UpdateFiles();

if (true === $sendMails) {
	SendMail($action, $f_player, $anzahlZugesagt, $anzahlAbgesagt, $nextTraining);
}

Redirect(null, !empty($action));
