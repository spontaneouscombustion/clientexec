<?php

require_once 'library/CE/NE_Plugin.php';
require_once 'modules/admin/models/server.php';
require_once 'modules/admin/models/Package.php';

/**
 * ServerPlugin Model Class
 *
 * @category Model
 * @package  Admin
 * @license  ClientExec License
 * @link     http://www.clientexec.com
 */
class ServerPlugin extends NE_Plugin
{
    public $features = array(
        'packageName' => false,
        'testConnection' => false,
        'showNameservers' => false,
        'directlink' => false,
        'admindirectlink' => false,
        'publicView' => false,
        'upgrades' => false,
        'publicPanels' => []
    );

    // 'cmd' => 'fa icon'
    public $icons = [

    ];

    /**
     * Server plugin constructor
     *
     * @param User &$user User object to pass to constructor
     */
    public function __construct($user)
    {
        parent::__construct($user);
        @set_time_limit(0);
    }

    /**
     * Validation of the domain account username and password
     * Plugins can also check with the server for the availability of the username.
     *
     * @param array $args Must at least contain DomainUserName, DomainPassword, isUpdate. Other variables are optional, and are used when the plugin wants to check with the server for the username availability: variables defined in getVariables(), plus UserEmail, UserFirstName, UserLastName, PassedUserName, DomainName, DomainSharedIP, DomainIP, PackageID, CustomerID, PackageName, PackageNameOnServer, ServeHostName, package_vars, package_addons, is_reseller (if applicable), acl values (if applicable).
     *
     * @abstract
     * @return string|CE_Error Returns username if username and password are valid, CE_Error if not (with message explaining why). If username is not valid, it will try to modify it to respect the plugin's rules, and returned the modified version.
     */
    public function validateCredentials($args)
    {
        return $args['package']['username'];
    }

    /**
     * Communicates with the server API to create the account
     *
     * This method is called when an account is activated, saying we want to use the server plugin.
     *
     * @param array $args Contains values for variables defined in getVariables(), plus: UserEmail, UserFirstName, UserLastName, PassedUserName, DomainName, DomainUserName, DomainPassword, DomainSharedIP, DomainIP, PackageID, CustomerID, PackageName, PackageNameOnServer, ServeHostName, package_vars, package_addons, is_reseller (if applicable), acl values (if applicable), PassedUserName, DomainUserName.
     *
     * @abstract
     * @return mixed null if operation was successfull, CE_Error if an error ocurred.
     */
    public function create($args)
    {
        CE_Lib::debug($args);
        throw new Exception("Create method called but not implemented");
    }

    /**
     * Communicates with server API to update the account
     *
     * @param array $args Contains changes in keys CHANGE_USERNAME, CHANGE_DOMAIN, CHANGE_IP, CHANGE_PASSWORD if any of these pieces of data have changed. If the package was changed, it might contain server info in the key CHANGE_PACKAGE and acl server info. It also contains DomainIP, package_vars, and package_addons (if applicable).
     *
     * @abstract
     * @return string Empty for a successfull operation, or an error string if an error occurred.
     */
    public function update($args)
    {
        CE_Lib::debug($args);
        throw new Exception("Update method called but not implemented");
    }

    /**
     * Communicates with server API to delete the account
     *
     * @param array $args Contains values for variables defined in getVariables(), plus: UserEmail, UserFirstName, UserLastName, PassedUserName, DomainName, DomainUserName, DomainPassword, DomainSharedIP, DomainIP, PackageID, CustomerID, PackageName, PackageNameOnServer, ServeHostName, package_vars, PassedUserName, DomainUserName.
     *
     * @abstract
     * @return string Empty for a successfull operation, or an error string if an error occurred.
     */
    public function delete($args)
    {
        CE_Lib::debug($args);
        throw new Exception("Delete method called but not implemented");
    }

