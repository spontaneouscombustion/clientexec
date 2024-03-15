# Delete non-existant users from user_groups table
DELETE user_groups FROM user_groups LEFT JOIN users ON ( user_groups.user_id = users.id ) WHERE users.id IS NULL;

# Show hosting account fields on sign up
UPDATE `customField` SET `inSettings` = '1', `fieldOrder` = '1', `isRequired` = '1', `inSignup` = '1' WHERE `name` = 'Domain Name' AND `groupId` = '2' AND `subGroupId` = '1'; 
UPDATE `customField` SET `inSettings` = '1', `fieldOrder` = '2', `isRequired` = '1', `inSignup` = '1'  WHERE `name` = 'User Name' AND `groupId` = '2' AND `subGroupId` = '1';
UPDATE `customField` SET `inSettings` = '1', `fieldOrder` = '3', `isRequired` = '1', `inSignup` = '1' WHERE `name` = 'Password' AND `groupId` = '2' AND `subGroupId` = '1';
