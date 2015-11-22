<?php
require_once '../config/conf.inc.php';
require_once '../inc/lib.inc.php';
require_once '../inc/model_player.inc.php';

# connect to db
$mysqli = mysqli_connect($dbHost, $dbUser, $dbPass, $dbDB);

$initPlayer = true;
if (isset($_POST['uid']) && @$_POST['club_id']) {
	// save player data
	$player = array();
	foreach (Player::$fields as $fld => $fldProp) {
		if (isset($_POST[$fld])) {
			$player[$fld] = $_POST[$fld];
		}
	}
	$success = Player::Save($player);
	if ($success) {
		$player['uid'] = mysqli_insert_id($mysqli);
		$_SESSION['notice'] = "<strong>Yes!</strong> Der Spieler \"{$player['name']}\" wurde erfolgreich hinzugefügt.";
		Redirect($rootUrl . 'admin/player_edit.php?id='.$player['uid']);
	} else {
		$prefix = '<strong>Nicht hinzugefügt.</strong> ';
		if (@$_SESSION['error']) {
			$_SESSION['error'] = $prefix . $_SESSION['error'];
		} else {
			$_SESSION['warning'] = $prefix . @$_SESSION['warning'];
		}
	}
	$initPlayer = false;
}


// set player data
if ($initPlayer) {
	$player = array(
		'uid' => 0,
		'club_id' => $club_id,
	);
}

$pagetitle = "{$teamNameShort} Training Admin";
html_header();

navbar_admin('players');
?>
    <div class="container">
	  <h1>Spieler hinzufügen <small><?=$teamNameShort?></small></h1>
      <div class="player-data">
	  <form role="form" method="post">
	  <div style="margin-bottom:10px;">
	  <?php
	  foreach (Player::$fields as $fld => $fldProp) {
		$val = @$player[$fld];
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
			// placeholder="foo"
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
	  <tr class="list-group-item"><td><a href="players_list.php"><span class="glyphicon glyphicon-th-list"></span> Alle Spieler auflisten</a></td></tr>
	  <tr class="list-group-item"><td><a href="./"><span class="glyphicon glyphicon-home"></span> Zurück zur Startseite</a></td></tr>
	  </table>
	</div>
<?php
html_footer();
