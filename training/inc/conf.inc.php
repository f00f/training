<?php
// Besides this config file, make sure to also change the files
// training/root.shtml
// training/inc/dbconf.inc.php
// training/inc/spieler.inc.php
// training/inc/trainingszeiten.inc.php


//rootUrl: used to build links in notification emails
// should point to the folder of the training website, with trailing slash.
$rootUrl = 'http://ba.uwr1.de/training/';

//teamNameShort: used in email sender name of notification emails.
$teamNameShort = 'UWR BA';

//emailFrom: used to build the sender username of notification emails.
// the actual sender will be "training-{$emailFrom}@uwr1.de"
$emailFrom = 'ba';


$teamId = 'ba';