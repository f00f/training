<?php
/*
 * Created on 04.06.2006
 */
require_once 'inc/conf.inc.php';

define('SPAM_SAME_IP_TIMEOUT', 120);
define('SPAM_SAME_IP_COUNT', 2);
define('SPAM_SAME_USER_TIMEOUT', 300);

define('SECONDS_PER_DAY', 86400);

if (!defined('ON_TEST_SERVER')) {
	define('ON_TEST_SERVER', (false !== strpos($_SERVER['HTTP_HOST'], '.test')));
}

$tag2Int = array(
	'Mo' => 1,
	'Di' => 2,
	'Mi' => 3,
	'Do' => 4,
	'Fr' => 5,
	'Sa' => 6,
	'So' => 0,
);


function AddWithEndDate(&$pTraining, $pEndDate) {
	global $date, $training;
	if ($date <= $pEndDate) {
		$training[] = $pTraining;
	}
}

function AddWithStartDate(&$pTraining, $pStartDate) {
	global $date, $training;
	$oneWeekBefore = date('Ymd', strtotime($pStartDate) - 7*SECONDS_PER_DAY);
	if ($date > $oneWeekBefore) {
		$training[] = $pTraining;
	}
}

function AddSingleDate(&$pTraining, $pDate) {
	global $date, $training;
	$oneWeekBefore = date('Ymd', strtotime($pDate) - 7*SECONDS_PER_DAY);
	if ($date > $oneWeekBefore AND $date <= $pDate) {
		$training[] = $pTraining;
	}
}

function GetAction() {
	if ((boolean) @$_REQUEST['zusage']) {
		return 'add';
	}
	if ((boolean) @$_REQUEST['absage']) {
		return 'remove';
	}
	if ((boolean) @$_REQUEST['reset']) {
		return 'reset';
	}
}

function InsertRow($p_name, $p_text, $p_status) {
	global $table, $aliases;
	global $ip, $host;

	$p_nameLC = strtolower($p_name);
	$p_name = isset($aliases[$p_nameLC]) ? $aliases[$p_nameLC] : $p_name;

	DbQuery("INSERT INTO `{$table}` "
		. "(`name`, `text`, `when`, `status`, `ip`, `host`) "
		. "VALUES "
		. "('{$p_name}', '{$p_text}', '".time()."', '{$p_status}', '{$ip}', '{$host}')");
}

function SpamCheck($p_name, $p_ip) {
	if (@ON_TEST_SERVER) {
		return;
	}

	global $table;

	# Find all updates during the last SPAM_SAME_IP_TIMEOUT seconds that
	# were made from $p_ip
	$result = DbQuery("SELECT `when` "
		. "FROM `{$table}` "
		. "WHERE `ip` = '{$p_ip}' "
		. "AND `name` <> 'RESET' "
		. "AND `when` > ".(time()-SPAM_SAME_IP_TIMEOUT));
	if (!$result) {
		die (mysql_error());
	}
	if (SPAM_SAME_IP_COUNT <= mysql_num_rows($result)) {
//print '<h3>SPAM_SAME_IP_TIMEOUT!</h3>';
		Redirect();
	}

	$lastIPOfUser = '';
	# Finde letzte IP von der aus $p_name aktualisiert wurde
	$result = DbQuery("SELECT `ip`, `when` AS `last_update_of_user` "
		. "FROM `{$table}` "
		. "WHERE `name` = '{$p_name}' "
		. "AND `when` > ".(time()-SPAM_SAME_USER_TIMEOUT)." "
		. "ORDER BY `when` DESC "
		. "LIMIT 1");
	if (!$result) {
		die (mysql_error());
	}
	if (0 < mysql_num_rows($result)) {
		$row = mysql_fetch_assoc($result);
		$lastIPOfUser		= $row['ip'];
	}
/*
 * Redirect wenn ein User innerhalb von SPAM_SAME_USER_TIMEOUT von der gleichen IP
 * aktualisiert wird. Es soll möglich sein einen User kurz nacheinander von
 * unterschiedlichen IPs aus zu aktualisieren
 */
	if ($lastIPOfUser == $p_ip) {
//print '<h3>SPAM_SAME_USER_TIMEOUT!</h3>';
		Redirect();
	}

//if ('131.188.24.2' == $GLOBALS['ip'])
//{
//	print "IP: {$p_ip}<br />".
//		"Name: {$p_name}<br />".
//		"LastUpdateFromIP: {$lastUpdateFromIP}<br />".
//		"LastUserFromIP: {$lastUserFromIP}<br />".
//		"LastUpdateOfUser: {$lastUpdateOfUser}<br />".
//		"LastIPOfUser: {$lastIPOfUser}<br />";
//	die();
//}

	return true;
}

