<?php
require_once '../config/conf.inc.php';
require_once '../inc/lib.inc.php';

get_club_id();
load_config($club_id);

# connect to db
mysql_connect($dbHost, $dbUser, $dbPass);
mysql_select_db($dbDB);

if (@$_POST['id']) {
	// save player data
	$player = array();
	$player['uid'] = $_POST['id'];
	$player['club_id'] = @$_POST['club_id'];
	foreach ($playerAvailableFields as $fld => $fldProp) {
		if (@$_POST[$fld]) {
			$player[$fld] = $_POST[$fld];
		}
	}
	SavePlayer($player);
	$_SESSION['notice'] = "<strong>Yes!</strong> {$player['name']} wurde erfolgreich gespeichert.";
}

$pagetitle = "{$teamNameShort} Training Admin";
html_header();

navbar_admin('config');

$ConfigModel = new stdClass();
$ConfigModel->fields = array(
	'teamNameShort' => array(
		'label' => 'Kurzname',
		'help' => 'Wird auf der Traininsseite (und in den E-Mails) angezeigt.',
	),
	'emailFrom' => array(
		'label' => 'Absender Suffix',
		'help' => 'Benachrichtigungs-E-Mails werden mit dem Absender <tt>training-<em>suffix</em>@uwr1.de</tt> versendet.<br>(Meist gleich der Mannschafts-ID)',
	),
	'teamId' => array(
		'label' => 'Mannschafts-ID',
		'help' => 'Interne ID der Mannschaft.',
	),
	'rootUrl' => array(
		'label' => 'Trainingsseite',
		'help' => 'Adresse der Trainingsseite.<br>(Meist: <tt>http://<em>teamID</em>.uwr1.de/training/</tt>)',
	),
	'forgetPlayersAfter' => array(
		'label' => 'Ausblenden nach Monaten',
		'help' => 'Spieler, die nicht in die Datenbank eingetragen wurden, verschwinden N Monate nach ihrer letzten Meldung wieder von der Seite.',
	),
	'forgetConfiguredPlayers' => array(
		'label' => 'Bekannte Spieler auch ausblenden?',
		'values' => 'bool',
		'help' => 'Sollen Spieler, die in die Datenbank eingetragen wurden, auch ausgeblendet werden?',
	),
);
?>
    <div class="container">
	  <h1>Konfiguration anzeigen <small><?=$teamNameShort?></small></h1>
	  <p>
	  Hier kannst Du einige Konfigurationsvariablen der Trainingsseite ansehen.
	  Ändern und speichern kannst Du sie hier aber (noch) nicht.
	  </p>
      <div class="config-data">
	  <form role="form" method="post">
	  <div style="margin-bottom:10px;">
	  <?php
	  foreach ($ConfigModel->fields as $fld => $fldProp) {
		$val = @$$fld;
		if ('hidden' == @$fldProp['type']) {
			print "<input type='hidden' name='{$fld}' value='{$val}'>\n";
			continue;
		}
		if (!@$fldProp['type']) { $fldProp['type'] = 'text'; }
		if (!@$fldProp['label']) { $fldProp['label'] = ucfirst($fld); }
		print "<div class='input-group'>\n";
		if ('bool' == @$fldProp['values']) {
			print "  <span class='input-group-addon'><input disabled='disabled' type='checkbox' name='{$fld}'".($val ? " checked='checked'" : '')."></span> \n"
				. "  <input disabled='disabled' class='form-control' name='_{$fld}_' value='{$fldProp['label']}'>\n";
		} else {
			print "  <span class='input-group-addon'>{$fldProp['label']}</span> \n"
				. "  <input disabled='disabled' class='form-control' name='{$fld}' value='{$val}'>\n";
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
<?php
/*
	  <div>
	  <button class="btn btn-primary" type="submit">Speichern</button>
	  </div>
*/
?>
	  </form>
      </div>
  </div>
<?php
html_footer();
