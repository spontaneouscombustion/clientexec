# Remove unused setting
DELETE FROM `setting` WHERE `name`='Archive Invoices After Payment';

# Remove unused permissions
DELETE FROM `permissions` WHERE `permission`='billing_archived_invoices';
DELETE FROM `permissions` WHERE `permission`='billing_archive_invoice';