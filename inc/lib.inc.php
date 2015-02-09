<?php
/*
 * Created on 04.06.2006
 */
date_default_timezone_set('Europe/Berlin');

session_start();

define('SPAM_SAME_IP_TIMEOUT', 120);
define('SPAM_SAME_IP_COUNT', 2);
define('SPAM_SAME_USER_TIMEOUT', 300);

// if a user submits less than LATE_THRESHOLD before start of the training, show notice
define('LATE_THRESHOLD', 1 * 3600); // TODO: make this a config value
// RESET_DELAY_HOURS after begin of the training, switch to next training
define('RESET_DELAY_HOURS', 1); // TODO: make this a config value

// Don't change values below.
define('RESET_DELAY_MINUTES', RESET_DELAY_HOURS * 60);
define('RESET_DELAY', RESET_DELAY_MINUTES * 60);
define('SECONDS_PER_DAY', 86400);
define('PLUGINS_FILE', 'inc/plugins.inc.php');
define('YOU_ARE_LATE_URL', './spaet-dran.shtml');


if (!defined('NO_INCLUDES') || !NO_INCLUDES) {
	require_once 'inc/conf.inc.php';
}
require_once 'PHPMailer/PHPMailerAutoload.php';
require_once 'mailconf.inc.php'; // TODO: adjust values in that file!

if (file_exists(PLUGINS_FILE)) {
	include_once PLUGINS_FILE;
}

if (!defined('ON_TEST_SERVER')) {
	define('ON_TEST_SERVER', (false !== strpos($_SERVER['HTTP_HOST'], '.test')));
}

/* obsolete (but used for import in admin)
TODO: move to admin files
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
*/

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

