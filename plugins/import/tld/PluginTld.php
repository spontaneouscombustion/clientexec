<?php

require_once 'modules/admin/models/ImportPlugin.php';

class PluginTld extends ImportPlugin
{
    public $_title;
    public $_description;

    private $registrars = [];

    public function __construct()
    {
        $this->_title = lang('TLDs');
        $this->_description = lang("This import plugin imports TLDs and sets a default price.");
        parent::__construct($this->user);
    }

    /**
     * Returns form for domain importing
     *
     * @return html
     */
    function getForm()
    {
        $this->view->registrars = $this->getSupportedRegistrars();
        $packageGroups = PackageTypeGateway::getPackageTypes(PACKAGE_TYPE_DOMAIN, 'type');
        while ($group = $packageGroups->fetch()) {
            $groups[] = $group;
        }
        $this->view->productGroups = $groups;

        return $this->view->render('PluginTld.phtml');
    }

    private function getSupportedRegistrars()
    {
        $registrars = [];
        $plugins = new NE_PluginCollection("registrars", $this->user);
        while ($plugin = $plugins->getNext()) {
            if ($plugin->supports('importPrices')) {
                 $registrars[] = $plugin->getInternalName();
            }
        }
        return $registrars;
    }
}
