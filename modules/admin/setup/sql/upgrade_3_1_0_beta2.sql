# ---------------------------------------------------------
# INCREASE GROUP NAMES FIELD LENTGTH
# ---------------------------------------------------------
ALTER TABLE `groups` CHANGE `name` `name` VARCHAR(50) NOT NULL;
