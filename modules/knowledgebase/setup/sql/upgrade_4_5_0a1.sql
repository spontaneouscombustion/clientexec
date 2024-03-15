ALTER TABLE `kb_categories` ADD `company_id` INT(11) NOT NULL DEFAULT '0' AFTER `id`;
ALTER TABLE `kb_categories` ADD INDEX `i_company_id` (`company_id`);
ALTER TABLE `kb_articles` ADD `company_id` INT(11) NOT NULL DEFAULT '0' AFTER `id`;
ALTER TABLE `kb_articles` ADD INDEX `i_company_id` (`company_id`);
ALTER TABLE `kb_articles_comments` CHANGE  `articleid`  `articleid` INT( 11 ) NOT NULL;
