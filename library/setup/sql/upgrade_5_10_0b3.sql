ALTER TABLE `email` CHANGE `subject` `subject` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;
ALTER TABLE `email` CHANGE `content` `content` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;
ALTER TABLE `email` CHANGE `to` `to` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;