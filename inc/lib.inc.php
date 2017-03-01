<?php
/*
 * Created on 04.06.2006
 */

session_start();

if (!defined('NO_INCLUDES') || !NO_INCLUDES) {
	require_once 'conf.inc.php';
}
require_once 'db.inc.php';
require_once 'html.inc.php';
require_once 'import.inc.php';
require_once 'mail.inc.php';
require_once 'config-site.inc.php';
require_once __DIR__ . '/FirebaseStore.php';

if (file_exists(PLUGINS_FILE)) {
	include_once PLUGINS_FILE;
}

define('SCRIPT_START_TIME', time());
define('TRAIN_HORIZON', SCRIPT_START_TIME - RESET_DELAY);

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

function SpamCheck($p_name, $p_ip) {
	if (@ON_TEST_SERVER) {
		return;
	}

	global $mysqli, $table;

	# Find all updates during the last SPAM_SAME_IP_TIMEOUT seconds that
	# were made from $p_ip
	$q = "SELECT `when` "
		. "FROM `{$table}` "
		. "WHERE `ip` = ? "
		. "AND `name` <> 'RESET' "
		. "AND `when` > ?";
	$dt = time() - SPAM_SAME_IP_TIMEOUT;
	$result = DbQueryP($q, 'si', $p_ip, $dt);
	if (!$result) {
		die (mysqli_error($mysqli));
	}
	if (SPAM_SAME_IP_COUNT <= mysqli_num_rows($result)) {
//print '<h3>SPAM_SAME_IP_TIMEOUT!</h3>';
		Redirect();
	}

	$lastIPOfUser = '';
	# Finde letzte IP von der aus $p_name aktualisiert wurde
	$q = "SELECT `ip`, `when` AS `last_update_of_user` "
		. "FROM `{$table}` "
		. "WHERE `name` = ? "
		. "AND `when` > ? "
		. "ORDER BY `when` DESC "
		. "LIMIT 1";
	$dt = time() - SPAM_SAME_USER_TIMEOUT;
	$result = DbQueryP($q, 'si', $p_name, $dt);
	if (!$result) {
		die (mysqli_error($mysqli));
	}
	if (0 < mysqli_num_rows($result)) {
		$row = mysqli_fetch_assoc($result);
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
	fwrite($fh, '{'.$jsonAllNames.'}');
	fclose($fh);

	// load this for detailed display
	$fh = fopen('json/'.$club_id.'-training.json', 'w');
	fwrite($fh, '{"train":{'.$jsonTrain.','.$jsonZuAbNames.','.$jsonExtra.'}}');
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

function UpdateFirebase() {
	global $ZTCacheFile;
	global $club_id, $allPlayers, $nextTraining, $lastUpdate,
		$anzahlZugesagt, $anzahlAbgesagt, $zugesagt, $abgesagt,
		$nixgesagtTendenzJa, $nixgesagtKeineTendenz, $nixgesagtTendenzNein,
		$poolImageFile, $poolThumbFile;

	// store people's status
	$train = array(
			'begin' => $nextTraining['begin'],
			'end' => $nextTraining['end'],
			'wtag' => $nextTraining['wtag'],
			'datum' => date('d.m.', $nextTraining['datum']),
			'zeit' => $nextTraining['zeit'],
			'anreise' => $nextTraining['anreise'],
			'ort' => $nextTraining['ort'],
			'updated' => $lastUpdate,
			'zu' => $zugesagt,
			'ab' => $abgesagt,
			);
	$xtra = new stdClass();
	/* TODO BA: create hook + plugin
	if ('Zapfendorf' == $nextTraining['ort']) {
		$xtra->temp = new stdClass();
		$xtra->temp->deg = GetWasserTemp();
		$xtra->temp->updated = filemtime($ZTCacheFile);
	}
	*/
	if ('' != @$poolImageFile) {
		$xtra->pic = new stdClass();
		$xtra->pic->full = $poolImageFile;
		$xtra->pic->thumb = $poolThumbFile;
	}
	$train['x'] = $xtra;

	$fbUrl = FIREBASE_URL . $club_id;
	$fbStore = new \Training\FirebaseStore($fbUrl, FIREBASE_SECRET);
	$fbStore->AuthUid = 'trainingServer';
	$fbStore->StoreData(array_values($allPlayers), $train);
}// UpdateFirebase

function UpdateFiles() {
	FindBadBild();
	UpdateHtmlFiles();
	UpdateJsonFiles();
	UpdateFirebase();
	// copy JSON files to old directory, for compatibility with old app versions.
	global $club_id, $copyJsonFiles;
	if ($copyJsonFiles) {
		$infiles = array(
			'json/'.$club_id.'-all-players.json',
			'json/'.$club_id.'-training.json',
		);
		$outfiles = array(
			'../'.$club_id.'/training/json/all-players.json',
			'../'.$club_id.'/training/json/training.json',
		);
		for($i=0; $i<count($infiles); $i++) {
			copy($infiles[$i], $outfiles[$i]);
		}
	}
}

function FindBadBild()
{
	global $nextTraining, $poolImageFile, $poolThumbFile;
	$ort = strtolower(trim($nextTraining['ort']));
	$poolImageFolder = './badbilder/'.$ort.'/';
	if (!$ort || !file_exists($poolImageFolder) || !is_dir($poolImageFolder)) {
		$poolImageFile = '';
		$poolThumbFile = '';
		return;
	}

	$poolImageFile = RandomFile($poolImageFolder, 'jpg|png|gif');
	// thumb
	$poolThumbFolder = './badbilder/thumbs/'.$ort.'/';
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


////////////////////////////////////////////////////////////////////////////////
//// HELPERS ////
////////////////////////////////////////////////////////////////////////////////

// A global cache object.
// Used by player model and practice time model
$CACHE = new stdClass();


function FirstWord($p_text) {
	$matches = array();
	$allowSpacesInWord = true;
	if ($allowSpacesInWord) {
		$pattern = '/^([\w ]*)/u';
	} else {
		// TODO: replace complicated set by \W or [^\p{L}\p{N}] (letter/number)
		$pattern = '/^(.*?)[^A-Za-z0-9äöüßÄÖÜ]/u';
	}
	preg_match($pattern, $p_text, $matches);
	return trim($matches[1]);
}

function sani($s) {
	global $mysqli;
	return mysqli_real_escape_string($mysqli, $s);
}


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
