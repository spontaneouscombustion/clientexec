<?php

require_once 'modules/admin/models/ActiveOrderGateway.php';

class Admin_SignuppublicController extends CE_Controller_Action
{
    public $moduleName = "admin";

    private function initializeView()
    {
        // Verify if there are Payment Processors configured
        $plugins = new NE_PluginCollection("gateways", $this->user);
        $thereAreBillingPlugins = false;
        while ($tplugin = $plugins->getNext()) {
            //we have to make sure it isn't grabbing from logged in Admin
            if ($tplugin->getVariable("In Signup") || (($this->user->getPaymentType() == $tplugin->getInternalName()) && $this->user->getPaymentType() != "0" && !$this->user->IsAdmin())) {
                $thereAreBillingPlugins = true;
            }
        }
        if (!$thereAreBillingPlugins) {
            CE_Lib::redirectPage('index.php?fuse=home&view=main', $this->user->lang('There are no Payment Processors configured!'));
        }

        include_once 'modules/admin/models/PackageType.php';

        $aogateway = new ActiveOrderGateway($this->user);

        // Override the cart
        if (@$_GET['cleanCart']) {
            $aogateway->destroyTempItem(true);
            $aogateway->destroyCart();
            $aogateway->destroyCurrency();
            $aogateway->destroyCouponCode();
            $aogateway->destroyInvoiceInformation();
            CE_Lib::redirectPage('order.php');
        }

        $ret_view = [];
        $ret_view['step'] = $this->getParam('step', FILTER_SANITIZE_STRING, "0");

        //we need to pull the style here based on what the group is
        $ret_view['productgroup'] = $this->getParam('productGroup', FILTER_VALIDATE_INT, 0);
        $ret_view['product'] = $this->getParam('product', FILTER_VALIDATE_INT, 0);
        $ret_view['bundled'] = $this->getParam('bundled', FILTER_SANITIZE_NUMBER_INT, 0);

        //Billing Cycles
        $defaultCycle = false;
        $billingcycles = [];

        include_once 'modules/billing/models/BillingCycleGateway.php';
        $gateway = new BillingCycleGateway();
        $iterator = $gateway->getBillingCycles(array(), array('order_value', 'ASC'));

        while ($cycle = $iterator->fetch()) {
            if ($defaultCycle === false && $cycle->id != 0) {
                $defaultCycle = $cycle->id;
            }

            $billingcycles[$cycle->id] = $cycle->id;
            $billingcycles[$cycle->amount_of_units . $cycle->time_unit] = $cycle->id;
            $billingcycles[$cycle->time_unit . $cycle->amount_of_units] = $cycle->id;
        }
        $ret_view['billingcycles'] = $billingcycles;
        //Billing Cycles

        if ($defaultCycle === false) {
            $defaultCycle = 0;
        }

        $currency = new Currency($this->user);

        if (!$this->user->getCurrency()) {
            $this->user->setCurrency($this->settings->get('Default Currency'));
        }

        if ($this->session->currency != '') {
            $currencyCode = base64_decode($this->session->currency);
        } else {
            $currencyCode = $this->user->getCurrency();
        }

        $decimalsSep = $currency->getDecimalsSeparator($currencyCode);
        $thousandsSep = $currency->getThousandsSeparator($currencyCode);

        $ret_view['currency'] = [
            'symbol' => $currency->ShowCurrencySymbol($currencyCode, "NONE", true),
            'decimalsSep' => ($decimalsSep === ' ') ? '&nbsp;' : $decimalsSep,
            'thousandsSep' => ($thousandssep === ' ') ? '&nbsp;' : $thousandsSep,
            'alignment' => ($currency->getAlignment($currencyCode) == 'left') ? "%s%v" : "%v%s",
            'precision' => $currency->getPrecision($currencyCode),
            'abrv' => $currency->getAbbr($currencyCode),
            'showabrv' => ($this->settings->get('Show Currency Code') == 1) ? ' ' . $currency->getAbbr($currencyCode) : ''
        ];

        // This code will set the paymentterm value even if not set, when there is only 1 billing cycle available for the product
        if ($ret_view['product'] > 0) {
            $tPackage = new Package($ret_view['product']);
            $tPackage->getProductPricingAllCurrencies();
            $productPricing = $aogateway->prettyPricing($tPackage->pricingInformationCurrency[$currencyCode], $defaultCycle);

            if (count($productPricing) == 1) {
                $defaultCycle = $productPricing[0]['termId'];
            }
        }

        $paymentterm = $this->getParam('paymentterm', FILTER_SANITIZE_STRING, '');
        if ($paymentterm === '') {
            $paymentterm = $this->getParam('paymentTerm', FILTER_SANITIZE_STRING, "$defaultCycle");
        }

        if (isset($billingcycles[$paymentterm])) {
            //The billing cycle exists with the provided id, or with the provided time unit and amount of units
            $ret_view['paymentterm'] = $billingcycles[$paymentterm];
        } elseif (is_numeric($paymentterm) && isset($billingcycles[$paymentterm . 'm'])) {
            //The billing cycle exists with the provided amount of months
            $ret_view['paymentterm'] = $billingcycles[$paymentterm . 'm'];
        } else {
            //Use the billing cycle with the lowest available amount of time
            $ret_view['paymentterm'] = $defaultCycle;
        }

        $ret_view['tempInformation'] = [];
        //CE_Lib::debug($ret_view['tempItem']);

        //if productgroup 0 then just get first one that is available on signup and set that as the selected one
        //if productgroup 0 but product is set grap the productgroup so we don't need to pass both ids
        //lets get the productgroup if we were passed a product without the productgroup
        if ($ret_view['product'] > 0 && $ret_view['productgroup'] == 0) {
            $tPackage = new Package($ret_view['product']);
            if ($tPackage->planname == "") {
                CE_Lib::addErrorMessage($this->user->lang("The selected product does not exist"));
                CE_Lib::redirectPage("order.php?step=1");
            }

            $ret_view['productgroup'] = $tPackage->planid;
        } elseif ($ret_view['product'] > 0) {
            $tPackage = new Package($ret_view['product']);
            if ($tPackage->planname == "") {
                CE_Lib::addErrorMessage($this->user->lang("The selected product does not exist"));
                CE_Lib::redirectPage("order.php?step=1");
            }
        }

        $ret_view['summary'] = $aogateway->getCartSummary();

        if ($ret_view['productgroup'] == 0) {
            include_once 'modules/admin/models/PackageTypeGateway.php';
            $packageTypes = PackageTypeGateway::getDefaultPackageTypes("");
            while ($packageType = $packageTypes->fetch()) {
                if ($packageType->inSignup()) {
                    $ret_view['productgroup'] = $packageType->fields['id'];
                    unset($packageType);
                    break;
                }
            }
        }

        $packageType = new PackageType($ret_view['productgroup']);
        $ret_view['packageType'] = $packageType->getType();

        // if we have 0 bundled products, then we need to go back to step 1, so we don't allow the domain to be ordered without the hosting package.
        if ($ret_view['bundled'] == 1 && (!isset($this->session->bundledProducts) || count($this->session->bundledProducts) == 0)) {
            CE_Lib::addErrorMessage($this->user->lang("The bundled product has not been configured yet"));
            CE_Lib::redirectPage("order.php?step=1");
        }

        // if we have a domain product that is not bundled, but its product group is not included in signup, then we need to go back to step 1, so we don't allow the domain to be ordered.
        if ($ret_view['packageType'] == PACKAGE_TYPE_DOMAIN && $ret_view['bundled'] == 0 && !$packageType->inSignup()) {
            CE_Lib::addErrorMessage($this->user->lang("The selected product is not available"));
            CE_Lib::redirectPage("order.php?step=1");
        }

        $path = [];
        $path[] = APPLICATION_PATH . '/../templates/order_forms/partials';
        $path[] = APPLICATION_PATH . '/../templates/order_forms/' . $packageType->getListStyle();
        $this->view->setScriptPath($path);
        $ret_view['order_form'] = $packageType->getListStyle();
        $ret_view['hideSetupFees'] = $this->settings->get('Hide Setup Fees');
        $ret_view['monthlyPriceBreakdown'] = $this->settings->get('Monthly Price Breakdown');
        $ret_view['showDiscountedPricingInBillingCycleSelector'] = $this->settings->get('Show Discounted Pricing in Billing Cycle Selector');
        $ret_view['showSaved'] = $this->settings->get('Include Saved Percentage');
        $ret_view['acceptCoupons'] = $this->settings->get('Accept Coupons');

        return $ret_view;
    }

