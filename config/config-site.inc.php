<?php
//! Global site-wide configuration options

if (!defined('ON_TEST_SERVER')) {
	define('ON_TEST_SERVER', (false !== strpos($_SERVER['HTTP_HOST'], '.test')));
}

date_default_timezone_set('Europe/Berlin');

define('SPAM_SAME_IP_TIMEOUT', 120);
define('SPAM_SAME_IP_COUNT', 2);
define('SPAM_SAME_USER_TIMEOUT', 300);

// if a user submits less than LATE_THRESHOLD before start of the training, show notice
define('LATE_THRESHOLD', 1 * 3600); // TODO: make this a config value
// RESET_DELAY_HOURS after begin of the training, switch to next training
define('RESET_DELAY_HOURS', 1); // TODO: make this a config value

// Don't change values below.
define('RESET_DELAY_MINUTES', RESET_DELAY_HOURS * 60);
define('RESET_DELAY', RESET_DELAY_MINUTES * 60);
define('SECONDS_PER_DAY', 86400);
define('PLUGINS_FILE', 'inc/plugins.inc.php');
define('YOU_ARE_LATE_URL', './spaet-dran.shtml');

if (@ON_TEST_SERVER) {
	ini_set('display_errors', 1);
	error_reporting(E_ALL);
}
