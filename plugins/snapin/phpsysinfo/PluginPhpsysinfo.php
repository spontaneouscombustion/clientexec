<?php

require_once 'modules/admin/models/SnapinPlugin.php';
require_once 'modules/clients/models/UserPackageGateway.php';

class PluginPhpsysinfo extends SnapinPlugin
{
    public $title = 'Server Stats';
    public $phpsysinfo_ver = "old";
    public $threshold = 90;

    public function init()
    {
        $this->settingsNotes = lang('When enabled this snapin allows your customers to see server information.');
        $this->addMappingForPublicMain("view", "View Server Details", 'Integrate PHPSysInfo in Public Home', 'templates/default/images/main-boxes/view-server-details.png');
        $this->addMappingForTopMenu('public', '', 'view', 'Server Info', 'Integrate PHPSysInfo in Public Top Menu');
    }


    public function getVariables()
    {
        $variables = array(
            lang('Plugin Name')       => array(
                'type'        => 'hidden',
                'description' => '',
                'value'       => 'Server Info',
            ),
            'Public Description'       => array(
                'type'        => 'hidden',
                'description' => 'Description to be seen by public',
                'value'       => 'View Server Details',
            ),
            'Use Image' => array(
                'type'        => 'hidden',
                'value'       => '1'
            )
        );

        return $variables;
    }



    public function view()
    {
        if (isset($_GET['pluginaction'])) {
            $this->view->serverId = filter_var($_GET['serverid'], FILTER_SANITIZE_NUMBER_INT);

            //$this->view->serverId = $this->getParam('serverid', FILTER_SANITIZE_NUMBER_INT);
            switch ($_GET['pluginaction']) {
                case 'showserver':
                    $this->showServer();
                    break;
            }
        }
        $this->showServerDropDown();
    }

    public function showServerDropDown()
    {
        $sql = "SELECT s.id, s.name FROM server s WHERE statsurl != '' ORDER BY s.name";
        $result = $this->db->query($sql);

        $this->view->servers = array();
        while (list($serverid, $servername) = $result->fetch()) {
            if (isset($this->view->serverId) && $this->view->serverId == $serverid) {
                $selected = "SELECTED";
            } else {
                $selected = "";
            }
            $server = array();
            $server['selected'] = $selected;
            $server['id'] = $serverid;
            $server['name'] = $servername;
            if (NE_ADMIN) {
                $server['view'] = 'viewsnapin';
            } else {
                $server['view'] = 'snapin';
            }
            $this->view->servers[] = $server;
        }
    }

    public function showServer()
    {
        $sql = "SELECT s.statsurl, s.name, s.id FROM server s WHERE s.id=?";
        $result = $this->db->query($sql, $this->view->serverId);
        list($statsurl, $servername, $serverid) = $result->fetch();

        $xml = $this->getXML($statsurl);
        if (is_a($xml, 'NE_Error')) {
            $this->view->error = $xml->getMessage();
        } else {
            $this->setVitals($xml);
            $this->setHardware($xml);
            $this->networkUsage($xml);
            $this->memoryUsage($xml);
            $this->fileSystem($xml);
        }
    }

    public function getDashboardData($statsurl)
    {
        $xml = $this->getXML($statsurl);
        if (is_a($xml, 'NE_Error')) {
            return $xml;
        }
        return array(
            'loadAverages'      => $this->getLoadAverages($xml),
            'uptime'            => $this->getUptime($xml),
            'memoryUsedPercent' => $this->getMemUsedPercent($xml),
            'memoryCached'      => $this->getCachedPercent($xml),
            'mounts'            => $this->getMounts($xml),
        );
    }

