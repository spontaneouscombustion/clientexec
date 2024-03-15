# ---------------------------------------------------------
# STORE THE LAST TIME A USER LOGGED IN FOR ONLINE STATUS
#----------------------------------------------------------
ALTER TABLE `users` ADD `lastlogin` DATETIME NULL DEFAULT NULL AFTER `signature_html` ;

# ---------------------------------------------------------
# TAX NAME, LEVEL, AND COMPOUND PROPERTIES
# ---------------------------------------------------------
ALTER TABLE `taxrule` ADD `name` VARCHAR( 20 ) NOT NULL DEFAULT '';
ALTER TABLE `taxrule` ADD `level` TINYINT( 4 ) NOT NULL DEFAULT '1';
ALTER TABLE `taxrule` ADD `compound` TINYINT( 4 ) NOT NULL DEFAULT '0';
ALTER TABLE `taxrule` DROP PRIMARY KEY, ADD PRIMARY KEY (`countrycode`,`state`,`level`);
UPDATE `taxrule` SET `name` = 'Tax' WHERE `name` = '';

# ---------------------------------------------------------
# STORE THE USER CHAT STATUS
#----------------------------------------------------------
ALTER TABLE `users` ADD `chatstatus` TINYINT( 4) NOT NULL DEFAULT 0;

# ---------------------------------------------------------
# TABLE STRUCTURE FOR TABLE `TEAM_STATUS`
# ---------------------------------------------------------
CREATE TABLE `team_status` (
  `id` int(11) NOT NULL auto_increment,
  `userid` int(11) NOT NULL,
  `userstatus` text NOT NULL,
  `status_datetime` datetime NOT NULL,
  `replyid` int(11),
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM;

# ---------------------------------------------------------
# TABLE STRUCTURE FOR TABLE `TEAM_STATUS_FOLLOW`
# ---------------------------------------------------------
CREATE TABLE `team_status_follow` (
  `userid` int(11) NOT NULL,
  `groupid` int(11) NOT NULL,
  UNIQUE(`userid`,`groupid`)
) ENGINE=MyISAM;

# -----------------------------------------------------------
# Update style settings to general
# -----------------------------------------------------------
UPDATE setting set type=1 where type=2;

# -----------------------------------------------------------
# Remove unneeded settings
# -----------------------------------------------------------
DELETE FROM `setting` WHERE `name` LIKE '%Recommend Us%';

# ----------------------------------------------------------------------------------
# Currency can define its rate based on the default currency
# ----------------------------------------------------------------------------------
ALTER TABLE `currency` ADD `rate` float NOT NULL default '1';

# ----------------------------------------------------------------------------------
# Currency can be enabled or disabled, instead of erased
# ----------------------------------------------------------------------------------
ALTER TABLE `currency` ADD `enabled` tinyint(4) NOT NULL default '1';

# ----------------------------------------------------------------------------------
# New setting for the selected currency plugin
# ----------------------------------------------------------------------------------
INSERT INTO `setting` (`id`, `name`, `value`, `value_alternate`, `description`, `type`, `isrequired`, `istruefalse`, `istextarea`, `issmalltextarea`, `isfromoptions`, `myorder`, `helpid`, `plugin`, `ispassword`, `ishidden`) VALUES (NULL, 'Selected Currency Plugin', 'europeancentralbank', '', '', 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0);

# ----------------------------------------------------------------------------------
# New table for packages that can have free domains
# ----------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `package_tld` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `package_id` int(11) unsigned NOT NULL DEFAULT '0',
  `tld_id` int(11) unsigned NOT NULL DEFAULT '0',
  `period` int(3) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
);

# ----------------------------------------------------------------------------------
# New table for storing custom user data for plugins
# ----------------------------------------------------------------------------------
CREATE TABLE  `plugin_custom_data` (
 `name` VARCHAR( 50 ) NOT NULL ,
 `value` TEXT NULL ,
 `plugin_name` VARCHAR( 25 ) NOT NULL ,
 `plugin_type` VARCHAR( 25 ) NOT NULL ,
 `user_id` INT NOT NULL ,
PRIMARY KEY (  `name` ,  `plugin_name` ,  `plugin_type` ,  `user_id` )
);

