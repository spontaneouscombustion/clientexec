<?php

include_once 'modules/clients/models/UserPackageGateway.php';
include_once "modules/admin/models/PluginGateway.php";

class CE_View_Helper_Publicproductheader extends CE_View_Helper_Abstract
{
    public function publicproductheader($user, $customOutput = null)
    {
        $productId = $this->getParam('id', FILTER_SANITIZE_NUMBER_INT);
        $view = $this->getParam('view', FILTER_SANITIZE_STRING);
        $userPackage = new UserPackage($productId);
        $userPackageGateway = new UserPackageGateway($user);

        //ensure viewer owns the id
        if ($user->getId() != $userPackage->getCustomerId()) {
            //CE_Lib::addErrorMessage($user->lang('You are trying to access a product that does not exist.'));
            CE_Lib::redirectPage("index.php?fuse=clients&controller=products&view=products");
        }

        switch ($view) {
            case 'product':
                $this->view->pageTitle = $user->lang('Package Details');
                break;
            case 'productdomaincontactinfo':
                $this->view->pageTitle = $user->lang('Contact Information');
                break;
            case 'productdomainhosts':
                $this->view->pageTitle = $user->lang('Host Records');
                break;
            case 'productdomainnameservers':
                $this->view->pageTitle = $user->lang('Name Servers');
                break;
        }


        //if we have plugin we need to see if it has a view
        //if so let's have plugin handle it's own main action view
        $tabs = array();
        $tabs[] = array(
            "name" => $user->lang('Overview'),
            "class" => $active_class,
            "type" => "plugin_actions",
            "view" => "product",
            'fa' => 'fa fa-th-large fa-fw'
        );

        //grabbing package data including the tabs we need for this type
        $data = [];
        $userPackageGateway->getFieldsByProductType($userPackage, $data);

        $tabs = array_merge($tabs, $data['public_tabs']);

        $pluginGateway = new PluginGateway($this->view->user);
        $snapins = $pluginGateway->getMappingsForView('public_profileproducttab', $userPackage->getProductType());

        if (count($snapins) > 0) {
            $this->view->productSnapinName = $this->getParam('name', FILTER_SANITIZE_STRING, "");
            $this->view->productSnapinKey = $this->getParam('key', FILTER_SANITIZE_STRING, "");
        }
        $this->view->product_tab_snapins = $snapins;
        $this->view->publicPanels = $userPackageGateway->getPublicPanels($userPackage);
        $publicPanel = $this->getParam('publicPanel', FILTER_VALIDATE_INT, 0, false);
        if ($publicPanel == 1) {
            $this->view->activePublicTab = $this->getParam('key', FILTER_SANITIZE_STRING, "");
        }

        $newTabsWithActiveClass = array();
        foreach ($tabs as $tab) {
            $active_class = "";
            if ($view === $tab['view']) {
                $active_class = "active";
            }
            $tab['class'] = $active_class;
            $newTabsWithActiveClass[] = $tab;
        }

        $this->view->tabs = $newTabsWithActiveClass;

        $languageKey = ucfirst(strtolower($user->getLanguage()));
        $this->view->productName = $userPackage->getReference(true, true, '', $languageKey);

        $this->view->productId = $productId;
        $this->view->pluginActions = $userPackageGateway->getPackageActions($productId);


        if ($customOutput != null) {
            $this->view->customOutput = $customOutput;
        }

        return $this->view->render('productspublic/productheader.phtml');
    }
}
