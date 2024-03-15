<?php

require_once 'Vultr.class.php';

class PluginVultr extends ServerPlugin
{
    public $features = [
        'packageName' => false,
        'testConnection' => true,
        'showNameservers' => false,
        'directlink' => true
    ];

    public $api;

    public function setup($args)
    {
        $this->api = new Vultr($args['server']['variables']['plugin_vultr_API_Key']);
    }

    public function getVariables()
    {
        $variables = [
            lang("Name") => [
                "type" => "hidden",
                "description" => "Used by CE to show plugin - must match how you call the action function names",
                "value" => "Vultr"
            ],
            lang("Description") => [
                "type" => "hidden",
                "description" => lang("Description viewable by admin in server settings"),
                "value" => lang("Vultr Cloud control panel integration")
            ],
            lang("API Key") => [
                "type" => "text",
                "description" => lang("API Key"),
                "value" => "",
                "encryptable" => true
            ],
            lang("VM Userlogin Custom Field") => [
                "type" => "text",
                "description" => lang("Enter the name of the package custom field that will hold the user name."),
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
            lang("VM IPv6 Custom Field") => [
                "type" => "text",
                "description" => lang("Enter the name of the package custom field that will hold the IPv6 Address."),
                "value" => ""
            ],
            lang("VM MainIp Custom Field") => [
                "type" => "text",
                "description" => lang("Enter the name of the package custom field that will hold the Main IPv4 Address."),
                "value" => ""
            ],
            lang("Actions") => [
                "type" => "hidden",
                "description" => lang("Current actions that are active for this plugin per server"),
                "value" => "Create,Delete,Suspend,UnSuspend,Reboot,Boot,Shutdown,Console,Rebuild"
            ],
            lang('Registered Actions For Customer') => [
                "type" => "hidden",
                "description" => lang("Current actions that are active for this plugin per server for customers"),
                "value" => "Reboot,Boot,Shutdown,Console,Rebuild"
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
                        'description' => '',
                        'value'       => '',
                    ),
                ),
            ],
        ];

