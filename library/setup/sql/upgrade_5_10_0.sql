UPDATE `setting` SET `value` = 'default-old' WHERE (`name` = 'Invoice Template' AND `value` = 'default');

UPDATE `users` SET `invoice_template` = 'default-old' WHERE `invoice_template` = 'default';

ALTER TABLE `events_log` CHANGE `ip` `ip` VARCHAR(40);

ALTER TABLE `chatvisitor` CHANGE `path` `path` VARCHAR(256) NOT NULL;

ALTER TABLE `chatvisitor` CHANGE `ip` `ip` VARCHAR(40) NOT NULL;

ALTER TABLE `chatvisitor` CHANGE `ref_url` `ref_url` VARCHAR(256) NOT NULL;