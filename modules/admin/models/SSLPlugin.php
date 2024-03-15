<?php
define('SSL_CERT_RAPIDSSL', 1);
define('SSL_CERT_GEOTRUST_QUICKSSL_PREMIUM', 2);
define('SSL_CERT_GEOTRUST_TRUE_BUSINESSID', 3);
define('SSL_CERT_GEOTRUST_TRUE_BUSINESSID_EV', 4);
define('SSL_CERT_GEOTRUST_QUICKSSL', 5);
define('SSL_CERT_GEOTRUST_TRUE_BUSINESSID_WILDCARD', 6);
define('SSL_CERT_VERISIGN_SECURE_SITE', 7);
define('SSL_CERT_VERISIGN_SECURE_SITE_PRO', 8);
define('SSL_CERT_VERISIGN_SECURE_SITE_EV', 9);
define('SSL_CERT_VERISIGN_SECURE_SITE_PRO_EV', 10);
define('SSL_CERT_COMODO_ESSENTIAL', 11);
define('SSL_CERT_COMODO_INSTANT', 12);
define('SSL_CERT_COMODO_PREMIUM_WILDCARD', 13);
define('SSL_CERT_COMODO_ESSENTIAL_WILDCARD', 14);
define('SSL_CERT_COMODO_EV', 15);
define('SSL_CERT_COMODO_EV_SGC', 16);

define('SSL_CERT_RAPIDSSL_WILDCARD', 17);
define('SSL_CERT_THAWTE_SSL123', 18);
define('SSL_CERT_THAWTE_SGC_SUPERCERT', 19);
define('SSL_CERT_THAWTE_SSL_WEBSERVER', 20);
define('SSL_CERT_THAWTE_SSL_WEBSERVER_EV', 21);
define('SSL_CERT_THAWTE_SSL_WEBSERVER_WILDCARD', 22);

define('SSL_GOGETSSL_TRIAL', 23);

// Sectigo PositiveSSL
define('SSL_SECTIGO_POSITIVESSL', 24);
define('SSL_SECTIGO_POSITIVESSL_WILDCARD', 25);
define('SSL_SECTIGO_PREMIUM_WILDCARD', 26);
define('SSL_SECTIGO_INSTANTSSL_PREMIUM', 27);
define('SSL_SECTIGO_INSTANTSSL_PRO', 28);
define('SSL_SECTIGO_EV', 29);
define('SSL_SECTIGO_CODE_SIGNING', 30);
define('SSL_SECTIGO_INSTANTSSL', 31);
define('SSL_SECTIGO_SSL', 32);
define('SSL_SECTIGO_ESSENTIAL', 33);
define('SSL_SECTIGO_ESSENTIAL_WILDCARD', 34);
define('SSL_SECTIGO_TRIAL', 35);
define('SSL_SECTIGO_SSL_WILDCARD', 36);

define('SSL_GOGETSSL_DOMAIN_SSL', 37);
define('SSL_GOGETSSL_WILDCARD_SSL', 38);

define('SSL_CERT_RAPIDSSL_TRIAL', 39);
define('SSL_CERT_THAWTE_CODE_SIGNING', 40);


// Do not edit this, this is for internal use only.
define('SSL_CERT_ISSUED_STATUS', 'Certificate Issued');

require_once 'library/CE/NE_Plugin.php';
require_once 'modules/admin/models/Package.php';

/**
 * SSLPlugin Model Class
 *
 * @category Model
 * @package  Admin
 * @license  ClientExec License
 * @link     http://www.clientexec.com
 */
abstract class SSLPlugin extends NE_Plugin
{
    /**
     *
     * @var array Mapped array of Ids to our interal Ids of each SSL Certificate Type
     * @abstract
     */
    public $mappedTypeIds = array();

    /**
     *
     * @var boolean True if the registrar is using invite URL rather then direct CSR.
     * @abstract
     */
    public $usingInviteURL = false;

    /**
     * SSL plugin constructor
     *
     * @param User &$user User object to pass to constructor
     */
    public function __constructor($user)
    {
        parent::__construct($user);
    }

    /**
     * Returns an array of supported SSL Certificate types
     *
     *
     * @abstract
     * @return array List of SSL Certificates.
     */
    abstract public function getCertificateTypes();

    /**
     * Returns an array of parsed data from a CSR.
     *
     * @param array $params Parameters for SSL Cert (automatically generated)
     *
     * @abstract
     * @return array The returned array should have the following keys: domain, email, city, state, country, organization, ou, info (optional)
     */
    abstract public function doParseCSR($params);

    /**
     * Returns a string representing the status of the SSL Certificate
     *
     * @param array $params Parameters for SSL Cert (automatically generated)
     *
     * @abstract
     * @return string Status of the SSL Certificate
     */
    abstract public function doGetCertStatus($params);