    public function getPublicOutput()
    {
        require_once 'modules/admin/models/ServerGateway.php';
        $serverGateway = new ServerGateway();
        $result = $serverGateway->getStatsUrlDBIT($this->user, true);
        $output = '';
        while ($row = $result->fetch()) {
            $xml = $this->getXML($row['statsurl']);
            if (is_a($xml, 'NE_Error')) {
                continue;
            }
            $output .= '<li><h4 style="font-weight:bold">Server ' . $row['name'] . '</h4><p><b>Load Average: </b>' . $this->getLoadAverages($xml) . '<br /><b>Uptime: </b>' . $this->getUptime($xml) . '</p></li>';
        }

        if (!$output) {
            return false;
        }

        return "<ul>$output</ul>";
    }

    private function getXML($statsurl)
    {
        require_once 'library/CE/XmlFunctions.php';
        require_once 'library/CE/NE_Network.php';

        $xmldata = NE_Network::curlRequest($this->settings, $statsurl, false, false, true, false);

        if (is_a($xmldata, 'NE_Error')) {
            return $xmldata;
        }

        //need to validate XML before we do anything so that we do not
        //get unexpected errors when using this xmlize function
        $xml = XmlFunctions::xmlize($xmldata, 1);

        if (array_key_exists("tns:phpsysinfo", $xml)) {
            $this->phpsysinfo_ver = "3.x";
        } else {
            $this->phpsysinfo_ver = "old";
        }


        if (isset($xml['tns:phpsysinfo']['#']['Options'][0]['@']['threshold'])) {
            $this->threshold = $xml['tns:phpsysinfo']['#']['Options'][0]['@']['threshold'];
        }

        $this->view->threshold = $this->threshold;

        return $xml;
    }

