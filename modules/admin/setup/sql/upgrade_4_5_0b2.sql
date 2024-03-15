CREATE TABLE `companies_plugins` (
  `company_id` int NOT NULL,
  `plugin` varchar(25) NOT NULL,
  `permission` tinyint NOT NULL DEFAULT '0'
) ENGINE=MyISAM;
