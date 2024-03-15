ALTER TABLE `troubleticket_log` ADD `company_id` INT(11) NOT NULL DEFAULT '0' AFTER `id`;
ALTER TABLE troubleticket_log ADD INDEX  `company_id` (  `company_id` );

DELETE FROM  `user_customuserfields` WHERE  `customid` IN (select id from `customuserfields` where name= 'Profile_ShowProfile');
DELETE from `customuserfields` where name= 'Profile_ShowProfile';