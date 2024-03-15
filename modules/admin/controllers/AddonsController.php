<?php

require_once("modules/admin/models/AddonGateway.php");

/**
 * @category View
 * @package  Admin
 * @author   Alberto Vasquez <alberto@clientexec.com>
 */
class Admin_AddonsController extends CE_Controller_Action
{

    var $moduleName = "admin";

    protected function saveproductaddonAction()
    {
        $this->checkPermissions('admin_edit_packagetypes');

        $productgroups = $this->getParam('productgroups');
        $productaddonid = (int) $this->getParam('id', FILTER_SANITIZE_NUMBER_INT, 0);
        $pluginoption = $this->getParam('pluginoption', FILTER_SANITIZE_STRING, "NONE");
        $productprices = (isset($_REQUEST['addonpricing'])) ? $_REQUEST['addonpricing'] : array();
        $custompluginvariable_value = $this->getParam('custompluginvariable_value', FILTER_SANITIZE_STRING, "");

        $vars = array();
        $languages = CE_Lib::getEnabledLanguages(true);
        if (count($languages) > 1) {
            $defaultLanguage = true;
            foreach ($languages as $languageKey => $language) {
                $vars['product-addon-name'.$languageKey]        = trim($this->getParam('product-addon-name'.$languageKey, FILTER_SANITIZE_STRING, ""));
                $vars['product-addon-description'.$languageKey] = $this->getParam('product-addon-description'.$languageKey, null, "");

                if ($defaultLanguage) {
                    $vars['product-addon-name']        = $vars['product-addon-name'.$languageKey];
                    $vars['product-addon-description'] = $vars['product-addon-description'.$languageKey];
                    $defaultLanguage = false;
                }
            }
        } else {
            $vars['product-addon-name']        = trim($this->getParam('product-addon-name', FILTER_SANITIZE_STRING, ""));
            $vars['product-addon-description'] = $this->getParam('product-addon-description', null, "");
        }

        if ($productaddonid !== 0) {
            $addon = new Addon($productaddonid);
        } else {
            $addon = new Addon(false);
            $addon->save();
            $productaddonid = $addon->getId();
        }

        //need to load old prices before they get deleted, to be able to compare later if prices are overridden
        $addon->getOldPrices();
        $addon->getOldPricesAllCurrencies();
        $addon->resetPrices();
        $addon->resetPricesAllCurrencies();

        $addon->setDescription($vars['product-addon-description']);
        $addon->setName($vars['product-addon-name']);

        //need to properly set this
        if ($pluginoption == "CUSTOM") {
            $pluginoption = "CUSTOM_".$custompluginvariable_value;
        }
        $addon->setPluginVar($pluginoption);

        //trying to save without a product name
        if (!is_array($productgroups)) {
            $this->error = true;
            $this->message = "You must select at least one product before saving";
            $this->send();
            return;
        }

        include_once 'modules/admin/models/PackageType.php';
        $packageType = new PackageType($productgroups[0]);

        //Clear price values for unused billing cycles in domain product groups
        if ($packageType->type == PACKAGE_TYPE_DOMAIN) {
            //Billing Cycles
            $billingcycles = array();

            include_once 'modules/billing/models/BillingCycleGateway.php';
            $gateway = new BillingCycleGateway();
            $iterator = $gateway->getBillingCycles(array(), array('order_value', 'ASC'));

            while ($cycle = $iterator->fetch()) {
                if ($cycle->id != 0 && $cycle->time_unit != 'y') {
                    $billingCyclesIdsToClear[] = $cycle->id;
                }
            }
            //Billing Cycles

            foreach ($productprices as $optionKey => $option) {
                foreach ($option as $priceKey => $priceValue) {
                    if (strpos($priceKey, '_force') !== false) {
                        $productprices[$optionKey][$priceKey] = 0;
                    } else {
                        foreach ($billingCyclesIdsToClear as $billingCycleId) {
                            if (@substr_compare($priceKey, 'price'.$billingCycleId, -strlen('price'.$billingCycleId)) === 0) {
                                $productprices[$optionKey][$priceKey] = '';
                            }
                        }
                    }
                }
            }
        }

        $oldproductgroups = $addon->getAddonProductGroups();
        $productgroups = implode(",", $productgroups);
        $addon->setProductGroupId($productgroups);

        //let's create array of prices that need to be returned with valid ids
        $newidstoreturn = array();

        include_once 'modules/admin/models/Translations.php';
        $translations = new Translations();

        $addon->deleteAddonPricesTranslations();

        //let's set pricing up
        if (count($productprices) == 0) {
            $addon->prices = array();
        } else {
            //let's add pricing
            foreach ($productprices as $option) {
                if (count($languages) > 1) {
                    //Default language is the first option always, so no need to continue iterating the array.
                    foreach ($languages as $languageKey => $language) {
                        $option['detail'] = $option['optionname'.$languageKey];
                        break;
                    }
                }

                //empty options would be id: 0 and optionname: ""
                if ($option['id'] == 0 && trim($option['detail']) == "") {
                    continue;
                }
                $tprice = $addon->insertPrice($option);
                $adonPriceId = $tprice;
                if (($option['id'] == 0)) {
                    //let's add ids to return
                    $newidstoreturn[] = array("oldid"=>$option['newid'], "newid" => $tprice);
                }

                if (count($languages) > 1) {
                    foreach ($languages as $languageKey => $language) {
                        if ($option['optionname'.$languageKey] != '') {
                            $translations->setValue(ADDON_OPTION_LABEL, $adonPriceId, $language, $option['optionname'.$languageKey]);
                        }
                    }
                }
            }
        }

        $this->message = "Your addon was saved successfully";
        $addon->save();

        $addonID = $addon->getId();
        $translations->deleteAllValues(ADDON_NAME, $addonID);
        $translations->deleteAllValues(ADDON_DESCRIPTION, $addonID);
        if (count($languages) > 1) {
            foreach ($languages as $languageKey => $language) {
                if ($vars['product-addon-name'.$languageKey] != '') {
                    $translations->setValue(ADDON_NAME, $addonID, $language, $vars['product-addon-name'.$languageKey]);
                }
                if ($vars['product-addon-description'.$languageKey] != '') {
                    $translations->setValue(ADDON_DESCRIPTION, $addonID, $language, $vars['product-addon-description'.$languageKey]);
                }
            }
        }

        if ($oldproductgroups != '') {
            $oldproductgroupsArray = explode(",", $oldproductgroups);
            $productgroupsArray = explode(",", $productgroups);
            $oldnotnewproductgroupsArray = array_diff($oldproductgroupsArray, $productgroupsArray);

            //If removed from some Product Groups, revalidate their addons in the packages
            if (count($oldnotnewproductgroupsArray) > 0) {
                $keeprecurringfees = (int) $this->getParam('keeprecurringfees', FILTER_SANITIZE_NUMBER_INT, 1);
                $ids = array(
                    $addonID
                );

                include_once 'modules/clients/models/UserPackageGateway.php';
                $userPackageGateway = new UserPackageGateway($this->user);
                $query = "SELECT `id` "
                    ."FROM `package` "
                    ."WHERE `planid` IN (" . implode(",", $oldnotnewproductgroupsArray) . ") ";
                $result = $this->db->query($query);
                while ($row = $result->fetch()) {
                    $userPackageGateway->removeAddonToPackages($row['id'], $ids, $keeprecurringfees);
                }
            }
        }

        $this->send(array("id"=>$productaddonid,"newids"=>$newidstoreturn));
    }

