<?php
// index=> array(permission, dependency, customer_allowed, description)

$config = array(
    'mandatory'     => true,
    'description'   => 'Dashboard section.',
    'navButtonLabel'=> lang('Dashboard'),
    //'dependencies'  => array('newedge' => '4.1.0a1'),
    'dependencies'  => array(),
    'hasSchemaFile' => false,
    'hasInitialData'=> true,
    'hasUninstallSQLScript' => false,
    'hasUninstallPHPScript' => false,
    'order'         => 1,
    'settingTypes'  => array(11),
    'hooks'         => array(
        'Menu'                          =>  'Home_menu'
    ),
    'permissions' => array(),
    'hreftarget' => 'index.php?fuse=home&view=dashboard'
);

?>
