<?php
include_once "modules/billing/models/BillingGateway.php";

/**
 * Client Module's Action Controller
 *
 * @category   Action
 * @package    Clients
 * @author     Alberto Vasquez <alberto@clientexec.com>
 * @license    http://www.clientexec.com  ClientExec License Agreement
 * @link       http://www.clientexec.com
 */
class Billing_IndexpublicController extends CE_Controller_Action {

    var $moduleName = "billing";

    /**
     * Forward pay invoice link created externally by validating hash
     * Requires invoice and t
     *
     * @return json
     */
    protected function paymentforwarderAction()
    {
        if ((!isset($_REQUEST['invoice'])) || (!isset($_REQUEST['t']) )) {
            CE_Lib::redirectPage("index.php", $this->user->lang("We are sorry.  You have either reached this page incorrectly or your invoice has already been paid."));
        }
        $hash = filter_var($_REQUEST['t'],FILTER_SANITIZE_STRING);
        $invoiceId = filter_var($_REQUEST['invoice'],FILTER_SANITIZE_NUMBER_INT);
        $billingGateway = new BillingGateway($this->user);
        $ret = $billingGateway->forwardPaymentToGatewayFromDirectLink($invoiceId,$hash);
        $this->error = $ret['error'];
        $this->message = $ret['message'];
        $this->send();
    }

    protected function checkvatAction()
    {
        $this->disableLayout();

        include_once 'modules/billing/models/TaxGateway.php';
        $taxGateway = new TaxGateway($this->user);

        if(!isset($_GET['ignoreuser']) || $_GET['ignoreuser'] != 1){
            // special handling for users who are logged in.
            // ignore guests and admins though
            if($this->user->getEmail() != '' && !$this->user->isAdmin()){
                $_GET['country'] = $this->user->getCountry();
                $_GET['vat'] = $this->user->getVatNumber();
            }
        }

        $userID = false;
        if(isset($_GET['userid'])){
            $userID = $_GET['userid'];
        }

        $needVAT = '';
        if(isset($_GET['state'])){
            // Get the tax information
            include_once 'modules/billing/models/BillingGateway.php';
            $billingGateway = new BillingGateway($this->user);
            $taxes = $billingGateway->getTaxes($_GET['country'], $_GET['state']);
            $needVAT = ($taxes['isEUcountry'])? '|1' : '|0';
        }

        $response = $taxGateway->ValidateVAT($_GET['country'], $_GET['vat'], $userID);
        $this->send(array('responseText' => implode('|', $response).$needVAT));
    }

     /**
     * Initial entry point for gateway callbacks
     */
    protected function gatewaycallbackAction()
    {

        $this->disableLayout();
        $plugin = $this->getParam('plugin',FILTER_SANITIZE_STRING);

        if (strpos($plugin, '.') !== false || strpos($plugin, '/') !== false) {
            die('Invalid Plugin Name');
        }

        $pluginClass = 'Plugin'.ucfirst($plugin).'Callback';
        if (!file_exists('plugins/gateways/'.$plugin.'/'.$pluginClass.'.php')) {
            echo 'The file plugins/gateways/'.$plugin.'/'.$pluginClass.'.php does not exist.';
            exit;
        }
        require_once 'plugins/gateways/'.$plugin.'/'.$pluginClass.'.php';
        $callback = new $pluginClass();
        $callback->setUser($this->user);
        $callback->setCustomer($this->customer);
        $callback->processCallback();

        $pluginClass = 'Plugin'.ucfirst(strtolower($plugin));
        if (!file_exists('plugins/gateways/'.$plugin.'/'.$pluginClass.'.php')) {
            echo 'The file plugins/gateways/'.$plugin.'/'.$pluginClass.'.php does not exist.';
            exit;
        }
        require_once 'plugins/gateways/'.$plugin.'/'.$pluginClass.'.php';
        $tplugin = new $pluginClass($this->user, 1);
        $tplugin->setInternalName($plugin);
        $tplugin->trackUsage("gateways","callback");

    }
}