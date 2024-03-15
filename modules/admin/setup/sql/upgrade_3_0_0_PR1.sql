# --------------------------------------------------------
# ADDED TEMPLATE FOR WELCOME E-MAIL SUBJECT
# --------------------------------------------------------
ALTER TABLE `promotion` ADD `welcomeemail_subject` VARCHAR( 50 ) NOT NULL AFTER `welcomeemail` ;
ALTER TABLE `package` ADD `welcomeemail_subject` VARCHAR( 50 ) NOT NULL AFTER `welcomeemail` ;


# --------------------------------------------------------
# CHANGED SOME PLUGINS SETTINGS DESCRIPTIONS 
# --------------------------------------------------------
UPDATE `setting` SET description= 'Please enter the path to your Linkpoint Digital Certificate.' WHERE name='plugin_linkpoint_Cert';
UPDATE `setting` SET description= 'Enter on which number of days late to send an invoice reminder.  You can enter the numbers of days late separated by commas, a number followed by a + sign to indicate all invoices greater than or equal to this number of days or use * to send reminders each day. <br> Example: 1,5,10+ would send on one day late, five days late and ten or more days late.' WHERE name='plugin_rebiller_Days to trigger reminder';

# --------------------------------------------------------
# HIDDEN SETTING TO PREVENT AUTOREPLY LOOPS
# --------------------------------------------------------
INSERT INTO `setting` (`id`, `name`, `value`, `description`, `type`, `isrequired`, `istruefalse`, `istextarea`, `isfromoptions`, `myorder`, `helpid`, `plugin`, `ispassword`, `ishidden`) VALUES (NULL, 'E-mail Piping Last Five', '', 'Stores a serialized array of the last five E-mails addresses that have piped a reply into the system and the time that they were processed.', 8, 0, 0, 0, 0, 0, 0, 0, 0, 1);
