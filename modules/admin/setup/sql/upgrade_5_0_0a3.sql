ALTER TABLE `taxrule`
	DROP PRIMARY KEY,
	CHANGE `countrycode` `countryiso` VARCHAR(5) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT '' NOT NULL,
	ADD UNIQUE `country-state-level` (`countryiso`, `state`, `level`),
	ADD COLUMN `id` INT UNSIGNED NOT NULL AUTO_INCREMENT FIRST, ADD PRIMARY KEY(`id`);

ALTER TABLE `country`
	ADD COLUMN `division` varchar(50)  COLLATE utf8_general_ci NULL after `exists`,
	ADD COLUMN `division_plural` varchar(50)  COLLATE utf8_general_ci NULL after `division`;

UPDATE `country` SET `division` = 'Territory / Province', `division_plural` = 'Territories / Provinces' WHERE `id` IN (34);
UPDATE `country` SET `division` = 'State / Territory', `division_plural` = 'States / Territories' WHERE `id` IN (11, 87);
UPDATE `country` SET `division` = 'State', `division_plural` = 'States' WHERE `id` IN (12, 143, 117, 204, 149, 181, 72, 125, 208, 133);
UPDATE `country` SET `division` = 'Region / Governorate', `division_plural` = 'Regions / Governorates' WHERE `id` IN (147);
UPDATE `country` SET `division` = 'Region', `division_plural` = 'Regions' WHERE `id` IN (39, 53, 93, 153, 67, 63);
UPDATE `country` SET `division` = 'Province / Municipality', `division_plural` = 'Provinces / Municipalities' WHERE `id` IN (129);
UPDATE `country` SET `division` = 'Province', `division_plural` = 'Provinces' WHERE `id` IN (3, 137, 40, 57, 88, 178, 69, 89, 47, 56, 187, 6, 103, 150, 98, 30, 8);
UPDATE `country` SET `division` = 'Prefecture', `division_plural` = 'Prefectures' WHERE `id` IN (38, 37, 95);
UPDATE `country` SET `division` = 'Parish', `division_plural` = 'Parishes' WHERE `id` IN (7, 17, 76, 5, 162, 55, 164);
UPDATE `country` SET `division` = 'Municipality', `division_plural` = 'Municipalities' WHERE `id` IN (105, 109);
UPDATE `country` SET `division` = 'Governorate', `division_plural` = 'Governorates' WHERE `id` IN (58, 102, 106, 90, 186, 196);
UPDATE `country` SET `division` = 'District', `division_plural` = 'Districts' WHERE `id` IN (20, 112);
UPDATE `country` SET `division` = 'Department', `division_plural` = 'Departments' WHERE `id` IN (78, 48, 152, 82, 83, 42, 21, 59, 24);
UPDATE `country` SET `division` = 'County', `division_plural` = 'Counties' WHERE `id` IN (184, 159, 62, 146, 49, 85);
UPDATE `country` SET `division` = 'Canton', `division_plural` = 'Cantons' WHERE `id` IN (185);

DELETE from customuserfields where name = 'Ticket_Sidebar_PendingItemsToggle';
DELETE from customuserfields where name = 'Ticket_Sidebar_FilterToggle';
DELETE from customuserfields where name = 'Ticket_Sidebar_RecentToggle';

ALTER TABLE  `invoice` ADD  `note` TEXT NOT NULL;

# New field to store subscription id (mainly for PayPal subscriptions)
ALTER TABLE `invoice` ADD `subscription_id` TEXT NOT NULL AFTER `note` ;

DROP TABLE `escalationrules`;
DROP TABLE  `escalationrules_departments`;
DROP TABLE  `troubleticket_escalated`;
DROP TABLE  `conversation` ,`conversation_messages` ,`conversation_participants` ;
UPDATE invoicetransaction SET response = REPLACE(response,'paid partially','Partially paid') WHERE response like '%paid partially%';