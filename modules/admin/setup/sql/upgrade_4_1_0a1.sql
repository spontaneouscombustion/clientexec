ALTER TABLE `setting` ADD UNIQUE (`id`);
ALTER TABLE `setting` DROP PRIMARY KEY;
ALTER TABLE `setting` DROP INDEX `id_2`;
ALTER TABLE `setting` ADD `issession` TINYINT NOT NULL DEFAULT '1';
UPDATE `setting` SET `issession` = 0 WHERE `type` = 6;

# TC URL
INSERT INTO `setting` VALUES (NULL, 'Terms and Conditions URL', '', '', 'Enter a URL to link users to a remote T&Cs page during signup. Note this URL takes precedence of anything entered below.', 10, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1);

CREATE TABLE `calendar` (
    `id` INT NOT NULL AUTO_INCREMENT ,
    `title` VARCHAR( 256 ) NOT NULL ,
    `allday` TINYINT NOT NULL DEFAULT '0',
    `start` TIMESTAMP NOT NULL ,
    `end` TIMESTAMP NOT NULL ,
    `url` TEXT NOT NULL ,
    `className` VARCHAR( 56 ) NOT NULL ,
    `appliesto` INT NOT NULL DEFAULT '0',
    `description` TEXT NOT NULL ,
    `userid` INT NOT NULL DEFAULT '0',
    `isprivate` TINYINT NOT NULL DEFAULT '0',
    `isrepeating` TINYINT NOT NULL DEFAULT '0',
    PRIMARY KEY ( `id` )
) DEFAULT CHARACTER SET utf8, ENGINE=MYISAM ;

# Adding the ReCaptcha Settings
INSERT INTO `setting` VALUES (NULL, 'ReCaptcha Private Key', '', '', '', 1, 1, 1, 0, 0, 0, 3, 0, 0, 0, 0, 0);
INSERT INTO `setting` VALUES (NULL, 'ReCaptcha Public Key', '', '', '', 1, 1, 1, 0, 0, 0, 3, 0, 0, 0, 0, 0);
INSERT INTO `setting` VALUES (NULL, 'Require Access Code', '0', '', '', 8, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0);

# Deleted permissions that is not used any longer - woohoooo
Delete  FROM `permissions` WHERE `permission` = 'home_view_snapshots';
Delete  FROM `permissions` WHERE `permission` = 'reports_view_dashboard_graphs';
Delete  FROM `permissions` WHERE `permission` = 'reports_export_data';
DELETE FROM `permissions` WHERE `permission` = 'clients_activate';
DELETE FROM `permissions` WHERE `permission` = 'clients_edit_isorganization';
DELETE FROM `permissions` WHERE `permission` = 'admin_view_services_status';

ALTER TABLE  `report` DROP  `dashboard`;

# Delete all report entries .. customer will have to reset permissions for staff
TRUNCATE TABLE  `report`;

