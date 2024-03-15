ALTER TABLE `recurringfee` CHANGE `quantity` `quantity` FLOAT NOT NULL DEFAULT '1.00';
ALTER TABLE `invoiceentry` CHANGE `quantity` `quantity` FLOAT NOT NULL DEFAULT '1.00';
ALTER TABLE `domain_packageaddon_prices` CHANGE `quantity` `quantity` FLOAT NOT NULL DEFAULT '1.00';

ALTER TABLE `invoice` CHANGE `amount` `amount` FLOAT NOT NULL DEFAULT '0.00';
ALTER TABLE `invoice` CHANGE `subtotal` `subtotal` FLOAT NOT NULL DEFAULT '0.00';
ALTER TABLE `invoice` CHANGE `balance_due` `balance_due` FLOAT NOT NULL DEFAULT '0.00';
ALTER TABLE `invoice` CHANGE `tax` `tax` FLOAT NOT NULL DEFAULT '0.00';
ALTER TABLE `invoice` CHANGE `tax2` `tax2` FLOAT NOT NULL DEFAULT '0.00';

ALTER TABLE `taxrule` CHANGE `tax` `tax` FLOAT NOT NULL DEFAULT '0.00';