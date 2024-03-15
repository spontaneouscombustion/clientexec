# --------------------------------------------------------

#
# Table structure for table `kb_articles`
#

DROP TABLE IF EXISTS `kb_articles`;
CREATE TABLE `kb_articles` (
  `id` int(11) NOT NULL auto_increment,
  `company_id` int(11) NOT NULL DEFAULT '0',
  `title` varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `excerpt` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tags` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `author` varchar(250) NOT NULL,
  `publisher` int(11) NOT NULL,
  `is_draft` tinyint(4) NOT NULL default '0',
  `access` tinyint(4) NOT NULL default '0',
  `is_ticket_summary` tinyint(4) NOT NULL default '0',
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  `modified_user_id` int(11) NOT NULL,
  `rating` float(7,2) NOT NULL default '0.00',
  `ratingvisitors` int(11) NOT NULL default '0',
  `totalvisitors` int(11) NOT NULL default '0',
  `categoryid` int( 11 ) NOT NULL default '0',
  `includefaq` tinyint( 2 ) NOT NULL default  '0',
  `myorder` int(11) NOT NULL default  '10',
  `seo_desc` text NOT NULL,
  `seo_keywords` text NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `i_company_id` (`company_id`),
  FULLTEXT KEY `title` (`title`, `excerpt`, `content`, `tags`),
  FULLTEXT KEY `tags` (`tags`)
) ENGINE=[MyISAM] DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
# --------------------------------------------------------

#
# Table structure for table `kb_categories`
#

DROP TABLE IF EXISTS `kb_categories`;
CREATE TABLE `kb_categories` (
  `id` int(11) NOT NULL auto_increment,
  `company_id` int(11) NOT NULL DEFAULT '0',
  `name` varchar(100) NOT NULL,
  `description` varchar(100) default NULL,
  `parent_id` int(11) NOT NULL default '0',
  `counter` int(11) NOT NULL default '0',
  `staffonly` tinyint(4) NOT NULL default '0',
  `is_series` TINYINT( 1 ) NOT NULL DEFAULT  '0' COMMENT  'articles are meant to be followed as a series',
  `is_global_series` TINYINT( 1 ) NOT NULL DEFAULT  '0' COMMENT  'view all global series together in sidebar',
  `my_order` INT NOT NULL DEFAULT  '0',
  PRIMARY KEY  (`id`),
  KEY `i_company_id` (`company_id`)
) DEFAULT CHARACTER SET utf8, ENGINE = MYISAM;
# --------------------------------------------------------

#
# Table structure for table `kb_articles_rate`
#

DROP TABLE IF EXISTS `kb_articles_rate`;
CREATE TABLE `kb_articles_rate` (
  `articleid` int(11) NOT NULL,
  `used_ips` varchar(32) NOT NULL,
  PRIMARY KEY  (`articleid`,`used_ips`)
) DEFAULT CHARACTER SET utf8, ENGINE = MYISAM;
# --------------------------------------------------------

#
# Table structure for table `kb_articles_views`
#

DROP TABLE IF EXISTS `kb_articles_views`;
CREATE TABLE `kb_articles_views` (
  `articleid` int(11) NOT NULL,
  `userid` varchar(11) NOT NULL,
  `ip` varchar(100) NOT NULL,
  PRIMARY KEY  (`articleid`,`userid`,`ip`)
) DEFAULT CHARACTER SET utf8, ENGINE=MyISAM;

# --------------------------------------------------------

#
# Table structure for table `kb_articles_comments`
#

DROP TABLE IF EXISTS `kb_articles_comments`;
CREATE TABLE `kb_articles_comments` (
  `commentid` INT(11) NOT NULL AUTO_INCREMENT,
  `articleid` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `added` datetime NOT NULL,
  `email` varchar(250) NOT NULL,
  `comment` text NOT NULL,
  `is_approved` tinyint(4) NOT NULL default '0',
  `is_internal` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`commentid`)
) DEFAULT CHARACTER SET utf8, ENGINE = MYISAM;
# --------------------------------------------------------

#
# Table structure for table `kb_articles_files`
#

DROP TABLE IF EXISTS `kb_articles_files`;
CREATE TABLE `kb_articles_files` (
  `id` int(11) NOT NULL auto_increment,
  `articleid` int(11) NOT NULL,
  `filename` varchar(50) NOT NULL,
  `dateadded` datetime NOT NULL,
  `filekey` varchar(16) NOT NULL,
  `userid` int(11) NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `filekey` (`filekey`)
) DEFAULT CHARACTER SET utf8, ENGINE = MyISAM;
# --------------------------------------------------------

#
# Table structure for table `kb_articles_tickettypes`
#

DROP TABLE IF EXISTS `kb_articles_tickettypes`;
CREATE TABLE `kb_articles_tickettypes` (
    `article_id` INT NOT NULL ,
    `tickettype_id` INT NOT NULL ,
    PRIMARY KEY ( `article_id` , `tickettype_id` )
) DEFAULT CHARACTER SET utf8, ENGINE = MYISAM ;
# --------------------------------------------------------

#
# Table structure for table `kb_articlesrelated`
#

DROP TABLE IF EXISTS `kb_articlesrelated`;
CREATE TABLE `kb_articlesrelated` (
  `articleid` int(11) NOT NULL,
  `relatedid` int(11) NOT NULL,
  PRIMARY KEY  (`articleid`,`relatedid`)
) DEFAULT CHARACTER SET utf8, ENGINE= MyISAM;