function Redirect() {
	header('Location: ./?nocache='.time());
	exit;
}

# calculate next training date
function FindNextTraining() {
	global $training;
	global $tag2Int;

	$trainingDates = array();
	foreach($training as $t) {
		$wdayIdx = $tag2Int[$t['tag']];
		$trainingDates[$wdayIdx] = $t;
	}

	$weekday      = date('w');
	$daysLeft     = 0;
	$nextTraining = false;
	$iter         = 0;

//	$now = strtotime('+1 hour'); // timezone correction
	$nowTime = date('H:i');
	while (false === $nextTraining AND ++$iter <= 8) { // 8, ok 
		if (isset($trainingDates[$weekday]) AND ($daysLeft > 0 OR $trainingDates[$weekday]['zeit'] >= $nowTime)) {
			$nextTraining = array(
				'wtag'    => $trainingDates[$weekday]['tag'],
				'datum'   => strtotime('+ '.$daysLeft.'days'),
				'zeit'    => $trainingDates[$weekday]['zeit'],
				'ort'     => $trainingDates[$weekday]['ort'],
				'anreise' => $trainingDates[$weekday]['anreise'],
			);
		}
		$weekday = ++$weekday % 7;
		++$daysLeft;
	}
	return $nextTraining;
}

function FirstWord($p_text) {
	$matches = array();
	preg_match('/^(.*?)[^A-Za-z0-9äöüßÄÖÜ]/', $p_text, $matches);
	return $matches[1];
}

function UpdateFiles() {
	global $allPlayers, $nextTraining, $lastUpdate, $anzahlZugesagt, $anzahlAbgesagt, $zugesagt, $abgesagt, $nixgesagtTendenzJa, $nixgesagtKeineTendenz, $nixgesagtTendenzNein;

	// store stats like next training's date and those JavaScript variables
	$html = '<script>
		var namen = new Array(\''.implode("', '", $allPlayers).'\');
		var naechstesTrain = '.$nextTraining['when'].';
		</script>
		Das nächste Training ist am <strong>'.$nextTraining['wtag'].', '.date('d.m.', $nextTraining['datum']).' um '.$nextTraining['zeit'].'</strong> (Beckenzeit) in <strong>'.$nextTraining['ort'].'</strong><br />
		Anreise empfohlen um '.$nextTraining['anreise'].' ;-)<br />
		<br />
		Letzte Meldung am '.date('d.m.y \u\m H:i', $lastUpdate).'<br />';

	$fh = fopen('inc/stats.html', 'w');
	fwrite($fh, $html);
	fclose($fh);

	// store people's status
	$html = '<strong class="zusage">zugesagt '.(1 == $anzahlZugesagt ? 'hat' : 'haben').' '.$anzahlZugesagt.':</strong><br />
'.($anzahlZugesagt ? implode('; ', $zugesagt) : '---').'<br />
<br />
<strong class="absage">abgesagt '.(1 == $anzahlAbgesagt ? 'hat' : 'haben').' '.$anzahlAbgesagt.':</strong><br />
'.($anzahlAbgesagt ? implode('; ', $abgesagt) : '---').'<br />
<br />
<div id="nixgesagt">
<strong>nix gesagt haben bisher:</strong><br />
'.implode('; ', $nixgesagtTendenzJa).'<br />
'.implode('; ', $nixgesagtKeineTendenz).'<br />
'.implode('; ', $nixgesagtTendenzNein).'<br />
</div>';

	$fh = fopen('inc/beteiligung.html', 'w');
	fwrite($fh, $html);
	fclose($fh);
}

