<?php

$errors = array();

if (version_compare(PHP_VERSION, '7.4.0', '<')) {
    $errors[] = 'PHP Version 7.4.0 or above is required';
}

if (!extension_loaded('ionCube Loader')) {
    $errors[] = 'You are missing the ionCube Loader';
}

if (function_exists('ioncube_loader_version')) {
    if (version_compare(ioncube_loader_version(), '11.0', '<')) {
        $errors[] = 'You are using an old version of the ionCube Loader';
    }
}

if ((isset($_REQUEST['missingExtension']) && $_REQUEST['missingExtension'] == true) || (count($errors) > 0)) {
    $requiredExtensions = ['pdo_mysql', 'mysqli', 'curl', 'mbstring', 'json', 'SimpleXML', 'iconv', 'soap'];
    $missingExtensions = [];
    foreach ($requiredExtensions as $extension) {
        if (!extension_loaded($extension)) {
            $missingExtensions[] = $extension;
        }
    }
    if (count($missingExtensions) > 0 || count($errors) > 0) {
        include 'templates/default/views/home/indexpublic/dependencyerrors.phtml';
        die();
    } else {
        header('Location: index.php');
    }
}

require 'library/front.php';
