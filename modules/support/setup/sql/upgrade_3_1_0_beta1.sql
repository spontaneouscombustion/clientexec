# ---------------------------------------------------------
# CLEAN UP ORPHANED TICKETS (left-over from earlier version)
# ---------------------------------------------------------
DELETE FROM troubleticket WHERE userid=0;

# ---------------------------------------------------------
# TICKET RATING
# ---------------------------------------------------------
ALTER TABLE `troubleticket` ADD `rate_hash` char(32) default NULL AFTER `lastlog_datetime`;
ALTER TABLE `troubleticket` ADD `rate` tinyint(4) NOT NULL default '0' AFTER `rate_hash`;

# ---------------------------------------------------------
# REFER TO TROUBLETICKET TYPES BY THEIR ID
# ---------------------------------------------------------
ALTER TABLE `troubleticket_type` DROP PRIMARY KEY;
ALTER TABLE `troubleticket_type` ADD `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST ;
ALTER TABLE `troubleticket_type` ADD `enabled` TINYINT NOT NULL default '1' AFTER `myorder`;
ALTER TABLE `troubleticket_type` ADD `is_billing_type` TINYINT DEFAULT '0' NOT NULL AFTER `enabled` ;
ALTER TABLE `troubleticket_type` ADD `description` text NOT NULL AFTER `name`;

# ---------------------------------------------------------
# DEFAULT ASSIGNATION AND MESSAGE COPYING
# ---------------------------------------------------------
ALTER TABLE `troubleticket_type` ADD `target_dept` int(11) NOT NULL AFTER `enabled`;
ALTER TABLE `troubleticket_type` ADD `target_staff` int(11) NOT NULL AFTER target_dept;

# ---------------------------------------------------------
# DOMAIN
# ---------------------------------------------------------
ALTER TABLE `troubleticket` ADD `domainid` int(11) NULL default '0' AFTER `userid`;

# ---------------------------------------------------------------------
# ADDED PERMISSIONS
# ---------------------------------------------------------------------
INSERT INTO `permissions` (`subject_id`, `is_group`, `permission`, `target_id`) VALUES (-1, 1, 'support_view', 0);
INSERT INTO `permissions` (`subject_id`, `is_group`, `permission`, `target_id`) VALUES (-1, 1, 'support_submit_ticket', 0);

INSERT INTO `permissions` (`subject_id`, `is_group`, `permission`, `target_id`) VALUES (1, 1, 'support_view_eventlog', 0);

INSERT INTO `permissions` (`subject_id`, `is_group`, `permission`, `target_id`) VALUES (3, 1, 'support_view_eventlog', 0);
INSERT INTO `permissions` (`subject_id`, `is_group`, `permission`, `target_id`) VALUES (5, 1, 'support_view_rates', 0);
INSERT INTO `permissions` (`subject_id`, `is_group`, `permission`, `target_id`) VALUES (5, 1, 'support_view_eventlog', 0);

# ---------------------------------------------------------
# SERVICE RATING
# ---------------------------------------------------------
ALTER TABLE `troubleticket` ADD `feedback` TEXT;
 
# ---------------------------------------------------------
# AUTO CLOSE SERVICE
# ---------------------------------------------------------
ALTER TABLE `troubleticket` ADD `autoclose` INT( 2 ) DEFAULT '0' NOT NULL ;

# ---------------------------------------------------------
# TROUBLETICKET FILTERS
# ---------------------------------------------------------
CREATE TABLE `troubleticket_filters` (
  `id` int(11) NOT NULL auto_increment,
  `user_id` int(10) NOT NULL default '0',
  `private` int(2) NOT NULL default '0',
  `temp` tinyint(4) NOT NULL,
  `name` varchar(50) NOT NULL,
  `sql` text NOT NULL,
  `lastlog_datetime` datetime NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM;

CREATE TABLE `departments` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(50) NOT NULL,
  `is_generaldep` tinyint(4) NOT NULL default '0',
  `lead_id` int(11) NOT NULL,
  `assign_to_lead` tinyint(4) NOT NULL default '0',
  `notify_lead` tinyint(4) NOT NULL default '1',
  `sendclosed` int(2) NOT NULL default '1',
  `sendfeedback` int(2) NOT NULL default '1',
  `sendresolution` int(2) NOT NULL default '1',
  `notification_list` text NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM;

CREATE TABLE `departments_members` (
  `department_id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  `is_group` tinyint(4) NOT NULL
) ENGINE=MyISAM;

