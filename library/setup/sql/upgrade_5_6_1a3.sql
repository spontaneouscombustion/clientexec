ALTER TABLE `product_addon` ADD `type` TINYINT(4) NOT NULL DEFAULT '0' AFTER `order`;
UPDATE `product_addon` pa SET pa.`type` = (SELECT p.`style` FROM `package` p WHERE p.`id` =  pa.`product_id`);
ALTER TABLE `package` DROP `style`;

ALTER TABLE `recurringfee` CHANGE `amount` `amount` FLOAT NOT NULL DEFAULT '0.00';
ALTER TABLE `recurringfee` ADD `quantity` INT NOT NULL DEFAULT '1' AFTER `amount_percent`;

ALTER TABLE `invoiceentry` CHANGE `price` `price` FLOAT NOT NULL DEFAULT '0.00';
ALTER TABLE `invoiceentry` CHANGE `taxamount` `taxamount` FLOAT NOT NULL DEFAULT '0.00';
ALTER TABLE `invoiceentry` ADD `quantity` INT NOT NULL DEFAULT '1' AFTER `price_percent`;

ALTER TABLE `domain_packageaddon_prices` ADD `quantity` INT NOT NULL DEFAULT '1' AFTER `billing_cycle`;