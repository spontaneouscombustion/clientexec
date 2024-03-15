<?php

/**
 * Groups configuration for default Minify implementation
 * @package Minify
 */
/**
 * You may wish to use the Minify URI Builder app to suggest
 * changes. http://yourdomain/min/builder/
 *
 * See http://code.google.com/p/minify/wiki/CustomSource for other ideas
 * */

require_once __DIR__ . '/../../../library/includes.php';
require_once __DIR__ . '/../../../library/config.php';

// defaults
$GLOBALS['langCode'] = 'en';
$GLOBALS['langLabel'] = 'English';

foreach (CE_Lib::getLanguages() as $code => $label) {
    if (isset($_GET['lang']) && $label == strtolower($_GET['lang'])) {
        $GLOBALS['langCode'] = $code;
        $GLOBALS['langLabel'] = $label;
        break;
    }
}

if (!function_exists('getJsLang')) {
    function getJsLang()
    {
        require_once __DIR__ . '/../../../config.php';
        require_once __DIR__ . '/../../../library/constants.php';
        return CE_Lib::getJsLangArr($GLOBALS['langLabel']);
    }
}

return array(
 'admincss' => array(
        // 3rd party libraries
        '//templates/admin/css/bootstrap-combined.no-icons.min.css',
        '//templates/admin/css/font-awesome.min.css',
        '//templates/admin/css/select2.css',
        '//templates/admin/css/datepicker.css',
        '//templates/admin/css/timepicker.css',
        '//templates/admin/css/redactor.css',
        '//templates/admin/css/bootstrapSwitch.css',

        // 1st party styles
        '//templates/admin/style/active_customer.css',
        '//templates/admin/css/common.css', //used for tabs needs migrated to style.css
        '//templates/admin/style/main.css', //settings styles needs migrated to style.css
        '//templates/admin/style/style.css',
        '//templates/admin/css/cur.css',
        '//javascript/richhtml/css/rich.grid.css',
        '//javascript/richhtml/css/rich.window.css',
        '//templates/admin/style/topmenu.css',
        '//templates/admin/style/profile-full-contact.css',
        '//templates/admin/views/support/ticket/viewaddticket.css'
    ),

    'adminbottomjs' => array (
        // 3rd party libraries
        '//templates/admin/js/bootstrap.min.js',
        '//templates/admin/js/jquery.cookie.js',
        '//templates/admin/js/select2.js',
        '//templates/admin/js/bootstrap-datepicker.js',
        '//templates/admin/js/bootstrap-timepicker.js',
        '//javascript/mustache.js',
        '//javascript/jquery.validate.min.js',
        '//javascript/jquery.history.min.js',
        '//templates/admin/js/redactor.js',
        '//javascript/redactor_plugins.js',
        '//templates/admin/js/bootstrapSwitch.js',
        '//templates/admin/js/favico-0.3.3.min.js',

        // 1st party libraries
        '//javascript/richhtml/rich.base.js',
        '//javascript/richhtml/rich.grid.js',
        '//javascript/richhtml/rich.window.js',
        '//templates/admin/js/heartbeat.js',
        '//javascript/common.js',
        '//javascript/customfields.js',
        '//templates/admin/js/common.js',
        '//templates/admin/js/admin.js',
        '//templates/admin/js/vendor/jquery.ui.widget.js',
        '//templates/admin/js/jquery.fileupload.js',
        '//templates/admin/js/clientexec.pluginmanager.js',
    ),
    'admintopjs' => array(
        // 3rd party libraries
        '//javascript/jquery-1.9.1.min.js'
    ),

    'supportwidgetformjs' => array(
        '//javascript/jquery-1.9.1.min.js',
        '//javascript/underscore-min.js',
        '//javascript/mustache.js',
        '//javascript/sammy.min.js',
        '//javascript/sammy.flash.js',
    ),
    'supportwidgetjs' => array(
        '//javascript/external/supportwidget.js',
    ),
    'supportwidgetcss' => array(
        '//javascript/external/supportwidget.css',
    ),
    'installer' => array (
        '//javascript/jquery-1.9.1.min.js',
        '//javascript/installer.js'
    ),
    'language' => new Minify_Source(array(
        'id' => "lang_{$GLOBALS['langCode']}",
        'getContentFunc' => 'getJsLang',
        'contentType' => Minify::TYPE_JS,
        'lastModified' => filemtime(__DIR__ . "/../../../language/javascript-{$GLOBALS['langCode']}.mo"),
    )),
    'admindashboard' => array(
        '//templates/admin/views/home/index/dashboard.css',
        '//templates/admin/css/xcharts.min.css',
        "//templates/admin/css/xcharts-overrides.css"
    )
);
