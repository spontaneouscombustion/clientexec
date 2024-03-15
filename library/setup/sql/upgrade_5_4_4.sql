UPDATE `setting` SET `value` = '0' WHERE `name`='plugin_cetransactions_Viewable by all staff';
ALTER TABLE `troubleticket_log` ADD `email` VARCHAR(250) NOT NULL DEFAULT '' AFTER `deletedname`, ADD INDEX (`email`);

INSERT INTO `help` VALUES(69, 'Package Cancellation Tags', '[{"name":"CLIENTNAME","description":"Client''s First and Last Name"},{"name":"PACKAGEREFERENCE","description":"Full reference of the customer package."},{"name":"COMPANYNAME","description":"Company name"},{"name":"COMPANYADDRESS","description":"Company address"},{"name":"CLIENTAPPLICATIONURL","description":"Url to your ClientExec installation"}]', 'Click to view available tags', 240, 320);
INSERT INTO autoresponders (`type`,`name`,subject,contents,description,helpid) VALUES( 4, 'Package Cancellation Requested Template','Cancellation requested for package [PACKAGEREFERENCE]',"<HTML><head></head><body>ATTN: [CLIENTNAME],<br />\n<br />\nA cancellation request has been received for package [PACKAGEREFERENCE].<br />\n<br />\nYou will be notified once the cancellation request has been processed.<br />\n<br />\nIf you have any questions regarding this cancellation please feel free to contact us.<br />\n<br />\nThank you,<br />\n[COMPANYNAME]</body></HTML>",'E-mail sent to a customer regarding a cancellation request for a package.',69);
INSERT INTO autoresponders (`type`,`name`,subject,contents,description,helpid) VALUES( 4, 'Package Cancelled Template','Package [PACKAGEREFERENCE] has been cancelled',"<HTML><head></head><body>ATTN: [CLIENTNAME],<br />\n<br />\nYour package [PACKAGEREFERENCE] has been cancelled.<br />\n<br />\nIf you have any questions regarding this cancellation please feel free to contact us.<br />\n<br />\nThank you,<br />\n[COMPANYNAME]</body></HTML>",'E-mail sent to a customer regarding a cancelled package.',69);