function SendMail($p_action, $p_name, $p_anzZu, $p_anzAb, $p_next) {
	if (@ON_TEST_SERVER OR !$p_action) { return; }

	if ('reset' == $p_action) {
		$subject	= 'Training - Reset';
		mail('training-test@uwr1.de', 'Training - Reset', 'k/T',
			"From: \"[{$teamNameShort}] Trainingsliste\" <training-{$emailFrom}@uwr1.de>\r\n"
				. "Sender: training-{$emailFrom}@uwr1.de\r\n"
				. "Return-Path: <training-{$emailFrom}@uwr1.de>\r\n"
		);
		return;
	}

	$toArray = $GLOBALS['spieler'];
	$anzahlGesamt = $p_anzZu + $p_anzAb;
	foreach ($toArray as $spielerId => $spieler) {
		// user will jede $freq-te mail
		if (0 < $spieler['freq'] AND 0 == $anzahlGesamt % $spieler['freq']) {
			$toArray[$spielerId]['nixgesagt'] = in_array($spieler['name'], $GLOBALS['nixgesagt']);
		}
		// user will die ersten $freq mails
		elseif (0 > $spieler['freq'] AND $anzahlGesamt <= abs($spieler['freq'])) {
			$toArray[$spielerId]['nixgesagt'] = in_array($spieler['name'], $GLOBALS['nixgesagt']);
		}
		// user will keine mails
		else {
			unset($toArray[$spielerId]);
		}

		// user will keine mail wenn er sich gerade selbst an-/abgemeldet hat
		if (($p_name == $spieler['name']) AND @$spieler['keineSelbstMail']) {
			unset($toArray[$spielerId]);
		}

		// user will keine mail wenn er sich bereits an-/abgemeldet hat
		if (@$spieler['keineMailsNachMeldung'] AND !$toArray[$spielerId]['nixgesagt']) {
			unset($toArray[$spielerId]);
		}
	}

	$meldungStatus = '';
	if ('add' == $p_action) {
		$meldungStatus = 'zum Training angemeldet.';
		$betreffStatus = 'Zusage';
	}
	if ('remove' == $p_action) {
		$meldungStatus = 'vom Training abgemeldet.';
		$betreffStatus = 'Absage';
	}
	$subject       = "[UWR] Training: {$betreffStatus} von {$p_name} ({$p_next['wtag']}, ".date('d.m.', $p_next['datum']).", {$p_next['zeit']} Uhr)";
	$trainingsUrl  = $rootUrl;
	$meldeUrl      = $trainingsUrl.'training.php?text=';
	$zwischenstand = "Zwischenstand: {$p_anzZu} Zusagen, {$p_anzAb} Absagen.\n"
				. "Den aktuellen Stand findest Du hier: {$trainingsUrl}\n\n";
	$neu = "Funktionen:\n"
		. "+ Einstellbare E-Mail Häufigkeit (mir sagen wie gewünscht):\n"
		. "  - Wahlweise nur die ersten x Mails oder jede x-te Mail bekommen.\n"
		. "  - Wahlweise keine Mail für die eigene Meldung bekommen.\n"
		. "  - Wahlweise keine Mails mehr bekommen nachdem man sich gemeldet hat.\n"
		. "+ Direkte An-/Abmeldung aus den Mails\n"
		. "+ Ein längerer Text ist möglich, das erste Wort wird als Name erkannt,\n"
		. "  z.B.: \"Flo muss schlafen\"\n\n";
	$ps = '';

//$to = 'training-test@uwr1.de';
//$subject .= ' - testing -';
	foreach ($toArray as $empf) {
		$anrede = "Hallo {$empf['name']},\n\n";
		$meldung = (($p_name == $empf['name']) ? 'Du hast dich' : "{$p_name} hat sich");
		$meldung .= " gerade {$meldungStatus}\n\n";
		$aufforderung = '';
		if ($empf['nixgesagt']) {
			$anmeldeUrl = $meldeUrl.$empf['name'].'&zusage=1';
			$abmeldeUrl = $meldeUrl.$empf['name'].'&absage=1';
			$aufforderung = "Kommst Du am {$p_next['wtag']}, ".date('d.m.', $p_next['datum'])." auch?\n"
					. "+ Ja:    {$anmeldeUrl}\n"
					. "- Nein:  {$abmeldeUrl}\n\n";
		}

		if (@ON_TEST_SERVER) {
/*
			print "mail({$empf['email']},
					{$subject},
					{$anrede}.{$meldung}.{$aufforderung}.{$zwischenstand}.{$neu}.{$ps},
					\"From: \\\"[{$teamNameShort}] Trainingsliste\\\" <training-{$emailFrom}@uwr1.de>\\n\"
					. \"Sender: training-{$emailFrom}@uwr1.de\\n\"
					. \"Return-Path: <training-{$emailFrom}@uwr1.de>\\n\"
			);";
			print "<br>";
			print '$empf[\'name\']='.($empf['nixgesagt']?1:0);
			print "<br><br>";
*/
		} else {
			mail($empf['email'],
				$subject,
				$anrede.$meldung.$aufforderung.$zwischenstand.$neu.$ps,
				"From: \"[{$teamNameShort}] Trainingsliste\" <training-{$emailFrom}@uwr1.de>\n"
					. "Sender: training-{$emailFrom}@uwr1.de\n"
					. "Return-Path: <training-{$emailFrom}@uwr1.de>\n"
			);
		}
	}
}

function DbQuery($query) {
	$result	= mysql_query($query);
	if (mysql_errno() != 0) {
		die(mysql_error() . '<br />Query was: ' . $query);
	}
	return $result;
}
?>