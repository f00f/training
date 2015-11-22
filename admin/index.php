<?php
require_once '../config/conf.inc.php';
require_once 'lib.inc.php';

# connect to db
$mysqli = mysqli_connect($dbHost, $dbUser, $dbPass, $dbDB);

$pagetitle = "{$teamNameShort} Training Admin";
html_header();

navbar_admin('home');
?>

    <div class="container">

      <div class="starter-template">
        <h1>Trainingsseite Admin <small><?=$teamNameShort?></small></h1>
        <p class="lead">Hier kannst Du die Trainingsseite von <?=$teamNameShort?> verwalten.</p>
		<p>
		Als Erstes kannst Du Spieler und Trainingszeiten aus den bestehenden Konfigurationsdateien importieren.
		Danach kannst Du Spieler und Trainingszeiten bearbeiten, sowie weitere hinzufügen oder löschen.
		Schließlich kannst Du noch ein paar Konfigurationsoptionen ansehen, was beim Beheben von Fehlern helfen kann.
		</p>
		<p>
		<small>Löschen geht im Moment nocht nicht, weitere Hinweise dazu findest Du auf über der <a href="players_list.php">Liste der Spieler</a> bzw. <a href="practice_times_list.php">Trainingszeiten</a>.</small>
		</p>

<?php
require_once '../inc/model_player.inc.php';
$playersCount = count(Player::LoadConfigured($club_id));
$unknownPlayersCount = '?';
?>
		<h2><span class="glyphicon glyphicon-user"></span> Spieler</h2>
		<div class='list-group'>
		<a class='list-group-item' href="players_list.php">Bekannte Spieler <span class="glyphicon glyphicon-th-list"></span> anzeigen, <span class='glyphicon glyphicon-pencil'></span> bearbeiten und <span class='glyphicon glyphicon-trash'></span> löschen<span class='badge'><?=$playersCount?></span></a>
		<a class='list-group-item' href="players_list_unkown.php">Unbekannte Spieler <span class="glyphicon glyphicon-th-list"></span> anzeigen, <span class="glyphicon glyphicon-plus"></span> in die Datenbank übernehmen oder <span class='glyphicon glyphicon-trash'></span> löschen<span class='badge'><?=$unknownPlayersCount?></span></a>
		<a class='list-group-item' href="player_add.php"><span class="glyphicon glyphicon-plus"></span> hinzufügen</a>
		</div>

<?php
require_once '../inc/model_practice_time.inc.php';
$practicesCount = count(PracticeTime::LoadAll($club_id));
?>
		<h2><span class="glyphicon glyphicon-calendar"></span> Trainingszeiten</h2>
		<div class='list-group'>
		<a class='list-group-item' href="practice_times_list.php"><span class="glyphicon glyphicon-th-list"></span> anzeigen, <span class='glyphicon glyphicon-pencil'></span> bearbeiten und <span class='glyphicon glyphicon-trash'></span> löschen<span class='badge'><?=$practicesCount?></span></a>
		<a class='list-group-item' href="practice_time_add.php"><span class="glyphicon glyphicon-plus"></span> hinzufügen</a>
		</div>

		<h2><span class="glyphicon glyphicon-star-empty"></span> Sonstiges</h2>
		<div class='list-group'>
		<a class='list-group-item' href="config_show.php"><span class="glyphicon glyphicon-cog"></span> Konfiguration anzeigen</a>
		<a class='list-group-item' href="players_migrate_to_db.php"><span class="glyphicon glyphicon-user"></span><span class="glyphicon glyphicon-log-in"></span> Spieler aus Datei in die DB importieren (einmalig)</a>
		<a class='list-group-item' href="practice_times_migrate_to_db.php"><span class="glyphicon glyphicon-calendar"></span><span class="glyphicon glyphicon-log-in"></span> Trainingszeiten aus Datei in die DB importieren (einmalig)</a>
		<a class='list-group-item' href="contact.php"><span class="glyphicon glyphicon-envelope"></span> Kontaktdaten</a>
		</div>
      </div>

    </div><!-- /.container -->
<?php
html_footer();
