<?php

require_once 'library/CE/NE_Plugin.php';
require_once 'modules/clients/models/DomainNameGateway.php';
require_once 'modules/domains/models/MethodNotImplemented.php';

/**
 * RegistrarPlugin Model Class
 *
 * @category Model
 * @package  Admin
 * @license  ClientExec License
 * @link     http://www.clientexec.com
 * @abstract
 */
abstract class RegistrarPlugin extends NE_Plugin
{
    /**
     * Method that communicates with the registrar API to find out if the domain name is available
     *
     * @param array $params Contains the values for the variables defined in getVariables() and tld and sld values (top-level domain and second-level domain)
     *
     * @abstract
     * @return array <pre>array(code [,message]), where code is:
     *                       0:       Domain available
     *                       1:       Domain already registered
     *                       2:       Registrar Error, domain extension not recognized or supported
     *                       3:       Domain invalid
     *                       5:       Could not contact registry to lookup domain</pre>
     */
    abstract public function checkDomain($params);

    /**
     * Communicates with the registrar API to carry out the domain name registration.
     *
     * @param array $params Contains the values for the variables defined in getVariables(), plus: tld, sld, NumYears, RegistrantOrganizationName, RegistrantFirstName, RegistrantLastName, RegistrantEmailAddress, RegistrantPhone, RegistrantAddress1, RegistrantCity, RegistrantProvince, RegistrantPostalCode, RegistrantCountry, DomainPassword, ExtendedAttributes, NSx (list of nameservers if set, and usedns.
     *
     * @abstract
     * @return array array(code [,message])
     *                       -1:  error trying to purchase domain
     *                       0:   domain not available
     *                       >0:  Operation successfull, returns orderid
     */
    abstract public function registerDomain($params);

    /**
     * Communicates with the registrar API to retrieve the contact information for a given domain.
     *
     * @param array $params Contains the values for the variables defined in getVariables(), plus: tld, sld.
     *
     * @abstract
     * @return array array('type' => array(contactField => contactValue))
     */
    abstract public function getContactInformation($params);

    /**
     * Communicates with the registrar API to retrieve the contact information for a given domain.
     *
     * @param array $params Contains the values for the variables defined in getVariables(), plus: tld, sld.
     *
     * @abstract
     * @return array array('type' => array(contactField => contactValue))
     */
    abstract public function setContactInformation($params);

    /**
     * Communicates with the registrar API to retrieve the dns information for a given domain.
     *
     * @param array $params Contains the values for the variables defined in getVariables(), plus: tld, sld.
     *
     * @abstract
     * @return array array('type' => array(contactField => contactValue))
     */
    abstract public function getNameServers($params);

    /**
     * Communicates with the registrar API to retrieve the dns information for a given domain.
     *
     * @param array $params Contains the values for the variables defined in getVariables(), plus: tld, sld.
     *
     * @abstract
     * @return array array('type' => array(contactField => contactValue))
     */
    abstract public function setNameServers($params);

    /**
     * Communicates with the registrar API to retrieve general information for a given domain.
     *
     * @param array $params Contains the values for the variables defined in getVariables(), plus: tld, sld.
     *
     * @abstract
     * @return array array(id,domain,expiration,registrationstatus,purchasestatus,autorenew)
     */
    abstract public function getGeneralInfo($params);

    /**
     * Communicates with the registrar API to set autorenew for a given domain.
     *
     * @param array $params Contains the values for the variables defined in getVariables(), plus: tld, sld.
     *
     * @abstract
     * @return array CE_Error on failure
     */
    abstract public function setAutorenew($params);

    /**
     * Communicates with the registrar API to retrieve the registrar lock information for a given domain.
     *
     * @param array $params Contains the values for the variables defined in getVariables(), plus: tld, sld.
     *
     * @abstract
     * @return boolean
     */
    abstract public function getRegistrarLock($params);

    /**
     * Communicates with the registrar API to retrieve the dns information for a given domain.
     *
     * @param array $params Contains the values for the variables defined in getVariables(), plus: tld, sld.
     *
     * @abstract
     * @return array CE_Error on failure
     */
    abstract public function setRegistrarLock($params);

    /**
     * Communicates with the registrar API to send the transfer key to registrant.
     *
     * @param array $params Contains the values for the variables defined in getVariables(), plus: tld, sld.
     *
     * @abstract
     * @return array CE_Error on failure
     */
    abstract public function sendTransferKey($params);

    public function getEPPCode($params)
    {
        return 'N/A';
    }

