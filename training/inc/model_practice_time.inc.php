<?php
////////////////////////////////////////////////////////////////////////////////
//// Practice Times ////
////////////////////////////////////////////////////////////////////////////////

@define('NO_INCLUDES', true);
require_once 'lib.inc.php';

$PracticeTimeModel = new stdClass();

$PracticeTimeModel->fields = array(
	'practice_id' => array(
		'is_column' => true,
		'type' => 'hidden',
		'values' => 'numeric',
	),
	'club_id' => array(
		'is_column' => true,
		'type' => 'hidden',
	),
	'dow' => array(
		'is_column' => true,
		'label' => 'Wochentag',
		'values' => array('Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa', 'So'),
		'help' => 'Wochentag in 2 Buchstaben.<br>[Mo, Di, Mi, Do, Fr, Sa, So]',
	),
	'begin' => array(
		'is_column' => true,
		'label' => 'Anfang',
		'values' => 'time',
		'help' => '[Format: HH:MM]',
	),
	'end' => array(
		'is_column' => true,
		'label' => 'Ende',
		'values' => 'time',
		'help' => '[Format: HH:MM]',
	),
	'ort' => array(
	),
	'anreise' => array(
	),
	'first' => array(
		'is_column' => true,
		'label' => 'Erstmals',
		'values' => 'date',
		'help' => 'Erster Termin für diese Trainingszeit.<br>[Format: YYYY-MM-DD]',
	),
	'last' => array(
		'is_column' => true,
		'label' => 'Letztmals',
		'values' => 'date',
		'help' => 'Letzter Termin für diese Trainingszeit.<br>[Format: YYYY-MM-DD]',
	),
);

$PracticeTimeModel->tag2int = array(
	'Mo' => 1,
	'Di' => 2,
	'Mi' => 3,
	'Do' => 4,
	'Fr' => 5,
	'Sa' => 6,
	'So' => 0,
);

$PracticeTimeModel->dow2int = array(
	'Mo' => 0,
	'Di' => 1,
	'Mi' => 2,
	'Do' => 3,
	'Fr' => 4,
	'Sa' => 5,
	'So' => 6,
);


# calculate next training date
function FindNextPracticeTime($cid) {
	global $PracticeTimeModel;

	$trainingDB = LoadActivePracticeTimes($cid);

	$trainingDatesDB = array();
	foreach($trainingDB as $t) {
		$dt = $t['next-date'].' '.$t['begin'];
		$trainingDatesDB[$dt] = $t;
	}
	ksort($trainingDatesDB);

	$nextTrainingDB = array_shift($trainingDatesDB);
	$nextTrainingDB['wtag'] = "{$nextTrainingDB['dow']}";
	$nextTrainingDB['zeit'] = "{$nextTrainingDB['begin']} - {$nextTrainingDB['end']}";
	$nextTrainingDB['datum'] = strtotime($nextTrainingDB['next-date']); // Change: timestamp is now at 00:00

	return $nextTrainingDB;
}

function LoadPracticeTime($practiceUID, $cid = 'use $club_id') {
	global $tables;

	$q = "SELECT `practice_id`, `club_id`, `dow`, `begin`, `end`, `first`, `last`, `data` FROM `{$tables['practices_conf']}` "
		. "WHERE `practice_id` = '{$practiceUID}'";
	if ($cid !== false) {
		$q .= " AND `club_id` = '{$cid}'";
	}
	$result = DbQuery($q);
	if (mysql_num_rows($result) != 1) {
		return false;
	}

	$row = mysql_fetch_assoc($result);
	$practice = row2practice($row);

	return $practice;
}

