<?php
//// index=> array(permission, dependency, customer_allowed, description)

$config = array(
    'mandatory'     => true,
    'recommended'   => true,
    'description'   => 'Extensible report generation framework.',
    'navButtonLabel'=> lang('Reports'),
    'dependencies'  => array(),
    'hasSchemaFile' => false,
    'hasInitialData'=> true,
    'hasUninstallSQLScript' => false,
    'hasUninstallPHPScript' => false,
    'order'         => 7,
    'settingTypes'  => array(5),
    'hooks'         => array(
        'Menu'                  =>  'Reports_menu',
    ),
    'permissions' => array(
        1   => array('reports_view',                        0, false,  lang('View reports list')),
        2   => array('reports_view_non_public_reports',    1, false,  lang('View reports that are not public'))
    ),
    'hreftarget' => '#'
);

// language entries referred in this module, but that need to be loaded always
// (e.g. menu item labels)
$lang = array(
  lang('Diagnostics'),
  lang('Income'),
);

?>
