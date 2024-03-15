# Increase help title size to avoid errors in strict mode when inserting some of our helps
ALTER TABLE `help` CHANGE `title` `title` VARCHAR( 100 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;

# Removed default value for lastview to avoid errors in strict mode
ALTER TABLE `users` CHANGE `lastview` `lastview` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL;