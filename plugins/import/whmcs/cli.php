<?php

define('NE_ADMIN', true);
define('CE_CLI_IMPORT', true);

chdir(dirname(__FILE__) . '/../../../');

$_POST['db_host'] = $argv[1];
$_POST['db_name'] = $argv[2];
$_POST['db_username'] = $argv[3];
$_POST['db_password'] = $argv[4];
$_POST['db_port'] = $argv[5];
$_POST['cc_encryption_hash'] = $argv[6];

$_GET['controller'] = 'index';
$_GET['fuse'] = 'reports';
$_GET['action'] = 'import';
$_GET['plugin'] = 'whmcs';
$_POST['importer_name'] = 'whmcs';

require 'library/front.php';
