# Removed default value for description to avoid errors in strict mode
ALTER TABLE `autoresponders` CHANGE `description` `description` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL;