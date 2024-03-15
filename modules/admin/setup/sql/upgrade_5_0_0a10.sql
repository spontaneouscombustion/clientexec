UPDATE setting set value=0 where name='plugin_paypal_Paypal Subscriptions Option In Signup' and value=1;
UPDATE setting set value=1 where name='plugin_paypal_Paypal Subscriptions Option In Signup' and value=2;
DELETE from setting where name like 'plugin_createaccount_%';
DELETE FROM `setting` WHERE `name` LIKE '%Viewable in the Public Section%';
DELETE from setting where name like '%Public Name%';
ALTER TABLE  `users` ADD  `plus_score` INT NOT NULL DEFAULT  '0', ADD `plus_date` datetime default NULL, ADD  `plus_data` TEXT NOT NULL;
ALTER TABLE  `promotion` ADD  `style` VARCHAR( 56 ) NOT NULL DEFAULT  'default';
ALTER TABLE `package` ADD `asset_html` TEXT NOT NULL AFTER `description`;
ALTER TABLE `package` ADD `highlight` INT NOT NULL DEFAULT  '0' AFTER `asset_html`;
ALTER TABLE  `package` ADD  `signup_order` INT NOT NULL DEFAULT  '1';
INSERT INTO `customuserfields` (`id`, `name`, `type`, `isrequired`, `isChangable`, `isAdminOnly`, `width`, `myOrder`, `showcustomer`, `showadmin`, `InSignup`, `inSettings`, `dropdownoptions`) VALUES (NULL, /*T*/'Full Name'/*~T*/, 63, 1, 2, 0, 20, 0, 1, 1, 1, 1, '');
INSERT INTO `customuserfields` (`id`, `name`, `type`, `isrequired`, `isChangable`, `isAdminOnly`, `width`, `myOrder`, `showcustomer`, `showadmin`, `InSignup`, `inSettings`, `dropdownoptions`) VALUES (NULL, /*T*/'Full Address'/*~T*/, 64, 1, 2, 0, 20, 0, 1, 1, 1, 1, '');
UPDATE customuserfields set InSignup = 1, inSettings = 0 where type IN(2,3,4,5,6,11,12,14);
UPDATE customuserfields set InSignup = 1, inSettings = 1, showadmin = 1, showcustomer = 1 where type IN(7);
UPDATE customuserfields set myOrder = 100, InSignup = 0, inSettings = 0 where type IN(8);
UPDATE customuserfields set myOrder = 101, inSettings = 0 where type IN(16);
UPDATE customuserfields set isChangable = 2 where type IN(61);
Delete from customuserfields where name = 'IM on New Signup';
UPDATE customuserfields set myOrder = -20 where type IN(11);
UPDATE customuserfields set myOrder = -19 where type IN(12);
UPDATE customuserfields set myOrder = -18, isrequired = 0 where type IN(14);
UPDATE customuserfields set myOrder = -17 where type IN(2);
UPDATE customuserfields set myOrder = -16 where type IN(3);
UPDATE customuserfields set myOrder = -15 where type IN(4);
UPDATE customuserfields set myOrder = -14 where type IN(5);
UPDATE customuserfields set myOrder = -13, isrequired = 1 where type IN(6);
UPDATE customuserfields set isrequired = 0 where type IN(61);
UPDATE setting set name = "plugin_paypal_Paypal Subscriptions Option" where name = "plugin_paypal_Paypal Subscriptions Option In Signup";
DELETE from setting where name like 'plugin_%_Payment Description';
UPDATE setting set value = 1 where value = 2 and name = 'plugin_paypal_Paypal Subscriptions Option';

DELETE FROM `permissions` WHERE `permission`='billing_archived_invoices';
DELETE FROM `permissions` WHERE `permission`='billing_archive_invoice';
DELETE FROM `permissions` WHERE `permission`='billing_unarchive_invoices';
DELETE from `permissions` where `permission` = 'knowledgebase_add_article' and subject_id=1;
DELETE FROM `permissions` WHERE `permission`='clients_view_server_info';

ALTER TABLE  `server` ADD  `status_message` TEXT NOT NULL;

DELETE FROM `setting` WHERE `name` = "plugin_quantumvault_One-Time Payments";

INSERT INTO autoresponders (`type`,`name`,subject,contents,contents_html,description,helpid) SELECT `type`,'Credit Card Invoice Template',subject,contents,contents_html,'Template for credit card invoices sent through E-mail.',helpid FROM autoresponders WHERE `name` = 'Batch Invoice Notification Template';
INSERT INTO autoresponders (`type`,`name`,subject,contents,contents_html,description,helpid) VALUES( 3, 'Subscription Invoice Template', 'Billing Department',"ATTN: [CLIENTNAME],\n\nYou have an upcoming invoice for [BALANCEDUE] on [DATE] that will be processed with an active subscription with id [SUBSCRIPTION_ID].  If you do not approve of this charge and would like to cancel your subscription please contact us immediately.\n\nYou may review your invoice history at any time by logging in at: [CLIENTAPPLICATIONURL]\n\nIf you have any questions regarding your account, please feel free to contact us at [BILLINGEMAIL].\n\n\n[COMPANYNAME]\n[BILLINGEMAIL]","<HTML><head></head><body>ATTN: [CLIENTNAME],<br />\n<br />\nYou have an upcoming invoice for [BALANCEDUE] on [DATE] that will be processed with an active subscription with id [SUBSCRIPTION_ID].  If you do not approve of this charge and would like to cancel your subscription please contact us immediately.<br />\n<br />\nYou may review your invoice history at any time by logging in at: [CLIENTAPPLICATIONURL]<br />\n<br />\nIf you have any questions regarding your account, please feel free to contact us at [BILLINGEMAIL].<br />\n<br />\n<br />\n[COMPANYNAME]<br />\n[BILLINGEMAIL]</body></HTML>",'Template for subscription invoices sent through E-mail.',50);
DELETE FROM autoresponders WHERE `name` = 'Batch Invoice Notification Template';
ALTER TABLE  `troubleticket` ADD `response_time` INT DEFAULT NULL;