<?php

require_once 'modules/admin/models/ServerPlugin.php';
require_once 'robot-client/RobotRestClient.class.php';
require_once 'robot-client/RobotClientException.class.php';
require_once 'robot-client/RobotClient.class.php';


class PluginHetznerrobot extends ServerPlugin
{

    public $features = [
        'packageName' => false,
        'testConnection' => true,
        'showNameservers' => false,
        'directlink' => false
    ];

    private $api;

    public function setup($args)
    {
        $this->api = new RobotClient(
            'https://robot-ws.your-server.de',
            $args['server']['variables']['plugin_hetznerrobot_API_Username'],
            $args['server']['variables']['plugin_hetznerrobot_API_Password']
        );
    }

    public function getVariables()
    {

        $variables = array (
            lang("Name") => array (
                "type"=>"hidden",
                "description"=>"Used by CE to show plugin - must match how you call the action function names",
                "value"=>"Hetznerrobot"
            ),
            lang("Description") => array (
                "type"=>"hidden",
                "description"=>lang("Description viewable by admin in server settings"),
                "value"=>lang("Hetzner Robot API Integration")
            ),
            lang("API Username") => array (
                "type"=>"text",
                "description"=>lang("API ID"),
                "value"=>"",
                "encryptable"=>true
            ),
            lang("API Password") => array (
                "type"=>"text",
                "description"=>lang("API Key"),
                "value"=>"",
                "encryptable"=>true
            ),
             lang("Main IP Custom Field") => array(
                "type"        => "text",
                "description" => lang("Enter the name of the package custom field that will hold the Main IP Address of the server."),
                "value"       => ""
            ),
            lang("Actions") => array (
                "type"=>"hidden",
                "description"=>lang("Current actions that are active for this plugin per server"),
                "value"=>"Reboot"
            ),
            lang('Registered Actions For Customer') => array(
                "type"=>"hidden",
                "description"=>lang("Current actions that are active for this plugin per server for customers"),
                "value"=>"Reboot"
            ),
            lang("reseller") => array (
                "type"=>"hidden",
                "description"=>lang("Whether this server plugin can set reseller accounts"),
                "value"=>"0",
            ),
            lang("package_addons") => array (
                "type"=>"hidden",
                "description"=>lang("Supported signup addons variables"),
                "value"=>"",
            ),
            lang('package_vars')  => array(
                'type'            => 'hidden',
                'description'     => lang('Whether package settings are set'),
                'value'           => '0',
            ),
            lang('package_vars_values') => array()
        );

        return $variables;
    }

    function validateCredentials($args)
    {
    }

    function doReboot($args)
    {
        $userPackage = new UserPackage($args['userPackageId']);
        $args = $this->buildParams($userPackage);
        $this->reboot($args);
        return 'Server has been rebooted.';
    }

    function reboot($args)
    {
        $this->setup($args);
        $userPackage = new UserPackage($args['package']['id']);

        $serverIp = $userPackage->getCustomField(
            $args['server']['variables']['plugin_hetznerrobot_Main_IP_Custom_Field'],
            CUSTOM_FIELDS_FOR_PACKAGE
        );

        if ($serverIp == '') {
            throw new CE_Exception($this->user->lang('No Main IP Defined'));
        }

        $result = $this->api->resetExecute($serverIp, 'hw');
        CE_Lib::log(4, $result);
    }

    function getAvailableActions($userPackage)
    {
        $actions = [];

        $actions[] = 'Reboot';
        return $actions;
    }

    public function testConnection($args)
    {
        CE_Lib::log(4, 'Testing connection to Hetzner');
        $this->setup($args);
        $result = $this->api->serverGetAll();
        CE_Lib::log(4, $result);
    }
}
