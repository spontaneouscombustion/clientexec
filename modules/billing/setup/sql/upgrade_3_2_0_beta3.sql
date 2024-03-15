# ---------------------------------------------------------
# INCREASE INVOICE TRANSACTION ID AS REQUIRED FOR SOME PROCESSORS
# ---------------------------------------------------------
ALTER TABLE `invoicetransaction` CHANGE `transactionid` `transactionid` VARCHAR( 100 ) NOT NULL DEFAULT 'NA';
