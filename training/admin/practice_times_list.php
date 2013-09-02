<?php
define('NO_INCLUDES', true);
require_once '../inc/conf.inc.php';
require_once '../inc/dbconf.inc.php';
require_once '../inc/lib.inc.php';
// load times model
require_once '../inc/model_practice_time.inc.php';

# connect to db
mysql_connect($dbHost, $dbUser, $dbPass);
mysql_select_db($dbDB);

$pagetitle = "{$teamNameShort} Training Admin";
html_header();

navbar();

// load all times data
$times = LoadAllPracticeTimes($teamId);
?>
    <div class="container">
	  <h1>Alle Trainingszeiten <small><?=$teamNameShort?></small></h1>
      <table class="list-group practice-times-list">
	  <?php
	  foreach ($times as $t) {
		$tId = $t['uid'];
		$icon = $t['active'] ? '<span class="glyphicon glyphicon-star"></span> ' : '<span class="glyphicon glyphicon-star-empty"></span> ';
		$classes = array();
		$classes[] = $t['active'] ? 'active' : 'inactive';
		$classes[] = $t['has-started'] ? 'has-started' : 'has-not-started';
		$classes[] = $t['has-ended'] ? 'has-ended' : 'has-not-ended';
	    print "<tr class='list-group-item practice-time ".implode(' ', $classes)."'>\n"
			. "  <th>{$icon}{$t['dow']}, {$t['begin']} &ndash; {$t['end']} Uhr, {$t['ort']}</th>\n"
			. "  <td>{$t['first']} &ndash; {$t['last']}</td>\n"
			. "  <td>".implode(' ', $classes)."</td>\n"
			. "  <td>"
			. "<a href='practice_time_edit.php?id={$tId}'><span class='glyphicon glyphicon-edit'></span> bearbeiten</a> "
			. "<a class='del' href='practice_time_del.php?id={$tId}'><span class='glyphicon glyphicon-remove'></span> löschen</a>"
			. "</td>\n"
			. "</tr>\n";
	  }
	  ?>
      </table>


	  <h3>Aktionen</h3>
	  <table class="list-group">
	  <tr class="list-group-item"><td><a href="practice_time_add.php"><span class="glyphicon glyphicon-plus"></span> Trainingszeit hinzufügen</a></td></tr>
	  <tr class="list-group-item"><td><a href="./"><span class="glyphicon glyphicon-home"></span> Zurück zur Startseite</a></td></tr>
	  </table>
  </div>
<?php
html_footer();
