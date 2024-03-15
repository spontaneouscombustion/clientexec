# ---------------------------------------------------------
# CLEAR CACHE
# ---------------------------------------------------------
DELETE FROM cache;

# ---------------------------------------------------------
# CREATED ADDITIONAL WAY OF SHOWING PACKAGE ADDONS
# ---------------------------------------------------------
ALTER TABLE package ADD COLUMN `style` tinyint(4) default '0';

# ---------------------------------------------------------
# CREATED A WAY TO CHOOSE BETWEEN AUTOMATIC/MANUAL PACKAGE ACTIVATION
# ---------------------------------------------------------
ALTER TABLE package ADD COLUMN `automaticactivation` tinyint(4) default '1';

# ---------------------------------
# INCREASED SIZE OF CHECK NUMBER ON INVOICE TABLE
# ---------------------------------

ALTER TABLE `invoice` MODIFY `checknum` VARCHAR( 50 ) NOT NULL ;

# ---------------------------------------------------------
# ADDED MORE TAGS FOR THE WELCOME EMAIL TEMPLATE
# ---------------------------------------------------------

UPDATE `help` SET detail = '[<font class=bodyhighlight>COMPANYNAME</font>] <br> Company name<br>[<font class=bodyhighlight>ACCOUNTINFORMATION</font>]<br> Includes Domain Name, Username, Password, IP<br>[<font class=bodyhighlight>DOMAINNAME</font>]<br> domain name without http://www.<br>[<font class=bodyhighlight>DOMAINUSERNAME</font>]<br> Domain User Name<br>[<font class=bodyhighlight>DOMAINPASSWORD</font>]<br> Domain Password<br>[<font class=bodyhighlight>DOMAINIP</font>]<br> IP Address to Domain<br>[<font class=bodyhighlight>COMPANYURL</font>]<br> URL to your web site<br>[<font class=bodyhighlight>SUPPORTEMAIL</font>]<br> E-mail to support staff<br>[<font class=bodyhighlight>CLIENTAPPLICATIONURL</font>]<br> URL to ClientExec.<br>[<font class=bodyhighlight>FORGOTPASSWORDURL</font>] URL to retrieve forgotten password.<br>[<font class=bodyhighlight>CLIENTNAME</font>]<br>Both first and last name<br>[<font class=bodyhighlight>CLIENTEMAIL</font>]<br>[<font class=bodyhighlight>ORGANIZATION</font>] <br> Client\'s Organization<br>[<font class=bodyhighlight>PLANNAME</font>] <br> Client\'s Plan<br>[<font class=bodyhighlight>NAMESERVERS</font>]<br>lists only hostnames<br>[<font class=bodyhighlight>NAMESERVERSANDIPS</font>]<br>lists both IPs and hostnames<br>[<font class=bodyhighlight>SERVERHOSTNAME</font>]<br>example server1.yourdomain.com<br>[<font class=bodyhighlight>SERVERSHAREDIP</font>]<br>shared IP for server<br>[<font class=bodyhighlight>CUSTOMPROFILE_xxxx</font>]<br>where xxx is custom profile field name<br>[<font class=bodyhighlight>CUSTOMPACKAGE_xxxx</font>]<br>where xxx is custom package field name' WHERE id = 1;

# ---------------------------------------------------------
# ERASED TABLES THAT ARE NO LONGER NECESSARY
# ---------------------------------------------------------
DROP TABLE `groups`;

# ---------------------------------------------------------
# ANNOUCEMENTS TABLE CHANGE
# ---------------------------------------------------------
ALTER TABLE `announcement` CHANGE `postdate` `postdate` DATETIME NULL DEFAULT '0000-00-00 00:00:00';

# ---------------------------------------------------------
# REPORTS TABLE CHANGE FOR DASHBOARD GRAPH SETTING
# ---------------------------------------------------------
ALTER TABLE `report` ADD `dashboard` TINYINT( 1 ) DEFAULT '0' NOT NULL ;
UPDATE `report` SET `dashboard`=1, `public`=1 WHERE `name`='Monthly_Signup_Rate_And_Percentage-Account_&_packages.php' OR `name`='Trouble_Tickets_Opened-Support.php';

# ---------------------------------------------------------
# DELETE HELP ENTRIES MOVED TO HOOKS
# ---------------------------------------------------------
DELETE FROM `help` WHERE `id` = 2;
DELETE FROM `help` WHERE `id` = 3;
DELETE FROM `help` WHERE `id` = 4;
DELETE FROM `help` WHERE `id` = 5;
DELETE FROM `help` WHERE `id` = 7;
DELETE FROM `help` WHERE `id` = 10;
DELETE FROM `help` WHERE `id` = 12;