function InsertRow($p_name, $p_text, $p_status, $p_app = 'web', $p_app_version = 'unknown', $p_club_id = 'unknown') {
	global $table, $aliases;
	global $ip, $host;

	$p_nameLC = strtolower($p_name);
	$p_name = isset($aliases[$p_nameLC]) ? $aliases[$p_nameLC] : $p_name;

	// insert one second later if not a reset
	// this avoids problems with automatic inserts from EvaluateFollowUps
	//
	// insert RESET_DELAY later if a reset
	// this allows to still show player list even after training has started
	$timeOffset = ('RESET' == $p_name) ? RESET_DELAY : 1;

	DbQuery("INSERT INTO `{$table}` "
		. "(`name`, `text`, `when`, `status`, `ip`, `host`, `app`, `app_ver`, `club_id`) "
		. "VALUES "
		. "('{$p_name}', '{$p_text}', '".(time()+$timeOffset)."', '{$p_status}', '{$ip}', '{$host}', '{$p_app}', '{$p_app_version}', '{$p_club_id}')");
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

function Redirect($loc = null, $checkTime = false ) {
	global $nextTraining;

	$now = time();
	if (!$loc) {
		$loc = './?nocache='.$now;
	}

	if ($checkTime && $now > ($nextTraining['when'] - LATE_THRESHOLD)) {
		$loc = YOU_ARE_LATE_URL;
	}

	header("HTTP/1.1 302 Moved Temporarily");
	header("Location: {$loc}");
	exit;
}

function FirstWord($p_text) {
	$matches = array();
	preg_match('/^([\w ]*)/', $p_text, $matches);// TODO: correct pattern? Used to be '/^(.*?)[^A-Za-z0-9äöüßÄÖÜ]/'
	return trim($matches[1]);
}

/* TODO: BA: move to plugin
function GetWasserTemp() {
	return ZapfenTemp();
}
*/

function UpdateHtmlFiles() {
/* TODO: BA:
global $ZTCacheFile;
*/
	global $club_id, $allPlayers, $nextTraining, $lastUpdate,
		$anzahlZugesagt, $anzahlAbgesagt, $zugesagt, $zugesagtNamen, $abgesagt,
		$nixgesagtTendenzJa, $nixgesagtKeineTendenz, $nixgesagtTendenzNein,
		$poolImageFile, $poolThumbFile;

	// store stats like next training's date and those JavaScript variables
	$html = '<script>
		var namen = new Array(\''.implode("', '", $allPlayers).'\');
		var naechstesTrain = '.$nextTraining['end'].';
		</script>
		<div id="nexttraining">Das nächste Training ist am <strong>'.$nextTraining['wtag'].', '.date('d.m.', $nextTraining['datum']).' um '.$nextTraining['zeit'].'</strong> (Beckenzeit) im <strong>'.$nextTraining['ort'].'</strong></div>
		<div id="infos">Anreise empfohlen um '.$nextTraining['anreise'].' ;-)</div>
		<div id="letztem">Letzte Meldung am '.date('d.m.y \u\m H:i', $lastUpdate).'</div>';
/* TODO BA: create hook + plugin
	if ('Zapfendorf' == $nextTraining['ort']) {
		$html .= 'Wassertemperatur: <strong>'.GetWasserTemp().'</strong> (Stand: '.date('d.m., H:i', filemtime($ZTCacheFile)).')<br />';
	}
*/

	$fh = fopen('shtml/'.$club_id.'-stats.html', 'w');
	fwrite($fh, $html);
	fclose($fh);

	// find random pool image
	$fh = fopen('shtml/'.$club_id.'-bad.html', 'w');
	if ('' == @$poolImageFile) {
		fwrite($fh, '');
	} else {
		fwrite($fh, '<div id="badbild"><a href="'.$poolImageFile.'"><img src="'.$poolThumbFile.'" /></a></div>');
	}
	fclose($fh);

	// store people's status
	$html = '<div id="zusagen">
  <strong class="zusage">zugesagt '.(1 == $anzahlZugesagt ? 'hat' : 'haben').' '.$anzahlZugesagt.':</strong>
  <div id="zusager">
'.($anzahlZugesagt ? '<span>' . implode("</span>;\n <span>", $zugesagt) . '</span>' : '---').'
  </div>
  <br />
</div>
<div id="absagen">
  <strong class="absage">abgesagt '.(1 == $anzahlAbgesagt ? 'hat' : 'haben').' '.$anzahlAbgesagt.':</strong>
  <div id="absager">'.($anzahlAbgesagt ? '<span>' . implode('</span>; <span>', $abgesagt) . '</span>' : '---').'
  </div>
<br />
</div>
<div id="nixgesagt">
  <strong>nix gesagt haben bisher:</strong>'."<br />\n"
.implode('; ', $nixgesagtTendenzJa) . ($nixgesagtTendenzJa?"<br />\n":'')
.implode('; ', $nixgesagtKeineTendenz)."<br />\n"
.implode('; ', $nixgesagtTendenzNein)."<br />\n"
.'</div>';

/* TODO STC+BA: move to hook + plugin
	$html .= '<div><p>';
	if (count($zugesagtNamen) < 5)
	{
		$html .= 'Automatische Einteilung (erst ab 5 Spielern)';
	}
	else
	{
		$nikLink = 'http://ba.uwr1.de/training/einteilung/?namen=' . urlencode(implode(',', $zugesagtNamen));
		$html .= '<a href="'.$nikLink.'">Automatische Einteilung</a>';
	}
	$html .= '</p></div>';
*/

	$fh = fopen('shtml/'.$club_id.'-beteiligung.html', 'w');
	fwrite($fh, $html);
	fclose($fh);
}// UpdateHtmlFiles

function UpdateJsonFiles() {
	global $ZTCacheFile;
	global $club_id, $allPlayers, $nextTraining, $lastUpdate,
		$anzahlZugesagt, $anzahlAbgesagt, $zugesagt, $abgesagt,
		$nixgesagtTendenzJa, $nixgesagtKeineTendenz, $nixgesagtTendenzNein,
		$poolImageFile, $poolThumbFile;

	// store people's status
	$jsonTrain = ''
			.'"begin":'.$nextTraining['begin'].','
			.'"end":'.$nextTraining['end'].','
			.'"wtag":"'.$nextTraining['wtag'].'",'
			.'"datum":"'.date('d.m.', $nextTraining['datum']).'",'
			.'"zeit":"'.$nextTraining['zeit'].'",'
			.'"anreise":"'.$nextTraining['anreise'].'",'
			.'"ort":"'.$nextTraining['ort'].'",'
			.'"updated":'.$lastUpdate
			;
	$jsonZuAbCnt = ''
		.'"numZu":'.$anzahlZugesagt.','
		.'"numAb":'.$anzahlAbgesagt.','
		;
	$jsonAllNames = '"names":['.'"'.implode('","', $allPlayers).'"'.']';
	$jsonZuNames = count($zugesagt) ? '"'.implode('","', $zugesagt).'"' : '';
	$jsonAbNames = count($abgesagt) ? '"'.implode('","', $abgesagt).'"' : '';
	$jsonZuAbNames = '"zu":['
			.$jsonZuNames // crashes if zugesagt contains quotes '"'
			.'],'
		.'"ab":['
			.$jsonAbNames // crashes if abgesagt contains quotes '"'
			.']'
		;
	$jsonExtra = '';
	/* TODO BA: create hook + plugin
	if ('Zapfendorf' == $nextTraining['ort']) {
		if ($jsonExtra != '') {
			$jsonExtra .= ',';
		}
		$jsonExtra .= '"temp":{'
			.'"deg":"'.GetWasserTemp().'"'
			.',"updated":'.filemtime($ZTCacheFile)
			.'}';
	}
	*/
	if ('' != @$poolImageFile) {
		if ($jsonExtra != '') {
			$jsonExtra .= ',';
		}
		$jsonExtra .= '"pic":{'
			.'"full":"'.$poolImageFile.'"'
			.',"thumb":"'.$poolThumbFile.'"'
			.'}';
	}
	$jsonExtra = '"x":{'.$jsonExtra.'}';

	// this can be aggressively cached
	$fh = fopen('json/'.$club_id.'-all-players.json', 'w');
	fwrite($fh, utf8_encode('{'.$jsonAllNames.'}'));
	fclose($fh);

	// load this for detailed display
	$fh = fopen('json/'.$club_id.'-training.json', 'w');
	fwrite($fh, utf8_encode('{"train":{'.$jsonTrain.','.$jsonZuAbNames.','.$jsonExtra.'}}'));
	fclose($fh);

	/*
	// load this for widget display, if cached training info is still valid
	$fh = fopen('json/'.$club_id.'-counts.json', 'w');
	fwrite($fh, '{'.$jsonZuAbCnt.'}');
	fclose($fh);

	// load this for widget display, if cached training info is no longer valid
	$fh = fopen('json/'.$club_id.'-training-counts.json', 'w');
	fwrite($fh, '{"train":{'.$jsonTrain.','.$jsonZuAbCnt.'}}');
	fclose($fh);
	*/
	//die('Hannes is am Frickeln.');
}// UpdateJsonFiles

function UpdateFiles() {
	FindBadBild();
	UpdateHtmlFiles();
	UpdateJsonFiles();
}

function FindBadBild()
{
	global $nextTraining, $poolImageFile, $poolThumbFile;
	$poolImageFolder = './badbilder/'.strtolower($nextTraining['ort']).'/';
	if (!file_exists($poolImageFolder) || !is_dir($poolImageFolder)) {
		$poolImageFile = '';
		$poolThumbFile = '';
		return;
	}

	$poolImageFile = RandomFile($poolImageFolder, 'jpg|png|gif');
	// thumb
	$poolThumbFolder = './badbilder/thumbs/'.strtolower($nextTraining['ort']).'/';
	if (!file_exists($poolThumbFolder) || !is_dir($poolThumbFolder)) {
		mkdir($poolThumbFolder);
		copy('./badbilder/thumbs/index_sub.html', $poolThumbFolder.'index.html');
	}
	$poolThumbFile = str_replace($poolImageFolder, $poolThumbFolder, $poolImageFile);
	$thumbMaxH = 240;
	$hasValidThumbnail = true;
	if (!file_exists($poolThumbFile)) { // is there a thumbnail at all?
		$hasValidThumbnail = false;
	}
	if ($hasValidThumbnail) { // is the thumbnail small enough?
		$imageSize = getimagesize($poolThumbFile);
		$imageH = $imageSize[1];
		if ($imageH > $thumbMaxH) {
			$hasValidThumbnail = false;
		}
	}
	if (!$hasValidThumbnail) { // try creating a new thumbnail
		$hasValidThumbnail = CreateThumbnail($poolImageFile, $poolThumbFile, $thumbMaxH);
	}
	if (!$hasValidThumbnail) { // fall-back
		$poolThumbFile = $poolImageFile;
	}
}

function CreateThumbnail($imageFile, $thumbFile, $maxHeight)
{
	$imageSize = getimagesize($imageFile);
	$imageW = $imageSize[0];
	$imageH = $imageSize[1];
	
	// image is small enough already
	if ($imageH <= $maxHeight) {
		return copy($imageFile, $thumbFile);
	}
	
	// image has to be resized
	$origImg = false;
	if (IMAGETYPE_GIF == $imageSize[2])
		$origImg = imagecreatefromgif($imageFile);
	if (IMAGETYPE_JPEG == $imageSize[2])
		$origImg = imagecreatefromjpeg($imageFile);
	if (IMAGETYPE_PNG == $imageSize[2])
		$origImg = imagecreatefrompng($imageFile);
	if (false === $origImg) {
		return false;
	}
	$thumbH = $maxHeight;
	$thumbW = $imageW / ($imageH / $thumbH);
	$thumbImg = imagecreatetruecolor($thumbW, $thumbH);
	if (false === $thumbImg) {
		return false;
	}
	$success = imagecopyresampled($thumbImg, $origImg, 0, 0, 0, 0, $thumbW, $thumbH, $imageW, $imageH);
	if (false === $success) {
		return false;
	}
	// safe thumbnail
	$qual = 85;
	imageinterlace($thumbImg, 1);
	return imagejpeg($thumbImg, $thumbFile, $qual);
}

// from: http://www.jonasjohn.de/snippets/php/random-file.htm
// mod: /i modifier for extensions pattern
function RandomFile($folder='', $extensions='.*')
{
    // fix path:
    $folder = trim($folder);
    $folder = ($folder == '') ? './' : $folder;

    // check folder:
    if (!is_dir($folder)) { die('invalid folder given!'); }

    // create files array
    $files = array();

    // open directory
    if ($dir = @opendir($folder)) {
        // go trough all files:
        while ($file = readdir($dir)) {
            if (!preg_match('/^\.+$/', $file) and
                preg_match('/\.('.$extensions.')$/i', $file)) {

                // feed the array:
                $files[] = $file;
            }
        }
        // close directory
        closedir($dir);    
    }
    else {
        die('Could not open the folder "'.$folder.'"');
    }

    if (count($files) == 0) {
        die('No files where found :-(');
    }

    // seed random function:
    mt_srand((double)microtime()*1000000);

    // get an random index:
    $rand = mt_rand(0, count($files)-1);

    // check again:
    if (!isset($files[$rand])) {
        die('Array index was not found! very strange!');
    }

    // return the random file:
    return $folder . $files[$rand];
}

function SendMail($p_action, $p_name, $p_anzZu, $p_anzAb, $p_next) {
	global $emailFrom, $rootUrl, $teamNameShort;
	if (@ON_TEST_SERVER OR !$p_action) { return; }

	$mailSender = "training-{$emailFrom}@uwr1.de";
	$mailFrom = "\"[{$teamNameShort}] Trainingsseite\" <{$mailSender}>";
	$mailReturnPath = "<{$mailSender}>";
	$mailHeader = "From: {$mailFrom}\r\n"
				. "Sender: {$mailSender}\r\n"
				. "Return-Path: {$mailReturnPath}\r\n";

	if ('reset' == $p_action) {
		$subject	= 'Training - Reset';
		mail_SMTP('training@uwr1.de', 'Training - Reset', 'k/T');
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
	$subject = "[UWR] Training: {$betreffStatus} von {$p_name}"
		. ' ('
		. "+{$p_anzZu}/-{$p_anzAb}"
		. ' - '
		. "{$p_next['wtag']}, ".date('d.m.', $p_next['datum']).", {$p_next['zeit']} Uhr";
/* TODO BA: integrate this into subject -> hook + plugin
	if ('Zapfendorf' == $p_next['ort']) {
		$subject .= ', ' . GetWasserTemp();
	}
*/
	$subject .=  ')';
	$trainingsUrl = $rootUrl;
	$meldeUrl     = $trainingsUrl.'training.php?text=';
	$wassertemp = '';
/* TODO BA: move to hook + plugin
	$wassertemp = '';
	if ('Zapfendorf' == $p_next['ort']) {
		$wassertemp = 'Wassertemperatur: ' . GetWasserTemp()."\n\n";
	}
*/
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
			print "mail_SMTP({$empf['email']},
					{$subject},
					{$anrede}.{$meldung}.{$aufforderung}.{$zwischenstand}.{$neu}.{$ps}
			);";
			print "<br>";
			print '$empf[\'name\']='.($empf['nixgesagt']?1:0);
			print "<br><br>";
*/
		} else {
			mail_SMTP($empf['email'],
				$subject,
				$anrede.$meldung.$aufforderung.$wassertemp.$zwischenstand.$neu.$ps
			);
		}
	}
}


