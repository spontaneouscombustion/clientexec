# ----------------------------------------------------
# CLEAR THE SUPPORTEMAIL COLUMN OF TROUBLETICKETS AS THEY MAY HAVE THE WRONG VALUE
# ----------------------------------------------------
UPDATE `troubleticket` SET `support_email` = NULL;
