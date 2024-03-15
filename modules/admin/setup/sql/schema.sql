DROP TABLE IF EXISTS `announcement`;
CREATE TABLE IF NOT EXISTS `announcement` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `company_id` int(11) NOT NULL DEFAULT '0',
    `title` varchar(125) NOT NULL DEFAULT '',
    `excerpt` text NOT NULL,
    `post` longtext NOT NULL,
    `postdate` datetime DEFAULT '0000-00-00 00:00:00',
    `publish` tinyint(4) NOT NULL DEFAULT '0',
    `authorid` int(11) NOT NULL DEFAULT '0',
    `recipient` tinyint(4) NOT NULL DEFAULT '0',
    `pinned` tinyint(4) NOT NULL DEFAULT '0',
    PRIMARY KEY (`id`),
    KEY `pinned` (`pinned`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `announcement_recipient`;
CREATE TABLE `announcement_recipient` (
    `ann_id` INT NOT NULL ,
    `recipient_id` INT NOT NULL ,
    PRIMARY KEY ( `ann_id` , `recipient_id` )
) DEFAULT CHARACTER SET utf8, ENGINE=MYISAM ;

DROP TABLE IF EXISTS `calendar`;
CREATE TABLE `calendar` (
    `id` INT NOT NULL AUTO_INCREMENT ,
    `title` VARCHAR( 256 ) NOT NULL ,
    `allday` BOOLEAN DEFAULT '0' NOT NULL,
    `start` DATETIME NULL,
    `end` DATETIME NULL,
    `url` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL,
    `className` VARCHAR(56) CHARACTER SET utf8 COLLATE utf8_general_ci NULL,
    `appliesto` INT(11) DEFAULT '0' NULL,
    `description` TEXT NULL,
    `userid` INT NOT NULL DEFAULT '0',
    `isprivate` BOOLEAN DEFAULT '0' NOT NULL,
    `isrepeating` BOOLEAN DEFAULT '0' NOT NULL,
    `company_id` INT DEFAULT '0' NOT NULL,
    PRIMARY KEY ( `id` )
) DEFAULT CHARACTER SET utf8, ENGINE=MYISAM ;

DROP TABLE IF EXISTS `billing_cycle`;
CREATE TABLE `billing_cycle` (
  `id` int(11) NOT NULL auto_increment,
  `name` text NOT NULL,
  `time_unit` char(1) NOT NULL default 'm',
  `amount_of_units` int(11) NOT NULL default 0,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `billing_cycle` (`time_unit`,`amount_of_units`)
) DEFAULT CHARACTER SET utf8, ENGINE=MyISAM AUTO_INCREMENT=1;

DROP TABLE IF EXISTS `billingtype`;
CREATE TABLE `billingtype` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(25) NOT NULL default '',
  `description` text NOT NULL,
  `detail` text NOT NULL,
  `price` varchar(10) NOT NULL default '0.00',
  `archived` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`id`)
) DEFAULT CHARACTER SET utf8, ENGINE=MyISAM AUTO_INCREMENT=1 ;

DROP TABLE IF EXISTS `coupons`;
CREATE TABLE `coupons` (
  `coupons_id` int(11) NOT NULL auto_increment,
  `coupons_name` varchar(50) NOT NULL default '',
  `coupons_description` text NOT NULL,
  `coupons_code` varchar(50) NOT NULL default '',
  `coupons_quantity` int(11) NOT NULL default '0',
  `coupons_type` tinyint(4) NOT NULL default '0',
  `coupons_applicable_to` tinyint(4) NOT NULL default '127',
  `coupons_discount` DECIMAL(25,3) NOT NULL default '0.00',
  `coupons_start` date NOT NULL default '0000-00-00',
  `coupons_expires` date NOT NULL default '0000-00-00',
  `coupons_recurring` tinyint(4) NOT NULL default '0',
  `coupons_recurringmonths` int(11) NOT NULL default '0',
  `coupons_billingcycles` text NOT NULL,
  `coupons_archive` tinyint(1) NOT NULL default '0',
  `coupons_taxable` tinyint(4) NOT NULL default '1',
  PRIMARY KEY  (`coupons_id`)
) DEFAULT CHARACTER SET utf8, ENGINE=MyISAM;

