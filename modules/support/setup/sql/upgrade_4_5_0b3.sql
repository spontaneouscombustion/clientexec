ALTER TABLE `tags` ADD `company_id` INT(11) NOT NULL DEFAULT '0' FIRST;
DROP INDEX `PRIMARY` ON `tags`;
ALTER TABLE `tags` ADD PRIMARY KEY (`company_id`, `tag_name` , `tag_type`);
