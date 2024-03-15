# ---------------------------------------------------------
# CHANGED TYPE FOR CUSTOM FIELD WIDTH
# ---------------------------------------------------------

ALTER TABLE `customuserfields` CHANGE `width` `width` MEDIUMINT NOT NULL DEFAULT '20';

# ---------------------------------------------------------
# ADDED MORE TYPES FOR CUSTOM USER FIELDS
# ---------------------------------------------------------

UPDATE `customuserfields` SET type=31 WHERE name='TABLESORT_snapshot_invoices';
UPDATE `customuserfields` SET type=32 WHERE name='TABLESORT_snapshot_cc';
UPDATE `customuserfields` SET type=33 WHERE name='TABLESORT_snapshot_tickets';
UPDATE `customuserfields` SET type=34 WHERE name='TABLESORT_snapshot_pending';
UPDATE `customuserfields` SET type=35 WHERE name='TABLESORT_snapshot_uninvoiced';
UPDATE `customuserfields` SET type=36 WHERE name='DASHBOARD_INVOICEFILTER';
UPDATE `customuserfields` SET type=37 WHERE name='selectedDashboardTab';
UPDATE `customuserfields` SET type=38 WHERE name='DASHBOARD_TICKETFILTER';
UPDATE `customuserfields` SET type=39 WHERE name='DASHBOARD_LASTUSEDSNAPSHOT';

# ---------------------------------------------------------
# ALLOW QUICK REPORTS
# ---------------------------------------------------------
INSERT INTO `customuserfields` VALUES (NULL, 'QUICK_REPORTS', 40, 0, 0, 0, 20, 0, 0, 0, 0, 0, '');

# ---------------------------------------------------------
# NEW CUSTOM FIELD TO STORE THE LAST USED DASHBOARD GRAPH
# ---------------------------------------------------------

INSERT INTO `customuserfields` VALUES (NULL, 'DASHBOARD_LASTUSEDGRAPH', 41, 0, 0, 0, 20, 0, 0, 0, 0, 0, '');


# ---------------------------------------------------------
# NEW PERMISSIONS
# ---------------------------------------------------------

INSERT INTO `permissions` (`subject_id`, `is_group`, `permission`, `target_id`) VALUES (1, 1, 'clients_edit_customers', 0);
INSERT INTO `permissions` (`subject_id`, `is_group`, `permission`, `target_id`) VALUES (1, 1, 'clients_view', 0);
INSERT INTO `permissions` (`subject_id`, `is_group`, `permission`, `target_id`) VALUES (1, 1, 'clients_view_domains', 0);
INSERT INTO `permissions` (`subject_id`, `is_group`, `permission`, `target_id`) VALUES (1, 1, 'clients_view_server_info', 0);
INSERT INTO `permissions` (`subject_id`, `is_group`, `permission`, `target_id`) VALUES (3, 1, 'clients_email_customers', 0);
INSERT INTO `permissions` (`subject_id`, `is_group`, `permission`, `target_id`) VALUES (3, 1, 'clients_view', 0);
INSERT INTO `permissions` (`subject_id`, `is_group`, `permission`, `target_id`) VALUES (3, 1, 'clients_view_customers', 0);
INSERT INTO `permissions` (`subject_id`, `is_group`, `permission`, `target_id`) VALUES (3, 1, 'clients_view_domains', 0);
INSERT INTO `permissions` (`subject_id`, `is_group`, `permission`, `target_id`) VALUES (3, 1, 'clients_view_server_info', 0);
INSERT INTO `permissions` (`subject_id`, `is_group`, `permission`, `target_id`) VALUES (4, 1, 'clients_create_customers', 0);
INSERT INTO `permissions` (`subject_id`, `is_group`, `permission`, `target_id`) VALUES (4, 1, 'clients_create_customer_packages', 0);
INSERT INTO `permissions` (`subject_id`, `is_group`, `permission`, `target_id`) VALUES (4, 1, 'clients_edit_customers', 0);
INSERT INTO `permissions` (`subject_id`, `is_group`, `permission`, `target_id`) VALUES (4, 1, 'clients_edit_customer_packages', 0);
INSERT INTO `permissions` (`subject_id`, `is_group`, `permission`, `target_id`) VALUES (4, 1, 'clients_email_customers', 0);
INSERT INTO `permissions` (`subject_id`, `is_group`, `permission`, `target_id`) VALUES (4, 1, 'clients_view', 0);
INSERT INTO `permissions` (`subject_id`, `is_group`, `permission`, `target_id`) VALUES (4, 1, 'clients_view_customers', 0);
INSERT INTO `permissions` (`subject_id`, `is_group`, `permission`, `target_id`) VALUES (4, 1, 'clients_view_domains', 0);
INSERT INTO `permissions` (`subject_id`, `is_group`, `permission`, `target_id`) VALUES (4, 1, 'clients_view_server_info', 0);
INSERT INTO `permissions` (`subject_id`, `is_group`, `permission`, `target_id`) VALUES (5, 1, 'clients_create_customers', 0);
INSERT INTO `permissions` (`subject_id`, `is_group`, `permission`, `target_id`) VALUES (5, 1, 'clients_create_customer_packages', 0);
INSERT INTO `permissions` (`subject_id`, `is_group`, `permission`, `target_id`) VALUES (5, 1, 'clients_edit_customers', 0);
INSERT INTO `permissions` (`subject_id`, `is_group`, `permission`, `target_id`) VALUES (5, 1, 'clients_edit_customer_packages', 0);
INSERT INTO `permissions` (`subject_id`, `is_group`, `permission`, `target_id`) VALUES (5, 1, 'clients_email_customers', 0);
INSERT INTO `permissions` (`subject_id`, `is_group`, `permission`, `target_id`) VALUES (5, 1, 'clients_view', 0);
INSERT INTO `permissions` (`subject_id`, `is_group`, `permission`, `target_id`) VALUES (5, 1, 'clients_view_customers', 0);
INSERT INTO `permissions` (`subject_id`, `is_group`, `permission`, `target_id`) VALUES (5, 1, 'clients_view_domains', 0);
INSERT INTO `permissions` (`subject_id`, `is_group`, `permission`, `target_id`) VALUES (5, 1, 'clients_view_server_info', 0);
