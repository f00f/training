training
========

Requirements
============
PHP, SSI, MySQL, Apache (.htaccess rewrite rules)

Installation
============
* Create a database table according to training/inc/db_schema.sql
* Configure (see section Config Files below)
* Install composer to inc/, `php composer.phar install`
* Add a club to the `.htaccess` file: copy and edit the "demo" line, chain with `[OR]`.

Config Files
============
yeah, it's a mess.
* training/root.shtml
* training/inc/conf.inc.php
* training/inc/dbconf.inc.php
* training/inc/spieler.inc.php
* training/inc/trainingszeiten.inc.php
