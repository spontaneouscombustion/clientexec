# Update some currencies separators
UPDATE `currency` SET `decimalssep` = ',', `thousandssep` = 'space' WHERE `abrv` = 'SEK';
UPDATE `currency` SET `decimalssep` = ',', `thousandssep` = 'space' WHERE `abrv` = 'NOK';
UPDATE `currency` SET `decimalssep` = ',', `thousandssep` = 'space' WHERE `abrv` = 'DKK';
UPDATE `currency` SET `decimalssep` = ',', `thousandssep` = 'space' WHERE `abrv` = 'ISK';