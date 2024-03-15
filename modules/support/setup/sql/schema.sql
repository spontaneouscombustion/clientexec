DROP TABLE IF EXISTS `troubleticket`;
CREATE TABLE `troubleticket` (
  `id` int(11) NOT NULL auto_increment,
  `hashed_id` varchar(32) NOT NULL DEFAULT '',
  `userid` int(11) NOT NULL default '0',
  `company_id` int(11) NOT NULL default '0',
  `domainid` int(11) NULL default '0',
  `subject` text NOT NULL,
  `priority` int(2) NOT NULL default '3',
  `support_email` varchar(60) default NULL,
  `datesubmitted` datetime NOT NULL default '0000-00-00 00:00:00',
  `status` int(2) NOT NULL default '0',
  `messagetype` int(11) NOT NULL default '0',
  `assignedtoid` int(11) NOT NULL default '0',
  `assignedtodeptid` int(11) NOT NULL default '0',
  `lastlog_datetime` datetime NOT NULL default '0000-00-00 00:00:00',
  `rate_hash` char(32) default NULL,
  `rate` tinyint(4) NOT NULL default '0',
  `feedback` TEXT,
  `autoclose` int(2) NOT NULL default '0',
  `tag` varchar(10) NOT NULL default 'clear',
  `method` TINYINT NOT NULL DEFAULT '0',
  `externalid` VARCHAR( 45 ) NOT NULL DEFAULT 0,
  `response_time` INT DEFAULT NULL,
  PRIMARY KEY  (`id`)
) DEFAULT CHARACTER SET utf8, ENGINE=[MyISAM] AUTO_INCREMENT=1 ;
ALTER TABLE troubleticket ADD FULLTEXT(subject);
ALTER TABLE troubleticket ADD INDEX  `i_userid_status` (  `userid`,`status` );
ALTER TABLE troubleticket ADD INDEX (  `status` );

DROP TABLE IF EXISTS `troubleticket_files`;
CREATE TABLE `troubleticket_files` (
  `id` int(11) NOT NULL auto_increment,
  `ticketid` int(11) NOT NULL,
  `filename` varchar(50) NOT NULL,
  `dateadded` datetime NOT NULL,
  `filekey` varchar(16) NOT NULL,
  `userid` int(11) NOT NULL,
  `troubleticket_log_id` INT NOT NULL DEFAULT  '0',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `filekey` (`filekey`)
) DEFAULT CHARACTER SET utf8, ENGINE=MyISAM AUTO_INCREMENT=1 ;
ALTER TABLE `troubleticket_files` ADD INDEX `i_ticketid` (`ticketid`);

DROP TABLE IF EXISTS `canned_response`;
CREATE TABLE `canned_response` (
`id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`company_id` int(11) NOT NULL DEFAULT '0',
`name` VARCHAR( 45 ) NOT NULL ,
`response` MEDIUMTEXT NOT NULL ,
`userid` INT( 11 ) NOT NULL DEFAULT '0',
 KEY `i_company_id` (`company_id`)
) DEFAULT CHARACTER SET utf8, ENGINE=MyISAM AUTO_INCREMENT=1 ;

DROP TABLE IF EXISTS `troubleticket_log`;
CREATE TABLE `troubleticket_log` (
  `id` int(11) NOT NULL auto_increment,
  `company_id` int(11) NOT NULL default '0',
  `troubleticketid` int(11) NOT NULL default '0',
  `logtype` tinyint(4) NOT NULL,
  `newstate` text NOT NULL,
  `message` text NOT NULL,
  `userid` int(11) NOT NULL default '0',
  `externalemail` tinyint(4) NOT NULL default '0',
  `mydatetime` datetime NOT NULL default '0000-00-00 00:00:00',
  `logaction` tinyint(4) NOT NULL default '0',
  `private` int(2) NOT NULL default '0',
  `deletedname` VARCHAR( 35 ) NOT NULL DEFAULT '',
  `email` varchar(250) NOT NULL DEFAULT '',
  PRIMARY KEY  (`id`),
  FULLTEXT KEY `message` (`message`),
  KEY `email` (`email`)
) DEFAULT CHARACTER SET utf8, ENGINE=[MyISAM] AUTO_INCREMENT=1 ;
ALTER TABLE troubleticket_log ADD INDEX  `troubleticketid` (  `troubleticketid` );
ALTER TABLE troubleticket_log ADD INDEX  `company_id` (  `company_id` );

DROP TABLE IF EXISTS `troubleticket_type`;
CREATE TABLE `troubleticket_type` (
  `id` int(11) NOT NULL auto_increment,
  `company_id` int(11) NOT NULL DEFAULT '0',
  `name` varchar(40) NOT NULL default '',
  `description` text NOT NULL,
  `myorder` int(2) NOT NULL default '0',
  `enabled` tinyint(4) NOT NULL default '1',
  `enabled_public` tinyint(4) NOT NULL default '1',
  `target_dept` int(11) NOT NULL,
  `target_staff` int(11) NOT NULL,
  `systemid` INT( 2 ) NOT NULL DEFAULT  '0',
  `allowclose` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY  (`id`),
  KEY `i_company_id` (`company_id`)
) DEFAULT CHARACTER SET utf8, ENGINE=MyISAM;

DROP TABLE IF EXISTS `troubleticket_type_customfields`;
CREATE TABLE `troubleticket_type_customfields` (
    `tickettype_id` INT NOT NULL ,
    `custom_id` INT NOT NULL
) DEFAULT CHARACTER SET utf8, ENGINE = [MYISAM];

DROP TABLE IF EXISTS `autoresponders`;
CREATE TABLE `autoresponders` (
    `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
    `company_id` int(11) NOT NULL DEFAULT '0',
    `type` TINYINT( 4 ) NOT NULL DEFAULT '1',
    `name` VARCHAR( 75 ) NOT NULL ,
    `subject` VARCHAR( 100 ) NOT NULL ,
    `contents` TEXT NOT NULL,
    `helpid` INT( 3 ) NOT NULL DEFAULT '0',
    `description` TEXT,
    `override_from` varchar(320) NOT NULL,
    KEY `i_company_id` (`company_id`)
) DEFAULT CHARACTER SET utf8, ENGINE = MYISAM ;

