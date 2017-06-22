CREATE TABLE `LOGBOT_authors` (
  `id` int(11) NOT NULL auto_increment,
  `name` tinytext NOT NULL,
  `privacy` tinyint(1) unsigned NOT NULL default '0',
  `privacywarning` tinyint(1) unsigned NOT NULL default '1',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

CREATE TABLE `LOGBOT_channels` (
  `id` int(11) NOT NULL auto_increment,
  `name` tinytext NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

CREATE TABLE `LOGBOT_msgtype` (
  `id` int(11) NOT NULL auto_increment,
  `name` tinytext NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

CREATE TABLE `LOGBOT_logs` (
  `id` int(11) NOT NULL auto_increment,
  `channel_id` int(11) NOT NULL default '0',
  `msgtype_id` int(11) NOT NULL default '0',
  `nickname_id` int(11) NOT NULL default '0',
  `message` text NOT NULL,
  `timeanddate` datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`id`),
  FULLTEXT KEY `search_index` (`message`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

LOCK TABLES `LOGBOT_msgtype` WRITE;
INSERT INTO `LOGBOT_msgtype` VALUES  (1,'message'),
 (2,'url'),
 (3,'picture');
UNLOCK TABLES;