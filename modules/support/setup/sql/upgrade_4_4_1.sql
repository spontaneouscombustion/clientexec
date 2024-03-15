CREATE TABLE tags (
    tag_type SMALLINT(3) NOT NULL DEFAULT 0,
    tag_name VARCHAR(35) NOT NULL,
    num_articles SMALLINT(5) UNSIGNED NOT NULL DEFAULT 1,
    PRIMARY KEY(`tag_name` , `tag_type`)
) DEFAULT CHARACTER SET utf8, ENGINE=MyISAM AUTO_INCREMENT=1 ;
ALTER TABLE `kb_articles` ADD `tags` text;
ALTER TABLE `kb_articles` ADD FULLTEXT (`tags`);

ALTER TABLE `troubleticket` ADD `externalid` VARCHAR( 45 ) NOT NULL DEFAULT 0;
ALTER TABLE `troubleticket_log` ADD `deletedname` VARCHAR( 35 ) NOT NULL DEFAULT '';
ALTER TABLE  `troubleticket_type` DROP  `is_billing_type`;
ALTER TABLE  `troubleticket_type` ADD  `systemid` INT( 2 ) NOT NULL DEFAULT  '0';

INSERT INTO `troubleticket_type` (`name`, `description`, `myorder`, `enabled`, `enabled_public`, `target_dept`, `target_staff`,`systemid`) VALUES ('Internal Billing Issues', 'This ticket type is used when ClientExec creates system messages for billing related issue.  Such as subscription cancelation etc', -1, 1, 0, 0, 0,1);
INSERT INTO `troubleticket_type` (`id`, `name`, `description`, `myorder`, `enabled`, `enabled_public`, `target_dept`, `target_staff`,`systemid`) VALUES (NULL, 'Externally Created', 'This ticket type is used when ClientExec creates system messages for externally created ticket using 3rd party services.', -1, 1, 0, 0, 0,2);