    /**
     * getAddonVariables by a given type
     *
     * @access protected
     * @return json
     */
    protected function getaddonvariablesAction()
    {
        include_once 'modules/admin/models/PackageAddonGateway.php';
        $gateway = new PackageAddonGateway();
        $productType = $this->getParam('productType', FILTER_SANITIZE_NUMBER_INT);
        $arr = $gateway->getPluginVariableTypesByProductType($productType);

        $aAddons = array();
        foreach ($arr as $key => $val) {
            if (!isset($val['available_in'])) {
                $val['available_in'] = "";
            }
            $aAddons[] = array("plugin_var" => $key,
                "name" => $val['lang'],
                "description" => $val['description'],
                'available_in' => $val['available_in']);
        }

        $this->send(array("addons" => $aAddons, "totalcount" => count($aAddons)));
    }


    /**
     * delete addon
     * @return json
     * This one will be deprecated in the future
     */
    protected function deleteaddonAction()
    {
        $this->checkPermissions('admin_edit_packagetypes');

        $AddonGateway = new AddonGateway();
        $addonids = $this->getParam('ids');
        $deleteResult= $AddonGateway->deleteAddon($addonids[0]);

        if (is_array($deleteResult)) {
            include_once 'modules/clients/models/UserPackage.php';
            $domainsUsingAddon =  array();
            $error="You can't delete this addon until you change the following domains\\packages to use the default option (None):\n";
            $count = 0;
            $limit = 5;
            foreach ($deleteResult as $UserPackageId) {
                if ($count >= $limit) {
                    break;
                }
                $UserPackage = new UserPackage($UserPackageId);
                $tCustomer = new user($UserPackage->CustomerId);
                $error .= "\nDomainId: " . $UserPackage->GetDisplayName() . " - User: " . $tCustomer->getFullName();
                $count++;
            }
            $count = count($deleteResult);
            if ($count > $limit) {
                $error .= "\n" . "... and ".($count - $limit)." many more.";
            }
            $this->error = 1;
            $this->message = $error;
            $this->send();
        } else {
            $this->send();
        }
    }

