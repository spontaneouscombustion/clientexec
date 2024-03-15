<?php
// index=> array(permission, dependency, customer_allowed, description, companyFeature (optional))

$config = array(
    'mandatory'     => true,
    'description'   => 'Client management section.',
    'navButtonLabel'=> lang('Accounts'),
    'dependencies'  => array(),
    'hasSchemaFile' => true,
    'hasInitialData'=> true,
    'hasUninstallSQLScript' => false,
    'hasUninstallPHPScript' => false,
    'order'         => 2,
    'settingTypes'  => array(),
    'hooks'         => array(
        'Menu'                              =>  'Clients_menu'
    ),
    'permissions' => array(
        1   => array('clients_view',                        0,  true,  lang('View customer data'), 'accounts'),
        2   => array('clients_view_customers',              1,  false, lang('View "Customer List"'), 'accounts'),
        3   => array('clients_view_domains',                1,  true,  lang('View packages'), 'products'),
        49  => array('clients_cancel_packages',             3,  true,  lang('Cancel packages'), 'products'),
        50  => array('clients_send_welcome_email',          3,  true,  lang('Send welcome email'), 'products'),
        51  => array('clients_change_package_password',     3,  true,  lang('Change package password'), 'products'),
        52  => array('clients_trigger_package_actions',     3,  true,  lang('Trigger package actions'), 'products'),
        56  => array('clients_upgrade_customer_packages',   3,  true,  lang('Upgrade/Downgrade packages'), 'products'),
        4   => array('clients_edit_customer_packages',      3,  false, lang('Edit customer packages'), 'products'),
        5   => array('clients_create_customer_packages',    4,  false, lang('Create and delete packages'), 'products'),
        6   => array('clients_edit_customers',              1,  true,  lang('Edit customer profile'), 'accounts'),
        9   => array('clients_create_customers',            6,  false, lang('Create customers'), 'accounts'),
        15   => array('clients_delete_customers',           6,  false, lang('Delete customers'), 'accounts'),
        10   => array('clients_passphrase_cc',              6,  false, lang('Validate and view customer credit card number'), 'billing'),
        11   => array('clients_edit_credit_card',           1,  true,  lang('Edit customer credit card'), 'billing'),
        12   => array('clients_email_customers',            1,  false, lang('Send emails to customers'), 'accounts'),
        13   => array('clients_add_notes',                  1,  false, lang('Add notes to customer'), 'accounts'),
        14   => array('clients_delete_notes',               1,  false, lang('Manage customer notes'), 'accounts'),
        16   => array('clients_view_eventlog',              1,  false, lang('View Event Log for Customer and Package events'), 'accounts'),
        43   => array('clients_view_as_client',             1,  false, lang('View as customer'), 'accounts'),
        44   => array('clients_edit_payment_type',          1,  true,  lang('Change payment method'), 'billing'),
        48   => array('clients_edit_account_credit',        1,  false, lang('Edit "credit balance" field'), 'billing'),
        53   => array('clients_view_pending_order',         3,  false, lang('View Pending Orders'), 'products'),
        54   => array('clients_view_announcements',         0,  true, lang('View Announcements')),
        55   => array('clients_view_emails',                1,  true, lang('View Email History')),
    ),
    'hreftarget' => '#'
);

$lang = array(
  lang('General'),
  lang('Hosting'),
  lang('SSL Cert.'),
  lang('Domain Name'),
  lang('Select the box and enter a name to create this account as an organization instead of an individual.')
);
