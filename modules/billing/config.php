<?php

$config = array(
    'mandatory' => true,
    'description' => 'Billing functionality.',
    'navButtonLabel' => lang('Billing'),
    'dependencies' => array(),
    'hasSchemaFile' => false,
    'hasInitialData' => true,
    'hasUninstallSQLScript' => false,
    'hasUninstallPHPScript' => false,
    'order' => 4,
    'settingTypes' => array(4),
    'hooks' => [
        'Menu' =>  'Billing_menu',
        'Settings' => [
            'Billing_Settings' => [
                'internalName' => 'billing',
                'tabLabel' => lang('Billing'),
                'order'  => 5,
            ],
            'Invoicing_Settings' => [
                'internalName' => 'invoicing',
                'tabLabel' => lang('Billing'),
                'order' => 5,
            ]
        ]
    ],
    'permissions' => [
        54  => [
            'billing_automatic_cc_charge',
            0,
            true,
            lang('Manage automatic credit card charging'), 'products'
        ],
        55 => [
            'billing_renew_package',
            0,
            true,
            lang('Renew Package'),
            'products'
        ],
        17 => [
            'billing_view',
            0,
            true,
            lang('View active invoices')
        ],
        30 => [
            'billing_create',
            17,
            false,
            lang('Edit, delete and add invoices')
        ],
        31 => [
            'billing_recurring_overview',
            17,
            true,
            lang('View "Recurring Overview"')
        ],
        32 => [
            'billing_edit_recurring',
            31,
            false,
            lang('Edit recurring invoice')
        ],
        33 => [
            'billing_delete_recurring',
            31,
            false,
            lang('Delete recurring charge')
        ],
        34 => [
            'billing_mark_invoice_paid',
            17,
            false,
            lang('Mark invoice paid or unpaid')
        ],
        35 => [
            'billing_send_invoices',
            17,
            true,
            lang('Send invoices and receipts')
        ],
        39 => [
            'billing_process_invoices',
            17,
            false,
            lang('Process invoices')
        ],
        40 => [
            'billing_generate_invoices',
            17,
            false,
            lang('Generate pending invoices and run batch payments')
        ],
        41 => [
            'billing_refund_invoices',
            17,
            false,
            lang('Refund invoices')
        ],
        42 => [
            'billing_void_invoices',
            17,
            false,
            lang('Void invoices')
        ],
        45 => [
            'billing_add_variable_payment',
            17,
            false,
            lang('Add variable payment to an invoice')
        ],
        46 => [
            'billing_apply_account_credit',
            17,
            true,
            lang('Apply account credit to an invoice')
        ],
        47 => [
            'billing_credit_invoices',
            17,
            false,
            lang('Credit invoices to credit balance')
        ],
        56 => [
            'billing_masspay',
            0,
            true,
            lang('Pay via Mass Pay'),
            'products'
        ]
    ],
    'hreftarget' => '#'
);

$lang = array(
    lang('Paid'),
    lang('Status NA'),
    lang('Not Paid'),
    lang('Voided'),
    lang('Partially Paid'),
    lang('Credited'),
    lang('Refunded'),
    lang('Pending'),
    lang('Draft'),
    lang('1 Day'),
    lang('1 Week'),
    lang('1 Month'),
    lang('1 Year'),
);
