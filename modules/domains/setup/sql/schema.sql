DROP TABLE IF EXISTS `tld_extra_attributes`;
CREATE TABLE `tld_extra_attributes` (
  `tld` varchar(10) NOT NULL,
  `extra_attributes` text NOT NULL,
  PRIMARY KEY (`tld`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `users_domains`;
CREATE TABLE `users_domains` (
    `id` INT NOT NULL AUTO_INCREMENT ,
    `userid` INT NOT NULL ,
    `domain` VARCHAR( 50 ) NOT NULL ,
    `username` VARCHAR( 50 ) NOT NULL ,
    `password` VARCHAR( 50 ) NOT NULL ,
    `period` INT NOT NULL ,
    `auto_renew` tinyint(4) NOT NULL DEFAULT '1',
    `nextbilldate` DATE NOT NULL ,
    `domain_extra_attr` TEXT,
    `registration_option` TINYINT NOT NULL ,
    `registrar` VARCHAR( 20 ) NOT NULL ,
    `registrar_orderid` VARCHAR( 50 ) NOT NULL ,
    `status` TINYINT NOT NULL ,
    PRIMARY KEY ( `id` ) ,
    INDEX ( `userid` )
) DEFAULT CHARACTER SET utf8, ENGINE=MyISAM;