    /**
     * Comunicates with server API to suspend the account
     *
     * @param array $args Contains values for variables defined in getVariables(), plus: UserEmail, UserFirstName, UserLastName, PassedUserName, DomainName, DomainUserName, DomainPassword, DomainSharedIP, DomainIP, PackageID, CustomerID, PackageName, PackageNameOnServer, ServeHostName, package_vars, is_reseller (if applicable), PassedUserName, DomainUserName.
     *
     * @abstract
     * @return string Empty for a successfull operation, or an error string if an error occurred.
     */
    public function suspend($args)
    {
        CE_Lib::debug($args);
        throw new Exception("Suspend method called but not implemented");
    }

    /**
     * Comunicates with server API to unsuspend the account
     *
     * @param array $args Contains values for variables defined in getVariables(), plus: UserEmail, UserFirstName, UserLastName, PassedUserName, DomainName, DomainUserName, DomainPassword, DomainSharedIP, DomainIP, PackageID, CustomerID, PackageName, PackageNameOnServer, ServeHostName, package_vars, PassedUserName, DomainUserName.
     *
     * @abstract
     * @return string Empty for a successfull operation, or an error string if an error occurred.
     */
    public function unsuspend($args)
    {
        CE_Lib::debug($args);
        throw new Exception("Unsuspend method called but not implemented");
    }

    /**
     * Show views that might be specific to this plugin.
     * This content should be echoed out not returned
     *
     * @param UserPackage $user_package
     * @param CE_Controller_Action $action
     * @return html
     */
    public function show_publicviews($user_package, $action)
    {
        //each plugin should override this method
    }

    /**
     * Communities with server API to ensure that access credentials are correct..
     * Contains values for variables defined in getVariables(), plus: UserEmail, UserFirstName, UserLastName, PassedUserName, DomainName, DomainUserName, DomainPassword, DomainSharedIP, DomainIP, PackageID, CustomerID, PackageName, PackageNameOnServer, ServeHostName, package_vars, PassedUserName, DomainUserName.
     *
     * @param array $args Arguments to pass to test
     *
     * @abstract
     * @return boolean
     */
    public function test($args)
    {
        CE_Lib::debug($args);
        throw new Exception("Test method called with but not implemented");
    }

    /**
     * Perform an action on the plugin should return new status of plugin
     *
     * @param UserPackage $userPackage     Userpackage that we want to perform a plugin action on
     * @param string      $actionToPerform Name of action to perform with plugin
     * @param array       $params          optional params array that might be needed for the action
     *
     * @return string new status
     */
    public function doAction($userPackage, $actionToPerform, $params = false)
    {
        CE_Lib::log(4, "Calling Server action " . $actionToPerform . " on package " . $userPackage->id);
        $prependedActionName = "do" . trim($actionToPerform);
        // Check we actually have a valid server plugin function to call
        if (method_exists($this, $prependedActionName)) {
            if ($actionToPerform != "CheckUserName") {
                $this->trackUsage("server", $actionToPerform);
            }
            return $this->$prependedActionName($params);
        } else {
            throw new Exception("Action " . $actionToPerform . " does not have a do function (" . $prependedActionName . ") in class:" . __CLASS__);
        }
    }

    /**
     * Build the required params to test server connection
     *
     * @return array
     */
    public function buildTestParams($serverId)
    {
        $params = array();
        $server = new Server($serverId);
        $pluginName = $server->getPluginName();
        $params['server']['variables'] = $server->getAllServerPluginVariables($this->user, $pluginName);
        return $params;
    }