# departments 1 and 2 and its leader are set in the upgrade php script.
INSERT INTO `departments_members` (`department_id`, `member_id`, `is_group`) VALUES (1, 2, 1);
INSERT INTO `departments_members` (`department_id`, `member_id`, `is_group`) VALUES (1, 3, 1);
INSERT INTO `departments_members` (`department_id`, `member_id`, `is_group`) VALUES (1, 4, 1);
INSERT INTO `departments_members` (`department_id`, `member_id`, `is_group`) VALUES (1, 5, 1);
INSERT INTO `departments_members` (`department_id`, `member_id`, `is_group`) VALUES (2, 4, 1);

# ---------------------------------------------------------------------
# ASSIGN ALL TICKETS TO DEPARTMENT GENERAL
# ---------------------------------------------------------------------
ALTER TABLE `troubleticket` ADD `assignedtodeptid` INT NOT NULL DEFAULT '0' AFTER `assignedtoid` ;
UPDATE `troubleticket` SET `assignedtodeptid` = 1;

CREATE TABLE `escalationrules_departments` (
    `escalationrule_id` INT NOT NULL ,
    `department_id` INT NOT NULL ,
    PRIMARY KEY ( `escalationrule_id` , `department_id` )
) ENGINE=MYISAM ;

CREATE TABLE `autoresponders` (
    `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
    `name` VARCHAR( 50 ) NOT NULL ,
    `subject` VARCHAR( 100 ) NOT NULL ,
    `contents` TEXT NOT NULL,
    `contents_html` TEXT NOT NULL
) ENGINE = MYISAM ;

CREATE TABLE `escalationrules` (
    `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
    `name` VARCHAR( 50 ) NOT NULL ,
    `time_elapsed` INT NOT NULL ,
    `ticket_status` TINYINT NOT NULL ,
    `ticket_priority` TINYINT NOT NULL ,
	`ticket_tag` varchar(10) NOT NULL,
    `reassign_dept` INT NOT NULL ,
    `reassign_staff` INT NOT NULL ,
    `new_priority` TINYINT NOT NULL ,
    `new_tag` varchar(10) NOT NULL,
    `only_apply_once` TINYINT NOT NULL ,
    `transcription_destinataries` TEXT NOT NULL
) ENGINE=MYISAM ;

CREATE TABLE `troubleticket_escalated` (
    `ticket_id` INT NOT NULL ,
    `escalationrule_id` INT NOT NULL ,
    PRIMARY KEY ( `ticket_id` , `escalationrule_id` )
) ENGINE = MYISAM ;

CREATE TABLE `routingrules` (
    `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
    `name` VARCHAR( 100 ) NOT NULL ,
    `order` int(11) NOT NULL default '0',
    `emails` TEXT NOT NULL ,
    `user_type` TINYINT NOT NULL ,
    `autoresponder_template` INT NOT NULL ,
    `openticket` tinyint(4) NOT NULL default '0',
    `target_type` INT NOT NULL ,
    `target_priority` INT NOT NULL ,
    `target_dept` INT NOT NULL ,
    `target_staff` INT NOT NULL ,
    `copy_destinataries` TEXT NOT NULL ,
    `routing_type` TINYINT NOT NULL ,
    `pop3_hostname` varchar(50) NOT NULL,
    `pop3_port` varchar(4) NOT NULL,
    `pop3_username` VARCHAR( 50 ) NOT NULL ,
    `pop3_password` VARCHAR( 50 ) NOT NULL ,
    `pop3_delete_emails` tinyint(4) NOT NULL
) ENGINE=MYISAM ;

CREATE TABLE `routingrules_groups` (
  `routingrule_id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  PRIMARY KEY  (`routingrule_id`,`group_id`)
) ENGINE=MyISAM;

# ---------------------------------------------------------
# TICKETLOG PRIVATE
# ---------------------------------------------------------
ALTER TABLE `troubleticket_log` ADD `private` INT( 2 ) DEFAULT '0' NOT NULL ;

# ---------------------------------------------------------
# TICKET TAG FIELD
# ---------------------------------------------------------
ALTER TABLE `troubleticket` ADD `tag` varchar(10) NOT NULL DEFAULT 'clear';

