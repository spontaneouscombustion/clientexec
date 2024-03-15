<?php

require_once 'Linode.class.php';

class PluginLinode extends ServerPlugin
{
    public $features = [
        'packageName' => true,
        'testConnection' => true,
        'showNameservers' => false,
        'directlink' => true
    ];

    public $api;

    public function setup($args)
    {
        $this->api = new Linode(
            $args['server']['variables']['plugin_linode_API_Key']
        );
    }

    public function getVariables()
    {
        $variables = [
            lang("Name") => [
                "type" => "hidden",
                "description" => "Used by CE to show plugin - must match how you call the action function names",
                "value" => "Linode"
            ],
            lang("Description") => [
                "type" => "hidden",
                "description" => lang("Description viewable by admin in server settings"),
                "value" => lang("Linode control panel integration")
            ],
            lang("API Key") => [
                "type" => "text",
                "description" => lang("API Key"),
                "value" => ""
            ],
            lang("VM Password Custom Field") => [
                "type" => "text",
                "description" => lang("Enter the name of the package custom field that will hold the root password."),
                "value" => ""
            ],
            lang("VM Hostname Custom Field") => [
                "type" => "text",
                "description" => lang("Enter the name of the package custom field that will hold the VM hostname."),
                "value" => ""
            ],
            lang("VM MainIp Custom Field") => [
                "type" => "text",
                "description" => lang("Enter the name of the package custom field that will hold the Main IPv4 Address."),
                "value" => ""
            ],
            lang("VM IPv6 Custom Field") => [
                "type" => "text",
                "description" => lang("Enter the name of the package custom field that will hold the IPv6 Address."),
                "value" => ""
            ],
            lang("VM Operating System Custom Field") => [
                "type" => "text",
                "description" => lang("Enter the name of the package custom field that will hold the VM Operating System."),
                "value" => ""
            ],
            lang("VM Location Custom Field") => [
                "type" => "text",
                "description" => lang("Enter the name of the package custom field that will hold the Location/Region"),
                "value" => ""
            ],
            lang("Actions") => [
                "type" => "hidden",
                "description" => lang("Current actions that are active for this plugin per server"),
                "value" => "Create,Delete,Suspend,UnSuspend,Reboot,Boot,Shutdown,Rebuild,Changepass,RescueOn"
            ],
            lang('Registered Actions For Customer') => [
                "type" => "hidden",
                "description" => lang("Current actions that are active for this plugin per server for customers"),
                "value" => "Reboot,Boot,Shutdown,Rebuild,Changepass,RescueOn"
            ],
            lang("reseller") => [
                "type" => "hidden",
                "description" => lang("Whether this server plugin can set reseller accounts"),
                "value" => "0",
            ],
            lang("package_addons") => [
                "type" => "hidden",
                "description" => lang("Supported signup addons variables"),
                "value" => "",
            ],
            lang('package_vars') => [
                'type' => 'hidden',
                'description' => lang('Whether package settings are set'),
                'value' => '0',
            ],
            lang('package_vars_values') => [
                'type'        => 'hidden',
                'description' => lang('VM account parameters'),
                'value'       => array(
                    'plan' => array(
                        'type'        => 'dropdown',
                        'multiple'    => false,
                        'getValues'   => 'getPlans',
                        'label'       => lang('Plan'),
                        'description' => lang('Default Plan'),
                        'value'       => '',
                    ),
                    'swap' => array(
                        'type'        => 'text',
                        'label'       => lang('Swap'),
                        'description' => lang('Default swap size for this plan'),
                        'value'       => '512',
                    ),
                ),
            ],
        ];

        return $variables;
    }


    public function getPlans()
    {
        $plans = [];
        $dir = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'json';
        $hideName = array('.','..','.DS_Store');
        $plans[0] = lang('-- Select VPS Plan --');
        if (file_exists($dir . '/plans.json')) {
            $ServerTypeGetAll = json_decode(file_get_contents($dir . '/plans.json'), true);
            foreach ($ServerTypeGetAll['data'] as $ServerTypeAll) {
                $plans[$ServerTypeAll['id']] = $ServerTypeAll['label'] . ' (' . $ServerTypeAll['vcpus'] . ' (vCPU), ' . ($ServerTypeAll['memory'] / 1024) . ' (GB RAM), ' . ($ServerTypeAll['disk'] / 1024) . ' (GB HDD), $' . $ServerTypeAll['price']['monthly'] . ' / month)';
            }
        }
        return $plans;
    }