    /**
     * Returns true if the contact information indicates that the domain has privacy protection enabled,
     * and thus the registrar won't return the customer's data.
     * For example Directi returns a contact with a company "PrivacyProtect.org".
     * In this case, when the domain is imported from the registrar, a corresponding customer account won't be created.
     *
     * @param array $contactInfo contact information to check
     *
     * @todo should be abstract
     * @return bool
     */
    public function hasPrivacyProtection($contactInfo)
    {
        return false;
    }

    /**
     * Get the username and password for this domain
     * Only used for some plugins but added to parent plugin class
     *
     * @param UserPackage $obj     User's Package we are getting username and password for
     * @param bool        $decrypt If we want to force decrypt the information
     *
     * @return array Username & Password
     * @access public
     */
    public function getUsernamePassword($obj, $decrypt = true)
    {
        $domain_extra_attr = $obj->getCustomField("Domain Extra Attr");
        $domain_extra_attr = @unserialize($domain_extra_attr);
        $domainName        = $obj->getCustomField("Domain Name");
        // Unserialize if its not already
        if (@!is_array($domain_extra_attr)) {
            $domain_extra_attr = unserialize($domain_extra_attr);
        }
        // check if the u/p exists
        if (@$domain_extra_attr['domainUsername'] && @$domain_extra_attr['domainPassword']) {
            // Return it
            if ($this->settings->get('Domain Passwords are Encrypted') == 1) {
                $domainPassword = Clientexec::decryptString($domain_extra_attr['domainPassword']);
            } else {
                $domainPassword = $domain_extra_attr['domainPassword'];
            }
            return array(
                "domainUsername" => @$domain_extra_attr['domainUsername'],
                "domainPassword" => $domainPassword
            );
        } else {
            @$domain_extra_attr['domainUsername'] = mb_substr($domainName, 0, 8);
            @$domain_extra_attr['domainUsername'] = str_replace(array('-', '_', '.'), '', @$domain_extra_attr['domainUsername']);
            // Generate the password
            @$domain_extra_attr['domainPassword'] = mb_substr(md5(time()), 0, 10);

            // store the data so the username and password are the same the next time this is called.
            $savedData = $domain_extra_attr;
            if ($this->settings->get('Domain Passwords are Encrypted') == 1) {
                $savedData['domainPassword'] = Clientexec::encryptString($domain_extra_attr['domainPassword']);
            } else {
                $savedData['domainPassword'] = $domain_extra_attr['domainPassword'];
            }
            $obj->setCustomField("Domain Extra Attr", serialize($savedData));

            return array(
                "domainUsername" => @$domain_extra_attr['domainUsername'],
                "domainPassword" => @$domain_extra_attr['domainPassword']
            );
        }
    }

    /**
     * Build the params needed to register domain
     *
     * @param UserPackage $userPackage User's package that params will be build for
     * @param array       $params      Optional params list to format
     *
     * @return array
     */
    public function buildRenewParams($userPackage, $params = false)
    {
        $dng = new DomainNameGateway();

        if (!$params) {
            $params = array();
        }

        $domainName = $userPackage->getCustomField("Domain Name");
        $splitDomain = $dng->splitDomain($domainName);
        $sld = $splitDomain[0];
        $tld = $splitDomain[1];

        $params = array_merge(
            $params,
            array(
                'tld' => $tld,
                'sld' => $sld
             )
        );

        // Included so we can renew for more then one year, depending on what the billing cycle is set to.
        require_once 'modules/billing/models/BillingCycle.php';
        $period = $dng->getPeriod($userPackage);
        $billingCycle = new BillingCycle($period);
        $numYears = 0;

        if ($billingCycle->time_unit == 'y') {
            $numYears = $billingCycle->amount_of_units;
        }

        $params['NumYears'] = $numYears;

        if ($addons = $userPackage->getAddons()) {
            $params['package_addons'] = $userPackage->getPackageAddons($addons);
        }

        CE_Lib::log(4, "Build Renew Params: " . print_r($params, true));
        return $params;
    }

    /**
     * Build the params needed to lock domain
     *
     * @param UserPackage $userPackage User's package that params will be build for
     * @param array       $params      Optional params list to format
     *
     * @return array
     */
    public function buildLockParams($userPackage, $params = false)
    {
        $dng = new DomainNameGateway();
        if (!$params) {
            $params = array();
        }

        $domainName          = $userPackage->getCustomField("Domain Name");
        $splitDomain = $dng->splitDomain($domainName);
        $sld = $splitDomain[0];
        $tld = $splitDomain[1];

        $params              = array_merge(
            $params,
            array(
                'tld' => $tld,
                'sld' => $sld,
                'lock' => $params['changes']
             )
        );

        CE_Lib::log(4, "Build Lock Params: " . print_r($params, true));
        return $params;
    }

