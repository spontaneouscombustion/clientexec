# --------------------------------------------------------------------------------------------------
# ADDED FIELD TO INVOICE TABLE TO TRACK THE BALANCE DUE OF EACH INVOICE
# --------------------------------------------------------------------------------------------------
ALTER TABLE `invoice` ADD `balance_due` FLOAT NOT NULL DEFAULT '0' AFTER `subtotal`;

# --------------------------------------------------------------------------------------------------
# ALL UNPAID INVOICES (STATUS DIFFERENT FROM PAID) WILL HAVE A BALANCE DUE EQUAL TO THE INVOICE AMOUNT
# --------------------------------------------------------------------------------------------------
UPDATE `invoice` SET `balance_due` = `amount` WHERE `status` <> 1;

# --------------------------------------------------------------------------------------------------
# NEW PERMISSIONS
# --------------------------------------------------------------------------------------------------
INSERT INTO `permissions` (`subject_id`, `is_group`, `permission`, `target_id`) VALUES (1, 1, 'billing_apply_account_credit', 0);
INSERT INTO `permissions` (`subject_id`, `is_group`, `permission`, `target_id`) VALUES (4, 1, 'billing_add_variable_payment', 0);
INSERT INTO `permissions` (`subject_id`, `is_group`, `permission`, `target_id`) VALUES (4, 1, 'billing_apply_account_credit', 0);
INSERT INTO `permissions` (`subject_id`, `is_group`, `permission`, `target_id`) VALUES (4, 1, 'billing_credit_invoices', 0);
INSERT INTO `permissions` (`subject_id`, `is_group`, `permission`, `target_id`) VALUES (4, 1, 'clients_edit_account_credit', 0);

# ---------------------------------------------------------
# ADDED FIELD TO INVOICE TABLE TO STORE THE TAX NAME, TAX LEVEL 2, AND TAX LEVEL 2 NAME
# ---------------------------------------------------------
ALTER TABLE `invoice` ADD `taxname` VARCHAR( 20 ) NOT NULL DEFAULT '' AFTER `tax`;
ALTER TABLE `invoice` ADD `tax2` FLOAT NOT NULL DEFAULT '0.0' AFTER `taxname`;
ALTER TABLE `invoice` ADD `tax2name` VARCHAR( 20 ) NOT NULL DEFAULT '' AFTER `tax2`;
ALTER TABLE `invoice` ADD `tax2compound` TINYINT( 4 ) NOT NULL DEFAULT '0' AFTER `tax2name`;
UPDATE `invoice` SET `taxname` = 'Tax' WHERE `taxname` = '';

# ---------------------------------------------------------
# New features to the billing types
# ---------------------------------------------------------
ALTER TABLE `billingtype` ADD `price` VARCHAR( 10 ) NOT NULL DEFAULT '0.00';
ALTER TABLE `billingtype` ADD `archived` TINYINT NOT NULL DEFAULT 0;

# ---------------------------------------------------------
# Clean up some data
# ---------------------------------------------------------
update `invoiceentry` set billingtypeid=-3 WHERE `billingtypeid` = 0 and description = 'Discount coupon';
update `invoiceentry` set billingtypeid=-1,setup=1 WHERE `billingtypeid` = 0 and description = 'Account Setup';
update `invoiceentry` set billingtypeid=-1 WHERE `billingtypeid` = 0;