////////////////////////////////////////////////////////////////////////////////
//// HELPERS ////
////////////////////////////////////////////////////////////////////////////////

function mail_SMTP($to, $subject, $msg) {
	if (!$to) return false;
	if (!$subject) return false;
	if (!$msg) return false;

	$mail = new PHPMailer;

	//$mail->SMTPDebug = 3;                               // Enable verbose debug output

	$mail->isSMTP();                                      // Set mailer to use SMTP
	$mail->Host = MAILER_SMTP_HOST;  // Specify main and backup SMTP servers
	$mail->SMTPAuth = true;                               // Enable SMTP authentication
	$mail->Username = MAILER_USER;                 // SMTP username
	$mail->Password = MAILER_PASSWORD;                           // SMTP password
	//$mail->SMTPSecure = 'ssl';                            // Enable TLS encryption, `ssl` also accepted
	//$mail->Port = 587;                                    // TCP port to connect to
	$mail->Port = 25;                                    // TCP port to connect to

	$mail->From = MAILER_FROM;
	$mail->FromName = MAILER_NAME;
	$mail->addAddress($to);               // Name is optional
	$mail->addReplyTo(MAILER_FROM, MAILER_NAME);
	// Add ReturnPath?
	// Add Sender?

	$mail->WordWrap = 78;                                 // Set word wrap to 78 characters

	$mail->Subject = $subject;
	$mail->Body    = $msg;
	//$mail->AltBody = 'This is the body in plain text for non-HTML mail clients';

	return $mail->send();
}

