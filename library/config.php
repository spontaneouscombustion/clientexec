<?php

$config = $newedge_config = [
    'mandatory'     => true,
    'description'   => 'The Newedge Framework, over which all modules are built.',
    'dependencies'  => array(),
    'hasSchemaFile' => true,
    'hasInitialData' => true,
    'hasUninstallSQLScript' => false,
    'hasUninstallPHPScript' => false,
    'order' => 0,   // this is hardcoded in the install procedure anyway, to avoid problems.
    'permissions' => array(),
    'hreftarget' => '#'
];

$hostedConfig = [
    'excluded_plugins' => [
        'gateways' => [
            'egold',
            'authnet',
            'bluepay',
            'buypass',
            'ccavenue',
            'ccavenueold',
            'eprocessingnetwork',
            'eway',
            'globalpay',
            'linkpoint',
            'paypalpro',
            'paysystems',
            'psigate',
            'quantum',
            'realauth',
            'offlinecreditcard'
        ],
        'services' => [
            'cedemodbcleaner',
            'distributorbilling',
            'expiringcc',
            'invoicestoprocess',
            'ownedsupportupdater',
            'payasyougobilling',
            'supportexpirenotices',
            'webhook'
        ],
        // 'dashboard' => array(),
        'server' => [
            'castcontrol',
            'hostedce',
            'plesk',
            'skeletonserver',
            'weebly'
        ],
        // 'ssl' => array(),
        // 'registrar' => array()
        'snapin' => [
            'cedemobackup',
            'ceusage',
            'gotomeeting',
            'opensrsform',
            'ownedsupport',
            'uservoice'
        ],
    ]
];

