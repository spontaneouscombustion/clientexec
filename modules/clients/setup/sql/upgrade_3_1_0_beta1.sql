# ---------------------------------------------------------
# ALLOW PACKAGE PRICES TO BE OVERRIDDEN
# ---------------------------------------------------------
ALTER TABLE `domains` ADD `custom_price` FLOAT NOT NULL DEFAULT '0',
ADD `use_custom_price` TINYINT NOT NULL DEFAULT '0';

# ---------------------------------------------------------
# CUSTOM TAG TO KNOW WHAT MENU IS THE USER USING
# ---------------------------------------------------------
INSERT INTO `customuserfields` ( `id` , `name` , `type` , `isrequired` , `isChangable` , `isAdminOnly` , `width` , `myOrder` , `showcustomer` , `showadmin` , `InSignup` , `inSettings` , `dropdownoptions` ) VALUES (NULL , 'ViewMenu', '42', '0', '1', '0', '20', '0', '0', '0', '0', '0', '');

# ---------------------------------------------------------
# CUSTOM TAG STORING LAST TIME FEEDBACK E-MAIL WAS SENT
# ---------------------------------------------------------
INSERT INTO `customuserfields` (`id` ,`name`, `type`, `isrequired`, `isChangable`, `isAdminOnly`, `width`, `myOrder`, `showcustomer`, `showadmin`, `InSignup`, `inSettings`, `dropdownoptions`) VALUES (NULL, 'LastSentFeedbackEmail', '43', '0', '1', '0', '20', '0', '0', '0', '0', '0', '');

# ---------------------------------------------------------
# NEW PERMISSIONS
# USE REPLACE ON SOME THAT WERE ADDED IN A PAST UPGRADE
# BUT WERE FORGOTTEN TO BE ADDED TO DATA.SQL
# ---------------------------------------------------------
REPLACE INTO `permissions` (`subject_id`, `is_group`, `permission`, `target_id`) VALUES (1, 1, 'clients_edit_isorganization', 0);
REPLACE INTO `permissions` (`subject_id`, `is_group`, `permission`, `target_id`) VALUES (4, 1, 'clients_edit_isorganization', 0);
REPLACE INTO `permissions` (`subject_id`, `is_group`, `permission`, `target_id`) VALUES (5, 1, 'clients_edit_isorganization', 0);
INSERT INTO `permissions` (`subject_id`, `is_group`, `permission`, `target_id`) VALUES (3, 1, 'clients_view_eventlog', 0);
INSERT INTO `permissions` (`subject_id`, `is_group`, `permission`, `target_id`) VALUES (4, 1, 'clients_view_eventlog', 0);
INSERT INTO `permissions` (`subject_id`, `is_group`, `permission`, `target_id`) VALUES (5, 1, 'clients_view_eventlog', 0);
INSERT INTO `permissions` (`subject_id`, `is_group`, `permission`, `target_id`) VALUES (3, 1, 'clients_add_notes', 0);
INSERT INTO `permissions` (`subject_id`, `is_group`, `permission`, `target_id`) VALUES (4, 1, 'clients_add_notes', 0);
INSERT INTO `permissions` (`subject_id`, `is_group`, `permission`, `target_id`) VALUES (5, 1, 'clients_add_notes', 0);
INSERT INTO `permissions` (`subject_id`, `is_group`, `permission`, `target_id`) VALUES (3, 1, 'clients_delete_notes', 0);
INSERT INTO `permissions` (`subject_id`, `is_group`, `permission`, `target_id`) VALUES (4, 1, 'clients_delete_notes', 0);
INSERT INTO `permissions` (`subject_id`, `is_group`, `permission`, `target_id`) VALUES (5, 1, 'clients_delete_notes', 0);
INSERT INTO `permissions` (`subject_id`, `is_group`, `permission`, `target_id`) VALUES (4, 1, 'clients_passphrase_cc', 0);

# ---------------------------------------------------------
# clients_notes AND clients_notes_tickettypes TABLES
# ---------------------------------------------------------
CREATE TABLE `clients_notes` (
    `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
    `date` datetime NOT NULL,
    `target_id` INT NOT NULL ,
    `is_target_group` tinyint(4) NOT NULL default '0',
    `admin_id` int(11) NOT NULL,
    `note` TEXT NOT NULL ,
    `visible_client` TINYINT NOT NULL
) ENGINE = MYISAM;

CREATE TABLE `clients_notes_tickettypes` (
  `note_id` int(11) NOT NULL,
  `tickettype_id` int(11) NOT NULL,
  PRIMARY KEY  (`note_id`,`tickettype_id`)
) ENGINE=MyISAM;

# ---------------------------------------------------------
# TO STORE ORDERING OF EVENT LOG SNAPSHOT
# ---------------------------------------------------------
INSERT INTO `customuserfields` VALUES (NULL, 'TABLESORT_snapshot_event_log', 44, 0, 0, 0, 20, 0, 0, 0, 0, 0, '');

# ---------------------------------------------------------
# Update width for customfield address due to latest style change
# ---------------------------------------------------------
UPDATE customuserfields set width="40" where id=3;


# --------------------------------------------------------
# TO STORE DOMAIN FILTER
# --------------------------------------------------------
INSERT INTO `customuserfields` VALUES (NULL, 'DASHBOARD_DOMAINFILTER',45,0,0,0,20,0,0,0,0,0,'');