        return $variables;
    }

    public function validateCredentials($args)
    {
    }

    public function doUpdate($args)
    {
    }

    public function testConnection($args)
    {
        $this->setup($args);
        $response = $this->api->allplans();
        CE_Lib::log(4, 'Testing connection to Vultr: ' .   $response);
        if (!is_array($response)) {
            throw new CE_Exception($response);
        }
    }

    public function doDelete($args)
    {
        $userPackage = new UserPackage($args['userPackageId']);
        $args = $this->buildParams($userPackage);

        $this->setup($args);
        $this->api->deleteInstance($args['package']['ServerAcctProperties']);

        $userPackage->setCustomField('Server Acct Properties', '');

        $vmHostname = $userPackage->getCustomField(
            $args['server']['variables']['plugin_vultr_VM_Hostname_Custom_Field'],
            CUSTOM_FIELDS_FOR_PACKAGE
        );
        return $vmHostname . ' has been deleted.';
    }

    public function doRebuild($args)
    {
        $userPackage = new UserPackage($args['userPackageId']);
        $args = $this->buildParams($userPackage);

        $this->setup($args);
        $vmHostname = $userPackage->getCustomField(
            $args['server']['variables']['plugin_vultr_VM_Hostname_Custom_Field'],
            CUSTOM_FIELDS_FOR_PACKAGE
        );
        $this->api->reinstallInstance($args['package']['ServerAcctProperties'], $vmHostname);

        return $vmHostname . ' has been reinstalled.';
    }

    public function doCreate($args)
    {
        $userPackage = new UserPackage($args['userPackageId']);
        $args = $this->buildParams($userPackage);

        $this->setup($args);
        $regionId = $userPackage->getCustomField(
            $args['server']['variables']['plugin_vultr_VM_Location_Custom_Field'],
            CUSTOM_FIELDS_FOR_PACKAGE
        );
        $os_id = $userPackage->getCustomField(
            $args['server']['variables']['plugin_vultr_VM_Operating_System_Custom_Field'],
            CUSTOM_FIELDS_FOR_PACKAGE
        );
        $hostname = $userPackage->getCustomField(
            $args['server']['variables']['plugin_vultr_VM_Hostname_Custom_Field'],
            CUSTOM_FIELDS_FOR_PACKAGE
        );

        $planId = $args['package']['variables']['plan'];

        $serverId = $this->api->createInstance($regionId, $planId, $hostname, $os_id)['instance'];
        if ($serverId['id']) {
            $userPackage->setCustomField('Server Acct Properties', $serverId['id']);
            sleep(50);
            $server = $this->api->getInstance($serverId['id'])['instance'];
            if ($server['main_ip'] != '0.0.0.0') {
                $server = $this->api->getInstance($serverId['id'])['instance'];
                $userPackage->setCustomField('IP Address', $server['main_ip']);
                $userPackage->setCustomField('Shared', 0);
                $userPackage->setCustomField(
                    $args['server']['variables']['plugin_vultr_VM_Password_Custom_Field'],
                    $serverId['default_password'],
                    CUSTOM_FIELDS_FOR_PACKAGE
                );
                $userPackage->setCustomField(
                    $args['server']['variables']['plugin_vultr_VM_MainIp_Custom_Field'],
                    $server['main_ip'],
                    CUSTOM_FIELDS_FOR_PACKAGE
                );
                $userPackage->setCustomField(
                    $args['server']['variables']['plugin_vultr_VM_IPv6_Custom_Field'],
                    $server["v6_main_ip"] . ' ( ' . $server["v6_network"] . ' / ' . $server["v6_network_size"] . ' )',
                    CUSTOM_FIELDS_FOR_PACKAGE
                );
                $userPackage->setCustomField(
                    $args['server']['variables']['plugin_vultr_VM_Userlogin_Custom_Field'],
                    'root',
                    CUSTOM_FIELDS_FOR_PACKAGE
                );
            }
        }
        $vmHostname = $userPackage->getCustomField(
            $args['server']['variables']['plugin_vultr_VM_Hostname_Custom_Field'],
            CUSTOM_FIELDS_FOR_PACKAGE
        );
        return $vmHostname . ' has been created.';
    }

    public function doSuspend($args)
    {
        $userPackage = new UserPackage($args['userPackageId']);
        $args = $this->buildParams($userPackage);
        $this->setup($args);
        $this->api->instancePostAction($args['package']['ServerAcctProperties'], 'halt');
        $vmHostname = $userPackage->getCustomField(
            $args['server']['variables']['plugin_vultr_VM_Hostname_Custom_Field'],
            CUSTOM_FIELDS_FOR_PACKAGE
        );
        return $vmHostname . ' has been suspended.';
    }

    public function doUnSuspend($args)
    {
        $userPackage = new UserPackage($args['userPackageId']);
        $args = $this->buildParams($userPackage);
        $this->setup($args);
        $this->api->instancePostAction($args['package']['ServerAcctProperties'], 'start');
        $vmHostname = $userPackage->getCustomField(
            $args['server']['variables']['plugin_vultr_VM_Hostname_Custom_Field'],
            CUSTOM_FIELDS_FOR_PACKAGE
        );
        return $vmHostname . ' has been unsuspended.';
    }

    public function getAvailableActions($userPackage)
    {
        $args = $this->buildParams($userPackage);
        $this->setup($args);

        $actions = [];
        if ($args['package']['ServerAcctProperties'] == '') {
            $actions[] = 'Create';
        } else {
            $server = $this->api->getInstance($args['package']['ServerAcctProperties'])['instance'];

            if (strtolower($server['power_status']) == 'running') {
                $actions[] = 'Suspend';
                $actions[] = 'Reboot';
                $actions[] = 'Shutdown';
                $actions[] = 'Rebuild';
            } else {
                $actions[] = 'UnSuspend';
                $actions[] = 'Boot';
            }
                $actions[] = 'Delete';
        }

        return $actions;
    }

    public function doReboot($args)
    {
        $userPackage = new UserPackage($args['userPackageId']);
        $args = $this->buildParams($userPackage);
        $this->setup($args);
        $this->api->instancePostAction($args['package']['ServerAcctProperties'], 'reboot');
        $vmHostname = $userPackage->getCustomField(
            $args['server']['variables']['plugin_vultr_VM_Hostname_Custom_Field'],
            CUSTOM_FIELDS_FOR_PACKAGE
        );
        return $vmHostname . ' has been rebooted.';
    }

    public function doBoot($args)
    {
        $userPackage = new UserPackage($args['userPackageId']);
        $args = $this->buildParams($userPackage);
        $this->setup($args);
        $this->api->instancePostAction($args['package']['ServerAcctProperties'], 'start');
        $vmHostname = $userPackage->getCustomField(
            $args['server']['variables']['plugin_vultr_VM_Hostname_Custom_Field'],
            CUSTOM_FIELDS_FOR_PACKAGE
        );
        return $vmHostname . ' has been booted.';
    }

    public function doShutdown($args)
    {
        $userPackage = new UserPackage($args['userPackageId']);
        $args = $this->buildParams($userPackage);
        $this->setup($args);
        $this->api->instancePostAction($args['package']['ServerAcctProperties'], 'halt');
        $vmHostname = $userPackage->getCustomField(
            $args['server']['variables']['plugin_vultr_VM_Hostname_Custom_Field'],
            CUSTOM_FIELDS_FOR_PACKAGE
        );
        return $vmHostname . ' has been shutdown.';
    }

    public function getPlans($serverId)
    {
        $server = new Server($serverId);
        $pluginVariables = $server->getAllServerPluginVariables($this->user, 'vultr');
        $this->setup($pluginVariables);

        $plans = [];
        $plans[0] = lang('-- Select VPS Plan --');
        foreach ($this->api->allplans()['plans'] as $plan) {
            $plans[$plan['id']] = $plan['id'] . ' - $' . $plan['monthly_cost'];
        }
        return $plans;
    }

    public function getDirectLink($userPackage, $getRealLink = true, $fromAdmin = false, $isReseller = false)
    {
        $linkText = $this->user->lang('Web Console');

        if ($fromAdmin) {
            $cmd = 'panellogin';

            return [
                'cmd' => $cmd,
                'label' => $linkText
            ];
        } elseif ($getRealLink) {
            $args = $this->buildParams($userPackage);
            $this->setup($args);
            $server = $this->api->getInstance($args['package']['ServerAcctProperties'])['instance'];
            $url = $server['kvm'];
            return array(
                'fa' => 'fa fa-user fa-fw',
                'link' => $url,
                'text' => $linkText,
                'form' => ''
            );
        } else {
            $link = 'index.php?fuse=clients&controller=products&action=openpackagedirectlink&packageId=' . $userPackage->getId() . '&sessionHash=' . CE_Lib::getSessionHash();

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
