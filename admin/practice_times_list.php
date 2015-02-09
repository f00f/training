<?php
define('NO_INCLUDES', true);
require_once '../inc/lib.inc.php';
require_once '../inc/conf.inc.php';
require_once '../inc/dbconf.inc.php';
require_once '../inc/model_practice_time.inc.php';

get_club_id();
load_config($club_id);

# connect to db
mysql_connect($dbHost, $dbUser, $dbPass);
mysql_select_db($dbDB);

$pagetitle = "{$teamNameShort} Training Admin";
html_header();

navbar_admin('practice-times');

// load all times data
$times = PracticeTime::LoadAll($club_id);
$icons = array(
  'active' => array(
	'desc' => 'Momentan aktive Trainingszeit.',
	'sym' => 'bell',
  ),
  'future' => array(
	'desc' => 'Trainingszeit, die in der Zukunft aktiv wird.',
	'sym' => 'time',
  ),
  'past' =>array(
	'desc' => 'Trainingszeit, die nicht mehr aktiv ist.',
	'sym' => 'ban-circle',
  ),
);
?>
    <div class="container">
	  <h1>Alle Trainingszeiten <small><?=$teamNameShort?></small></h1>
	  <div class='panel panel-default'>
		<div class='panel-body'>
		Im Moment kannst Du Trainingszeiten noch nicht löschen. Wenn Du ihr Ende-Datum ("letztmals") aber auf ein Datum in der Vergangenheit setzt, werden sie nie berücksichtigt werden.
		</div>
	  </div>

		<table class="table _list-group practice-times-list">
	  <thead>
	  <tr>
	  <th>Tag und Zeit</th>
	  <th>Ort und Zeitraum</th>
	  <th>Aktionen</th>
	  </tr>
	  </thead>
	  <tbody>
	  <?php
	  foreach ($times as $t) {
		$tId = $t['uid'];
		$icon = false;
		if (!$icon && $t['active']) { $icon = $icons['active']; }
		if (!$icon && !$t['has-started']) { $icon = $icons['future']; }
		if (!$icon && $t['has-ended']) { $icon = $icons['past']; }
		if (!$icon && !$t['active'] && $t['has-started'] && !$t['has-ended']) { $icon = $icons['past']; }
		$icon = "<span class='glyphicon glyphicon-{$icon['sym']}'></span> ";
		$classes = array();
		$classes[] = $t['active'] ? 'current' : 'inactive';
		$classes[] = $t['has-started'] ? 'has-started' : 'has-not-started';
		$classes[] = $t['has-ended'] ? 'has-ended' : 'has-not-ended';
		$zeitraum = '';
		if (@$t['first'] && @$t['last']) {
			if ($t['first'] == $t['last']) {
				$zeitraum = "1x am {$t['first']}";
			} else {
				$zeitraum = "{$t['first']} &ndash; {$t['last']}";
			}
		} else if (@$t['first']) {
				$zeitraum = "ab dem {$t['first']}";
		} else if (@$t['last']) {
				$zeitraum = "bis zum {$t['last']}";
		}
	    print "<tr class='_list-group-item practice-time ".implode(' ', $classes)."'>\n"
			. "  <th>{$icon}{$t['dow']}, {$t['begin']} &ndash; {$t['end']} Uhr</th>\n"
			. "  <td>{$t['ort']}, {$zeitraum}</td>\n"
			//. "  <td>".implode(' ', $classes)."</td>\n"
			. "  <td>"
			. "<a href='practice_time_edit.php?id={$tId}'><span class='glyphicon glyphicon-pencil'></span> bearbeiten</a> "
			. "<a class='text-danger' href='practice_time_del.php?id={$tId}'><span class='glyphicon glyphicon-trash'></span> löschen</a>"
			. "</td>\n"
			. "</tr>\n";
	  }
	  ?>
	  </tbody>
      </table>
	  <p>
	  <span class='lead'>Legende</span><br>
	  <?php
	  foreach($icons as $slug => $icon) {
		$sym = "<span class='glyphicon glyphicon-{$icon['sym']}'></span> ";
		print "{$sym} {$icon['desc']}<br>";
	  }
	  ?>
	  </p>

	  <h3>Aktionen</h3>
	  <table class="list-group">
	  <tr class="list-group-item"><td><a href="practice_time_add.php"><span class="glyphicon glyphicon-plus"></span> Eine Trainingszeit hinzufügen</a></td></tr>
	  <tr class="list-group-item"><td><a href="./"><span class="glyphicon glyphicon-home"></span> Zurück zur Startseite</a></td></tr>
	  </table>
  </div>
<?php
html_footer();
