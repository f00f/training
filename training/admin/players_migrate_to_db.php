<?php
require_once '../inc/conf.inc.php';
require_once '../inc/dbconf.inc.php';
require_once '../inc/spieler.inc.php';
define('NO_INCLUDES', true);
require_once '../inc/lib.inc.php';

$club_id = $teamId;
$players =& $spieler;

html_header();
navbar();

print '<div class="container">';
print "Migrating ".count($players)." players for club '{$club_id}' from file to database.<br>\n";

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
	print "Migrating {$k}...<br>\n";
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

print "<div class='alert alert-".(0 == $errors ? 'success' : 'info')."'>Done ".(0 == $errors ? 'without' : 'with '.$errors)." errors.</div>\n";
print "<strong>Note</strong> that aliases have not been migrated.\n";

print '</div>';
html_footer();
