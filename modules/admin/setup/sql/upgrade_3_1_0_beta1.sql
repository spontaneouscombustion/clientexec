# ---------------------------------------------------------
# REMOVED HARD-CODING OF GROUP NAMES
# ---------------------------------------------------------
CREATE TABLE `groups` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(25) NOT NULL,
  `isadmin` tinyint(4) NOT NULL default '1',
  `issuperadmin` tinyint(4) NOT NULL default '0',
  `description` text NOT NULL,
  `usedefaultcolor` tinyint(4) NOT NULL default '1',
  `groupcolor` varchar(7),
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM;

INSERT INTO `groups` (`id`, `name`, `isadmin`, `issuperadmin`, `description`) VALUES (1, 'Customer', 0, 0, '');
INSERT INTO `groups` (`id`, `name`, `isadmin`, `issuperadmin`, `description`) VALUES (2, 'Super Admin', 1, 1, '');
INSERT INTO `groups` (`id`, `name`, `isadmin`, `issuperadmin`, `description`) VALUES (3, 'Support Staff', 1, 0, '');
INSERT INTO `groups` (`id`, `name`, `isadmin`, `issuperadmin`, `description`) VALUES (4, 'Billing', 1, 0, '');
INSERT INTO `groups` (`id`, `name`, `isadmin`, `issuperadmin`, `description`) VALUES (5, 'Support Manager', 1, 0, '');

# ---------------------------------------------------------
# ALLOW TLDS TO ASSOCIATE WITH A REGISTRAR PLUGIN
# ---------------------------------------------------------
ALTER TABLE `tld` ADD COLUMN `plugin` varchar(25) default '';

# ---------------------------------------------------------
# ALLOW PACKAGE ADDONS TO BE SET AS TAXABLE OR NONTAXABLE
# ---------------------------------------------------------
ALTER TABLE `packageaddon` ADD `taxable` TINYINT NOT NULL DEFAULT '0';

# ----------------------------------------------------------------------------------
# CURRENCY CAN DEFINE WHERE TO LOCATE THE SYMBOL AND THE QUANTITY OF DECIMAL PLACES
# ----------------------------------------------------------------------------------
ALTER TABLE `currency` ADD `alignment` varchar(10) NOT NULL default 'left',
 ADD `precision` int(1) NOT NULL default '2';
 
# ---------------------------------------------------------
# Table structure for table `coupons_usage`
# ---------------------------------------------------------
CREATE TABLE `coupons_usage` (
  `invoiceentryid` INT(11) NOT NULL ,
  `couponid` INT(11) NOT NULL ,
  `isrecurring` tinyint(1) NOT NULL,
  PRIMARY KEY ( `invoiceentryid` , `couponid`, `isrecurring` )
) ENGINE=MYISAM ;

# ---------------------------------------------------------
# DEFINE IF THE COUPON IS ACTIVE OR ARCHIVED
# ---------------------------------------------------------
ALTER TABLE `coupons` ADD `coupons_archive` TINYINT( 1 ) DEFAULT '0' NOT NULL ;

# ---------------------------------------------------------
# CHANGING WORD TROUBLE FOR SUPPORT
# ---------------------------------------------------------

UPDATE `setting` SET `name` ='Support Ticket Start Number' WHERE `name` = 'Trouble Ticket Start Number';
UPDATE `setting` SET `name` ='E-mail For New Support Tickets' WHERE `name` = 'E-mail For New Trouble Tickets';
UPDATE `setting` SET `name` ='E-mail For New High Priority Support Tickets' WHERE `name` = 'E-mail For New High Priority Trouble Tickets';
UPDATE `setting` SET `name` ='Support Ticket Subject Template' WHERE `name` = 'Trouble Ticket Subject Template';
UPDATE `setting` SET `name` ='Support Ticket Template' WHERE `name` = 'Trouble Ticket Template';
UPDATE `report` SET `name` ='Support_Tickets_Opened-Support.php' WHERE `name` = 'Trouble_Tickets_Opened-Support.php';

