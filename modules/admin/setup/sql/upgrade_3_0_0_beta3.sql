# --------------------------------------------------------
# CREATE TABLE FOR STORING THE PACKAGE CUSTOM FIELD EXCLUSIONS
# --------------------------------------------------------

CREATE TABLE `promotion_customdomainfields` (
`promotionid` INT NOT NULL ,
`customid` INT NOT NULL
) ENGINE = MYISAM ;


# --------------------------------------------------------
# CONVERT MAXMIND SETTINGS TO REAL PLUGIN SETTINGS
# AND MOVE TELEPHONE VERIFICATION TO A SEPARATE PLUGIN SECTION
# --------------------------------------------------------
UPDATE `setting` SET `name` = 'plugin_maxmind_Enabled', `plugin` = 1 WHERE name = 'Enable MaxMind Fraud Control';
UPDATE `setting` SET `name` = 'plugin_maxmind_MaxMind License Key', `plugin` = 1 WHERE name = 'MaxMind License Key';
UPDATE `setting` SET `name` = 'plugin_maxmind_Reject Free E-mail Service', `plugin` = 1 WHERE name = 'Reject Free E-mail Service';
UPDATE `setting` SET `name` = 'plugin_maxmind_Reject Country Mismatch', `plugin` = 1 WHERE name = 'Reject Country Mismatch';
UPDATE `setting` SET `name` = 'plugin_maxmind_Reject Anonymous Proxy', `plugin` = 1 WHERE name = 'Reject Anonymous Proxy';
UPDATE `setting` SET `name` = 'plugin_maxmind_Reject High Risk Country', `plugin` = 1 WHERE name = 'Reject High Risk Country';
UPDATE `setting` SET `name` = 'plugin_maxmind_MaxMind Fraud Risk Score', `plugin` = 1 WHERE name = 'MaxMind Fraud Risk Score';
UPDATE `setting` SET `name` = 'plugin_maxmindphone_Enabled', `description`= 'This setting will\r\nenable the Maxmind telephone verification plugin on signup for new\r\ncustomers. (Phone credits are bought separate from regular credit card\r\nfraud detection services)<br><a\r\nhref=http://www.maxmind.com/app/telephone_buynow?rId=clientexec>http://www.maxmind.com/app/telephone_buynow</a>', `plugin` = 1 WHERE name = 'Enable MaxMind Telephone Verification';
UPDATE `setting` SET `name` = 'plugin_maxmindphone_Minimum Bill Amount to Trigger Telephone Verification', `plugin` = 1 WHERE name = 'Minimum Bill Amount to Trigger Telephone Verification';
UPDATE `setting` SET `name` = 'plugin_maxmind_Show MaxMind Logo', `plugin` = 1 WHERE name = 'Show MaxMind Logo';
UPDATE `setting` SET `name` = 'plugin_maxmindphone_Minimum Fraud Score to Trigger Telephone Verification', `plugin` = 1 WHERE name = 'Minimum Fraud Score to Trigger Telephone Verification';

# --------------------------------------------------------
# NEW SETTINGS FOR MAXMIND LOW REMAINING QUERIES NOTIFICATIONS
# --------------------------------------------------------

INSERT INTO `setting` (`id`, `name`, `value`, `description`, `type`, `isrequired`, `istruefalse`, `istextarea`, `isfromoptions`, `myorder`, `helpid`, `plugin`, `ispassword`, `ishidden`) VALUES (NULL, 'plugin_maxmind_MaxMind Warning E-mail', '', 'The E-mail address where a notification will be sent when the number of remaining queries reaches your MaxMind Low Query Threshold', 12, 0, 0, 0, 0, 0, 0, 1, 0, 0);
INSERT INTO `setting` (`id`, `name`, `value`, `description`, `type`, `isrequired`, `istruefalse`, `istextarea`, `isfromoptions`, `myorder`, `helpid`, `plugin`, `ispassword`, `ishidden`) VALUES (NULL, 'plugin_maxmind_MaxMind Low Query Threshold', '10', 'A notification E-mail will be sent when the number of remaining queries reaches this value.', 12, 0, 0, 0, 0, 0, 0, 1, 0, 0);