function DbQuery($query) {
	$result	= mysql_query($query);
	if (mysql_errno() != 0) {
		$msg = mysql_error();
		if (@ON_TEST_SERVER) {
			$msg += '<br />Query was: ' . $query;
		}
		die($msg);
	}
	return $result;
}

$CACHE = new stdClass();


function sani($s) {
	return mysql_real_escape_string($s);
}


////////////////////////////////////////////////////////////////////////////////
//// HTML ////
////////////////////////////////////////////////////////////////////////////////


function html_header() {
	global $pagetitle;
?>
<!DOCTYPE html>
<html lang="de">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="">
    <meta name="author" content="">
    <!-- link rel="shortcut icon" href="../../assets/ico/favicon.png" -->

    <title><?= $pagetitle ?></title>

    <!-- Bootstrap core CSS -->
    <link href="../css/bootstrap.css" rel="stylesheet">

    <!-- Custom styles for this template -->
    <link href="admin.css" rel="stylesheet">

    <!-- HTML5 shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!--[if lt IE 9]>
      <script src="../js/html5shiv.js"></script>
      <script src="../js/respond.min.js"></script>
    <![endif]-->
  </head>

  <body>
<?php
	if (@$_SESSION['error']) {
		print '<div class="container">'
			. "<div class='alert alert-danger'>{$_SESSION['error']}</div>"
			. '</div>';
		unset($_SESSION['error']);
	}
	if (@$_SESSION['warning']) {
		print '<div class="container">'
			. "<div class='alert alert-warning'>{$_SESSION['warning']}</div>"
			. '</div>';
		unset($_SESSION['warning']);
	}
	if (@$_SESSION['notice']) {
		print '<div class="container">'
			. "<div class='alert alert-success'>{$_SESSION['notice']}</div>"
			. '</div>';
		unset($_SESSION['notice']);
	}
} // html_header