    private function memoryUsage($xml)
    {
        if ($this->phpsysinfo_ver == "3.x") {
            //Getting Physical Memory
            //we get these in kilobytes so we convert to byte before passing to this function
            $memfree = $this->convertByte(floatval($xml["tns:phpsysinfo"]["#"]["Memory"][0]["@"]["Free"]), "");
            $memtotal = $this->convertByte(floatval($xml["tns:phpsysinfo"]["#"]["Memory"][0]["@"]["Total"]), "");

            //Get Kernal + Application
            //we get these in kilobytes so we convert to byte before passing to this function
            $kernused = $this->convertByte(floatval($xml["tns:phpsysinfo"]["#"]["Memory"][0]["#"]["Details"][0]["@"]["App"]), "");
            $kernpercent = $xml["tns:phpsysinfo"]["#"]["Memory"][0]["#"]["Details"][0]["@"]["AppPercent"];

            //Get Buffers
            //we get these in kilobytes so we convert to byte before passing to this function
            $buffused = $this->convertByte(floatval($xml["tns:phpsysinfo"]["#"]["Memory"][0]["#"]["Details"][0]["@"]["Buffers"]), "");
            $buffpercent = $xml["tns:phpsysinfo"]["#"]["Memory"][0]["#"]["Details"][0]["@"]["BuffersPercent"];

            //Get Cached
            //we get these in kilobytes so we convert to byte before passing to this function
            $cachedused = $this->convertByte(floatval($xml["tns:phpsysinfo"]["#"]["Memory"][0]["#"]["Details"][0]["@"]["Cached"]), "");

            // Get Disk Swap
            //we get these in kilobytes so we convert to byte before passing to this function
            // some systems don't show swap info so gotta make sure
            if ((isset($xml['tns:phpsysinfo']["#"]["Memory"][0]["#"]["Swap"][0]['@']['Used']))) {
                $swapused = $this->convertByte(floatval($xml["tns:phpsysinfo"]["#"]["Memory"][0]["#"]["Swap"][0]["@"]["Used"]), "");
            } else {
                $swapused = $this->user->lang('NA');
            }

            if ((isset($xml['tns:phpsysinfo']["#"]["Memory"][0]["#"]["Swap"][0]['@']['Free']))) {
                $swapfree = $this->convertByte(floatval($xml["tns:phpsysinfo"]["#"]["Memory"][0]["#"]["Swap"][0]["@"]["Free"]), "");
            } else {
                $swapfree = $this->user->lang('NA');
            }
            if ((isset($xml['tns:phpsysinfo']["#"]["Memory"][0]["#"]["Swap"][0]['@']['Total']))) {
                $swaptotal = $this->convertByte(floatval($xml["tns:phpsysinfo"]["#"]["Memory"][0]["#"]["Swap"][0]["@"]["Total"]), "");
            } else {
                $swaptotal = $this->user->lang('NA');
            }

            if ((isset($xml['tns:phpsysinfo']["#"]["Memory"][0]["#"]["Swap"][0]['@']['Percent']))) {
                $swappercent = $xml["tns:phpsysinfo"]["#"]["Memory"][0]["#"]["Swap"][0]["@"]["Percent"];
            } else {
                $swappercent = $this->user->lang('NA');
            }
        } else {
            //Getting Physical Memory
            //we get these in kilobytes so we convert to byte before passing to this function
            $memfree = $this->convertByte(floatval($xml["phpsysinfo"]["#"]["Memory"][0]["#"]["Free"][0]["#"]) * 1024, "");
            $memtotal = $this->convertByte(floatval($xml["phpsysinfo"]["#"]["Memory"][0]["#"]["Total"][0]["#"]) * 1024, "");

            //Get Kernal + Application
            //we get these in kilobytes so we convert to byte before passing to this function
            $kernused = $this->convertByte(floatval($xml["phpsysinfo"]["#"]["Memory"][0]["#"]["App"][0]["#"]) * 1024, "");
            $kernpercent = $xml["phpsysinfo"]["#"]["Memory"][0]["#"]["AppPercent"][0]["#"];

            //Get Buffers
            //we get these in kilobytes so we convert to byte before passing to this function
            $buffused = $this->convertByte(floatval($xml["phpsysinfo"]["#"]["Memory"][0]["#"]["Buffers"][0]["#"]) * 1024, "");
            $buffpercent = $xml["phpsysinfo"]["#"]["Memory"][0]["#"]["BuffersPercent"][0]["#"];

            //Get Cached
            //we get these in kilobytes so we convert to byte before passing to this function
            $cachedused = $this->convertByte(floatval($xml["phpsysinfo"]["#"]["Memory"][0]["#"]["Cached"][0]["#"]) * 1024, "");

            // Get Disk Swap
            //we get these in kilobytes so we convert to byte before passing to this function
            // some systems don't show swap info so gotta make sure
            if ((isset($xml['phpsysinfo']["#"]["Swap"][0]['#']['Used']))) {
                $swapused = $this->convertByte(floatval($xml["phpsysinfo"]["#"]["Swap"][0]["#"]["Used"][0]["#"]) * 1024, "");
            } else {
                $swapused = $this->user->lang('NA');
            }
            if ((isset($xml['phpsysinfo']["#"]["Swap"][0]['#']['Free']))) {
                $swapfree = $this->convertByte(floatval($xml["phpsysinfo"]["#"]["Swap"][0]["#"]["Free"][0]["#"]) * 1024, "");
            } else {
                $swapfree = $this->user->lang('NA');
            }
            if ((isset($xml['phpsysinfo']["#"]["Swap"][0]['#']['Total']))) {
                $swaptotal = $this->convertByte(floatval($xml["phpsysinfo"]["#"]["Swap"][0]["#"]["Total"][0]["#"]) * 1024, "");
            } else {
                $swaptotal = $this->user->lang('NA');
            }
            if ((isset($xml['phpsysinfo']["#"]["Swap"][0]['#']['Percent']))) {
                $swappercent = $xml["phpsysinfo"]["#"]["Swap"][0]["#"]["Percent"][0]["#"];
            } else {
                $swappercent = $this->user->lang('NA');
            }
        }

        //Set Vitals
        $this->view->assign(array(
            'SYSINFO_MEMFREE'       => $memfree,
            'SYSINFO_MEMUSED'       => $this->getMemUsed($xml),
            'SYSINFO_MEMTOTAL'      => $memtotal,
            'SYSINFO_MEMPERCENT'    => round($this->getMemUsedPercent($xml)),
            'SYSINFO_KERNUSED'      => $kernused,
            'SYSINFO_KERNPERCENT'   => round($kernpercent),
            'SYSINFO_BUFFUSED'      => $buffused,
            'SYSINFO_BUFFPERCENT'   => round($buffpercent),
            'SYSINFO_CACHUSED'      => $cachedused,
            'SYSINFO_CACHPERCENT'   => round($this->getCachedPercent($xml)),
            'SYSINFO_SWAPFREE'      => $swapfree,
            'SYSINFO_SWAPUSED'      => $swapused,
            'SYSINFO_SWAPTOTAL'     => $swaptotal,
            'SYSINFO_SWAPPERCENT'   => round($swappercent),
            'totalMemUsed'          => round($kernpercent + $buffpercent + $this->getCachedPercent($xml))
        ));
    }

