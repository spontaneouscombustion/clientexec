# ---------------------------------------------------------
# WHEN EDITING THE CUSTOMERS ACCESS GROUP IT WAS BEING SET
# AS ADMIN
# ---------------------------------------------------------
UPDATE `groups` SET isadmin=0 WHERE id=1;
