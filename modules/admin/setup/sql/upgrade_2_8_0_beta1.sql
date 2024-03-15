# --------------------------------------------------------
# VERSION UPDATING
# --------------------------------------------------------
UPDATE `setting` set value='2.8.0 beta1' WHERE name='ClientExec Version';

# ---------------------------------------------------------
# MAXMIND PHONE VERIFICATION TRIGGER VARIABLE
# ---------------------------------------------------------
INSERT INTO `setting` VALUES (NULL, 'Minimum Fraud Score to Trigger Telephone Verification',0,'If MaxMind Telephone Verification and Fraud Control are enabled, only trigger the verification call if the fraud score exceeds this number.', 12, 1, 0, 0, 0, 0, 0, 0, 0, 0);

# ---------------------------------------------------------
# EMAIL TEMPLATE AND HELP FOR BATCH INVOICE NOTIFIER SERVICE
# ---------------------------------------------------------
INSERT INTO `setting` VALUES (NULL , 'Batch Invoice Notification Template', 'ATTN: [CLIENTNAME],\n\nYou have an upcoming invoice for [AMOUNT] on [DATE] that will be charged to your credit card on file with [COMPANYNAME].  If you do not approve of this charge and would like to cancel your subscription please contact us immediately.\n\nYou may review your invoice history at any time by logging in at: [CLIENTAPPLICATIONURL]\n\nIf you have any questions regarding your account, please feel free to contact us at [BILLINGEMAIL].\n\n\n[COMPANYNAME]\n[BILLINGEMAIL]', 'Template for the email sent to clients by the upcoming batch invoice notifier service.', '7', '1', '0', '1', '0', '0', '2', '0', '0', '0');
INSERT INTO `help` VALUES (NULL , 'Batch Invoice Notifier Tags', '[<font class=bodyhighlight>DATE</font>] Date payment is due.<br>[<font class=bodyhighlight>CLIENTNAME</font>] <br>[<font class=bodyhighlight>CLIENTEMAIL</font>] <br>[<font class=bodyhighlight>AMOUNT</font>] The total price due.<br>[<font class=bodyhighlight>CLIENTAPPLICATIONURL</font>] URL to Client Exec.<br>[<font class=bodyhighlight>FORGOTPASSWORDURL</font>] URL to retrieve forgotten password.<BR>[<font class=bodyhighlight>COMPANYNAME</font>] <BR>[<font class=bodyhighlight>BILLINGEMAIL</font>] Email address for billing inquiries<br>[<font class=bodyhighlight>CUSTOMPROFILE_xxxx</font>]<br>where xxx is custom profile field name', 'Click to view available tags', '240', '320');

# ----------------------------------------------------------
# SETTING FOR SELECTING SMTP PORT
# ----------------------------------------------------------
INSERT INTO `setting` VALUES (NULL, 'SMTP Port', '', 'This is the SMTP port that will be used when sending email.  Leaving this blank will use port 25 by default.', 3, 0, 0, 0, 0, 4, 0, 0, 0, 0);

# -----------------------------------------------------------
# SETTING FOR NUMBER OF DAYS TO ALLOW A TICKET TO BE REOPENED
# -----------------------------------------------------------
INSERT INTO `setting` VALUES (NULL , 'Days To Allow Tickets To Be Reopened', '', 'Number of days that a customer is able to reopen a closed ticket. Leave blank to always allow a ticket to be reopened.', '8', '0', '0', '0', '0', '0', '0', '0', '0', '0');

# -----------------------------------------------------------
# REMOVED WELCOME SYSTEM MESSAGE SETTING, NO LONGER USED
# -----------------------------------------------------------
DELETE FROM setting WHERE name='Welcome System Message';
ALTER TABLE `users` DROP `systemmsg`;

# -----------------------------------------------------------
# Email Template AND HELP FOR EXPIRING CC EMAIL TEMPLATE
# -----------------------------------------------------------
INSERT INTO `help` VALUES (NULL, 'Expiring CC Template', '[<font class=bodyhighlight>CLIENTNAME</font>] <br>[<font class=bodyhighlight>CLIENTEMAIL</font>]<br>[<font class=bodyhighlight>CCLASTFOUR</font>] The last four digits of the customer\'s credit card.<br>[<font class=bodyhighlight>CCEXPDATE</font>] The expiration date of the customer\'s credit card.<br>[<font class=bodyhighlight>CLIENTAPPLICATIONURL</font>] URL to Client Exec.<br>[<font class=bodyhighlight>FORGOTPASSWORDURL</font>] URL to retrieve forgotten password.<BR>[<font class=bodyhighlight>COMPANYNAME</font>] <BR>[<font class=bodyhighlight>BILLINGEMAIL</font>] Email address for billing inquiries<br>[<font class=bodyhighlight>CUSTOMPROFILE_xxxx</font>]<br>where xxx is custom profile field name', 'Click to view available tags', 240, 320);
INSERT INTO `setting` VALUES (NULL, 'Expiring CC Template', 'ATTN: [CLIENTNAME],\n\nYour credit card ending with [CCLASTFOUR] will soon be expiring on [CCEXPDATE].  Please login to update your credit card information as soon as possible at: [CLIENTAPPLICATIONURL]\n\nIf you have any questions regarding your account, please feel free to contact us at [BILLINGEMAIL].\n\n\n[COMPANYNAME]\n[BILLINGEMAIL]', 'Template for the email sent to clients by the expiring credit card notifier service.', '7', '1', '0', '1', '0', '0', '10', '0', '0', '0');

