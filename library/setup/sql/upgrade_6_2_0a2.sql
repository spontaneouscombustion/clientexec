DROP TABLE IF EXISTS `billing_cycle`;
CREATE TABLE `billing_cycle` (
  `id` int(11) NOT NULL auto_increment,
  `name` text NOT NULL,
  `time_unit` char(1) NOT NULL default 'm',
  `amount_of_units` int(11) NOT NULL default 0,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `billing_cycle` (`time_unit`,`amount_of_units`)
) DEFAULT CHARACTER SET utf8, ENGINE=MyISAM AUTO_INCREMENT=1;

SET sql_mode='NO_AUTO_VALUE_ON_ZERO';

INSERT INTO `billing_cycle` (`id`, `name`, `time_unit`, `amount_of_units`) VALUES
(0, 'One Time', 'm', 0),
(1, '1 Month', 'm', 1),
(3, '3 Months', 'm', 3),
(6, '6 Months', 'm', 6),
(12, '1 Year', 'y', 1),
(24, '2 Years', 'y', 2),
(36, '3 Years', 'y', 3),
(48, '4 Years', 'y', 4),
(60, '5 Years', 'y', 5),
(72, '6 Years', 'y', 6),
(84, '7 Years', 'y', 7),
(96, '8 Years', 'y', 8),
(108, '9 Years', 'y', 9),
(120, '10 Years', 'y', 10);

ALTER TABLE `domain_packageaddon_prices` CHANGE `billing_cycle` `billing_cycle` INT(11) NOT NULL DEFAULT '0';

ALTER TABLE `invoiceentry` CHANGE `paymentterm` `paymentterm` INT(11) NOT NULL DEFAULT '0';

ALTER TABLE `recurringfee` CHANGE `paymentterm` `paymentterm` INT(11) NOT NULL DEFAULT '0';

ALTER TABLE `coupons` CHANGE `coupons_recurringmonths` `coupons_recurringmonths` INT(11) NOT NULL DEFAULT '0';

INSERT INTO `permissions` (`subject_id`, `is_group`, `permission`, `target_id`) VALUES (4, 1, 'billing_show_billing_cycles', 0);
