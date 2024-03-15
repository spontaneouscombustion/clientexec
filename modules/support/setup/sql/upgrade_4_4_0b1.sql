# Lets start tracking how ticket was opened
ALTER TABLE `troubleticket` ADD `method` TINYINT NOT NULL DEFAULT '0';