    /**
     * Build the required params to pass to server plugin
     *
     * @param UserPackage $userPackage User's package object
     * @param bool        $params      list of params
     *
     * @return array
     */
    public function buildParams($userPackage, $params = false)
    {
        include_once "modules/admin/models/PackageGateway.php";
        $packageGateway = new PackageGateway($this->user);

        if (!$params) {
            $params = array();
        } //!$params

        $params['customer'] = array();
        $params['package']  = array();
        $package            = new Package($userPackage->Plan);
        $server             = new Server($userPackage->getCustomField('Server Id'));
        $pluginName         = $server->getPluginName();

        // User Details
        $user                             = new User($userPackage->CustomerId);
        $params['customer']['email']      = $user->getEmail();
        $params['customer']['first_name'] = $user->getFirstName();
        $params['customer']['last_name']  = $user->getLastname();
        $params['customer']['id']         = $userPackage->CustomerId;
        $params['customer']['organization']    = $user->getOrganization();

        // Package Details
        $params['package']['id']             = $userPackage->id;
        $params['package']['name']           = $package->planname;
        $params['package']['username']       = $userPackage->getCustomField('User Name');
        if ($this->settings->get('Domain Passwords are Encrypted') == 1) {
            $params['package']['password'] = htmlspecialchars_decode(Clientexec::decryptString($userPackage->getCustomField('Password')), ENT_QUOTES);
        } else {
            $params['package']['password'] = htmlspecialchars_decode($userPackage->getCustomField('Password'), ENT_QUOTES);
        }
        $params['package']['domain_name']          = $userPackage->getCustomField("Domain Name");
        $params['package']['ip']                   = $userPackage->getCustomField("IP Address");
        $params['package']['ServerAcctProperties'] = $userPackage->getCustomField("Server Acct Properties");

        $params['server']['id']        = $userPackage->getCustomField('Server Id');
        // Server Variables
        $params['server']['variables'] = $server->getAllServerPluginVariables($this->user, $pluginName);
        $params['server']['nameservers'] = $server->getNameServers();
        // Package Addons
        if ($addons = $userPackage->getAddons()) {
            $params['package']['addons'] = $userPackage->getPackageAddons($addons);
        }
        $params['package']['customfields'] = $userPackage->getCustomFieldsForPlugin();


        if ($server->getPrependUsername() == 1) {
            $params['package']['name_on_server'] = $params['server']['variables']['plugin_cpanel_Username'] . '_' . $package->getVariable('plugin_' . $pluginName);
        } else {
            $params['package']['name_on_server'] = $package->getVariable('plugin_' . $pluginName);
        }

        // Reseller Package?
        if ($package->getVariable("plugin_{$pluginName}_reseller")) {
            $params['package']['is_reseller'] = 1;
            $params['package']['acl']         = array();
            $params['package']['acl']         = array_merge($params['package']['acl'], $packageGateway->getResellerAcl($userPackage->Plan, $pluginName));
        } //$package->getVariable("plugin_{$pluginName}_reseller")

        $params['package']['variables'] = $packageGateway->getServerPackageVars($pluginName, $userPackage->Plan);

        return $params;
    }

    /**
     * Comunicates with server API to check if an account exists or not
     * This one is based on the Available Actions for the plugin.
     * If it can 'Create', then assume "This account has not been created on the server yet"
     * Override this function if the Plugin do not have a 'Create' action.
     *
     * @param array $args Arguments to pass to method
     *
     * @return boolean
     */
    public function doCheckUserName($args)
    {
        $userPackage = new UserPackage($args['userPackageId']);
        $actions = $this->getAvailableActions($userPackage);
        if (is_array($actions)) {
            return !in_array('Create', $actions);
        } else {
            return false;
        }
    }

    /**
     * Comunicates with server API to check status of an account, and return the valid actions we can perform
     *
     * @param array $userPackage
     *
     * @abstract
     * @return array
     */
    public function getAvailableActions($userPackage)
    {
        include_once 'modules/admin/models/StatusAliasGateway.php' ;
        $statusAliasGateway = StatusAliasGateway::getInstance($this->user);

        $actions = array();
        if ($statusAliasGateway->isActivePackageStatus($userPackage->status)) {
            $actions[] = 'Suspend';
            $actions[] = 'Delete';
        } elseif ($statusAliasGateway->isSuspendedPackageStatus($userPackage->status)) {
            $actions[] = 'UnSuspend';
            $actions[] = 'Delete';
        } else {
            $actions[] = 'Create';
        }
        return $actions;
    }

    public function supports($feature)
    {
        if (isset($this->features[$feature])) {
            return $this->features[$feature];
        }
        return false;
    }

    /**
     * Internal function used to get the title and direct URL link to login to a userPackage's control panel.
     *
     * @param array $userPackage
     * @param bool  $getRealLink
     *
     * @abstract
     * @return array(link, form)
     */
    public function getDirectLink($userPackage, $getRealLink)
    {
    }
}