function navbar_admin($active = null) {
	global $pagetitle;
?>
    <div class="navbar navbar-fixed-top navbar-default">
      <div class="container">
        <div class="navbar-header">
          <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
          </button>
          <a class="navbar-brand" href="#"><?= $pagetitle; ?></a>
        </div>
        <div class="collapse navbar-collapse">
          <ul class="nav navbar-nav pull-right">
            <li<?=('home'==$active)?' class="active"':''?>><a href="./">Home</a></li>
            <li><a href="../">Trainingsseite</a></li>
            <li class="dropdown<?=('players'==$active)?' active':''?>">
			  <a href="#" class="dropdown-toggle" data-toggle="dropdown">Spieler <b class="caret"></b></a>
			  <ul class="dropdown-menu">
			  <li><a href="players_list.php">Auflisten</a></li>
			  <li><a href="player_add.php">Hinzufügen</a></li>
			  </ul>
			</li>
            <li class="dropdown<?=('practice-times'==$active)?' active':''?>">
			  <a href="#" class="dropdown-toggle" data-toggle="dropdown">Zeiten <b class="caret"></b></a>
			  <ul class="dropdown-menu">
			  <li><a href="practice_times_list.php">Auflisten</a></li>
			  <li><a href="practice_time_add.php">Hinzufügen</a></li>
			  </ul>
			</li>
            <li<?=('config'==$active)?' class="active"':''?>><a href="config_show.php">Konfig</a></li>
            <li<?=('contact'==$active)?' class="active"':''?>><a href="contact.php">Kontakt</a></li>
          </ul>
        </div><!--/.nav-collapse -->
      </div>
    </div>

<?php
}