# Increased description size for invoices and invoice entries, to avoid truncating them
ALTER TABLE `invoice` CHANGE `description` `description` VARCHAR( 200 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '';
ALTER TABLE `invoiceentry` CHANGE `description` `description` VARCHAR( 200 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '';

# add an index to events for subject.. there are a few where clauses that could take advantage of this
ALTER TABLE `events_log` ADD INDEX `subject` ( `subject` );
ALTER TABLE `events_log` ADD INDEX `iDate` ( `date` );

# adding setting to store teamstatus API information
INSERT INTO `setting` VALUES (NULL , 'TeamStatusAPIInfo', '', '', 'API information for team status to send to CampFire or HipChat', 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0);

# delete unused settings
DELETE FROM `setting` WHERE `name` LIKE 'Enable Pay Invoice';
DELETE FROM `setting` WHERE `name` LIKE 'Number of days to show unread articles';
DELETE FROM `setting` WHERE `name` LIKE 'Disable CE Tickets Support System';
DELETE FROM `setting` WHERE `name` LIKE '3rd Party Support URL';
DELETE FROM `setting` WHERE `name` LIKE 'Signup Completion Template';
DELETE FROM `setting` WHERE `name` LIKE 'Signup Rejection Template';

# update recurringfee table
UPDATE `recurringfee` SET `recurring` = 0 WHERE `recurring` IS NULL;
ALTER TABLE `recurringfee` CHANGE `recurring` `recurring` TINYINT( 4 ) NOT NULL DEFAULT 0;

# Remove old signup setting & add new welcome email setting
INSERT INTO `setting` VALUES(NULL, 'Send Account Welcome E-mail', '0', '', '', 10, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1);
DELETE FROM `setting` WHERE `name` LIKE 'Show Menu On Signup';

# Add account welcome email
INSERT INTO `autoresponders` VALUES(NULL, 8, 'Account Creation', 'Account Created', '[CLIENTNAME],\n\nThanks for choosing [COMPANYNAME], you have successfully created an account with us. Please keep this email as a reference of your login information.\n\nUsername: [CLIENTEMAIL]\n\nTo login to your account, please visit:\n[CLIENTAPPLICATIONURL]\n\nMany Thanks,\n[COMPANYNAME]\n', '<div><font face="''Lucida Grande'', ''Lucida Sans Unicode'', Verdana, Arial, sans-serif"><span style="font-size: 12px;">[CLIENTNAME],</span></font></div><div><font face="''Lucida Grande'', ''Lucida Sans Unicode'', Verdana, Arial, sans-serif"><span style="font-size: 12px;"><br></span></font></div><div><font face="''Lucida Grande'', ''Lucida Sans Unicode'', Verdana, Arial, sans-serif"><span style="font-size: 12px;">Thanks for choosing [COMPANYNAME], you have successfully created an account with us. Please keep this email as a reference of your login information.</span></font></div><div><font face="''Lucida Grande'', ''Lucida Sans Unicode'', Verdana, Arial, sans-serif"><span style="font-size: 12px;"><br></span></font></div><div><font face="''Lucida Grande'', ''Lucida Sans Unicode'', Verdana, Arial, sans-serif"><span style="font-size: 12px;">Username: [CLIENTEMAIL]</span></font></div><div><font face="''Lucida Grande'', ''Lucida Sans Unicode'', Verdana, Arial, sans-serif"><span style="font-size: 12px;"><br></span></font></div><div><font face="''Lucida Grande'', ''Lucida Sans Unicode'', Verdana, Arial, sans-serif"><span style="font-size: 12px;">To login to your account, please visit:</span></font></div><div><font face="''Lucida Grande'', ''Lucida Sans Unicode'', Verdana, Arial, sans-serif"><span style="font-size: 12px;">[CLIENTAPPLICATIONURL]</span></font></div><div><font face="''Lucida Grande'', ''Lucida Sans Unicode'', Verdana, Arial, sans-serif"><span style="font-size: 12px;"><br></span></font></div><div><font face="''Lucida Grande'', ''Lucida Sans Unicode'', Verdana, Arial, sans-serif"><span style="font-size: 12px;">Many Thanks,</span></font></div><div><font face="''Lucida Grande'', ''Lucida Sans Unicode'', Verdana, Arial, sans-serif"><span style="font-size: 12px;">[COMPANYNAME]</span></font></div><div style="color: rgb(0, 0, 0); font-family: ''Lucida Grande'', ''Lucida Sans Unicode'', Verdana, Arial, sans-serif; font-size: 12px; "><br></div>', 0, 'This email is sent to customers when they create an account in the system, generally during signup.');

DELETE FROM `setting` WHERE `name` LIKE '%Viewable in the Public Section%';
DELETE FROM `setting` WHERE `name` = 'Show Context Help When Available';

# Fix some old settings that should not be part of session settings
update setting set issession=0;
Update setting set issession=1 where name='Template';
Update setting set issession=1 where name='Date Format';
Update setting set issession=1 where name='Default Country Name';
Update setting set issession=1 where name='Default Country Name 2';
Update setting set issession=1 where name='Show Last Users Online';
Update setting set issession=1 where name='Company Name';
Update setting set issession=1 where name='Language';
Update setting set issession=1 where name='Login Disabled';
Update setting set issession=1 where name='Show Execution Time';
Update setting set issession=1 where name='Default Currency';
Update setting set issession=1 where name='license';
Update setting set issession=1 where name='Default Country Currency2';
Update setting set issession=1 where name='snapinsList';
Update setting set issession=1 where name='services_meta_info';
Update setting set issession=1 where name='Path to cURL';

delete from setting where name like 'plugin_idevaffiliate_%';
delete from setting where name like 'plugin_jrox_%';