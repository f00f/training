<?php
require_once '../config/conf.inc.php';
require_once '../inc/lib.inc.php';

$pagetitle = "{$teamNameShort} Training Admin";
html_header();

navbar_admin('contact');
?>
    <div class="container">
	  <h1>Kontakt <small><?=$teamNameShort?></small></h1>
	  
	  <h3>Ansprechpartner <?=$teamNameShort?> Training</h3>
	  <?php print $KontaktName
				? $KontaktName
				: 'Vorname Nachname'?><br>
	  E-Mail: <?php print $KontaktEmail
				? '<a href="mailto:'.$KontaktEmail.'">'.$KontaktEmail.'</a>'
				: 'vorname.nachname@...'?><br>

	  <h3>Ansprechpartner bei Problemen mit der Trainingsseite</h3>
	  Hannes Hofmann<br>
	  <a href="http://uwr1.de/kontakt/">Aktuelle Kontaktdaten</a>
	</div>
<?php
html_footer();
