<?php
// This file is a shortcut to including all config files.

$my_inc_paths = array('./', './inc/', './config/', '../inc/', '../config/');
set_include_path(get_include_path() . implode(PATH_SEPARATOR, $my_inc_paths));

require_once 'config-site.inc.php';
require_once 'config-clubs.inc.php';
require_once 'dbconf.inc.php';
require_once 'firebaseconf.inc.php';
require_once 'mailconf.inc.php';