$frameworkConfig = [
    'appName' => 'ClientExec',
    'errorLevel' => E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_WARNING,
    'displayErrors' => 0,
    'dbEngine' => 'mysql',
    'dbDefaultEncoding' => 'utf8',
    'defaultCharSet' => 'utf-8',
    'defaultLanguage' > 'English',
    'defaultTemplate' => 'default',
    'defaultModule' => 'home',
    'defaultModule_Mobile' => 'home',

    // declare here which plugins can have a section in the public section
    // 'plugin type (snapins or services)' => array of plugins
    'hooksPublicSection' => [
        'snapins'   => [
            'phpsysinfo'
        ],
    ],

    // configuration files for each module. If not entered, defaults to config.php
    'specialConfigurations' => [
    ],
    //List of often run actions or views to skip from logging (ajax views that cache data)
    //This only stops the logging of running the views or actions.  Any logging within those actions
    //will still be logged including (Only really affects Log Level 3 where we log views/actions called)
    //will be seen in Log Level 5
    'skipLogging'  => [
        'sidebarticketsummary',
        'updatesessionvar',
        'getEventNotifications',
        'conversationspoll',
        'pulse'
    ],

    // Whitelist of GET requests (fuse/controller/action) that require a CSRF check (aka session hash)
    'CSRF' => [
        ['admin', 'index', 'executeservice'],
        ['admin', 'index', 'Logout'],
        ['knowledgebase', 'index', 'KBGetAttachment'],
        ['support', 'ticket', 'setstatus'],
        ['billing', 'invoice', 'payinvoice'],
    ],

    'SAMEORIGIN' => [
        ['support', 'index', 'supportwidgetform']
    ],

    // this is only for gettext to include the language labels in its files;
    // the actual list of supported langs is extracted at runtime through the
    // CE_Lib::getLanguages() call, based on the files currently under the /language dir
    'languages' => [
        'en' => lang('english'),
        'nl' => lang('dutch'),
        'fr' => lang('french'),
        'pt' => lang('portuguese'),
        'es' => lang('spanish')
    ],

    // List of plugins, that must conform to the standard plugin naming, and derive from the class NE_Plugin.
    'pluginTypes' => [
        'fraud',
        'phoneverification',
        'gateways',
        'registrars',
        'services',
        'snapin',
    ],

    // text you which to mask (hide) in the logs
    //                     array(
    //       text will be replaced with this
    //          =>  regular expression)
    'maskInLog' => [
            '[new_domain_password] => XXX MASKED DOMAIN PASSWORD XXX'
                =>  '/\[new_domain_password\] => .+/',
            '[new_password] => XXX MASKED PASSWORD XXX'
                =>  '/\[new_password\] => .+/',
            '[confirm_password] => XXX MASKED PASSWORD XXX'
                =>  '/\[confirm_password\] => .+/',

            '[passed_password] => XXX MASKED PASSWORD XXX'
                =>  '/\[passed_password\] => .+/',

            '[password_again] => XXX MASKED PASSWORD XXX'
                =>  '/\[password_again\] => .+/',

            '[new_password_confirm] => XXX MASKED PASSWORD XXX'
                =>  '/\[new_password_confirm\] => .+/',

            '[passwd] => XXX MASKED PASSWORD XXX'
                =>  '/\[passwd\] => .+/',

            '[member_password] => XXX MASKED PASSWORD XXX'
                =>  '/\[member_password\] => .+/',

            'password=PASSWORD(\'XXX MASKED PASSWORD XXX\')'
                =>  '/password=PASSWORD\(\'.+\'\)/U',

            'password=OLD_PASSWORD(\'XXX MASKED PASSWORD XXX\')'
                =>  '/password=OLD_PASSWORD\(\'.+\'\)/U',

            'password = \'XXX MASKED PASSWORD XXX\', recurring'
                =>  '/password = \'.+\', recurring/U',

            '[code] => XXX MASKED COUPON CODE XXX'
                =>  '/\[code\] => .+/',

            '[coupon_code] => XXX MASKED COUPON CODE XXX'
                =>  '/\[coupon_code\] => .+/',

            'coupons_code=\'XXX MASKED COUPON CODE XXX\' AND'
                =>  '/coupons_code=\'.+\' AND/U',

            'coupons_code = \'XXX MASKED COUPON CODE XXX\' AND'
                =>  '/coupons_code = \'.+\' AND/U',

            'coupons_code=\'XXX MASKED COUPON CODE XXX\', coupons_quantity'
                =>  '/coupons_code=\'.+\', coupons_quantity/U',

            '[passphrase] => XXX MASKED PASSPHRASE XXX'
                =>  '/\[passphrase\] => .+/',

            '[pp] => XXX MASKED PASSPHRASE XXX'
                =>  '/\[pp\] => .+/',

            '[oldpassphrase] => XXX MASKED PASSPHRASE XXX'
                =>  '/\[oldpassphrase\] => .+/',

            '[newpassphrase] => XXX MASKED PASSPHRASE XXX'
                =>  '/\[newpassphrase\] => .+/',

            '[confirmpassphrase] => XXX MASKED PASSPHRASE XXX'
                =>  '/\[confirmpassphrase\] => .+/',

            'oldpassphrase=XXX MASKED PASSPHRASE XXX&newpassphrase=XXX MASKED PASSPHRASE XXX&view'
                =>  '/oldpassphrase=.+&newpassphrase=.+&view/U',

            '&pp=XXX MASKED PASSPHRASE XXX&view=viewccnumber'
                =>  '/&pp=.+&view=viewccnumber/U',

            '[ccnumber] => XXX MASKED CCNUMBER XXX'
                =>  '/\[ccnumber\] => \d+/',

            '[newccnumber] => XXX MASKED CCNUMBER XXX'
                =>  '/\[newccnumber\] => \d+/',

            '[plugin_ccNumber] => XXX MASKED CCNUMBER XXX'
                =>  '/\[[a-z]+_ccNumber\] => \d+/',

            '[cvv2] => XXX MASKED CVV2 XXX'
                => '/\[cvv2\] => \d+/',

            '[plugin_ccCVV2] => XXX MASKED CVV2 XXX'
                => '/\[[a-z]+_ccCVV2\] => \d+/',

            'data3=\'XXX MASKED CCNUMBER XXX\', data2'
                =>  '/data3=\'.+\', data2/U',

            'data3 = \'XXX MASKED CCNUMBER XXX\', passphrased'
                =>  '/data3 = \'.+\', passphrased/U',

            '[Password] => XXX MASKED PASSWORD XXX'
                =>  '/\[Password\] => .+/',

            '[password] => XXX MASKED PASSWORD XXX'
                =>  '/\[password\] => .+/',

            '[password1] => XXX MASKED PASSWORD XXX'
                =>  '/\[password1\] => .+/',

            '[password2] => XXX MASKED PASSWORD XXX'
                =>  '/\[password2\] => .+/',

            '[CHANGE_PASSWORD] => XXX MASKED PASSWORD XXX'
                =>  '/\[CHANGE_PASSWORD\] => .+/',

            '[DomainPassword] => XXX MASKED PASSWORD XXX'
                =>  '/\[DomainPassword\] => .+/',

            '[domainPassword] => XXX MASKED PASSWORD XXX'
                =>  '/\[domainPassword\] => .+/',

            'password=XXX MASKED PASSWORD XXX&domain'
                =>  '/password=.+&domain/U',

            '&key=XXX MASKED API KEY XXX&version='
                => '/&key=.+&version=/U',

            '&pass=XXX MASKED PASSWORD XXX'
                => '/&pass=.+/',

            '[pass] => XXX MASKED PASSWORD XXX'
                =>  '/\[pass\] => .+/',

            '[apiKey] => XXX MASKED API KEY XXX'
                =>  '/\[apiKey\] => .+/',

            '&pw=XXX MASKED PASSWORD XXX&'
                => '/&pw=.+&/U',
            'oldpassphrase=XXX MASKED PASSPHRASE XXX&newpassphrase=XXX MASKED PASSPHRASE XXX'
                =>  '/oldpassphrase=.+&newpassphrase=.+/',
            '[API Key] => XXX MASKED API KEY XXX'
                =>  '/\[API Key\] => .+/',
            '[plugin_cpanel_Access_Hash_encryptable] => XXX MASKED API TOKEN/HASH XXX'
                =>  '/\[plugin_cpanel_Access_Hash_encryptable\] => .+/',
            '[adminPass] => XXX MASKED ADMIN PASSWORD XXX'
                =>  '/\[adminPass\] => .+/',
            '[ownerPassword] => XXX MASKED OWNER PASSWORD XXX'
                =>  '/\[ownerPassword\] => .+/',
            '[db_password] => XXX MASKED DB PASSWORD XXX'
                =>  '/\[db_password\] => .+/',
            '[cc_encryption_hash] => XXX MASKED CC HASH XXX'
                =>  '/\[cc_encryption_hash\] => .+/',
    ],
    'appVersions' => [
        '2.7.4',
        '2.7.5',
        '2.7.6',
        '2.8.0 beta1',
        '2.8.0 beta2',
        '2.8.0',
        '2.8.1',
        '2.8.2',
        '2.8.3',
        '2.8.4',
        '3.0.0 alpha1',
        '3.0.0 beta3',
        '3.0.0 beta4',
        '3.0.0 PR1',
        '3.0.0 PR2',
        '3.0.0',
        '3.0.1',
        '3.0.2',
        '3.1.0 beta1',
        '3.1.0 beta2',
        '3.1.0 beta3',
        '3.1.0 beta4',
        '3.1.0 RC1',
        '3.1.0',
        '3.1.1',
        '3.1.2',
        '3.1.3',
        '3.1.4',
        '3.2.0 beta1',
        '3.2.0 beta2',
        '3.2.0 beta3',
        '3.2.0 RC1',
        '3.2.0',
        '3.2.1',
        '3.2.2',
        '3.2.3',
        '4.0.0 alpha1',
        '4.0.0b1',
        '4.0.0b2',
        '4.0.0b3',
        '4.0.0rc1',
        '4.0.0rc2',
        '4.0.0',
        '4.0.1',
        '4.0.2',
        '4.0.3',
        '4.0.4',
        '4.0.5',
        '4.0.6',
        '4.0.7',
        '4.0.8',
        '4.0.9',
        '4.0.10',
        '4.1.0a1',
        '4.1.0a2',
        '4.1.0a3',
        '4.1.0a4',
        '4.1.0a5',
        '4.1.0a6',
        '4.1.0b1',
        '4.1.0b2',
        '4.1.b3',
        '4.1.b4',
        '4.1.RC1',
        '4.1.0',
        '4.1.1',
        '4.1.2',
        '4.1.3',
        '4.2',
        '4.2.1',
        '4.3.0b1',
        '4.3.0',
        '4.3.1',
        '4.4.0b1',
        '4.4.0b2',
        '4.4.0b3',
        '4.4.0b4',
        '4.4.0',
        '4.4.1',
        '4.4.2',
        '4.5.0a1',
        '4.5.0a2',
        '4.5.0a3',
        '4.5.0b1',
        '4.5.0b2',
        '4.5.0b3',
        '4.5.0',
        '4.5.1',
        '4.5.2',
        '4.6.0a1',
        '4.6.0b1',
        '4.6.0b2',
        '4.6.0b3',
        '4.6.0',
        '4.6.1',
        '4.6.2',
        '4.6.3',
        '4.6.4',
        '4.6.5',
        '4.6.6',
        '4.6.7',
        '4.6.8',
        '4.6.9',
        '4.6.10',
        '5.0.0a1',
        '5.0.0a2',
        '5.0.0a3',
        '5.0.0a4',
        '5.0.0a5',
        '5.0.0a6',
        '5.0.0a7',
        '5.0.0a8',
        '5.0.0a9',
        '5.0.0a10',
        '5.0.0a11',
        '5.0.0a12',
        '5.0.0a13',
        '5.0.0b1',
        '5.0.0b2',
        '5.0.0b3',
        '5.0.0b4',
        '5.0.0RC',
        '5.0.0RC2',
        '5.0.0RC3',
        '5.0.0RC4',
        '5.0.0',
        '5.0.1',
        '5.0.2',
        '5.1.0a1',
        '5.1.0a2',
        '5.1.0a3',
        '5.1.0a4',
        '5.1.0a5',
        '5.1.0RC1',
        '5.1.0RC2',
        '5.1.0',
        '5.1.1a1',
        '5.1.1',
        '5.1.2a1',
        '5.1.2',
        '5.1.3',
        '5.1.4',
        '5.2.0a1',
        '5.2.0a2',
        '5.2.0b1',
        '5.2.0b2',
        '5.2.0',
        '5.2.1a1',
        '5.2.1',
        '5.2.2a1',
        '5.3.0a1',
        '5.3.0b1',
        '5.3.0b2',
        '5.3.0RC1',
        '5.3.0RC2',
        '5.3.0',
        '5.3.1',
        '5.3.2',
        '5.3.3',
        '5.4.0b1',
        '5.4.0',
        '5.4.1',
        '5.4.2',
        '5.4.3',
        '5.4.4',
        '5.4.5',
        '5.4.6',
        '5.5.0a1',
        '5.5.0RC1',
        '5.5.0RC2',
        '5.5.0',
        '5.5.1',
        '5.5.2',
        '5.5.3',
        '5.5.4',
        '5.6.0a1',
        '5.6.0a2',
        '5.6.0RC1',
        '5.6.0',
        '5.6.1a1',
        '5.6.1a2',
        '5.6.1a3',
        '5.7.0b1',
        '5.7.0b2',
        '5.7.0',
        '5.7.1',
        '5.7.2a1',
        '5.7.2a2',
        '5.7.2a3',
        '5.7.2a4',
        '5.8.0b1',
        '5.8.0',
        '5.8.1',
        '5.8.2a1',
        '5.8.2a2',
        '5.8.2a3',
        '5.9.0b1',
        '5.9.0',
        '5.10.0a1',
        '5.10.0a2',
        '5.10.0b1',
        '5.10.0b2',
        '5.10.0b3',
        '5.10.0',
        '6.0.0',
        '6.0.1a1',
        '6.0.1',
        '6.0.2a1',
        '6.0.2',
        '6.1.0a1',
        '6.1.0b1',
        '6.1.0',
        '6.1.1',
        '6.2.0a1',
        '6.2.0a2',
        '6.2.0a3',
        '6.2.0b1',
        '6.2.0',
        '6.2.1',
        '6.3.0a1',
        '6.3.0a2',
        '6.3.0a3',
        '6.3.0a4',
        '6.3.0a5',
        '6.3.0a6',
        '6.3.0b1',
        '6.3.0b2',
        '6.3.0',
        '6.4.0b1',
        '6.4.0b2',
        '6.4.0b3',
        '6.4.0',
        '6.4.1',
        '6.4.2',
        '6.5.0a1',
        '6.5.0b1',
        '6.5.0b2',
        '6.5.0b3',
        '6.5.0',
        '6.5.1b1',
        '6.5.1b2',
        '6.6.0a1',
        '6.6.0a2',
        '6.6.0a3',
        '6.6.0a4',
        '6.6.0b1',
        '6.6.0',
        '6.6.1a1',
        '6.6.1',
        '6.6.2a1',
        '6.6.2a2',
        '6.7.0a1',
        '6.7.0a2',
        '6.7.0b1',
        '6.7.0b2',
        '6.7.0',
        '6.7.1a1',
        '6.7.1',
    ]
];
