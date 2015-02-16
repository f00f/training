<?php
/*
 * Edit this file, then rename it to spieler.inc.php
 *
 * Players only need an entry in this file if they want to use the email features.
 * Otherwise, arbitrary names can simply be entered into the web form.
 */
$spieler = array(
	/* Erklärung der Felder
	name:  
	email: E-Mail Adresse
	freq:  Wie oft will er E-Mails?
	       0: nie
		   >0: Bei jeder n-ten Meldung
		   <0: Bei den ersten n Meldungen

	Optionale Felder:
	keineSelbstMail: Schicke keine Email, wenn derjenige
	                 sich grad selbst an-/abgemeldet hat
					 [true/false]
	tendenz:         Kommt derjenige eher schon (gruen)
	                 oder eher net (rot)
					 ['ja'/'nein']
	grund:   Wird in Klammern hinterm Namen angezeigt
	*/
	'maxmustermann' => array(
		'name' => 'MaxMustermann',
		'email' => 'mustermann@web.de',
		'freq' => 4,
		'keineSelbstMail' => true,
		'tendenz' => 'nein',
		'grund' => 'Faule Socke',
		), 
	// add more player entries
);

$aliases = array(
);