    /**
     * delete addon
     * @return json
     */
    protected function deleteaddonwithrevalidationAction()
    {
        $this->checkPermissions('admin_edit_packagetypes');

        include_once("modules/admin/models/Package.php");
        include_once 'modules/clients/models/UserPackageGateway.php';

        $keeprecurringfees = $this->getParam('keeprecurringfees');
        $ids = $this->getParam('ids');

        $userPackageGateway = new UserPackageGateway($this->user);

        if (count($ids) > 0) {
            $query = "SELECT DISTINCT `product_id` "
                ."FROM `product_addon` "
                ."WHERE `addon_id` IN (".$this->db->escape(implode(', ', $ids)).") ";
            $result = $this->db->query($query);

            while ($row = $result->fetch()) {
                $package = new Package($row['product_id']);
                $addonsRemoved = $package->deleteAddonsById($ids);
            }

            $userPackageGateway->removeAddons($ids, $keeprecurringfees);

            $AddonGateway = new AddonGateway();
            foreach ($ids as $id) {
                $AddonGateway->deleteAddon($id);
            }
        }

        $this->send();
    }

    /**
     * returns content for addon window
     * @return html
     */
    protected function productaddonAction()
    {
        $this->checkPermissions("admin_view_packagetypes");
        $this->jsLibs = array('templates/admin/js/jquery.tablednd.js','templates/admin/views/admin/addons/productaddon.js');
        $this->cssPages = array('templates/admin/views/admin/addons/productaddon.css');

        $productaddonid = $this->getParam('id', FILTER_SANITIZE_NUMBER_INT, 0);
        $this->view->productAddonsPrices = array();
        $this->view->productAddonsPricesCurrencies = array();

        $languages = CE_Lib::getEnabledLanguages();
        if (count($languages) > 1) {
            $data2 = array();
            $data2['addonNameLanguages'] = array();
            $data2['descriptionLanguages'] = array();
        }

        //Billing Cycles
        $billingcycles = array();

        include_once 'modules/billing/models/BillingCycleGateway.php';
        $gateway = new BillingCycleGateway();
        $iterator = $gateway->getBillingCycles(array(), array('order_value', 'ASC'));

        while ($cycle = $iterator->fetch()) {
            $billingcycles[$cycle->id] = array(
                'name'      => $this->user->lang($cycle->name),
                'time_unit' => $cycle->time_unit
            );
        }
        //Billing Cycles

        $priceExtraData = array();

        foreach ($billingcycles as $billingCycleId => $billingCycleData) {
            if ($billingCycleId == 0) {
                //One Time Cycle is really the Setup Price
                $priceExtraData[$billingCycleId]['name'] = 'Setup';
                $priceExtraData[$billingCycleId]['class'] = array(
                    'displayIfOther',
                    'displayIfDomain'
                );
            } else {
                $priceExtraData[$billingCycleId]['name'] = $billingCycleData['name'];

                if ($billingCycleData['time_unit'] == 'y') {
                    $priceExtraData[$billingCycleId]['class'] = array(
                        'displayIfOther',
                        'displayIfDomain'
                    );
                } else {
                    $priceExtraData[$billingCycleId]['class'] = array(
                        'displayIfOther'
                    );
                }
            }
        }

        $this->view->productAddonsPricesExtraData = $priceExtraData;

        //Code for prices in different currencies
        include_once 'modules/billing/models/CurrencyGateway.php';
        $gateway = new CurrencyGateway($this->user);
        $currencies = $gateway->GetCurrencies();

        if ($productaddonid !== 0) {
            $this->title = $this->user->lang('Edit Addon');
            $addon = new Addon($productaddonid);
            $prices = $addon->getPrices(true);

            foreach ($prices as $price) {
                $this->view->productAddonsPrices[] =  $price;
            }

            if ($currencies['totalcount'] > 1) {
                $this->view->productAddonsPricesCurrencies = $addon->getPricesAllCurrencies(true);
            }

            $languages = CE_Lib::getEnabledLanguages(true);

            if (count($languages) > 1) {
                include_once 'modules/admin/models/Translations.php';
                $translations = new Translations();

                foreach ($languages as $languageKey => $language) {
                    $data2['addonNameLanguages'][$languageKey] = $translations->getValue(ADDON_NAME, $productaddonid, $language, $addon->getName());

                    // Replace the % sign with the HTML code to avoid breaking the JSON
                    $data2['descriptionLanguages'][$languageKey] = str_replace('%', "&#37;", $translations->getValue(ADDON_DESCRIPTION, $productaddonid, $language, $addon->getDescription()));

                    foreach ($this->view->productAddonsPrices as &$productAddonsPrice) {
                        $productAddonsPrice['detailLanguages'][$languageKey] = $translations->getValue(ADDON_OPTION_LABEL, $productAddonsPrice['id'], $language, $productAddonsPrice['detail']);
                    }
                }
                $this->view->assign($data2);
            }

            $this->view->addonName = $addon->getName();
            $this->view->description = $addon->getDescription();
            $this->view->plugin_var = $addon->getPluginVarName();
            $this->view->order = $addon->getOrder();
            $this->view->taxable = $addon->getTaxable();
            $this->view->productgroup_ids = explode(",", $addon->getAddonProductGroups());
            $this->view->grouptype = $addon->getProductGroupType();
            $this->view->pluginvar = $addon->getPluginVar();
        } else {
            $this->title = $this->user->lang('Add New Addon');
            //let's set some defaults for new product addons
            $this->view->addonName = "";
            $this->view->description = "";
            $this->view->plugin_var = "";
            $this->view->order = "";
            $this->view->taxable = "";
            $this->view->productgroup_ids = array();
            $this->view->grouptype = -1;
            $this->view->pluginvar = "NONE";

            $addonGateway = new AddonGateway($this->user);
            $this->view->productAddonsPrices = $addonGateway->getNonePrice();

            if ($currencies['totalcount'] > 1) {
                foreach ($currencies['currencies'] as $currencyValues) {
                    $this->view->productAddonsPricesCurrencies[$currencyValues['abrv']] = $addonGateway->getNonePrice();
                }
            }

            if (count($languages) > 1) {
                foreach ($languages as $languageKey => $language) {
                    $data2['addonNameLanguages'][$languageKey]   = '';
                    $data2['descriptionLanguages'][$languageKey] = '';

                    foreach ($this->view->productAddonsPrices as &$productAddonsPrice) {
                        $productAddonsPrice['detailLanguages'][$languageKey] = $productAddonsPrice['detail'];
                    }
                }
                $this->view->assign($data2);
            }
        }

        include_once "modules/admin/models/PackageTypeGateway.php";
        $gateway = new PackageTypeGateway($this->user);
        $this->view->productgroups = $gateway->getProductGroupsGroupedByType();
        $this->view->productaddonid = $productaddonid;
    }

