# --------------------------------------------------------

#
# Table structure for table `files`
#

DROP TABLE IF EXISTS `files`;
CREATE TABLE `files` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_parent` INT(11) UNSIGNED NULL,
  `name` VARCHAR(256) NOT NULL,
  `description` VARCHAR(256) NULL,
  `hash` VARCHAR(32) NULL,
  `visible` INT(11) DEFAULT '0' NULL,
  `public` INT(11) DEFAULT "0" NULL,
  `size` INT(11) UNSIGNED DEFAULT '0' NOT NULL,
  `added` date NOT NULL default '0000-00-00',
  `downloads` INT(11) UNSIGNED DEFAULT '0' NOT NULL,
  `lastip` VARCHAR(15) NULL,
  `notes` TEXT NULL,
  `user_status` SET('-3','-2','-1','0','1') NULL,
  `traverse_left` INT(11) UNSIGNED DEFAULT '0' NULL,
  `traverse_right` INT(11) UNSIGNED DEFAULT '0' NULL,
  `roomid` VARCHAR(20) NOT NULL DEFAULT  '0',
  PRIMARY KEY  (`id`)
) DEFAULT CHARACTER SET utf8, ENGINE=MyISAM AUTO_INCREMENT=1 ;

# --------------------------------------------------------

#
# Table structure for table `files_status`
#

DROP TABLE IF EXISTS `files_status`;
CREATE TABLE `files_status` (
  `id_file` INT UNSIGNED NOT NULL,
  `id_status` INT NOT NULL,
  PRIMARY KEY (`id_file`, `id_status`)
) DEFAULT CHARACTER SET utf8, ENGINE=MyISAM ;

# --------------------------------------------------------

#
# Table structure for table `files_public`
#

DROP TABLE IF EXISTS `files_public`;
CREATE TABLE `files_public` (
  `id_file` INT UNSIGNED NOT NULL,
  `setting` TINYINT(1) DEFAULT "0" NOT NULL,
  PRIMARY KEY (`id_file`)
) DEFAULT CHARACTER SET utf8, ENGINE=MyISAM ;

# --------------------------------------------------------

#
# Table structure for table `files_loggedin`
#

DROP TABLE IF EXISTS `files_loggedin`;
CREATE TABLE `files_loggedin` (
  `id_file` INT UNSIGNED NOT NULL,
  `setting` TINYINT(1) DEFAULT "0" NOT NULL,
  PRIMARY KEY (`id_file`)
) DEFAULT CHARACTER SET utf8, ENGINE=MyISAM ;

# --------------------------------------------------------

#
# Table structure for table `files_clienttypes`
#

DROP TABLE IF EXISTS `files_clienttypes`;
CREATE TABLE `files_clienttypes` (
  `id_file` int(11) NOT NULL default '0',
  `id_clienttype` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id_file`,`id_clienttype`)
) DEFAULT CHARACTER SET utf8, ENGINE=MyISAM;

# --------------------------------------------------------

#
# Table structure for table `files_servers`
#

DROP TABLE IF EXISTS `files_servers`;
CREATE TABLE `files_servers` (
  `id_file` int(11) NOT NULL default '0',
  `id_server` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id_file`,`id_server`)
) DEFAULT CHARACTER SET utf8, ENGINE=MyISAM;

# --------------------------------------------------------

#
# Table structure for table `files_users`
#

DROP TABLE IF EXISTS `files_users`;
CREATE TABLE `files_users` (
  `id_file` int(11) NOT NULL default '0',
  `id_user` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id_file`,`id_user`)
) DEFAULT CHARACTER SET utf8, ENGINE=MyISAM;