    private function getMemUsed(&$xml)
    {
        if ($this->phpsysinfo_ver == "3.x") {
            return $this->convertByte(floatval($xml["tns:phpsysinfo"]["#"]["Memory"][0]["@"]["Used"]), "");
        } else {
            return $this->convertByte(floatval($xml["phpsysinfo"]["#"]["Memory"][0]["#"]["Used"][0]["#"]) * 1024, "");
        }
    }

    private function getMemUsedPercent(&$xml)
    {
        if ($this->phpsysinfo_ver == "3.x") {
            return $xml["tns:phpsysinfo"]["#"]["Memory"][0]["@"]["Percent"];
        } else {
            return $xml["phpsysinfo"]["#"]["Memory"][0]["#"]["Percent"][0]["#"];
        }
    }

    private function getCachedPercent(&$xml)
    {
        if ($this->phpsysinfo_ver == "3.x") {
            return $xml["tns:phpsysinfo"]["#"]["Memory"][0]["#"]["Details"][0]["@"]["CachedPercent"];
        } else {
            return $xml["phpsysinfo"]["#"]["Memory"][0]["#"]["CachedPercent"][0]["#"];
        }
    }

    private function fileSystem($xml)
    {
        $this->view->mounts = array();
        $mounts = $this->getMounts($xml);

        for ($i = 0; $i < sizeof($mounts); $i++) {
            $mount = $mounts[$i];
            if ($this->phpsysinfo_ver == "3.x") {
                $mounttype  = $mount["@"]["FSType"];
                $mountpart = $mount["@"]["Name"];

                //we get these in kilobytes so we convert to byte before passing to this function
                $mountfree = $this->convertByte(floatval($mount["@"]["Free"]), "0.00 KB");
                $mountused = $this->convertByte(floatval($mount["@"]["Used"]), "0.00 KB");
                $mountsize = $this->convertByte(floatval($mount["@"]["Total"]), "0.00 KB");
            } else {
                $mounttype  = $mount["#"]["Type"][0]["#"];
                if (is_array($mount["#"]["Device"][0]["#"])) {
                    $mountpart = $mount["#"]["Device"][0]["#"]['Name'][0]['#'];
                } else {
                    $mountpart = $mount["#"]["Device"][0]["#"];
                }

                //we get these in kilobytes so we convert to byte before passing to this function
                $mountfree = $this->convertByte(floatval($mount["#"]["Free"][0]["#"]) * 1024, "0.00 KB");
                $mountused = $this->convertByte(floatval($mount["#"]["Used"][0]["#"]) * 1024, "0.00 KB");
                $mountsize = $this->convertByte(floatval($mount["#"]["Size"][0]["#"]) * 1024, "0.00 KB");
            }


            $barColor = 'bg-info';
            if ($this->getMountPercent($mount) >= $this->threshold) {
                $barColor = 'bg-danger';
            }

            $mountArray = array(
                'SYSINFO_MOUNTPOINT'        => $this->getMountPoint($mount),
                'SYSINFO_MOUNTTYPE'         => $mounttype,
                'SYSINFO_MOUNTPARTITION'    => $mountpart,
                'SYSINFO_MOUNTPERCENT'      => $this->getMountPercent($mount),
                'SYSINFO_MOUNTFREE'         => $mountfree,
                'SYSINFO_MOUNTUSED'         => $mountused,
                'SYSINFO_MOUNTSIZE'         => $mountsize,
                'barColor'                  => $barColor
            );

            $this->view->mounts[] = $mountArray;
        }
    }