    protected function cart0Action()
    {
        $activeOrderGateway = new ActiveOrderGateway($this->user);
        $languages = CE_Lib::getEnabledLanguages();
        $translations = new Translations();
        $languageKey = ucfirst(strtolower($this->user->getLanguage()));
        $packageTypes = PackageTypeGateway::getDefaultPackageTypes('');

        $this->view->assign($this->initializeView());
        $this->view->setLfiProtection(false);
        $this->title = $this->user->lang('Cart');

        $this->view->productGroups = [];
        if ($this->view->productgroup == 0) {
            $firstGroup = 0;
        } else {
            $firstGroup = $this->view->productgroup;
        }

        while ($packageType = $packageTypes->fetch()) {
            if ($packageType->insignup != true) {
                continue;
            }


            $this->view->productGroups[] = [
                'id' => $packageType->fields['id'],
                'name' => $packageType->fields['name'],
                'namelanguage' => $translations->getValue(PRODUCT_GROUP_NAME, $packageType->fields['id'], $languageKey, $packageType->fields['name']),
                'description' => $packageType->fields['description'],
                'descriptionlanguage' => $translations->getValue(PRODUCT_GROUP_DESCRIPTION, $packageType->fields['id'], $languageKey, $packageType->fields['description']),
                'type' => $packageType->fields['type'],
                'style' => $packageType->fields['style'],
                'advanced' => unserialize($packageType->fields['advanced']),
                'insignup' => $packageType->fields['insignup'],
            ];
        }

        $activeOrderGateway->clearAllOrdersForProductId();
    }

    protected function cart1Action()
    {
        include_once 'modules/admin/models/Translations.php';
        $languages = CE_Lib::getEnabledLanguages();
        $translations = new Translations();
        $languageKey = ucfirst(strtolower($this->user->getLanguage()));

        // if we are on step 1, and have this set, we need to unset it, or it causes issues.
        if (isset($this->session->on_back_send_to_invoice_id)) {
            unset($this->session->on_back_send_to_invoice_id);
        }

        $this->view->assign($this->initializeView());
        $this->view->setLfiProtection(false);

        $this->title = $this->user->lang('Step 1');
        $aogateway = new ActiveOrderGateway($this->user);

        $currencyCode = base64_decode($this->session->currency);

        // This code will take you to stept 2 if using a direct link and there is only 1 billing cycle available for the product or the style of the product group is compare
        if ($this->view->productgroup > 0 && $this->view->product > 0) {
            $tPackage = new Package($this->view->product);
            $tPackage->getProductPricingAllCurrencies();
            $productPricing = $aogateway->prettyPricing($tPackage->pricingInformationCurrency[$currencyCode], 1);
            $isCompare = ($tPackage->productGroup->fields['style'] == 'compare' ? true : false);

            if (count($productPricing) == 0) {
                CE_Lib::redirectPage("order.php");
            }

            if ($isCompare || count($productPricing) == 1) {
                CE_Lib::redirectPage("order.php?step=2&productGroup=" . $this->view->productgroup . "&product=" . $this->view->product . "&paymentterm=" . $this->view->paymentterm);
            }
        }

        if ($this->view->bundled == 0) {
            unset($this->session->bundledProducts);
            // if we're here, and not bundled, we should try to destroy temp item to prevent an issue with the temp item and the new product (this happens when someone doesn't fully finish configuring a product, and goes to another product)
            $aogateway->destroyTempItem();
        }

        //First Time through step 0
        include_once 'modules/admin/models/PackageTypeGateway.php';
        $packageTypes = PackageTypeGateway::getDefaultPackageTypes("");
        $plancount = $packageTypes->getNumItems();

        $this->view->productGroups = [];

        //Loop through all the packagetypes
        if ($this->view->productgroup == 0) {
            $firstgroup = 0;
        } else {
            $firstgroup = $this->view->productgroup;
        }

        while ($packageType = $packageTypes->fetch()) {
            if ($firstgroup == 0 && $packageType->inSignup()) {
                if ($this->view->packageType == null) {
                    $this->view->packageType = $packageType->fields['type'];
                }
                $firstgroup = $packageType->fields['id'];
                $this->view->productgroup = $firstgroup;
            }

            $this->view->productGroups[] = array(
                'id' => $packageType->fields['id'],
                'name' => $packageType->fields['name'],
                'namelanguage' => $translations->getValue(PRODUCT_GROUP_NAME, $packageType->fields['id'], $languageKey, $packageType->fields['name']),
                'description' => $packageType->fields['description'],
                'descriptionlanguage' => $translations->getValue(PRODUCT_GROUP_DESCRIPTION, $packageType->fields['id'], $languageKey, $packageType->fields['description']),
                'type' => $packageType->fields['type'],
                'style' => $packageType->fields['style'],
                'advanced' => unserialize($packageType->fields['advanced']),
                'insignup' => $packageType->fields['insignup'],
            );
        }

        //lets see if there are any products that are stock controled
        //that need to be cleared
        $aogateway->clearAllOrdersForProductId();

        //$productGroup = PackageTypeGateway::getPackageTypes($this->view->productgroup);
        //$productGroup = $productGroup->fetch();

        $isDomain = false;
        if ($this->view->packageType == PACKAGE_TYPE_DOMAIN) {
            $isDomain = true;
        }
        $this->view->packages = $aogateway->getPackageForSelectedGroup([
            "productGroup" => $firstgroup,
            "selectedProduct" => $this->view->product,
            "paymentterm" => $this->view->paymentterm
        ], $isDomain);

        $this->view->tempInformation['bundledProducts'] = $this->session->bundledProducts;

        $this->view->cartParentPackageId = 0;
        $this->view->cartParentPackageTerm = 0;

        if ($this->view->packageType == PACKAGE_TYPE_DOMAIN) {
            foreach ($this->view->productGroups as $group) {
                if ($this->view->productgroup == $group['id']) {
                    $this->view->group = $group;
                }
            }

            // Has somebody specified a domain name in the URL?
            if (isset($_GET['domainName'])) {
                if (isset($_GET['tld'])) {
                    $this->session->domainName = $this->getParam('domainName', FILTER_SANITIZE_STRING);
                    $this->session->tld = $this->getParam('tld', FILTER_SANITIZE_STRING);
                } else {
                    $domainNameGateway = new DomainNameGateway($this->user);
                    $split = $domainNameGateway->splitDomain($this->getParam('domainName', FILTER_SANITIZE_STRING));
                    $this->session->domainName = $split[0];
                    $this->session->tld = $split[1];
                }
            } elseif (isset($_REQUEST['domain']) && isset($_REQUEST['ext'])) {
                $this->session->domainName = $this->getParam('domain', FILTER_SANITIZE_STRING);
                $tld = $this->getParam('ext', FILTER_SANITIZE_STRING);
                if (substr($tld, 0, 1) == '.') {
                    $tld = substr($tld, 1);
                }
                $this->session->tld = $tld;
            }

            $this->session->autoSearchType = 'register';

            if (isset($_GET['autoSearchType'])) {
                $this->session->autoSearchType = $_GET['autoSearchType'];
            }

            include_once 'modules/admin/models/TopLevelDomainGateway.php';
            $TopLevelDomainGateway = new TopLevelDomainGateway();

            // ensure that we have valid pricing before showing the tld
            foreach ($this->view->packages as $key => $p) {
                $data = $TopLevelDomainGateway->getTLDData($p['id'], $currencyCode);

                // remove the registrar and taxable index if there is one.
                if (isset($data['registrar'])) {
                    unset($data['registrar']);
                }

                if (isset($data['taxable'])) {
                    unset($data['taxable']);
                }

                if (count($data) < 1) {
                    unset($this->view->packages[$key]);
                }
            }

            // allow passing domain name and tld to domain packages
            $this->view->domainName = '';
            $this->view->tld = '';

            if (isset($this->session->domainName) && isset($this->session->tld)) {
                $this->view->domainName = $this->session->domainName;
                $this->view->tld = $this->session->tld;

                unset($this->session->domainName);
                unset($this->session->tld);
            }

            if ($this->session->autoSearchType != '') {
                $this->view->autoSearchType = $this->session->autoSearchType;
                unset($this->session->autoSearchType);
            }

            // used for hosting package sub domains
            $this->view->subdomains = [];

            // check if we have sub domains, we only want to show sub-domains if we have a parent package (which means this is a bundle).
            if (isset($this->session->cartParentPackage)) {
                $cartItems = unserialize(base64_decode(@$this->session->cartContents));
                // get the product id of the parent package
                $productId = $cartItems[$this->session->cartParentPackage]['productId'];
                $this->view->cartParentPackageId = $productId;
                $this->view->cartParentPackageTerm = $cartItems[$this->session->cartParentPackage]['params']['term'];
                $package = new Package($productId);
                $advanced = unserialize($package->advanced);
                if (isset($advanced['subdomain']) && $advanced['subdomain'] != '') {
                    $this->view->subdomains = explode(';', $advanced['subdomain']);
                }
            }
        } else {
            foreach ($this->view->packages as $key => $p) {
                if (!is_array($p['pricing']) || count($p['pricing']) == 0) {
                    unset($this->view->packages[$key]);
                }
            }
        }

        $this->view->cartParentPackage = null;

        if (isset($this->session->cartParentPackage)) {
            $this->view->cartParentPackage = $this->session->cartParentPackage;
        }

        // check if we have savings to show.
        if (count($this->view->packages) == 0) {
            CE_Lib::redirectPage('index.php?fuse=home&view=main', $this->user->lang('There are no products configured'));
        }
    }