DROP TABLE IF EXISTS `coupons_usage`;
CREATE TABLE `coupons_usage` (
  `invoiceentryid` INT(11) NOT NULL ,
  `couponid` INT(11) NOT NULL ,
  `isrecurring` tinyint(1) NOT NULL,
  PRIMARY KEY ( `invoiceentryid` , `couponid`, `isrecurring` )
) DEFAULT CHARACTER SET utf8, ENGINE=MYISAM ;

DROP TABLE IF EXISTS `coupons_packages`;
CREATE TABLE `coupons_packages` (
  `id` int(11) NOT NULL auto_increment,
  `coupons_id` int(11) NOT NULL default '0',
  `promotion_id` int(11) NOT NULL default '0',
  `package_id` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`)
) DEFAULT CHARACTER SET utf8, ENGINE=MyISAM;

DROP TABLE IF EXISTS `currency`;
CREATE TABLE `currency` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(250) NOT NULL default '',
  `symbol` char(10) NOT NULL default '',
  `decimalssep` char(10) NOT NULL default '.',
  `thousandssep` char(10) NOT NULL default ',',
  `abrv` varchar(4) NOT NULL default '',
  `alignment` varchar(10) NOT NULL default 'left',
  `precision` int(1) NOT NULL default '2',
  `rate` DECIMAL(25,10) NOT NULL default '1.0000000000',
  `enabled` tinyint(4) NOT NULL default '1',
  UNIQUE KEY `abrv` (`abrv`),
  KEY `id` (`id`)
) DEFAULT CHARACTER SET utf8, ENGINE=MyISAM AUTO_INCREMENT=18 ;

DROP TABLE IF EXISTS `domain_packageaddon_prices`;
CREATE TABLE `domain_packageaddon_prices` (
    `domain_id` int(11) NOT NULL default '0',
    `packageaddon_prices_id` int(11) NOT NULL default '0',
    `billing_cycle` int(11) NOT NULL default '0',
    `quantity` DECIMAL(25,3) NOT NULL DEFAULT '1.00',
    `nextbilldate` date default NULL,
    `openticket` TINYINT(4) NOT NULL DEFAULT '0',
    PRIMARY KEY  (`domain_id`,`packageaddon_prices_id`)
) DEFAULT CHARACTER SET utf8, ENGINE=MyISAM;

DROP TABLE IF EXISTS `email_queue`;
CREATE TABLE `email_queue` (
  `id` int(11) NOT NULL auto_increment,
  `subject` varchar(255) NOT NULL default '',
  `from` varchar(50) NOT NULL default '',
  `from_name` varchar(50) NOT NULL default '',
  `bcc` text NOT NULL,
  `priority` tinyint(4) NOT NULL default '0',
  `confirmreceipt` tinyint(4) NOT NULL default '0',
  `emailtype` varchar(50) NOT NULL default '',
  `body` text NOT NULL,
  `contenttype` tinyint(4) NOT NULL default '0',
  `dfilename` varchar(50) NOT NULL default '',
  `attachment` longtext NOT NULL,
  `cc` text NOT NULL,
  PRIMARY KEY  (`id`)
) DEFAULT CHARACTER SET utf8, ENGINE=MyISAM;

DROP TABLE IF EXISTS `groups`;
CREATE TABLE `groups` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(50) NOT NULL,
  `company_id` INT(11) NOT NULL default '0',
  `isadmin` tinyint(4) NOT NULL default '1',
  `issuperadmin` tinyint(4) NOT NULL default '0',
  `iscompanysuperadmin` tinyint(4) NOT NULL default '0',
  `iscustomersmaingroup` tinyint(4) NOT NULL DEFAULT '0',
  `description` text NOT NULL,
  `usedefaultcolor` tinyint(4) NOT NULL default '1',
  `groupcolor` varchar(7),
  `livesupportid` varchar( 25 ) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `i_company_id` (`company_id`)
) DEFAULT CHARACTER SET utf8, ENGINE=MyISAM;

DROP TABLE IF EXISTS `email_queue_addressees`;
CREATE TABLE `email_queue_addressees` (
    `email_queue_id` INT NOT NULL ,
    `userid` INT NOT NULL ,
    PRIMARY KEY ( `email_queue_id` , `userid` )
) DEFAULT CHARACTER SET utf8, ENGINE = MYISAM ;

DROP TABLE IF EXISTS `help`;
CREATE TABLE `help` (
  `id` int(11) NOT NULL auto_increment,
  `title` varchar(100) NOT NULL default '',
  `detail` text NOT NULL,
  `linkwords` varchar(40) NOT NULL default 'Click to view available tags',
  `width` int(3) NOT NULL default '240',
  `height` int(3) NOT NULL default '320',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `id` (`id`),
  KEY `id_2` (`id`)
) DEFAULT CHARACTER SET utf8, ENGINE=MyISAM AUTO_INCREMENT=8 ;

DROP TABLE IF EXISTS `invoice`;
CREATE TABLE `invoice` (
  `id` int(11) NOT NULL auto_increment,
  `customerid` int(11) NOT NULL default '0',
  `billdate` date NOT NULL default '0000-00-00',
  `description` varchar(200) NOT NULL default '',
  `currency` varchar(5) default '0',
  `amount` DECIMAL(25,3) NOT NULL default '0.00',
  `subtotal` DECIMAL(25,3) NOT NULL default '0.00',
  `balance_due` DECIMAL(25,3) NOT NULL default '0.00',
  `sent` tinyint(4) NOT NULL default '0',
  `status` tinyint(4) NOT NULL default '0',
  `archive` tinyint(4) NOT NULL default '0',
  `datecreated` DATE default NULL,
  `sentdate` date default NULL,
  `datepaid` date default NULL,
  `tax` DECIMAL(25,3) NOT NULL default '0.00',
  `taxname` varchar(20) NOT NULL default '',
  `tax2` DECIMAL(25,3) NOT NULL default '0.00',
  `tax2name` varchar(20) NOT NULL default '',
  `tax2compound` tinyint(4) NOT NULL default '0',
  `processorid` varchar(255) NOT NULL default '',
  `pluginused` VARCHAR( 30 ) DEFAULT 'none' NOT NULL,
  `checknum` VARCHAR( 50 ) NOT NULL ,
  `note` TEXT NOT NULL,
  `subscription_id` TEXT NOT NULL,
  PRIMARY KEY  (`id`),
  INDEX ( `customerid` ),
  INDEX ( `status` )
) DEFAULT CHARACTER SET utf8, ENGINE=MyISAM AUTO_INCREMENT=1 ;

DROP TABLE IF EXISTS `invoiceentry`;
CREATE TABLE `invoiceentry` (
  `id` int(11) NOT NULL auto_increment,
  `customerid` int(11) NOT NULL default '0',
  `description` varchar(200) NOT NULL default '',
  `detail` text NOT NULL,
  `invoiceid` int(11) NOT NULL default '0',
  `date` date NOT NULL default '0000-00-00',
  `period_start` date DEFAULT NULL,
  `period_end` date DEFAULT NULL,
  `billingtypeid` int(11) NOT NULL default '0',
  `is_prorating` tinyint(4) NOT NULL default '0',
  `price` DECIMAL(25,3) NOT NULL default '0.00',
  `price_percent` DECIMAL(25,3) NOT NULL default '0.00',
  `quantity` DECIMAL(25,3) NOT NULL DEFAULT '1.00',
  `recurring` int(11) NOT NULL default '0',
  `recurringappliesto` int(11) default '0',
  `appliestoid` int(11) NOT NULL default '0',
  `coupon_applicable_to` tinyint(4) NOT NULL default '0',
  `includenextpayment` tinyint(4) NOT NULL default '0',
  `paymentterm` int(11) NOT NULL default '0',
  `setup` tinyint(4) NOT NULL default '0',
  `addon_setup` tinyint(4) NOT NULL default '0',
  `taxable` tinyint(4) NOT NULL default '0',
  `taxamount` DECIMAL(25,3) NOT NULL DEFAULT '0.00',
  PRIMARY KEY  (`id`),
  INDEX `invoiceid-appliestoid` (`invoiceid`, `appliestoid`),
  INDEX `appliestoid` (`appliestoid`)
) DEFAULT CHARACTER SET utf8, ENGINE=MyISAM COMMENT='Maintain information of work completed for customer' AUTO_INCREMENT=1 ;

DROP TABLE IF EXISTS `invoicetransaction`;
CREATE TABLE `invoicetransaction` (
  `id` int(11) NOT NULL auto_increment,
  `invoiceid` int(11) NOT NULL default '0',
  `accepted` tinyint(4) NOT NULL default '0',
  `response` text NOT NULL,
  `transactiondate` datetime NOT NULL default '0000-00-00 00:00:00',
  `transactionid` VARCHAR( 100 ) DEFAULT 'NA' NOT NULL,
  `action` VARCHAR( 10 ) DEFAULT 'none' NOT NULL,
  `last4` VARCHAR( 5 ) DEFAULT '0000' NOT NULL,
  `amount` DECIMAL(25,3) DEFAULT '0.00' NULL,
  PRIMARY KEY  (`id`),
  INDEX ( `invoiceid` )
) DEFAULT CHARACTER SET utf8, ENGINE=MyISAM AUTO_INCREMENT=1 ;
ALTER TABLE  `invoicetransaction` ADD INDEX (  `transactionid` ) ;

DROP TABLE IF EXISTS `nameserver`;
CREATE TABLE `nameserver` (
  `id` int(11) NOT NULL auto_increment,
  `serverid` int(11) NOT NULL default '0',
  `ip` varchar(50) NOT NULL default '',
  `hostname` varchar(50) NOT NULL default '',
  PRIMARY KEY  (`id`)
) DEFAULT CHARACTER SET utf8, ENGINE=MyISAM AUTO_INCREMENT=1 ;


DROP TABLE IF EXISTS `package`;
CREATE TABLE `package` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `planname` varchar(45) NOT NULL,
  `description` text NOT NULL,
  `asset_html` TEXT NOT NULL,
  `highlight` tinyint(1) NOT NULL DEFAULT '0',
  `planid` int(11) NOT NULL DEFAULT '0',
  `showpackage` smallint(6) NOT NULL DEFAULT '1',
  `pricing` text NOT NULL,
  `automaticactivation` tinyint(4) DEFAULT '1',
  `allowdirectlink` tinyint(4) NOT NULL DEFAULT '1',
  `sendwelcome` tinyint(4) NOT NULL DEFAULT '1',
  `stockInfo` text NOT NULL,
  `emailTemplate` smallint(5) NOT NULL DEFAULT '0',
  `bundledProducts` TEXT NOT NULL,
  `advanced` text NOT NULL,
  `signup_order` INT NOT NULL DEFAULT  '1',
  `openticket` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=0 ;

DROP TABLE IF EXISTS `package_variable`;
CREATE TABLE `package_variable` (
  `packageid` int(11) NOT NULL default '0',
  `varname` varchar(250) NOT NULL default '',
  `value` varchar(200) NOT NULL default '',
  PRIMARY KEY  (`packageid`,`varname`)
) DEFAULT CHARACTER SET utf8, ENGINE=MyISAM;

DROP TABLE IF EXISTS `packageaddon`;
CREATE TABLE `packageaddon` (
    `id` INT NOT NULL AUTO_INCREMENT ,
    `package_id` INT NOT NULL ,
    `name` varchar(50) NOT NULL,
    `description` text NOT NULL,
    `plugin_var` VARCHAR( 50 ) NOT NULL ,
    `order` int(11) NOT NULL default '0',
    `taxable` tinyint(4) NOT NULL default '0',
    PRIMARY KEY ( `id` )
) DEFAULT CHARACTER SET utf8, ENGINE=MyISAM;

DROP TABLE IF EXISTS `packageaddon_prices`;
CREATE TABLE `packageaddon_prices` (
    `id` int(11) NOT NULL auto_increment,
    `packageaddon_id` int(11) NOT NULL default '0',
    `sortkey` int(11) NOT NULL default '0',
    `detail` varchar(50) NOT NULL default '',
    `plugin_var_value` varchar(50) NOT NULL default '',
    `price` text NOT NULL,
    `openticket` TINYINT NOT NULL DEFAULT  '0',
    PRIMARY KEY  (`id`)
) DEFAULT CHARACTER SET utf8, ENGINE=MyISAM;

DROP TABLE IF EXISTS `package_server`;
CREATE TABLE `package_server` (
  `id` int(11) NOT NULL auto_increment,
  `package_id` int(11) NOT NULL default '0',
  `server_id` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`)
) DEFAULT CHARACTER SET utf8, ENGINE=MyISAM;

