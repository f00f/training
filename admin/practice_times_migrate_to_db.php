<?php
require_once '../config/conf.inc.php';
require_once '../inc/lib.inc.php';

require_once '../inc/'.$club_id.'-trainingszeiten.inc.php';
require_once '../inc/model_practice_time.inc.php';

# connect to db
mysql_connect($dbHost, $dbUser, $dbPass);
mysql_select_db($dbDB);

$defaultErstmals = date('Y-m-d', strtotime('yesterday'));
$defaultLetztmals = date('Y-m-d', strtotime('+1 year'));
$pagetitle = "{$teamNameShort} Training Admin";

html_header();
navbar_admin('practice-times');

print '<div class="container">';

if (@$_REQUEST['go']) {
	do_practice_times_migration($club_id);
} else {
	preview_practice_times_migration($club_id);
}

print '</div>';
html_footer();

function is_db_empty($cid) {
	$countDB = count(PracticeTime::LoadAll($cid));
	return $countDB == 0;
}
function not_empty_warning($cid) {
	if (!is_db_empty($cid)) {
		print "<p class='alert alert-danger'><strong>Achtung!</strong> Es stehen bereits Trainingszeiten in der Datenbank. Beim Importieren werden wahrscheinlich Fehler auftreten.</p>";
	}
}

function preview_practice_times_migration($club_id) {
	global $trainingX;
	global $teamNameShort;
	global $defaultErstmals, $defaultLetztmals;
	?>
	<h1>Trainingszeiten importieren &ndash; Vorschau <small><?=$teamNameShort?></small></h1>
	<p>
	In der Konfigurationsdatei <tt>trainingszeiten.inc.php</tt> wurden <?=count($trainingX)?> aktive Trainingszeiten gefunden.
	Diese können jetzt für die Mannschaft <em><?=$club_id?></em> in die Datenbank importiert werden.
	Hinweis: Es könnten nur Trainingszeiten importiert werden, die im Moment aktiv sind.
	</p>

	<?php not_empty_warning($club_id); ?>

	<div class='alert alert-info'>
	<strong>Hinweis:</strong> Die Werte für <em>erstmals</em> und <em>letzmals</em> werden auf gestern (<?=$defaultErstmals?>) bzw. in einem Jahr (<?=$defaultLetztmals?>) gesetzt.
	</div>

	<table class='table'>
	<thead>
	<th>Tag und Zeit</th>
	<th>Ort, Anreise</th>
	<th>Zeitraum</th>
	</thead>
	<tbody>
	<?php
	foreach ($trainingX as $t) {
		$comment = '';
		if (@$t['first'] && @$t['last']) {
			if ($t['first'] == $t['last']) {
				$comment = '1x am '.$t['first'];
			} else {
				$comment = $t['first'] .' &ndash; '. $t['last'];
			}
		} else if (@$t['first']) {
				$comment = 'ab dem '.$t['first'];
		} else if (@$t['last']) {
				$comment = 'bis zum '.$t['last'];
		}
		print "<tr>\n"
			. "<td>{$t['tag']}, {$t['zeit']}</td>\n"
			. "<td>{$t['ort']}, {$t['anreise']}</td>\n"
			. "<td>{$comment}</td>\n"
			. "</tr>\n";
	}
	?>
	</tbody>
	</table>

	<p>
	<?php
	if (!is_db_empty($club_id)) {
		?>
		<div class='panel panel-default'>
		<div class='panel-heading'>
		<strong>Achtung!</strong> Die Datenbank ist nicht leer.
		</div>
		<div class='panel-body'>
		<a href='?go=1' class='btn btn-danger'>Import trotzdem durchführen</a>
		<a href='./' class='btn btn-default'>Abbrechen</a>
		</div>
		</div>
		<?php
	} else {
		print "<a href='?go=1' class='btn btn-primary'>Import durchführen</a>\n";
	}
	print '</p>';
}

function do_practice_times_migration($club_id) {
	global $dbHost, $dbUser, $dbPass, $dbDB;
	global $teamNameShort;

	global $trainingX;
	global $defaultErstmals, $defaultLetztmals;

	print "<h1>Trainingszeiten importieren <small>{$teamNameShort}</small></h1>\n";
	print "<p>Importiere ".count($trainingX)." Trainingszeiten für die Mannschaft '{$club_id}' in die Datenbank.</p>\n";

	# connect to db
	$mysqli = new mysqli($dbHost, $dbUser, $dbPass, $dbDB);
	if (!$mysqli->set_charset("utf8")) {
		printf("Error loading character set utf8: %s\n", $mysqli->error);
	}

	# check connection
	if (mysqli_connect_errno()) {
		printf("Connect failed: %s\n", mysqli_connect_error());
		exit();
	}

	$stmt = $mysqli->prepare("INSERT INTO `train_practices`"
							." (`club_id`, `dow`, `begin`, `end`, `first`, `last`, `data`)"
							." VALUES (?, ?, ?, ?, ?, ?, ?)");
	$dow   = '';
	$begin = '';
	$end   = '';
	$first = '';
	$last  = '';
	$data  = '';
	if (!$stmt->bind_param('sssssss', $club_id, $dow, $begin, $end, $first, $last, $data)) {
		print "Error binding parameters: (" . $stmt->errno . ") " . $stmt->error . "\n";
		die();
	}

	$errors = 0;
	foreach ($trainingX as $t) {
		$practice_name = "{$t['tag']} {$t['zeit']} {$t['ort']}";
		print "Importiere {$practice_name}.<br>\n";

		$dow = $t['tag'];
		unset($t['tag']);
		list($begin, $end) = split(' - ', $t['zeit']);
		unset($t['zeit']);
		$first = @$t['first'] ? $t['first'] : $defaultErstmals;
		$last  = @$t['last'] ? $t['last'] : $defaultLetztmals;
		$data = serialize($t);

		if (!$stmt->execute()) {
			print "<div class='alert alert-warning'>Execute failed: (" . $stmt->errno . ") " . $stmt->error . "</div>\n";
			$errors++;
			continue;
		}
		if ($stmt->affected_rows != 1) {
			print "<div class='alert alert-warning'>There was an error inserting {$k}.</div>\n";
			$errors++;
			continue;
		}
	}
	$stmt->close();


	# disconnect from db, only output follows
	$mysqli->close();

	print "<div class='alert alert-".(0 == $errors ? 'success' : 'danger')."'>Fertig ".(0 == $errors ? 'ohne Fehler' : 'mit '.$errors.' Fehlern').".</div>\n";
}
