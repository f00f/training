CREATE TABLE IF NOT EXISTS `training` (
  `name` varchar(50) NOT NULL DEFAULT '',
  `text` varchar(255) NOT NULL DEFAULT '',
  `when` int(11) NOT NULL DEFAULT '0',
  `status` varchar(10) NOT NULL DEFAULT '',
  `ip` varchar(15) NOT NULL DEFAULT '',
  `host` varchar(255) NOT NULL DEFAULT '',
  `app` varchar(255) NOT NULL DEFAULT 'web',
  `app_ver` varchar(10) NOT NULL DEFAULT '-1',
  UNIQUE KEY `name` (`name`,`when`),
  KEY `when` (`when`) USING BTREE,
  KEY `status` (`status`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
