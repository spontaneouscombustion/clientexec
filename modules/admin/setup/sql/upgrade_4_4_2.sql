delete from setting USING setting, setting as vtable WHERE (setting.id < vtable.id) AND (setting.name=vtable.name);

ALTER TABLE  `events_log` ADD INDEX  `i_userid` (  `user_id` );
ALTER TABLE  `users` ADD INDEX  `i_lastx` (  `lastseen` ,  `loggedin` ,  `groupid` );