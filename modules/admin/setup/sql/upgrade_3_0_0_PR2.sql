# --------------------------------------------------------
# ALTER INVOICEENTRY TABLE TO RELATE ENTRIES TO RECURRINGFEES
# --------------------------------------------------------
ALTER TABLE `invoiceentry` ADD `recurringappliesto` INT( 11 ) DEFAULT '0' AFTER `recurring` ;

# --------------------------------------------------------
# ADMIN SUMMARY CACHE IS CAUSING ERRORS AFTER UPGRADE
# --------------------------------------------------------
UPDATE `cache` SET `content`='' WHERE `cachekey`='ADMINSUMMARY';

# --------------------------------------------------------
# SHELL SERVER PLUGIN IS NOW NAMED SKELETON
# --------------------------------------------------------
UPDATE package_variable SET varname = 'plugin_skeleton' WHERE varname = 'plugin_shell';
UPDATE server SET plugin = 'skeleton' WHERE plugin = 'shell';

# --------------------------------------------------------
# ADDED PACKAGE ADDON DESCRIPTION FIELD
# --------------------------------------------------------
ALTER TABLE `packageaddon` CHANGE `description` `name` VARCHAR( 50 ) NOT NULL ;
ALTER TABLE `packageaddon` ADD `description` TEXT NOT NULL AFTER `name`;

# --------------------------------------------------------
# HTML/PLAINTEXT E-MAILS RELATED CHANGES
# --------------------------------------------------------
ALTER TABLE `package` ADD `welcomeemail_html` TEXT NOT NULL AFTER `welcomeemail` ;
ALTER TABLE `package` CHANGE `welcomeemail` `welcomeemail_text` TEXT NOT NULL ;
ALTER TABLE `promotion` CHANGE `welcomeemail` `welcomeemail_text` TEXT NOT NULL ;
ALTER TABLE `email_queue` DROP `striptags`;
ALTER TABLE `email_queue` CHANGE `nl2br` `contenttype` TINYINT NOT NULL DEFAULT '0';
ALTER TABLE `users` CHANGE `signature` `signature_text` TEXT DEFAULT NULL;
ALTER TABLE `users` ADD `signature_html` TEXT DEFAULT NULL AFTER `signature_text` ;

# --------------------------------------------------------
# REMOVED THE NO LONGER USED PAYSYSTEMS GATEWAY PLUGIN
# --------------------------------------------------------
DELETE FROM `setting` WHERE name LIKE 'plugin_paysystems%';

# --------------------------------------------------------
# ALLOW SETTINGS TO STORE PLAINTEXT AND HTML VALUES
# --------------------------------------------------------
ALTER TABLE `setting` ADD `value_alternate` TEXT AFTER `value` ;
