ALTER TABLE `coupons` CHANGE `coupons_discount` `coupons_discount` DECIMAL(25,3) NOT NULL DEFAULT '0.00';

ALTER TABLE `currency` CHANGE `rate` `rate` DECIMAL(25,10) NOT NULL DEFAULT '1.0000000000';

ALTER TABLE `domain_packageaddon_prices` CHANGE `quantity` `quantity` DECIMAL(25,3) NOT NULL DEFAULT '1.00';

ALTER TABLE `invoice` CHANGE `amount` `amount` DECIMAL(25,3) NOT NULL DEFAULT '0.00';
ALTER TABLE `invoice` CHANGE `subtotal` `subtotal` DECIMAL(25,3) NOT NULL DEFAULT '0.00';
ALTER TABLE `invoice` CHANGE `balance_due` `balance_due` DECIMAL(25,3) NOT NULL DEFAULT '0.00';
ALTER TABLE `invoice` CHANGE `tax` `tax` DECIMAL(25,3) NOT NULL DEFAULT '0.00';
ALTER TABLE `invoice` CHANGE `tax2` `tax2` DECIMAL(25,3) NOT NULL DEFAULT '0.00';

ALTER TABLE `invoiceentry` CHANGE `price` `price` DECIMAL(25,3) NOT NULL DEFAULT '0.00';
ALTER TABLE `invoiceentry` CHANGE `price_percent` `price_percent` DECIMAL(25,3) NOT NULL DEFAULT '0.00';
ALTER TABLE `invoiceentry` CHANGE `taxamount` `taxamount` DECIMAL(25,3) NOT NULL DEFAULT '0.00';
ALTER TABLE `invoiceentry` CHANGE `quantity` `quantity` DECIMAL(25,3) NOT NULL DEFAULT '1.00';

ALTER TABLE `invoicetransaction` CHANGE `amount` `amount` DECIMAL(25,3) NOT NULL DEFAULT '0.00';

ALTER TABLE `packageaddon_prices` CHANGE `price0` `price0` DECIMAL(25,3) NOT NULL DEFAULT '-1.00';
ALTER TABLE `packageaddon_prices` CHANGE `price1` `price1` DECIMAL(25,3) NOT NULL DEFAULT '-1.00';
ALTER TABLE `packageaddon_prices` CHANGE `price3` `price3` DECIMAL(25,3) NOT NULL DEFAULT '-1.00';
ALTER TABLE `packageaddon_prices` CHANGE `price6` `price6` DECIMAL(25,3) NOT NULL DEFAULT '-1.00';
ALTER TABLE `packageaddon_prices` CHANGE `price12` `price12` DECIMAL(25,3) NOT NULL DEFAULT '-1.00';
ALTER TABLE `packageaddon_prices` CHANGE `price24` `price24` DECIMAL(25,3) NOT NULL DEFAULT '-1.00';
ALTER TABLE `packageaddon_prices` CHANGE `price36` `price36` DECIMAL(25,3) NOT NULL DEFAULT '-1.00';
ALTER TABLE `packageaddon_prices` CHANGE `price48` `price48` DECIMAL(25,3) NOT NULL DEFAULT '-1.00';
ALTER TABLE `packageaddon_prices` CHANGE `price60` `price60` DECIMAL(25,3) NOT NULL DEFAULT '-1.00';
ALTER TABLE `packageaddon_prices` CHANGE `price72` `price72` DECIMAL(25,3) NOT NULL DEFAULT '-1.00';
ALTER TABLE `packageaddon_prices` CHANGE `price84` `price84` DECIMAL(25,3) NOT NULL DEFAULT '-1.00';
ALTER TABLE `packageaddon_prices` CHANGE `price96` `price96` DECIMAL(25,3) NOT NULL DEFAULT '-1.00';
ALTER TABLE `packageaddon_prices` CHANGE `price108` `price108` DECIMAL(25,3) NOT NULL DEFAULT '-1.00';
ALTER TABLE `packageaddon_prices` CHANGE `price120` `price120` DECIMAL(25,3) NOT NULL DEFAULT '-1.00';

ALTER TABLE `recurringfee` CHANGE `amount` `amount` DECIMAL(25,3) NOT NULL DEFAULT '0.00';
ALTER TABLE `recurringfee` CHANGE `amount_percent` `amount_percent` DECIMAL(25,3) NOT NULL DEFAULT '0.00';
ALTER TABLE `recurringfee` CHANGE `quantity` `quantity` DECIMAL(25,3) NOT NULL DEFAULT '1.00';

ALTER TABLE `server` CHANGE `cost` `cost` DECIMAL(25,3) NOT NULL DEFAULT '0.00';

ALTER TABLE `taxrule` CHANGE `tax` `tax` DECIMAL(25,3) NOT NULL DEFAULT '0.00';

ALTER TABLE `users` CHANGE `balance` `balance` DECIMAL(25,3) NOT NULL DEFAULT '0.00';