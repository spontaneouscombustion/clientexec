DROP TABLE IF EXISTS `prices`;
CREATE TABLE `prices` (
  `type` int(11) NOT NULL,
  `itemid` int(11) NOT NULL,
  `currency_abrv` varchar(4) NOT NULL,
  `pricing` text NOT NULL,
  PRIMARY KEY ( `type` , `itemid` , `currency_abrv` )
) DEFAULT CHARACTER SET utf8, ENGINE=MyISAM;

ALTER TABLE `invoice` ADD `currency` VARCHAR(5) DEFAULT '0' AFTER `description`;
UPDATE `invoice` i SET i.`currency` = (SELECT u.`currency` FROM `users` u WHERE u.`id` = i.`customerid`);
UPDATE `invoice` i SET i.`currency` = (SELECT s.`value` FROM `setting` s WHERE s.`name` = 'Default Currency') WHERE i.`currency` = '0';