    public function validateCredentials($args)
    {
    }

    public function doUpdate($args)
    {
    }


    public function doDelete($args)
    {
        $userPackage = new UserPackage($args['userPackageId']);
        $args = $this->buildParams($userPackage);
        $this->setup($args);
        $this->api->deleteinstance($args['package']['ServerAcctProperties']);
        $userPackage = new UserPackage($args['package']['id']);
        $userPackage->setCustomField('Server Acct Properties', '');
        $userPackage->setCustomField($args['server']['variables']['plugin_linode_VM_Password_Custom_Field'], "", CUSTOM_FIELDS_FOR_PACKAGE);
        $userPackage->setCustomField($args['server']['variables']['plugin_linode_VM_IPv6_Custom_Field'], "", CUSTOM_FIELDS_FOR_PACKAGE);
        $userPackage->setCustomField($args['server']['variables']['plugin_linode_VM_MainIp_Custom_Field'], "", CUSTOM_FIELDS_FOR_PACKAGE);
        $vmHostname = $userPackage->getCustomField($args['server']['variables']['plugin_linode_VM_Hostname_Custom_Field'], CUSTOM_FIELDS_FOR_PACKAGE);
        return $vmHostname . ' has been deleted.';
    }

    public function doSuspend($args)
    {
        $userPackage = new UserPackage($args['userPackageId']);
        $args = $this->buildParams($userPackage);
        $this->setup($args);
        $this->api->shutdownInstance($args['package']['ServerAcctProperties']);
        $vmHostname = $userPackage->getCustomField($args['server']['variables']['plugin_linode_VM_Hostname_Custom_Field'], CUSTOM_FIELDS_FOR_PACKAGE);
        return $vmHostname . ' has been suspended.';
    }

    public function doUnSuspend($args)
    {
        $userPackage = new UserPackage($args['userPackageId']);
        $args = $this->buildParams($userPackage);
        $this->setup($args);
        $this->api->bootInstance($args['package']['ServerAcctProperties']);
        $vmHostname = $userPackage->getCustomField($args['server']['variables']['plugin_linode_VM_Hostname_Custom_Field'], CUSTOM_FIELDS_FOR_PACKAGE);
        return $vmHostname . ' has been unsuspended.';
    }

    public function doBoot($args)
    {
        $userPackage = new UserPackage($args['userPackageId']);
        $args = $this->buildParams($userPackage);
        $this->setup($args);
        $this->api->bootInstance($args['package']['ServerAcctProperties']);
        $vmHostname = $userPackage->getCustomField($args['server']['variables']['plugin_linode_VM_Hostname_Custom_Field'], CUSTOM_FIELDS_FOR_PACKAGE);
        return $vmHostname . ' has been booted.';
    }

    public function doShutdown($args)
    {
        $userPackage = new UserPackage($args['userPackageId']);
        $args = $this->buildParams($userPackage);
        $this->setup($args);
        $this->api->shutdownInstance($args['package']['ServerAcctProperties']);
        $vmHostname = $userPackage->getCustomField($args['server']['variables']['plugin_linode_VM_Hostname_Custom_Field'], CUSTOM_FIELDS_FOR_PACKAGE);
        return $vmHostname . ' has been shutdown.';
    }


    public function doRebuild($args)
    {
        $userPackage = new UserPackage($args['userPackageId']);
        $args = $this->buildParams($userPackage);
        $vmHostname = $userPackage->getCustomField($args['server']['variables']['plugin_linode_VM_Hostname_Custom_Field'], CUSTOM_FIELDS_FOR_PACKAGE);
        $this->setup($args);
        $osname = $userPackage->getCustomField($args['server']['variables']['plugin_linode_VM_Operating_System_Custom_Field'], CUSTOM_FIELDS_FOR_PACKAGE);
        $rootPassword = $this->api->linode_random_password(12);
        $rebuild = $this->api->rebuildinstance($args['package']['ServerAcctProperties'], 'linode/' . $osname, $rootPassword);
        if (!$rebuild['errors']) {
            $userPackage->setCustomField($args['server']['variables']['plugin_linode_VM_Password_Custom_Field'], $rootPassword, CUSTOM_FIELDS_FOR_PACKAGE);
        }
        return $vmHostname . ' has been reinstalled.';
    }

