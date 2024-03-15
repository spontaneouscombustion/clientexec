ALTER TABLE `customField` CHANGE `desc` `desc` VARCHAR( 250 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '';
ALTER TABLE `customuserfields` CHANGE `desc` `desc` VARCHAR( 250 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '';

# Add Domain custom field for expiration date.
INSERT INTO `customField` (`id` ,`groupId` ,`subGroupId` ,`fieldType` ,`name` ,`isRequired` ,`isChangeable` ,`isAdminOnly` ,`fieldOrder` ,`showCustomer` ,`showAdmin` ,`dropDownOptions` ,`inSettings` ,`InSignup`) VALUES (NULL , '2', '3', '0', 'Expiration Date', '0', '0', '0', '0', '0', '0', '', '0', '0');
# Add Domain custom field for transfer status.
INSERT INTO `customField` (`id` ,`groupId` ,`subGroupId` ,`fieldType` ,`name` ,`isRequired` ,`isChangeable` ,`isAdminOnly` ,`fieldOrder` ,`showCustomer` ,`showAdmin` ,`dropDownOptions` ,`inSettings` ,`InSignup`) VALUES (NULL , '2', '3', '0', 'Transfer Status', '0', '0', '0', '0', '0', '0', '', '0', '0');