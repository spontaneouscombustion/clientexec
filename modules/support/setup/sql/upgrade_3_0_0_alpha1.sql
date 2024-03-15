# ---------------------------------------------------------
# NEW PERMISSIONS
# ---------------------------------------------------------

INSERT INTO `permissions` (`subject_id`, `is_group`, `permission`, `target_id`) VALUES (1, 1, 'support_reopen_ticket', 0);
INSERT INTO `permissions` (`subject_id`, `is_group`, `permission`, `target_id`) VALUES (1, 1, 'support_submit_ticket', 0);
INSERT INTO `permissions` (`subject_id`, `is_group`, `permission`, `target_id`) VALUES (1, 1, 'support_view', 0);
INSERT INTO `permissions` (`subject_id`, `is_group`, `permission`, `target_id`) VALUES (3, 1, 'support_close_tickets', 0);
INSERT INTO `permissions` (`subject_id`, `is_group`, `permission`, `target_id`) VALUES (3, 1, 'support_delete_trouble_ticket', 0);
INSERT INTO `permissions` (`subject_id`, `is_group`, `permission`, `target_id`) VALUES (3, 1, 'support_edit_ticket', 0);
INSERT INTO `permissions` (`subject_id`, `is_group`, `permission`, `target_id`) VALUES (3, 1, 'support_reopen_ticket', 0);
INSERT INTO `permissions` (`subject_id`, `is_group`, `permission`, `target_id`) VALUES (3, 1, 'support_submit_ticket', 0);
INSERT INTO `permissions` (`subject_id`, `is_group`, `permission`, `target_id`) VALUES (3, 1, 'support_view', 0);
INSERT INTO `permissions` (`subject_id`, `is_group`, `permission`, `target_id`) VALUES (3, 1, 'support_view_all_closed_tickets', 0);
INSERT INTO `permissions` (`subject_id`, `is_group`, `permission`, `target_id`) VALUES (4, 1, 'support_close_tickets', 0);
INSERT INTO `permissions` (`subject_id`, `is_group`, `permission`, `target_id`) VALUES (4, 1, 'support_delete_trouble_ticket', 0);
INSERT INTO `permissions` (`subject_id`, `is_group`, `permission`, `target_id`) VALUES (4, 1, 'support_edit_ticket', 0);
INSERT INTO `permissions` (`subject_id`, `is_group`, `permission`, `target_id`) VALUES (4, 1, 'support_reopen_ticket', 0);
INSERT INTO `permissions` (`subject_id`, `is_group`, `permission`, `target_id`) VALUES (4, 1, 'support_view', 0);
INSERT INTO `permissions` (`subject_id`, `is_group`, `permission`, `target_id`) VALUES (4, 1, 'support_view_all_closed_tickets', 0);
INSERT INTO `permissions` (`subject_id`, `is_group`, `permission`, `target_id`) VALUES (5, 1, 'support_assign_tickets', 0);
INSERT INTO `permissions` (`subject_id`, `is_group`, `permission`, `target_id`) VALUES (5, 1, 'support_close_tickets', 0);
INSERT INTO `permissions` (`subject_id`, `is_group`, `permission`, `target_id`) VALUES (5, 1, 'support_delete_trouble_ticket', 0);
INSERT INTO `permissions` (`subject_id`, `is_group`, `permission`, `target_id`) VALUES (5, 1, 'support_edit_ticket', 0);
INSERT INTO `permissions` (`subject_id`, `is_group`, `permission`, `target_id`) VALUES (5, 1, 'support_reopen_ticket', 0);
INSERT INTO `permissions` (`subject_id`, `is_group`, `permission`, `target_id`) VALUES (5, 1, 'support_reply_any_open_ticket', 0);
INSERT INTO `permissions` (`subject_id`, `is_group`, `permission`, `target_id`) VALUES (5, 1, 'support_submit_ticket', 0);
INSERT INTO `permissions` (`subject_id`, `is_group`, `permission`, `target_id`) VALUES (5, 1, 'support_view', 0);
INSERT INTO `permissions` (`subject_id`, `is_group`, `permission`, `target_id`) VALUES (5, 1, 'support_view_all_closed_tickets', 0);
INSERT INTO `permissions` (`subject_id`, `is_group`, `permission`, `target_id`) VALUES (5, 1, 'support_view_assigned_others_tickets', 0);

# ---------------------------------------------------------
# MARK TICKET REPLIES COMMING THROUGH THE SUPPORTPIPE
# ---------------------------------------------------------
ALTER TABLE `troubleticket_log` ADD `externalemail` TINYINT NOT NULL DEFAULT '0' AFTER `userid` ;
