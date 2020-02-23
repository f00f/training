SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

--
-- Table structure for table `train_players`
--

CREATE TABLE IF NOT EXISTS `train_players` (
  `uid` int(11) NOT NULL,
  `club_id` varchar(20) NOT NULL,
  `player_name` varchar(50) NOT NULL,
  `player_data` text NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `train_practices`
--

CREATE TABLE IF NOT EXISTS `train_practices` (
  `club_id` varchar(20) NOT NULL,
  `practice_id` int(11) NOT NULL,
  `dow` varchar(2) NOT NULL,
  `begin` time NOT NULL,
  `end` time NOT NULL,
  `first` date NOT NULL,
  `last` date NOT NULL,
  `data` text NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `train_psessions`
--

CREATE TABLE IF NOT EXISTS `train_psessions` (
  `club_id` varchar(20) NOT NULL,
  `session_id` datetime NOT NULL,
  `meta` text,
  `count_yes` int(11) DEFAULT NULL,
  `count_no` int(11) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `train_replies`
--

CREATE TABLE IF NOT EXISTS `train_replies` (
  `club_id` varchar(20) NOT NULL,
  `session_id` datetime NOT NULL,
  `name` varchar(50) NOT NULL DEFAULT '',
  `text` text NOT NULL,
  `when` int(11) NOT NULL DEFAULT '0',
  `status` varchar(10) NOT NULL DEFAULT '',
  `ip` varchar(15) NOT NULL DEFAULT '',
  `host` varchar(255) NOT NULL DEFAULT '',
  `app` varchar(255) NOT NULL DEFAULT 'web',
  `app_ver` varchar(10) NOT NULL DEFAULT '-1'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Indexes for table `train_players`
--
ALTER TABLE `train_players`
  ADD PRIMARY KEY (`uid`),
  ADD UNIQUE KEY `club_player` (`club_id`,`player_name`);

--
-- Indexes for table `train_practices`
--
ALTER TABLE `train_practices`
  ADD PRIMARY KEY (`practice_id`);

--
-- Indexes for table `train_psessions`
--
ALTER TABLE `train_psessions`
  ADD UNIQUE KEY `namewhen` (`club_id`,`session_id`),
  ADD KEY `club_id` (`club_id`),
  ADD KEY `practice_id` (`session_id`);

--
-- Indexes for table `train_replies`
--
ALTER TABLE `train_replies`
  ADD UNIQUE KEY `namewhen` (`name`,`when`),
  ADD KEY `club_id` (`club_id`),
  ADD KEY `practice_id` (`session_id`),
  ADD KEY `name` (`name`),
  ADD KEY `status` (`status`);

--
-- AUTO_INCREMENT for table `train_players`
--
ALTER TABLE `train_players`
  MODIFY `uid` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `train_practices`
--
ALTER TABLE `train_practices`
  MODIFY `practice_id` int(11) NOT NULL AUTO_INCREMENT;