    public function doRescueOn($args)
    {
        $userPackage = new UserPackage($args['userPackageId']);
        $args = $this->buildParams($userPackage);
        $vmHostname = $userPackage->getCustomField($args['server']['variables']['plugin_linode_VM_Hostname_Custom_Field'], CUSTOM_FIELDS_FOR_PACKAGE);
        $this->setup($args);

        $diskList =  $this->api->getInstanceConfigList($args['package']['ServerAcctProperties']);
        foreach ($diskList['data'] as $list) {
            foreach ($list['devices'] as $key => $detail) {
                if (!empty($detail)) {
                    $RescueData['devices'][$key]['disk_id'] = $detail['disk_id'];
                }
            }
        }
        $rescueDisk = $this->api->rescueDisk($args['package']['ServerAcctProperties'], $RescueData);
        if (!$rescueDisk['errors']) {
            return $vmHostname . ' has been booted into rescue mode.';
        } else {
            return $vmHostname . ' has not booted into rescue mode as an error occured';
        }
    }

    public function doChangepass($args)
    {
        $userPackage = new UserPackage($args['userPackageId']);
        $args = $this->buildParams($userPackage);
        $this->setup($args);
        $InstanceInfo = $this->api->getListInstance($args['package']['ServerAcctProperties']);
        if ($InstanceInfo['status'] != 'offline') {
                                $InstanceAction = $this->api->shutdownInstance($args['package']['ServerAcctProperties']);
            while ($this->api->getListInstance($args['package']['ServerAcctProperties'])['status'] == 'shutting_down') {
                sleep(15);
            }
        }
                  $InstancePasswords = $this->api->linode_random_password(12);
                    $DiskInfo = $this->api->getDiskList($args['package']['ServerAcctProperties'])['data'];
        foreach ($DiskInfo as $diskData) {
            if ($diskData['filesystem'] == 'ext4') {
                $disklabel = $diskData['id'];
            }
        }

        $ResetPassword = $this->api->updateRootPassword($args['package']['ServerAcctProperties'], $disklabel, $InstancePasswords);
        if (!$ResetPassword['errors']) {
            $userPackage->setCustomField($args['server']['variables']['plugin_linode_VM_Password_Custom_Field'], $InstancePasswords, CUSTOM_FIELDS_FOR_PACKAGE);
            $this->api->bootInstance($args['package']['ServerAcctProperties']);
        }
        $vmHostname = $userPackage->getCustomField($args['server']['variables']['plugin_linode_VM_Hostname_Custom_Field'], CUSTOM_FIELDS_FOR_PACKAGE);
        return $vmHostname . ' password has been changed.';
    }

    public function doReboot($args)
    {
        $userPackage = new UserPackage($args['userPackageId']);
        $args = $this->buildParams($userPackage);
        $this->setup($args);
        $this->api->rebootInstance($args['package']['ServerAcctProperties']);
        $vmHostname = $userPackage->getCustomField($args['server']['variables']['plugin_linode_VM_Hostname_Custom_Field'], CUSTOM_FIELDS_FOR_PACKAGE);
        return $vmHostname . ' has been rebooted.';
    }

    public function getAvailableActions($userPackage)
    {
        $args = $this->buildParams($userPackage);
        $this->setup($args);
        $actions = [];
        if ($args['package']['ServerAcctProperties'] == '') {
            $actions[] = 'Create';
        } else {
            $foundServer = false;
            $servers = $this->api->getListInstance($args['package']['ServerAcctProperties']);
            if ($servers['status']) {
                $foundServer = true;
                if ($servers['status'] == 'running') {
                    $actions[] = 'Suspend';
                    $actions[] = 'Reboot';
                    $actions[] = 'Shutdown';
                    $actions[] = 'Rebuild';
                    $actions[] = 'RescueOn';
                    $actions[] = 'Changepass';
                } else {
                    $actions[] = 'UnSuspend';
                    $actions[] = 'Boot';
                }
                $actions[] = 'Delete';
            }
            if ($foundServer == false) {
                $actions[] = 'Create';
            }
        }
        return $actions;
    }