    protected function cart2Action()
    {
        // if we are on step 2, and have this set, we need to unset it, or it causes issues.
        if (isset($this->session->on_back_send_to_invoice_id)) {
            unset($this->session->on_back_send_to_invoice_id);
        }

        $this->view->setLfiProtection(false);
        $this->view->assign($this->initializeView());

        $currencyCode = base64_decode($this->session->currency);

        $this->title = $this->user->lang('Step 2');
        $aogateway = new ActiveOrderGateway($this->user);

        $package = new Package($this->view->product);
        $package->getProductPricingAllCurrencies();
        $isCompare = ($package->productGroup->fields['style'] == 'compare' ? true : false);
        $this->view->package = [];
        $this->view->package['package'] = $package;

        $this->view->package['pricing'] = $aogateway->prettyPricing($package->pricingInformationCurrency[$currencyCode], $this->view->paymentterm, false, $package->id);
        $hasMultipleCycles = true;

        if (count($this->view->package['pricing']) == 0) {
            CE_Lib::redirectPage("order.php");
        }

        if (count($this->view->package['pricing']) == 1) {
            $hasMultipleCycles = false;
        }

        $stockInfo = unserialize($package->stockInfo);
        if (is_array($stockInfo)) {
            if ($stockInfo['stockEnabled'] == 1 && $stockInfo['availableStock'] <= 0 && $stock['acceptSoldOut'] == 0) {
                CE_Lib::redirectPage('order.php', $this->user->lang('That product is out of stock.'));
            }
        }

        $customFields = $aogateway->getCustomFields('package', true, "", $package, $this->view->productgroup);
        $this->view->customFields = array_map(function ($field) {
            $name = str_replace(' ', '', strtolower($field['name']));
            if (isset($_GET['cf_' . $name])) {
                $field['value'] = $this->view->escape($_GET['cf_' . $name]);
            }
            return $field;
        }, $customFields['customFields']);

        //let's see if we have custom fields
        if (is_array($this->view->customFields)) {
            $customFieldCount = count($this->view->customFields);
        } else {
            $customFieldCount = 0;
        }

        $this->view->packageAddons = array_map(function ($value) {
            if (is_array($value['prices']) || count($value['prices']) > 0) {
                // the addon name is passed through user->lang() in the template,
                // so gotta guard against percentage signs
                $value['name'] = str_replace('%', '%%', $value['name']);
                $value['namelanguage'] = str_replace('%', '%%', $value['namelanguage']);

                if (isset($_GET['addonSelect_' . $value['id']])) {
                    $found = false;
                    foreach ($value['prices'] as &$price) {
                        if ($_GET['addonSelect_' . $value['id']] == 'addon_' . $value['id'] . '_' . $price['price_id'] . '_' . $price['recurringprice_cyle']) {
                            $price['price_selected'] = 'selected = "true"';
                            $found = true;
                            if ($value['addon_type'] == '2') {
                                $value['price_selected'] = $_GET['addonQuantity_' . $value['id']];
                            }
                        }
                    }
                    if ($found) {
                        foreach ($value['prices'] as &$price) {
                            if ($_GET['addonSelect_' . $value['id']] != 'addon_' . $value['id'] . '_' . $price['price_id'] . '_' . $price['recurringprice_cyle']) {
                                $price['price_selected'] = '';
                            }
                        }
                    }
                }
                return $value;
            }
        }, $aogateway->getAddons($this->view->product, $this->view->paymentterm));

        if (is_array($this->view->packageAddons)) {
            $addonCount = count($this->view->packageAddons);
        } else {
            $addonCount = 0;
        }


        //Something to check here is if I'm working on a bundled product (count > 0)
        //If we are working on a bundled product don't check to see if hasbundled products
        //We only allow bundled off of the main product and not child products
        //So if the session is set for bundled products and this product isn't it do not check if bundled.
        if (!isset($this->session->bundledProducts) || !isset($this->session->bundledProducts['bundlesleft']) || !is_array($this->session->bundledProducts['bundlesleft'])) {
            $this->session->bundledProducts = [];
            $this->session->bundledProducts['bundlesleft'] = $package->getBundledProducts();
            if (!$this->session->bundledProducts['bundlesleft']) {
                $this->session->bundledProducts['bundlesleft'] = [];
                $hasbundledproducts = false;
            } else {
                $hasbundledproducts = true;
            }
        } elseif (isset($this->session->bundledProducts['bundlesleft']) && is_array($this->session->bundledProducts['bundlesleft']) && count($this->session->bundledProducts['bundlesleft']) > 0) {
            $hasbundledproducts = true;
        } else {
            $hasbundledproducts = false;
        }

        // Handle the form post
        if (!$addonCount && !$customFieldCount && !$hasbundledproducts && !($isCompare && $hasMultipleCycles)) {
            $cartItemId = $aogateway->processFormPost($_REQUEST, 'selectandprocess');
            //we don't have addons, custom fields or bundled products so let's move forward to checkout
            CE_Lib::redirectPage("order.php?step=3");
        } elseif ($hasbundledproducts && (!$addonCount && !$customFieldCount)) {
            if ($isCompare && !$hasMultipleCycles) {
                $cartItemId = $aogateway->processFormPost($_REQUEST, 'selectandprocess');
                $this->session->cartParentPackage = $cartItemId;

                $forward_to_bundled_id = array_shift($this->session->bundledProducts['bundlesleft']);
                $bundlePackage = new Package($forward_to_bundled_id);
                CE_Lib::redirectPage("order.php?step=1&productGroup=" . $bundlePackage->id . "&bundled=1");
            } else {
                $cartItemId = $aogateway->processFormPost($_REQUEST, 'select');
            }
        } elseif ($addonCount || $customFieldCount || ($isCompare && $hasMultipleCycles)) {
            $cartItemId = $aogateway->processFormPost($_REQUEST, 'select');
            // show custom fields, addons or billing cycle if we're on compare.
        } else {
            CE_Lib::debug("Error in signup.  Not sure how to handle product selection");
            // Went wrong somewhere.
            CE_Lib::redirectPage("order.php?step=3");
        }

        $this->view->cartItemId = $cartItemId;

        // Do we have a manually specified domain
        if ((isset($_GET['domainName']) && isset($_GET['tld'])) || (isset($this->session->domainName) && isset($this->session->tld))) {
            $foundDomainField = false;

            foreach ($this->view->customFields as $id => $customField) {
                if (@$customField['isDomain'] == 1) {
                    $foundDomainField = true;
                    $this->view->customFields[$id]['savedValue'] = (isset($_GET['domainName'])) ? $_GET['domainName'] : $this->session->domainName;
                    $this->view->customFields[$id]['value'] = (isset($_GET['domainName'])) ? $_GET['domainName'] : $this->session->domainName;

                    // PHP kept erroring unless we did this if the long way...
                    if (isset($_GET['tld'])) {
                        $this->view->customFields[$id]['savedValue'] .= '.' . $_GET['tld'];
                        $this->view->customFields[$id]['value'] .= '.' . $_GET['tld'];
                    } else {
                        $this->view->customFields[$id]['savedValue'] .= '.' . $this->session->tld;
                        $this->view->customFields[$id]['value'] .= '.' . $this->session->tld;
                    }

                    break;
                }
            }

            // only clear the session variable if we've found a domain field.
            // If we haven't found a domain field, it means we're bundling, so we need to save it for the next call to cart2
            if ($foundDomainField == true) {
                unset($this->session->domainName);
                unset($this->session->tld);
                unset($this->session->autoSearchType);
            }
        }

        //Adding temp information to view
        //Debug information
        $this->view->tempInformation['bundledProducts'] = $this->session->bundledProducts;

        if ($this->settings->get('Enforce Password Strength')) {
            $this->view->passwordFields = [];
            foreach ($this->view->customFields as $customField) {
                if ($customField['fieldtype'] == TYPEPASSWORD) {
                    $this->view->passwordFields[] = $customField['id'];
                }
            }
            $this->view->enforcePassword = true;
        }

        $this->view->couponCode = base64_decode($this->session->couponCode);
        if ($this->view->couponCode === null) {
            $this->view->couponCode = $this->view->package['package']->getProductPricingAllCurrencies()[$currencyCode]['automaticCoupon'];
        } else {
            // check that the session coupon is valid
            $return = Coupon::validate(
                $this->view->couponCode,
                $this->view->package['package']->planid,
                $this->view->package['package']->id,
                $this->view->package['pricing'][0]['termId'],
                $currencyCode
            );
            // invalid coupon, so use automatic
            if (!is_array($return)) {
                $this->view->couponCode = $this->view->package['package']->getProductPricingAllCurrencies()[$currencyCode]['automaticCoupon'];
            }
        }
    }