# ---------------------------------------------------------
# TYPO IN FORGOT PASSWORD TEMPLATE
# ---------------------------------------------------------
UPDATE `setting` SET `value` = '[CLIENTNAME],\r\n\r\nA web user from [REQUESTIP] has just requested to change the password on this account.\r\n\r\nBy clicking on the confirmation link below you will be sent a new password and are recommended to change it after logging in.\r\n\r\nConfirmation URL: [CONFIRMATION URL]\r\n\r\nThank you,\r\n[COMPANYNAME]' WHERE `name` = 'Forgot Password Template' AND `value` = '[CLIENTNAME],\r\n\r\nA web user from [REQUESTIP] has just requested to change the password on this account.\r\n\r\nBy clicking on the confimation link below you will be sent a new password and are recommended to change it after logging in.\r\n\r\nConfirmation URL: [CONFIRMATION URL]\r\n\r\nThank you,\r\n[COMPANYNAME]';
UPDATE `setting` SET `value_alternate` = '[CLIENTNAME],<br />\r\n<br />\r\nA web user from [REQUESTIP] has just requested to change the password on this account.<br />\r\n<br />\r\nBy clicking on the confirmation link below you will be sent a new password and are recommended to change it after logging in.<br />\r\n<br />\r\nConfirmation URL: [CONFIRMATION URL]<br />\r\n<br />\r\nThank you,<br />\r\n[COMPANYNAME]' WHERE `name` = 'Forgot Password Template' AND `value_alternate` = '[CLIENTNAME],<br />\r\n<br />\r\nA web user from [REQUESTIP] has just requested to change the password on this account.<br />\r\n<br />\r\nBy clicking on the confimation link below you will be sent a new password and are recommended to change it after logging in.<br />\r\n<br />\r\nConfirmation URL: [CONFIRMATION URL]<br />\r\n<br />\r\nThank you,<br />\r\n[COMPANYNAME]';

# ---------------------------------------------------------
# REMOVED NO LONGER USED TABLE.
# Wasn't created anymore since a few versions ago, so it might
# or not exist.
# ---------------------------------------------------------
DROP TABLE IF EXISTS `module_group`;

# ---------------------------------------------------------
# ADD 24 MONTH BILLING (2 YEARS)
# --------------------------------------------------------
ALTER TABLE `package` ADD `price24` FLOAT NOT NULL DEFAULT '0' AFTER `price12` ;
ALTER TABLE `package` ADD `price24included` TINYINT( 4 ) NOT NULL DEFAULT '1' AFTER `price12included` ;
ALTER TABLE `packageaddon_prices` ADD `price24` FLOAT NOT NULL DEFAULT '-1',
ADD `price24_force` TINYINT( 4 ) NOT NULL DEFAULT '0';
UPDATE `package` SET `price24included`='0';

# ---------------------------------------------------------
# ABILITY TO SET RENEWAL PRICE
# --------------------------------------------------------
ALTER TABLE `tld` ADD `renewal` FLOAT NOT NULL DEFAULT '9.95' AFTER `price` ;

# --------------------------------------------------------------------------------------------------
# REMOVED "UNSIGNED" ATTRIBUTE TO groupid FIELD IN users TABLE TO ALLOW FOR THE GUEST GROUP (-1)
# --------------------------------------------------------------------------------------------------
ALTER TABLE `users` CHANGE `groupid` `groupid` INT( 10 ) NULL DEFAULT '1';

# --------------------------------------------------------------------
# ADDED FIELD TO DETERMINE TO WHAT A PERCENTUAL COUPON APPLIES TO
# --------------------------------------------------------------------
ALTER TABLE `coupons` ADD `coupons_applicable_to` TINYINT NOT NULL DEFAULT '127' AFTER `coupons_type` ;

# --------------------------------------------------------------------
# LET RECURRINGFEE TABLE HANDLE PERCENTUAL COUPONS
# --------------------------------------------------------------------
ALTER TABLE `recurringfee` ADD `amount_percent` FLOAT NOT NULL DEFAULT '0' AFTER `amount` ;
ALTER TABLE `recurringfee` ADD `coupon_applicable_to` TINYINT NOT NULL DEFAULT '0' AFTER `appliestoid` ;
