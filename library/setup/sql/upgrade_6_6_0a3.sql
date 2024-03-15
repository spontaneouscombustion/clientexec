UPDATE `invoiceentry` SET `detail` = REPLACE(`detail`, "Applies to: <br>", "") WHERE `detail` LIKE "%Applies to: <br>%" AND `billingtypeid` = -3 AND `price_percent` = 0;
UPDATE `invoiceentry` SET `detail` = REPLACE(`detail`, "Applies to: all<br>", "") WHERE `detail` LIKE "%Applies to: all<br>%" AND `billingtypeid` = -3 AND `price_percent` = 0;

UPDATE `recurringfee` SET `detail` = REPLACE(`detail`, "Applies to: <br>", "") WHERE `detail` LIKE "%Applies to: <br>%" AND `billingtypeid` = -3 AND `amount_percent` = 0;
UPDATE `recurringfee` SET `detail` = REPLACE(`detail`, "Applies to: all<br>", "") WHERE `detail` LIKE "%Applies to: all<br>%" AND `billingtypeid` = -3 AND `amount_percent` = 0;

DELETE FROM `customuserfields` WHERE `type` IN (63, 64);
UPDATE `customuserfields` SET `inSettings` = 1 WHERE `type` IN (2, 3, 4, 5, 11, 12);