DROP TABLE IF EXISTS `package_upgrades`;
CREATE TABLE `package_upgrades` (
  `id` int(11) NOT NULL auto_increment,
  `origin_package_id` int(11) NOT NULL default '0',
  `promotion_id` int(11) NOT NULL default '0',
  `package_id` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`)
) DEFAULT CHARACTER SET utf8, ENGINE=MyISAM;

DROP TABLE IF EXISTS `plugin_custom_data`;
CREATE TABLE `plugin_custom_data` (
 `name` VARCHAR( 50 ) NOT NULL ,
 `value` TEXT NULL ,
 `plugin_name` VARCHAR( 25 ) NOT NULL ,
 `plugin_type` VARCHAR( 25 ) NOT NULL ,
 `user_id` INT NOT NULL ,
PRIMARY KEY (  `name` ,  `plugin_name` ,  `plugin_type` ,  `user_id` )
) DEFAULT CHARACTER SET utf8, ENGINE=MyISAM;

DROP TABLE IF EXISTS `promotion`;
CREATE TABLE `promotion` (
  `id` int(11) NOT NULL auto_increment,
  `description` text NOT NULL,
  `insignup` smallint(1) NOT NULL default '0',
  `name` varchar(50) NOT NULL default '',
  `type` smallint(1) NOT NULL default '0',
  `canDelete` tinyint(1) NOT NULL default '1',
  `groupOrder` tinyint(3) NOT NULL default '1',
  `style` VARCHAR( 56 ) NOT NULL DEFAULT  'default',
  `advanced` text NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=0 ;

DROP TABLE IF EXISTS `promotion_customdomainfields`;
CREATE TABLE `promotion_customdomainfields` (
`promotionid` INT NOT NULL ,
`customid` INT NOT NULL
) DEFAULT CHARACTER SET utf8, ENGINE = MYISAM ;

DROP TABLE IF EXISTS `recurringfee`;
CREATE TABLE `recurringfee` (
  `id` int(11) NOT NULL auto_increment,
  `customerid` int(11) NOT NULL default '0',
  `billingtypeid` int(11) NOT NULL default '0',
  `packageaddon_prices_id` int(11) NOT NULL default '0',
  `description` text default NULL,
  `detail` text default NULL,
  `amount` DECIMAL(25,3) NOT NULL default '0.00',
  `amount_percent` DECIMAL(25,3) NOT NULL default '0.00',
  `quantity` DECIMAL(25,3) NOT NULL DEFAULT '1.00',
  `appliestoid` int(11) NOT NULL default '0',
  `coupon_applicable_to` tinyint(4) NOT NULL default '0',
  `nextbilldate` date default NULL,
  `paymentterm` int(11) NOT NULL default '0',
  `disablegenerate` tinyint(4) NOT NULL default '0',
  `taxable` tinyint(4) NOT NULL default '0',
  `monthlyusage` text default NULL,
  `recurring` tinyint(4) NOT NULL default '1',
  `auto_charge_cc` tinyint(4) NOT NULL default '1',
  `subscription_id` TEXT NOT NULL,
  PRIMARY KEY  (`id`)
) DEFAULT CHARACTER SET utf8, ENGINE=MyISAM AUTO_INCREMENT=1 ;
ALTER TABLE `recurringfee` ADD INDEX  `applies_recurring` (  `appliestoid` ,  `recurring` );
ALTER TABLE `recurringfee` ADD INDEX (  `appliestoid` );
ALTER TABLE `recurringfee` ADD INDEX (  `paymentterm` );

DROP TABLE IF EXISTS `report`;
CREATE TABLE `report` (
    `name` VARCHAR( 80 ) NOT NULL ,
    `public` TINYINT DEFAULT '0' NOT NULL ,
    `quickgraph` TINYINT DEFAULT '0' NOT NULL,
    PRIMARY KEY ( `name` )
) DEFAULT CHARACTER SET utf8, ENGINE=MyISAM;

DROP TABLE IF EXISTS `server`;
CREATE TABLE `server` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(100) NOT NULL default '',
  `hostname` varchar(50) NOT NULL default '',
  `sharedip` varchar(50) NOT NULL default '',
  `isdefault` tinyint(1) NOT NULL default '0',
  `plugin` varchar(25) NOT NULL default '',
  `last_utilization` datetime NOT NULL default '0000-00-00 00:00:00',
  `domains_quota` int(11) NOT NULL default '0',
  `statsurl` VARCHAR( 225 ) NOT NULL ,
  `statsviewable` TINYINT DEFAULT '0' NOT NULL,
  `status_message` TEXT NOT NULL,
  `cost` DECIMAL(25,3) NOT NULL default '0.00',
  `provider` varchar(250) NOT NULL,
  `prepend_username` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY  (`id`)
) DEFAULT CHARACTER SET utf8, ENGINE=MyISAM;

DROP TABLE IF EXISTS `serverip`;
CREATE TABLE `serverip` (
  `serverid` int(11) NOT NULL default '0',
  `ip` varchar(50) NOT NULL default ''
) DEFAULT CHARACTER SET utf8, ENGINE=MyISAM;

DROP TABLE IF EXISTS `serverplugin_options`;
CREATE TABLE `serverplugin_options` (
  `serverid` int(11) NOT NULL default '0',
  `optionname` varchar(125) NOT NULL default '',
  `value` longblob NOT NULL,
  PRIMARY KEY  (`serverid`,`optionname`)
) DEFAULT CHARACTER SET utf8, ENGINE=MyISAM;

DROP TABLE IF EXISTS `setting_options`;
CREATE TABLE `setting_options` (
  `id` int(11) NOT NULL auto_increment,
  `settingid` int(11) NOT NULL default '0',
  `name` varchar(50) NOT NULL default '',
  `value` text,
  PRIMARY KEY  (`id`)
) DEFAULT CHARACTER SET utf8, ENGINE=MyISAM COMMENT='Contains all the available options for a giving setting' AUTO_INCREMENT=1 ;

DROP TABLE IF EXISTS `taxrule`;
CREATE TABLE `taxrule` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `countryiso` VARCHAR(5) DEFAULT '' NOT NULL,
  `state` varchar(20) NOT NULL default '',
  `tax` DECIMAL(25,3) NOT NULL default '0.00',
  `vat` BOOL DEFAULT '0' NOT NULL,
  `name` varchar(20) NOT NULL default '',
  `level` TINYINT(1) DEFAULT '1' NOT NULL,
  `compound` BOOL DEFAULT '0' NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `country-state-level` (`countryiso`,`state`,`level`)
) DEFAULT CHARACTER SET utf8, ENGINE=MyISAM AUTO_INCREMENT = 1;

DROP TABLE IF EXISTS `team_status`;
CREATE TABLE `team_status` (
  `id` int(11) NOT NULL auto_increment,
  `userid` int(11) NOT NULL,
  `userstatus` text NOT NULL,
  `status_datetime` datetime NOT NULL,
  `replyid` int(11),
  PRIMARY KEY  (`id`)
) DEFAULT CHARACTER SET utf8, ENGINE=MyISAM;

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL auto_increment,
  `company_id` int(11) NOT NULL DEFAULT '0',
  `current` tinyint(1) default '1',
  `paymenttype` varchar(30) NOT NULL default '0',
  `clienttype` tinyint(4) NOT NULL default '0',
  `groupid` int(10) default '1',
  `password` varchar(60) default NULL,
  `remember_token` VARCHAR( 100 ) NULL,
  `dateActivated` date default NULL,
  `signature_text` text default NULL,
  `signature_html` text default NULL,
  `loggedin` SMALLINT( 1 ) NOT NULL DEFAULT '0',
  `lastlogin` datetime default NULL,
  `lastseen` datetime default NULL,
  `lastview` TEXT,
  `active` smallint(6) NOT NULL default '1',
  `recurring` tinyint(4) default '1',
  `currency` varchar(5) default '0',
  `data1` text default NULL,
  `autopayment` smallint(1) NOT NULL default '0',
  `ccmonth` varchar(4) NOT NULL default '0',
  `ccyear` varchar(4) NOT NULL default '0',
  `cclastfour` varchar(5) NOT NULL default '',
  `data2` text default NULL,
  `data3` text default NULL,
  `passphrased` smallint(1) NOT NULL default '0',
  `updating` tinyint(4) NOT NULL default '0',
  `taxable` tinyint(4) NOT NULL default '0',
  `status` tinyint(4) NOT NULL default '0',
  `firstname` varchar(25) NOT NULL default '',
  `lastname` varchar(25) NOT NULL default '',
  `email` varchar(254) NOT NULL default '',
  `organization` varchar(60) NOT NULL default '',
  `usernotes` longtext default NULL,
  `usernotespos` varchar(75) NOT NULL default '',
  `warningmask` int(11) NOT NULL default '0',
  `balance` DECIMAL(25,3) DEFAULT '0.00' NOT NULL,
  `chatstatus` tinyint(4) NOT NULL default '0',
  `plus_score` INT NOT NULL DEFAULT  '0',
  `plus_data` TEXT NOT NULL,
  `plus_date` datetime default NULL,
  `profile_updated` TINYINT( 1 ) NOT NULL DEFAULT '1',
  `invoice_template` VARCHAR( 45 ) NOT NULL DEFAULT  '',
  PRIMARY KEY  (`id`)
) DEFAULT CHARACTER SET utf8, ENGINE=MyISAM AUTO_INCREMENT=101 ;
ALTER TABLE `users` ADD INDEX `loggedin` ( `loggedin` );
ALTER TABLE `users` ADD INDEX  `i_lastx` (  `lastseen` ,  `loggedin` ,  `groupid` );
ALTER TABLE `users` ADD INDEX ( `groupid` );
ALTER TABLE `users` ADD INDEX ( `status` );
ALTER TABLE `users` ADD INDEX ( `autopayment` );
ALTER TABLE `users` ADD INDEX ( `cclastfour` ) ;
ALTER TABLE  `users` ADD  `updated_at` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00';
ALTER TABLE  `users` ADD  `created_at` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00';

DROP TABLE IF EXISTS `companies`;
CREATE TABLE `companies` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `hashed_id` varchar(32) NOT NULL,
    `subdomain` varchar(20) NOT NULL,
    `status` tinyint(4) NOT NULL,
    `type` varchar(50),
    `limit_staff` tinyint(4) NOT NULL DEFAULT '-1',
    PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `productgroup_addon`;
CREATE TABLE `productgroup_addon` (
  `productgroup_id` int(11) NOT NULL,
  `addon_id` int(11) NOT NULL
) DEFAULT CHARACTER SET utf8, ENGINE=MyISAM;

DROP TABLE IF EXISTS `product_addon`;
CREATE TABLE `product_addon` (
  `product_id` int(11) NOT NULL,
  `addon_id` int(11) NOT NULL,
  `order` int(11) NOT NULL,
  `type` TINYINT(4) NOT NULL DEFAULT '0'
) DEFAULT CHARACTER SET utf8, ENGINE=MyISAM;

DROP TABLE IF EXISTS `active_orders`;
CREATE TABLE active_orders (
    `id` VARCHAR(13) NOT NULL DEFAULT '',
    `product_id` int(11) NOT NULL DEFAULT 0,
    `expires` datetime NOT NULL,
    PRIMARY KEY(`id`),
    INDEX  `prod` (  `product_id` )
) DEFAULT CHARACTER SET utf8, ENGINE=MyISAM;

DROP TABLE IF EXISTS `chatlog`;
CREATE TABLE `chatlog` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `roomid` varchar(20) NOT NULL,
  `chatterid` varchar(20) NOT NULL,
  `msg` text NOT NULL,
  `time` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) DEFAULT CHARACTER SET utf8, ENGINE=MyISAM;
CREATE INDEX IRoomTime ON chatlog (roomid, id);

DROP TABLE IF EXISTS `chatuser`;
CREATE TABLE `chatuser` (
  `chatterid` varchar(20) NOT NULL,
  `fullname` varchar(45) NOT NULL,
  `email` varchar(254) NOT NULL,
  `usertype` tinyint(4) NOT NULL,
  PRIMARY KEY (`chatterid`)
) DEFAULT CHARACTER SET utf8, ENGINE=MyISAM;

DROP TABLE IF EXISTS `chatroom`;
CREATE TABLE `chatroom` (
  `id` varchar(20) NOT NULL,
  `chatterid` varchar(20) NOT NULL,
  `title` varchar(45) NOT NULL,
  `time` int(11) NOT NULL,
  `ip` varchar(20) NOT NULL,
  `status` tinyint(4) NOT NULL DEFAULT '1',
  `description` varchar(200) NOT NULL DEFAULT '',
  `ispublic` INT NOT NULL DEFAULT  '0',
  UNIQUE KEY `room_chatter` (`id`,`chatterid`)
) DEFAULT CHARACTER SET utf8, ENGINE=MyISAM;

DROP TABLE IF EXISTS `chattyping`;
CREATE TABLE `chattyping` (
  `roomid` varchar(20) NOT NULL,
  `chatterid` varchar(20) NOT NULL,
  `time` int(11) NOT NULL,
  `subtype` int(11) NOT NULL,
  UNIQUE KEY `typing_index` (`roomid`,`chatterid`)
) DEFAULT CHARACTER SET utf8, ENGINE=MyISAM;

DROP TABLE IF EXISTS `chatvisitor`;
CREATE TABLE `chatvisitor` (
  `ip` varchar(40) NOT NULL,
  `lastvisit` int(11) NOT NULL,
  `chatterid` varchar(20) NOT NULL,
  `country` text CHARACTER SET utf8 NOT NULL,
  `lang` varchar(10) NOT NULL,
  `browser_name` varchar(10) NOT NULL,
  `browser_ver` varchar(10) NOT NULL,
  `device` varchar(7) NOT NULL,
  `os` varchar(17) NOT NULL,
  `search_engine` varchar(10) NOT NULL,
  `search_terms` varchar(56) NOT NULL,
  `ref_url` varchar(1024) NOT NULL,
  `ref_host` varchar(150) NOT NULL,
  `ref_path` varchar(25) NOT NULL,
  `url` varchar(1024) NOT NULL,
  `path` varchar(256) NOT NULL,
  `title` varchar(255) NOT NULL,
  `session` text CHARACTER SET utf8 NOT NULL,
  PRIMARY KEY  (`ip`),
  KEY `lastvisit` (`lastvisit`)
) DEFAULT CHARACTER SET utf8, ENGINE=MyISAM;

DROP TABLE IF EXISTS `translations`;
CREATE TABLE `translations` (
  `type` int(11) NOT NULL,
  `itemid` int(11) NOT NULL,
  `language` varchar(200) NOT NULL,
  `value` longtext NOT NULL,
  PRIMARY KEY ( `type` , `itemid` , `language` )
) DEFAULT CHARACTER SET utf8, ENGINE=MyISAM;

ALTER TABLE `translations` ADD FULLTEXT KEY `value` (`value`);

DROP TABLE IF EXISTS `prices`;
CREATE TABLE `prices` (
  `type` int(11) NOT NULL,
  `itemid` int(11) NOT NULL,
  `currency_abrv` varchar(4) NOT NULL,
  `pricing` text NOT NULL,
  PRIMARY KEY ( `type` , `itemid` , `currency_abrv` )
) DEFAULT CHARACTER SET utf8, ENGINE=MyISAM;