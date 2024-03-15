# --------------------------------------------------------
# adding custom fields for the recent ticket list to support toggle and saving the list
# --------------------------------------------------------
INSERT INTO `customuserfields` (`id`, `name`, `type`, `isrequired`, `isChangable`, `isAdminOnly`, `width`, `myOrder`, `showcustomer`, `showadmin`, `InSignup`, `inSettings`, `dropdownoptions`) VALUES (NULL , 'Ticket_Sidebar_RecentToggle', 59, 0, 0, 0, 20, 0, 0, 0, 0, 0, '');
INSERT INTO `customuserfields` (`id`, `name`, `type`, `isrequired`, `isChangable`, `isAdminOnly`, `width`, `myOrder`, `showcustomer`, `showadmin`, `InSignup`, `inSettings`, `dropdownoptions`) VALUES (NULL , 'Ticket_Sidebar_RecentList', 60, 0, 0, 0, 20, 0, 0, 0, 0, 0, '');

# Removed default value for lastview to avoid errors in strict mode
ALTER TABLE  `users` ADD  `lastview` TEXT default NULL;

# ---------------------------------------------------------
# Adding date stamps to various fields for the at a glance plugin
# ---------------------------------------------------------
ALTER TABLE `invoice` ADD `datecreated` DATE default NULL AFTER `archive`;
ALTER TABLE `users_domains` ADD `date_added` DATE default NULL;

DELETE FROM domains where Plan=0 AND promotionid = 0;

ALTER TABLE `customField` ADD `partofproductidentifier` TINYINT( 1 ) NOT NULL DEFAULT '0';

ALTER TABLE `package` ADD `bundledProducts` TEXT NOT NULL;

# Had to add another field 
ALTER TABLE `users` ADD `loggedin` SMALLINT( 1 ) NOT NULL DEFAULT '0';
ALTER TABLE `users` ADD INDEX `loggedin` ( `loggedin` );

# Description for custom fields
ALTER TABLE `customField` ADD `desc` VARCHAR( 250 ) NOT NULL DEFAULT '';
ALTER TABLE `customuserfields` ADD `desc` VARCHAR( 250 ) NOT NULL DEFAULT '';

# Show hosting account fields on sign up
UPDATE `customField` SET `inSettings` = '1', `fieldOrder` = '1', `isRequired` = '1', `inSignup` = '1' WHERE `name` = 'Domain Name' AND `groupId` = '2' AND `subGroupId` = '1'; 
UPDATE `customField` SET `inSettings` = '1', `fieldOrder` = '2', `isRequired` = '1', `inSignup` = '1'  WHERE `name` = 'User Name' AND `groupId` = '2' AND `subGroupId` = '1';
UPDATE `customField` SET `inSettings` = '1', `fieldOrder` = '3', `isRequired` = '1', `inSignup` = '1' WHERE `name` = 'Password' AND `groupId` = '2' AND `subGroupId` = '1';

# New setting to validate VAT numbers
INSERT INTO `customuserfields` (`id`, `name`, `type`, `isrequired`, `isChangable`, `isAdminOnly`, `width`, `myOrder`, `showcustomer`, `showadmin`, `InSignup`, `inSettings`, `dropdownoptions`) VALUES (NULL, 'VAT Validation', 51, 0, 0, 0, 20, 0, 0, 0, 0, 0, '');

# Update the descriptions of custom fields
UPDATE customuserfields SET `desc` = 'When entering a phone number be sure to include the leading "+" sign followed by the country code.' WHERE `name` = 'Phone';
UPDATE customuserfields SET `desc` = 'Select the box and enter a name to create this account as an organization instead of an individual.' WHERE `name` = 'Organization';
UPDATE customuserfields SET `desc` = 'We urge you to subscribe to our email announcements however you may opt-out by selecting NO.' WHERE `name` = 'Receive Email Announcements';

