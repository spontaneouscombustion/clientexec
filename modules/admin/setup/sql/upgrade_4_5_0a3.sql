ALTER TABLE `announcement` ADD `company_id` INT(11) NOT NULL default '0' AFTER `id`;
ALTER TABLE `groups` ADD `company_id` INT(11) NOT NULL default '0' AFTER `name`;
ALTER TABLE `groups` ADD `iscustomersmaingroup` tinyint(4) NOT NULL default '0' AFTER `iscompanysuperadmin`;
ALTER TABLE `groups` ADD INDEX `i_company_id` (`company_id`);
UPDATE `groups` SET iscustomersmaingroup=1 WHERE id=1;

DELETE FROM `permissions` WHERE `permission`='home_view';

#added because some previous 4.4.1 installs might have missed this
CREATE TABLE IF NOT EXISTS tags (
    tag_type SMALLINT(3) NOT NULL DEFAULT 0,
    tag_name VARCHAR(35) NOT NULL,
    num_articles SMALLINT(5) UNSIGNED NOT NULL DEFAULT 1,
    PRIMARY KEY(`tag_name` , `tag_type`)
) DEFAULT CHARACTER SET utf8, ENGINE=MyISAM AUTO_INCREMENT=1 ;

#update these two types ensure they are disabled from public
Update troubleticket_type set myorder=-1,enabled_public = 0 where systemid > 0;

CREATE TABLE IF NOT EXISTS active_orders (
    `id` VARCHAR(13) NOT NULL DEFAULT '',
    `product_id` int(11) NOT NULL DEFAULT 0,
    `expires` datetime NOT NULL,
    PRIMARY KEY(`id`),
    INDEX  `prod` (  `product_id` )  
) DEFAULT CHARACTER SET utf8, ENGINE=MyISAM;

DELETE from `customuserfields` where name = 'ICQ Number';
DELETE from `customuserfields` where name = 'IM on Payment';
DELETE from `customuserfields` where name = 'IM on Login';
DELETE from `customuserfields` where name = 'IM on New Ticket';