    /**
     * Build the params needed to call any function at the registrar.
     *
     * @param UserPackage $userPackage User's package that params will be built for
     * @param array       $params      Optional params list to format
     *
     * @return array
     */
    public function buildParams($userPackage, $params = false)
    {
        include_once("modules/admin/models/PluginGateway.php");
        include_once("modules/clients/models/SSLGateway.php");

        if (!$params) {
            $params = array();
        }
        $gateway = new PluginGateway($this->user);
        $sslGateway = new SSLGateway($this->user);

        $registrar = $userPackage->getCustomField('Registrar');

        $plugin = $gateway->getPluginByUserPackage($userPackage, $registrar);

        // ensure we have a plugin before trying to call it.
        if (!is_null($plugin)) {
            foreach ($plugin->getVariables() as $key => $var) {
                $settingname = "plugin_".$registrar."_".$key;
                $params = array_merge($params, array( $key => $this->settings->get($settingname) ));
            }

            foreach ($this->getTechVariables() as $key => $var) {
                $settingname = "plugin_".$registrar."_".$key;
                $params = array_merge($params, array( $key => $this->settings->get($settingname) ));
            }

            $user = new User($userPackage->CustomerId);
            $params = array_merge(
                $params,
                array(
                    'OrganizationName' => $user->getOrganization(),
                    'FirstName' => $user->getFirstName(),
                    'LastName' => $user->getLastName(),
                    'EmailAddress' => $user->getEmail(),
                    'Phone' => $user->getPhone(),
                    'Address1' => $user->getAddress(),
                    'City' => $user->getCity(),
                    'StateProvince' => $user->getState(),
                    'PostalCode' => $user->getZipCode(),
                    'Country' => $user->getCountry()
                )
            );

            $params['userPackageId'] = $userPackage->id;

            require_once 'modules/billing/models/BillingCycle.php';
            $period = $sslGateway->getPeriod($userPackage);
            $billingCycle = new BillingCycle($period);
            $numYears = 0;
            $numMonths = 0;

            if ($billingCycle->time_unit == 'y') {
                $numYears = $billingCycle->amount_of_units;
            }

            if ($billingCycle->time_unit == 'm') {
                $numMonths = $billingCycle->amount_of_units;
            }

            $params['numYears'] = $numYears;
            $params['numMonths'] = $numMonths;

            // Cert Information
            $params['CSR'] = $userPackage->getCustomField('Certificate CSR');
            $params['certId'] = $userPackage->getCustomField('Certificate Id');
            $params['typeId'] = $userPackage->getCustomField('Certificate Type');
            $params['adminEmail'] = $userPackage->getCustomField('Certificate Admin Email');
            $params['serverType'] = $userPackage->getCustomField('Certificate Server Type');
        }
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
     * Get all Technical Contact Fields for the Certificate
     *
     * @return array Array of all technical contact fields.
     */
    public function getTechVariables()
    {
        $variables = array(
            /*T*/'Tech E-Mail'/*/T*/ => array (
                'type'          =>'text',
                'description'   =>/*T*/'Tech Contact Email'/*/T*/,
                'value'         =>/*T*/''/*/T*/
            ),
            /*T*/'Tech First Name'/*/T*/ => array (
                'type'          =>'text',
                'description'   =>/*T*/'Tech Contact First Name'/*/T*/,
                'value'         =>/*T*/''/*/T*/
            ),
            /*T*/'Tech Last Name'/*/T*/ => array (
                'type'          =>'text',
                'description'   =>/*T*/'Tech Contact Last Name'/*/T*/,
                'value'         =>/*T*/''/*/T*/
            ),
            /*T*/'Tech Address'/*/T*/ => array (
                'type'          =>'text',
                'description'   =>/*T*/'Tech Contact Address'/*/T*/,
                'value'         =>/*T*/''/*/T*/
            ),
            /*T*/'Tech City'/*/T*/ => array (
                'type'          =>'text',
                'description'   =>/*T*/'Tech Contact City'/*/T*/,
                'value'         =>/*T*/''/*/T*/
            ),
            /*T*/'Tech State'/*/T*/ => array (
                'type'          =>'text',
                'description'   =>/*T*/'Tech Contact State / Province'/*/T*/,
                'value'         =>/*T*/''/*/T*/
            ),
            /*T*/'Tech Country'/*/T*/ => array (
                'type'          =>'text',
                'description'   =>/*T*/'Tech Contact Country'/*/T*/,
                'value'         =>/*T*/''/*/T*/
            ),
            /*T*/'Tech Postal Code'/*/T*/ => array (
                'type'          =>'text',
                'description'   =>/*T*/'Tech Contact Postal Code'/*/T*/,
                'value'         =>/*T*/''/*/T*/
            ),
            /*T*/'Tech Phone'/*/T*/ => array (
                'type'          =>'text',
                'description'   =>/*T*/'Tech Contact Phone Number'/*/T*/,
                'value'         =>/*T*/''/*/T*/
            ),
            /*T*/'Tech Organization'/*/T*/ => array (
                'type'          =>'text',
                'description'   =>/*T*/'Tech Contact Organization'/*/T*/,
                'value'         =>/*T*/''/*/T*/
            ),
            /*T*/'Tech Job Title'/*/T*/ => array (
                'type'          =>'text',
                'description'   =>/*T*/'Tech Contact Job Title'/*/T*/,
                'value'         =>/*T*/''/*/T*/
            )
        );

        return $variables;
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
    public function getAvailableActions($status)
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

    /**
     * Perform an action on the plugin. Should return new status of plugin
     *
     * @param UserPackage $userPackage     User's Package you want to perform an action on
     * @param string      $actionToPerform Action you wish to perform
     * @param array       $params          Optional params list to pass to action
     *
     * @return string new status
     */
    function doAction($userPackage, $actionToPerform, $params = false)
    {
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
        $prependedActionName = "do" . trim($actionToPerform);
        if (method_exists($this, $prependedActionName)) {
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
    function supportsAction($action)
    {
        $prependedActionName = "do" . trim($action);
        if (method_exists($this, $prependedActionName)) {
            return true;
        }
        return false;
    }

    public function supports($feature)
    {
        if (isset($this->features[$feature])) {
            return $this->features[$feature];
        }
        return false;
    }

    public function doUpdate()
    {
    }
}
