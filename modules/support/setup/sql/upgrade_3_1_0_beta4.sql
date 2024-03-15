# ---------------------------------------------------------
# CHANGE NAME OF DEFAULT AUTORESPONDER RULES
# ---------------------------------------------------------
UPDATE routingrules SET name='Default autoresponder for E-mails from registered users' WHERE name='Default autoresponder for registered users';
UPDATE routingrules SET name='Default autoresponder for E-mails from unregistered users' WHERE name='Default autoresponder for unregistered users';

# ---------------------------------------------------------
# ADD FIELD enabled_public TO TICKET TYPE
# ---------------------------------------------------------
ALTER TABLE `troubleticket_type` ADD `enabled_public` TINYINT NOT NULL DEFAULT '1' AFTER `enabled` ;
