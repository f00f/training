<?php
define('NO_INCLUDES', true);
require_once '../inc/conf.inc.php';
require_once '../inc/lib.inc.php';

$pagetitle = "{$teamNameShort} Training Admin";
html_header();

navbar();
?>

    <div class="container">

      <div class="starter-template">
        <h1>Trainingsseite Admin <small><?=$teamNameShort?></small></h1>
        <p class="lead">Hier kann die Trainingsseite von <?=$teamNameShort?> verwaltet werden.</p>
		<p>
		Als Erstes kannst Du Spieler und Trainingszeiten aus den bestehenden Konfigurationsdateien importieren.
		Danach kannst Du Spieler und Trainingszeiten bearbeiten, sowie weitere hinzufügen oder löschen.
		Schließlich kannst Du noch ein paar Konfigurationsoptionen ansehen, was beim Beheben von Fehlern helfen kann.
		</p>

		<h2><span class="glyphicon glyphicon-user"></span> Spieler</h2>
		<div class='list-group'>
		<a class='list-group-item' href="players_list.php"><span class="glyphicon glyphicon-th-list"></span> anzeigen, bearbeiten und löschen</a></li>
		<a class='list-group-item' href="player_add.php"><span class="glyphicon glyphicon-plus"></span> hinzufügen</a>
		</div>

		<h2><span class="glyphicon glyphicon-calendar"></span> Trainingszeiten</h2>
		<div class='list-group'>
		<a class='list-group-item' href="practice_times_list.php"><span class="glyphicon glyphicon-th-list"></span> anzeigen, bearbeiten und löschen</a>
		<a class='list-group-item' href="practice_time_add.php"><span class="glyphicon glyphicon-plus"></span> hinzufügen</a>
		</div>

		<h2><span class="glyphicon glyphicon-star-empty"></span> Sonstiges</h2>
		<div class='list-group'>
		<a class='list-group-item' href="config_show.php"><span class="glyphicon glyphicon-cog"></span> Konfiguration anzeigen</a>
		<a class='list-group-item' href="players_migrate_to_db.php"><span class="glyphicon glyphicon-user"></span><span class="glyphicon glyphicon-log-in"></span> Spieler aus Datei in die DB importieren (einmalig)</a>
		<a class='list-group-item' href="practice_times_migrate_to_db.php"><span class="glyphicon glyphicon-calendar"></span><span class="glyphicon glyphicon-log-in"></span> Trainingszeiten aus Datei in die DB importieren (einmalig)</a>
		</div>
      </div>

    </div><!-- /.container -->
<?php
html_footer();
