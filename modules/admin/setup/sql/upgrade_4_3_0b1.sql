# Move some settings to new id
UPDATE `setting` set type=20 WHERE `name`='Language' and type=1;
UPDATE `setting` set type=20 WHERE `name`='Date Format' and type=1;
UPDATE `setting` set type=20 WHERE `name`='Default Country' and type=1;
UPDATE `setting` set type=21 WHERE `name`='ReCaptcha Private Key' and type=1;
UPDATE `setting` set type=21 WHERE `name`='ReCaptcha Public Key' and type=1;

# Cancel URL
INSERT INTO `setting` VALUES (NULL, 'Cancel Order URL', '', '', 'URL to redirect a customer to if they choose to cancel their order at any point during the order process (via the Cancel Order link).', 10, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1);

# Set new description
UPDATE `setting` SET `description`='URL to redirect customer upon signup completion. Use GET Request parameter "success" to identify the action in your page (1 for Success or 0 to Reject).' WHERE `name`='Signup Completion URL';

# Add new currency format settings
ALTER TABLE `currency` ADD `decimalssep` CHAR( 10 ) NOT NULL DEFAULT '.' AFTER `symbol`;
ALTER TABLE `currency` ADD `thousandssep` CHAR( 10 ) NOT NULL DEFAULT ',' AFTER `decimalssep`;

UPDATE `setting` SET `issession` = '0' WHERE `name` = 'services_meta_info';