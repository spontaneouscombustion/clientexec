UPDATE `users` SET `currency` = '0' WHERE `groupid` IN (SELECT `id` FROM `groups` WHERE `isadmin` = 1);