# ---------------------------------------------------------
# RENAMING A FIELD IN THE `promotion` TABLE, FOR A MORE WIDE USE
# ---------------------------------------------------------
ALTER TABLE `promotion` CHANGE `regdomain` `type` SMALLINT(1) NOT NULL DEFAULT '0';

# ---------------------------------------------------------
# UPDATING THE VALUE OF THE RENAMED FIELD IN THE `promotion` TABLE ACCORDING TO THE NEW USE
#   `type` = 2 [DOMAIN] FOR THE `promotion`.`regdomain` = 1
#   `type` = 1 [HOSTING] FOR THE OTHER VALUES OF `promotion`.`regdomain`
#   `type` = 0 [GENERAL] WILL BE USED LATER FOR NEW PRODUCTS.
# ---------------------------------------------------------
UPDATE `promotion` SET `type` = 2 WHERE `type` = 1;
UPDATE `promotion` SET `type` = 1 WHERE `type` != 2;