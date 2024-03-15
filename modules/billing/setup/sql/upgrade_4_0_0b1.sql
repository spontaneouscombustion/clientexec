# ---------------------------------------------------------
# UPDATES TO Database tables for utf8 compliance
# ---------------------------------------------------------
ALTER TABLE `invoice` DEFAULT CHARACTER SET utf8;
ALTER TABLE `invoice` CHANGE `description` `description` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci  NOT NULL ;
ALTER TABLE `invoice` CHANGE `taxname` `taxname` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci  NOT NULL ;
ALTER TABLE `invoice` CHANGE `tax2name` `tax2name` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci  NOT NULL ;
ALTER TABLE `invoice` CHANGE `processorid` `processorid` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci  NOT NULL ;
ALTER TABLE `invoice` CHANGE `pluginused` `pluginused` varchar(15) CHARACTER SET utf8 COLLATE utf8_general_ci  NOT NULL  DEFAULT 'none';
ALTER TABLE `invoice` CHANGE `checknum` `checknum` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci  NOT NULL ;
ALTER TABLE `invoiceentry` DEFAULT CHARACTER SET utf8;
ALTER TABLE `invoiceentry` CHANGE `description` `description` varchar(95) CHARACTER SET utf8 COLLATE utf8_general_ci  NOT NULL ;
ALTER TABLE `invoiceentry` CHANGE `detail` `detail` text CHARACTER SET utf8 COLLATE utf8_general_ci  NOT NULL ;
ALTER TABLE `invoicetransaction` DEFAULT CHARACTER SET utf8;
ALTER TABLE `invoicetransaction` CHANGE `response` `response` text CHARACTER SET utf8 COLLATE utf8_general_ci  NOT NULL ;
ALTER TABLE `invoicetransaction` CHANGE `transactionid` `transactionid` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci  NOT NULL  DEFAULT 'NA';
ALTER TABLE `invoicetransaction` CHANGE `action` `action` varchar(10) CHARACTER SET utf8 COLLATE utf8_general_ci  NOT NULL  DEFAULT 'none';
ALTER TABLE `invoicetransaction` CHANGE `last4` `last4` varchar(5) CHARACTER SET utf8 COLLATE utf8_general_ci  NOT NULL  DEFAULT '0000';