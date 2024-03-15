<?php

include_once("modules/clients/models/UserGateway.php");

/**
 * Client Module's Action Controller
 *
 * @category   Action
 * @package    Clients
 * @author     Alberto Vasquez <alberto@clientexec.com>
 * @license    http://www.clientexec.com  ClientExec License Agreement
 * @link       http://www.clientexec.com
 */
class Clients_UserpublicController extends CE_Controller_Action {

    var $moduleName = "clients";

	protected function gettaxAction()
    {    	
        if (!isset($_GET['ignoreuser']) || $_GET['ignoreuser'] != 1) {
            // special handling for users who are logged in.
            // ignore guests and admins though
            if ($this->user->getEmail() != '' && !$this->user->isAdmin()) {
                $country = $this->user->getCountry();
                $state = $this->user->getState();
            }
        } else {
        	$country = $this->getParam('country',FILTER_SANITIZE_STRING,"");
        	$state = $this->getParam('state',FILTER_SANITIZE_STRING,"");
        }

        include_once 'modules/billing/models/BillingGateway.php';
        $billingGateway = new BillingGateway($this->user);
        $taxes = $billingGateway->getTaxes($country, $state);

        $default = 0;
        if ($country == $this->settings->get('Default Country')) {
            $default = 1;
        }

        $this->send(array("taxes"=>$taxes,"default"=>$default));
    }

}