    private function getMounts(&$xml)
    {
        if ($this->phpsysinfo_ver == "3.x") {
            if (!isset($xml["tns:phpsysinfo"]["#"]["FileSystem"][0]["#"]["Mount"])) {
                return array();
            }
            return $xml["tns:phpsysinfo"]["#"]["FileSystem"][0]["#"]["Mount"];
        } else {
            if (!isset($xml["phpsysinfo"]["#"]["FileSystem"][0]["#"]["Mount"])) {
                return array();
            }

            return $xml["phpsysinfo"]["#"]["FileSystem"][0]["#"]["Mount"];
        }
    }

    private function getMountPoint($mount)
    {
        if ($this->phpsysinfo_ver == "3.x") {
            return $mount["@"]["MountPoint"];
        } else {
            return $mount["#"]["MountPoint"][0]["#"];
        }
    }

    private function getMountPercent($mount)
    {
        if ($this->phpsysinfo_ver == "3.x") {
            return $mount["@"]["Percent"];
        } else {
            return $mount["#"]["Percent"][0]["#"];
        }
    }

    private function networkUsage($xml)
    {
        $this->view->netDevices = [];

        if ($this->phpsysinfo_ver == "3.x") {
            if (@array_key_exists('NetDevice', $xml["tns:phpsysinfo"]["#"]["Network"][0]["#"])) {
                $netdevices = $xml["tns:phpsysinfo"]["#"]["Network"][0]["#"]["NetDevice"];

                for ($i = 0; $i < sizeof($netdevices); $i++) {
                    $netdevice = $netdevices[$i];

                    $name   = $netdevice["@"]["Name"];
                    $rxbytes    = $this->convertByte($netdevice["@"]["RxBytes"], "0.00 KB");
                    $txbytes    = $this->convertByte($netdevice["@"]["TxBytes"], "0.00 KB");
                    $errors     = $netdevice["@"]["Err"];
                    $drops  = $netdevice["@"]["Drops"];

                    $this->view->netDevices[] = array(
                        'SYSINFO_DEVICENAME' => $name,
                        'SYSINFO_DEVICERECEIVED'   => $rxbytes,
                        'SYSINFO_DEVICESENT' => $txbytes,
                        'SYSINFO_DEVICEERR'   => $errors,
                        'SYSINFO_DEVICEDROP'   => $drops,
                    );
                }
            }
        } else {
            if (@array_key_exists('NetDevice', $xml["phpsysinfo"]["#"]["Network"][0]["#"])) {
                $netdevices = $xml["phpsysinfo"]["#"]["Network"][0]["#"]["NetDevice"];
                for ($i = 0; $i < sizeof($netdevices); $i++) {
                    $netdevice = $netdevices[$i];
                    $name   = $netdevice["#"]["Name"][0]["#"];
                    $rxbytes    = $this->convertByte($netdevice["#"]["RxBytes"][0]["#"], "0.00 KB");
                    $txbytes    = $this->convertByte($netdevice["#"]["TxBytes"][0]["#"], "0.00 KB");
                    if (array_key_exists('Err', $netdevice["#"])) {
                        $errors = $netdevice["#"]["Err"][0]["#"];
                    } else {
                        $errors = $netdevice["#"]["Errors"][0]["#"];
                    }
                    $drops  = $netdevice["#"]["Drops"][0]["#"];

                    $this->view->netDevices[] = array(
                        'SYSINFO_DEVICENAME' => $name,
                        'SYSINFO_DEVICERECEIVED'   => $rxbytes,
                        'SYSINFO_DEVICESENT' => $txbytes,
                        'SYSINFO_DEVICEERR'   => $errors,
                        'SYSINFO_DEVICEDROP'   => $drops,
                    );
                }
            }
        }
    }

