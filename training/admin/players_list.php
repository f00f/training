<?php
define('NO_INCLUDES', true);
require_once '../inc/conf.inc.php';
require_once '../inc/dbconf.inc.php';
require_once '../inc/lib.inc.php';
// load player model
require_once '../inc/model_player.inc.php';

# connect to db
mysql_connect($dbHost, $dbUser, $dbPass);
mysql_select_db($dbDB);

$pagetitle = "{$teamNameShort} Training Admin";
html_header();

navbar_admin('players');

// load all players data
$players = LoadConfiguredPlayers($teamId);
?>
    <div class="container">
	  <h1>Alle Spieler <small><?=$teamNameShort?></small></h1>
	  <div class='panel panel-default'>
		<div class='panel-body'>
		Im Moment kannst Du Spieler noch nicht löschen. Wenn Du allerdings für einen Spieler die Frequenz aud 0 setzt, wird er keine Benachrichtigungs-E-Mails mehr erhalten. In der nichts-gesagt-Liste auf der Trainingsseite wird er aber weiterhin auftauchen.
		</div>
	  </div>

		<table class="list-group player-list">
	  <?php
	  foreach ($players as $pLower => $p) {
		$pId = $p['uid'];
	    print '<tr class="list-group-item player">';
		print "<th class='player-name'>{$p['name']}</th>";
		print "<td>"
			. "<a href='player_edit.php?id={$pId}'><span class='glyphicon glyphicon-pencil'></span> bearbeiten</a> "
			. "<a class='text-danger' href='player_del.php?id={$pId}'><span class='glyphicon glyphicon-trash'></span> löschen</a>"
			. "</td>";
	    print '</tr>';
	  }
	  ?>
      </table>


	  <h3>Aktionen</h3>
	  <table class="list-group">
	  <tr class="list-group-item"><td><a href="player_add.php"><span class="glyphicon glyphicon-plus"></span> Einen Spieler hinzufügen</a></td></tr>
	  <tr class="list-group-item"><td><a href="./"><span class="glyphicon glyphicon-home"></span> Zurück zur Startseite</a></td></tr>
	  </table>
  </div>
<?php
html_footer();