# ----------------------------------------------------------------------------------
# Drop useless table column
# ----------------------------------------------------------------------------------
ALTER TABLE  `promotion` DROP  `code`;
ALTER TABLE `promotion` ADD `emailid` INT NOT NULL DEFAULT '0';

# -----------------------------------------------------------
# Remove unneeded settings
# -----------------------------------------------------------
DELETE FROM `setting` WHERE `name` LIKE '%PopUpBackColor%';
DELETE FROM `setting` WHERE `name` LIKE '%PopUpTextColor%';
DELETE FROM `setting` WHERE `name` LIKE '%PopUpBorderColor%';
DELETE FROM `setting` WHERE `name` LIKE '%Use Effects%';
DELETE FROM setting WHERE name = 'View Customer DropDown';
DELETE FROM setting WHERE name = 'Show Open Support Tickets';
DELETE FROM setting WHERE name = 'Show Users and Packages Awaiting Activation';
DELETE FROM setting WHERE name = 'Show Domains';
DELETE FROM setting WHERE name = 'Show Articles';
DELETE FROM setting WHERE name = 'Show Outstanding Invoices';
DELETE FROM setting WHERE name = 'Show Credit Cards Needing Validation';
DELETE FROM setting WHERE name = 'Highlight Selected Column';
DELETE FROM setting WHERE name = 'Show Uninvoiced Work';
DELETE FROM setting WHERE name = 'Show Event Log';
DELETE FROM setting WHERE name = 'Number of displayed announcements';
DELETE FROM setting WHERE name = 'Display Tickets Ascending';

# -----------------------------------------------------------
# Remove unneeded permissions
# -----------------------------------------------------------
DELETE FROM `permissions` where permission = 'billing_edit_billing_types';

# -----------------------------------------------------------
# Add column to package table (no longer set showinsignup = -1 "hidden" for this)
# Send Welcome E-mail when activating a new customer
# -----------------------------------------------------------
ALTER TABLE `package` ADD `allowdirectlink` TINYINT NOT NULL DEFAULT '1';
ALTER TABLE `package` ADD `sendwelcome` TINYINT(4) NOT NULL DEFAULT '1';

# -----------------------------------------------------------
# Create productgroup_addon as link between addons and product groups
# -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `productgroup_addon` (
  `productgroup_id` int(11) NOT NULL,
  `addon_id` int(11) NOT NULL
);

# -----------------------------------------------------------
# Create product_addon as link between addons and producs
# -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `product_addon` (
  `product_id` int(11) NOT NULL,
  `addon_id` int(11) NOT NULL,
  `order` int(11) NOT NULL
);

# -----------------------------------------------------------------
# -----  Drop table that is not used any longer
# -----------------------------------------------------------------
drop table if exists turns_schedule;

