<?php

include_once 'modules/admin/models/StatusAliasGateway.php' ;
include_once 'modules/clients/models/UserPackageGateway.php';

/**
 * Description of Settingsgroup
 *
 * @author alberto
 */
class CE_View_Helper_Profileproductheader extends CE_View_Helper_Abstract{

    public function profileproductheader($customer, $customOutput=null){

        UserGateway::ensureCustomerIsValid($customer);

        $this->view->customerId = $customer->getId();

        $packageid = $this->getParam('id',FILTER_VALIDATE_INT);
        $activetab = $this->getParam('selectedtab',FILTER_SANITIZE_STRING,"groupinfo");
        $this->view->packageid = $packageid;

        $UserPackageGateway = new UserPackageGateway($this->view->user,$this->customer);

        $products = $UserPackageGateway->getUserPackages($customer->getId(),$packageid);
        $this->view->productinfo = $products['results'][0];
        $this->view->activetab = $activetab;

        include_once "modules/admin/models/PluginGateway.php";
        $pg = new PluginGateway($this->view->user);
        $snapins = $pg->getMappingsForView('admin_profileproducttab', $products['results'][0]['producttype']);

        if (count($snapins) > 0){
            //let's see if we ara in a snapin
            $this->view->product_snapin_name = $this->getParam('name',FILTER_SANITIZE_STRING,"");
            $this->view->product_snapin_key = $this->getParam('key',FILTER_SANITIZE_STRING,"");
        }
        $this->view->product_tab_snapins = $snapins;

        if ( $customOutput != null ) {
            $this->view->customOutput = $customOutput;
        }

        return $this->view->render('userprofile/profileproductheader.phtml');

    }

}