    protected function cart3Action()
    {
        $this->view->setLfiProtection(false);
        $this->title = $this->user->lang('Step 3');

        //if we have $this->session->on_back_send_to_invoice_id that means we came back
        //unset and go to invoice
        if (isset($this->session->on_back_send_to_invoice_id)) {
            $invoice_id = $this->session->on_back_send_to_invoice_id;
            unset($this->session->on_back_send_to_invoice_id);
            CE_Lib::addErrorMessage($this->user->lang("The following invoice was not paid.  Please submit a %ssupport ticket%s if you need assistance.", "<a href='index.php?fuse=support&controller=ticket&view=submitticket'>", '</a>'));
            CE_Lib::redirectPage('index.php?fuse=billing&controller=invoice&view=invoice&id=' . $invoice_id);
        }

        $aogateway = new ActiveOrderGateway($this->user);
        // Check to see if we have any products that should be bundled but aren't.
        // We need to check for invalid products before we call initialize_view so the cart summary shows properly.
        $aogateway->checkInvalidProducts();

        $this->view->assign($this->initializeView());
        $this->session->bundledProducts = [];

        $this->view->assign($this->view->summary);

        if ($this->view->summary['cartCount'] == 0) {
            CE_Lib::redirectPage('order.php?cleanCart=true');
            return;
        }
        // Do we have an error from processing?
        if (isset($_REQUEST['errorReason']) && trim($_REQUEST['errorReason']) != "") {
            CE_Lib::addErrorMessage(trim($_REQUEST['errorReason']));
        }

        if ($this->user->getEmail() != '' && !$this->user->isAdmin() && $this->user->isRegistered()) {
            $this->view->loggedIn = true;
            $countryCode = $this->user->getCountry();
            $stateCode = $this->user->getState();
            $vatNumber = $this->user->getVatNumber();
            $isTaxable = $this->user->isTaxable();
            $userID = $this->user->getId();
        } else {
            $this->view->loggedIn = false;
            $countryCode = null;
            $stateCode = null;
            $vatNumber = null;
            $isTaxable = true;
            $userID = false;
        }

        $breakPoints = [
            // typeORGANIZATION,
            // typePHONENUMBER,
            // typeEMAIL,
            // typeCOUNTRY,
            // typeVATNUMBER
        ];

        $preBreakPoints = [
           // TYPEPASSWORD
        ];

        require_once 'modules/admin/models/ActiveOrderGateway.php';
        $activeOrderGateway = new ActiveOrderGateway($this->user);

        $customFields = $activeOrderGateway->getCustomFields(
            'profile',
            true,
            $this->session->oldFields
        );

        $this->view->stateVarId = $customFields['state_var_id'];
        $this->view->countryVarId = $customFields['country_var_id'];
        $this->view->vatVarId = $customFields['vat_var_id'];
        $this->view->customFieldValues = [];
        $this->view->selectCustomFields = [];
        $arrCustomFields = [];

        $this->view->customFields = [];



        foreach ($customFields['customFields'] as $key => $field) {
            if ($userID !== false) {
                // we need to manipulate select boxes differently
                if (in_array($field['fieldtype'], array(typeCOUNTRY, typeSTATE, typeYESNO, typeLANGUAGE, typeDROPDOWN, TYPE_ALLOW_EMAIL))) {
                    $this->user->getCustomFieldsValue($field['id'], $value);
                } else {
                    $this->user->getCustomFieldsValue($field['id'], $value);
                    $value = htmlspecialchars_decode($value, ENT_QUOTES);
                }
                $field['value'] = $value;
            }

            if (in_array($field['fieldtype'], $preBreakPoints)) {
                $fieldBreak = [
                    'fieldtype' => 'break'
                ];
                $this->view->customFields[] = $fieldBreak;
            }

            $this->view->customFields[] = $field;
            if (in_array($field['fieldtype'], $breakPoints)) {
                $field = [
                    'fieldtype' => 'break'
                ];
                $this->view->customFields[] = $field;
            }
        }

        $this->view->state_var_id = $customFields['state_var_id'];
        $this->view->country_var_id = $customFields['country_var_id'];
        $this->view->vat_var_id = $customFields['vat_var_id'];



        //CE_Lib::debug($this->view->summary);
        //$this->view->tax = $aogateway->processCartTax($this->view->summary, $countryCode, $stateCode, $vatNumber, $isTaxable, $userID);

        // Start billing / payment processors
        $plugins = new NE_PluginCollection("gateways", $this->user);

        // Get a list of valid payment processors
        $pluginsArray = [];
        while ($tplugin = $plugins->getNext()) {
            $tvars = $tplugin->getVariables();
            $tvalue = $this->user->lang($tvars['Plugin Name']['value']);
            $pluginsArray[$tvalue] = $tplugin;
        }
        uksort($pluginsArray, "strnatcasecmp");

        // Start making the array
        $this->view->paymentmethods = [];

        // Loop the processors
        $new_values = $aogateway->yourinfo_gateway_info($pluginsArray);
        $this->view->assign($new_values);

        if ($this->user->hasPermission('billing_automatic_cc_charge')) {
            $this->view->automaticCCCharge = 1;
        } else {
            $this->view->automaticCCCharge = 0;
        }

        // Handle T&C's
        if (@$this->settings->get('Show Terms and Conditions') == 1) {
             // Site URL for T&Cs
            if (@$this->settings->get('Terms and Conditions URL')) {
                 $this->view->termsConditions = '-1';
                 $this->view->termsConditionsUrl = $this->settings->get('Terms and Conditions URL');
            } else {
                 $this->view->termsConditions = 1;

                 $termsAndConditions = $this->settings->get('Terms and Conditions');
                 $termsAndConditions = str_replace('&quot;', '"', $termsAndConditions);
                 $termsAndConditions = str_replace('&#039;', '\'', $termsAndConditions);
                 $this->view->termsConditionsText = $termsAndConditions;
            }
        }

        $this->view->Currency = base64_decode($this->session->currency);
        $this->view->CouponCode = base64_decode($this->session->couponCode);

        $pluginCollection = new NE_PluginCollection('gateways', $this->user);
        $pluginCollection->setTemplate($this->view);
        $params = [];
        $params['currency'] = $this->view->Currency;
        $params['invoiceBalanceDue'] = $this->view->invoiceBalanceDue;
        $params['from'] = 'signup';
        $params['panellabel'] =  $this->user->lang("Pay");
        $params['userZipcode'] = $this->user->getZipCode();
        $params['termsConditions'] = $this->view->termsConditions;
        $params['loggedIn'] = $this->view->loggedIn;
        $params['cartsummary'] = $aogateway->getCartSummary();

        include_once "modules/admin/models/PluginGateway.php";
        $plugingateway = new PluginGateway($this->user);
        $GatewayWithFormPlugins = $plugingateway->getGatewayWithVariablePlugins('Form');
        $gatewayForms = [];

        foreach ($GatewayWithFormPlugins as $GatewayWithForm) {
            $gatewayForms[$GatewayWithForm] = $pluginCollection->callFunction($GatewayWithForm, 'getForm', $params);
        }

        $this->view->gatewayForms = $gatewayForms;

        // Handle Captcha
        $this->view->showCaptcha = false;
        $captchaPlugin = $this->settings->get('Enabled Captcha Plugin');

        if ($this->settings->get('Show Captcha on Signup Page') == 1 && $captchaPlugin != '' && $captchaPlugin != 'disabled') {
            $pluginGateway = new PluginGateway($this->user);
            $this->view->showCaptcha = true;

            $plugin = $pluginGateway->getPluginByName('captcha', $captchaPlugin);
            $plugin->setTemplate($this->view);
            $this->view->captchaHtml = $plugin->view();
        }

        // Set the password strength information
        if ($this->settings->get('Enforce Password Strength')) {
            $this->view->enforcePassword = true;
            $this->view->minPassLength = $this->settings->get('Minimum Password Length');
        }

        $this->view->cartCancel = true;
        $this->view->cartTotal = $this->view->summary['cartTotal']['price'];

        // Set the cancel order URL
        if ($this->settings->get('Cancel Order URL') != '') {
            $this->view->cancelOrderURL = $this->settings->get('Cancel Order URL');
        } else {
            $this->view->cancelOrderURL = 'order.php?cleanCart=true';
        }

        $this->view->enableFraudLabsPro = $this->settings->get('plugin_fraudlabspro_Enabled');

        $this->view->numPaymentMethods = count($this->view->paymentmethods);
    }