    public function doCreate($args)
    {
        $userPackage = new UserPackage($args['userPackageId']);
        $args = $this->buildParams($userPackage);
        $this->setup($args);
        $location = $userPackage->getCustomField($args['server']['variables']['plugin_linode_VM_Location_Custom_Field'], CUSTOM_FIELDS_FOR_PACKAGE);
        $images = $userPackage->getCustomField($args['server']['variables']['plugin_linode_VM_Operating_System_Custom_Field'], CUSTOM_FIELDS_FOR_PACKAGE);
        $name = $userPackage->getCustomField($args['server']['variables']['plugin_linode_VM_Hostname_Custom_Field'], CUSTOM_FIELDS_FOR_PACKAGE);
        $rootPassword = $this->api->linode_random_password(12);
        $swap = $args['package']['variables']['swap'];
        $plan = $args['package']['variables']['plan'];
        $backup = false;
        $privateIP = false;

        $serverId = $this->api->createInstance(
            $name,
            $location,
            $plan,
            $backup,
            $privateIP,
            'linode/' . $images,
            (int)$swap,
            $rootPassword
        );
        if ($serverId['id']) {
            $userPackage->setCustomField('Server Acct Properties', $serverId['id']);
            $foundIp = false;
            while ($foundIp == false) {
                if ($serverId['ipv4'][0] != '0.0.0.0') {
                    $userPackage->setCustomField('IP Address', $serverId['ipv4'][0]);
                    $userPackage->setCustomField('Shared', 0);
                    $userPackage->setCustomField($args['server']['variables']['plugin_linode_VM_Password_Custom_Field'], $rootPassword, CUSTOM_FIELDS_FOR_PACKAGE);
                    $userPackage->setCustomField($args['server']['variables']['plugin_linode_VM_IPv6_Custom_Field'], $serverId['ipv6'], CUSTOM_FIELDS_FOR_PACKAGE);
                    $userPackage->setCustomField($args['server']['variables']['plugin_linode_VM_MainIp_Custom_Field'], $serverId['ipv4'][0], CUSTOM_FIELDS_FOR_PACKAGE);
                            $foundIp = true;
                            break;
                } else {
                    CE_Lib::log(4, "Sleeping for four seconds...");
                    sleep(4);
                }
            }
            $vmHostname = $userPackage->getCustomField($args['server']['variables']['plugin_linode_VM_Hostname_Custom_Field'], CUSTOM_FIELDS_FOR_PACKAGE);
            return $vmHostname . ' has been created.';
        } else {
            $vmHostname = $userPackage->getCustomField($args['server']['variables']['plugin_linode_VM_Hostname_Custom_Field'], CUSTOM_FIELDS_FOR_PACKAGE);
            return $vmHostname . ' An error occured during VPS creation.';
        }
    }

    public function testConnection($args)
    {
        CE_Lib::log(4, 'Testing connection to linode');
        $this->setup($args);
        $response = $this->api->getLinodesPlans();
        if (!is_array($response)) {
            throw new CE_Exception($response);
        }
    }


    public function getDirectLink($userPackage, $getRealLink = true, $fromAdmin = false, $isReseller = false)
    {
        $linkText = $this->user->lang('Web Console');
        $args = $this->buildParams($userPackage);
        $this->setup($args);
        $InstanceInfo = $this->api->getListInstance($args['package']['ServerAcctProperties']);
        $lish_token = $this->api->Instancelish_token($args['package']['ServerAcctProperties'])['lish_token'];
        $getGlishURL =  $this->api->getwebconsoleglish($InstanceInfo['region']);
        $b64Data = base64_encode('host=https://' . $getGlishURL . '&port=8080&encrypt=1&token=' . $lish_token);

        if ($fromAdmin) {
            return [
                'cmd' => 'panellogin',
                'label' => $linkText
            ];
        } elseif ($getRealLink) {
            $url = '../plugins/server/linode/glishconsole.php?tokens=' . $b64Data;
            return array(
                'fa' => 'fa fa-user fa-fw',
                'link' => $url,
                'text' => $linkText,
                'form' => ''
            );
        } else {
            $link = 'plugins/server/linode/glishconsole.php?tokens=' . $b64Data;

            return [
                'fa' => 'fa fa-user fa-fw',
                'link' => $link,
                'text' => $linkText,
                'form' => ''
            ];
        }
    }

    public function dopanellogin($args)
    {
        $userPackage = new UserPackage($args['userPackageId']);
        $response = $this->getDirectLink($userPackage);
        return $response['link'];
    }
}
