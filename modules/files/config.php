<?php
// index=> array(permission, dependency, customer_allowed, description)

$config = array(
    'mandatory'     => true,
    'description'   => 'File manager.',
    'navButtonLabel'=> lang('Files'),
    'dependencies'  => array(),
    'hasSchemaFile' => true,
    'hasInitialData'=> true,
    'hasUninstallSQLScript' => false,
    'hasUninstallPHPScript' => false,
    'order'         => 6,
    'settingTypes'  => array(),
    'hooks'         => array(
        'Menu'          =>  'Files_menu',
    ),
    'permissions' => array(
        //1   => array('files_view',              0, true,   lang('View files for which the user has explicit permission')),
        //2   => array('files_admin',             1, false,  lang('Manage files and directories')),
    ),
    'hreftarget' => '#'
);

?>