    protected function phoneverificationAction()
    {
        $this->view->assign($this->initializeView());

        $this->cssPages = [
            "templates/order_forms/{$this->view->order_form}/signuppublic/cart.css",
            "templates/default/css/customfields_public.css"
        ];

        $this->jsLibs = [
            "templates/order_forms/{$this->view->order_form}/signuppublic/cart.js"
        ];

        $aogateway = new ActiveOrderGateway($this->user);
        $cartSummary = $aogateway->getCartSummary();

        $phoneVerificationPlugins = new NE_PluginCollection('phoneverification', $this->user);
        while ($phoneVerificationPlugin = $phoneVerificationPlugins->getNext()) {
            // if plugin is not enabled, continue with next one
            if (!$this->settings->get("plugin_" . $phoneVerificationPlugin->getInternalName() . "_Enabled")) {
                continue;
            }

            if ($this->settings->get("plugin_" . $phoneVerificationPlugin->getInternalName() . "_Minimum Bill Amount to Trigger Telephone Verification") > @$cartSummary['cartTotal']['truePrice'] || (@$this->session->fraudScore && $this->settings->get("plugin_" . $phoneVerificationPlugin->getInternalName() . "_Minimum Fraud Score to Trigger Telephone Verification") > $this->session->fraudScore)) {
                break;
            }

            $phoneId = $this->user->getCustomFieldsObj()->_getCustomFieldIdByType(typePHONENUMBER);
            $langId = $this->user->getCustomFieldsObj()->_getCustomFieldIdByType(typeCOUNTRY);
            $customFields = $aogateway->getCustomFields('profile', true, $this->session->oldFields);
            foreach ($customFields['customFields'] as $customField) {
                if ($customField['id'] == $phoneId) {
                    $phoneNum = $customField['value'];
                }
                if ($customField['id'] == $langId) {
                    $lang = $customField['value'];
                }
            }

            $alreadyCalled = (isset($this->session->phoneNum) && $this->session->phoneNum == $phoneNum);

            // Do we have a session set already with the number?
            if (isset($this->session->phoneCode) && $alreadyCalled) {
                // Stops people re-loading the page by accident and having a new code generated
                $this->view->isCalled = true;
            } else {
                $this->session->phoneCode = rand(1000, 9999);

                $this->view->isCalled = false;

                $phoneVerificationPlugin->setPhoneNumber($phoneNum);
                $this->session->phoneNum = $phoneNUm;
                $phoneVerificationPlugin->setLanguage($lang);
                $phoneVerificationPlugin->execute($this->session->phoneCode);
                $this->view->phoneNumber = $phoneNum;
            }

            break;
        }
    }

