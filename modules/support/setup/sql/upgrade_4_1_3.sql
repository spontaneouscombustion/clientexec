# Fix welcome e-mails not having a help id.
UPDATE `autoresponders` SET helpid=1 WHERE type=8 AND helpid=0;