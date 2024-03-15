ALTER TABLE `state` CHANGE `iso2` `iso2` VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL;


ALTER TABLE `kb_articles` DROP INDEX `title`;
ALTER TABLE `kb_articles` CHANGE `title` `title` VARCHAR(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `kb_articles` CHANGE `excerpt` `excerpt` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `kb_articles` CHANGE `content` `content` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `kb_articles` CHANGE `tags` `tags` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL;
ALTER TABLE `kb_articles` ADD FULLTEXT(`title`, `excerpt`, `content`, `tags`);