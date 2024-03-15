ALTER TABLE `users` ADD `company_id` INT(11) NOT NULL DEFAULT '0' AFTER `id`;
ALTER TABLE `users` ADD INDEX `i_company_id` (`company_id`);

ALTER TABLE `groups` ADD `iscompanysuperadmin` TINYINT(4) NOT NULL DEFAULT 0 AFTER `issuperadmin`;

CREATE TABLE `companies` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `hashed_id` varchar(32) NOT NULL,
    `subdomain` varchar(20) NOT NULL,
    `status` tinyint(4) NOT NULL,
    `type` varchar(50),
    PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