    protected function phoneverificationcheckAction()
    {
        $aogateway = new ActiveOrderGateway($this->user);

        if ($_POST['code'] == $this->session->phoneCode) {
            unset($this->session->phoneverification_tries);
            unset($this->session->phoneCode);
            unset($this->session->phoneNum);
            $_REQUEST = array_merge($_REQUEST, $this->session->oldFields);
            $aogateway->create_new_account();
        } else {
            $this->session->phoneverification_tries = @$this->session->phoneverification_tries + 1;
            if ($this->session->phoneverification_tries >= 10) {
                CE_Lib::addErrorMessage($this->user->lang('Sorry, you have attempted to enter the code incorrectly too many times.'));
                  CE_Lib::redirectPage('order.php?cleanCart=true');
            } else {
                CE_Lib::addErrorMessage($this->user->lang('Sorry, the verification code you entered is invalid. Please try again.'));
                CE_Lib::redirectPage("order.php?step=phone-verification");
            }
        }
    }

    protected function updateparentpackageAction()
    {
        $productGroupId = $_POST['productGroup'];

        if (!isset($this->session->absoluteCartParentPackage) && isset($this->session->cartParentPackage)) {
            $this->session->absoluteCartParentPackage = $this->session->cartParentPackage;
        }

        if (!isset($this->session->absoluteCartParentDomainName) && isset($_POST['domainname'])) {
            $this->session->absoluteCartParentDomainName = trim($_POST['domainname']);
        }

        $aogateway = new ActiveOrderGateway($this->user);

        // update the bundled domain if we have a parent package
        if (@$this->session->absoluteCartParentPackage) {
            $aogateway->updateCartItem(
                array(
                    $this->session->absoluteCartParentPackage => array(
                        'bundledDomain' => (isset($this->session->absoluteCartParentDomainName)) ? $this->session->absoluteCartParentDomainName : null,
                        'isBundle'      => array(
                            $productGroupId => 'IGNORE VALIDATION'
                        )
                    )
                )
            );
        }

        //let's see if we still have other bundled products we need to do
        if (isset($this->session->bundledProducts) && isset($this->session->bundledProducts['bundlesleft']) && is_array($this->session->bundledProducts['bundlesleft']) && count($this->session->bundledProducts['bundlesleft']) > 0) {
            $forward_to_bundled_id = array_shift($this->session->bundledProducts['bundlesleft']);
            $bundlePackage = new Package($forward_to_bundled_id);
            $nextURL = "order.php?step=1&productGroup=" . $bundlePackage->id . "&bundled=1";
        } else {
            $nextURL = 'order.php?step=3';
        }

        $this->send(array("nexturl" => $nextURL));
    }

    protected function savedomainfieldsAction()
    {
        $products = $_REQUEST['products'];
        if (!is_array($products)) {
            throw new CE_Exception("There was an error with the submitted data");
        }

        $aogateway = new ActiveOrderGateway($this->user);
        foreach ($products as $product) {
            $aogateway->processFormPost($product, 'selectandprocess');
        }

        //let's see if we still have other bundled products we need to do
        if (isset($this->session->bundledProducts) && isset($this->session->bundledProducts['bundlesleft']) && is_array($this->session->bundledProducts['bundlesleft']) && count($this->session->bundledProducts['bundlesleft']) > 0) {
            $forward_to_bundled_id = array_shift($this->session->bundledProducts['bundlesleft']);
            $bundlePackage = new Package($forward_to_bundled_id);
            $nextURL = "order.php?step=1&productGroup=" . $bundlePackage->id . "&bundled=1";
        } else {
            $nextURL = 'order.php?step=3';
        }

        $this->send(array("nexturl" => $nextURL));
    }

    protected function saveproductfieldsAction()
    {
        $product_id = $this->getParam('product', FILTER_SANITIZE_NUMBER_INT);

        $aogateway = new ActiveOrderGateway($this->user);
        // store the parent package here, in case we need to use it to store the domain from the next step
        $this->session->cartParentPackage = $aogateway->processFormPost($_REQUEST, 'process');

        $package = new Package($product_id);
        if (!isset($this->session->bundledProducts) || !isset($this->session->bundledProducts['bundlesleft']) || !is_array($this->session->bundledProducts['bundlesleft'])) {
            $this->session->bundledProducts = [];
            $this->session->bundledProducts['bundlesleft'] = $package->getBundledProducts();
            if (!$this->session->bundledProducts['bundlesleft']) {
                $this->session->bundledProducts['bundlesleft'] = [];
                $hasbundledproducts = false;
            } else {
                $hasbundledproducts = true;
            }
        } elseif (isset($this->session->bundledProducts['bundlesleft']) && is_array($this->session->bundledProducts['bundlesleft']) && count($this->session->bundledProducts['bundlesleft']) > 0) {
            $hasbundledproducts = true;
        } else {
            $hasbundledproducts = false;
        }

        if ($hasbundledproducts) {
            $forward_to_bundled_id = array_shift($this->session->bundledProducts['bundlesleft']);
            $bundlePackage = new Package($forward_to_bundled_id);
            CE_Lib::redirectPage("order.php?step=1&productGroup=" . $bundlePackage->id . "&bundled=1");
        } else {
            // no bundles, so drop the parent package id
            $this->session->cartParentPackage = null;
            unset($this->session->cartParentPackage);
        }

        CE_Lib::redirectPage('order.php?step=3');
    }

