# ---------------------------------------------------------
# TICKET ADITIONAL NOTIFICATION TABLE
# ---------------------------------------------------------

CREATE TABLE `troubleticket_additionalnotification` (
  `id` int(11) NOT NULL auto_increment,
  `ticketid` int(11) NOT NULL,
  `userid` int(11) NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE(`ticketid`,`userid`)   
) ENGINE=MyISAM AUTO_INCREMENT=1 ;
