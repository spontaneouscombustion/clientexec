# --------------------------------------------------------

#
# Table structure for table `conversation`
#

CREATE TABLE `conversation` (
  `id` int(11) NOT NULL auto_increment,
  `start_time` datetime NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM;

# --------------------------------------------------------

#
# Table structure for table `conversation_messages`
#

CREATE TABLE `conversation_messages` (
  `id` int(11) NOT NULL auto_increment,
  `conversation_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `sent_time` datetime NOT NULL,
  `message` mediumtext NOT NULL,
  `event` int(11) default NULL,
  KEY `id` (`id`),
  KEY `conversation_id` (`conversation_id`,`user_id`)
) ENGINE=MyISAM;

# --------------------------------------------------------

#
# Table structure for table `conversation_participants`
#

CREATE TABLE `conversation_participants` (
  `conversation_id` int(11) NOT NULL auto_increment,
  `user_id` int(11) NOT NULL,
  `read_time` datetime NOT NULL,
  `active` tinyint(4) NOT NULL,
  `typing` tinyint(4) NOT NULL,
  `color` varchar(25) NOT NULL DEFAULT 'black',
  PRIMARY KEY  (`conversation_id`,`user_id`)
) ENGINE=MyISAM;


# --------------------------------------------------------
# ADDED CUSTOM FIELD TO STORE DASHBOARD STATE
# --------------------------------------------------------
INSERT INTO `customuserfields` VALUES (NULL, 'DashboardState', 48, 0, 1, 1, 20, 0, 0, 0, 0, 0, '');
INSERT INTO `customuserfields` VALUES (NULL, 'SidebarState', 53, 0, 1, 1, 20, 0, 0, 0, 0, 0, '');

# --------------------------------------------------------
# ADDED CUSTOM FIELD TO the state of the user profile
# --------------------------------------------------------
INSERT INTO `customuserfields` VALUES (NULL , 'Profile_ShowProfile', 49, 0, 0, 0, 20, 0, 0, 0, 0, 0, '');

# --------------------------------------------------------
# ADDED CUSTOM FIELD TO the state of the user profile
# --------------------------------------------------------
INSERT INTO `customuserfields` VALUES (NULL , 'Ticket_ShowHeader', 50, 0, 0, 0, 20, 0, 0, 0, 0, 0, '');

# --------------------------------------------------------
# Remove customfields that are not needed any longer
# --------------------------------------------------------
DELETE FROM user_customuserfields WHERE customid in (select c.id FROM customuserfields c where c.name='ViewSideBar');
DELETE FROM customuserfields WHERE name='ViewSideBar';
DELETE FROM user_customuserfields WHERE customid in (select c.id FROM customuserfields c where c.name='TABLESORT_snapshot_invoices');
DELETE FROM customuserfields WHERE name='TABLESORT_snapshot_invoices';
DELETE FROM user_customuserfields WHERE customid in (select c.id FROM customuserfields c where c.name='TABLESORT_snapshot_cc');
DELETE FROM customuserfields WHERE name='TABLESORT_snapshot_cc';
DELETE FROM user_customuserfields WHERE customid in (select c.id FROM customuserfields c where c.name='TABLESORT_snapshot_tickets');
DELETE FROM customuserfields WHERE name='TABLESORT_snapshot_tickets';
DELETE FROM user_customuserfields WHERE customid in (select c.id FROM customuserfields c where c.name='TABLESORT_snapshot_pending');
DELETE FROM customuserfields WHERE name='TABLESORT_snapshot_pending';
DELETE FROM user_customuserfields WHERE customid in (select c.id FROM customuserfields c where c.name='TABLESORT_snapshot_uninvoiced');
DELETE FROM customuserfields WHERE name='TABLESORT_snapshot_uninvoiced';
DELETE FROM user_customuserfields WHERE customid in (select c.id FROM customuserfields c where c.name='DASHBOARD_INVOICEFILTER');
DELETE FROM customuserfields WHERE name='DASHBOARD_INVOICEFILTER';
DELETE FROM user_customuserfields WHERE customid in (select c.id FROM customuserfields c where c.name='TABLESORT_snapshot_event_log');
DELETE FROM customuserfields WHERE name='TABLESORT_snapshot_event_log';
DELETE FROM user_customuserfields WHERE customid in (select c.id FROM customuserfields c where c.name='DASHBOARD_LASTUSEDSNAPSHOT');
DELETE FROM customuserfields WHERE name='DASHBOARD_LASTUSEDSNAPSHOT';

# --------------------------------------------------------
# ADDED CUSTOM FIELD TO the state of the user profile
# --------------------------------------------------------
INSERT INTO `customuserfields` VALUES (NULL , 'Ticket_Sidebar_DepartmentToggle', 51, 0, 0, 0, 20, 0, 0, 0, 0, 0, '');

# --------------------------------------------------------
# ADDED CUSTOM FIELD TO the state of the user profile
# --------------------------------------------------------
INSERT INTO `customuserfields` VALUES (NULL , 'Ticket_Sidebar_FilterToggle', 52, 0, 0, 0, 20, 0, 0, 0, 0, 0, '');

# --------------------------------------------------------
# ALTERED THE domains TABLE to use DATETIME rather than DATE to save a more accurate time
# --------------------------------------------------------
ALTER TABLE  `domains` CHANGE  `dateActivated`  `dateActivated` DATETIME NULL DEFAULT NULL;

# --------------------------------------------------------
# UPDATED CUSTOM FIELDS "COUNTRY" AND "STATE" TO AVOID ERASE OR CHANGE THEIR TYPES
# --------------------------------------------------------
UPDATE `customuserfields` SET `isChangable` = '2' WHERE `type` = 6;
UPDATE `customuserfields` SET `isChangable` = '2' WHERE `type` = 4;
