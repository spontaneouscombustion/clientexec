#
# Table structure for table `altuseremail`
#

DROP TABLE IF EXISTS `altuseremail`;
CREATE TABLE `altuseremail` (
  `id` int(11) NOT NULL auto_increment,
  `userid` int(11) NOT NULL default '0',
  `email` varchar(254) NOT NULL default '',
  `sendnotifications` tinyint(4) NOT NULL default '1',
  `sendinvoice` tinyint(4) NOT NULL default '1',
  `sendsupport` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`id`)
) DEFAULT CHARACTER SET utf8, ENGINE=MyISAM AUTO_INCREMENT=1 ;

# --------------------------------------------------------

# --------------------------------------------------------

#
# Table structure for table `domains`
#

DROP TABLE IF EXISTS `domains`;
CREATE TABLE `domains` (
  `id` int(11) NOT NULL auto_increment,
  `DomainName` text,
  `CustomerID` int(11) default NULL,
  `current` tinyint(4) NOT NULL default '1',
  `status` tinyint(1) NOT NULL default '0',
  `Plan` int(11) NOT NULL default '0',
  `dateActivated` datetime default NULL,
  `signup` TINYINT NOT NULL DEFAULT '0',
  `custom_price` FLOAT(23,2) NOT NULL DEFAULT '0.00',
  `use_custom_price` TINYINT NOT NULL DEFAULT '0',
  `parentPackageId` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `CustomerID` (`CustomerID`)
) DEFAULT CHARACTER SET utf8, ENGINE=MyISAM AUTO_INCREMENT=1 ;
ALTER TABLE `domains` ADD INDEX `PARENTID` ( `parentPackageId` );
ALTER TABLE `domains` ADD INDEX (  `status` );
# --------------------------------------------------------

#
# Table structure for table `user_customuserfields`
#

DROP TABLE IF EXISTS `user_customuserfields`;
CREATE TABLE `user_customuserfields` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL default '0',
  `customid` int(11) NOT NULL default '0',
  `value` text,
  PRIMARY KEY ( `id` )
) DEFAULT CHARACTER SET utf8, ENGINE=MyISAM;
ALTER TABLE  `user_customuserfields` ADD  `updated_at` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00';
ALTER TABLE  `user_customuserfields` ADD  `created_at` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00';
ALTER TABLE `user_customuserfields` ADD UNIQUE INDEX `userid_customid` (`userid`,`customid`);

#
# Table structure for table `customuserfields`
#

DROP TABLE IF EXISTS `customuserfields`;
CREATE TABLE `customuserfields` (
  `id` int(11) NOT NULL auto_increment,
  `company_id` int(11) NOT NULL DEFAULT '0',
  `name` varchar(50) NOT NULL default '',
  `type` tinyint(4) NOT NULL default '0',
  `isrequired` tinyint(4) NOT NULL default '0',
  `isChangable` tinyint(4) NOT NULL default '1',
  `isAdminOnly` tinyint(4) NOT NULL default '0',
  `width` mediumint(9) NOT NULL default '20',
  `myOrder` int(11) NOT NULL default '0',
  `showcustomer` tinyint(4) NOT NULL default '1',
  `showadmin` tinyint(4) NOT NULL default '1',
  `InSignup` tinyint(1) NOT NULL default '0',
  `showingridadmin` tinyint(1) NOT NULL default '0',
  `inSettings` smallint(6) NOT NULL default '1',
  `dropdownoptions` longtext NOT NULL,
  `desc` VARCHAR(250) NOT NULL default '',
  `usedbyplugin` VARCHAR( 60 ) NOT NULL DEFAULT  '',
  `regex` text DEFAULT NULL,
  PRIMARY KEY  (`id`)
) DEFAULT CHARACTER SET utf8, ENGINE=MyISAM AUTO_INCREMENT=25 ;

DROP TABLE IF EXISTS `object_customField`;
CREATE TABLE `object_customField` (
    `objectid` int(11) NOT NULL default '0',
    `customFieldId` int(11) NOT NULL default '0',
    `value` text,
    PRIMARY KEY (`objectid`, `customFieldId`)
) DEFAULT CHARACTER SET utf8, ENGINE=MyISAM;
ALTER TABLE `object_customField` ADD INDEX (  `customFieldId` ) ;

DROP TABLE IF EXISTS `customField`;
CREATE TABLE `customField` (
    `id` int(11) NOT NULL auto_increment,
    `company_id` int(11) NOT NULL DEFAULT '0',
    `groupId` tinyint(4) NOT NULL default '0',
    `subGroupId` tinyint(4) NOT NULL default '0',
    `fieldType` tinyint(4) NOT NULL default '0',
    `name` varchar(50) NOT NULL default '',
    `isRequired` tinyint(4) NOT NULL default '0',
    `isChangeable` tinyint(4) NOT NULL default '1',
    `isAdminOnly` tinyint(4) NOT NULL default '0',
    `fieldOrder` int(11) NOT NULL default '0',
    `showCustomer` tinyint(4) NOT NULL default '1',
    `showAdmin` tinyint(4) NOT NULL default '1',
    `dropDownOptions` longtext NOT NULL,
    `inSettings` smallint(6) NOT NULL default '1',
    `InSignup` tinyint(1) NOT NULL default '0',
    `showingridadmin` tinyint(1) NOT NULL default '0',
    `showingridportal` tinyint(1) NOT NULL default '0',
    `partofproductidentifier` tinyint(1) NOT NULL default '0',
    `desc` VARCHAR(250) NOT NULL default '',
    `isEncrypted` tinyint(1) NOT NULL DEFAULT '0',
    `usedbyplugin` VARCHAR( 60 ) NOT NULL DEFAULT  '',
    `isClientChangeable` tinyint(4) NOT NULL DEFAULT '0',
    `regex` text DEFAULT NULL,
    CONSTRAINT groupId_subGroupId_name UNIQUE(`groupId`, `subGroupId`, `name`),
    PRIMARY KEY (`id`)
) DEFAULT CHARACTER SET utf8, ENGINE=MyISAM AUTO_INCREMENT=1;
# -----------------------------------------
# #########################################

#
# Table structure for table `clients_notes`
#

DROP TABLE IF EXISTS `clients_notes`;
CREATE TABLE `clients_notes` (
    `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
    `date` datetime NOT NULL,
    `target_id` INT NOT NULL ,
    `is_target_group` tinyint(4) NOT NULL default '0',
    `admin_id` int(11) NOT NULL,
    `note` TEXT NOT NULL ,
    `visible_client` TINYINT NOT NULL,
    `archived` TINYINT NOT NULL DEFAULT '0',
    `subject` varchar(250) NOT NULL
) DEFAULT CHARACTER SET utf8, ENGINE = MYISAM;

# --------------------------------------------------------

#
# Table structure for table `clients_notes_tickettypes`
#

DROP TABLE IF EXISTS `clients_notes_tickettypes`;
CREATE TABLE `clients_notes_tickettypes` (
  `note_id` int(11) NOT NULL,
  `tickettype_id` int(11) NOT NULL,
  PRIMARY KEY  (`note_id`,`tickettype_id`)
) DEFAULT CHARACTER SET utf8, ENGINE=MyISAM;