    private function setHardware($xml)
    {
        if ($this->phpsysinfo_ver == "3.x") {
           //Getting Server Information
            $processors = sizeof($xml["tns:phpsysinfo"]["#"]["Hardware"][0]["#"]["CPU"][0]["#"]["CpuCore"]);
            $cpumodel = $xml["tns:phpsysinfo"]["#"]["Hardware"][0]["#"]["CPU"][0]["#"]["CpuCore"][0]["@"]["Model"];
            $cpuspeed = round(floatval($xml["tns:phpsysinfo"]["#"]["Hardware"][0]["#"]["CPU"][0]["#"]["CpuCore"][0]["@"]["CpuSpeed"]) / 1000, 2);
            if (isset($xml["tns:phpsysinfo"]["#"]["Hardware"][0]["#"]["CPU"][0]["#"]["CpuCore"][0]["@"]["Cache"])) {
                $cpucache = $xml["tns:phpsysinfo"]["#"]["Hardware"][0]["#"]["CPU"][0]["#"]["CpuCore"][0]["@"]["Cache"];
                $cpucache = $this->convertByte(floatval($cpucache), "0.00 KB");
            } else {
                $cpucache = 'unavailable';
            }
        } else {
            $processors = $xml["phpsysinfo"]["#"]["Hardware"][0]["#"]["CPU"][0]["#"]["Number"][0]["#"];
            $cpumodel = $xml["phpsysinfo"]["#"]["Hardware"][0]["#"]["CPU"][0]["#"]["Model"][0]["#"];
            $cpuspeed = round(floatval($xml["phpsysinfo"]["#"]["Hardware"][0]["#"]["CPU"][0]["#"]["Cpuspeed"][0]["#"]) / 1000, 2);
            if (isset($xml["phpsysinfo"]["#"]["Hardware"][0]["#"]["CPU"][0]["#"]["Cache"][0]["#"])) {
                $cpucache = $xml["phpsysinfo"]["#"]["Hardware"][0]["#"]["CPU"][0]["#"]["Cache"][0]["#"];
                $cpucache = $this->convertByte(floatval($cpucache) * 1024, "0.00 KB");
            } else {
                $cpucache = 'unavailable';
            }
        }



        if ($cpuspeed > 0.00) {
            $cpuspeed = $cpuspeed . " GHz";
        }


        //Set Vitals
        $this->view->assign(array(
            'SYSINFO_PROCESSORS' => $processors,
            'SYSINFO_CPUMODEL' => $cpumodel,
            'SYSINFO_CPUSPEED' => $cpuspeed,
            'SYSINFO_CPUCACHESIZE' => $cpucache,
        ));
    }

