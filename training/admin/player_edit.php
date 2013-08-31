<?php
define('NO_INCLUDES', true);
require_once '../inc/conf.inc.php';
require_once '../inc/dbconf.inc.php';
require_once '../inc/lib.inc.php';

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

if (!@$_REQUEST['id']) {
	$_SESSION['error'] = 'Spieler nicht gefunden.';
	Redirect($rootUrl . 'admin/player_list.php');
}
$playerUID = $_REQUEST['id'];

// load player data
$player = LoadPlayer($playerUID);
if (false === $player) {
	$_SESSION['error'] = 'Spieler nicht gefunden.';
	Redirect($rootUrl . 'admin/player_list.php');
}

$pagetitle = "{$teamNameShort} Training Admin";
html_header();

navbar();
?>
    <div class="container">
	  <h1>Spieler "<?=$player['name']?>" bearbeiten <small><?=$teamNameShort?></small></h1>
      <div class="player-data">
	  <form role="form" method="post">
	  <input type="hidden" name="id" value="<?=$playerUID?>">
	  <input type="hidden" name="club_id" value="<?=$player['club_id']?>">
	  <div style="margin-bottom:10px;">
	  <?php
	  foreach ($playerAvailableFields as $fld => $fldProp) {
		if ('uid' == $fld || 'club_id' == $fld || 'nameLC' == $fld) {
			//continue;
		}
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
	  <div style="width:200px">
	  <div class="list-group">
	  <a class="list-group-item" href="player_show.php?id=<?=$playerUID?>"><span class="glyphicon glyphicon-search"></span> <?=$player['name']?> ansehen</a>
	  <a class="list-group-item" href="player_del.php?id=<?=$playerUID?>"><span class="glyphicon glyphicon-remove"></span> <?=$player['name']?> löschen</a>
	  <a class="list-group-item" href="player_list.php"><span class="glyphicon glyphicon-list"></span> Alle Spieler auflisten</a>
	  </div>
	  </div>
	</div>
<?php
html_footer();