# -----------------------------------------------------------
# ALLOW BILLING STAFF TO VIEW TICKETS ASSIGNED TO THEM
# -----------------------------------------------------------
UPDATE `module_group` SET `viewable` = '1' WHERE `moduleid` =5 AND `groupid` =4 LIMIT 1;

# -----------------------------------------------------------
# ALLOW EXECUTING SERVICES FROM AN URL
# -----------------------------------------------------------
INSERT INTO `setting` VALUES (NULL, 'Allow run services from URL', '0', 'Select Yes if you want to allow executing services from an URL.<br><b>Note:</b> Set it to <b>yes</b> if your server is running under windows, or if you have no access to CRONTAB', 1, 1, 1, 0, 0, 3, 0, 0, 0, 0);

# -----------------------------------------------------------
# UPDATE custom field that holds the direction and order column for needing cc validation
# -----------------------------------------------------------
UPDATE customuserfields set name='TABLESORT_snapshot_cc' where name='TABLESORT_NeedingValidation';

# -----------------------------------------------------------
# OTHERS
# -----------------------------------------------------------
UPDATE customuserfields SET TYPE =20 WHERE name = 'Records per page';
UPDATE customuserfields SET TYPE =21 WHERE name = 'ViewSideBar';
UPDATE customuserfields SET TYPE =22 WHERE name = 'Dashboard graph totals';
UPDATE customuserfields SET TYPE =23 WHERE name = 'Dashboard graph legend';
UPDATE customuserfields SET TYPE =24 WHERE name = 'Use Paypal Subscriptions';

# ------------------------------------------------------------
# UPDATE custom user fields width for drop downs to start using
# ------------------------------------------------------------
update customuserfields set width=55 where name='HTML Email Format';
update customuserfields set width=55 where name='Receive Email Announcements';

# ------------------------------------------------------------
# REMOVE REDUNDANT INDEXED
# ------------------------------------------------------------
ALTER TABLE `user_customuserfields` DROP INDEX `userid`;
ALTER TABLE `user_customuserfields` DROP INDEX `customid` ;
ALTER TABLE `domain_customdomainfields` DROP INDEX `userid`;
ALTER TABLE `domain_customdomainfields` DROP INDEX `customid`;

# ------------------------------------------------------------
# UPDATE setting for use Email template --> Get New Password Template
# ------------------------------------------------------------
INSERT INTO `setting` VALUES (NULL, 'Get New Password Template', '[CLIENTNAME],\r\n\r\nYour password has been reset successfully.\r\n\r\nNew password: [NEWPASSWORD]\r\n\r\nPlease goto [CLIENTEXEC URL] to login.\r\n\r\nThank you,\r\n[COMPANYNAME]\r\n[COMPANYEMAIL]', 'Initial email customer receives to know the new password to their account.', 7, 0, 0, 1, 0, 8, 12, 0, 0, 0);
INSERT INTO `help` VALUES (12, 'Get New Password Tags', '[<font class=bodyhighlight>CLIENTNAME</font>] <br>[<font class=bodyhighlight>NEWPASSWORD</font>] <br>[<font class=bodyhighlight>CLIENTEXEC URL</font>] <br>[<font class=bodyhighlight>COMPANYNAME</font>] <br>[<font class=bodyhighlight>COMPANYEMAIL</font>]', 'Click to view available tags', 240, 320);
UPDATE setting SET  myorder = 1 WHERE name = 'Invoice Template';
UPDATE setting SET  myorder = 2 WHERE name = 'Payment Receipt Template';
UPDATE setting SET  myorder = 3 WHERE name = 'Overdue Invoice Template';
UPDATE setting SET  myorder = 4 WHERE name = 'Batch Invoice Notification Template';
UPDATE setting SET  myorder = 5 WHERE name = 'Expiring CC Template';
UPDATE setting SET  myorder = 6 WHERE name = 'Recommend Us Template';
UPDATE setting SET  myorder = 7 WHERE name = 'Forgot Password Template';
UPDATE setting SET  myorder = 9 WHERE name = 'Trouble Ticket Template';
UPDATE setting SET  myorder = 10 WHERE name = 'New Trouble Ticket Autoresponder Template';
UPDATE setting SET  myorder = 11 WHERE name = 'Support Ticket Assigned Template';

