ALTER TABLE `server` ADD `prepend_username` TINYINT NOT NULL DEFAULT '0';

UPDATE `promotion` SET `style`='default_domains' WHERE `style`='domainsearch';