    protected function validatecouponAction()
    {
        include_once 'modules/billing/models/Coupon.php';

        $currencyCode = base64_decode($this->session->currency);

        $couponCode = $this->getParam('couponCode', FILTER_SANITIZE_STRING, '', false);
        $productId = $this->getParam('productId', FILTER_SANITIZE_NUMBER_INT, '', false);
        $billingCycle = $this->getParam('billingCycle', FILTER_SANITIZE_NUMBER_INT, '', false);

        if ($couponCode != '' && $productId != '' && $billingCycle != '') {
            $package = new Package($productId);

            $return = Coupon::validate(
                $couponCode,
                $package->planid,
                $productId,
                $billingCycle,
                $currencyCode
            );

            if (is_array($return)) {
                $data['valid'] = true;
                $data['coupon'] = $return;
            } else {
                $data['valid'] = false;
                $this->error = true;
                $this->message = $this->user->lang("The coupon code '%s' is invalid.", $couponCode);
            }
            $this->send($data);
            return;
        } else {
            // Check the item
            if (@$_REQUEST['itemID']) {
                // As we can't call the view ITEMS we have to work the session manually
                if (CE_Lib::generateSignupCartHash() == @$this->session->cartHash && @$this->session->cartHash != null) {
                    // Get the cart items
                    $cartItems = unserialize(base64_decode(@$this->session->cartContents));

                    // Get the cart item
                    if (@is_array($cartItems[$_REQUEST['itemID']])) {
                        // Do we have a coupon?
                        if (@$_REQUEST['couponCode']) {
                            $couponCode = $_REQUEST['couponCode'];

                            // Get the product's information
                            $package = new Package($cartItems[$_REQUEST['itemID']]['productId']);
                            $package->getProductPricingAllCurrencies();

                            // Get the product group information
                            if (!@$productGroupInfo[$package->planid]) {
                                $productGroup = PackageTypeGateway::getPackageTypes($package->planid);
                                $productGroupInfo[$package->planid] = $productGroup->fetch();
                            }

                            $billingCycle = $cartItems[$_REQUEST['itemID']]['params']['term'];

                            // Check the coupon code
                            $return = Coupon::validate(
                                @$_REQUEST['couponCode'],
                                $productGroupInfo[$package->planid]->fields['id'],
                                $package->id,
                                $billingCycle,
                                $currencyCode
                            );

                            // Is coupon valid?
                            if (is_array($return)) {
                                // Push the coupon code to the product
                                $cartItems[$_REQUEST['itemID']]['couponCode'] = $return['id'];

                                // Add the coupon code the session
                                $cartItems['couponCodes'][$return['id']] = $return;

                                // Update session
                                $this->session->cartContents = base64_encode(serialize($cartItems));

                                // Save the new hash
                                $this->session->cartHash = CE_Lib::generateSignupCartHash();

                                // Hvae to send blank or the JS wont validate
                                $this->send();
                            } else {
                                throw new CE_Exception($this->user->lang("The coupon code '%s' is invalid.", $couponCode), EXCEPTION_CODE_NO_EMAIL);
                            }
                        } elseif (@!$_REQUEST['couponCode'] && @$cartItems[$_REQUEST['itemID']]['couponCode']) {
                            // Push the coupon code to the product
                            $cartItems[$_REQUEST['itemID']]['couponCode'] = null;

                            // Update session
                            $this->session->cartContents = base64_encode(serialize($cartItems));

                            // Save the new hash
                            $this->session->cartHash = CE_Lib::generateSignupCartHash();

                            // Hvae to send blank or the JS wont validate
                            $this->send();
                        } else {
                            throw new CE_Exception($this->user->lang("The coupon code '%s' is invalid.", $couponCode), EXCEPTION_CODE_NO_EMAIL);
                        }
                    } else {
                        throw new CE_Exception($this->user->lang("The coupon code '%s' is invalid.", $couponCode), EXCEPTION_CODE_NO_EMAIL);
                    }
                } else {
                    throw new Exception($this->user->lang("Unknown error."), EXCEPTION_CODE_NO_EMAIL);
                }
            } else {
                throw new CE_Exception($this->user->lang("The coupon code '%s' is invalid.", $couponCode), EXCEPTION_CODE_NO_EMAIL);
            }
        }
    }

    protected function getnumberofcartitemsAction()
    {
        $activeOrderGateway = new ActiveOrderGateway($this->user);
        $cartSummary = $activeOrderGateway->getCartSummary();

        $this->send(['items' => $cartSummary['cartCount']]);
    }

    /**
     * Action dispatch method
     *
     * @return void
     */
    protected function deletecartitemAction()
    {

        $aogateway = new ActiveOrderGateway($this->user);

        // Check the item
        if (@$_REQUEST['cartItem']) {
            // The problem here is that we are in an action but need to call functions
            // From a View file. So rather than initialing the class, just call the functions
            // This means we need to set the hash again manually as $this-> doesnt work inside
            // The removeFromCart function.

            // Remove the item
            $aogateway->removeFromCart($_REQUEST['cartItem']);

            // Save the new hash
            $this->session->cartHash = CE_Lib::generateSignupCartHash();
        } else {
            throw new Exception("No Cart Item Specified");
        }

        $this->send();
    }

    protected function getfinalpricinginfoAction()
    {

        $aogateway = new ActiveOrderGateway($this->user);
        $cartSummary = $aogateway->getCartSummary();

        if ($this->user->getEmail() != '' && !$this->user->isAdmin()) {
            $countryCode = $this->user->getCountry();
            $stateCode = $this->user->getState();
            $vatNumber = $this->user->getVatNumber();
            $isTaxable = $this->user->isTaxable();
            $userID = $this->user->getId();
        } else {
            $countryCode = $this->getParam('country', FILTER_SANITIZE_STRING, "");
            $stateCode = $this->getParam('state', FILTER_SANITIZE_STRING, "");
            $vatNumber = $this->getParam('vatNumber', FILTER_SANITIZE_STRING, "");
            $isTaxable = true;
            $userID = false;
        }

        // Sort out the tax
        $totals = $aogateway->processCartTax($cartSummary, $countryCode, $stateCode, $vatNumber, $isTaxable, $userID);
        $itemcount = $cartSummary['cartCount'];

        $this->send(array("totals" => $totals, "itemcount" => $itemcount));
    }

