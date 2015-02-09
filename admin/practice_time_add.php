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

$initPractice = true;
if (isset($_POST['practice_id']) && @$_POST['club_id']) {
	// save practice time data
	$practice = array();
	$practice['practice_id'] = $_POST['practice_id'];
	foreach (PracticeTime::$fields as $fld => $fldProp) {
		if (isset($_POST[$fld])) {
			$practice[$fld] = $_POST[$fld];
		}
	}
	$success = PracticeTime::Save($practice);
	if ($success) {
		$practice['uid'] = mysql_insert_id();
		$_SESSION['notice'] = "<strong>Yes!</strong> Die neue Trainingszeit wurde erfolgreich hinzugefügt.";
		Redirect($rootUrl . 'admin/practice_time_edit.php?id='.$practice['uid']);
	}
	else
	{
		$prefix = '<strong>Nicht hinzugefügt.</strong> ';
		if (@$_SESSION['error']) {
			$_SESSION['error'] = $prefix . $_SESSION['error'];
		} else {
			$_SESSION['warning'] = $prefix . @$_SESSION['warning'];
		}
	}
	$initPractice = false;
}

// set practice time data
if ($initPractice) {
	$practice = array(
		'practice_id' => 0,
		'club_id' => $club_id,
	);
}

$pagetitle = "{$teamNameShort} Training Admin";
html_header();

navbar_admin('practice-times');
?>
    <div class="container">
	  <h1>Trainingszeit hinzufügen <small><?=$teamNameShort?></small></h1>
      <div class="practice-time-data">
	  <form role="form" method="post">
	  <div style="margin-bottom:10px;">
	  <?php
	  foreach (PracticeTime::$fields as $fld => $fldProp) {
		$val = @$practice[$fld];
		if ('hidden' == @$fldProp['type']) {
			print "<input type='hidden' name='{$fld}' value='{$val}'>\n";
			continue;
		}
		if (!@$fldProp['type']) { $fldProp['type'] = 'text'; }
		if (!@$fldProp['label']) { $fldProp['label'] = ucfirst($fld); }
		print "<div class='input-group'>\n";
		if ('bool' == @$fldProp['values']) {
			print "  <span class='input-group-addon'><input type='checkbox' name='{$fld}'".($val ? " checked='checked'" : '')."></span> \n"
				. "  <input class='form-control' onfocus='blur()' name='_{$fld}_' value='{$fldProp['label']}'>\n";
		} else {
			print "  <span class='input-group-addon'>{$fldProp['label']}</span> \n"
				. "  <input class='form-control' name='{$fld}' value='{$val}'>\n";
		}
		if (@$fldProp['help']) {
			$enablePopovers['.pop'] = true;
			print "  <span class='input-group-btn'>\n"
				. "    <button class='btn btn-default pop' data-toggle='popover' data-content='{$fldProp['help']}' data-placement='left' type='button'>\n"
				. "      <span class='glyphicon glyphicon-info-sign'></span>\n"
				. "    </button>\n"
				. "  </span>\n";
		}
		print "</div>\n";
	  }
	  ?>
	  </div>
	  <div>
	  <button class="btn btn-primary" type="submit">Speichern</button>
	  </div>
	  </form>
      </div>

	  <h3>Aktionen</h3>
	  <table class="list-group">
	  <tr class="list-group-item"><td><a href="practice_times_list.php"><span class="glyphicon glyphicon-th-list"></span> Alle Trainingszeiten auflisten</a></td></tr>
	  <tr class="list-group-item"><td><a href="./"><span class="glyphicon glyphicon-home"></span> Zurück zur Startseite</a></td></tr>
	  </table>
  </div>
<?php
html_footer();