DROP TABLE IF EXISTS `routingrules`;
CREATE TABLE `routingrules` (
    `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
    `company_id` int(11) NOT NULL DEFAULT '0',
    `name` VARCHAR( 100 ) NOT NULL ,
    `order` int(11) NOT NULL default '0',
    `emails` TEXT NOT NULL ,
    `user_type` TINYINT NOT NULL ,
    `autoresponder_template` INT NOT NULL ,
    `openticket` tinyint(4) NOT NULL default '0',
    `target_type` INT NOT NULL ,
    `target_priority` INT NOT NULL ,
    `target_dept` INT NOT NULL ,
    `target_staff` INT NOT NULL ,
    `copy_destinataries` TEXT NOT NULL ,
    `routing_type` TINYINT NOT NULL ,
    `pop3_hostname` varchar(50) NOT NULL,
    `pop3_port` varchar(4) NOT NULL,
    `pop3_username` VARCHAR( 50 ) NOT NULL ,
    `pop3_password` VARCHAR( 50 ) NOT NULL ,
    `pop3_delete_emails` tinyint(4) NOT NULL,
    KEY `i_company_id` (`company_id`)
) DEFAULT CHARACTER SET utf8, ENGINE=MYISAM ;

DROP TABLE IF EXISTS `routingrules_groups`;
CREATE TABLE `routingrules_groups` (
  `routingrule_id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  PRIMARY KEY  (`routingrule_id`,`group_id`)
) DEFAULT CHARACTER SET utf8, ENGINE=MyISAM;


DROP TABLE IF EXISTS `troubleticket_filters`;
CREATE TABLE `troubleticket_filters` (
  `id` int(11) NOT NULL auto_increment,
  `company_id` int(11) NOT NULL DEFAULT '0',
  `user_id` int(10) NOT NULL default '0',
  `private` int(2) NOT NULL default '0',
  `temp` tinyint(4) NOT NULL,
  `name` varchar(50) NOT NULL,
  `sql` text NOT NULL,
  `lastlog_datetime` datetime NOT NULL,
  PRIMARY KEY  (`id`)
) DEFAULT CHARACTER SET utf8, ENGINE=MyISAM;


DROP TABLE IF EXISTS `troubleticket_additionalnotification`;
CREATE TABLE `troubleticket_additionalnotification` (
  `id` int(11) NOT NULL auto_increment,
  `ticketid` int(11) NOT NULL,
  `userid` int(11) NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE(`ticketid`,`userid`)
) DEFAULT CHARACTER SET utf8, ENGINE=MyISAM AUTO_INCREMENT=1 ;

DROP TABLE IF EXISTS `departments`;
CREATE TABLE `departments` (
  `id` int(11) NOT NULL auto_increment,
  `company_id` int(11) NOT NULL DEFAULT '0',
  `name` varchar(50) NOT NULL,
  `is_generaldep` tinyint(4) NOT NULL default '0',
  `lead_id` int(11) NOT NULL,`emails_to_notify` TEXT,
  PRIMARY KEY  (`id`),
  KEY `i_company_id` (`company_id`)
) DEFAULT CHARACTER SET utf8, ENGINE=MyISAM AUTO_INCREMENT=3 ;


DROP TABLE IF EXISTS `departments_members`;
CREATE TABLE `departments_members` (
  `department_id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  `is_group` tinyint(4) NOT NULL,
  `assign_to` tinyint(4) NOT NULL default '0',
  `notify_to` tinyint(4) NOT NULL default '0',
  `sendclosed` int(2) NOT NULL default '0',
  `sendfeedback` int(2) NOT NULL default '0',
  `sendresolution` int(2) NOT NULL default '0'
) DEFAULT CHARACTER SET utf8, ENGINE=MyISAM;

DROP TABLE IF EXISTS `tags`;
CREATE TABLE tags (
    `company_id` int(11) NOT NULL DEFAULT '0',
    tag_type SMALLINT(3) NOT NULL DEFAULT 0,
    tag_name VARCHAR(35) NOT NULL,
    num_articles SMALLINT(5) UNSIGNED NOT NULL DEFAULT 1,
    PRIMARY KEY(`company_id`, `tag_name` , `tag_type`)
) DEFAULT CHARACTER SET utf8, ENGINE=MyISAM AUTO_INCREMENT=1 ;


DROP TABLE IF EXISTS `spam_filters`;
CREATE TABLE IF NOT EXISTS `spam_filters` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `value` text NOT NULL,
  `enabled` tinyint(4) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `enabled` (`enabled`)
) DEFAULT CHARACTER SET utf8, ENGINE=MyISAM;