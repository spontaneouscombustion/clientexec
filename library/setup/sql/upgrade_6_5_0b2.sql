ALTER TABLE users CHANGE email email VARCHAR(254) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '';
ALTER TABLE `chatuser` CHANGE `email` `email` VARCHAR(254) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;
ALTER TABLE `altuseremail` CHANGE `email` `email` VARCHAR(254) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '';

ALTER TABLE `kb_articles` DROP INDEX `title`, ADD FULLTEXT `title` (`title`, `excerpt`, `content`, `tags`);
ALTER TABLE `translations` ADD FULLTEXT (`value`);