    /**
     * addons list
     */
    protected function productaddonsAction()
    {
        $this->checkPermissions("admin_view_packagetypes");
        $this->jsLibs = array('templates/admin/views/admin/addons/productaddons.js');
        $this->title = $this->user->lang("Product Addons");
        include_once "modules/admin/models/PackageTypeGateway.php";
        $gateway = new PackageTypeGateway($this->user);

        $this->view->selected_group = $this->getParam("groupid", FILTER_SANITIZE_STRING, "");
        $this->view->productgroups = $gateway->getProductGroupsGroupedByType();
    }

    /**
     * Returns all addons or addons for a selected group
     *
     * @return json
     * @access protected
     */
    protected function admingetaddonsAction()
    {
        $this->checkPermissions("admin_view_packagetypes");

        $addonGateway = new AddonGateway($this->user);

        $groupId = $this->getParam('filter', FILTER_VALIDATE_INT);
        $this->send($addonGateway->getAllAddons($groupId));
    }

    /**
     * Returns list of products that use a given addon id
     *
     * @return json
     * @access protected
     */
    protected function admingetaddonsusedAction()
    {
        include_once("modules/admin/models/Package.php");
        $addonid = json_decode($_REQUEST['addonid']);
        $gateway = new AddonGateway();
        $ids = $gateway->getProductsThatUseAddon($addonid);
        $datalist = array();
        foreach ($ids as $id) {
            $data = array();
            $package = new Package($id);
            $data["productid"] = $id;
            $data["productname"] = $package->planname;
            $datalist[] = $data;
        }
        $this->send(array("products" => $datalist));
    }
}
