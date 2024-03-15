# ---------------------------------------------------------
# MOVE TAX TO INVOICE ENTRY LEVEL
# ---------------------------------------------------------
ALTER TABLE `recurringfee` ADD `taxable` TINYINT NOT NULL DEFAULT '0' AFTER `paymentterm` ;
ALTER TABLE `invoiceentry` ADD `taxable` TINYINT NOT NULL DEFAULT '0';
ALTER TABLE `invoice` ADD `subtotal` FLOAT NOT NULL DEFAULT '0' AFTER `amount`;
UPDATE `invoiceentry`, `invoice`, `users` SET `invoiceentry`.`taxable`='1' WHERE `invoiceentry`.`invoiceid`=`invoice`.`id` AND `invoice`.`tax` > 0 AND `invoiceentry`.`customerid`=`users`.`id` AND `users`.`taxable` = '1';
UPDATE `recurringfee`, `users` SET `recurringfee`.`taxable`='1' WHERE `recurringfee`.`customerid`=`users`.`id` AND `users`.`taxable` = '1';

# --------------------------------------------------------------------------------------------------
# ADDED FIELDS TO INVOICEENTRY TABLE TO DISTINGUISH PRORATED ENTRIES AND SETUP PORTION OF ADDONS
# --------------------------------------------------------------------------------------------------
ALTER TABLE `invoiceentry` ADD `is_prorating` TINYINT NOT NULL DEFAULT '0' AFTER `billingtypeid` ;
ALTER TABLE `invoiceentry` ADD `addon_setup` TINYINT NOT NULL DEFAULT '0' AFTER `setup` ;

# ---------------------------------------------------------
# ALLOW MARKING INVOICES AS PAID, UNPAID, VOID, REFUNDED
# ---------------------------------------------------------
ALTER TABLE `invoice` CHANGE `paid` `status` TINYINT( 4 ) NOT NULL DEFAULT '0';
INSERT INTO `permissions` (`subject_id`, `is_group`, `permission`, `target_id`) VALUES (4, 1, 'billing_void_invoices', 0);
INSERT INTO `permissions` (`subject_id`, `is_group`, `permission`, `target_id`) VALUES (4, 1, 'billing_refund_invoices', 0);

# ---------------------------------------------------------
# NEW EVENTLOG PERMISSIONS
# ---------------------------------------------------------
INSERT INTO `permissions` (`subject_id`, `is_group`, `permission`, `target_id`) VALUES (4, 1, 'billing_view_eventlog', 0);
INSERT INTO `permissions` (`subject_id`, `is_group`, `permission`, `target_id`) VALUES (5, 1, 'billing_view_eventlog', 0);

# ---------------------------------------------------------
# ADDED RECURRING TIME TO COUPONS
# ---------------------------------------------------------
ALTER TABLE `coupons` ADD `coupons_recurringmonths` tinyint(4) NOT NULL default '0' AFTER `coupons_recurring`;

# ---------------------------------------------------------
# COUPONS CAN CHOOSE IF TAXABLE OR NOT
# ---------------------------------------------------------
ALTER TABLE `coupons` ADD `coupons_taxable` tinyint(4) NOT NULL default '1';


# ---------------------------------------------------------
# ADDED RECURRING TIME TO RECURRING FEES
# ---------------------------------------------------------
ALTER TABLE `recurringfee` ADD `monthlyusage` TEXT DEFAULT NULL;

# --------------------------------------------------------------------
# ADDED FIELD TO DETERMINE TO WHAT A PERCENTUAL COUPON APPLIES TO
# --------------------------------------------------------------------
ALTER TABLE `invoiceentry` ADD `price_percent` FLOAT NOT NULL DEFAULT '0' AFTER `price` ;
ALTER TABLE `invoiceentry` ADD `coupon_applicable_to` TINYINT NOT NULL DEFAULT '0' AFTER `appliestoid` ;
UPDATE `invoiceentry` SET `coupon_applicable_to` = 127 WHERE `billingtypeid` = -3;

# ---------------------------------------------------------
# ADDED INDEX TO INVOICEENTRY TABLE INDEXING THEIR INVOICE ID
# ---------------------------------------------------------
ALTER TABLE `invoiceentry` ADD INDEX ( `invoiceid` );
