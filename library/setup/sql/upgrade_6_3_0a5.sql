DROP TABLE IF EXISTS `spam_filters`;
CREATE TABLE IF NOT EXISTS `spam_filters` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `value` text NOT NULL,
  `enabled` tinyint(4) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `enabled` (`enabled`)
) DEFAULT CHARACTER SET utf8, ENGINE=MyISAM;