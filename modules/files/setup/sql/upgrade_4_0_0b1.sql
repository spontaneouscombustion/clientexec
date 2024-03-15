# ---------------------------------------------------------
# UPDATES TO Database tables for utf8 compliance
# ---------------------------------------------------------
ALTER TABLE `files` DEFAULT CHARACTER SET utf8;
ALTER TABLE `files` CHANGE `name` `name` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci  NOT NULL ;
ALTER TABLE `files` CHANGE `description` `description` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci  NOT NULL ;
ALTER TABLE `files` CHANGE `hash` `hash` varchar(16) CHARACTER SET utf8 COLLATE utf8_general_ci  NOT NULL ;
ALTER TABLE `files` CHANGE `lastip` `lastip` varchar(15) CHARACTER SET utf8 COLLATE utf8_general_ci  NOT NULL ;
ALTER TABLE `files` CHANGE `notes` `notes` text CHARACTER SET utf8 COLLATE utf8_general_ci  NOT NULL ;
ALTER TABLE `files` CHANGE `user_status` `user_status` set('-3','-2','-1','0','1') CHARACTER SET utf8 COLLATE utf8_general_ci  NOT NULL ;
ALTER TABLE `files_cats` DEFAULT CHARACTER SET utf8;
ALTER TABLE `files_cats` CHANGE `name` `name` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci  NOT NULL ;
ALTER TABLE `files_cats` CHANGE `user_status` `user_status` set('-3','-2','-1','0','1') CHARACTER SET utf8 COLLATE utf8_general_ci  NOT NULL ;
ALTER TABLE `files_cats_clienttypes` DEFAULT CHARACTER SET utf8;
ALTER TABLE `files_cats_servers` DEFAULT CHARACTER SET utf8;
ALTER TABLE `files_cats_users` DEFAULT CHARACTER SET utf8;
ALTER TABLE `files_clienttypes` DEFAULT CHARACTER SET utf8;
ALTER TABLE `files_servers` DEFAULT CHARACTER SET utf8;
ALTER TABLE `files_users` DEFAULT CHARACTER SET utf8;