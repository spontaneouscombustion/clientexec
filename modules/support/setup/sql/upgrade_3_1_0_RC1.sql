# ---------------------------------------------------------------------
# ADD FIELD routing_type TO routingrules
# AND UPDATE WITH DEFAULT VALUE (TEXT TYPE CAN'T HAVE A DEFAULT VALUE)
# ---------------------------------------------------------------------
ALTER TABLE `routingrules` ADD `filter_out` TEXT NOT NULL AFTER `routing_type` ;
UPDATE `routingrules` SET filter_out = "Subject: ***SPAM***\r\nX-Spam-Status: Yes";
