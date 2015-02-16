<?php
require_once '../config/conf.inc.php';
require_once '../inc/lib.inc.php';
require_once '../inc/model_player.inc.php';

get_club_id();
load_config($club_id);

# connect to db
mysql_connect($dbHost, $dbUser, $dbPass);
mysql_select_db($dbDB);

$loadFromDB = true;
if (@$_POST['id']) {
	// save player data
	$player = array();
	$player['uid'] = $_POST['id'];
	foreach (Player::$fields as $fld => $fldProp) {
		if (isset($_POST[$fld])) {
			$player[$fld] = $_POST[$fld];
		}
	}
	$success = Player::Save($player);
	if ($success) {
		$_SESSION['notice'] = "<strong>Yes!</strong> {$player['name']} wurde erfolgreich gespeichert.";
	} else {
		$prefix = '<strong>Nicht gespeichert.</strong> ';
		if (@$_SESSION['error']) {
			$_SESSION['error'] = $prefix . $_SESSION['error'];
		} else {
			$_SESSION['warning'] = $prefix . @$_SESSION['warning'];
		}
	}
	$loadFromDB = false;
}

if (!@$_REQUEST['id']) {
	$_SESSION['warning'] = 'Spieler nicht gefunden.';
	Redirect($rootUrl . 'admin/player_list.php');
}
$playerUID = $_REQUEST['id'];

// load player data
if ($loadFromDB) {
	$player = Player::Load($playerUID, $club_id);
	if (false === $player) {
		$_SESSION['warning'] = 'Spieler nicht gefunden.';
		Redirect($rootUrl . 'admin/player_list.php');
	}
}

$pagetitle = "{$teamNameShort} Training Admin";
html_header();

navbar_admin('players');
?>
    <div class="container">
	  <h1>Spieler "<?=$player['name']?>" bearbeiten <small><?=$teamNameShort?></small></h1>
      <div class="player-data">
	  <form role="form" method="post">
	  <input type="hidden" name="id" value="<?=$playerUID?>">
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
	  <tr class="list-group-item"><td><a class='text-danger' href="player_del.php?id=<?=$playerUID?>"><span class="glyphicon glyphicon-trash"></span> <?=$player['name']?> löschen</a></td></tr>
	  <tr class="list-group-item"><td><a href="player_add.php"><span class="glyphicon glyphicon-plus"></span> Einen Spieler hinzufügen</a></td></tr>
	  <tr class="list-group-item"><td><a href="players_list.php"><span class="glyphicon glyphicon-th-list"></span> Alle Spieler auflisten</a></td></tr>
	  <tr class="list-group-item"><td><a href="./"><span class="glyphicon glyphicon-home"></span> Zurück zur Startseite</a></td></tr>
	  </table>
	</div>
<?php
html_footer();
