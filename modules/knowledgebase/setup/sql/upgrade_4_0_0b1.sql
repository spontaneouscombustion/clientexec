# ---------------------------------------------------------
# UPDATES TO Database tables for utf8 compliance
# ---------------------------------------------------------
# ---------------------------------------------------------
# Remove fulltext index so that we can upgrade fields to utf8 readd below
# ---------------------------------------------------------
ALTER TABLE `kb_articles` DROP INDEX title;
ALTER TABLE `kb_articles` DEFAULT CHARACTER SET utf8;
ALTER TABLE `kb_articles` CHANGE `title` `title` varchar(250) CHARACTER SET utf8 COLLATE utf8_general_ci  NOT NULL ;
ALTER TABLE `kb_articles` CHANGE `excerpt` `excerpt` text CHARACTER SET utf8 COLLATE utf8_general_ci  NOT NULL ;
ALTER TABLE `kb_articles` CHANGE `content` `content` text CHARACTER SET utf8 COLLATE utf8_general_ci  NOT NULL ;
ALTER TABLE `kb_articles` CHANGE `author` `author` varchar(250) CHARACTER SET utf8 COLLATE utf8_general_ci  NOT NULL ;
ALTER TABLE `kb_articles_categories` DEFAULT CHARACTER SET utf8;
ALTER TABLE `kb_articles_comments` DEFAULT CHARACTER SET utf8;
ALTER TABLE `kb_articles_comments` CHANGE `articleid` `articleid` varchar(11) CHARACTER SET utf8 COLLATE utf8_general_ci  NOT NULL ;
ALTER TABLE `kb_articles_comments` CHANGE `username` `username` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci  NOT NULL ;
ALTER TABLE `kb_articles_comments` CHANGE `email` `email` varchar(250) CHARACTER SET utf8 COLLATE utf8_general_ci  NOT NULL ;
ALTER TABLE `kb_articles_comments` CHANGE `comment` `comment` text CHARACTER SET utf8 COLLATE utf8_general_ci  NOT NULL ;
ALTER TABLE `kb_articles_files` DEFAULT CHARACTER SET utf8;
ALTER TABLE `kb_articles_files` CHANGE `filename` `filename` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci  NOT NULL ;
ALTER TABLE `kb_articles_files` CHANGE `filekey` `filekey` varchar(16) CHARACTER SET utf8 COLLATE utf8_general_ci  NOT NULL ;
ALTER TABLE `kb_articles_rate` DEFAULT CHARACTER SET utf8;
ALTER TABLE `kb_articles_rate` CHANGE `used_ips` `used_ips` varchar(32) CHARACTER SET utf8 COLLATE utf8_general_ci  NOT NULL ;
ALTER TABLE `kb_articles_tickettypes` DEFAULT CHARACTER SET utf8;
ALTER TABLE `kb_articles_views` DEFAULT CHARACTER SET utf8;
ALTER TABLE `kb_articles_views` CHANGE `userid` `userid` varchar(11) CHARACTER SET utf8 COLLATE utf8_general_ci  NOT NULL ;
ALTER TABLE `kb_articles_views` CHANGE `ip` `ip` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci  NOT NULL ;
ALTER TABLE `kb_articlesrelated` DEFAULT CHARACTER SET utf8;
ALTER TABLE `kb_categories` DEFAULT CHARACTER SET utf8;
ALTER TABLE `kb_categories` CHANGE `name` `name` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci  NOT NULL ;
ALTER TABLE `kb_categories` CHANGE `description` `description` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci  NULL  ;
# ---------------------------------------------------------
# Readd fulltext search index
# ---------------------------------------------------------
ALTER TABLE `kb_articles` ADD FULLTEXT  `title` (`title` ,`excerpt` ,`content`);