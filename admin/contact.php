<?php
define('NO_INCLUDES', true);
require_once '../inc/lib.inc.php';
require_once '../inc/conf.inc.php';
require_once '../inc/dbconf.inc.php';

get_club_id();
load_config($club_id);

$pagetitle = "{$teamNameShort} Training Admin";
html_header();

navbar_admin('contact');
?>
    <div class="container">
	  <h1>Kontakt <small><?=$teamNameShort?></small></h1>
	  
	  <h3>Ansprechpartner <?=$teamNameShort?> Training</h3>
	  Vorname Nachname<br>
	  E-Mail: vorname.nachname@...

	  <h3>Ansprechpartner bei Problemen mit der Trainingsseite</h3>
	  Hannes Hofmann<br>
	  <a href="http://uwr1.de/kontakt/">Aktuelle Kontaktdaten</a>
	</div>
<?php
html_footer();
