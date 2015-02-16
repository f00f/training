<?php
/* These functions are for backward-compatibility.
 * They are only used for import in the admin area.
 * TODO: move to admin files?
 */

require_once 'config-site.inc.php'; // @uses SECONDS_PER_DAY

 function AddWithEndDate(&$pTraining, $pEndDate) {
	global $date, $training, $trainingX;
	if ($date <= $pEndDate) {
		$training[] = $pTraining;
	}
	$pTraining['last'] = $pEndDate;
	$trainingX[] = $pTraining;
}

function AddWithStartDate(&$pTraining, $pStartDate) {
	global $date, $training, $trainingX;
	$oneWeekBefore = date('Ymd', strtotime($pStartDate) - 7*SECONDS_PER_DAY);
	if ($date > $oneWeekBefore) {
		$training[] = $pTraining;
	}
	$pTraining['first'] = $pStartDate;
	$trainingX[] = $pTraining;
}

function AddSingleDate(&$pTraining, $pDate) {
	global $date, $training, $trainingX;
	$oneWeekBefore = date('Ymd', strtotime($pDate) - 7*SECONDS_PER_DAY);
	if ($date > $oneWeekBefore AND $date <= $pDate) {
		$training[] = $pTraining;
	}
	$pTraining['first'] = $pDate;
	$pTraining['last'] = $pDate;
	$trainingX[] = $pTraining;
}