    /** Build the params needed to transfer a domain
     *
     * @param UserPackage $userPackage User's package that params will be build for
     * @param array       $params      Optional params list to format
     *
     * @return array
     */
    public function buildTransferParams($userPackage, $params = false)
    {
        $dng = new DomainNameGateway();
        if (!$params) {
            $params = array();
        }

        $domainName          = $userPackage->getCustomField("Domain Name");
        $splitDomain = $dng->splitDomain($domainName);
        $sld = $splitDomain[0];
        $tld = $splitDomain[1];

        $user                = new User($userPackage->CustomerId);
        $params              = array_merge(
            $params,
            array(
                'RegistrantOrganizationName' => $user->getOrganization(),
                'RegistrantFirstName' => $user->getFirstName(),
                'RegistrantLastName' => $user->getLastName(),
                'RegistrantEmailAddress' => $user->getEmail(),
                'RegistrantPhone' => $user->getPhone(),
                'RegistrantAddress1' => $user->getAddress(),
                'RegistrantCity' => $user->getCity(),
                'RegistrantStateProvince' => $user->getState(true),
                'RegistrantStateProvinceCode' => $user->getState(),
                'RegistrantPostalCode' => $user->getZipCode(),
                'RegistrantCountry' => $user->getCountry()
             )
        );

        $extraAttributes = $userPackage->getCustomField("Domain Extra Attr");
        $extraAttributes = @unserialize($extraAttributes);
        $params['ExtendedAttributes'] = $extraAttributes ? $extraAttributes : false;
        // handle EPP code
        $eppCode = '';
        if (isset($params['changes']) && $params['changes'] != '') {
            $eppCode = $params['changes'];
        } elseif (isset($extraAttributes['eppCode'])) {
            $eppCode = $extraAttributes['eppCode'];
        }

        $domainUsernamePassword = $this->getUsernamePassword($userPackage);
        $params              = array_merge(
            $params,
            array(
                'tld' => $tld,
                'sld' => $sld,
                'DomainUsername' => $domainUsernamePassword['domainUsername'],
                'DomainPassword' => $domainUsernamePassword['domainPassword'],
                'eppCode'        => $eppCode
             )
        );

        CE_Lib::log(4, "Build Transfer Params: " . print_r($params, true));
        return $params;
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
     * Build the params needed to register domain
     *
     * @param UserPackage $userPackage User's package that params will be build for
     * @param array       $params      Optional params list to format
     *
     * @return array
     */
    public function buildRegisterParams($userPackage, $params = false)
    {
        $dng = new DomainNameGateway();

        if (!$params) {
            $params = array();
        }
        //creat params from $userPackage
        $autoRenew           = $userPackage->getCustomField("Auto Renew");

        require_once 'modules/billing/models/BillingCycle.php';
        $period = $dng->getPeriod($userPackage);
        $billingCycle = new BillingCycle($period);
        $numYears = 0;

        if ($billingCycle->time_unit == 'y') {
            $numYears = $billingCycle->amount_of_units;
        }

        $domainName          = $userPackage->getCustomField("Domain Name");
        $domain_extra_attr   = $userPackage->getCustomField("Domain Extra Attr");
        $domain_extra_attr   = @unserialize($domain_extra_attr);
        $user                = new User($userPackage->CustomerId);
        $params              = array_merge(
            $params,
            array(
                'NumYears' => $numYears,
                'RegistrantOrganizationName' => $user->getOrganization(),
                'RegistrantFirstName' => $user->getFirstName(),
                'RegistrantLastName' => $user->getLastName(),
                'RegistrantEmailAddress' => $user->getEmail(),
                'RegistrantPhone' => $user->getPhone(),
                'RegistrantAddress1' => $user->getAddress(),
                'RegistrantCity' => $user->getCity(),
                'RegistrantStateProvince' => $user->getState(true),
                'RegistrantStateProvinceCode' => $user->getState(),
                'RegistrantPostalCode' => $user->getZipCode(),
                'RegistrantCountry' => $user->getCountry(),
                'renewname' => $autoRenew
             )
        );

        $params['ExtendedAttributes'] = $domain_extra_attr ? $domain_extra_attr : false;
        // Ensure we pass a non-encrypted password
        if (isset($params['ExtendedAttributes']['domainPassword']) && $this->settings->get('Domain Passwords are Encrypted') == 1) {
            $params['ExtendedAttributes']['domainPassword'] = ClientExec::decryptString($params['ExtendedAttributes']['domainPassword']);
        }

        $splitDomain = $dng->splitDomain($domainName);
        $sld = $splitDomain[0];
        $tld = $splitDomain[1];

        //in case needed
        $domainUsernamePassword = $this->getUsernamePassword($userPackage);
        $params                 = array_merge(
            $params,
            array(
                'tld' => $tld,
                'sld' => $sld,
                'DomainUsername' => $domainUsernamePassword['domainUsername'],
                'DomainPassword' => $domainUsernamePassword['domainPassword']
            )
        );

        $query = "SELECT objectid FROM object_customField WHERE value = ? AND customFieldId IN (SELECT id FROM customField WHERE name = 'Domain Name' and subGroupId = 1 AND groupId = 2)";
        $result = $this->db->query($query, $domainName);

        // No hosting package, so just use default name servers
        if ($result->getNumRows() == 0) {
            $params['usedns'] = 'default';
        } else {
            list($userPackageId) = $result->fetch();
            $userPackageDNS = new UserPackage($userPackageId);
            $query = "SELECT ip, hostname FROM nameserver WHERE serverid=?";
            $result = $this->db->query($query, $userPackageDNS->getCustomField('Server Id'));

            // No nameservers configured for server, so check default
            if ($result->getNumRows() == 0) {
                $query = "SELECT nameserver.ip as ip, nameserver.hostname as hostname FROM nameserver, server WHERE server.id = nameserver.serverid AND server.isdefault = '1'";
                $result = $this->db->query($query);
            }
            $i = 1;
            while ($row = $result->fetch()) {
                if (isset($row['hostname'])) {
                    $params["NS$i"]["hostname"] = $row["hostname"];
                } else {
                    break;
                }
                $i++;
                if ($i == 12) {
                    break;
                }
            }

            if ($i == 1) {
                $params['usedns'] = 'default';
            }
        }

        if ($addons = $userPackage->getAddons()) {
            $params['package_addons'] = $userPackage->getPackageAddons($addons);
        }

        CE_Lib::log(4, "Build Registrar Params: " . print_r($params, true));
        return $params;
    }

    /**
     * Perform an action on the plugin. Should return new status of plugin
     *
     * @param UserPackage $userPackage     User's Package you want to perform an action on
     * @param string      $actionToPerform Action you wish to perform
     * @param array       $params          Optional params list to pass to action
     *
     * @return string new status
     */
    public function doAction($userPackage, $actionToPerform, $params = false)
    {

        $actionToPerform = trim($actionToPerform);

        $registrar = strtolower($userPackage->getCustomField("Registrar"));
        $variables = $this->getVariables();
        if (!$params) {
            $params = array();
        }
        foreach (array_keys($variables) as $key) {
            $settingname = "plugin_" . $registrar . "_" . $key;
            $params      = array_merge(
                $params,
                array($key => $this->settings->get($settingname))
            );
        }
        $prependedActionName = "do" . $actionToPerform;
        if (method_exists($this, $prependedActionName)) {
            $this->trackUsage("registrar", $actionToPerform);
            return $this->$prependedActionName($params);
        } else {
            throw new Exception("Action " . $actionToPerform . " does not have a do function (" . $prependedActionName . ") in class:" . __CLASS__);
        }
    }

    /**
     * Checks if an action is supported in the plugin
     *
     * @param string $action action name to check if supproted or not.
     *
     * @return boolean If action is supported or not
     */
    public function supportsAction($action)
    {
        $prependedActionName = "do" . trim($action);
        if (method_exists($this, $prependedActionName)) {
            return true;
        }
        return false;
    }

    /**
     * Get list of available plugin actions based on current plugin status
     *
     * @param string $status Status either from plugin or UserPackage status
     *
     * @abstract
     * @todo determine what status we are sending
     * @return array array of available actions for this plugin
     */
    public function getAvailableActions($userPackage, $status)
    {
        try {
            $vars = $this->getVariables();
            if ($status === true) {
                $actions = $vars['Registered Actions'];
            } else {
                $actions = $vars['Actions'];
            }
            if ($actions == "") {
                $retArray = array();
            } else {
                $retArray = explode(",", $actions['value']);
            }
        } catch (Exception $ex) {
            $retArray = array();
            CE_Lib::log(1, $ex->getMessage());
        }
        return $retArray;
    }

    public function doCancel($params)
    {
        $userPackage = new UserPackage($params['userPackageId']);
        $userPackage->cancel(true);
        return $userPackage->getCustomField('Domain Name') . ' has been canceled.';
    }

    public function supports($feature)
    {
        if (isset($this->features[$feature])) {
            return $this->features[$feature];
        }
        return false;
    }
}
