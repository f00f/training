<?php
////////////////////////////////////////////////////////////////////////////////
//// Practice Times ////
////////////////////////////////////////////////////////////////////////////////

@define('NO_INCLUDES', true);
require_once 'lib.inc.php';

class PracticeTime {
	public static $fields = array(
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

	public static $tag2int = array(
		'Mo' => 1, 'Di' => 2, 'Mi' => 3, 'Do' => 4,
		'Fr' => 5, 'Sa' => 6, 'So' => 0,
	);

	public static $dow2int = array(
		'Mo' => 0, 'Di' => 1, 'Mi' => 2, 'Do' => 3,
		'Fr' => 4, 'Sa' => 5, 'So' => 6,
	);
	
	# Get information about the next upcoming practice time.
	# Side-effect: Creates record for corresponding practice session.
	static function GetNext($cid) {
		$training = self::LoadActive($cid);

		$trainingDates = array();
		foreach($training as $t) {
			$dt = $t['next-date'].' '.$t['begin'];
			$trainingDates[$dt] = $t;
		}
		ksort($trainingDates);

		$nextTraining = array_shift($trainingDates);
		if (!$nextTraining) {
			die('Kein Trainingstermin gefunden.');
		}
		$nextTraining['wtag'] = "{$nextTraining['dow']}";
		$nextTraining['zeit'] = "{$nextTraining['begin']} - {$nextTraining['end']}";
		$nextTraining['datum'] = strtotime($nextTraining['next-date']); // Change: timestamp is now at 00:00

		// TODO: store $nextTraining to $tables['practice_sessions']
		// @see: Practice::CreateRecord
		// correct timestamp
		list($ntHour, $ntMin, $ntSec) = explode(':', $nextTraining['zeit']);
		$ntMin = (int) $ntMin; // kludge: cut the tail
		$ntDay	= date('d', $nextTraining['datum']);
		$ntMon	= date('m', $nextTraining['datum']);
		$ntYear	= date('Y', $nextTraining['datum']);
		$nextTraining['when'] = mktime($ntHour, $ntMin, 0, $ntMon, $ntDay, $ntYear);
		$nextTraining['session_id'] = date('Y-m-d H:i:s', $nextTraining['when']);

		PracticeSession::CreateRecord($cid, $nextTraining['session_id'], $nextTraining);

		return $nextTraining;
	}

	static function Load($practice_id, $cid = 'use $club_id') {
		global $tables;

		$q = "SELECT `practice_id`, `club_id`, `dow`, `begin`, `end`, `first`, `last`, `data` FROM `{$tables['practices_conf']}` "
			. "WHERE `practice_id` = '{$practice_id}'";
		if ($cid !== false) {
			$q .= " AND `club_id` = '{$cid}'";
		}
		$result = DbQuery($q);
		if (mysql_num_rows($result) != 1) {
			return false;
		}

		$row = mysql_fetch_assoc($result);
		$practice = self::row2practice($row);

		return $practice;
	}

	static function Save($practice) {
		global $tables;

		$warnings = array();
		$errors = array();
		$success = ValidateInstance($practice, self::$fields, $warnings, $errors);
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
		$data = serialize(GetOptionalData($practice, self::$fields));

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
	static function LoadActive($cid) {
		global $CACHE;
		if (!isset($CACHE->activePracticeTimes) || false === @$CACHE->activePracticeTimes) {
			self::LoadAll($cid);
		}
		return $CACHE->activePracticeTimes;
	}

	// load all practice times from DB
	static function LoadAll($cid) {
		global $CACHE;
		global $tables;

		if (isset($CACHE->allPracticeTimes) && false !== @$CACHE->allPracticeTimes) {
			return $CACHE->allPracticeTimes;
		}

		$CACHE->allPracticeTimes = array();
		$CACHE->activePracticeTimes = array();

		$q = "SELECT `practice_id`, `club_id`, `dow`, `begin`, `end`, `first`, `last`, `data` FROM `{$tables['practices_conf']}` "
			. " WHERE `club_id` = '{$cid}'"
			. " ORDER BY `last` < CURDATE(),"
			. " `first` ASC,"
			. " `last` ASC";//TODO: add deleted
		$result = DbQuery($q);

		if (mysql_num_rows($result) > 0) {
			while ($row = mysql_fetch_assoc($result)) {
				$p = self::row2practice($row);

				$CACHE->allPracticeTimes[] = $p;

				if ($p['active']) {
					$CACHE->activePracticeTimes[] = $p;
				}
			}
		}
		//asort($CACHE->allPracticeTimes);// TODO: Sort by dow, meaningfully

		return $CACHE->allPracticeTimes;
	}

	private static function CreateDow2Date() {
		global $CACHE;
		if (isset($CACHE->dow2date)) {
			return;
		}

		$CACHE->dow2date = array();
		for ($i = 0; $i < 7; $i++) {
			$nowPlusXDays = strtotime("+ {$i} days", SCRIPT_START_TIME);
			$dowThen = date('w', $nowPlusXDays);
			$dateThen = date('Y-m-d', $nowPlusXDays);
			$CACHE->dow2date[$dowThen] = $dateThen;
		}
	}

	private static function row2practice(&$row) {
		global $CACHE;

		self::CreateDow2Date();
		$dow2date =& $CACHE->dow2date;
		$today = date('Y-m-d', SCRIPT_START_TIME);
		$nowTime = date('H:i', SCRIPT_START_TIME);

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
		$dowIdx = self::$tag2int[$row['dow']];
		$p['dowIdx'] = ($dowIdx - 1) % 7;
		$nextDate = $dow2date[$dowIdx];
		$p['has-started'] = $row['first'] <= $today;
		$p['has-ended'] = $row['last'] < $today;
		$p['active'] = $nextDate >= $row['first'] && $nextDate <= $row['last'];
		if ($nextDate == $today AND $p['begin'] < $nowTime) {
			// this practice session had been today, but it's over, so add one week to next-date
			$nextDate = date('Y-m-d', strtotime('+ 1 week', strtotime($nextDate)));
			$p['active'] = $nextDate >= $row['first'] && $nextDate <= $row['last'];
		}
		if ($p['active']) {
			$p['next-date'] = $nextDate;
		}

		return $p;
	}
}


////////////////////////////////////////////////////////////////////////////////
//// DEPRECATED ////
////////////////////////////////////////////////////////////////////////////////
$PracticeTimeModel = new stdClass();
$PracticeTimeModel->fields =& PracticeTime::$fields;
$PracticeTimeModel->tag2int =& PracticeTime::$tag2int;
$PracticeTimeModel->dow2int =& PracticeTime::$dow2int;

# calculate next training date
function FindNextPracticeTime($cid) {
	die('deprecated :'.__FUNCTION__);
	return PracticeTime::GetNext($cid);
}

function LoadPracticeTime($practiceUID, $cid = 'use $club_id') {
	die('deprecated :'.__FUNCTION__);
	return PracticeTime::Load($practiceUID, $cid);
}

function SavePracticeTime($practice) {
	die('deprecated :'.__FUNCTION__);
	return PracticeTime::Save($practice);
}

// load active practice times from DB
function LoadActivePracticeTimes($cid) {
	die('deprecated :'.__FUNCTION__);
	return PracticeTime::LoadActive($cid);
}

// load all practice times from DB
function LoadAllPracticeTimes($cid) {
	die('deprecated :'.__FUNCTION__);
	return PracticeTime::LoadAll($cid);
}


////////////////////////////////////////////////////////////////////////////////
//// Practice Sessions ////
////////////////////////////////////////////////////////////////////////////////

class PracticeSession {
	// load info about current user for a practice session
	public static function GetPlayerStatus($name, $cid, $sid) {
		global $tables;

		$q = "SELECT `status` FROM `{$tables['replies']}` "
			."WHERE `session_id` = '{$sid}' AND `club_id` = '{$cid}' "
			."AND `name` = '{$name}' "
			."ORDER BY `when` DESC "
			."LIMIT 1";
		$res = DbQuery($q);
		$status = false;
		if (mysql_num_rows($res) > 0) {
			$row = mysql_fetch_assoc($res);
			$status = $row['status'];
		}
		return $status;
	}

	static function GetNextId($cid) {
		global $CACHE;
		global $tables;

		if (!@$CACHE->nextSessionID) {
			$now = time() - 2*3600; // offset for overlap

			$date = new DateTime("@{$now}");
			$now = $date->format('Y-m-d H:i:s');

			$q = "SELECT MIN(`session_id`) AS `sid` FROM `{$tables['practice_sessions']}` "
				."WHERE `session_id` >= '{$now}' AND `club_id` = '{$cid}'";
			$res = DbQuery($q);
			if (0 == mysql_num_rows($res)) {
				//die("Err0r in GetNextSessionId({$cid})!");
				return false;
			}
			$row = mysql_fetch_assoc($res);

			$CACHE->nextSessionID = $row['sid'];
		}

		return $CACHE->nextSessionID;
	}

	// Stores initial (meta) information about the practice session.
	static function CreateRecord($cid, $session_id, $practice) {
		global $tables;

		unset($practice['club_id']);
		unset($practice['anreise']);
		unset($practice['dowIdx']);
		unset($practice['has-started']);
		unset($practice['has-ended']);
		unset($practice['active']);
		unset($practice['next-date']);
		unset($practice['wtag']);
		unset($practice['datum']);
		unset($practice['when']);
		unset($practice['zeit']);
		unset($practice['session_id']);

		$practice['zusagen'] = array();
		$practice['absagen'] = array();

		$meta = serialize($practice);
		$q = "INSERT IGNORE INTO `{$tables['practice_sessions']}` "
			. "(`club_id`, `session_id`, `meta`, `count_yes`, `count_no`) "
			. "VALUES "
			. "('{$cid}', '{$session_id}', '{$meta}', 0, 0)";
		DbQuery($q);
	}

	// Loads information about a practice session.
	static function Load($cid, $session_id) {
		global $tables;

		$q = "SELECT * FROM `{$tables['practice_sessions']}` "
			. "WHERE `club_id` = '{$cid}' "
			. "AND `session_id` = '{$session_id}'";
		$result = DbQuery($q);
		if (mysql_num_rows($result) != 1) {
			return false;
		}

		$row = mysql_fetch_assoc($result);
		$session = array();
		foreach ($row as $k => $v) {
			if ('meta' == $k) {
				$session[$k] = unserialize($v);
				continue;
			}
			$session[$k] = $v;
		}

		return $session;
	}

	static function Update($reply, $statusChanged, $name, $text, $cid, $session_id) {
		global $tables;

		// load session record
		$s = self::Load($cid, $session_id);
		if (false === $s) {
			return false;
		}

		if ('ja' == $reply) {
			$s['meta']['zusagen'][$name] = $text;

			if ($statusChanged) {
				++$s['count_yes'];
			}
			if (isset($s['meta']['absagen'][$name])) {
				--$s['count_no'];
				unset($s['meta']['absagen'][$name]);
			}
		}
		if ('nein' == $reply) {
			$s['meta']['absagen'][$name] = $text;

			if ($statusChanged) {
				++$s['count_no'];
			}
			if (isset($s['meta']['zusagen'][$name])) {
				--$s['count_yes'];
				unset($s['meta']['zusagen'][$name]);
			}
		}

		$s['meta']['last-reply'] = time();

		$meta = serialize($s['meta']);
		$q = "UPDATE `{$tables['practice_sessions']}` "
			. "SET `meta` = '{$meta}' "
			. ", `count_yes` = " . intval($s['count_yes']) . " "
			. ", `count_no` = " . intval($s['count_no']) . " "
			. "WHERE `club_id` ='{$cid}' "
			. "AND `session_id` = '{$session_id}'";
		DbQuery($q);
	}
}
