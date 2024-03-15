# ---------------------------------------------------------
# DROPS DEPRECATED FIELDS
# ---------------------------------------------------------
ALTER TABLE `domains` DROP `billsent`;


# #########################################

# ---------------------------------------------------------
# MIGRATING DOMAIN CUSTOMFIELDS
# ---------------------------------------------------------
INSERT INTO `customField` (`id`, `groupId`, `subGroupId`, `fieldType`, `name`, `isRequired`, `isChangeable`, `isAdminOnly`, `fieldOrder`, `showCustomer`, `showAdmin`, `dropDownOptions`, `inSettings`, `InSignup`) SELECT cdf.`id`, 1, 0, cdf.`type`, cdf.`name`, cdf.`isrequired`, cdf.`isChangable`, cdf.`isAdminOnly`, cdf.`myOrder`, cdf.`showcustomer`, cdf.`showadmin`, cdf.`dropdownoptions`, cdf.`inSettings`, cdf.`InSignup` FROM `customdomainfields` cdf;

INSERT INTO `object_customField` (`objectid`, `customFieldId`, `value`) SELECT dcdf.`userid`, dcdf.`customid`, dcdf.`value` FROM `domain_customdomainfields` dcdf;

DROP TABLE `customdomainfields`;

DROP TABLE `domain_customdomainfields`;
