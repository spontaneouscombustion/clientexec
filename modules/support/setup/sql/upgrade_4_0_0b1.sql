# ---------------------------------------------------------
# UPDATES TO Database tables for utf8 compliance
# ---------------------------------------------------------
ALTER TABLE `troubleticket` DEFAULT CHARACTER SET utf8;
ALTER TABLE `troubleticket` CHANGE `subject` `subject` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci  NOT NULL ;
ALTER TABLE `troubleticket` CHANGE `support_email` `support_email` varchar(60) CHARACTER SET utf8 COLLATE utf8_general_ci  NULL  ;
ALTER TABLE `troubleticket` CHANGE `rate_hash` `rate_hash` varchar(32) CHARACTER SET utf8 COLLATE utf8_general_ci  NULL  ;
ALTER TABLE `troubleticket` CHANGE `feedback` `feedback` text CHARACTER SET utf8 COLLATE utf8_general_ci  NULL  ;
ALTER TABLE `troubleticket` CHANGE `tag` `tag` varchar(10) CHARACTER SET utf8 COLLATE utf8_general_ci  NOT NULL  DEFAULT 'clear';
ALTER TABLE `troubleticket_additionalnotification` DEFAULT CHARACTER SET utf8;
ALTER TABLE `troubleticket_escalated` DEFAULT CHARACTER SET utf8;
ALTER TABLE `troubleticket_files` DEFAULT CHARACTER SET utf8;
ALTER TABLE `troubleticket_files` CHANGE `filename` `filename` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci  NOT NULL ;
ALTER TABLE `troubleticket_files` CHANGE `filekey` `filekey` varchar(16) CHARACTER SET utf8 COLLATE utf8_general_ci  NOT NULL ;
ALTER TABLE `troubleticket_filters` DEFAULT CHARACTER SET utf8;
ALTER TABLE `troubleticket_filters` CHANGE `name` `name` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci  NOT NULL ;
ALTER TABLE `troubleticket_filters` CHANGE `sql` `sql` text CHARACTER SET utf8 COLLATE utf8_general_ci  NOT NULL ;
ALTER TABLE `troubleticket_log` DEFAULT CHARACTER SET utf8;
ALTER TABLE `troubleticket_log` CHANGE `message` `message` text CHARACTER SET utf8 COLLATE utf8_general_ci  NOT NULL ;
ALTER TABLE `troubleticket_type` DEFAULT CHARACTER SET utf8;
ALTER TABLE `troubleticket_type` CHANGE `name` `name` varchar(40) CHARACTER SET utf8 COLLATE utf8_general_ci  NOT NULL ;
ALTER TABLE `troubleticket_type` CHANGE `description` `description` text CHARACTER SET utf8 COLLATE utf8_general_ci  NOT NULL ;