function SavePracticeTime($practice) {
	global $PracticeTimeModel;
	global $tables;

	$warnings = array();
	$errors = array();
	$success = ValidateInstance($practice, $PracticeTimeModel->fields, $warnings, $errors);
	if (!$success) {
		if (count($errors) > 0) {
			$error_msg = 'Folgende nicht-optionale Felder haben fehlerhafte Eingaben: <em>' . implode('</em>, <em>', $errors) . '</em>.';
			$_SESSION['error'] = $error_msg;
		}
		if (count($warnings) > 0) {
			$warn_msg = 'Folgende optionale Felder haben fehlerhafte Eingaben: <em>' . implode('</em>, <em>', $warnings) . '</em>.';
			$_SESSION['warning'] = $warn_msg;
		}
		// TODO: show add/edit page, but do not load data from DB.
		return false;
	}
	$data = serialize(GetOptionalData($practice, $PracticeTimeModel->fields));

	$practice_id = 0;
	if (isset($practice['practice_id'])) { $practice_id = intval($practice['practice_id']); }
	if ($practice_id < 1) { $practice_id = 0; }
	$cid = 'n/a';
	if ($practice['club_id']) { $cid = $practice['club_id']; }

	// store to DB
	$q = "REPLACE INTO `{$tables['practices_conf']}` "
		. "(`practice_id`, `club_id`, `dow`, `begin`, `end`, `first`, `last`, `data`) "
		. "VALUES "
		. "({$practice_id}, '{$cid}', '{$practice['dow']}', '{$practice['begin']}', '{$practice['end']}', '{$practice['first']}', '{$practice['last']}', '{$data}')";
	$result = DbQuery($q);

	return true;
}

// load active practice times from DB
function LoadActivePracticeTimes($cid) {
	global $CACHE;
	if (isset($CACHE->activePracticeTimes) && false !== @$CACHE->activePracticeTimes) {
		return $CACHE->activePracticeTimes;
	}
	LoadAllPracticeTimes($cid);
	return $CACHE->activePracticeTimes;
}

// load all practice times from DB
function LoadAllPracticeTimes($cid) {
	global $CACHE;
	global $tables;

	if (isset($CACHE->allPracticeTimes) && false !== @$CACHE->allPracticeTimes) {
		return $CACHE->allPracticeTimes;
	}

	$CACHE->allPracticeTimes = array();
	$CACHE->activePracticeTimes = array();

	$q = "SELECT `practice_id`, `club_id`, `dow`, `begin`, `end`, `first`, `last`, `data` FROM `{$tables['practices_conf']}` "
		. "WHERE `club_id` = '{$cid}' "
		. "ORDER BY `last` DESC, `first` DESC";//TODO: add deleted
	$result = DbQuery($q);

	if (mysql_num_rows($result) > 0) {
		while ($row = mysql_fetch_assoc($result)) {
			$p = row2practice($row);

			$CACHE->allPracticeTimes[] = $p;

			if ($p['active']) {
				$CACHE->activePracticeTimes[] = $p;
			}
		}
	}
	//asort($CACHE->allPracticeTimes);// TODO: Sort by dow, meaningfully

	return $CACHE->allPracticeTimes;
}

function CreateDow2Date() {
	global $CACHE;
	if (isset($CACHE->dow2date)) {
		return;
	}

	$CACHE->dow2date = array();
	for ($i = 0; $i < 7; $i++) {
		$nowPlusXDays = strtotime("+ {$i} days");
		$dowThen = date('w', $nowPlusXDays);
		$dateThen = date('Y-m-d', $nowPlusXDays);
		$CACHE->dow2date[$dowThen] = $dateThen;
	}
}

function row2practice(&$row) {
	global $CACHE;
	global $PracticeTimeModel;

	CreateDow2Date();
	$dow2date =& $CACHE->dow2date;
	$now = date('Y-m-d');
	$nowTime = date('H:i');

	$p = unserialize($row['data']);

	$p['uid'] = $row['practice_id'];
	$p['practice_id'] = $row['practice_id'];
	$p['club_id'] = $row['club_id'];
	$p['dow'] = $row['dow'];
	list($h, $m, $s) = explode(':', $row['begin']);
	$p['begin'] = "{$h}:{$m}";
	list($h, $m, $s) = explode(':', $row['end']);
	$p['end'] = "{$h}:{$m}";
	$p['first'] = $row['first'];
	$p['last'] = $row['last'];
	$dowIdx = $PracticeTimeModel->tag2int[$row['dow']];
	$p['dowIdx'] = ($dowIdx - 1) % 7;
	$nextDate = $dow2date[$dowIdx];
	$p['has-started'] = $row['first'] <= $now;
	$p['has-ended'] = $row['last'] < $now;
	$p['active'] = $nextDate >= $row['first'] && $nextDate <= $row['last'];
	if ($nextDate == $now && $p['begin'] < $nowTime) {
		$p['active'] = false;
	}
	if ($p['active']) {
		$p['next-date'] = $nextDate;
	}

	return $p;
}
