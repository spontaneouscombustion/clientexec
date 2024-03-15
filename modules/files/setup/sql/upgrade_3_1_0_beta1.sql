# ---------------------------------------------------------
# NEW FILE ATTRIBUTE
# ---------------------------------------------------------

ALTER TABLE `files` ADD `public` INT(11) NOT NULL DEFAULT '0' AFTER `visible`;
ALTER TABLE `files_cats` ADD `public` INT(11) NOT NULL DEFAULT '0' AFTER `visible`;
