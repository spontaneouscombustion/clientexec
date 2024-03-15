ALTER TABLE  `recurringfee` CHANGE  `recurring`  `recurring` TINYINT( 4 ) NULL DEFAULT  '0';
ALTER TABLE  `recurringfee` CHANGE  `paymentterm`  `paymentterm` TINYINT( 4 ) NOT NULL DEFAULT  '1';

# NEW PERMISSIONS
INSERT INTO `permissions` (`subject_id`, `is_group`, `permission`, `target_id`) VALUES (4, 1, 'admin_billing_setup', 0);
DELETE from permissions where subject_id=2 and is_group=1;

ALTER TABLE `recurringfee` DROP `status`;

# New field to store subscription id (mainly for PayPal subscriptions)
ALTER TABLE `recurringfee` ADD `subscription_id` TEXT default NULL AFTER `recurring` ;