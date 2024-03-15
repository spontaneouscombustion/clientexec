ALTER TABLE `customuserfields` ADD `company_id` INT(11) NOT NULL DEFAULT '0' AFTER `id`;
ALTER TABLE `customuserfields` ADD INDEX `i_company_id` (`company_id`);
DELETE FROM `user_customuserfields` WHERE `customid` IN (SELECT `id` FROM `customuserfields` WHERE `name` LIKE 'SidebarState');
DELETE FROM `customuserfields` WHERE `name` LIKE 'SidebarState';
ALTER TABLE `customField` CHANGE `desc` `desc` VARCHAR( 250 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '';
ALTER TABLE `customuserfields` CHANGE `desc` `desc` VARCHAR( 250 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '';
INSERT INTO `customuserfields` (`id`, `name`, `type`, `isrequired`, `isChangable`, `isAdminOnly`, `width`, `myOrder`, `showcustomer`, `showadmin`, `InSignup`, `inSettings`, `dropdownoptions`) VALUES (NULL, 'Ticket_Sidebar_PendingItemsToggle', 51, 0, 0, 0, 20, 0, 0, 0, 0, 0, '');
DELETE from user_customuserfields where customid IN (select id from customuserfields where name = 'Ticket_Sidebar_DepartmentToggle');