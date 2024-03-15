# --------------------------------------------------------
# VERSION UPDATING
# --------------------------------------------------------
UPDATE `setting` set value='2.8.2' WHERE name='ClientExec Version';

# --------------------------------------------------------
# ENLARGE RECURRING FIELD
# --------------------------------------------------------
ALTER TABLE `domains` CHANGE `recurring` `recurring` TINYINT( 4 ) NULL DEFAULT NULL;

# --------------------------------------------------------
# UPDATE PLESK PACKAGE VARS FOR WEBSTATS
# --------------------------------------------------------
UPDATE `package_variable` SET value='none' WHERE varname='plugin_plesk_package_vars_webstat' AND value='0';
UPDATE `package_variable` SET value='webalizer' WHERE varname='plugin_plesk_package_vars_webstat' AND value='1';
