<?php
require_once '../inc/conf.inc.php';
require_once '../inc/dbconf.inc.php';
@define('NO_INCLUDES', true);
require_once '../inc/lib.inc.php';
require_once '../inc/spieler.inc.php';
require_once '../inc/model_player.inc.php';

# connect to db
mysql_connect($dbHost, $dbUser, $dbPass);
mysql_select_db($dbDB);

$club_id = $teamId;
$players =& $spieler;
$pagetitle = "{$teamNameShort} Training Admin";

html_header();
navbar_admin('players');

print '<div class="container">';

if (@$_REQUEST['go']) {
	do_players_migration($club_id);
} else {
	preview_players_migration($club_id);
}

print '</div>';
html_footer();

function is_db_empty($cid) {
	$countDB = count(LoadConfiguredPlayers($cid));
	return $countDB == 0;
}
function not_empty_warning($cid) {
	if (!is_db_empty($cid)) {
		print "<p class='alert alert-danger'><strong>Achtung!</strong> Es stehen bereits Spieler in der Datenbank. Beim Importieren werden wahrscheinlich Fehler auftreten.</p>";
	}
}

function preview_players_migration($club_id) {
	global $players;
	global $teamNameShort;

	?>
	<h1>Spieler importieren &ndash; Vorschau <small><?=$teamNameShort?></small></h1>
	<p>
	In der Konfigurationsdatei <tt>spieler.inc.php</tt> wurden <?=count($players)?> Spieler gefunden.
	Diese können jetzt für die Mannschaft <em><?=$club_id?></em> in die Datenbank importiert werden.
	</p>
	<?php not_empty_warning($club_id); ?>
	<table class='table'>
	<thead>
	<th>Name</th>
	<th>E-Mail</th>
	</thead>
	<tbody>
	<?php
	foreach ($players as $k => $p) {
		print "<tr>\n"
			. "<td>{$p['name']}</td>\n"
			. "<td>{$p['email']}</td>\n"
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

function do_players_migration($club_id) {
	global $dbHost, $dbUser, $dbPass, $dbDB;
	global $teamNameShort;

	global $players;

	print "<h1>Spieler importieren <small>{$teamNameShort}</small></h1>\n";
	print "<p>Importiere ".count($players)." Spieler für die Mannschaft '{$club_id}' in die Datenbank.</p>\n";

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

	$stmt = $mysqli->prepare("INSERT INTO `train_players`"
							." (`club_id`, `player_name`, `player_data`)"
							." VALUES (?, ?, ?)");
	$player_name = '';
	$player_data = '';
	if (!$stmt->bind_param('sss', $club_id, $player_name, $player_data)) {
		print "Error binding parameters: (" . $stmt->errno . ") " . $stmt->error . "\n";
		die();
	}

	$errors = 0;
	foreach ($players as $k => $p) {
		$name = $p['name'];
		if (!$name) { $name = $k; }
		print "Importiere {$name}...<br>\n";
		$player_name = $k;
		$player_data = serialize($p);
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

	print "<div class='alert alert-".(0 == $errors ? 'success' : 'danger')."'>Fertig ".(0 == $errors ? 'ohne' : 'mit '.$errors)." Fehlern.</div>\n";
	print "<div class='alert alert-info'><strong>Hinweis:</strong> Aliase wurden nicht importiert. Diese werden weiterhin aus der Datei <tt>spieler.inc.php</tt> geladen.</div>\n";
}
