# ---------------------------------------------------------
# LIMIT TICKET SUBJECT TO 200 CHARACTERS
# ---------------------------------------------------------
ALTER TABLE `troubleticket` CHANGE `subject` `subject` VARCHAR( 200 ) NOT NULL;

# ---------------------------------------------------------
# ADDED FIELD TO STORE E-MAIL USED TO OPEN TICKET
# ---------------------------------------------------------
ALTER TABLE `troubleticket` ADD `support_email` VARCHAR( 60 ) NULL AFTER `priority` ;
