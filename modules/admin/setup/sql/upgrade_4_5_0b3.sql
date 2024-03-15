ALTER TABLE `companies` ADD `limit_staff` TINYINT( 4 ) NOT NULL DEFAULT '-1';

INSERT INTO `customuserfields` (`id`, `name`, `type`, `isrequired`, `isChangable`, `isAdminOnly`, `width`, `myOrder`, `showcustomer`, `showadmin`, `InSignup`, `inSettings`, `dropdownoptions`) VALUES (NULL , 'Sidebar_Position', 60, 0, 0, 0, 20, 0, 0, 0, 0, 0, '');

ALTER TABLE  `canned_response` CHANGE  `name`  `name` VARCHAR( 45 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;