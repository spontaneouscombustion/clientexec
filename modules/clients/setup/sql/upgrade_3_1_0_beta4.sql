# ---------------------------------------------------------------------------------------------------------
# NEW CUSTOM FIELD TO STORE IF STAFF MEMBER WANTS TO USE THE RESOLUTION SUMMARY FORM WHEN CLOSING A TICKET
# ---------------------------------------------------------------------------------------------------------
INSERT INTO `customuserfields` VALUES (NULL, 'SHOW_RESOLUTION_SUMMARY_FORM', 46, 0, 0, 0, 20, 0, 0, 0, 0, 0, '');

# ---------------------------------------------------------------------------------------------------------
# ADDED FIELD TO ALLOW ARCHIVING CLIENTS NOTES
# ---------------------------------------------------------------------------------------------------------
ALTER TABLE `clients_notes` ADD `archived` TINYINT NOT NULL DEFAULT '0';
