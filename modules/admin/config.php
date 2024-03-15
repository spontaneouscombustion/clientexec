<?php
    // index=> array(permission, dependency, customer_allowed, companyFeature, description)

    $config = array(
        'mandatory'             => true,
        'description'           => 'Administrator section.',
        'navButtonLabel'        => lang('Settings'),
        'dependencies'          => array(),
        'hasSchemaFile'         => true,
        'hasInitialData'        => true,
        'hasUninstallSQLScript' => false,
        'hasUninstallPHPScript' => false,
        'order'                 => 8,
        'hooks'                 => array(
            'Menu'     => 'Admin_menu',
            'Settings' => array(
                'General_Settings'      => array(
                    'internalName' => 'general',
                    'tabLabel'     => lang('General'),
                    'order'        => 1
                ),
                'Localization_Settings' => array(
                    'internalName' => 'localization',
                    'tabLabel'     => lang('Localization'),
                    'order'        => 4
                ),
                'Captcha_Settings'      => array(
                    'internalName' => 'captcha',
                    'tabLabel'     => lang('Captcha'),
                    'order'        => 4
                ),
                'Email_Settings'        => array(
                    'internalName' => 'email',
                    'tabLabel'     => lang('Mail'),
                    'order'        => 4
                ),
                'Signup_Settings'       => array(
                    'internalName' => 'signup',
                    'tabLabel'     => lang('Signup'),
                    'order'        => 8
                ),
                'Stock_Settings'        => array(
                    'internalName' => 'stock',
                    'tabLabel'     => lang('Stock Control'),
                    'order'        => 8
                ),
                'Upgrade_Settings'      => array(
                    'internalName' => 'upgrade',
                    'tabLabel'     => lang('Upgrade/Downgrade Package Settings'),
                    'order'        => 8
                ),
                'Social_Settings'       => array(
                    'internalName' => 'social',
                    'tabLabel'     => lang('Social Sharing'),
                    'order'        => 8
                ),
                'Passwords_Settings'    => array(
                    'internalName' => 'passwords',
                    'tabLabel'     => lang('Passwords'),
                    'order'        => 6
                ),
                'Domain_Settings'       => array(
                    'internalName' => 'domain',
                    'tabLabel'     => lang('Domains'),
                    'order'        => 9
                ),
                'Style_Settings'        => array(
                    'internalName' => 'style',
                    'tabLabel'     => lang('Style'),
                    'order'        => 10
                ),
                'Bannedips_Settings'    => array(
                    'internalName' => 'bannedips',
                    'tabLabel'     => lang('Banned IPs'),
                    'order'        => 11
                )
            ),
            'Events'   => array(
                'UpdateGateway' => 'Admin_Event_UpdateGateway'
            )
        ),
        'permissions'           => array(
            1  => array('admin_view',                               0, false, lang('View own profile'),               'unrestricted'),
            17 => array('admin_staff_view',                         0, false, lang('View staff profiles'),            'unrestricted'),
            2  => array('admin_edit_passphrase',                    0, false, lang('Manage passphrase'),              'billing'),
            3  => array('admin_edit_settings',                      0, false, lang('Manage settings'),                'unrestricted'),
            5  => array('admin_edit_servers',                       0, false, lang('Manage servers'),                 'products'),
            6  => array('admin_view_packagetypes',                  0, false, lang('View Products'),                  'products'),
            7  => array('admin_edit_packagetypes',                  6, false, lang('Manage Products'),                'products'),
            8  => array('admin_show_custom_fields',                 0, false, lang('Manage custom fields'),           'products'),
            15 => array('admin_edit_announcements',                 0, false, lang('Manage announcements'),           'unrestricted'),
            18 => array('admin_billing_setup',                      0, false, lang('View Billing Setup'),             'billing'),
            10 => array('admin_edit_coupons',                      18, false, lang('Manage coupons'),                 'billing'),
            36 => array('billing_show_billing_types',              18, false, lang('Manage billing types'),           'billing'),
            60 => array('billing_show_billing_cycles',             18, false, lang('Manage billing cycles'),          'billing'),
            12 => array('admin_show_currency_overview',            18, false, lang('Manage currency'),                'billing'),
            13 => array('admin_show_tax_overview',                 18, false, lang('Manage taxes'),                   'billing'),
            37 => array('admin_support_setup',                      0, false, lang('View Support Setup'),             'support'),
            39 => array('support_edit_departments',                37, false, lang('Manage departments'),             'support'),
            43 => array('support_edit_emailroutings',              37, false, lang('Manage email routing rules'),     'support'),
            45 => array('support_edit_tickettypes',                37, false, lang('Manage ticket types'),            'support'),
            4  => array('admin_manage_plugins',                     0, false, lang('Manage Plugins'),                 'unrestricted'),
            46 => array('admin_view_import_export',                 0, false, lang('Manage Importing and Exporting'), 'restricted'),
          //47 => array('admin_manage_webhooks',                    0, false, lang('Manage Webhooks'),                'unrestricted')
            48 => array('admin_manage_customer_group',              0, false, lang('Manage Client Groups'),         'restricted'),
            49 => array('admin_edit_notifications',                 0, false, lang('Manage Notifications'),           'restricted'),
            50 => array('admin_edit_aliases',                       0, false, lang('Manage Aliases'),                 'restricted'),
            51 => array('admin_view_dashboard',                     0, false, lang('Dashboard'),                      'restricted'),
            52 => array('admin_view_dashboard_new_customers',      51, false, lang('View New Clients'),             'restricted'),
            53 => array('admin_view_dashboard_revenue',            51, false, lang('View Revenue'),                   'restricted'),
            54 => array('admin_view_dashboard_invoices_generated', 51, false, lang('View Invoices Generated'),        'restricted'),
            55 => array('admin_view_dashboard_invoices_paid',      51, false, lang('View Invoices Paid'),             'restricted'),
            56 => array('admin_view_dashboard_tickets_opened',     51, false, lang('View Tickets Opened'),            'restricted'),
            57 => array('admin_view_dashboard_tickets_closed',     51, false, lang('View Tickets Closed'),            'restricted'),
            58 => array('admin_view_events',                        0, false, lang('View Events'),                    'restricted'),
            59 => array('admin_delete_events',                     58, false, lang('Delete Events'),                  'restricted')
        ),
        'hreftarget'            => 'index.php?fuse=admin&controller=settings&view=all'
    );

    // language entries referred in this module, but that need to be loaded always
    // (e.g. menu item labels)
    $lang = array(
        lang('Incorrect email or password.&nbsp;&nbsp;If you do not have an account with us please register first.'),
        lang('Incorrect Email and/or password'),
        lang('Password you will use to login to your account'),
    );