function html_footer() {
	global $enablePopovers;
?>
    <!-- Bootstrap core JavaScript
    ================================================== -->
    <!-- Placed at the end of the document so the pages load faster -->
    <script src="../js/jquery.js"></script>
    <script src="../js/bootstrap.min.js"></script>
	<?php
	if (count($enablePopovers) > 0) {
		$sels = array_keys($enablePopovers);
		print "<script>\n";
		foreach ($sels as $sel) {
			print "\$('{$sel}').popover({html:true});\n";
		}
		print "</script>\n";
	}
	?>
  </body>
</html>
<?php
} // html_footer


////////////////////////////////////////////////////////////////////////////////
//// Model Data Validation ////
////////////////////////////////////////////////////////////////////////////////


function ValidateInstance(&$inst, &$model, &$warnings, &$errors) {
	// fill def. values and sanitize
	foreach ($model as $fld => $fldProp) {
		$lbl = @$fldProp['label'];
		if (!$lbl) { $lbl = ucfirst($fld); }

		if (isset($inst[$fld])) {
			$val = sani($inst[$fld]);
			if (@$fldProp['values']) {
				if (is_array($fldProp['values'])) {
					// todo: maybe process $val (strtolower, trim, etc.)
					if (!in_array($val, $fldProp['values'])) {
						unset($inst[$fld]);
						if (!@$fldProp['is_column'] && !@$fldProp['required']) {
							// only for optional fields. Otherwise, an error will be raised later.
							$warnings[] = $lbl;
						}
						continue;
					}
				}
				if ('date' == $fldProp['values']) {
					list($y, $m, $d) = explode('-', $val);
					$y = intval($y);
					$m = intval($m);
					$d = intval($d);
					if ($m < 10) { $m = "0{$m}"; }
					if ($d < 10) { $d = "0{$d}"; }
					if ("{$y}-{$m}-{$d}" != $val) {
						unset($inst[$fld]);
						continue;
					}
				}
				if ('time' == $fldProp['values']) {
					list($h, $m) = explode(':', $val);
					$h = intval($h);
					$m = intval($m);
					if ($h < 10) { $h = "0{$h}"; }
					if ($m < 10) { $m = "0{$m}"; }
					if ("{$h}:{$m}" != $val) {
						unset($inst[$fld]);
						continue;
					}
				}
				if ('numeric' == $fldProp['values']) {
					$val = intval($val);
				}
				if ('bool' == $fldProp['values']) {
					$val = $val ? 1 : 0;
				}
			}
			$inst[$fld] = $val;
		}
		if (!isset($inst[$fld]) && @$fldProp['default']) {
			$inst[$fld] = sani($fldProp['default']);
		}
	}

	foreach ($model as $fld => $fldProp) {
		if (@$fldProp['is_column'] || @$fldProp['required']) {
			if (!isset($inst[$fld])) {
				$lbl = @$fldProp['label'];
				if (!$lbl) { $lbl = ucfirst($fld); }
				$errors[] = $lbl;
			}
		}
	}

	$warnings = array_unique($warnings);
	$errors = array_unique($errors);
	return count($warnings) == 0 && count($errors) == 0;
}

function GetOptionalData(&$inst , &$model) {
	$optData = array();

	foreach ($model as $fld => $fldProp) {
		if (!@$fldProp['is_column']) {
			if (isset($inst[$fld])) {
				$optData[$fld] = $inst[$fld];
			}
		}
	}

	return $optData;
}
