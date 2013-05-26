<?php
/*
 * Edit this file, then rename it to trainingszeiten.inc.php
 */
$training = array();
$date = date('Ymd');

/**
 * Die Informationen über einen Trainingstermin sind in einem Array
 * gespeichert, das immer wie folgt aufgebaut ist. Das Format von Wochentag
 * und Uhrzeiten ist wichtig.
 * $t = array(
 *	 'tag'     => 'Mo',            // Wochentag [2 Buchstaben]
 *	 'zeit'    => '20:00 - 21:30', // Beckenzeit [HH:MM - HH:MM]
 *	 'anreise' => '19:45',         // Anreise / Treffpunkt [HH:MM]
 *	 'ort'     => 'Bambados',       // Bad
 * );
 *
 * Trainingstermine müssen danach immer eingetragen werden. Dazu gibt es drei
 * Moeglichkeiten. Das Datum ist immer im Format YYYYMMDD.
 * 1) ab einem bestimmten Datum auf unbestimmte Zeit
 *    AddWithStartDate($t, 20120401);
 * 2) ab sofort, bis zu einem bestimmten Datum
 *    AddWithEndDate($t, 20120401);
 * 3) einmalige Termine
 *    AddSingleDate($t, 20120401);
*/

// Montagstraining  Sprunggrube und 3 Bahnen
$t = array(
	'tag'     => 'Mo',
	'zeit'    => '20:00 - 21:30',
	'anreise' => '19:45',
	'ort'     => 'Bambados',
);
AddWithStartDate($t, 20111131);


// Donnerstagstraining Sprunggrube und 1 Bahn

$t = array(
	'tag'     => 'Mi',
	'zeit'    => '19:00 - 20:30',
	'anreise' => '18:45',
	'ort'     => 'Bambados',
);
AddWithStartDate($t, 20111131);
?>