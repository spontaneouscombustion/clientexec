# ---------------------------------------------------------
# UPDATES TO Database tables for utf8 compliance
# ---------------------------------------------------------
ALTER TABLE `domain_customdomainfields` DEFAULT CHARACTER SET utf8;
ALTER TABLE `domain_customdomainfields` CHANGE `value` `value` text CHARACTER SET utf8 COLLATE utf8_general_ci  NULL  ;
ALTER TABLE `domain_packageaddon_prices` DEFAULT CHARACTER SET utf8;
ALTER TABLE `domains` DEFAULT CHARACTER SET utf8;
ALTER TABLE `domains` CHANGE `DomainName` `DomainName` text CHARACTER SET utf8 COLLATE utf8_general_ci  NULL  ;
ALTER TABLE `domains` CHANGE `UserName` `UserName` text CHARACTER SET utf8 COLLATE utf8_general_ci  NULL  ;
ALTER TABLE `domains` CHANGE `Comments` `Comments` mediumtext CHARACTER SET utf8 COLLATE utf8_general_ci  NULL  ;
ALTER TABLE `domains` CHANGE `registrar_orderid` `registrar_orderid` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci  NOT NULL  DEFAULT '0';
ALTER TABLE `domains` CHANGE `server_acct_properties` `server_acct_properties` text CHARACTER SET utf8 COLLATE utf8_general_ci  NULL  ;
ALTER TABLE `domains` CHANGE `domain_extra_attr` `domain_extra_attr` text CHARACTER SET utf8 COLLATE utf8_general_ci  NULL  ;
ALTER TABLE `user_customuserfields` DEFAULT CHARACTER SET utf8;
ALTER TABLE `user_customuserfields` CHANGE `value` `value` text CHARACTER SET utf8 COLLATE utf8_general_ci  NULL  ;


# --------------------------------------------------------
# ADDED CUSTOM FIELD TO the state of the invoiceticketgridstate
# --------------------------------------------------------
INSERT INTO `customuserfields` VALUES (NULL , 'InvoiceTicketGridState', 54, 0, 0, 0, 20, 0, 0, 0, 0, 0, '');
INSERT INTO `customuserfields` VALUES (NULL , 'SupportTicketGridState', 55, 0, 0, 0, 20, 0, 0, 0, 0, 0, '');
INSERT INTO `customuserfields` VALUES (NULL , 'ClientListGridState', 56, 0, 0, 0, 20, 0, 0, 0, 0, 0, '');
INSERT INTO `customuserfields` VALUES (NULL , 'DomainsListGridState', 57, 0, 0, 0, 20, 0, 0, 0, 0, 0, '');
INSERT INTO `customuserfields` VALUES (NULL , 'AnnouncementGridState', 58, 0, 0, 0, 20, 0, 0, 0, 0, 0, '');


# --------------------------------------------------------
# UPDATES OLD NOT ADMIN USERS HAVING ZERO (0) AS CURRENCY TO USE THE DEFAULT CURRENCY
# --------------------------------------------------------
UPDATE `users` SET `currency` = (SELECT `value` FROM `setting` WHERE `name` LIKE 'Default Currency') WHERE `currency` = '0' AND `groupid` NOT IN (SELECT `id` FROM `groups` WHERE `isadmin` = 1 OR `issuperadmin` = 1);

# #########################################
# userPackage and customfield by productype
CREATE TABLE `userPackage` (
    `id` int(11) NOT NULL auto_increment,
    `userId` int(11) default NULL,
    `status` tinyint(1) NOT NULL default '0',
    `productGroupId` int(11) NOT NULL default '0',
    `productId` int(11) NOT NULL default '0',
    `useCustomPrice` TINYINT NOT NULL DEFAULT '0',
    `customPrice` FLOAT NOT NULL DEFAULT '0',
    `dateActivated` datetime default NULL,
    PRIMARY KEY (`id`),
    KEY `userId` (`userId`)
) DEFAULT CHARACTER SET utf8, ENGINE=MyISAM AUTO_INCREMENT=1;

CREATE TABLE `object_customField` (
    `objectid` int(11) NOT NULL default '0',
    `customFieldId` int(11) NOT NULL default '0',
    `value` text,
    PRIMARY KEY (`objectid`, `customFieldId`)
) DEFAULT CHARACTER SET utf8, ENGINE=MyISAM;

CREATE TABLE `customField` (
    `id` int(11) NOT NULL auto_increment,
    `groupId` tinyint(4) NOT NULL default '0',
    `subGroupId` tinyint(4) NOT NULL default '0',
    `fieldType` tinyint(4) NOT NULL default '0',
    `name` varchar(50) NOT NULL default '',
    `isRequired` tinyint(4) NOT NULL default '0',
    `isChangeable` tinyint(4) NOT NULL default '1',
    `isAdminOnly` tinyint(4) NOT NULL default '0',
    `fieldOrder` int(11) NOT NULL default '0',
    `showCustomer` tinyint(4) NOT NULL default '1',
    `showAdmin` tinyint(4) NOT NULL default '1',
    `dropDownOptions` longtext NOT NULL,
    `inSettings` smallint(6) NOT NULL default '1',
    `InSignup` tinyint(1) NOT NULL default '0',
    PRIMARY KEY (`id`)
) DEFAULT CHARACTER SET utf8, ENGINE=MyISAM AUTO_INCREMENT=1;


ALTER TABLE `users_domains` DEFAULT CHARACTER SET utf8;
ALTER TABLE `users_domains` CHANGE `domain` `domain` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci  NOT NULL ;
ALTER TABLE `users_domains` CHANGE `username` `username` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci  NOT NULL ;
ALTER TABLE `users_domains` CHANGE `domain_extra_attr` `domain_extra_attr` text CHARACTER SET utf8 COLLATE utf8_general_ci  NULL  ;
ALTER TABLE `users_domains` CHANGE `registrar` `registrar` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci  NOT NULL ;
ALTER TABLE `users_domains` CHANGE `registrar_orderid` `registrar_orderid` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci  NOT NULL ;
