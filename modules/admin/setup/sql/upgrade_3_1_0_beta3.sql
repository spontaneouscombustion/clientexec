# ---------------------------------------------------------
# UPDATE setting to 0-6 days per week
# ---------------------------------------------------------
UPDATE `setting` SET `description` = 'Enter number in range 0-6 (0 is Sunday) or a 3 letter shortcut (e.g. sun)' WHERE `description` = 'Enter number in range 0-7 (0 is Sunday) or a 3 letter shortcut (e.g. sun)';
UPDATE `help` SET `detail` = 'In the Run Schedule fields you can enter any format accepted by the Cron utility. For example for the field Minute: <ul> <li>* : every minute <li>Number: e.g. 4 will make it run at :04 <li>Range: e.g. 1-3 will make it run at :01, :02, :03 <li>List: e.g. 1,3,7 will make it run at :01, :03, :07 <li>Step: e.g. */3 will make it run every third minute <li>Range and step: 0-30/2 will make it run every other minute till :30 </ul> In Day of the Week you can enter a number in the range 0-6 (0 for Sunday) or a 3 letter shortcut (e.g. sun).' WHERE `title` = 'Run schedule format';

# ---------------------------------------------------------
# WIDEN BCC FIELD IN QUEUE TO BE ABLE TO ADD MANY BCC'S
# ---------------------------------------------------------
ALTER TABLE `email_queue` CHANGE `bcc` `bcc` TEXT NOT NULL;
