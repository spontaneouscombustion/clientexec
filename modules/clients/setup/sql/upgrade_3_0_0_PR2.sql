# --------------------------------------------------------
# CHANGED PASSWORD FIELD TYPE (FIX SOME MYSQL ISSUES)
# --------------------------------------------------------
ALTER TABLE `domains` CHANGE `password` `password` TINYBLOB NOT NULL;


# --------------------------------------------------------
# ADDED NEW PERMISSIONS
# --------------------------------------------------------
INSERT INTO `permissions` (`subject_id`, `is_group`, `permission`, `target_id`) VALUES (1, 1, 'clients_edit_credit_card', 0);
INSERT INTO `permissions` (`subject_id`, `is_group`, `permission`, `target_id`) VALUES (4, 1, 'clients_edit_credit_card', 0);
INSERT INTO `permissions` (`subject_id`, `is_group`, `permission`, `target_id`) VALUES (5, 1, 'clients_edit_credit_card', 0);


# --------------------------------------------------------
# ADDED DEFAULT VALUE NEW TROUBLE TICKET AUTORESPONDER SUBJECT TEMPLATE
# --------------------------------------------------------
UPDATE `setting` SET `value` = 'A new support ticket has been added' WHERE `name` LIKE 'New Trouble Ticket Autoresponder Subject Template' AND `value` LIKE '';