# ------------------------------------------------------------
# PACKAGE ADDONS ORDERING
# ------------------------------------------------------------
ALTER TABLE `packageaddon` ADD `order` INT NOT NULL ;

# -----------------------------------------------------------
# UPDATE custom field that holds the direction and order column for needing cc validation
# -----------------------------------------------------------
UPDATE customuserfields set name='TABLESORT_snapshot_invoices' where name='TABLESORT_OutstandingInvoices';
UPDATE customuserfields set name='TABLESORT_snapshot_tickets' where name='TABLESORT_OpenTickets';
UPDATE customuserfields set name='TABLESORT_snapshot_pending' where name='TABLESORT_PendingCustomers';
UPDATE customuserfields set name='TABLESORT_snapshot_uninvoiced' where name='TABLESORT_UninvoicedWork';

# -----------------------------------------------------------
# Insert custom field to store staff members default filter for invoices
# -----------------------------------------------------------
Insert into customuserfields values(NULL,'DASHBOARD_TICKETFILTER',0,0,0,0,20,0,0,0,0,0,'');

# -----------------------------------------------------------
# UPDATE setting to allow "HTML" in the Terms and Conditions template
# -----------------------------------------------------------
UPDATE setting SET  description = 'Terms and Conditions that new customers need to agree to before receiving your services. The setting, Show Terms and Conditions, will need to be set to YES before this content is displayed in the signup process.<br><b>Note:</b> HTML is accepted.' WHERE name = 'Terms and Conditions';

# -----------------------------------------------------------
# Insert custom field to store staff members default filter for invoices
# -----------------------------------------------------------
Insert into customuserfields values(NULL,'DASHBOARD_LASTUSEDSNAPSHOT',0,0,0,0,20,0,0,0,0,0,'');

# -----------------------------------------------------------
# Update setting Google Checkout Sandbox to be translated
# -----------------------------------------------------------
UPDATE setting SET description = 'Select YES if you want to set Google Checkout into Sandbox for testing. (<b>Note:</b> You must set to NO before accepting actual payments through this processor.)<p><p><b>Important:</b> Before using this plugin you must set the following on your Google Checkout Account, even if it is the Sandbox Account:<p><li>On <i><u>Settings -> Integration -> API callback URL</u></i> set the full Callback URL stated above.(<b>Note: </b>If you are going to use the Production Account, you must have installed an HTTPS connection secured by 128-bit SSL v3 or TLS connection, and your Callback URL must start with <b>https</b> instead of http</li><li>On <i><u>Settings -> Preferences -> Order processing preferences</u></i> select the option <i>Automatically authorize and charge the buyer\'s credit card.</i></li><li>On <i><u>Settings -> Profile ->  Public business website</u></i> write the full Callback URL stated above.</li>' WHERE name = 'plugin_googlecheckout_Google Checkout Sandbox';

# -----------------------------------------------------------
# Explain better charset encoding setting
# -----------------------------------------------------------
UPDATE `setting` SET `description` = 'Please enter the charset you want to use in your application.<br />If you change this make sure the database connection uses the same encoding by adding the following line to config.php (example for UTF-8):<br />$dbencoding = \'utf8\'' WHERE `name` = 'Character Set' ;

# ----------------------------------------------------------------------
# We are storing languages in the cache table, so we need more space
# MEDIUMBLOB because TEXT will give us encodings headaches
# ----------------------------------------------------------------------
ALTER TABLE `cache` CHANGE `content` `content` MEDIUMBLOB NOT NULL ;

# -------------------------------------------------------------------
# Update setting so we do not delete and recreate the plugin setting
# -------------------------------------------------------------------
UPDATE `setting` SET `description` = 'Select Yes if you wish to use Directi&#039;s testing environment, so that transactions are not actually made.<br><br><b>Note: </b>You will first need to register for a demo account at<br>http://cp.onlyfordemo.net/servlet/ResellerSignupServlet?&validatenow=false.' WHERE `name` = 'plugin_directi_Use testing server' ;

# --------------------------------------------------------------------
# Track if supportpipe is in use so we can show reply above this line
# --------------------------------------------------------------------
INSERT INTO `setting` VALUES (NULL, 'Email Piping In Use', 0, 'If email piping is in use set this to true so the reply above this line can be added to support ticket emails.', 8, 0, 1, 0, 0, 0, 0, 0, 0, 1);
