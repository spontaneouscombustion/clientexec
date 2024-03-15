# Add generic custom field to store last status date
ALTER TABLE `customField` CHANGE `desc` `desc` VARCHAR( 250 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '';
ALTER TABLE `customuserfields` CHANGE `desc` `desc` VARCHAR( 250 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '';
INSERT INTO `customField` (`id` ,`groupId` ,`subGroupId` ,`fieldType` ,`name` ,`isRequired` ,`isChangeable` ,`isAdminOnly` ,`fieldOrder` ,`showCustomer` ,`showAdmin` ,`dropDownOptions` ,`inSettings` ,`InSignup`) VALUES (NULL , '2', '0', '0', 'Last Status Date', '0', '0', '0', '0', '0', '0', '', '0', '0');
INSERT INTO `customuserfields` (`id` ,`name` ,`type` ,`isrequired` ,`isChangable` ,`isAdminOnly` ,`width` ,`myOrder` ,`showcustomer` ,`showadmin` ,`InSignup` ,`inSettings` ,`dropdownoptions`) VALUES (NULL , 'Last Status Date', '52', '0', '0', '0', '20', '0', '0', '0', '0', '0', '');