    protected function getstatelistAction()
    {
        include_once 'modules/admin/models/Countries.php';
        include_once 'modules/admin/models/States.php';

        $countryIso = $this->getParam('countryIso', FILTER_SANITIZE_STRING, '');
        $stateIso = $this->getParam('stateIso', FILTER_SANITIZE_STRING, '');
        $state_array = array();
        $states = new States($this->user);
        $codes = $states->getCodesArr($countryIso, true, $stateIso);

        foreach ($codes as $iso => $name) {
            $state_array[] = array(
                'iso'             => $iso,
                'name'            => $this->user->lang($name),
            );
        }

        $data = array(
            'totalcount' => count($state_array),
            'states'  => $state_array,
        );

        $this->send($data);
    }

    protected function searchdomainAction()
    {

        $search_type = $this->getParam('searchType', FILTER_SANITIZE_STRING);
        $domain_name = $this->getParam('name', FILTER_SANITIZE_STRING);
        $domain_tld = $this->getParam('tld', FILTER_SANITIZE_STRING);
        $product_id = $this->getParam('product', FILTER_SANITIZE_NUMBER_INT);
        $cartParentPackageId = $this->getParam('cartParentPackageId', FILTER_SANITIZE_NUMBER_INT);
        $cartParentPackageTerm = $this->getParam('cartParentPackageTerm', FILTER_SANITIZE_NUMBER_INT);
        $full_domain_name = $domain_name . "." . $domain_tld;

        $return_array = [];
        // Preset some global TPL vars
        $return_array['domainName'] = htmlentities($full_domain_name);
        $return_array['domainNameSuggest'] = false;
        $return_array['transferCheckList'] = $this->settings->get('Force Transfer Checklist');

        include "modules/admin/models/TopLevelDomainGateway.php";
        $tldgateway = new TopLevelDomainGateway($this->user);

        $return_array2 = $tldgateway->search_domain($domain_name, $domain_tld, $product_id, $search_type, $cartParentPackageId, $cartParentPackageTerm);
        $return_array = array_merge($return_array, $return_array2);
        $this->send(array("search_results" => $return_array));
    }

    /**
    * Process new order
    */
    protected function processAction()
    {

        $aogateway = new ActiveOrderGateway($this->user);
        $init_vars = $this->initializeView();

        //let's save old fields in the event we need to redirect
        $this->session->oldFields = $_REQUEST;
        $aogateway->process_new_order();
        $this->session->oldFields = null;

        $this->send();
    }

    protected function successAction()
    {
        $this->title = $this->user->lang('Completed');
        $aogateway = new ActiveOrderGateway($this->user);
        // This information can be required for affiliates URL
        $invoice_information = unserialize(base64_decode($this->session->invoice_information));
        if (is_array($invoice_information) && isset($invoice_information['invoice_id']) && isset($invoice_information['invoice_amount_no_tax']) && isset($invoice_information['invoice_amount'])) {
            $this->view->invoice_id            = $invoice_information['invoice_id'];
            $this->view->invoice_amount_no_tax = $invoice_information['invoice_amount_no_tax'];
            $this->view->invoice_amount        = $invoice_information['invoice_amount'];
        } else {
            $this->view->invoice_id            = 0;
            $this->view->invoice_amount_no_tax = 0;
            $this->view->invoice_amount        = 0;
        }
        $this->view->assign($this->initializeView());

        // Destroy cart session info
        $aogateway->destroyCart();
        $aogateway->destroyCurrency();
        $aogateway->destroyCouponCode();
        $aogateway->destroyInvoiceInformation();

        $this->view->shareTwitter = $this->settings->get('Show Twitter Button');
        $this->view->tweet = $this->settings->get('Default Tweet');
        $this->view->companyName = $this->settings->get('Company Name');
        $this->view->fbAppId = $this->settings->get('Facebook App ID');
        $this->view->shareFB = $this->settings->get('Show Facebook Button') && $this->view->fbAppId;
        $this->view->completeMessage = $this->settings->get('Order Complete Message');
    }

    protected function testpasswordstrengthAction()
    {
        $this->isType('POST');

        $returnArray = [];
        $returnArray['valid'] = true;
        $returnArray['errorMessage'] = "";

        $password = $this->getParam('password');

        include_once 'modules/admin/models/PasswordStrength.php';
        $passwordStrength = new PasswordStrength($this->settings, $this->user);

        $passwordStrength->setPassword($password);
        if (!$passwordStrength->validate()) {
            $errorMessage = '';
            foreach ($passwordStrength->getMessages() as $message) {
                $errorMessage .= $message . '<br/><br/>';
            }
            $returnArray['valid'] = false;
            $returnArray['errorMessage'] = $errorMessage;
        }

        $this->send($returnArray);
    }

    protected function customfieldsAction()
    {
        $this->disableLayout();

        $productId = $this->getParam('productId');
        $searchType = $this->getParam('searchType');

        $activeOrderGateway = new ActiveOrderGateway($this->user);
        $package = new Package($productId);

        $customFields = $activeOrderGateway->getCustomFields(
            'package',
            false,
            '',
            $package,
            $package->productGroup->fields['id']
        );

        $workingArray = [];
        if ($searchType == 'transfer') {
            $eppCodeArray = [];
            $eppCodeArray['id'] = 'eppCode';
            $eppCodeArray['name'] = 'EPP Code';
            $eppCodeArray['fieldtype'] = '0';
            $eppCodeArray['ischangeable'] = 1;
            $eppCodeArray['isrequired'] = 0;
            $eppCodeArray['description'] = $this->user->lang('Please enter the EPP code for this domain');
            $eppCodeArray['value'] = '';
            $workingArray[] = $eppCodeArray;

            if ($this->settings->get('Force Transfer Checklist')) {
                $tempArray = [];
                $tempArray['id'] = 'domain_unlocked';
                $tempArray['name'] = $this->user->lang("Is the domain unlocked?");
                $tempArray['fieldtype'] = '53';
                $tempArray['ischangeable'] = 1;
                $tempArray['isrequired'] = 1;
                $tempArray['description'] = $this->user->lang("Please ensure your domain is not locked.");
                $tempArray['value'] = '';
                $workingArray[] = $tempArray;

                $tempArray = [];
                $tempArray['id'] = 'private_registration';
                $tempArray['name'] = $this->user->lang("Is WHOIS privacy protection disabled?");
                $tempArray['fieldtype'] = '53';
                $tempArray['ischangeable'] = 1;
                $tempArray['isrequired'] = 1;
                $tempArray['description'] = $this->user->lang("Please ensure WHOIS privacy protection is not turned on for your domain.");
                $tempArray['value'] = '';
                $workingArray[] = $tempArray;

                $tempArray = [];
                $tempArray['id'] = 'email_address';
                $tempArray['name'] = $this->user->lang("Do you have access to the email address listed as the registrant?");
                $tempArray['fieldtype'] = '53';
                $tempArray['ischangeable'] = 1;
                $tempArray['isrequired'] = 1;
                $tempArray['description'] = $this->user->lang("Please ensure that you have access to the email address that is listed as the registrant.");
                $tempArray['value'] = '';
                $workingArray[] = $tempArray;
            }
        }

        $fields = array_merge($customFields['customFields'], $workingArray);
        $this->view->customFields = $fields;

        $path = [];
        $path[] = APPLICATION_PATH . '/../templates/order_forms/partials';
        $this->view->setScriptPath($path);

        echo $this->view->render("customfields.phtml");
        return;
    }
}