    private function setVitals($xml)
    {
        if ($this->phpsysinfo_ver == "3.x") {
            //Getting Vitals
            $hostname = $xml["tns:phpsysinfo"]["#"]["Vitals"][0]["@"]["Hostname"];
            $ipaddress = $xml["tns:phpsysinfo"]["#"]["Vitals"][0]["@"]["IPAddr"];
            $per_cpuload = $xml["tns:phpsysinfo"]["#"]["Vitals"][0]["@"]["CPULoad"];
            $distroname = $xml["tns:phpsysinfo"]["#"]["Vitals"][0]["@"]["Distro"];
            $kernalver = $xml["tns:phpsysinfo"]["#"]["Vitals"][0]["@"]["Kernel"];
            $currentusers = $xml["tns:phpsysinfo"]["#"]["Vitals"][0]["@"]["Users"];
        } else {
            //Getting Vitals
            $hostname = $xml["phpsysinfo"]["#"]["Vitals"][0]["#"]["Hostname"][0]["#"];
            $ipaddress = $xml["phpsysinfo"]["#"]["Vitals"][0]["#"]["IPAddr"][0]["#"];
            $per_cpuload = $xml["phpsysinfo"]["#"]["Vitals"][0]["#"]["CPULoad"][0]["#"];
            $distroname = $xml["phpsysinfo"]["#"]["Vitals"][0]["#"]["Distro"][0]["#"];
            $kernalver = $xml["phpsysinfo"]["#"]["Vitals"][0]["#"]["Kernel"][0]["#"];
            $currentusers = $xml["phpsysinfo"]["#"]["Vitals"][0]["#"]["Users"][0]["#"];
        }

        //Set Vitals
        $this->view->assign(array(
            'SYSINFO_HOSTNAME'      => $hostname,
            'SYSINFO_LISTENINGIP'   => $ipaddress,
            'SYSINFO_UPTIME'        => $this->getUptime($xml),
            'SYSINFO_LOADAVERAGE'   => $this->getLoadAverages($xml),
            'SYSINFO_CPULOAD'       => round($per_cpuload),
            'SYSINFO_DISTRONAME'    => $distroname,
            'SYSINFO_KERNALVER'     => $kernalver,
            'SYSINFO_CURRENTUSERS'  => $currentusers,
        ));
    }

    private function getLoadAverages(&$xml)
    {
        if ($this->phpsysinfo_ver == "3.x") {
            return $xml["tns:phpsysinfo"]["#"]["Vitals"][0]["@"]["LoadAvg"];
        } else {
            return $xml["phpsysinfo"]["#"]["Vitals"][0]["#"]["LoadAvg"][0]["#"];
        }
    }

    private function getUptime(&$xml)
    {
        if ($this->phpsysinfo_ver == "3.x") {
            return $this->parseUptime($xml["tns:phpsysinfo"]["#"]["Vitals"][0]["@"]["Uptime"]);
        } else {
            return $this->parseUptime($xml["phpsysinfo"]["#"]["Vitals"][0]["#"]["Uptime"][0]["#"]);
        }
    }

    //Make Human Readable
    private function convertByte($bytes, $emptyStr)
    {

        /*
        1 KiloByte == 1024 bytes
        1 MegaByte == 1024 KiloByte
        1 GigaByte == 1024 MegaByte
        */

         $amount = $bytes / 1024;
         $symbol = "KB";

        if ($amount > 1024) {
            $symbol = "MB";
            $amount = $amount / 1024;
        }

        if ($amount > 1024) {
            $symbol = "GB";
            $amount = $amount / 1024;
        }

        $returnStr = ($amount == 0) ? $emptyStr : sprintf("%01.2f", round($amount, 2)) . " " . $symbol;
        return $returnStr;
    }

    private function parseUptime($tuptime)
    {
         $uptimeString = "";
         $secs = floor($tuptime) % 60;
         $mins = floor($tuptime / 60) % 60;
         $hours = floor($tuptime / 3600) % 24;
         $days = floor($tuptime / 86400);

        if ($days > 0) {
            $uptimeString .= $days;
            $uptimeString .= ($days == 1) ? " day " : " days ";
        }

        if ($hours > 0) {
            $uptimeString .= $hours;
            $uptimeString .= ($hours == 1) ? " hour " : " hours ";
        }

        if ($mins > 0) {
            $uptimeString .= $mins;
            $uptimeString .= ($mins == 1) ? " minute " : " minutes ";
        }

         $uptimeString .= $secs;
         $uptimeString .= ($secs == 1) ? " second " : " seconds ";

         return $uptimeString;
    }
}