# Increase help title size to avoid errors in strict mode when inserting some of our helps
ALTER TABLE `help` CHANGE `title` `title` VARCHAR( 100 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;

# -----------------------------------------------------------------
# -----  Add additional help sections that are migrated from old email settings
# -----------------------------------------------------------------
# --  from emailtemplatrsbilling_settings.php
INSERT INTO `help` (`id`, `title`, `detail`, `linkwords`, `width`, `height`) VALUES ('50', 'Invoice Template Tags', '[<font class=bodyhighlight>DATE</font>] Date payment is due.<br>[<font class=bodyhighlight>SENTDATE</font>] Date invoice was last sent.<br>[<font class=bodyhighlight>CLIENTNAME</font>] <br>[<font class=bodyhighlight>CLIENTEMAIL</font>] <br>[<font class=bodyhighlight>INVOICENUMBER</font>]<br>[<font class=bodyhighlight>INVOICEDESCRIPTION</font>]<br>[<font class=bodyhighlight>TAX</font>]<br>[<font class=bodyhighlight>AMOUNT_EX_TAX</font>] The total price excluding taxes.<br>[<font class=bodyhighlight>AMOUNT</font>] The total price due.<br>[<font class=bodyhighlight>PAID</font>] The amount already paid of the invoice.<br>[<font class=bodyhighlight>BALANCEDUE</font>] The balance due of the invoice.<br>[<font class=bodyhighlight>RAW_AMOUNT</font>] The total price excluding currency symbol.<br>[<font class=bodyhighlight>CLIENTAPPLICATIONURL</font>] URL to ClientExec.<br>[<font class=bodyhighlight>FORGOTPASSWORDURL</font>] URL to retrieve forgotten password.<BR>[<font class=bodyhighlight>COMPANYNAME</font>] <BR>[<font class=bodyhighlight>COMPANYADDRESS</font>] <BR>[<font class=bodyhighlight>BILLINGEMAIL</font>] E-mail address for billing inquiries<br>[<font class=bodyhighlight>CUSTOMPROFILE_xxxx</font>]<br>where xxx is a profile custom field name', 'Click to view available tags', '240', '320');
INSERT INTO `help` (`id`, `title`, `detail`, `linkwords`, `width`, `height`) VALUES ('51', 'Expiring CC Template', '[<font class=bodyhighlight>CLIENTNAME</font>] <br>[<font class=bodyhighlight>CLIENTEMAIL</font>] <br>[<font class=bodyhighlight>CCLASTFOUR</font>] The last four digits of the customer\'s credit card.<br>[<font class=bodyhighlight>CCEXPDATE</font>] The expiration date of the customer\'s credit card.<br>[<font class=bodyhighlight>CLIENTAPPLICATIONURL</font>] URL to Client Exec.<br>[<font class=bodyhighlight>FORGOTPASSWORDURL</font>] URL to retrieve forgotten password.<BR>[<font class=bodyhighlight>COMPANYNAME</font>] <BR>[<font class=bodyhighlight>COMPANYADDRESS</font>] <BR>[<font class=bodyhighlight>BILLINGEMAIL</font>] Email address for billing inquiries<br>[<font class=bodyhighlight>CUSTOMPROFILE_xxxx</font>]<br>where xxx is a profile custom field name', 'Click to view available tags', '240', '320');
INSERT INTO `help` (`id`, `title`, `detail`, `linkwords`, `width`, `height`) VALUES ('52', 'Domain Reminder Tags', '[<font class=bodyhighlight>CLIENTNAME</font>]<br>[<font class=bodyhighlight>CLIENTEMAIL</font>] <br>[<font class=bodyhighlight>DOMAIN</font>] <br> The client domain name.<br>[<font class=bodyhighlight>EXPDATE</font>] <br> The expiry date of the domain.<br>[<font class=bodyhighlight>COMPANYNAME</font>] <br>[<font class=bodyhighlight>COMPANYADDRESS</font>] <br>[<font class=bodyhighlight>FORGOTPASSWORDURL</font>] <br> URL to retrieve forgotten password.<br>[<font class=bodyhighlight>BILLINGEMAIL</font>] <br> Email address for billing inquiries<br>[<font class=bodyhighlight>CLIENTAPPLICATIONURL</font>] URL to Client Exec.<br>[<font class=bodyhighlight>CUSTOMPROFILE_xxxx</font>]<br>where xxx is a profile custom field name.<br>', 'Click to view available tags', '240', '320');
INSERT INTO `help` (`id`, `title`, `detail`, `linkwords`, `width`, `height`) VALUES ('53', 'Reset Password Tags', '[<font class=bodyhighlight>CLIENTNAME</font>] <br>[<font class=bodyhighlight>CLIENTEMAIL</font>] <br>[<font class=bodyhighlight>COMPANYNAME</font>]<br>[<font class=bodyhighlight>COMPANYADDRESS</font>]<br>[<font class=bodyhighlight>REQUESTIP</font>] IP of the machine which requested the password change.<br>[<font class=bodyhighlight>CONFIRMATION URL</font>] URL that user must press to confirm the password change<br>[<font class=bodyhighlight>CUSTOMPROFILE_xxxx</font>]<br>where xxx is a profile custom field name', 'Click to view available tags', '240', '320');
INSERT INTO `help` (`id`, `title`, `detail`, `linkwords`, `width`, `height`) VALUES ('54', 'Get New Password Tags', '[<font class=bodyhighlight>CLIENTNAME</font>] <br>[<font class=bodyhighlight>CLIENTEMAIL</font>] <br>[<font class=bodyhighlight>NEWPASSWORD</font>] <br>[<font class=bodyhighlight>CLIENTEXEC URL</font>] <br>[<font class=bodyhighlight>COMPANYNAME</font>] <br>[<font class=bodyhighlight>COMPANYADDRESS</font>] <br>[<font class=bodyhighlight>COMPANYEMAIL</font>]', 'Click to view available tags', '240', '320');
INSERT INTO `help` (`id`, `title`, `detail`, `linkwords`, `width`, `height`) VALUES ('55', 'Rejection E-Mail Tags', '[<font class=bodyhighlight>CLIENTNAME</font>] <br>[<font class=bodyhighlight>COMPANYNAME</font>] <br>[<font class=bodyhighlight>COMPANYADDRESS</font>] <br>[<font class=bodyhighlight>COMPANYURL</font>]<br> URL to your web site<br>[<font class=bodyhighlight>SUPPORTEMAIL</font>]<br> E-mail to support staff<br>[<font class=bodyhighlight>CLIENTAPPLICATIONURL</font>]<br> URL to ClientExec.', 'Click to view available tags', '240', '320');
INSERT INTO `help` (`id`, `title`, `detail`, `linkwords`, `width`, `height`) VALUES ('56', 'Team Status Reply E-Mail Tags', "[<font class=bodyhighlight>TEAMSTATUS</font>]<br> Team status used to reply<br>[<font class=bodyhighlight>REPLIEDTEAMSTATUSUSERNAME</font>]<br> Name of the user who's team status was replied<br>[<font class=bodyhighlight>REPLIEDTEAMSTATUSDATE</font>]<br> Date of the team status replied<br>[<font class=bodyhighlight>REPLIEDTEAMSTATUS</font>]<br> Team status replied<br>", 'Click to view available tags', '240', '320');
INSERT INTO `help` (`id`, `title`, `detail`, `linkwords`, `width`, `height`) VALUES ('57', 'Team Status Activity E-Mail Tags', "[<font class=bodyhighlight>TEAMSTATUSDYNAMICBLOCK</font>]<br> Use for add the information of the Team Status Activity Dynamic Block Template<br>", 'Click to view available tags', '240', '320');
INSERT INTO `help` (`id`, `title`, `detail`, `linkwords`, `width`, `height`) VALUES ('58', 'Team Status Dynamic Block Tags', "[<font class=bodyhighlight>TEAMSTATUSUSERNAME</font>]<br> Name of the user who post the team status<br>[<font class=bodyhighlight>TEAMSTATUS</font>]<br> Team status<br>[<font class=bodyhighlight>TEAMSTATUSDATE</font>]<br> Date of the team status<br>[<font class=bodyhighlight>TEAMSTATUSREPLYINFO</font>]<br> Use for add the information of the Team Status Activity Reply Template<br>", 'Click to view available tags', '240', '320');
INSERT INTO `help` (`id`, `title`, `detail`, `linkwords`, `width`, `height`) VALUES ('59', 'Team Status Activity Reply Tags', "[<font class=bodyhighlight>REPLIEDTEAMSTATUSUSERNAME</font>]<br> Name of the user who's team status was replied<br>", 'Click to view available tags', '240', '320');
INSERT INTO `help` (`id`, `title`, `detail`, `linkwords`, `width`, `height`) VALUES ('60', 'Ticket Template Tags', '[<font class=bodyhighlight>CLIENTNAME</font>] <br>[<font class=bodyhighlight>CLIENTEMAIL</font>] <br>[<font class=bodyhighlight>TICKETNUMBER</font>]<br>[<font class=bodyhighlight>TICKETSUBJECT</font>]<br>[<font class=bodyhighlight>DESCRIPTION</font>]<br>[<font class=bodyhighlight>CLIENTAPPLICATIONURL</font>] URL to ClientExec.<BR>[<font class=bodyhighlight>COMPANYNAME</font>] <br>[<font class=bodyhighlight>COMPANYADDRESS</font>] <br>[<font class=bodyhighlight>CUSTOMPROFILE_xxxx</font>]<br>where xxx is a profile custom field name', 'Click to view available tags', '240', '320');
INSERT INTO `help` (`id`, `title`, `detail`, `linkwords`, `width`, `height`) VALUES ('61', 'Feedback Template Tags', '[<font class=bodyhighlight>CLIENTNAME</font>] <br>[<font class=bodyhighlight>CLIENTEMAIL</font>] <br>[<font class=bodyhighlight>COMPANYNAME</font>]<br>[<font class=bodyhighlight>COMPANYADDRESS</font>]<br>[<font class=bodyhighlight>RATEEXCELLENTSERVICEURL</font>]<br />[<font class=bodyhighlight>RATEGOODSERVICEURL</font>]<br />[<font class=bodyhighlight>RATENOTGREATSERVICEURL</font>]<br />[<font class=bodyhighlight>RATEPOORSERVICEURL</font>]<br />[<font class=bodyhighlight>CUSTOMPROFILE_xxxx</font>]<br>where xxx is a profile custom field name', 'Click to view available tags', '240', '320');
INSERT INTO `help` (`id`, `title`, `detail`, `linkwords`, `width`, `height`) VALUES ('62', 'AutoClose Service Template Tags', '[<font class=bodyhighlight>CLIENTNAME</font>] <br>[<font class=bodyhighlight>CLIENTEMAIL</font>] <br>[<font class=bodyhighlight>COMPANYNAME</font>]<br>[<font class=bodyhighlight>COMPANYADDRESS</font>]<br>[<font class=bodyhighlight>TICKETNUMBER</font>]<br />[<font class=bodyhighlight>CUSTOMPROFILE_xxxx</font>]<br>where xxx is a profile custom field name', 'Click to view available tags', '240', '320');
INSERT INTO `help` (`id`, `title`, `detail`, `linkwords`, `width`, `height`) VALUES ('63', 'Notify Support For New High Priority Ticket Template Tags', '[<font class=bodyhighlight>CLIENTNAME</font>] <br>[<font class=bodyhighlight>CLIENTEMAIL</font>] <br>[<font class=bodyhighlight>COMPANYNAME</font>] <br>[<font class=bodyhighlight>COMPANYADDRESS</font>] <br>[<font class=bodyhighlight>TICKETNUMBER</font>] <br>[<font class=bodyhighlight>TICKETSUBJECT</font>] <br>[<font class=bodyhighlight>TICKETTYPE</font>] <br>[<font class=bodyhighlight>DESCRIPTION</font>] <br>[<font class=bodyhighlight>CLIENTAPPLICATIONURL</font>]', 'Click to view available tags', '240', '320');
INSERT INTO `help` (`id`, `title`, `detail`, `linkwords`, `width`, `height`) VALUES ('64', 'Notify Customer For New Ticket Template Tags', '[<font class=bodyhighlight>CLIENTNAME</font>] <br>[<font class=bodyhighlight>TICKETNUMBER</font>] <br>[<font class=bodyhighlight>TICKETSUBJECT</font>] <br>[<font class=bodyhighlight>TICKETTYPE</font>] <br>[<font class=bodyhighlight>DESCRIPTION</font>]<br>[<font class=bodyhighlight>COMPANYNAME</font>] <br>[<font class=bodyhighlight>COMPANYADDRESS</font>] <br>', 'Click to view available tags', '240', '320');
INSERT INTO `help` (`id`, `title`, `detail`, `linkwords`, `width`, `height`) VALUES ('65', 'Notify Assignee For Ticket Reply Template Tags', '[<font class=bodyhighlight>CLIENTNAME</font>] <br>[<font class=bodyhighlight>TICKETNUMBER</font>] <br>[<font class=bodyhighlight>DESCRIPTION</font>]', 'Click to view available tags', '240', '320');
INSERT INTO `help` (`id`, `title`, `detail`, `linkwords`, `width`, `height`) VALUES ('66', 'Notify For New FeedBack Template Tags', '[<font class=bodyhighlight>CLIENTNAME</font>] <br>[<font class=bodyhighlight>TICKETNUMBER</font>] <br>[<font class=bodyhighlight>DESCRIPTION</font>]', 'Click to view available tags', '240', '320');

# -----------
# Update Addons
# -----------
insert into product_addon (product_id,addon_id,`order`) select package_id,id,`order` from packageaddon;
insert into productgroup_addon (productgroup_id,addon_id) select p.planid,pa.id from packageaddon pa inner join package p on pa.package_id=p.id;
update packageaddon set package_id=0;

# ------------
# Update the template to default no matter what is stored
# ------------
Update setting set `value`='default' WHERE name = 'Template';