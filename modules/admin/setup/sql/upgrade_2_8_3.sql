# --------------------------------------------------------
# VERSION UPDATING
# --------------------------------------------------------
UPDATE `setting` set value='2.8.3' WHERE name='ClientExec Version';

# --------------------------------------------------------
# TYPOS
# --------------------------------------------------------
UPDATE `setting` SET description = 'E-mail addresses to CC notification messages for new high priority tickets', name = 'E-mail For New High Priority Trouble Tickets' WHERE name = 'Email For New High Priority Trouble Tickets';
UPDATE `setting` SET description = 'This is the E-mail address that is visible to users after receiving an invoice payment request by E-mail.', name = 'Billing E-mail' WHERE name = 'Billing Email';
UPDATE `setting` SET description = 'Please enter the absolute path to cURL on your system.<br>NOTE: Leaving this blank will instruct CE to assume you have PHP compiled with libCurl.', name = 'Path to cURL' WHERE name = 'Path to Curl';
UPDATE `setting` SET description = 'E-mail address you want all support questions sent to.  This E-mail address is used as an entry to some template based E-mails, such as the welcome E-mail template.', name = 'Support E-mail' WHERE name = 'Support Email';
UPDATE `setting` SET description = 'E-mail addresses ClientExec will send E-mail notification on all new trouble tickets. You can separate E-mails by a comma.<br>Example: support1@domain.com, support2@domain.com', name = 'E-mail For New Trouble Tickets' WHERE name = 'Email For New Trouble Tickets';
UPDATE `setting` SET description = 'E-mail addresses ClientExec will send E-mail notifications to after all new signups. You can separate E-mails by a comma.<br>Example: support1@domain.com, support2@domain.com', name = 'E-mail For New Signups' WHERE name = 'Email For New Signups';
UPDATE `setting` SET description = 'Setting allows you to reject any order using free E-mail services like Hotmail and Yahoo (free E-mail = higher risk).<br><b>NOTE: </b>Requires MaxMind', name = 'Reject Free E-mail Service' WHERE name = 'Reject Free Email Service';

# --------------------------------------------------------
# UPGRADING OF SERVER STATUS SERVICE
# --------------------------------------------------------
UPDATE `setting` SET name = 'plugin_serverstatus_1 Min. Load Average', description = 'Add 1 Minute server load average threshold you want this service to E-mail if passed.<br/>Ex: 1.5, will E-mail when load goes over 1.5' WHERE name = 'plugin_serverstatus_Server Load';

# --------------------------------------------------------
# ABILITY TO MARK ALTERNATE E-MAILS AS FOR SUPPORT PIPING
# --------------------------------------------------------
ALTER TABLE `altuseremail` ADD `support` TINYINT NOT NULL DEFAULT '0';
