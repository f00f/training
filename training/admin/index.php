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
		<h2>Spieler</h2>
		<ul>
		<li><a href="player_list.php">anzeigen, bearbeiten und löschen</a></li>
		<li><a href="player_add.php">hinzufügen</a></li>
		</ul>

		<div class="hidden">
		<h2>Trainingszeiten</h2>
		<ul>
		<li><a href="times_list.php">anzeigen, bearbeiten und löschen</a></li>
		<li><a href="times_add.php">hinzufügen</a></li>
		</ul>
		</div>
      </div>

    </div><!-- /.container -->
<?php
html_footer();
