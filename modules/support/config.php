<?php

$config = [
    'mandatory' => true,
    'recommended' => true,
    'description' => 'Support management module.',
    'navButtonLabel' => lang('Support'),
    'dependencies' => [],
    'hasSchemaFile' => true,
    'hasInitialData' => true,
    'hasUninstallSQLScript' => false,
    'hasUninstallPHPScript' => false,
    'order' => 5,
    'settingTypes' => [8],
    'hooks' => [
        'Menu' => 'Support_menu',
        'Settings' => [
            'Support_Settings' => [
                'internalName' => 'support',
                'tabLabel' => lang('General'),
                'order' => 6,
            ],
            'Knowledgebase_Settings' => [
                'internalName' => 'knowledgebase',
                'tabLabel' => lang('KB'),
                'order' => 5,
            ],
        ],
        'Events' => [
            'PipedEmail' => 'Support_Event_PipedEmail'
        ]
    ],
    'permissions' => [
        1 => [
            'support_view',
            0,
            true,
            lang('View ticket list')
        ],
        2 => [
            'support_view_assigned_department_tickets',
            1,
            false,
            lang('View tickets assigned to their departments')
        ],
        3 => [
            'support_view_assigned_otherdepartment_tickets',
            1,
            false,
            lang('View tickets assigned to other departments, or unassigned')
        ],
        4 => [
            'support_reply_any_open_ticket',
            1,
            false,
            lang('Reply to any assigned ticket')
        ],
        28 => [
            'support_view_closed_tickets',
            1,
            true,
            lang('View closed tickets')
        ],
        6 => [
            'support_submit_ticket',
            1,
            true,
            lang('Submit ticket')
        ],
        8 => [
            'support_edit_ticket',
            1,
            false,
            lang('Edit ticket')
        ],
        9 => [
            'support_delete_trouble_ticket',
            1,
            false,
            lang('Delete ticket')
        ],
        10 => [
            'support_assign_tickets',
            1,
            false,
            lang('Assign tickets')
        ],
        25 => [
            'support_close_tickets',
            1,
            true,
            lang('Close tickets')
        ],
        26 => [
            'support_view_rates',
            1,
            false,
            lang('View closed tickets service rates')
        ],
        27 => [
            'support_view_eventlog',
            1,
            true,
            lang('View Event Log for Ticket events')
        ],
        29 => [
            'support_view_live_chat',
            0,
            true,
            lang('View Live Chat'),
            'accounts'
        ],
        30 => [
            'support_view_feedback',
            0,
            false,
            lang('View Ticket Feedback'),
            'accounts'
        ],
        31 => [
            'support_manage_spam_filters',
            1,
            false,
            lang('Manage Spam Filters')
        ],
    ],
    'hreftarget' => '#'
];

// language entries referred in this module, but that need to be loaded always
// (e.g. menu item labels)
$lang = [
    lang('Unassigned Tickets'),
    lang('Awaiting Reply'),
    lang('All Open Tickets'),
    lang('All Tickets Closed Today'),
    lang('Internal Billing Issues'),
    lang('Externally Created'),
    lang('General'),
];
