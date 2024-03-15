<?php
require_once 'modules/reports/models/ReportsGateway.php';
/**
* @package Clients
*/
class Reports_menu extends NE_MenuHook
{

    var $width = "575px;";
    var $offset = "-340px;";
    var $direction = "left";

    function __construct($user)
    {

        if (!$user->hasPermission("reports_view")) {
            return;
        }


        $typeArray = array();
        $fileArray = array();

        if (is_dir("plugins/reports")) {
            if ($dirhandle = opendir("plugins/reports")) {
                $reportsGateway = new ReportsGateway($user);
                while (($file = readdir($dirhandle)) !== false) {
                    if (($file != '.svn') && ($file != '.') && ($file != '..') && ($file != basename($_SERVER['PHP_SELF']))) {
                        if (is_dir("plugins/reports" . "/" . $file)) {
                            $reportsGateway->readReportForType("plugins/reports" . "/" . $file, $file, $fileArray, $typeArray);
                        }
                    }
                }
                closedir($dirhandle);
            }
        }

        // Sort each menu section so they are in alphabetical order
        sort($fileArray['Accounts']);
        sort($fileArray['Diagnostics']);
        sort($fileArray['Income']);
        sort($fileArray['Support']);
        sort($fileArray['Knowledgebase']);

        $report_count = 0;
        $key_count = 1;

        $typeArray = array("Accounts", "Diagnostics",  "Income", "Support",  "Knowledgebase"); // referred to in config.php

        $report_keys = array();
        $report_keys['first'] = array("accounts","diagnostics");
        $report_keys['second'] = array("income");
        $report_keys['third'] = array("support","knowledgebase");

        foreach ($typeArray as $type) {
            $hasSubMenus = false;
            $menuItem = new NE_MenuItem($user->lang($type));

            if ($report_count > 5) {
                $key_count++;
                $report_count = 0;
            }

            if (in_array(strtolower($type), $report_keys['first'])) {
                $menuItem->setKey("first");
            } elseif (in_array(strtolower($type), $report_keys['second'])) {
                $menuItem->setKey("second");
            } elseif (in_array(strtolower($type), $report_keys['third'])) {
                $menuItem->setKey("third");
            } else {
                $menuItem->setKey("fourth");
            }

            foreach ($fileArray as $key => $name) {
                if ($key == $type) {
                    $submenu = new NE_MenuHook($user);
                    foreach ($name as $innerName) {
                        $report_count ++;
                        $reportName = str_replace(" ", "_", $innerName);
                        $reportNameDB = $reportName."-".ucfirst(str_replace(' ', '_', strtolower($type))).".php";
                        $reportNameFile = $type."/".$reportName.".php";
                        include_once('plugins/reports/' . $reportNameFile);

                        $tReport = new $reportName();
                        $tReport->setName($reportNameDB);
                        $tReport->setUser($user);
                        $tReport->PopulateSettings();

                        if (($tReport->getPublic() == 1 || $user->hasPermission('reports_view_non_public_reports') )) {
                            $hasSubMenus = true;
                            $submenuItem = new NE_MenuItem($user->lang($innerName), "index.php?fuse=reports&view=viewreport&controller=index&report=" . urlencode($innerName) . "&type=" . urlencode($type));
                            $submenu->addItem($submenuItem);
                        }
                    }
                    $menuItem->addSubmenu($submenu);
                }
            }

            $menuItem->addViews(array("viewreport"));
            $menuItem->addSecondaryTag("type", $type);
            if ($hasSubMenus == true) {
                $this->addItem($menuItem);
            }
        }
    }
}
