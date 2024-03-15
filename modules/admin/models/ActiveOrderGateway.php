<?php
require_once 'modules/admin/models/ActiveOrderIterator.php';
require_once 'modules/admin/models/PackageAddonGateway.php';
require_once 'modules/admin/models/Package.php';
require_once 'modules/admin/models/Countries.php';
require_once 'modules/admin/models/States.php';
require_once 'modules/admin/models/ServerGateway.php';
require_once 'modules/clients/models/UserGateway.php';
require_once 'modules/billing/models/Coupon.php';
require_once 'modules/billing/models/Currency.php';
require_once 'modules/billing/models/BillingCycle.php';

use Carbon\Carbon;
use Clientexec\Utils\Cookie as Cookie;

/**
 * ActiveOrderGateway File
 *
 * @category Model
 * @package  Admin
 * @author   Alberto Vasquez <alberto@clientexec.com>
 * @license  ClientExec License
 * @version  [someversion]
 * @link     http://www.clientexec.com
 */

/**
 * ActiveOrderGateway Model Class
 *
 * @category Model
 * @package  Admin
 * @author   Alberto Vasquez <alberto@clientexec.com>
 * @license  ClientExec License
 * @version  [someversion]
 * @link     http://www.clientexec.com
 */
class ActiveOrderGateway extends NE_Model
{

    private $signupEmail = '';
    protected $validPricing = false;
    protected $currencys = false;
    protected $currency;
    protected $couponCode;

    /**
     * Returns all active orders for product id
     *
     * @return ActiveOrderIterator
     */
    function getAllOrdersForProductId()
    {
    }

    /**
    * clears any active order entries that should be cleared
    * so that may become available for future customers
    *
    * @return void
    */
    function clearAllOrdersForProductId()
    {
        $sql = "SELECT id, product_id FROM active_orders WHERE expires < NOW() ";
        $result = $this->db->query($sql);

        while ($row=$result->fetch()) {
            $this->removeCartItem($row['id'], $row['product_id']);
        }
    }

    /**
    * Removes entry in active_orders table for this cartitemid
    * If the entry doesn't exist it was already removed so we don't
    * want to update stock
    *
    * @return null
    */
    function removeCartItem($cartItemId, $productid = 0)
    {
        //lets get setting for stock timer
        $stockTimer = (int)$this->settings->get('Stock Timer');

        $activeOrder = new ActiveOrder($cartItemId);
        //$this->debug(print_r($activeOrder,true));

        //lets ensure we have an active order entry
        //if we don't then this cart item was removed already
        //and stock was already raised or never existed
        //because we are removing an item during stock timer
        //disabled setting.  If the object's product_id = 0 then
        //this item was not in the db, but if timer is 0 then
        //this item was not part of the db and stock was not readded
        //so we need to add it below using the product_id passed
        if (($activeOrder->product_id == 0) && ($stockTimer>0)) {
            return;
        }

        $package = new Package($productid);
        $activeOrder->delete();

        $stockInfo = unserialize($package->stockInfo);
        if (is_array($stockInfo)) {
            // Check if we are doing stockControl
            if ($stockInfo['stockEnabled'] == 1) {
                // Increase the stock control option
                $stockInfo['availableStock']++;
                // Serialize it and send it back
                $package->stockInfo = serialize($stockInfo);
                // Save the package
                $package->save();
            }
        }
    }

    /*
     * Function to destroy a temp item once finished with it
     */
    function destroyTempItem($removeFromCart = false)
    {
        if (isset($this->session->tempCartItemId) && $removeFromCart) {
            $this->removeFromCart($this->session->tempCartItemId);
        }

        // Destroy the cart contents
        $this->session->tempCartItemHash = null;
        $this->session->tempCartItem = null;
        $this->session->tempCartItemId = null;

        unset($this->session->tempCartItemHash);
        unset($this->session->tempCartItem);
        unset($this->session->tempCartItemId);
    }

    /*
     * Function to destroy the cart
     */
    function destroyCart()
    {
        //we should loop to see which cart items have stock enabled
        //so that we can readd the stock properly (so lets remove from cart properly)
        if (isset($this->session->cartContents)) {
            $cartItems = unserialize(base64_decode($this->session->cartContents));
            if (is_array($cartItems)) {
                foreach ($cartItems as $item) {
                    if (isset($item['cartItemId'])) {
                        $this->removeFromCart($item['cartItemId']);
                    }
                }
            }
        }

        // Destroy the cart contents
        $this->session->cartHash = null;
        unset($this->session->cartHash);
        $this->session->cartContents = null;
        unset($this->session->cartContents);
        $this->session->cartParentPackage = null;
        unset($this->session->cartParentPackage);
        $this->session->absoluteCartParentPackage = null;
        unset($this->session->absoluteCartParentPackage);
        $this->session->absoluteCartParentDomainName = null;
        unset($this->session->absoluteCartParentDomainName);
    }

    /*
     * Function to remove an item from the cart
     */
    function removeFromCart($cartItemId)
    {
        if (isset($this->session->cartContents)) {
            // get the current cart
            $cartContents = unserialize(base64_decode($this->session->cartContents));

            // Check if the cart item is valid
            if (isset($cartContents[$cartItemId]) && is_array($cartContents[$cartItemId])) {
                //Remove any bundled item
                if (isset($cartContents[$cartItemId]['isBundle']) && is_array($cartContents[$cartItemId]['isBundle']) && count($cartContents[$cartItemId]['isBundle']) > 0) {
                    foreach ($cartContents[$cartItemId]['isBundle'] as $bundledItemId) {
                        //'IGNORE VALIDATION' is used to ignore validation of Selfmanage Domains, and Sub Domains, as they do not have a real product
                        if ($bundledItemId !== 'IGNORE VALIDATION') {
                            $this->removeFromCart($bundledItemId);
                        }
                    }
                    //Need to get a fresh copy of the cart as the bundled items has been now removed
                    $cartContents = unserialize(base64_decode($this->session->cartContents));
                }

                //lets add the stock back
                if ($cartContents[$cartItemId]['stock']) {
                    $this->removeCartItem($cartItemId, $cartContents[$cartItemId]['productId']);
                }

                unset($cartContents[$cartItemId]);

                // Save it to the session
                $this->session->cartContents = base64_encode(serialize($cartContents));
            }
        }
    }

    /*
     * Function to push updates to cart items
     * $itemsArray is an array of items with cartItemId as key, and params as values
     */
    function updateCartItem($itemsArray)
    {
        if (isset($itemsArray) && is_array($itemsArray) && count($itemsArray) > 0) {
            // get the current cart
            if (isset($this->session->cartContents)) {
                $cartContents = unserialize(base64_decode($this->session->cartContents));
            } else {
                $cartContents = array();
            }

            foreach ($itemsArray as $cartItemId => $params) {
                // Check if we got everything
                if ($cartItemId == null) {
                    // Destroy the cart as something went wrong
                    $this->destroyCart();

                    // remove the parent package from session since we've updated the cart now
                    $this->session->cartParentPackage = null;
                    unset($this->session->cartParentPackage);

                    return;
                }

                // Check if the ID we have is valid
                if (isset($cartContents[$cartItemId]) && is_array($cartContents[$cartItemId])) {
                    foreach ($params as $key => $value) {
                        if ($key === 'isBundle' && is_array($value)) {
                            foreach ($value as $bKey => $bValue) {
                                $cartContents[$cartItemId][$key][$bKey] = $bValue;
                            }
                        } elseif ($key === 'params' && is_array($value)) {
                            foreach ($value as $bKey => $bValue) {
                                $cartContents[$cartItemId][$key][$bKey] = $bValue;
                            }
                        } else {
                            $cartContents[$cartItemId][$key] = $value;
                        }
                    }
                } else {
                    // Destroy the cart as something went wrong
                    $this->destroyCart();

                    // remove the parent package from session since we've updated the cart now
                    $this->session->cartParentPackage = null;
                    unset($this->session->cartParentPackage);

                    return;
                }
            }

            // Save it to the session
            $this->session->cartContents = base64_encode(serialize($cartContents));

            // Save the new hash
            $this->session->cartHash = CE_Lib::generateSignupCartHash();

            // remove the parent package from session since we've updated the cart now
            $this->session->cartParentPackage = null;
            unset($this->session->cartParentPackage);
        }
    }

    function getPackageForSelectedGroup($data, $isDomain = false)
    {
        include_once 'modules/admin/models/Translations.php';
        $languages = CE_Lib::getEnabledLanguages();
        $translations = new Translations();
        $languageKey = ucfirst(strtolower($this->user->getLanguage()));

        $paymentterm = $data['paymentterm'];

        include_once 'modules/admin/models/PackageTypeGateway.php';
        $packageTypeGateway = new PackageTypeGateway();
        $packageType = $packageTypeGateway->getPackageTypesWithIds(array($data['productGroup']));
        $productGroup = $packageType->fetch();

        // Get the packages for this group
        $return_packages = array();

        $currencyCode = base64_decode($this->session->currency);

        $dropdownCount = 0;
        $packageCount = 0;
        if (isset($data['productsToGet']) && is_array($data['productsToGet'])) {
            $query = "SELECT id FROM package WHERE planid = ? AND id IN (".implode(', ', $data['productsToGet']).") ORDER BY signup_order ASC ";
            $result = $this->db->query($query, $data['productGroup']);
        } else {
            $query = "SELECT id FROM package WHERE planid = ? ORDER BY signup_order ASC ";
            $result = $this->db->query($query, $data['productGroup']);
        }

        $count = 0;
        while ($row = $result->fetch()) {
            // Get the info for this package
            $package = new Package($row['id']);

            // Should we include this product
            if ($package->showpackage != 1) {
                // Do we make an execption for direct link
                if ($package->allowdirectlink == 1 && isset($_GET['product']) && $package->id == $_GET['product']) {
                    // Move on
                } else {
                    continue;
                }
            }

            // start adding some pieces
            $aPackage = array();
            $aPackage['id'] = $package->id;
            $aPackage['showDropdown'] = ($package->showpackage == 1 || ($package->allowdirectlink == 1 && $selectedProduct == $package->id))?true:false;
            $aPackage['selected'] = false;

            if ($aPackage['showDropdown']) {
                ++$dropdownCount;
            }

            // Work out if we have selected this product
            if (($data['selectedProduct'] == $package->id || ($data['selectedProduct'] == 0 && $count==0))
              &&($package->showpackage == 1 || $package->allowdirectlink == 1)) {
                $aPackage['selected'] = true;
            }

            ++$packageCount;

            $isUpgradePackage = false;

            if (isset($data['isUpgradePackage']) && $data['isUpgradePackage'] === true) {
                $isUpgradePackage = true;
            }

            // Set the pricing

            if ($isDomain === false) {
                $package->getProductPricingAllCurrencies();
                $aPackage['pricing'] = $this->prettyPricing(
                    $package->pricingInformationCurrency[$currencyCode], $paymentterm, $isUpgradePackage);
                $aPackage['taxable'] = ($package->isCurrencyTaxable($currencyCode))? 1 : 0;
                $aPackage['validPricing'] = $this->validPricing;
            }

            //If there is no pricing for the product, ignore it
            if ($isUpgradePackage && count($aPackage['pricing']) == 0) {
                continue;
            }



            // Add the description
            $aPackage['description'] = $package->description;
            $aPackage['descriptionlanguage'] = $translations->getValue(PRODUCT_DESCRIPTION, $package->id, $languageKey, $package->description);
            $aPackage['assetHTML'] = $package->asset_html;
            $aPackage['assetHTMLlanguage'] = $translations->getValue(PRODUCT_ASSET, $package->id, $languageKey, $package->asset_html);
            $aPackage['highlight'] = $package->highlight;
            $aPackage['planname'] = $package->planname;
            $aPackage['plannamelanguage'] = $translations->getValue(PRODUCT_NAME, $package->id, $languageKey, $package->planname);
            $aPackage['groupname'] = $productGroup->getName();
            $aPackage['groupnamelanguage'] = $translations->getValue(PRODUCT_GROUP_NAME, $package->planid, $languageKey, $productGroup->getName());
            $aPackage['timeExpireText'] = "";
            $aPackage['advanced'] = unserialize($package->advanced);

            // Get the stockInfo
            $stockInfo = unserialize($package->stockInfo);
            if (is_array($stockInfo)) {
                // Check if we are doing stockControl
                if ($stockInfo['stockEnabled'] == 1) {
                    // Work out if stock control should be enabled or not
                    if ($stockInfo['availableStock'] == 0 && $stockInfo['acceptSoldOut'] == 1) {
                        // Turn stock control off as we are still accepting orders
                        $aPackage['stockControl'] = 0;
                    } elseif ($stockInfo['availableStock'] > 0) {
                        // Turn stock control off as we are still accepting orders
                        $aPackage['stockControl'] = 0;
                    } else {
                        // Not sure what else todo so turn stock control on and stop orders
                        $aPackage['stockControl'] = 1;
                    }

                    // Are we displaying the stock level
                    if ($stockInfo['showStockLevel'] == 1) {
                        // Set the current stock level
                        $aPackage['stockLevel'] = $stockInfo['availableStock'];
                    } else {
                        // Set disabled
                        $aPackage['stockLevel'] = -1;
                    }

                    $stockTimer = (int)$this->settings->get('Stock Timer');
                    if ($stockTimer > 0) {
                        $aPackage['timeExpireText'] = "-&nbsp;".$this->user->lang("You have")." ".$stockTimer." ".$this->user->lang("minutes")." ".$this->user->lang("to complete each step");
                    }
                } else {
                    // Disabled
                    $aPackage['stockControl'] = 0;
                    $aPackage['stockLevel'] = -1;
                }
            } else {
                // Disabled
                $aPackage['stockControl'] = 0;
                $aPackage['stockLevel'] = -1;
            }
            if (isset($_REQUEST['bundled'])) {
                $aPackage['nextUrl'] = "order.php?step=2&bundled=1&product=".$package->id;
            } else {
                $aPackage['nextUrl'] = "order.php?step=2&product=".$package->id;
            }
            if ($aPackage['stockControl'] == 1) {
                $aPackage['nextUrl'] = "order.php";
            }

            // set the selected term index of the pricing, so we can show in adapative pricing in cart_style
            $aPackage['selectedTermIndex'] = 0;
            foreach ($aPackage['pricing'] as $index => $pricing) {
                if ($pricing['selected'] == 1) {
                    $aPackage['selectedTermIndex'] = $index;
                    if (!isset($_REQUEST['bundled'])) {
                        $aPackage['nextUrl'] = "order.php?step=2&product=".$package->id."&paymentTerm=" .$pricing['termId'];
                    }
                }
            }

            // Update the selected product so we don't overwrite the pricing
            $selectedProduct = $package->id;

            if (isset($data['useIdAsKey']) && $data['useIdAsKey'] === true) {
                $return_packages[$package->id] = $aPackage;
            } else {
                $return_packages[] = $aPackage;
            }

            $count++;
        }

        return $return_packages;
    }

    function initialize_currency()
    {
        $resetOrder = false;

        // Do we have an existing logged in user?
        if ($this->user->getEmail() != '' && !$this->user->isAdmin() && $this->user->isRegistered()) {
            //Use his currency
            $_REQUEST['currency'] = $this->user->getCurrency();
        }

        //Validate if the currency is enabled
        if (isset($_REQUEST['currency'])) {
            $_REQUEST['currency'] = strtoupper($_REQUEST['currency']);
            $query = "SELECT * FROM currency WHERE abrv = ? AND enabled = 1";
            $result = $this->db->query($query, $_REQUEST['currency']);

            //If the currency is not enabled, unset it to later use the currency that was on the session
            if ($result->getNumRows() == 0) {
                unset($_REQUEST['currency']);
            } else {
                //If the currency is changed, reset (clear) everything, as the options available for the new currency will be something completely different
                if (isset($this->session->currency) && $_REQUEST['currency'] != base64_decode($this->session->currency)) {
                    $this->destroyCart();
                    $this->destroyCurrency();
                    $this->destroyCouponCode();
                    $this->destroyInvoiceInformation();
                    $resetOrder = true;
                }

                $this->session->currency = base64_encode($_REQUEST['currency']);
            }
        }

        //If the currency is not enabled, use the currency that was on the session
        if (!isset($_REQUEST['currency']) && isset($this->session->currency)) {
            $_REQUEST['currency'] = base64_decode($this->session->currency);
            //Validate if the currency is enabled
            $query = "SELECT * FROM currency WHERE abrv = ? AND enabled = 1";
            $result = $this->db->query($query, $_REQUEST['currency']);

            //If the currency in session is not enabled, unset it to later use the default currency
            if ($result->getNumRows() == 0) {
                unset($_REQUEST['currency']);
            }
        }

        //If the currency is not enabled use the default currency
        if (!isset($_REQUEST['currency'])) {
            //If the currency is not enabled, reset (clear) everything, as the options available for the default currency will be something completely different
            $this->destroyCart();
            $this->destroyCurrency();
            $this->destroyCouponCode();
            $this->destroyInvoiceInformation();
            // Only redirect to order if this is not the fist time the user is entering the page
            if (isset($this->session->currency)) {
                $resetOrder = true;
            }

            $_REQUEST['currency'] = $this->settings->get('Default Currency');

            $this->session->currency = base64_encode(strtoupper($_REQUEST['currency']));
        }

        $this->currency = htmlspecialchars($_REQUEST['currency']);

        if ($resetOrder) {
            CE_Lib::redirectPage('order.php');
        }
    }

    function initialize_couponCode()
    {
        if (isset($_REQUEST['couponCode'])) {
            $this->session->couponCode = base64_encode($_REQUEST['couponCode']);
        } elseif (isset($this->session->couponCode)) {
            $_REQUEST['couponCode'] = base64_decode($this->session->couponCode);
        }

        if (!isset($_REQUEST['couponCode'])) {
            $_REQUEST['couponCode'] = '';
            $this->session->couponCode = base64_encode($_REQUEST['couponCode']);
        }

        $this->couponCode = htmlspecialchars($_REQUEST['couponCode']);
    }

    /*
     * Turn the returned pricing array into a pretty ony
     */
    function prettyPricing($pricingArray, $paymentterm, $isUpgradePackage = false, $productId = false)
    {
        //Billing Cycles
        $billingcycles = array();

        include_once 'modules/billing/models/BillingCycleGateway.php';
        $gateway = new BillingCycleGateway();
        $iterator = $gateway->getBillingCycles(array(), array('order_value', 'ASC'));

        while ($cycle = $iterator->fetch()) {
            $billingcycles[$cycle->id] = array(
            'name'            => $this->user->lang($cycle->name),
            'time_unit'       => $cycle->time_unit,
            'amount_of_units' => $cycle->amount_of_units
            );
        }
        //Billing Cycles

        $time_units_to_days_array = array(
            'd' => 1,
            'w' => 7,
            'm' => 30, // Assuming each month is 30 days for pricing
            'y' => 360 // Assuming each year is 12 months and each month is 30 days for pricing. That is, 12 * 30 = 360 days. This will give a better approximation than using 365 days
        );

        $this->currencys = new Currency($this->user);
        $this->initialize_currency();

        if ($productId !== false) {
            $package = new Package($productId);

            include_once 'modules/admin/models/PackageGateway.php';
            $packagegateway = new PackageGateway($this->user);
            $packageData = $packagegateway->getPackageData($package->id);
        }

        $finalPricing = array();
        $firstPrice = 0;
        $firstSelected = false;
        $saved = '-';

        foreach ($billingcycles as $billingCycleId => $billingCycleData) {
            if ($billingCycleId == 0) {
                continue;
            }

            if (isset($pricingArray['price'.$billingCycleId.'included']) && $pricingArray['price'.$billingCycleId.'included']) {
                // set the valid pricing term
                $this->validPricing = true;

                $priceValue = (is_numeric($pricingArray['price'.$billingCycleId]))? (float) $pricingArray['price'.$billingCycleId] : 0;

                if ($isUpgradePackage && !$this->settings->get("Charge Setup Prices")) {
                    $setupValue = 0;
                } else {
                    $setupValue = (is_numeric($pricingArray['price'.$billingCycleId.'_setup']))? (float) $pricingArray['price'.$billingCycleId.'_setup'] : 0;
                }

                $currencyPrecision = $this->currencys->getPrecision($this->currency);
                $subtotalAmount = sprintf("%01.".$currencyPrecision."f", round($priceValue + $setupValue, $currencyPrecision));

                // Get the saved percentage
                if ($firstPrice != 0) {
                    if ($billingCycleData['amount_of_units'] > 0) {
                        $saved = $firstPrice - $priceValue / ($billingCycleData['amount_of_units'] * $time_units_to_days_array[$billingCycleData['time_unit']]);
                        $saved = $saved / $firstPrice * 100;

                        if ($saved >= 0.01) {
                            $saved = sprintf("%01.2f", round($saved, 2)).'%';
                            $this->hasSavings = true;
                        } else {
                            $saved = "-";
                        }
                    } else {
                         $saved = "-";
                    }
                } else {
                    if ($billingCycleData['amount_of_units'] > 0) {
                        $firstPrice = $priceValue / ($billingCycleData['amount_of_units'] * $time_units_to_days_array[$billingCycleData['time_unit']]);
                    }
                }

                // Get the selected item
                if ($paymentterm == "$billingCycleId" || $firstSelected == false) {
                    $selected = true;
                    $firstSelected = true;
                } else {
                    $selected = false;
                }

                $signupPrice = false;
                $signupPriceMonthly = false;

                if ($productId !== false) {
                    $signupPrice = $packageData['priceExtraDataCurrencies'][$this->currency][$billingCycleId]['signup_price'];

                    //Convert prices to months
                    $signupPriceMonthly = ($signupPrice * 30) / ($billingCycleData['amount_of_units'] * $time_units_to_days_array[$billingCycleData['time_unit']]);

                    //Format prices
                    $signupPrice = $this->currencys->format($this->currency, $signupPrice, true, "NONE", true);
                    $signupPriceMonthly = $this->currencys->format($this->currency, $signupPriceMonthly, true, "NONE", true);
                }

                //Convert prices to months
                $priceValueMonthly = ($priceValue * 30) / ($billingCycleData['amount_of_units'] * $time_units_to_days_array[$billingCycleData['time_unit']]);

                // Add the price
                $finalPricing[] = array(
                    'term'                 => $billingCycleData['name'],
                    'price'                => $this->currencys->format($this->currency, $priceValue, true, "NONE", true),
                    'price_raw'            => $this->currencys->format_raw($this->currency, $priceValue),
                    'setup'                => $this->currencys->format($this->currency, $setupValue, true, "NONE", true),
                    'setup_raw'            => $this->currencys->format_raw($this->currency, $setupValue),
                    'subtotal_amount'      => $subtotalAmount,
                    'termId'               => "$billingCycleId",
                    'save'                 => $saved,
                    'selected'             => $selected,
                    'signup_price'         => $signupPrice,
                    'signup_price_monthly' => $signupPriceMonthly,
                    'price_monthly'        => $this->currencys->format($this->currency, $priceValueMonthly, true, "NONE", true)
                );
            }
        }

        // One Time
        if (isset($pricingArray['price0included']) && $pricingArray['price0included']) {
            // set the valid pricing term
            $this->validPricing = true;

            $priceValue = (is_numeric($pricingArray['price0']))? (float) $pricingArray['price0'] : 0;
            $currencyPrecision = $this->currencys->getPrecision($this->currency);
            $subtotalAmount = sprintf("%01.".$currencyPrecision."f", round($priceValue, $currencyPrecision));


            // Get the selected item
            if ($paymentterm == '0' || $firstSelected == false) {
                $selected = true;
                $firstSelected = true;
            } else {
                $selected = false;
            }

            $signupPrice = false;

            if ($productId !== false) {
                $signupPrice = $packageData['priceExtraDataCurrencies'][$this->currency][0]['signup_price'];

                //Format prices
                $signupPrice = $this->currencys->format($this->currency, $signupPrice, true, "NONE", true);
            }

            // Add the price
            $finalPricing[] = [
                'term'            => $billingcycles[0]['name'],
                'price'           => $this->currencys->format($this->currency, $priceValue, true, "NONE", true),
                'price_raw'       => $this->currencys->format_raw($this->currency, $priceValue),
                'setup'           => $this->currencys->format($this->currency, 0, true, "NONE", true),
                'setup_raw'       => $this->currencys->format_raw($this->currency, 0),
                'subtotal_amount' => $subtotalAmount,
                'termId'          => '0',
                'save'            => '-',
                'selected'        => $selected,
                'signup_price'    => $signupPrice
            ];
        }

        return $finalPricing;
    }



    function getCustomFields($type, $isSignup = false, $oldvalues = "", $package = false, $productGroup = null, $langField = false)
    {
        $returnArray = array();
        $state_var_id = "";
        $vat_var_id = "";
        $country_var_id = "";
        $hasDomainProduct = 0;
        $countrySavedValue = $this->settings->get("Default Country");

        if ($oldvalues == "") {
            $oldvalues == null;
        }

        if (isset($package) && isset($package->fields['advanced'])) {
            $advancedSettings = unserialize($package->advanced);
        } else {
            $advancedSettings = array();
        }

        if ($type == 'package') {
            $injectPassword = false;
            $query = "SELECT `id`, `name`, `fieldType`, `isRequired`, `dropDownOptions`, `desc`, 20, `groupId`, `subGroupId`, 1, `regex` "
            ."FROM `customField` "
            ."WHERE `groupId` IN (?, ?) "
            ."AND `InSignup` = 1 "
            ."AND `inSettings` = 1 "
            ."ORDER BY `groupId` DESC, `fieldOrder` ASC ";
            $result = $this->db->query($query, CUSTOM_FIELDS_FOR_PACKAGE, CUSTOM_FIELDS_FOR_PRODUCTTYPE);
            // If we don't have a domain product bundled to a hosting product, we need to inject a Domain name custom field to the start of the array
            if ($package->productGroup->fields['type'] == 1) {
                // Check if we have a domain product bundled
                $bundledProducts = $package->getBundledProducts();

                // Check the array & Loop the products
                if (is_array($bundledProducts)) {
                    foreach ($bundledProducts as $key) {
                        // Get the product
                        $bundledProduct = new PackageType($key);
                        // Check if the bundle is a domain
                        if ($bundledProduct->type == 3) {
                            $hasDomainProduct = 1;
                        }
                    }
                }
            }
        } elseif ($type == 'profile') {
            $countryFieldId = $this->user->getCustomFieldsObj()->_getCustomFieldIdByType(typeCOUNTRY);

            if (is_array($oldvalues) && isset($oldvalues['CT_' . $countryFieldId]) && $oldvalues['CT_' . $countryFieldId] != '') {
                $countrySavedValue = htmlspecialchars($oldvalues['CT_' . $countryFieldId], ENT_QUOTES);
            } else {
                $this->user->getCustomFieldsValue($countryFieldId, $value);
                $countrySavedValue = $value;

                if ($countrySavedValue == '') {
                    $countrySavedValue = $this->settings->get("Default Country");
                }
            }

            $injectPassword = true;
            $query = "SELECT `id`, `name`, `type`, `isrequired`, `dropdownoptions`, `desc`, `width`, 0, 0, `isAdminOnly`, `regex` FROM `customuserfields` ";
            if ($isSignup) {
                $query .= "WHERE `insignup` = 1 ";
            } else {
                //It looks like `isAdminOnly` can be:
                //0 = The customer and admin can see the field in the customer profile if set to "Include in Customer"
                //1 = Only the admin can see the field in the customer profile if set to "Include in Customer"
                //2 = The customer and admin can see the field in the customer profile if set to "Include in Customer", but the customer see the field disabled.
                $query .= "WHERE `showcustomer` = 1 AND `isAdminOnly` IN (0, 2) ";
            }
            if ($langField) {
                $query .= "OR `type`= " . typeLANGUAGE . ' ';
            }
            $query .= "ORDER BY `myOrder` ";
            $result = $this->db->query($query);
        }

        while (list($tID, $tName, $tType, $tRequired, $dropdownoptions, $desc, $width, $groupId, $subGroupId, $ischangeable, $regex) = $result->fetch(MYSQLI_NUM)) {
            if ($type == 'package') {
                // Check to ensure if we are to show this custom field for the product group.
                if ($groupId == CUSTOM_FIELDS_FOR_PACKAGE && $subGroupId != 0) {
                    $innerQuery = "SELECT * FROM promotion_customdomainfields WHERE promotionid=? AND customid=?";
                    $innerResult = $this->db->query($innerQuery, $productGroup, $tID);
                    if ($innerResult->getNumRows() == 0) {
                        continue;
                    }
                }

                // Handle system added fields
                if ($groupId == 2) {
                    if ($package->productGroup->fields['type'] != $subGroupId) {
                        continue;
                    }
                }
            }

            // Check if we are adding a domain name with a bundled package
            if (($hasDomainProduct == 1 || (isset($advancedSettings['hostingcustomfields']) && $advancedSettings['hostingcustomfields'] == 1)) && (isset($tName) && $tName == 'Domain Name') && ($groupId!=CUSTOM_FIELDS_FOR_PACKAGE)) {
                continue;
            }

            // Check if we are asking for domain username & password
            if (($this->settings->get("Prompt for domain username and password") == 0 || (isset($advancedSettings['hostingcustomfields']) && $advancedSettings['hostingcustomfields'] == 1))
              && ((isset($tName) && ($tName == 'User Name' || $tName == 'Password')) && ($groupId!=CUSTOM_FIELDS_FOR_PACKAGE))) {
                continue;
            }

            // SSL Product, we need to check if usingInviteURL and disable fields
            if ($package->productGroup->fields['type'] == 2) {
                if (isset($advancedSettings['registrar'])) {
                    include_once "modules/admin/models/PluginGateway.php";
                    $pluginGateway = new PluginGateway($this->user);
                    $sslPlugin = $pluginGateway->getPluginByName('ssl', $advancedSettings['registrar']);

                    if (($sslPlugin->usingInviteURL == true) && ($tName == 'Certificate CSR' || $tName == 'Certificate Admin Email') && ($groupId!=CUSTOM_FIELDS_FOR_PACKAGE)) {
                        continue;
                    }
                }
            }

            $savedValue = '';
            $tmpArray = array();

            $tmpArray['id'] = $tID;
            $tmpArray['name'] = $this->user->lang($tName);
            $tmpArray['fieldtype'] = $tType;
            $tmpArray['ischangeable'] = (!$isSignup && $ischangeable == 2)? false : true; //Signup fields should be always changeable in signup
            $tmpArray['isrequired'] = $tRequired;
            $tmpArray['description'] = str_replace('"', "'", $this->user->lang($desc));
            $tmpArray['regex'] = $regex;

            // If we have a description of these in the database, it breaks sign-up, since you can't edit the value in the UI, these should be "" anyways.
            $noDesc = array(typeFIRSTNAME, typeLASTNAME, typeADDRESS, typeCITY, typeSTATE, typeZIPCODE, typeCOUNTRY);
            if (in_array($tType, $noDesc)) {
                $tmpArray['description'] = '';
            }

            // Add isDomain to the array
            if (isset($tName) && $tName == 'Domain Name' && $groupId != CUSTOM_FIELDS_FOR_PACKAGE) {
                $tmpArray['isDomain'] = true;
            }

            if (is_array($oldvalues) && isset($oldvalues['CT_' . $tID]) && $oldvalues['CT_' . $tID] != '') {
                $savedValue = htmlspecialchars($oldvalues['CT_' . $tID], ENT_QUOTES);
            } else {
                $this->user->getCustomFieldsValue($tID, $savedValue);
            }

            switch ($tType) {
                case typeDROPDOWN:
                    $script_name_array = explode(",", trim($dropdownoptions));
                    $selectOptions = array();

                    $tValue = '';
                    if ($type == 'profile') {
                        $this->user->getCustomFieldsValue($tID, $tValue);
                    }
                    $valueIsInDropdown = false;

                    foreach ($script_name_array as $option) {
                        // Allows for a label to be different then the value:
                        // such as: value,label 2(value 2),value 3
                        if (preg_match('/(.*)(?<!\\\)\((.*)(?<!\\\)\)/', $option, $matches)) {
                            $value = $matches[2];
                            $label = $matches[1];
                        } else {
                            $value = $label = $option;
                        }

                        $label = str_replace(array('\\(', '\\)'), array('(', ')'), $label);
                        $selectOptions[] = array($value,$label);

                        if ($value == $tValue) {
                            $valueIsInDropdown = true;
                        }
                    }

                    if (!$valueIsInDropdown && $tValue != "") {
                        $selectOptions[] = array($tValue, $tValue);
                    }

                    $tmpArray['dropdownoptions'] = $selectOptions;
                    break;
                case typeCOUNTRY:
                    // Get list of countries
                    $country_var_id = $tID;
                    $countries = new Countries($this->user);

                    if (strlen($savedValue) > 0) {
                        $tCountryCode = $savedValue;
                    } else {
                        $tCountryCode = $this->settings->get("Default Country");
                        $ip = CE_Lib::getRemoteAddr();

                        if ($ip !== false) {
                            $CountryCode = CE_Lib::getCountryCodeFromIP($ip);

                            if ($CountryCode !== false) {
                                $validCountryCode = $countries->validCountryCode($CountryCode, true);

                                if ($validCountryCode !== false) {
                                    $tCountryCode = $validCountryCode;
                                }
                            }
                        }
                    }

                    $savedValue = $tCountryCode;
                    $selectOptions = array();
                    $codes = $countries->getCodesArr(true);

                    foreach ($codes as $code => $name) {
                        $selectOptions[] = array($code, $this->user->lang($name));
                    }

                    $tmpArray['dropdownoptions'] = $selectOptions;
                    break;
                case typeSTATE:
                    // Get list of states
                    $state_var_id = $tID;
                    $states = new States($this->user);
                    $tCountryCode = $countrySavedValue;

                    if (strlen($savedValue) > 0) {
                        $tStateCode = $savedValue;
                    } else {
                        $tStateCode = '';
                        $ip = CE_Lib::getRemoteAddr();

                        if ($ip !== false) {
                            $StateName = CE_Lib::getStateNameFromIP($ip);

                            if ($StateName !== false) {
                                $validStateCode = $states->validStateCode($StateName['country iso'], $StateName['state name'], 'name', true);

                                if ($validStateCode !== false) {
                                    $tStateCode = $validStateCode;
                                }
                                $tCountryCode = $StateName['country iso'];
                            }
                        }
                    }

                    $savedValue = $tStateCode;

                    $selectOptions = array();
                    $codes = $states->getCodesArr($tCountryCode, true, $tStateCode);

                    foreach ($codes as $code => $name) {
                        $selectOptions[] = array($code, $this->user->lang($name));
                    }

                    $tmpArray['dropdownoptions'] = $selectOptions;
                    break;
                case typeLANGUAGE:
                    foreach (CE_Lib::getEnabledLanguages() as $key => $entry) {
                        $tmpArray['dropdownoptions'][] = array(ucFirst($entry), ucfirst($this->user->lang($entry)));
                    }
                    break;
                case typeVATNUMBER:
                    $vat_var_id = $tID;
                    break;
                case typeEMAIL:
                    $tmpArray['validation_type'] = "email";
                    break;
                case typeORGANIZATION:
                case typeADDRESS:
                    $tmpArray['width'] = "655";
                    break;
                case typeTEXTAREA:
                    $tmpArray['width'] = "660";
                    $tmpArray['height'] = "150";
                    break;
            }

            if ($tmpArray['isDomain'] == true) {
                // check if we have subdomains
                if (isset($advancedSettings['subdomain']) && $advancedSettings['subdomain'] != '' && !$package->hasBundledProducts()) {
                    $subDomains = explode(";", $advancedSettings['subdomain']);
                    $tmpArray['subdomains'] = $subDomains;
                    $tmpArray['fieldtype'] = 'subdomain';
                }
            }

            $tmpArray['value'] = $savedValue;
            $returnArray[] = $tmpArray;
        }

        // Password injection
        if ($injectPassword == true) {
            $tmpArray = array();
            $tmpArray['isrequired'] = true;
            $tmpArray['id'] = 'password';
            $tmpArray['ischangeable'] = true;
            $tmpArray['name'] = $this->user->lang('Password');
            $tmpArray['fieldtype'] = (string)TYPEPASSWORD;
            $tmpArray['description'] = $this->user->lang("Password you will use to login to your account"); // referred to in config.php

            // Add it
            $returnArray[] = $tmpArray;
        }

        if ($type == 'package') {
            $returnArray = array("customFields" => $returnArray);
            //return array("customFields" => $returnArray);
        } else {
            $returnArray = array(
            "customFields"   => $returnArray,
            "state_var_id"   => $state_var_id,
            "country_var_id" => $country_var_id,
            "vat_var_id"     => $vat_var_id
            );
        }

        return $returnArray;
    }

    //fieldtype
    function getAddons($productId = null, $priceTerm = null, $isUpgradePackage = false)
    {
        include_once 'modules/admin/models/Translations.php';
        $languages = CE_Lib::getEnabledLanguages();
        $translations = new Translations();
        $languageKey = ucfirst(strtolower($this->user->getLanguage()));

        $currency = new Currency($this->user);
        $currencyPrecision = $currency->getPrecision($this->currency);
        $this->initialize_currency();
        // package addons
        $selectedAddons = array();

        if (isset($_REQUEST['addonChargesStr'])) {
            $selectedAddons = $this->_getPriceIds($_REQUEST['addonChargesStr']);
        }

        $returnArray = array();

        $packageAddonGateway = new PackageAddonGateway();
        $addons = $packageAddonGateway->getPackageAddons($productId, 'addonIterator');
        $thereArePackageAddons = false;

        //Billing Cycles
        $billingcycles = array();

        include_once 'modules/billing/models/BillingCycleGateway.php';
        $gateway = new BillingCycleGateway();
        $iterator = $gateway->getBillingCycles(array(), array('order_value', 'ASC'));

        while ($cycle = $iterator->fetch()) {
            $billingcycles[$cycle->id] = array(
            'name'            => $this->user->lang($cycle->name),
            'time_unit'       => $cycle->time_unit,
            'amount_of_units' => $cycle->amount_of_units
            );
        }
        //Billing Cycles

        //Addon is taxable if package is taxable
        $package = new Package($productId);
        $package->getProductPricingAllCurrencies();
        $taxable = $package->isCurrencyTaxable($this->currency);

        while ($addon = $addons->fetch()) {
            $tmpArray = array();
            $lastid = null;

            $query = "SELECT `type` FROM `product_addon` WHERE `product_id` = ? AND `addon_id` = ? ";
            $result = $this->db->query($query, $productId, $addon->getId());
            list($addonType) = $result->fetch();

            if (!isset($addonType)) {
                $addonType = 0;
            }

            $firstIteration = true;
            $thereAreAddonsOptions = false;

            $tmpArray['id'] = $addon->getId();
            $tmpArray['taxable'] = $taxable ? 1 : 0;
            $tmpArray['name'] = $addon->getName();
            $tmpArray['namelanguage'] = $translations->getValue(ADDON_NAME, $addon->getId(), $languageKey, $addon->getName());
            $tmpArray['desc'] = $addon->getDescription();
            $tmpArray['desclanguage'] = $translations->getValue(ADDON_DESCRIPTION, $addon->getId(), $languageKey, $addon->getDescription());

            // Array to store all the differnent prices of the addon
            $tmpArray['prices'] = array();

            // loop over each cycle price
            foreach ($addon->getCyclePrices($priceTerm, $this->currency) as $price) {
                if (!is_array($price) || count($price) == 0) {
                    continue;
                }

                $priceOption = '';

                if (($isUpgradePackage && !$this->settings->get("Charge Setup Prices")) || !isset($price['setupFee'])) {
                    $price['setupFee'] = 0;
                    $price['hasSetupFee'] = false;
                } else {
                    $price['hasSetupFee'] = true;
                }

                if (!isset($price['cycleFee'])) {
                    $price['cycleFee'] = 0;
                }

                if ($price['setupFee'] != 0 || $price['cycleFee'] != 0) {
                    $priceOption .= ': ';
                    if ($price['setupFee'] != 0) {
                        $priceOption .= $currency->format($this->currency, $price['setupFee'], true, "NONE", true) . ' ' . $this->user->lang('one time fee');
                        if ($price['cycleFee'] != 0) {
                            $priceOption .= ' '.$this->user->lang('+').' ';
                        }
                    }
                    if ($price['cycleFee'] != 0) {
                        $priceOption .= $currency->format($this->currency, $price['cycleFee'], true, "NONE", true).' '.$this->user->lang('every').' '.$billingcycles[$price['cycle']]['name'];
                    }
                }

                $priceOptionLanguage = $translations->getValue(ADDON_OPTION_LABEL, $price['id'], $languageKey, $price['detail']).$priceOption;
                $priceOption = $price['detail'].$priceOption;

                $thereAreAddonsOptions = true;
                $tmpPriceArray = array();
                $tmpPriceArray['price_id'] = $price['id'];
                $tmpPriceArray['price'] = $priceOption;
                $tmpPriceArray['pricelanguage'] = $priceOptionLanguage;
                $tmpPriceArray['price_float'] = $currency->format($this->currency, $price['cycleFee']);
                $tmpPriceArray['recurringprice_cyle'] = $price['cycle'];
                $tmpPriceArray['term_name'] = $billingcycles[$price['cycle']]['name'];
                $tmpPriceArray['setupprice_value'] = $currency->format($this->currency, (isset($price['setupFee']) && $price['setupFee'] > 0) ? $price['setupFee'] : 0);
                $tmpPriceArray['item_name'] = $price['detail'];
                $tmpPriceArray['item_price'] = $price['cycleFee'];
                $tmpPriceArray['item_setup'] = $price['setupFee'];
                $tmpPriceArray['item_subtotal_amount'] = sprintf("%01.".$currencyPrecision."f", round($price['cycleFee'] + ((isset($price['setupFee']) && $price['setupFee'] > 0) ? $price['setupFee'] : 0), $currencyPrecision));
                $tmpPriceArray['item_has_setup'] = $price['hasSetupFee'];

                // prorating for package addons
                list($tAddonPrice, $addonProratedPrice, $addonProratedDays, $addonProIncFollowing, $addonTmpNextBill, $addonProNextBill)
                    = $this->processProrating($productId, $price['cycle'], $price['cycleFee']);

                $tmpPriceArray['price_float_prorated'] = $addonProratedPrice;
                $tmpPriceArray['next_bill_date'] = $addonProNextBill;

                // Ensure we don't reinitialize the price array if it already exists
                if ($lastid != $price['id']) {
                    $lastid = $price['id'];
                }

                switch ($addonType) {
                    case 0:    // dropdown
                    case 2:    // quantity
                        if ($selectedAddons) {
                            if (in_array($price['id'], array_keys($selectedAddons))) {
                                $selArr = $selectedAddons[$price['id']];
                                $tmpPriceArray['price_selected'] = in_array($price['cycle'], $selArr) ? 'selected = "true"' : '';
                            } else {
                                $tmpPriceArray['price_selected'] = $firstIteration ? 'selected = "true"' : '';
                            }
                        } else {
                            $tmpPriceArray['price_selected'] = $firstIteration ? 'selected = "true"' : '';
                        }
                        break;
                    case 1:    // radio buttons
                        if ($selectedAddons) {
                            if (in_array($price['id'], array_keys($selectedAddons))) {
                                $selArr = $selectedAddons[$price['id']];
                                $tmpPriceArray['price_selected'] = in_array($price['cycle'], $selArr) ? 'checked = "true"' : '';
                            } else {
                                $tmpPriceArray['price_selected'] = $firstIteration ? 'checked = "true"' : '';
                            }
                        } else {
                            $tmpPriceArray['price_selected'] = $firstIteration ? 'checked = "true"' : '';
                        }
                        break;
                }

                $firstIteration = false;
                $tmpArray['prices'][] = $tmpPriceArray;
            }

            if ($thereAreAddonsOptions) {
                $thereArePackageAddons = true;
                $tmpArray['addon_type'] = $addonType;
            }

            if (count($tmpArray['prices']) == 0) {
                continue;
            }

            $returnArray[] = $tmpArray;
        }

        return $returnArray;
    }

    function processProrating($productId, $paymentTerm, $price)
    {
        $currencyCode = base64_decode($this->session->currency);
        $billingCycle = new BillingCycle($paymentTerm);
        $package = new Package($productId);
        $package->getProductPricingAllCurrencies();
        $prorateToDay = $package->getCurrencyProrateToDay($currencyCode);
        $incFollPayment = $package->getCurrencyIncludeFollowingPayment($currencyCode);

        $tProratedPrice = 0;
        $proratedDays = 0;
        $proIncFollowing = 0;
        $tmpNextBill = 0;
        $proNextBill = 0;

        // 0 means disable prorating
        if ($prorateToDay != 0 && $billingCycle->amount_of_units > 0 && in_array($billingCycle->time_unit, array('m', 'y'))) {
            $today = date('d');
            $month = date('m');
            $year = date('Y');

            if ($today < $prorateToDay) {
                $proratedDays = $prorateToDay - $today;
                $tmpNextBill = mktime(0, 0, 0, $month, $prorateToDay);
            } elseif ($today == $prorateToDay) {
                $proratedDays = 0; // Don't pro-rate

                switch ($billingCycle->time_unit) {
                    case 'm':
                        $tmpNextBill = mktime(0, 0, 0, $month + $billingCycle->amount_of_units, $prorateToDay);
                        break;
                    case 'y':
                        $tmpNextBill = mktime(0, 0, 0, $month, $prorateToDay, $year + $billingCycle->amount_of_units);
                        break;
                }

                return array($price, $tProratedPrice, $proratedDays, $proIncFollowing, $tmpNextBill, $tmpNextBill);
            } else {
                $today_stamp = mktime(0, 0, 0, $month, $today);
                $end_date_stamp = mktime(0, 0, 0, $month + 1, $prorateToDay);
                $proratedDays = round(($end_date_stamp-$today_stamp) / (60*60*24)); // Conversion from seconds to days
                $tmpNextBill = mktime(0, 0, 0, $month + 1, $prorateToDay);
            }
        }

        //Check to see if this sale is going to get prorated
        if ($proratedDays > 0) {
            switch ($billingCycle->time_unit) {
                case 'm': // Assuming each month is 30 days for pricing
                    $tProratedPrice = ($price / ($billingCycle->amount_of_units * 30)) * $proratedDays;
                    break;
                case 'y': // Assuming each year is 12 months and each month is 30 days for pricing. That is, 12 * 30 = 360 days. This will give a better approximation than using 365 days
                    $tProratedPrice = ($price / ($billingCycle->amount_of_units * 360)) * $proratedDays;
                    break;
            }

            // Decide if we are also billing the customer for the full next billing cycle, or just for this prorated ammount.
            $proIncFollowing = 1;

            // Needs to be after the previous chunk of logic
            if ($prorateToDay != 0) {
                // -1 Never bill next payment
                // 0 Bill if prorating less than 11 days
                // 1 Always bill next payment
                // We unset $price so that it is not included in the total
                // payment due line displayed to the user.  Doesn't actually
                // affect anything outside of this function.
                if ($incFollPayment == -1
                  || ($incFollPayment == 0 && $proratedDays > 10)
                  || ($incFollPayment == 2 && $billingCycle->amount_of_units == 1 && $billingCycle->time_unit == 'm')
                  || ($incFollPayment == 3 && ($billingCycle->amount_of_units != 1 || $billingCycle->time_unit != 'm'))) {
                    $price = 0;
                    $proIncFollowing = 0;
                }
            }

            // Include the following payment if we are supposed to
            if ($proIncFollowing == 1) {
                switch ($billingCycle->time_unit) {
                    case 'm':
                        $proNextBill = strtotime('+'.$billingCycle->amount_of_units.' month', $tmpNextBill);
                        break;
                    case 'y':
                        $proNextBill = strtotime('+'.$billingCycle->amount_of_units.' year', $tmpNextBill);
                        break;
                }
            } else { // No following payment
                $proNextBill = strtotime('+0 month', $tmpNextBill);
            }
        }

        return array($price, $tProratedPrice, $proratedDays, $proIncFollowing, $tmpNextBill, $proNextBill);
    }

    /*
     * Function to add an item to the cart
     */
    function addToCart($packageId = null, $params = null, $onCart = false)
    {
        // Check if we got everything
        // ensure packageId is an int
        if ($packageId == null) {
            // Destroy the cart as something went wrong
            $this->destroyCart();
        } else {
            $packageId = (int)$packageId;
        }

        // get the current cart
        if (isset($this->session->cartContents)) {
            $cartContents = unserialize(base64_decode($this->session->cartContents));
        } else {
            $cartContents = array();
        }

        $cartItemId = uniqid();

        // Handle stock control if required
        $stock = false;
        $package = new Package($packageId);
        $stockInfo = unserialize($package->stockInfo);
        if (is_array($stockInfo)) {
            // Check if we are doing stockControl
            if ($stockInfo['stockEnabled'] == 1) {
                $stock = true;
                // Decrease the stock control option
                --$stockInfo['availableStock'];
                // Serialize it and send it back
                $package->stockInfo = serialize($stockInfo);
                // Save the pacakge
                $package->save();
            }

            $stockTimer = (int)$this->settings->get('Stock Timer');
            if ($stockTimer > 0) {
                //lets add to cart this order if it is time sensitive
                $activeOrder = new ActiveOrder();
                $activeOrder->setForcedId($cartItemId);
                $activeOrder->product_id = $packageId;
                $activeOrder->save();
                $activeOrder->setExpires($stockTimer+1);
            }
        }

        // Add to the cart
        $cartContents[$cartItemId] = array(
        'productId'  => $packageId,
        'params'     => $params,
        'stock'      => $stock,
        'cartItemId' => $cartItemId,
        'onCart'     => $onCart
        );

        // Save it to the session
        $this->session->cartContents = base64_encode(serialize($cartContents));

        // Save the new hash
        $this->session->cartHash = CE_Lib::generateSignupCartHash();

        return $cartItemId;
    }


    /*
     * Function to store the data for a product that's being ordered BEFORE its properly added to the cart
     */
    function addTempItem($params, $cartItemId = 0)
    {
        // Get the current contents of the temp item
        $currentItem = $this->getTempItem();

        if (!is_array($params)) {
            $params = array();
        }

        // Merge the arrays
        if (is_array($currentItem)) {
            $params = array_merge($params, $currentItem);
        }

        // Save it to the session
        $this->session->tempCartItem = base64_encode(serialize($params));

        // Save the new hash
        $this->session->tempCartItemHash = $this->generateTempItemHash();
        $this->session->tempCartItemId = $cartItemId;
    }

    /*
     * Function to return a temp item's information to be added to the cart. There can only ever be 1 temp item.
     */
    function getTempItem()
    {
        if (isset($this->session->tempCartItemHash) && $this->generateTempItemHash() == $this->session->tempCartItemHash && $this->session->tempCartItemHash != null && isset($this->session->tempCartItem)) {
            return unserialize(base64_decode($this->session->tempCartItem));
        } else {
            return false;
        }
    }

    /*
     * Function to get a temp item's hash to ensure security
     */
    function generateTempItemHash()
    {
        // Check the session
        if (isset($this->session->tempCartItem) && $this->session->tempCartItem) {
            // MD5 the base64 along with a unique string
            return md5($this->session->tempCartItem."ClientExecOrderForm-TempItem");
        } else {
            return null;
        }
    }

    /*
     * Function to process the data from a form post and then work out what to do next
     *
     * Returns an array of information on what we should be doing next.
     * - nextURL
     * - childProduct
     *
     * @input array - Array of form post data, can be sanitized or just pass $_POST
     * @input string - Location of the form that posted to us, can be static, usually pass $_REQUEST['formid']
     * @input string - Location of the page that is calling this function
     *
     * @return array Information on what to do next
     */
    function processFormPost($params, $actiontoperform)
    {
        $cartItemId = 0;

        $package = new Package($params['product']);
        $productGroupId = $package->planid;

        if (!isset($this->session->absoluteCartParentPackage) && isset($this->session->cartParentPackage)) {
            $this->session->absoluteCartParentPackage = $this->session->cartParentPackage;
        }

        if (!isset($this->session->absoluteCartParentDomainName) && isset($params['domainname'])) {
            $this->session->absoluteCartParentDomainName = trim($params['domainname']);
        }

        $currencyCode = base64_decode($this->session->currency);

        // If we are being called by the cart summary we need to add to the cart
        if (($actiontoperform == 'selectandprocess') || ($actiontoperform == 'select')) {
            //Billing Cycles
            $defaultCycle = false;
            $billingcycles = array();

            include_once 'modules/billing/models/BillingCycleGateway.php';
            $gateway = new BillingCycleGateway();
            $iterator = $gateway->getBillingCycles(array(), array('order_value', 'ASC'));

            while ($cycle = $iterator->fetch()) {
                if ($defaultCycle === false && $cycle->id != 0) {
                    $defaultCycle = $cycle->id;
                }

                $billingcycles[$cycle->id] = $cycle->id;
                $billingcycles[$cycle->amount_of_units.$cycle->time_unit] = $cycle->id;
                $billingcycles[$cycle->time_unit.$cycle->amount_of_units] = $cycle->id;
            }
            //Billing Cycles

            if ($defaultCycle === false) {
                $defaultCycle = 0;
            }

            // This code will set the paymentterm value even if not set, when there is only 1 billing cycle available for the product
            if ($params['product'] > 0) {
                $tPackage = new Package($params['product']);
                $tPackage->getProductPricingAllCurrencies();
                $productPricing = $this->prettyPricing($tPackage->pricingInformationCurrency[$currencyCode], $defaultCycle);

                if (count($productPricing) == 1) {
                    $defaultCycle = $productPricing[0]['termId'];
                }
            }

            $paymentterm = '';

            if (isset($params['paymentterm'])) {
                $paymentterm = (string) trim($params['paymentterm']);
            }

            if ($paymentterm === '' && isset($params['paymentTerm'])) {
                $paymentterm = (string) trim($params['paymentTerm']);
            }

            if (isset($billingcycles[$paymentterm])) {
                //The billing cycle exists with the provided id, or with the provided time unit and amount of units
                $params['paymentterm'] = $billingcycles[$paymentterm];
            } elseif (is_numeric($paymentterm) && isset($billingcycles[$paymentterm.'m'])) {
                //The billing cycle exists with the provided amount of months
                $params['paymentterm'] = $billingcycles[$paymentterm.'m'];
            } else {
                //Use the billing cycle with the lowest available amount of time
                $params['paymentterm'] = $defaultCycle;
            }

            //$cartItemId = $this->addToCart($params['product'], $postParams, true);
            $postParams = array(
            'product'     => $params['product'],
            'term'        => trim($params['paymentterm']),
            'is_domain'   => (isset($params['is_domain'])) ? true : false,
            'domain_name' => (isset($params['domainname'])) ? trim($params['domainname']) : ((isset($this->session->absoluteCartParentDomainName)) ? $this->session->absoluteCartParentDomainName : null),
            'domain_type' => (isset($params['domainType'])) ? $params['domainType'] : null,
            'eppCode'     => (isset($params['eppCode'])) ? $params['eppCode'] : null
            );
            if (isset($this->session->tempCartItemId)) {
                $cartItemId = $this->session->tempCartItemId;
            }

            $this->addTempItem($postParams, $cartItemId);

            if ($actiontoperform == 'selectandprocess') {
                $parentPackage = $this->_processTempItem($params);
            }

            if (isset($this->session->absoluteCartParentPackage) && $this->session->absoluteCartParentPackage && isset($parentPackage)) {
                $this->updateCartItem(
                    array(
                    // update the bundled domain if we have a parent package
                    $this->session->absoluteCartParentPackage => array(
                        'bundledDomain' => (isset($this->session->absoluteCartParentDomainName)) ? $this->session->absoluteCartParentDomainName : null,
                        'isBundle'      => array(
                            $productGroupId => $parentPackage
                        )
                    ),

                    //update this package with a reference to its absolute parent
                    $parentPackage => array(
                        'bundledBy' => $this->session->absoluteCartParentPackage
                    )
                    )
                );
            }

            if (isset($parentPackage)) {
                return $parentPackage;
            }
        } elseif ($actiontoperform == 'process') {
            $cartItemId = $this->_processTempItem($params);

            if (isset($this->session->absoluteCartParentPackage) && $this->session->absoluteCartParentPackage) {
                $this->updateCartItem(
                    array(
                    // update the bundled package if we have a parent package
                    $this->session->absoluteCartParentPackage => array(
                        'bundledDomain' => (isset($this->session->absoluteCartParentDomainName)) ? $this->session->absoluteCartParentDomainName : null,
                        'isBundle'      => array(
                            $productGroupId => $cartItemId
                        )
                    ),

                    //update this package with a reference to its absolute parent
                    $cartItemId => array(
                        'bundledBy' => $this->session->absoluteCartParentPackage
                    )
                    )
                );
            }

            // If we have a payment term here, update it in the cart, as this could be an updated term from step 2.
            if ($params['paymentterm'] != '') {
                $this->updateCartItem([
                    $cartItemId => [
                        'params' =>[
                            'term' => $params['paymentterm']
                        ]
                    ]
                ]);
            }

            // This needs to be refactored into it's own function that also gets called from SignupPublicController::validateCoupon()
            if (isset($params['couponCode'])) {
                if (CE_Lib::generateSignupCartHash() == @$this->session->cartHash && @$this->session->cartHash != null) {
                    $cartItems = unserialize(base64_decode(@$this->session->cartContents));
                    if (@is_array($cartItems[$cartItemId])) {
                        $couponCode = $params['couponCode'];
                        $package = new Package($cartItems[$cartItemId]['productId']);
                        $package->getProductPricingAllCurrencies();

                        if (!@$productGroupInfo[$package->planid]) {
                                $productGroup = PackageTypeGateway::getPackageTypes($package->planid);
                                $productGroupInfo[$package->planid] = $productGroup->fetch();
                        }

                        $billingCycle = $cartItems[$cartItemId]['params']['term'];
                        $return = Coupon::validate(
                            $couponCode,
                            $productGroupInfo[$package->planid]->fields['id'],
                            $package->id,
                            $billingCycle,
                            $currencyCode
                        );

                        if (is_array($return)) {
                            $cartItems[$cartItemId]['couponCode'] = $return['id'];
                            $cartItems['couponCodes'][$return['id']] = $return;
                            $this->session->cartContents = base64_encode(serialize($cartItems));
                            $this->session->cartHash = CE_Lib::generateSignupCartHash();
                        }
                    }
                }
            }
        }

        return $cartItemId;
    }

    function _processTempItem($params)
    {
        $parentProduct = null;
        $productId = $params['product'];
        $tempItem = $this->getTempItem();

        $passed_custom_fields = $_POST;

        //some products send custom fields in an array instead of root
        //so let's take a look and if so we can copy
        if (isset($_POST['products']) && isset($_POST['products'][$productId])) {
            $passed_custom_fields = $_POST['products'][$productId];
        }

        // Make the params to pass
        $postParams = array('term' => $tempItem['term']);

        // Process the posted custom fields
        $customFields = array();
        $addonFields = array();
        $addonQuantities = array();
        $extraAttributes = array();

        foreach ($passed_custom_fields as $key => $value) {
            // Explode to look for custom fields
            $explodePost = explode("_", $key);
            //Explode for extra attributes
            $keyExplode = explode("-", $key);

            // Check for custom fields
            if (isset($explodePost[0]) && $explodePost[0] == 'CT') {
                if (isset($params['subdomaintld_' . $key])) {
                    $customFields[$explodePost[1]] = $value  . '.' . $params['subdomaintld_' . $key];
                } else {
                    $customFields[$explodePost[1]] = $value;
                }
            }

            // Check for addons
            if (isset($explodePost[0]) && $explodePost[0] == 'addonSelect') {
                $addonFields[$explodePost[1]] = $value;
            }

            // Check for addons quantities
            if (isset($explodePost[0]) && $explodePost[0] == 'addonQuantity') {
                $addonQuantities[$explodePost[1]] = $value;
            }

            //check for extra attributes
            if (isset($keyExplode[1]) && $keyExplode[1] == 'EA') {
                $extraAttributes[$keyExplode[2]] = $value;
            }
        }

        // Add the three
        $postParams['customFields'] = $customFields;
        $postParams['addons'] = $addonFields;
        $postParams['addonsQuantities'] = $addonQuantities;

        /* some type of products might need additional information */

        if ($tempItem['is_domain']) {
            $domain = DomainNameGateway::splitDomain($tempItem['domain_name']);
            $postParams['isDomain'] = true;
            $postParams['domain_name'] = $tempItem['domain_name'];

            if ($tempItem['domain_type'] == 'register') {
                $postParams['domainType'] = '0';
            } elseif ($tempItem['domain_type'] == 'transfer') {
                $postParams['domainType'] = '1';
            }

            $postParams['sld'] = $domain[0];
            $postParams['tld'] = $domain[1];

            // Get any extra attributes
            $postParams['extraAttributes'] = $extraAttributes;
            // add eppCode to extra attributes
            if ($tempItem['domain_type'] == 'transfer') {
                $postParams['extraAttributes']['eppCode'] = $tempItem['eppCode'];
            }
        }

        //If we are skipping all we wanted was really to add the customfields,addons, additioninfo to the temp item
        // Add to the cart
        $parentProduct = $this->addToCart($productId, $postParams, true);
        $this->destroyTempItem(true);

        return $parentProduct;
    }

    /*
     * Function to process the cart summary
     */
    function getCartSummary()
    {
        $this->currencys = new Currency($this->user);
        $this->initialize_currency();
        $this->initialize_couponCode();

        //Billing Cycles
        $billingcycles = array();

        include_once 'modules/billing/models/BillingCycleGateway.php';
        $gateway = new BillingCycleGateway();
        $iterator = $gateway->getBillingCycles(array(), array('order_value', 'ASC'));

        while ($cycle = $iterator->fetch()) {
            $billingcycles[$cycle->id] = array(
            'name'            => $this->user->lang($cycle->name),
            'time_unit'       => $cycle->time_unit,
            'amount_of_units' => $cycle->amount_of_units
            );
        }
        //Billing Cycles

        if (isset($_REQUEST['step']) && in_array($_REQUEST['step'], array(0, 1, 4))) {
            $this->removeInvalidItemsFromCart();
        }

        // Override the cart
        if (isset($_GET['cleanCart']) && $_GET['cleanCart']) {
            $this->destroyCart();
            $this->destroyCurrency();
            $this->destroyCouponCode();
            $this->destroyInvoiceInformation();
            CE_Lib::redirectPage('order.php');
        }

        $viewAttributes = array();
        $viewAttributes['showTimer'] = false;
        $packagesWithStockEnabled = array();

        // Check if the cart has been tampered with
        if (isset($this->session->cartHash) && CE_Lib::generateSignupCartHash() == $this->session->cartHash && $this->session->cartHash != null) {
            // Get the cart items
            if (isset($this->session->cartContents)) {
                $cartItems = unserialize(base64_decode($this->session->cartContents));
            } else {
                $cartItems = array();
            }

            // Set an array to use for product group info to avoid getting it potientially many times
            $productGroupInfo = array();

            if (count($cartItems) == 0) {
                // Something went wrong so empty the cart just to be sure
                $this->destroyCart();

                // Return nothing
                return array(
                'cartTotal' => array(
                    'price'                       => $this->currencys->format($this->currency, 0, true, "NONE", false, true),
                    'recurringItems'              => false,
                    'truePrice'                   => 0,
                    'totalBeforeCoupons'          => 0,
                    'totalBeforeCouponsFormatted' => $this->currencys->format($this->currency, 0, true, "NONE", false, true)
                ),
                'cartItems' => '',
                'cartCount' => 0
                );
            }
            // Calculate the next due date when prorating
            $realProNextBill = 0;

            // Set an array for products to hide options
            $hideOptions = array();

            // Set an array for products with free domain options
            $freeDomainOptions = array();

            // Work out a running total
            $runningTotal = 0;
            $trueTotal = 0;
            $finalCart = array();
            $i = 0;

            foreach ($cartItems as $value => $key) {
                //CE_Lib::debug(array($i,$key,$value));
                //CE_Lib::debug($value);

                // Skip coupons
                if ($value == 'couponCodes') {
                    continue;
                }

                //print_r($key);

                // Make a working item
                $workingItem = array();

                // Set some vars
                $workingItem['cartID'] = $i;

                // Gracefully ignore a product ID of 0
                if ($key['productId'] == 0) {
                    continue;
                }

                // Get the product's information
                $package = new Package($key['productId']);
                $package->getProductPricingAllCurrencies();

                //we want to keep track of weather we need to show the timer information on cart summary
                //lets get a count of stock enabled products in our cart
                if ($package->getStockEnabled()) {
                    $packagesWithStockEnabled[] = $value;
                }

                // Get the product group information
                if (!isset($productGroupInfo[$package->planid])) {
                    $productGroup = PackageTypeGateway::getPackageTypes($package->planid);
                    $productGroupInfo[$package->planid] = $productGroup->fetch();
                }
                include_once 'modules/admin/models/Translations.php';
                $languages = CE_Lib::getEnabledLanguages();
                $translations = new Translations();
                $languageKey = ucfirst(strtolower($this->user->getLanguage()));

                // Set some vars
                $workingItem['name'] = $package->planname;
                $workingItem['productId'] = $key['productId'];
                $workingItem['namelanguage'] = $translations->getValue(PRODUCT_NAME, $key['productId'], $languageKey, $workingItem['name']);

                $workingItem['groupName'] = $productGroupInfo[$package->planid]->fields['name'];
                $workingItem['groupNameLanguage'] = $translations->getValue(PRODUCT_GROUP_NAME, $package->planid, $languageKey, $workingItem['groupName']);
                $workingItem['groupType'] = $productGroupInfo[$package->planid]->fields['type'];
                $workingItem['cartItemId'] = $key['cartItemId'];
                $workingItem['hasAddons'] = false;
                $workingItem['appliedCoupon'] = '';
                $includesAddons = false;
                $affectsSetup = false;
                $affectsPrice = false;

                // Set safe names up with no quotes for the JS delete functions
                $workingItem['safeGroupName'] = str_replace("'", "", $productGroupInfo[$package->planid]->fields['name']);
                $workingItem['safeGroupName'] = str_replace('"', "", $workingItem['safeGroupName']);
                $workingItem['safeName'] = str_replace("'", "", $package->planname);
                $workingItem['safeName'] = str_replace('"', "", $workingItem['name']);

                // Set any hidden attributes
                if (isset($hideOptions[$key['cartItemId']])) {
                    $workingItem['hidden'] = $hideOptions[$key['cartItemId']];
                }

                // Is the product taxable?
                $workingItem['taxable'] = $package->isCurrencyTaxable($this->currency);

                //Coupon Code
                $return = 0;

                // Do we have a coupon code?
                if (isset($this->couponCode) && $this->couponCode !== '') {
                    $return = Coupon::validate(
                        $this->couponCode,
                        $productGroupInfo[$package->planid]->fields['id'],
                        $package->id,
                        $key['params']['term'],
                        $this->currency
                    );
                }

                if (!is_array($return)) {
                    // Do we have an automatic coupon?
                    if (isset($package->pricingInformationCurrency[$this->currency]['automaticCoupon']) && (!isset($cartItems['couponCodes'][$key['couponCode']]) || !is_array($cartItems['couponCodes'][$key['couponCode']]))) {
                        $return = Coupon::validate(
                            $package->pricingInformationCurrency[$this->currency]['automaticCoupon'],
                            $productGroupInfo[$package->planid]->fields['id'],
                            $package->id,
                            $key['params']['term'],
                            $this->currency
                        );
                    }
                }

                if (is_array($return)) {
                    // Push the coupon code to the product
                    $cartItems[$value]['couponCode'] = $return['id'];

                    // Add the coupon code the session
                    $cartItems['couponCodes'][$return['id']] = $return;

                    // Update session
                    $this->session->cartContents = base64_encode(serialize($cartItems));

                    // Save the new hash
                    $this->session->cartHash = CE_Lib::generateSignupCartHash();

                    // Set up the coupon information
                    $key['couponCode'] = $return['id'];
                }
                //Coupon Code

                // Process the addons here first so we can add to the package pricing later
                // Have to reset the arrays here as we don't enter the IF statement meaning addon totals stay for the next product
                $finalAddons = array();
                $addonsTotal = array(
                'price' => 0,
                'setup' => 0
                );
                if (isset($key['params']['addons']) && $key['params']['addons']) {
                    $addon_term = $key['params']['term'];

                    $packageAddons = $this->getAddons($key['productId'], $addon_term);

                    // Loop the addons in the array
                    foreach ($key['params']['addons'] as $addonId => $addonValue) {
                        // Explode the addon value
                        $addonExplode = explode("_", $addonValue);

                        // Check we have enough info from the post
                        if ((count($addonExplode) == 4) && (count($packageAddons) > 0)) {
                            // Set addons up
                            $workingItem['hasAddons'] = true;

                            // Get the addon
                            foreach ($packageAddons as $addon) {
                                // Do we have the right addon?
                                if ($addon['id'] == $addonId) {
                                    // Get the price
                                    foreach ($addon['prices'] as $price) {
                                        if ($price['price_id'] == $addonExplode[2] && $price['recurringprice_cyle'] == $addonExplode[3]) {
                                            $workingAddon = array();
                                            $workingAddon['name'] = $addon['name'];
                                            $workingAddon['namelanguage'] = $addon['namelanguage'];
                                            $workingAddon['item'] = $price['price'];
                                            $workingAddon['itemlanguage'] = $price['pricelanguage'];
                                            $workingAddon['taxable'] = (isset($addon['taxable']))? $addon['taxable'] : 0;
                                            $workingAddon['recurringprice_cyle'] = $price['recurringprice_cyle'];

                                            //$workingAddon['price'] = (isset($addon['price']))? $addon['price'] : 0;
                                            $workingAddon['item_price'] = $price['item_price'];
                                            $workingAddon['item_setup'] = $price['item_setup'];
                                            $workingAddon['price_float_prorated'] = $price['price_float_prorated'];
                                            $workingAddon['next_bill_date'] = $price['next_bill_date'];
                                            $workingAddon['addonQuantity'] = (isset($key['params']['addonsQuantities'][$addonId]))? $this->verifyAddonQuantity($key['params']['addonsQuantities'][$addonId]) : 1;
                                            $workingAddon['isQuantity'] = isset($key['params']['addonsQuantities'][$addonId]);

                                            $finalAddons[] = $workingAddon;

                                            // Add to the total
                                            $addonsTotal['price'] += $price['item_price'] * $workingAddon['addonQuantity'];
                                            $addonsTotal['setup'] += $price['item_setup'] * $workingAddon['addonQuantity'];
                                        }
                                    }
                                }
                            }
                        }
                    }

                    $workingItem['addons'] = $finalAddons;
                    $workingItem['addonsTruePrice'] = $addonsTotal['price'];
                    $workingItem['addonsTrueSetup'] = $addonsTotal['setup'];
                }

                // Get pricing based on types
                // Note this is before ANY deductions or additions.
                if ($workingItem['groupType'] == 3) {
                    $workingItem['isDomain'] = true;
                    $workingItem['domainName'] = (isset($key['params']['domain_name']))? $key['params']['domain_name'] : '';
                    //CE_Lib::debug($key);
                    // Check the pricing is valid, and get the right options
                    $domainPricing = $package->isCurrencyTermValid($this->currency, $key['params']['term']);

                    // Work out the total
                    if ($key['params']['domainType'] == 0) {
                        // Add the extra text for the title
                        $workingItem['titleText'] = $this->user->lang('Registration');
                        $workingItem['term'] = $package->getTermText($key['params']['term']);

                        // Get values to determine if the domain will be free or not
                        if ($workingItem['domainName'] != '' && $workingItem['domainName'] != null && isset($freeDomainOptions[$workingItem['domainName']])) {
                            $cartParentPackageFreeDomain = $freeDomainOptions[$workingItem['domainName']]['freedomain'];
                            $cartParentPackageDomainExtension = $freeDomainOptions[$workingItem['domainName']]['domainextension'];
                            $cartParentPackageDomainCycle = $freeDomainOptions[$workingItem['domainName']]['domaincycle'];
                        } else {
                            $cartParentPackageFreeDomain = 0;
                            $cartParentPackageDomainExtension = array();
                            $cartParentPackageDomainCycle = array();
                        }

                        // If free domain, set price to 0
                        if ($cartParentPackageFreeDomain > 0 && in_array($key['productId'], $cartParentPackageDomainExtension) && in_array($key['params']['term'], $cartParentPackageDomainCycle)) {
                            $domainPricing['price'] = 0;
                            if ($cartParentPackageFreeDomain == 2) {
                                $domainPricing['renew'] = 0;
                            }
                        }

                        // Add the true prices & terms
                        $workingItem['truePrice'] = $domainPricing['price'];
                        $workingItem['totalPrice'] = $domainPricing['price'];
                        $workingItem['renewPrice'] = $domainPricing['renew'];
                        $workingItem['renewTotalPrice'] = $domainPricing['renew'];
                        $workingItem['trueSetup'] = null;
                        $workingItem['setupFee'] = null;
                        $workingItem['trueTerm'] = $key['params']['term'];
                    } elseif ($key['params']['domainType'] == 1) {
                        // Add the extra title text
                        $workingItem['titleText'] = $this->user->lang('Transfer');
                        $workingItem['term'] = $package->getTermText($key['params']['term']);

                        // Get values to determine if the domain will be free or not
                        if ($workingItem['domainName'] != '' && $workingItem['domainName'] != null && isset($freeDomainOptions[$workingItem['domainName']])) {
                            $cartParentPackageFreeDomain = $freeDomainOptions[$workingItem['domainName']]['freedomain'];
                            $cartParentPackageDomainExtension = $freeDomainOptions[$workingItem['domainName']]['domainextension'];
                            $cartParentPackageDomainCycle = $freeDomainOptions[$workingItem['domainName']]['domaincycle'];
                        } else {
                            $cartParentPackageFreeDomain = 0;
                            $cartParentPackageDomainExtension = array();
                            $cartParentPackageDomainCycle = array();
                        }

                        // If free domain, set price to 0
                        if ($cartParentPackageFreeDomain > 0 && in_array($key['productId'], $cartParentPackageDomainExtension) && in_array($key['params']['term'], $cartParentPackageDomainCycle)) {
                            $domainPricing['transfer'] = 0;
                            if ($cartParentPackageFreeDomain == 2) {
                                $domainPricing['renew'] = 0;
                            }
                        }

                        // Add the true prices & terms
                        $workingItem['truePrice'] = $domainPricing['transfer'];
                        $workingItem['totalPrice'] = $domainPricing['transfer'];
                        $workingItem['renewPrice'] = $domainPricing['renew'];
                        $workingItem['renewTotalPrice'] = $domainPricing['renew'];
                        $workingItem['trueSetup'] = null;
                        $workingItem['setupFee'] = null;
                        $workingItem['trueTerm'] = $key['params']['term'];
                    }
                } else {
                    $workingItem['titleText'] = "";

                    // Check if we have a valid price term
                    if ($package->isCurrencyPriceIncluded($this->currency, $key['params']['term'])) {
                        // Set some basic information
                        $workingItem['term'] = $package->getTermText($key['params']['term']);

                        // Add the true prices & terms
                        $workingItem['truePrice'] = $package->getCurrencyPrice($this->currency, $key['params']['term']);
                        $workingItem['totalPrice'] = $package->getCurrencyPrice($this->currency, $key['params']['term']);
                        $workingItem['trueSetup'] = $package->getCurrencySetupFee($this->currency, $key['params']['term']);
                        $workingItem['setupFee'] = $package->getCurrencySetupFee($this->currency, $key['params']['term']);
                        $workingItem['trueTerm'] = $key['params']['term'];
                    } else {
                        // Something went wrong so abandon ship
                        CE_Lib::log(1, "Invalid price term found during signup for ".$key['productId']." and term:".$key['params']['term'], false, false);
                        $this->destroyCart();
                        CE_Lib::redirectPage('order.php');
                    }
                }

                // Set the extra ddon pricing
                $addonsTotal['totalPrice'] = $addonsTotal['price'];
                $addonsTotal['totalSetup'] = $addonsTotal['setup'];

                /*
                 * Handle any deductions from coupons and also additions from addons.
                 * The coupon codes decide when we include the addon prices
                 */
                $couponAmount = 0;
                if (isset($cartItems['couponCodes'][$key['couponCode']]) && is_array($cartItems['couponCodes'][$key['couponCode']])) {
                    // Set some basic coupon related information
                    $workingItem['hasCoupon'] = true;
                    $workingItem['appliedCoupon'] = $cartItems['couponCodes'][$key['couponCode']]['code'];
                    $workingItem['appliedCouponId'] = $cartItems['couponCodes'][$key['couponCode']]['id'];
                    $workingItem['appliedCouponTaxable'] = $cartItems['couponCodes'][$key['couponCode']]['taxable'];

                    // What type of coupon are we doing?
                    if ($cartItems['couponCodes'][$key['couponCode']]['type'] == 0) {
                        $includesAddons = false;

                        // Amount based coupon
                        // NOTE: We don't take into account the addon pricing here as the coupon system
                        // does not support this yet.
                        $couponAmount = $cartItems['couponCodes'][$key['couponCode']]['discount'];
                        $couponAmountRenew = $couponAmount;
                        $workingItem['appliedCouponAmount'] = $couponAmount;

                        // If we have a recurring coupon then deduct the amount from the product fee first
                        if ($cartItems['couponCodes'][$key['couponCode']]['recurring'] == 1) {
                            // Deduct it from the product fee first
                            if ((isset($workingItem['truePrice']) && $workingItem['truePrice']) || (isset($addonsTotal['price']) && $addonsTotal['price'])) {
                                $affectsPrice = true;

                                if (isset($workingItem['truePrice'])) {
                                    $couponAmount = $couponAmount - $workingItem['truePrice'];
                                    $workingItem['totalPrice'] = max(0, 0 - $couponAmount);

                                    if (isset($workingItem['groupType']) && $workingItem['groupType'] == 3 && isset($workingItem['isDomain']) && $workingItem['isDomain'] == 1) {
                                        $couponAmountRenew = $couponAmountRenew - ((isset($workingItem['renewPrice']))? $workingItem['renewPrice'] : 0);
                                        $workingItem['renewTotalPrice'] = max(0, 0 - $couponAmountRenew);
                                    }
                                }

                                if (isset($addonsTotal['price']) && $couponAmount > 0) {
                                    $couponAmount = $couponAmount - $addonsTotal['price'];
                                    $addonsTotal['totalPrice'] = max(0, 0 - $couponAmount);
                                }
                            }

                            // Deduct the rest from the setup fees
                            if ($couponAmount > 0 && ((isset($workingItem['trueSetup']) && $workingItem['trueSetup']) || (isset($addonsTotal['setup']) && $addonsTotal['setup']))) {
                                $affectsSetup = true;

                                if (isset($workingItem['trueSetup']) && $workingItem['trueSetup']) {
                                    $couponAmount = $couponAmount - $workingItem['trueSetup'];
                                    $workingItem['setupFee'] = max(0, 0 - $couponAmount);
                                }

                                if (isset($addonsTotal['setup']) && $couponAmount > 0) {
                                    $couponAmount = $couponAmount - $addonsTotal['setup'];
                                    $addonsTotal['totalSetup'] = max(0, 0 - $couponAmount);
                                }
                            }
                        } else {
                            // Deduct it from the setup fees first then product fee
                            if ((isset($workingItem['trueSetup']) && $workingItem['trueSetup']) || (isset($addonsTotal['setup']) && $addonsTotal['setup'])) {
                                $affectsSetup = true;
                                $includesAddons = false;

                                if (isset($workingItem['trueSetup']) && $workingItem['trueSetup']) {
                                    $couponAmount = $couponAmount - $workingItem['trueSetup'];
                                    $workingItem['setupFee'] = max(0, 0 - $couponAmount);
                                }

                                if (isset($addonsTotal['setup']) && $couponAmount > 0) {
                                    $couponAmount = $couponAmount - $addonsTotal['setup'];
                                    $addonsTotal['totalSetup'] = max(0, 0 - $couponAmount);
                                }
                            }

                            // Do we have of the coupon amount left to apply to the fee?
                            if ($couponAmount > 0) {
                                // Amount based
                                $affectsPrice = true;
                                $includesAddons = false;

                                if (isset($workingItem['truePrice'])) {
                                    $couponAmount = $couponAmount - $workingItem['truePrice'];
                                    $workingItem['totalPrice'] = max(0, 0 - $couponAmount);
                                }

                                if (isset($addonsTotal['price']) && $couponAmount > 0) {
                                    $couponAmount = $couponAmount - $addonsTotal['price'];
                                    $addonsTotal['totalPrice'] = max(0, 0 - $couponAmount);
                                }
                            }
                        }
                    } else {
                        // This is where the fun starts, we need to handle %age based coupons
                        $couponAmount = $cartItems['couponCodes'][$key['couponCode']]['discount'];

                        // We need to work out what exactly the coupon applies to figure wize
                        $couponAppliesTo = $cartItems['couponCodes'][$key['couponCode']]['applicableTo'];

                        $workingItem['appliedCouponAmount'] = 0;
                        if (COUPON_APPLIESTO_PACKAGE & $couponAppliesTo) {
                            // Package Only
                            $affectsPrice = true;

                            // Calculate the totals
                            $couponAmount = ((isset($workingItem['truePrice']))? $workingItem['truePrice'] : 0) * $cartItems['couponCodes'][$key['couponCode']]['discount'];
                            $workingItem['appliedCouponAmountToPackage'] = $couponAmount;
                            $workingItem['appliedCouponAmount'] += $couponAmount;

                            if (isset($workingItem['groupType']) && $workingItem['groupType'] == 3 && isset($workingItem['isDomain']) && $workingItem['isDomain'] == 1) {
                                $itemDiscountRenew = ((isset($workingItem['renewPrice']))? $workingItem['renewPrice'] : 0) * $cartItems['couponCodes'][$key['couponCode']]['discount'];
                                $workingItem['renewTotalPrice'] = max(((isset($workingItem['renewTotalPrice']))? $workingItem['renewTotalPrice'] : 0) - $itemDiscountRenew, 0);
                            }

                            $couponAmount = ((isset($workingItem['truePrice']))? $workingItem['truePrice'] : 0) - $couponAmount;

                            if ($couponAmount > 0) {
                                $workingItem['totalPrice'] = $couponAmount;
                            } else {
                                $workingItem['totalPrice'] = '0.00';
                            }
                        }
                        if (COUPON_APPLIESTO_PACKAGE_SETUP & $couponAppliesTo) {
                            // Setup fee only
                            $affectsSetup = true;

                            // Calculate the totals
                            $couponAmount = ((isset($workingItem['trueSetup']) && $workingItem['trueSetup'])? $workingItem['trueSetup'] : 0) * $cartItems['couponCodes'][$key['couponCode']]['discount'];
                            $workingItem['appliedCouponAmount'] += $couponAmount;
                            $couponAmount = ((isset($workingItem['trueSetup']) && $workingItem['trueSetup'])? $workingItem['trueSetup'] : 0) - $couponAmount;

                            if ($couponAmount > 0) {
                                $workingItem['setupFee'] = $couponAmount;
                            } else {
                                $workingItem['setupFee'] = '0.00';
                            }
                        }
                        if (COUPON_APPLIESTO_ADDONS & $couponAppliesTo) {
                            // Addons only
                            $affectsPrice = true;

                            // Get the total discount from addons price
                            $couponAmount = $addonsTotal['price'] * $cartItems['couponCodes'][$key['couponCode']]['discount'];
                            $workingItem['appliedCouponAmountToAddons'] = $couponAmount;
                            $workingItem['appliedCouponAmount'] += $couponAmount;

                            if (isset($addonsTotal['price'])) {
                                $itemDiscount = $addonsTotal['price'] * $cartItems['couponCodes'][$key['couponCode']]['discount'];
                                $addonsTotal['totalPrice'] = $addonsTotal['price'] - $itemDiscount;
                            }
                        }
                        if (COUPON_APPLIESTO_ADDONS_SETUP & $couponAppliesTo) {
                            // Addons setup only
                            $affectsSetup = true;

                            // Get the total discount from addon setup
                            $couponAmount = $addonsTotal['setup'] * $cartItems['couponCodes'][$key['couponCode']]['discount'];
                            $workingItem['appliedCouponAmount'] += $couponAmount;

                            if (isset($addonsTotal['setup'])) {
                                $itemDiscount = $addonsTotal['setup'] * $cartItems['couponCodes'][$key['couponCode']]['discount'];
                                $addonsTotal['totalSetup'] = $addonsTotal['setup'] - $itemDiscount;
                            }
                        }
                    }
                }

                // Do we have to add up the addon prices?
                if (!$includesAddons) {
                    $workingItem['finalPrice'] = ((isset($workingItem['totalPrice']) && $workingItem['totalPrice'])? $workingItem['totalPrice'] : 0) + ((isset($addonsTotal['totalPrice']) && $addonsTotal['totalPrice'])? $addonsTotal['totalPrice'] : 0);
                    $workingItem['tempTruePrice'] = ((isset($workingItem['truePrice']) && $workingItem['truePrice'])? $workingItem['truePrice'] : 0) + ((isset($addonsTotal['price']) && $addonsTotal['price'])? $addonsTotal['price'] : 0);

                    $workingItem['finalSetup'] = ((isset($workingItem['setupFee']) && $workingItem['setupFee'])? $workingItem['setupFee'] : 0) + ((isset($addonsTotal['totalSetup']) && $addonsTotal['totalSetup'])? $addonsTotal['totalSetup'] : 0);
                    $workingItem['tempTrueSetup'] = ((isset($workingItem['trueSetup']) && $workingItem['trueSetup'])? $workingItem['trueSetup'] : 0) + ((isset($addonsTotal['setup']) && $addonsTotal['setup'])? $addonsTotal['setup'] : 0);
                } else {
                    $workingItem['finalPrice'] = $workingItem['totalPrice'];
                    $workingItem['tempTruePrice'] = $workingItem['truePrice'] + ((isset($addonsTotal['price']))? $addonsTotal['price'] : 0);
                    $workingItem['finalSetup'] = $workingItem['setupFee'];
                    $workingItem['tempTrueSetup'] = $workingItem['trueSetup'] + ((isset($addonsTotal['setup']))? $addonsTotal['setup'] : 0);
                }

                if ($workingItem['finalPrice'] == $workingItem['tempTruePrice'] && $workingItem['finalSetup'] == $workingItem['tempTrueSetup']) {
                    // Remove the coupon information
                    if (isset($cartItems[$value]['couponCode'])) {
                        unset($cartItems[$value]['couponCode']);
                    }
                    if (isset($key['couponCode'])) {
                        unset($key['couponCode']);
                    }

                    // Update session
                    $this->session->cartContents = base64_encode(serialize($cartItems));
                    // Save the new hash
                    $this->session->cartHash = CE_Lib::generateSignupCartHash();
                }

                // Process prorating
                $workingItem['isProRated'] = false;

                // *** Starting prorating code ***
                // We are prorating, so the only discount possible is an Amount Coupon.
                $discount = max($workingItem['tempTruePrice'] - $workingItem['finalPrice'], 0);

                // Lets add the amount to the $couponAmount, and we will deduct it again also with the prorate.
                if ($discount > 0) {
                    if ($couponAmount > 0 && $cartItems['couponCodes'][$key['couponCode']]['type'] == 0) {
                        $couponAmount += $discount;
                    } else {
                        $couponAmount = $discount;
                    }
                } else {
                    $couponAmount = 0;
                }

                list($itemPrice, $proRatedPrice, $proratedDays, $proIncFollowing, $tmpnextBill, $proNextBill) = $this->processProrating($workingItem['productId'], $workingItem['trueTerm'], $workingItem['truePrice']);

                if ($proRatedPrice > 0) {
                    $workingItem['isProRated'] = true;

                    if ($cartItems['couponCodes'][$key['couponCode']]['type'] == 1) {
                        $workingItem['appliedCouponAmount'] -= $workingItem['appliedCouponAmountToPackage'];
                        $couponAmount -= $workingItem['appliedCouponAmountToPackage'];

                        // Calculate the totals
                        $workingItem['appliedCouponAmountToPackage'] = ($itemPrice + $proRatedPrice) * $cartItems['couponCodes'][$key['couponCode']]['discount'];
                        $workingItem['appliedCouponAmount'] += $workingItem['appliedCouponAmountToPackage'];
                        $couponAmount += $workingItem['appliedCouponAmountToPackage'];
                    }
                }

                $applyDiscount = sprintf("%01.2f", round($couponAmount, 2));

                // Lets discount as much as we can from the $couponAmount
                $workingItem['finalPrice'] = max($itemPrice + $proRatedPrice - $applyDiscount, 0);
                $workingItem['tempTruePrice'] = $itemPrice + $proRatedPrice;

                if ($applyDiscount > 0) {
                    $couponAmount = $couponAmount - $itemPrice - $proRatedPrice;
                }

                if ($couponAmount < 0) {
                    $couponAmount = 0;
                }

                if ($realProNextBill !== 0) {
                    if ($proNextBill !== 0) {
                        $realProNextBill = min($realProNextBill, $proNextBill);
                    }
                } else {
                    $realProNextBill = $proNextBill;
                }

                if (isset($workingItem['addons']) && is_array($workingItem['addons'])) {
                    foreach ($workingItem['addons'] as $tempAddon) {
                        list($itemPrice, $proRatedPrice, $proratedDays, $proIncFollowing, $tmpnextBill, $proNextBill) = $this->processProrating($workingItem['productId'], $tempAddon['recurringprice_cyle'], $tempAddon['item_price'] * $tempAddon['addonQuantity']);

                        if ($proRatedPrice > 0) {
                            $workingItem['isProRated'] = true;

                            if ($cartItems['couponCodes'][$key['couponCode']]['type'] == 1) {
                                $workingItem['appliedCouponAmount'] -= $workingItem['appliedCouponAmountToAddons'];
                                $couponAmount -= $workingItem['appliedCouponAmountToAddons'];

                                $workingItem['appliedCouponAmountToAddons'] = ($itemPrice + $proRatedPrice) * $cartItems['couponCodes'][$key['couponCode']]['discount'];
                                $workingItem['appliedCouponAmount'] += $workingItem['appliedCouponAmountToAddons'];
                                $couponAmount += $workingItem['appliedCouponAmountToAddons'];
                            }

                            if ($realProNextBill !== 0) {
                                if ($proNextBill !== 0) {
                                    $realProNextBill = min($realProNextBill, $proNextBill);
                                }
                            } else {
                                $realProNextBill = $proNextBill;
                            }
                        }

                        $applyDiscount = sprintf("%01.2f", round($couponAmount, 2));

                        // Lets discount as much as we can from the $couponAmount
                        $workingItem['finalPrice'] += max($itemPrice + $proRatedPrice - $applyDiscount, 0);
                        $workingItem['tempTruePrice'] += $itemPrice + $proRatedPrice;

                        if ($applyDiscount > 0) {
                            $couponAmount = $couponAmount - $itemPrice - $proRatedPrice;
                        }

                        if ($couponAmount < 0) {
                            $couponAmount = 0;
                        }
                    }
                }

                if ($workingItem['isProRated']) {
                    $viewAttributes['isProRating'] = true;
                    $viewAttributes['proRatingTo'] = date('jS', $realProNextBill);
                    $viewAttributes['proRateDate'] = date($this->settings->get('Date Format'), $realProNextBill);

                    $workingItem['proRateDate'] = $viewAttributes['proRateDate'];
                }
                // *** Ending prorating code ***

                $trueTotal += ((isset($workingItem['tempTrueSetup']) && $workingItem['tempTrueSetup'])? $workingItem['tempTrueSetup'] : 0);
                $trueTotal += ((isset($workingItem['tempTruePrice']) && $workingItem['tempTruePrice'])? $workingItem['tempTruePrice'] : 0);

                // Loop the addons and add the recurring amounts
                if (isset($workingItem['addons']) && is_array($workingItem['addons'])) {
                    foreach ($workingItem['addons'] as $tempAddon) {
                        // Do we have a coupon adjusted price?
                        if (isset($tempAddon['adjustedPrice']) && $tempAddon['adjustedPrice']) {
                            $tempAddonPrice = $tempAddon['adjustedPrice'];
                        } else {
                            $tempAddonPrice = $tempAddon['item_price'];
                        }

                        // Add the recurring amount. This number will always be true after coupons
                        $this->recurringAmount($tempAddon['recurringprice_cyle'], $tempAddonPrice);
                    }
                }

                // Now we handle any / all deductions and format the pricing
                if ($affectsPrice == true) {
                    $workingItem['totalFormatted'] = $this->currencys->format($this->currency, ((isset($workingItem['tempTruePrice']))? $workingItem['tempTruePrice'] : 0), true, "NONE", false, true);
                    $workingItem['newTotal'] = $this->currencys->format($this->currency, ((isset($workingItem['finalPrice']))? $workingItem['finalPrice'] : 0), true, "NONE", false, true);
                } else {
                    $workingItem['totalFormatted'] = $this->currencys->format($this->currency, $workingItem['finalPrice'], true, "NONE", false, true);
                }

                if ((isset($workingItem['trueSetup']) && !in_array($workingItem['trueSetup'], array (0, '', '0', '0.00'))) || (isset($workingItem['finalSetup']) && !in_array($workingItem['finalSetup'], array (0, '', '0', '0.00')))) {
                    if ($affectsSetup == true) {
                        $workingItem['setupFormatted'] = $this->currencys->format($this->currency, ((isset($workingItem['tempTrueSetup']) && $workingItem['tempTrueSetup'])? $workingItem['tempTrueSetup'] : 0), true, "NONE", false, true);
                        $workingItem['newSetup'] = $this->currencys->format($this->currency, $workingItem['finalSetup'], true, "NONE", false, true);
                    } else {
                        $workingItem['setupFormatted'] = $this->currencys->format($this->currency, $workingItem['finalSetup'], true, "NONE", false, true);
                    }
                }

                // Handle the running total & recurring prices
                if (isset($cartItems['couponCodes'][$key['couponCode']]['recurring']) && $cartItems['couponCodes'][$key['couponCode']]['recurring'] == 1) {
                    // Add subtotal
                    $this->runningTotal($workingItem['finalPrice'], ((isset($workingItem['finalSetup']))? $workingItem['finalSetup'] : 0));

                    // Add recurring item
                    if (isset($workingItem['groupType']) && $workingItem['groupType'] == 3 && isset($workingItem['isDomain']) && $workingItem['isDomain'] == 1) {
                        $this->recurringAmount(((isset($workingItem['trueTerm']))? $workingItem['trueTerm'] : 0), ((isset($workingItem['renewTotalPrice']))? $workingItem['renewTotalPrice'] : 0));
                    } else {
                        $this->recurringAmount(((isset($workingItem['trueTerm']))? $workingItem['trueTerm'] : 0), ((isset($workingItem['totalPrice']))? $workingItem['totalPrice'] : 0));
                    }
                } else {
                    // Add subtotal
                    $this->runningTotal($workingItem['finalPrice'], ((isset($workingItem['finalSetup']))? $workingItem['finalSetup'] : 0));

                    // Add Recurring item
                    if (isset($workingItem['groupType']) && $workingItem['groupType'] == 3 && isset($workingItem['isDomain']) && $workingItem['isDomain'] == 1) {
                        $this->recurringAmount(((isset($workingItem['trueTerm']))? $workingItem['trueTerm'] : 0), ((isset($workingItem['renewPrice']))? $workingItem['renewPrice'] : 0));
                    } else {
                        $this->recurringAmount(((isset($workingItem['trueTerm']))? $workingItem['trueTerm'] : 0), ((isset($workingItem['truePrice']))? $workingItem['truePrice'] : 0));
                    }
                }

                // Work out what domain to display
                //CE_Lib::debug($productGroupInfo);

                $workingItem['isBundle'] = false;
                $workingItem['bundledBy'] = (isset($key['bundledBy']))? $key['bundledBy'] : false;

                if (isset($key['isBundle']) && is_array($key['isBundle']) && count($key['isBundle']) > 0) {
                    $workingItem['isBundle'] = $key['isBundle'];

                    // Hide the delete button of the associated items
                    foreach ($key['isBundle'] as $bundledItemId) {
                        $hideOptions[$bundledItemId]['delete'] = true;
                    }
                }

                // Check if we have an associated domain
                if (isset($key['bundledDomain'])&& $key['bundledDomain'] && isset($cartItems[$key['cartItemId']]['bundledDomain']) && $cartItems[$key['cartItemId']]['bundledDomain']) {
                    $workingItem['associatedDomain'] = $key['bundledDomain'];

                    //This information will be used to know if the domain should be free or not
                    $freeDomainOptions[$key['bundledDomain']] = array(
                    'freedomain' => $package->getCurrencyFreeDomainInfo($this->currency, $key['params']['term'], 'freedomain'),
                    'domainextension' => $package->getCurrencyFreeDomainInfo($this->currency, $key['params']['term'], 'domainextension'),
                    'domaincycle' => $package->getCurrencyFreeDomainInfo($this->currency, $key['params']['term'], 'domaincycle')
                    );

                    if ($workingItem['titleText'] != '') {
                        $workingItem['bundledDomainId'] = $key['bundledDomain'];
                    }
                } elseif (isset($key['bundledDomain']) && !is_numeric($key['bundledDomain'])) {
                    // Domain is probably self managed so add it here.
                    $workingItem['associatedDomain'] = $key['bundledDomain'];
                } else {
                    // Query to get the ID of the DOmain Name custom field
                    $query = "SELECT id FROM customField WHERE name = ? AND groupId = 2 AND subGroupId = 1 ORDER BY id ASC LIMIT 1";
                    $result = $this->db->query($query, 'Domain Name');
                    $domainCustomFieldId = $result->fetch();

                    if (isset($key['params']['customFields'][$domainCustomFieldId['id']]) && $key['params']['customFields'][$domainCustomFieldId['id']]) {
                        $workingItem['associatedDomain'] = $key['params']['customFields'][$domainCustomFieldId['id']];
                    } else {
                        $workingItem['associatedDomain'] = false;
                    }
                }

                if (in_array($workingItem['groupType'], array(PACKAGE_TYPE_HOSTING, PACKAGE_TYPE_SSL))) {
                    if (!isset($workingItem['associatedDomain']) || $workingItem['associatedDomain'] === false || $workingItem['associatedDomain'] === '') {
                        // Get domain name from the absolute parent
                        if (isset($key['bundledBy']) && $key['bundledBy'] && isset($cartItems[$key['bundledBy']])) {
                            if (isset($cartItems[$key['bundledBy']]['bundledDomain']) && $cartItems[$key['bundledBy']]['bundledDomain'] !== false) {
                                $workingItem['associatedDomain'] = $cartItems[$key['bundledBy']]['bundledDomain'];
                            }
                        }
                    }
                } else {
                    $workingItem['associatedDomain'] = false;
                }

                // Add the working item to the final array
                $finalCart[] = $workingItem;

                // Increment $i
                ++$i;
            }

            // Update the running total
            $runningTotal = ((isset($this->runningTotal['total']))? $this->runningTotal['total'] : 0);

            // Calculate if we need a 'then' section for recurring items
            if (isset($this->runningTotal['recurringItem']) && is_array($this->runningTotal['recurringItem'])) {
                // Create the array
                $recurringItems = array();

                // Loop hosting
                if (isset($this->runningTotal['recurringItem'][1]) && is_array($this->runningTotal['recurringItem'][1])) {
                    foreach ($this->runningTotal['recurringItem'][1] as $key => $value) {
                        // Format the hosting
                        if ($value != 0) {
                            $recurringItems[] = array(
                            'period' => $key,
                            'text'   => $billingcycles[$key]['name'],
                            'amount' => $this->currencys->format($this->currency, $value, true, "NONE", false, true)
                            );
                        }
                    }
                }

                // Loop the array
                if (isset($this->runningTotal['recurringItem'][3]) && is_array($this->runningTotal['recurringItem'][3])) {
                    foreach ($this->runningTotal['recurringItem'][3] as $key => $value) {
                        // Format the Domains
                        if ($value != 0) {
                            $recurringItems[] = array(
                            'period' => $key,
                            'text'   => $billingcycles[$key]['name'],
                            'amount' => $this->currencys->format($this->currency, $value, true, "NONE", false, true)
                            );
                        }
                    }
                }
            } else {
                $recurringItems = false;
            }

            // Return the cart
            $retArray = array(
            'cartTotal' => array(
                'price'                       => $this->currencys->format($this->currency, $runningTotal, true, "NONE", false, true),
                'recurringItems'              => $recurringItems,
                'truePrice'                   => $runningTotal,
                'totalBeforeCoupons'          => $trueTotal,
                'totalBeforeCouponsFormatted' => $this->currencys->format($this->currency, $trueTotal, true, "NONE", false, true)
            ),
            'cartItems' => $finalCart,
            'cartCount' => count($finalCart)
            );
        } else {
            // Something went wrong so empty the cart just to be sure
            $this->destroyCart();
            $viewAttributes['showTimer'] = false;
            // Return nothing
            $retArray = array(
            'cartTotal' => array(
                'price'                       => $this->currencys->format($this->currency, 0, true, "NONE", false, true),
                'recurringItems'              => false,
                'truePrice'                   => 0,
                'totalBeforeCoupons'          => 0,
                'totalBeforeCouponsFormatted' => $this->currencys->format($this->currency, 0, true, "NONE", false, true)
            ),
            'cartItems' => '',
            'cartCount' => 0
            );
        }

        //when do we show stock timer if we have a product that is
        //controlled by stock and we have stock timer above 0 minutes
        $stockTimer = (int)$this->settings->get('Stock Timer');
        if ((count($packagesWithStockEnabled)>0) && ($retArray['cartCount'] > 0) && ($stockTimer > 0)) {
            $viewAttributes['showTimer'] = true;
            $viewAttributes['timerMinutes'] = $stockTimer;
            foreach ($packagesWithStockEnabled as $packageWithStockEnabled) {
                $activeOrder = new ActiveOrder($packageWithStockEnabled);
                $activeOrder->setExpires($stockTimer+1);
            }
        }

        return array_merge($retArray, $viewAttributes);
    }


    /*
     * Function to process the cart tax
     * Can be called from step 4 or by ajax so we don't do ANY session handling
     */
    function processCartTax(&$cartItems, $country, $state, $vatNumber, $isTaxable, $userID)
    {
        include_once 'modules/billing/models/TaxGateway.php';
        $taxGateway = new TaxGateway($this->user);

        // Set some variables
        $validVat = false;
        $returnArray = array();
        $couponDiscount = 0;

        // Check if the VAT number is valid
        if (isset($vatNumber) && $vatNumber != '') {
            $vatResponse = $taxGateway->ValidateVAT($country, $vatNumber, $userID);
            if ($vatResponse[0] == 1) {
                $validVat = true;
            }
        }

        // Get the country
        if ($country == null) {
            $country = $this->settings->get('Default Country');
        }

        // Get the tax information
        include_once 'modules/billing/models/BillingGateway.php';
        $billingGateway = new BillingGateway($this->user);
        $taxes = $billingGateway->getTaxes($country, $state);
        $returnArray['requestVAT'] = ($taxes['isEUcountry'])? 1: 0;
        $returnArray['vatResponse'] = ''.$vatResponse[0].'';
        $taxes['allPreTaxCoupon'] = 0;
        $taxes['allPostTaxCoupon'] = 0;
        $taxes['taxableAmount'] = 0;
        $taxes['taxableitems'] = 0;

        // Loop the cart items
        if (isset($cartItems['cartItems']) && is_array($cartItems['cartItems'])) {
            foreach ($cartItems['cartItems'] as $item) {
                // Process prorating
                list($itemPrice, $proRatedPrice, $proratedDays, $proIncFollowing, $tmpnextBill, $proNextBill) = $this->processProrating($item['productId'], $item['trueTerm'], $item['truePrice']);

                $lineTotal = $itemPrice + $proRatedPrice;
                $lineTotal += ((isset($item['trueSetup']) && $item['trueSetup'])? $item['trueSetup'] : 0);

                if (isset($item['addons']) && is_array($item['addons'])) {
                    foreach ($item['addons'] as $tempAddon) {
                        list($itemPrice, $proRatedPrice, $proratedDays, $proIncFollowing, $tmpnextBill, $proNextBill) = $this->processProrating($item['productId'], $tempAddon['recurringprice_cyle'], $tempAddon['item_price'] * $tempAddon['addonQuantity']);

                        $lineTotal += $itemPrice + $proRatedPrice;
                        $lineTotal += $tempAddon['item_setup'] * $tempAddon['addonQuantity'];
                    }
                }

                // Is this product taxable?
                if ($item['taxable']) {
                    // Store the TOTAL for this ordered product
                    $taxes['taxableitems']++;
                    $taxes['taxableAmount'] += $lineTotal;
                }

                // Handle the coupon Discount
                if (isset($item['hasCoupon']) && $item['hasCoupon']) {
                    if (isset($item['appliedCouponTaxable']) && $item['appliedCouponTaxable'] && $item['taxable']) {
                        $taxes['allPreTaxCoupon'] += ((isset($item['appliedCouponAmount']))? sprintf("%01.2f", round($item['appliedCouponAmount'], 2)) : 0);
                    } else {
                        $taxes['allPostTaxCoupon'] += ((isset($item['appliedCouponAmount']))? sprintf("%01.2f", round($item['appliedCouponAmount'], 2)) : 0);
                    }
                    $couponDiscount += ((isset($item['appliedCouponAmount']))? sprintf("%01.2f", round($item['appliedCouponAmount'], 2)) : 0);
                }
            }
        }

        // Process the tax amount
        if ($isTaxable && $taxes['taxableitems'] > 0 && ($taxes['taxrate'] > 0 || $taxes['tax2rate'] > 0)) {
            // Store the taxable rates
            $taxrate = $taxes['taxrate'];
            $tax2rate = $taxes['tax2rate'];
            if ($validVat) {
                if ($taxes['vat']) {
                    $taxrate = 0;
                }
                if ($taxes['vat2']) {
                    $tax2rate = 0;
                }
            }

            // Setup the array
            $returnArray['taxRequired'] = true;
            $returnArray['subTotal'] = $cartItems['cartTotal']['totalBeforeCouponsFormatted']; // Saving the TOTAL cart amount before deductions

            // Calculate the total amount availble to be taxed
            // This number is the taxableAmount MINUS and preTaxCoupons
            $taxes['totalTaxableAmount'] = ((isset($taxes['taxableAmount']))? $taxes['taxableAmount'] : 0) - ((isset($taxes['allPreTaxCoupon']))? $taxes['allPreTaxCoupon'] : 0);

            // Apply the taxes.
            // We also need to tax the postTaxCoupon amount.
            if ($taxes['taxrate'] > 0) {
                $returnArray['taxName'] = $taxes['taxname'] . " (" . (float)$taxes['taxrate'] . "%)";
                $cartItems['cartTotal']['taxAmount'] = ($taxes['totalTaxableAmount'] * $taxrate / 100);

                // Fix for when tax is calculated as below 0
                if ($cartItems['cartTotal']['taxAmount'] < 0) {
                    $cartItems['cartTotal']['taxAmount'] = 0;
                }
                $returnArray['taxAmount'] = $this->currencys->format($this->currency, $cartItems['cartTotal']['taxAmount'], true, "NONE", false, true);
            }
            if ($taxes['tax2rate'] > 0) {
                $returnArray['tax2Name'] = $taxes['tax2name'] . " (" . (float)$taxes['tax2rate'] . "%)";

                if ($taxes['tax2compound']) {
                    $cartItems['cartTotal']['subtotalTaxable2'] = $taxes['totalTaxableAmount'] + ($taxes['totalTaxableAmount'] * $taxrate / 100);
                } else {
                    $cartItems['cartTotal']['subtotalTaxable2'] = $taxes['totalTaxableAmount'];
                }

                $cartItems['cartTotal']['tax2Amount'] = ($cartItems['cartTotal']['subtotalTaxable2'] * $tax2rate / 100);

                // Fix for if tax2 is calculated as a negative number
                if ($cartItems['cartTotal']['tax2Amount'] < 0) {
                    $cartItems['cartTotal']['tax2Amount'] = 0;
                }

                $returnArray['tax2Amount'] = $this->currencys->format($this->currency, $cartItems['cartTotal']['tax2Amount'], true, "NONE", false, true);
            }
            $returnArray['taxableitems'] = $taxes['taxableitems'];
            // Get the total price with taxes and pre-tax coupons.

            $cartItems['cartTotal']['truePriceWithTaxes'] = (($cartItems['cartTotal']['totalBeforeCoupons'] - ((isset($taxes['allPreTaxCoupon']))? $taxes['allPreTaxCoupon'] : 0)) + ((isset($cartItems['cartTotal']['taxAmount']))? $cartItems['cartTotal']['taxAmount'] : 0) + ((isset($cartItems['cartTotal']['tax2Amount']))? $cartItems['cartTotal']['tax2Amount'] : 0));

            // Subtract the post tax coupon amount
            $cartItems['cartTotal']['truePriceWithTaxes'] = $cartItems['cartTotal']['truePriceWithTaxes'] - ((isset($taxes['allPostTaxCoupon']))? $taxes['allPostTaxCoupon'] : 0);

            // Format it and save the total to pay.
            $cartItems['cartTotal']['priceWithTaxes'] = $this->currencys->format($this->currency, max($cartItems['cartTotal']['truePriceWithTaxes'], 0), true, "NONE", false, true);
            $returnArray['totalPay_raw'] = max($cartItems['cartTotal']['truePriceWithTaxes'], 0);
            $returnArray['totalPay'] = $cartItems['cartTotal']['priceWithTaxes'];
        } else {
            // Just set some basic totals
            $returnArray['taxableitems'] = 0;
            $returnArray['subTotal'] = $cartItems['cartTotal']['totalBeforeCouponsFormatted'];
            $returnArray['totalPay_raw'] = max($cartItems['cartTotal']['totalBeforeCoupons'] - ((isset($couponDiscount))? $couponDiscount : 0), 0);
            $returnArray['totalPay'] = $this->currencys->format($this->currency, max($cartItems['cartTotal']['totalBeforeCoupons'] - ((isset($couponDiscount))? $couponDiscount : 0), 0), true, "NONE", false, true);
        }

        // Format coupon amounts
        if ($couponDiscount > 0) {
            $returnArray['couponDiscount'] = $this->currencys->format($this->currency, $couponDiscount, true, "NONE", false, true);
        }

        return $returnArray;
    }

    /*
     * Function to handle the recurring amounts
     *
     */
    function recurringAmount($term, $recurringAmount, $type = 1)
    {
        if ($term != 0) {
            if (isset($this->runningTotal['recurringItem'][$type][$term])) {
                $this->runningTotal['recurringItem'][$type][$term] += $recurringAmount;
            } else {
                $this->runningTotal['recurringItem'][$type][$term] = $recurringAmount;
            }
        }
    }

    /*
     * Function to remove products not completely configured from the cart
     */
    function removeInvalidItemsFromCart()
    {
        $itemsToRemove = array();
        //we should loop to see which cart items have stock enabled
        //so that we can readd the stock properly (so lets remove from cart properly)
        if (isset($this->session->cartContents)) {
            $cartItems = unserialize(base64_decode($this->session->cartContents));
        } else {
            $cartItems = array();
        }

        if (is_array($cartItems)) {
            foreach ($cartItems as $item) {
                if (isset($item['cartItemId'])) {
                    if (!isset($item['onCart']) || !$item['onCart'] || (isset($item['bundledDomain']) && isset($cartItems[$item['bundledDomain']]))) {
                        //The product was not completely configured
                        $itemsToRemove[$item['cartItemId']] = $item['cartItemId'];

                        //The parent product was not completely configured. Remove the parent and it will automatically remove all his childs
                        if (isset($item['bundledBy']) && $item['bundledBy'] && isset($cartItems[$item['bundledBy']])) {
                            $itemsToRemove[$item['bundledBy']] = $item['bundledBy'];
                        }
                    }
                }
            }
        }

        foreach ($itemsToRemove as $itemToRemove) {
            $this->removeFromCart($itemToRemove);
        }
    }

    /*
     * Function to handle the subtotal calculation. We add the calculated line total
     * $term - Payment term of the line
     */
    function runningTotal($lineAmount, $setup = null)
    {
        // Add the total
        if (isset($this->runningTotal['total'])) {
            $this->runningTotal['total'] += $lineAmount + ((isset($setup))? $setup : 0);
        } else {
            $this->runningTotal['total'] = $lineAmount + ((isset($setup))? $setup : 0);
        }
    }

    function yourinfo_gateway_info($pluginsArray)
    {
        $retArray = array();
        $retArray['paymentmethods'] = array();
        $retArray['creditbalance'] = 0;
        $retArray['hidePaymentMethods'] = $this->settings->get('Hide Payment Methods');

        if ($this->user->hasPermission('billing_apply_account_credit') && $this->user->getCreditBalance() > 0) {
            $currency = new Currency($this->user);
            $retArray['formattedCreditBalance'] = $currency->format($this->user->getCurrency(), $this->user->getCreditBalance(), true);
            $retArray['creditbalance'] = $this->user->getCreditBalance();
        }

        foreach ($pluginsArray as $value => $plugin) {
            // Start the array
            $paymentmethod = array();
            $paymentmethod['iscreditcard'] = false;
            $paymentmethod['weaccept'] = array("options" => array());

            //we have to make sure it isn't grabbing from logged in Admin
            if ($plugin->getVariable("In Signup") || (($this->user->getPaymentType() == $plugin->getInternalName()) && $this->user->getPaymentType() != "0" && !$this->user->IsAdmin())) {
                // Set some variables
                $paymentmethod['paymentTypeOptionValue'] = $plugin->getInternalName();
                $paymentmethod['paymentTypeOptionLabel'] = $plugin->getVariable("Signup Name"). '&nbsp;&nbsp;&nbsp;';
                $paymentmethod['description'] = "";

                if (isset($_REQUEST['paymentMethod'])) {
                    // Select the plugin if it was already selected
                    if ($_REQUEST['paymentMethod'] == $plugin->getInternalName()) {
                        $paymentmethod['paymentTypeOptionSelected'] = true;
                        $retArray['defaultGateway'] = $plugin->getInternalName();
                        //$this->tplView->defaultGateway = $plugin->getInternalName();
                    } else {
                        $paymentmethod['paymentTypeOptionSelected'] = false;
                    }
                } else {
                    // Select the plugin if it's default
                    if ($this->settings->get('Default Gateway') == $plugin->getInternalName()) {
                        $paymentmethod['paymentTypeOptionSelected'] = true;
                        $retArray['defaultGateway'] = $plugin->getInternalName();
                    } else {
                        $paymentmethod['paymentTypeOptionSelected'] = false;
                    }
                }

                if ($this->settings->get("plugin_".$plugin->getInternalName()."_Auto Payment") == 1) {
                    $paymentmethod['autoPayment'] = 1;
                } else {
                    $paymentmethod['autoPayment'] = 0;
                }

                // Handle credit card plugins
                if ($this->settings->get("plugin_".$plugin->getInternalName()."_Accept CC Number") == 1) {
                    // Start the fields
                    $paymentmethod['extraFields'] = array();
                    $paymentmethod['iscreditcard'] = true;

                    // Handle the we accept list
                    $weAccept = array();
                    if (!is_null($this->settings->get("plugin_".$plugin->getInternalName()."_Visa")) && $this->settings->get("plugin_".$plugin->getInternalName()."_Visa") == 1) {
                        $weAccept[] = array(
                        'id'  => 'visa_logo',
                        'alt' => 'Visa',
                        'img' => 'images/creditcards/visa.gif'
                        );
                    }
                    if (!is_null($this->settings->get("plugin_".$plugin->getInternalName()."_MasterCard")) && $this->settings->get("plugin_".$plugin->getInternalName()."_MasterCard") == 1) {
                        $weAccept[] = array(
                        'id'  => 'mastercard_logo',
                        'alt' => 'MasterCard',
                        'img' => 'images/creditcards/mc.gif'
                        );
                    }
                    if (!is_null($this->settings->get("plugin_".$plugin->getInternalName()."_AmericanExpress")) && $this->settings->get("plugin_".$plugin->getInternalName()."_AmericanExpress") == 1) {
                        $weAccept[] = array(
                        'id'  => 'americanexpress_logo',
                        'alt' => 'American Express',
                        'img' => 'images/creditcards/amex1.gif'
                        );
                    }
                    if (!is_null($this->settings->get("plugin_".$plugin->getInternalName()."_Discover")) && $this->settings->get("plugin_".$plugin->getInternalName()."_Discover") == 1) {
                        $weAccept[] = array(
                        'id'  => 'discover_logo',
                        'alt' => 'Discover',
                        'img' => 'images/creditcards/discover.gif'
                        );
                    }
                    if (!is_null($this->settings->get("plugin_".$plugin->getInternalName()."_LaserCard")) && $this->settings->get("plugin_".$plugin->getInternalName()."_LaserCard") == 1) {
                        $weAccept[] = array(
                        'id'  => 'lasercard_logo',
                        'alt' => 'LaserCard',
                        'img' => 'images/creditcards/laser.gif'
                        );
                    }
                    if (!is_null($this->settings->get("plugin_".$plugin->getInternalName()."_DinersClub")) && $this->settings->get("plugin_".$plugin->getInternalName()."_DinersClub") == 1) {
                        $weAccept[] = array(
                        'id'  => 'dinersclub_logo',
                        'alt' => 'DinersClub',
                        'img' => 'images/creditcards/diners.gif'
                        );
                    }
                    if (!is_null($this->settings->get("plugin_".$plugin->getInternalName()."_Switch")) && $this->settings->get("plugin_".$plugin->getInternalName()."_Switch") == 1) {
                        $weAccept[] = array(
                        'id'  => 'switch_logo',
                        'alt' => 'Switch',
                        'img' => 'images/creditcards/switch.gif'
                        );
                    }

                    $paymentmethod['weaccept'] = array(
                    'fieldType'  => 'weaccept',
                    'fieldName'  => 'ccWeAccept',
                    'fieldTitle' => $this->user->lang('We Accept'),
                    'options'    => $weAccept
                    );

                    $paymentmethod['extraFields'][] = array(
                    'fieldType'  => 'grouplabel',
                    'fieldName'  => $this->user->lang("Card Information"),
                    'fieldTitle' => $this->user->lang("Card Information")
                    );

                    // Card Number
                    $paymentmethod['extraFields'][] = array(
                    'fieldType'  => 'text',
                    'fieldName'  => $plugin->getInternalName().'_ccNumber',
                    'labelpos'   => "low",
                    'fieldTitle' => $this->user->lang('Credit Card Number'),
                    'fieldSize'  => '207',
                    'fieldLuhn'  => true
                    );

                    // Expiration month
                    $paymentmethod['extraFields'][] = array(
                    'fieldType'  => 'dropdown',
                    'fieldName'  => $plugin->getInternalName().'_ccMonth',
                    'labelpos'   => "low",
                    'fieldTitle' => $this->user->lang('Expiration Month'),
                    'fieldValue' => array(
                        array(
                            'value' => 1,
                            'text'  => "01 - ".$this->user->lang("January")
                        ),
                        array(
                            'value' => 2,
                            'text'  => "02 - ".$this->user->lang("February")
                        ),
                        array(
                            'value' => 3,
                            'text'  => "03 - ".$this->user->lang("March")
                        ),
                        array(
                            'value' => 4,
                            'text'  => "04 - ".$this->user->lang("April")
                        ),
                        array(
                            'value' => 5,
                            'text'  => "05 - ".$this->user->lang("May")
                        ),
                        array(
                            'value' => 6,
                            'text'  => "06 - ".$this->user->lang("June")
                        ),
                        array(
                            'value' => 7,
                            'text'  => "07 - ".$this->user->lang("July")
                        ),
                        array(
                            'value' => 8,
                            'text'  => "08 - ".$this->user->lang("August")
                        ),
                        array(
                            'value' => 9,
                            'text'  => "09 - ".$this->user->lang("September")
                        ),
                        array(
                            'value' => 10,
                            'text'  => "10 - ".$this->user->lang("October")
                        ),
                        array(
                            'value' => 11,
                            'text'  => "11 - ".$this->user->lang("November")
                        ),
                        array(
                            'value' => 12,
                            'text'  => "12 - ".$this->user->lang("December")
                        )
                    )
                    );

                    // Expiration Year
                    $yearValues = array();
                    $currentYear = date("Y");
                    for ($i = 0; $i <= 15; $i++) {
                        $yearValues[] = array(
                        'value' => $currentYear,
                        'text'  => $currentYear
                        );
                        $currentYear++;
                    }
                    $paymentmethod['extraFields'][] = array(
                    'fieldType'  => 'dropdown',
                    'fieldName'  => $plugin->getInternalName().'_ccYear',
                    'labelpos'   => "low",
                    'fieldTitle' => $this->user->lang('Expiration Year'),
                    'fieldValue' => $yearValues
                    );

                    // Handle CVV2 - Check if we're allowed to ask for it
                    if ($this->settings->get('plugin_'.$plugin->getInternalName().'_Check CVV2') && $this->settings->get('Forward To Gateway')) {
                        $paymentmethod['extraFields'][] = array(
                        'fieldType'        => 'text',
                        'fieldName'        => $plugin->getInternalName().'_ccCVV2',
                        'labelpos'         => "low",
                        'fieldTitle'       => $this->user->lang('CVV2'),
                        'fieldSize'        => '40',
                        'fieldDescription' => $this->user->lang("<b>Visa/MasterCard/Discover:</b><br />The CVV2 number is the 3 digit value printed on the back of the card.  It follows the credit card number in the signature area of the card.")."<br/><br/>".$this->user->lang("<b>American Express:</b><br />The CVV2 number is the 4 digit value printed on the front of the card above the credit card number.")
                        );
                    }
                }
                $retArray['paymentmethods'][] = $paymentmethod;
            }
        }
        return $retArray;
    }

    /*
     * Function to destroy the invoice information
     */
    function destroyInvoiceInformation()
    {
        //echo("Destroying Invoice Information");

        // Destroy the invoice information
        $this->session->invoice_information = null;
        unset($this->session->invoice_information);
    }

    /*
     * Function to destroy the currency
     */
    function destroyCurrency()
    {
        // Destroy the currency
        $this->session->currency = null;
        unset($this->session->currency);
    }

    /*
     * Function to destroy the couponCode
     */
    function destroyCouponCode()
    {
        // Destroy the couponCode
        $this->session->couponCode = null;
        unset($this->session->couponCode);
    }

    /*
    * Function that removes any active_orders entries for these cart items
    * since the order has been completed there isn't an active order any longer.
    * This prevents the expires datetime to be accidently reached by another function
    * and readding the stock items for a product that was purchased
    *
    */
    function orderCompleted()
    {
        $sqlItems = array();

        if (isset($this->session->cartContents)) {
            $cartItems = unserialize(base64_decode($this->session->cartContents));
        } else {
            $cartItems = array();
        }


        if (is_array($cartItems)) {
            foreach ($cartItems as $item) {
                if (isset($item['cartItemId'])) {
                    $sqlItems[] = "'".$item['cartItemId']."'";
                }
            }
        }

        if (count($sqlItems) > 0) {
            $items = implode(",", $sqlItems);
            $sql = "delete from active_orders where id IN($items)";
            $this->db->query($sql);
        }

        $this->destroyCart();
    }

    function process_new_order()
    {
        require_once 'library/CE/CurlPostCE.php';

        $currencys = new Currency($this->user);
        $signupExistingUser = false;

        // Start logging
        CE_Lib::log(2, "******* NEW SIGNUP IS STARTING *******");
        CE_Lib::log(2, "Signup: STARTING PROCESSING");

        // Do we have an existing logged in user?
        if ($this->user->getEmail() != '' && !$this->user->isAdmin() && $this->user->isRegistered()) {
            $signupExistingUser = true;
            CE_Lib::log(2, "Signup: Existing logged in user found. Email: '".$this->user->getEmail()."'. User ID:'".$this->user->getId()."'");
        }

        if (!$signupExistingUser) {
            CE_Lib::log(2, "Signup: New user is signing up.");
        }

        $plugininternalname = filter_var($_REQUEST['paymentMethod'], FILTER_SANITIZE_STRING);
        if ($plugininternalname == 'apply_my_credit') {
            //When paying the invoice with account credit
            $plugin_form = '';
        } else {
            include_once "modules/admin/models/PluginGateway.php";
            $plugin_gateway = new PluginGateway($this->user);
            $plugin = $plugin_gateway->getPluginByName("gateways", $plugininternalname);
            $plugin_form = $plugin->getVariable("Form");
        }

        //Only validate the captcha if it is enabled, it is a new customer and the payment plugin selected does not displays a popup window
        //Payment plugins that displays a popup window can try to process the payment without already validating the captcha, so to avoid issues we will ignore the captcha in those cases
        $captchaPlugin = $this->settings->get('Enabled Captcha Plugin');
        if ($this->settings->get('Show Captcha on Signup Page') == 1 && $captchaPlugin != '' && $captchaPlugin != 'disabled' && !$signupExistingUser) {
            $pluginGateway = new PluginGateway($this->user);
            $plugin = $pluginGateway->getPluginByName('captcha', $captchaPlugin);
            if (!$plugin->verify($_REQUEST)) {
                CE_Lib::log(2, "Signup: Invalid Captcha Response");
                CE_Lib::addErrorMessage($this->user->lang('Failed Captcha'));
                CE_Lib::redirectPage("order.php?step=3");
            }
        }

        /*
         * WE NEED TO HANDLE FIELD VALIDATION HERE AND SEND USERS BACK IF WE HAVE ISSUES
         * TODO: FIELD VALIDATION
         *
         * Email Duplicates
         * Password Strength policy (try and move to jQuery)
         * Credit Card Number (if required)
         *
         */

        // Validate Credit Card Number
        if (isset($_REQUEST['payment_information_display']) && $_REQUEST['payment_information_display'] && !$signupExistingUser
          && isset($_REQUEST['paymentMethod']) && isset($_REQUEST[$_REQUEST['paymentMethod'].'_ccNumber'])) {
            $cards = array();
            if (!is_null($this->settings->get("plugin_".$_REQUEST['paymentMethod']."_Visa")) && $this->settings->get("plugin_".$_REQUEST['paymentMethod']."_Visa") == 1) {
                $cards['VISA'] = 'Visa';
            }
            if (!is_null($this->settings->get("plugin_".$_REQUEST['paymentMethod']."_MasterCard")) && $this->settings->get("plugin_".$_REQUEST['paymentMethod']."_MasterCard") == 1) {
                $cards['MC'] = 'MasterCard';
            }
            if (!is_null($this->settings->get("plugin_".$_REQUEST['paymentMethod']."_AmericanExpress")) && $this->settings->get("plugin_".$_REQUEST['paymentMethod']."_AmericanExpress") == 1) {
                $cards['AMEX'] = 'American Express';
            }
            if (!is_null($this->settings->get("plugin_".$_REQUEST['paymentMethod']."_Discover")) && $this->settings->get("plugin_".$_REQUEST['paymentMethod']."_Discover") == 1) {
                $cards['DISC'] = 'Discover';
            }
            if (!is_null($this->settings->get("plugin_".$_REQUEST['paymentMethod']."_LaserCard")) && $this->settings->get("plugin_".$_REQUEST['paymentMethod']."_LaserCard") == 1) {
                $cards['LASER'] = 'LaserCard';
            }
            if (!is_null($this->settings->get("plugin_".$_REQUEST['paymentMethod']."_DinersClub")) && $this->settings->get("plugin_".$_REQUEST['paymentMethod']."_DinersClub") == 1) {
                $cards['DINERS'] = 'Diners Club';
            }
            if (!is_null($this->settings->get("plugin_".$_REQUEST['paymentMethod']."_Switch")) && $this->settings->get("plugin_".$_REQUEST['paymentMethod']."_Switch") == 1) {
                $cards['SWITCH'] = 'Switch';
            }
            $cardtype = 'UNKNOW';
            include_once 'modules/billing/models/CreditCard.php';
            $cc = new CreditCard();
            foreach ($cards as $cardKey => $card) {
                $errornumber = '';
                $errortext   = '';
                if ($cc->checkCreditCard($_REQUEST[$_REQUEST['paymentMethod'].'_ccNumber'], $card, $errornumber, $errortext)) {
                    $cardtype = $cardKey;
                    break;
                }
            }

            if ($cardtype == 'UNKNOW') {
                $message = $this->user->lang("Invalid Credit Card Number");
                CE_Lib::log(2, "Signup: Credit Card Number failed. Reason: '$message'. Done.");

                // Fail due to Credit Card Number
                CE_Lib::addErrorMessage($message);
                CE_Lib::redirectPage("order.php?step=3");
                return;
            }

            CE_Lib::log(2, "Signup: Validated Credit Card Number Successfully.");
        }

        // Validate password
        if ($this->settings->get('Enforce Password Strength')) {
            include_once 'modules/admin/models/PasswordStrength.php';
            $passwordStrength = new PasswordStrength($this->settings, $this->user);

            // no need to check existing users passwords
            if ($this->settings->get('Enforce Password Strength') && !$signupExistingUser) {
                $passwordStrength->setPassword($_REQUEST['password']);
                if (!$passwordStrength->validate()) {
                    foreach ($passwordStrength->getMessages() as $message) {
                        CE_Lib::log(2, "Signup: Password strength failed. Reason: '$message'. Done.");

                        // Fail due to password
                        CE_Lib::addErrorMessage($message);
                    }
                    CE_Lib::redirectPage("order.php?step=3");
                }
                CE_Lib::log(2, "Signup: Validated Password Successfully.");
            }
        }

        // Validate email
        if (!$signupExistingUser) {
            // Before we make any transactions with the database, let's validate the email address.
            $emailId = $this->user->getCustomFieldsObj()->_getCustomFieldIdByType(typeEMAIL);

            if (isset($_REQUEST["CT_$emailId"])) {
                // Don't know why we are trying to suppress anything, but OK!
                $email = htmlspecialchars($_REQUEST["CT_$emailId"], ENT_QUOTES);
            } else {
                $email = '';
            }

            if (!CE_Lib::valid_email($email)) {
                $message = $this->user->lang("Email address provided is not valid.");
                CE_Lib::log(2, "Signup: ".$message);
                CE_Lib::addErrorMessage($message);
                CE_Lib::redirectPage("order.php?step=3");
            }

            $userGateway = new UserGateway($this->user);
            try {
                $verify = $userGateway->VerifyEmailDuplicate($email, true);
            } catch (Exception $ex) {
                CE_Lib::log(2, "Signup: New user duplicate email failed. Message '".$ex->getMessage()."'. Done.");

                // Fail due to email
                CE_Lib::addErrorMessage($ex->getMessage());
                CE_Lib::redirectPage("order.php?step=3");
            }

            if ($userGateway->SupportEmailExist($email)) {
                $gAlertMessage = $this->user->lang("The email %s can not be used because it is already in use for support by another user.", $email);
                CE_Lib::log(2, "Signup: New user duplicate email failed. Message '".$gAlertMessage."'. Done.");

                // Fail due to email
                CE_Lib::addErrorMessage($gAlertMessage);
                CE_Lib::redirectPage("order.php?step=3");
            }

            CE_Lib::log(2, "Signup: New user duplicate email OK.");
        }

        // Check fraud plugins
        if (!$signupExistingUser) {
            $fraudmsg = array();
            $fraudPlugins = new NE_PluginCollection("fraud", $this->user);
            while ($fraudPlugin = $fraudPlugins->getNext()) {
                if (!$this->settings->get('plugin_'.$fraudPlugin->getInternalName()."_Enabled")) {
                    continue;
                }

                CE_Lib::log(2, "Signup: Calling Fraud Plugin: ".$this->settings->get("plugin_".$fraudPlugin->getInternalName()."_Plugin Name"));

                $fraudPlugin->grabDataFromRequest($_REQUEST);
                $fraudCheckResult = $fraudPlugin->execute();
                if (!is_array($fraudCheckResult)) {
                    $fraudCheckResult = array();
                }

                $outputkeys = array_keys($fraudCheckResult);
                $numoutputkeys = count($fraudCheckResult);

                for ($i = 0; $i < $numoutputkeys; $i++) {
                    $key = $outputkeys[$i];
                    $value = $fraudCheckResult[$key];
                    if ($key == 'queriesRemaining') {
                        continue;
                    }
                    $fraudmsg[] = $key . " = " . $value;
                }

                // post the fraud score for use in the phone verification step
                $this->session->fraudScore = $fraudPlugin->getRiskScore();
                $fraudPlugin->extraSteps();

                if (!$fraudPlugin->isOrderAccepted()) {
                    $failureString = "";
                    if (is_array($fraudPlugin->getFailureMessages())) {
                        foreach ($fraudPlugin->getFailureMessages() as $key) {
                            $failureString .= $key."<br>";
                        }
                    }

                    CE_Lib::log(2, "Signup: FRAUD FAILED. Reason '$failureString'. Done.");
                    CE_Lib::addErrorMessage($failureString);
                    CE_Lib::redirectPage("order.php?step=3");
                    //$arrError = array_merge($arrError, $fraudPlugin->getFailureMessages());
                }
                if (count($fraudmsg) > 0) {
                    $this->session->fraudmsg = $fraudmsg;
                }
                CE_Lib::log(2, "Signup: Fraud Passed");
            }
        }

        // Check Phone Plugins
        if (!$signupExistingUser && (!isset($this->session->phoneCalled) || !$this->session->phoneCalled)) {
            $phoneVerificationPlugins = new NE_PluginCollection('phoneverification', $this->user);

            // Get the cart summary
            $cartSummary = $this->getCartSummary();

            // Find a plugin
            while ($phoneVerificationPlugin = $phoneVerificationPlugins->getNext(false)) {
                if ($this->settings->get("plugin_{$phoneVerificationPlugin}_Enabled") == 1
                  && $this->settings->get("plugin_{$phoneVerificationPlugin}_Minimum Bill Amount to Trigger Telephone Verification") <= ((isset($cartSummary['cartTotal']['truePrice']))? $cartSummary['cartTotal']['truePrice'] : 0)
                  && ((!isset($this->session->fraudScore) || !$this->session->fraudScore) || $this->settings->get("plugin_{$phoneVerificationPlugin}_Minimum Fraud Score to Trigger Telephone Verification") <= $this->session->fraudScore)) {
                    // Log the attempt
                    CE_Lib::log(2, "Signup: Calling Telephone Verification Fraud Plugin: ".$phoneVerificationPlugin." Invoice total is: ".((isset($cartSummary['cartTotal']['truePrice']))? $cartSummary['cartTotal']['truePrice'] : 0));
                    CE_Lib::redirectPage("order.php?step=phone-verification");
                }
            }
        } else {
            CE_Lib::log(2, "Signup: Phone verification not needed or already passed.");
        }
        CE_Lib::log(2, "Signup: All checks passed.");

        $this->create_new_account();
    }

    /**
     * Function to add an entry to the email to send to staff
     */
    private function add_staff_notification_event($event)
    {
        $this->signupEmail .= $event."<br>";
    }

    /**
     * Creates new packages during signup
     * @param  [type] $newUser [description]
     * @return [type]          [description]
     */
    public function create_new_packages($newUser)
    {
        include_once 'modules/billing/models/RecurringEntryGateway.php';
        include_once 'modules/admin/models/server.php';
        $packageAddonGateway = new PackageAddonGateway();

        $customertax = $newUser->GetTaxRate();
        //-1 is returned when we don't have a taxrate
        $customertax = ($customertax == -1) ? 0 : $customertax;
        $customertax2 = $newUser->GetTaxRate(2);
        //-1 is returned when we don't have a taxrate
        $customertax2 = ($customertax2 == -1) ? 0 : $customertax2;
        if ($newUser->isTax2Compound()) {
            $customertax2compound = 1;
        } else {
            $customertax2compound = 0;
        }

        $this->initialize_currency();

        // Get the cart items
        if (isset($this->session->cartContents)) {
            $cartItems = unserialize(base64_decode($this->session->cartContents));
        } else {
            $cartItems = array();
        }

        //No invoice for free order (Total Amount 0)
        $createInvoice = true;
        $noInvoice = $this->settings->get('No Invoice');

        if ($noInvoice) {
            $cartSummary = $this->getCartSummary();
            $countryCode = $newUser->getCountry();
            $stateCode = $newUser->getState();
            $vatNumber = $newUser->getVatNumber();
            $isTaxable = $newUser->isTaxable();
            $userID = $newUser->getId();
            $totals = $this->processCartTax($cartSummary, $countryCode, $stateCode, $vatNumber, $isTaxable, $userID);

            if ($totals['totalPay_raw'] == 0) {
                $createInvoice = false;
            }
        }
        //No invoice for free order (Total Amount 0)

        $productsToBundle = array();
        $customerId = $newUser->getId();

        $invoiceEntries = '';

        // Set an array for products with free domain options
        $freeDomainOptions = array();

        CE_Lib::log(2, "Signup: STARTING PROCESSING CART CONTENTS for customer Id:".$customerId);

        // Loop the cart
        foreach ($cartItems as $value => $key) {
            // Skip coupons
            if ($value == 'couponCodes') {
                continue;
            }

            // Get the information of the package
            $package = new Package($key['productId']);
            $package->getProductPricingAllCurrencies();

            // Get the product group information
            if (!isset($productGroupInfo[$package->planid]) || !$productGroupInfo[$package->planid]) {
                $productGroup = PackageTypeGateway::getPackageTypes($package->planid);
                $productGroupInfo[$package->planid] = $productGroup->fetch();
            }

            // Generate a full name for the product
            $productFullName = $productGroupInfo[$package->planid]->fields['name']." / ".$package->planname;
            $productDescription = '';

            // Package Event Log
            $pEventLog = array();

            $pEventLog[/*T*/'Package Type'/*/T*/] = $productGroupInfo[$package->planid]->fields['name'];
            $pEventLog[/*T*/'Package'/*/T*/] = $package->planname;

            // Add the product record manually
            $sql = "INSERT into domains(CustomerId, Plan, dateActivated) values(?, ?, NOW()) ";
            $result = $this->db->query($sql, $customerId, $key['productId']);
            $userPackageId = $result->getInsertId();
            CE_Lib::log(3, "Signup: Cart: Created OrderID: '".$userPackageId."' with CustomerID '".$customerId."'. From ProductID: '".$key['productId']."'");

            if (CE_Lib::affiliateSystem() && !empty(Cookie::get('Affiliate'))) {
                $affId = Cookie::get('Affiliate');
                $affiliateGateway = new AffiliateGateway($this->user);
                if ($affiliateGateway->doesAffiliateIdExist($affId)) {
                    $affiliateAccount = new AffiliateAccount();
                    $affiliateAccount->affiliate_id = $affId;
                    $affiliateAccount->userpackage_id = $userPackageId;
                    $affiliateAccount->date = Carbon::now()->toDateTimeString();
                    $affiliateAccount->save();
                } else {
                    Cookie::delete('Affiliate');
                }
            }

            $this->add_staff_notification_event("");
            $this->add_staff_notification_event("--------------");
            $this->add_staff_notification_event("Order Item #$userPackageId");
            $this->add_staff_notification_event("--------------");
            $this->add_staff_notification_event("Product Ordered: ".$productFullName);

            // Push the product ID back into the session so we can use it for bundling at any point
            $cartItems[$value]['savedProductId'] = $userPackageId;

            // Create the userpackage
            $userPackage = new UserPackage($userPackageId);
            $userPackage->signup = 1;
            $userPackage->loadCustomFields();

            // Work out what type of product we are handling in order to support the custom fields
            if ($productGroupInfo[$package->planid]->fields['type'] == PACKAGE_TYPE_HOSTING) {
                CE_Lib::log(3, "Signup: Cart: Product is of type Hosting.");
                $this->add_staff_notification_event("Type: Hosting");

                // We first need to FIND the ID of the username and password
                $cfUserName = $userPackage->getCustomFieldObject('User Name');
                $cfPassword = $userPackage->getCustomFieldObject('Password');
                $cfDomainName = $userPackage->getCustomFieldObject('Domain Name');

                if (isset($package->fields['advanced'])) {
                    $advancedSettings = unserialize($package->advanced);
                } else {
                    $advancedSettings = array();
                }

                //This information will be used to know if the domain should be free or not
                if (isset($key['bundledDomain']) && !is_numeric($key['bundledDomain'])) {
                    $freeDomainOptions[$key['bundledDomain']] = array(
                    'freedomain' => $package->getCurrencyFreeDomainInfo($this->currency, $key['params']['term'], 'freedomain'),
                    'domainextension' => $package->getCurrencyFreeDomainInfo($this->currency, $key['params']['term'], 'domainextension'),
                    'domaincycle' => $package->getCurrencyFreeDomainInfo($this->currency, $key['params']['term'], 'domaincycle')
                    );
                }

                // Do not try to use username/password if we are hidding hosting custom fields
                if (!isset($advancedSettings['hostingcustomfields']) || $advancedSettings['hostingcustomfields'] != 1) {
                    // Handle the default hosting fields
                    if (isset($key['bundledDomain']) && $key['bundledDomain'] && isset($cartItems[$key['bundledDomain']])) {
                        $packageDomainName = $cartItems[$key['bundledDomain']]['params']['sld'].".".$cartItems[$key['bundledDomain']]['params']['tld'];
                    } elseif (isset($key['bundledDomain']) && !is_numeric($key['bundledDomain'])) {
                         $packageDomainName =  $key['bundledDomain'];
                    } else {
                        $packageDomainName = $key['params']['customFields'][$cfDomainName['id']];
                    }

                    if (!isset($packageDomainName) || $packageDomainName === false || $packageDomainName === '') {
                        // Get domain name from the absolute parent
                        if (isset($key['bundledBy']) && $key['bundledBy'] && isset($cartItems[$key['bundledBy']])) {
                            if (isset($cartItems[$key['bundledBy']]['bundledDomain']) && $cartItems[$key['bundledBy']]['bundledDomain'] !== false) {
                                $packageDomainName = $cartItems[$key['bundledBy']]['bundledDomain'];
                            }
                        }
                    }

                    $userPackage->setCustomField('Domain Name', $packageDomainName);
                    $pEventLog[/*T*/'Domain Name'/*/T*/] = $packageDomainName;
                    CE_Lib::log(3, "Signup: Cart: Domain Name - ".$packageDomainName);
                    $this->add_staff_notification_event("Domain Name: ".$packageDomainName);

                    /*
                     * We need to generate the possible username & password first before saving it so that we can
                     * call validateCredentials on the server plugin to check we have a valid u/p
                     */

                    // Check if we have submitted a username
                    if (isset($key['params']['customFields'][$cfUserName['id']])) {
                        $packageUsername = strtolower($key['params']['customFields'][$cfUserName['id']]);
                    } else {
                        $packageUsername = CE_Lib::generateUsername(8, $packageDomainName);
                    }

                    // Check if we have submitted a password
                    if (isset($key['params']['customFields'][$cfPassword['id']])) {
                        $packagePassword = $key['params']['customFields'][$cfPassword['id']];
                    } else {
                        $packagePassword = CE_Lib::generatePassword();
                    }
                }

                // Get the servers
                $servers = new ServerGateway();
                $serversList = $servers->getServersGridList();
                $serversList = $serversList['data'];
                $packageServers = $package->getProductServerIds();

                $lowestCountId = 0;
                $lowestCount = 0;

                if (is_array($packageServers) && count($packageServers) > 0) {
                    foreach ($packageServers as $packageServer) {
                        $tempServer = $serversList[array_search($packageServer, array_column($serversList, 'id'))];
                        if ($tempServer['rawQuota'] == null) {
                            $tempServer['rawQuota'] = '0';
                        }

                        if ($tempServer['rawAmount'] < $lowestCount || $lowestCountId == 0) {
                            if ($tempServer['rawQuota'] == 0 || $tempServer['rawQuota'] > $tempServer['rawAmount']) {
                                $lowestCount = $tempServer['rawAmount'];
                                $lowestCountId = $packageServer;
                            }
                        }
                    }

                    if ($lowestCountId != 0) {
                        $server = new Server($lowestCountId);
                        $pEventLog['Server'] = $server->getName();

                        $userPackage->setCustomField("Server Id", $lowestCountId);
                        $serverips = $servers->getAvailableIPs($lowestCountId, true);
                        $userPackage->setCustomField("Shared", 1);
                        $pEventLog['Use Shared IP'] = $this->user->lang('Yes');
                        $userPackage->setCustomField("IP Address", $serverips['sharedip']);
                        $pEventLog['IP Address'] = $serverips['sharedip'];
                        CE_Lib::log(3, "Signup: Cart: ServerID - " . $lowestCountId);
                        $this->add_staff_notification_event("Allocated Account to Server : ". $server->getName());

                        if (!isset($advancedSettings['hostingcustomfields']) || $advancedSettings['hostingcustomfields'] != 1) {
                            $pluginGateway = new PluginGateway();
                            $plugin = $pluginGateway->getPluginByName('server', $server->getPlugin());
                            if ($plugin != false && method_exists($plugin, 'validateCredentials')) {
                                $validCredentials = $plugin->validateCredentials(
                                    array(
                                    'package' => array(
                                        'username' => $packageUsername,
                                        'password' => $packagePassword
                                    ),
                                    'noError' => true
                                    )
                                );
                                if ($validCredentials) {
                                    $packageUsername = $validCredentials;
                                }
                            }
                        }
                    } else {
                        CE_Lib::log(3, "Signup: Cart: WARNING - NO SERVERS FOUND.");
                        $this->add_staff_notification_event("Unable to find a server to allocate account");
                        $ticketUser = new User($userPackage->CustomerId);
                        $subject = 'Unable to allocate Package #' . $userPackage->getId() . ' to a server';
                        $message = 'Unable to allocate ' . $userPackage->getReference(true) . ' to a server.  Please manually select a server and activate this package.';
                        $this->createSupportTicket($subject, $message, $ticketUser, $userPackage->getId());
                    }
                }

                // Do not try to set username/password if we are hidding hosting custom fields
                if (!isset($advancedSettings['hostingcustomfields']) || $advancedSettings['hostingcustomfields'] != 1) {
                    // Save the username now thats its validated
                    $userPackage->setCustomField('User Name', $packageUsername);
                    $pEventLog[/*T*/'User Name'/*/T*/] = $packageUsername;
                    CE_Lib::log(3, "Signup: Cart: User Name - ".$packageUsername);
                    $this->add_staff_notification_event("User Name: ".$packageUsername);

                    // Save the password, but do not add to the e-mail, as it can be a security issue.
                    if ($this->settings->get('Domain Passwords are Encrypted') == 1) {
                        $userPackage->setCustomField('Password', Clientexec::encryptString($packagePassword));
                    } else {
                        $userPackage->setCustomField('Password', $packagePassword);
                    }
                }

                // Add the product to bundle
                if (isset($key['bundledDomain']) && $key['bundledDomain']) {
                    $productsToBundle[$userPackageId] = $key['bundledDomain'];
                    $this->add_staff_notification_event("Product is bundled with a domain name");
                }
            } elseif ($productGroupInfo[$package->planid]->fields['type'] == PACKAGE_TYPE_DOMAIN) {
                CE_Lib::log(3, "Signup: Cart: Product is of type Domain");
                $this->add_staff_notification_event("Type: Domain Name");

                // Handle the custom field information for domains
                $userPackage->setCustomField('Domain Name', $key['params']['sld'].".".$key['params']['tld']);
                $pEventLog[/*T*/'Domain Name'/*/T*/] = $key['params']['sld'].".".$key['params']['tld'];
                $this->add_staff_notification_event("Domain Name: ".$key['params']['sld'].".".$key['params']['tld']);

                $userPackage->setCustomField('Registration Option', $key['params']['domainType']);

                //Set this value to allow updating the next due date of the recurring fee the first time with the Domain Updater service when using Sync Due Date?
                $userPackage->setCustomField("Transfer Update Date", 1);

                // Extract the registrar from the pricing information
                $package->getProductPricingAllCurrencies();

                //let's pop the pricing array so we don't have to assume the key
                $tld_array_keys = array_keys($package->pricingInformationCurrency[$this->currency]['pricedata']);
                $tld_array = $package->pricingInformationCurrency[$this->currency]['pricedata'][$tld_array_keys[0]];

                $registrar = $tld_array['registrar'];

                $userPackage->setCustomField('Registrar', $registrar);
                $pEventLog[/*T*/'Registrar'/*/T*/] = $registrar;
                $this->add_staff_notification_event("Registrar: " . $registrar);

                // Generate a domain username and password
                $packageUsername = CE_Lib::generateUsername(8, $key['params']['sld'].".".$key['params']['tld']);
                $packagePassword = CE_Lib::generatePassword();
                $key['params']['extraAttributes']['domainUsername'] = CE_Lib::generateUsername(8, $key['params']['sld'].".".$key['params']['tld']);
                $domainPassword = CE_Lib::generatePassword(false);

                if ($this->settings->get('Domain Passwords are Encrypted') == 1) {
                    $key['params']['extraAttributes']['domainPassword'] = Clientexec::encryptString($domainPassword);
                } else {
                    $key['params']['extraAttributes']['domainPassword'] = $domainPassword;
                }

                $this->add_staff_notification_event("Domain Username: ".$key['params']['extraAttributes']['domainUsername']);
                $this->add_staff_notification_event("Domain Password: ".$domainPassword);

                // Show what the customer wants the host to do with the domain they are requesting
                switch ($key['params']['domainType']) {
                    case 0:
                        $strMessageOutput = "Registrar Service Requested: Customer wants host to register domain";
                        $pEventLog[/*T*/'Registrar Service Requested'/*/T*/] = 'Customer wants host to register domain';
                        break;
                    case 1:
                        $strMessageOutput = "Registrar Service Requested: Customer wants host to transfer domain";
                        $pEventLog[/*T*/'Registrar Service Requested'/*/T*/] = 'Customer wants host to transfer domain';
                        break;
                    case 2:
                        $strMessageOutput = "Registrar Service Requested: Customer will manage their own domain";
                        $pEventLog[/*T*/'Registrar Service Requested'/*/T*/] = 'Customer will manage their own domain';
                        break;
                }
                $this->add_staff_notification_event($strMessageOutput);

                // Capture the domain extra attributes
                if (isset($key['params']['extraAttributes']) && is_array($key['params']['extraAttributes'])) {
                    $userPackage->setCustomField('Domain Extra Attr', serialize($key['params']['extraAttributes']));

                    foreach ($key['params']['extraAttributes'] as $extattrname => $extattrvalue) {
                        if (!in_array($extattrname, array('domainPassword'))) {
                            $pEventLog[/*T*/'Extra attribute'/*/T*/ .' "'.$extattrname.'"'] = $extattrvalue;
                        }
                    }

                    // Show EPP code in e-mail if there is one.
                    if (array_key_exists('eppCode', $key['params']['extraAttributes'])) {
                        $this->add_staff_notification_event('EPP Code: ' . $key['params']['extraAttributes']['eppCode']);
                    }
                }
            } elseif ($productGroupInfo[$package->planid]->fields['type'] == PACKAGE_TYPE_SSL) {
                CE_Lib::log(3, "Signup: Cart: Product is of type SSL");
                $this->add_staff_notification_event("Type: SSL Certificate");

                $advanced = unserialize($package->advanced);
                $registrar = $advanced['registrar'];
                $certType = $advanced['certificateId'];
                $userPackage->setCustomField('Registrar', $registrar);
                $pEventLog[/*T*/'Registrar'/*/T*/] = $registrar;
                $this->add_staff_notification_event("Registrar: ". $registrar);

                $userPackage->setCustomField('Certificate Type', $certType);
                $pEventLog[/*T*/'Certificate Type'/*/T*/] = $certType;
                $this->add_staff_notification_event("Certificate Type: ". $certType);

        // Handle the default ssl fields
                if (isset($key['bundledBy']) && $key['bundledBy'] && isset($cartItems[$key['bundledBy']])) {
                    if (isset($cartItems[$key['bundledBy']]['bundledDomain']) && $cartItems[$key['bundledBy']]['bundledDomain'] !== false) {
                        $packageDomainName = $cartItems[$key['bundledBy']]['bundledDomain'];
                        $userPackage->setCustomField('Certificate Domain', $packageDomainName);
                        $pEventLog[/*T*/'Certificate Domain'/*/T*/] = $packageDomainName;
                        CE_Lib::log(3, "Signup: Cart: Certificate Domain - ".$packageDomainName);
                        $this->add_staff_notification_event("Certificate Domain: ".$packageDomainName);
                    }
                }
            } else {
                CE_Lib::log(3, "Signup: Cart: Product is of type General");
                $this->add_staff_notification_event("Type: General");

    // No Specific work to do for general products.
            }

            // Handle any user generated custom fields
            if (isset($key['params']['customFields']) && is_array($key['params']['customFields'])) {
                $firstTime = true;
                foreach ($key['params']['customFields'] as $customTag => $value) {
                    // Skip domain name, username & password as they have been saved above
                    if ((isset($cfDomainName['id']) && $cfDomainName['id'] == $customTag)
                      || (isset($cfUserName['id']) && $cfUserName['id'] == $customTag)
                      || (isset($cfPassword['id']) && $cfPassword['id'] == $customTag)) {
                        continue;
                    }

                    if ($firstTime == true) {
                        $firstTime = false;
                        // Only show Custom Fields header if we have custom fields to show.
                        $this->add_staff_notification_event("");
                        $this->add_staff_notification_event("--------------");
                        $this->add_staff_notification_event("Custom Fields:");
                        $this->add_staff_notification_event("--------------");
                    }

                    // Sanitize $value
                    $value = htmlspecialchars(strip_tags($value), ENT_QUOTES);

                    // Don't bother adding a blank value
                    if (trim($value) != '') {
                        $cfObject = $userPackage->getCustomFieldObject($customTag, CUSTOM_FIELDS_FOR_PACKAGE);
                        if (is_array($cfObject)) {
                            if ($cfObject['fieldtype'] == typeDATE) {
                                $value = CE_Lib::form_to_db($value, $this->settings->get('Date Format'), "/");
                            }
                            // Get the name of the custom field so we can log it
                            CE_Lib::log(3, "Signup: Cart: CustomFieldID ".$cfObject['name']." - ".$value);
                            $this->add_staff_notification_event($cfObject['name'].": ".$value);
                        }

                        // save the value
                        $userPackage->setCustomField($customTag, $value);
                        if (isset($cfObject['name'])) {
                            $pEventLog[$cfObject['name']] = $value;
                        } else {
                            $pEventLog["Custom Field ".$customTag] = $value;
                        }
                    }
                }
            }

            // Handle saving of addons
            $userPackage->saveAddons($key['params']['addons'], $key['params']['addonsQuantities'], $pEventLog);

            include_once 'modules/clients/models/Package_EventLog.php';
            $packageLog = Package_EventLog::newInstance(false, $newUser->getId(), $userPackage->getID(), PACKAGE_EVENTLOG_CREATED, $newUser->getId(), serialize($pEventLog));
            $packageLog->save();

            // Check if we should open a ticket for this product being ordered.
            $this->checkProductOpenTicket($userPackageId);

            /*
         * HANDLE THE BILLING
         * Seems that we create invoice items for single things like setup fees
         * and recurring work entries for things like monthly billing
         * domains have to have an invoice item AND recurring work for X in the future as
         * we can only make one paypal subscription
         */
            $trueTerm = $key['params']['term'];

            CE_Lib::log(3, "Signup: Cart: Product has billing term of ".$trueTerm);

            if (!isset($firstCycle)) {
                $firstCycle = $trueTerm;
            }

            if ($newUser->getUsePaypalSubscriptions()) {
                // Try to create a paypal subscription
                $disableGenerate = 1;
            } else {
                // Do not create a paypal subscription
                $disableGenerate = 0;
            }

            CE_Lib::log(3, "Signup: Cart: DisableGenerate Value: ".$disableGenerate);

            $couponNextBilling = false;

            if (in_array($productGroupInfo[$package->planid]->fields['type'], array(PACKAGE_TYPE_HOSTING, PACKAGE_TYPE_GENERAL, PACKAGE_TYPE_SSL))) {
                // Process prorating
                $linePrice = $package->getCurrencyPrice($this->currency, $key['params']['term']);
                $nextBillDate = $this->generate_next_bill_date($key['params']['term']);
                $proratedDays = 0;

                list($itemPrice, $proRatedPrice, $proratedDays, $proIncFollowing, $tmpnextBill, $proNextBill) = $this->processProrating($key['productId'], $key['params']['term'], $linePrice);

                $linePrice = $itemPrice + $proRatedPrice;

                if ($proNextBill !== 0) {
                    $nextBillDate['nextBilling'] = date("Y-m-d", $proNextBill);
                    $couponNextBilling = $nextBillDate['nextBilling'];
                }

                // Add the domain to the title if required
                if (isset($packageDomainName)) {
                    $extraText = ": ".$packageDomainName;
                } else {
                    $extraText = '';
                }

                $productDescription = $productFullName.$extraText;

                // Hosting and General share the same term types
                // If we have 0 then it's one time
                if ($key['params']['term'] == 0) {
                    $m_Taxable = ($package->isCurrencyTaxable($this->currency))? 1 : 0;
                    $m_Price = $package->getCurrencyPrice($this->currency, $key['params']['term']);
                    $tax1 = $m_Price * ($customertax / 100);
                    $m_TaxAmount = $m_Taxable*($tax1 + ($m_Price + $customertax2compound * $tax1) * ($customertax2 / 100));

                    if ($createInvoice) {
                        // Create an invoice entry for a one time fee
                        $params = array(
                            'm_CustomerID'    => $customerId,
                            'm_Description'   => $productDescription,
                            'm_Detail'        => RecurringEntryGateway::getTermText($key['params']['term']),
                            'm_InvoiceID'     => 0,
                            'm_Date'          => date("Y-m-d"),
                            'm_BillingTypeID' => BILLINGTYPE_PACKAGE,
                            'm_IsProrating'   => 0,
                            'm_Price'         => $m_Price,
                            'm_Quantity'      => 1,
                            'm_Recurring'     => 0,
                            'm_AppliesToID'   => $userPackageId,
                            'm_Setup'         => 0,
                            'm_Taxable'       => $m_Taxable,
                            'm_TaxAmount'     => $m_TaxAmount
                        );

                        $invoiceEntry = new InvoiceEntry($params);
                        $invoiceEntry->updateRecord();

                        $invoiceEntries .= $invoiceEntry->m_EntryID." ";

                        CE_Lib::log(3, "Signup: Cart: Created Invoice Entry '".$invoiceEntry->m_EntryID."'. For: ".$params['m_Description'].' '.$params['m_Detail']);
                    }

                    // Create some recurring work, even if not recurring
                    $tParams = array(
                        'customerid'      => $customerId,
                        'paymentterm'     => 0,
                        'description'     => $productDescription,
                        'detail'          => RecurringEntryGateway::getTermText($key['params']['term']),
                        'billingtypeid'   => BILLINGTYPE_PACKAGE,
                        'appliestoid'     => $userPackageId,
                        'quantity'        => 1,
                        'disablegenerate' => 0,
                        'recurring'       => 0,
                        'subscriptionId'  => '',
                        'auto_charge_cc'  => $this->session->autochargecc
                    );

                    $domainRegRecurringEntry = new RecurringWork($tParams);
                    $domainRegRecurringEntry->update();
                } else {
                    // We still need to handle setup fee's as invoice items
                    $itemSetupFee = $package->getCurrencySetupFee($this->currency, $key['params']['term']);

                    if ($itemSetupFee || $itemSetupFee == '0' || $itemSetupFee == '0.00') {
                        $m_Taxable = ($package->isCurrencyTaxable($this->currency))? 1 : 0;
                        $m_Price = $package->getCurrencySetupFee($this->currency, $key['params']['term']);
                        $tax1 = $m_Price * ($customertax / 100);
                        $m_TaxAmount = $m_Taxable*($tax1 + ($m_Price + $customertax2compound * $tax1) * ($customertax2 / 100));

                        if ($createInvoice) {
                            // Create setup fee
                            $params = array(
                                'm_CustomerID'    => $customerId,
                                'm_Description'   => $productDescription,
                                'm_Detail'        => "Setup fee",
                                'm_InvoiceID'     => 0,
                                'm_Date'          => date("Y-m-d"),
                                'm_BillingTypeID' => BILLINGTYPE_PACKAGE,
                                'm_IsProrating'   => 0,
                                'm_Price'         => $m_Price,
                                'm_Quantity'      => 1,
                                'm_Recurring'     => 0,
                                'm_AppliesToID'   => $userPackageId,
                                'm_Setup'         => 1,
                                'm_Taxable'       => $m_Taxable,
                                'm_TaxAmount'     => $m_TaxAmount
                            );

                            $invoiceEntry = new InvoiceEntry($params);
                            $invoiceEntry->updateRecord();

                            $invoiceEntries .= $invoiceEntry->m_EntryID." ";
                            CE_Lib::log(3, "Signup: Cart: Created Invoice Entry '".$invoiceEntry->m_EntryID."'. For: ".$params['m_Description'].' '.$params['m_Detail']);
                        }
                    }

                    $itemFee = $package->getCurrencyPrice($this->currency, $key['params']['term']);
                    if ($itemFee || $itemFee == '0' || $itemFee == '0.00') {
                        // Create some recurring work
                        $tParams = array(
                            'customerid'      => $customerId,
                            'paymentterm'     => $key['params']['term'],
                            'description'     => $productDescription,
                            'detail'          => "Every".' '.strtolower(RecurringEntryGateway::getTermText($key['params']['term'])),
                            'billingtypeid'   => BILLINGTYPE_PACKAGE,
                            'nextbilldate'    => $nextBillDate['nextBilling'],
                            'appliestoid'     => $userPackageId,
                            'amount'          => $itemFee,
                            'quantity'        => 1,
                            'disablegenerate' => $disableGenerate,
                            'taxable'         => ($package->isCurrencyTaxable($this->currency))? 1 : 0,
                            'recurring'       => 1,
                            'subscriptionId'  => '',
                            'auto_charge_cc'  => $this->session->autochargecc
                        );
                        $domainRegRecurringEntry = new RecurringWork($tParams);
                        $domainRegRecurringEntry->update();

                        CE_Lib::log(3, "Signup: Cart: Created Recurring work. Paymentterm: '".$key['params']['term']."'. For: ".$tParams['description'].' '.$tParams['detail']);

                        $secondsInADay = 60*60*24;

                        $m_Taxable = ($package->isCurrencyTaxable($this->currency))? 1 : 0;
                        $m_Price = $linePrice;
                        $tax1 = $m_Price * ($customertax / 100);
                        $m_TaxAmount = $m_Taxable*($tax1 + ($m_Price + $customertax2compound * $tax1) * ($customertax2 / 100));

                        if ($createInvoice) {
                            // Create invice entry
                            $params = array(
                                'm_CustomerID'         => $customerId,
                                'm_Description'        => $productDescription,
                                'm_Detail'             => "Every".' '.strtolower(RecurringEntryGateway::getTermText($key['params']['term'])),
                                'm_InvoiceID'          => 0,
                                'm_Date'               => date("Y-m-d"),
                                'm_PeriodStart'        => date('Y-m-d', mktime(0, 0, 0, date("m"), date("d"), date("Y"))),
                                'm_PeriodEnd'          => date('Y-m-d', mktime(0, 0, 0, date("m", strtotime($nextBillDate['nextBilling'])), date("d", strtotime($nextBillDate['nextBilling'])), date("Y", strtotime($nextBillDate['nextBilling']))) - $secondsInADay),
                                'm_BillingTypeID'      => BILLINGTYPE_PACKAGE,
                                'm_IsProrating'        => ($proratedDays) ? 1 : 0,
                                'm_Price'              => $m_Price,
                                'm_Quantity'           => 1,
                                'm_Recurring'          => 1,
                                'm_AppliesToID'        => $userPackageId,
                                'm_Setup'              => 0,
                                'm_Taxable'            => $m_Taxable,
                                'm_RecurringAppliesTo' => $domainRegRecurringEntry->vars['id']['data'],
                                'm_BillingCycle'       => $key['params']['term'],
                                'm_TaxAmount'          => $m_TaxAmount
                            );
                            $invoiceEntry = new InvoiceEntry($params);
                            $invoiceEntry->updateRecord();

                            $invoiceEntries .= $invoiceEntry->m_EntryID." ";
                            CE_Lib::log(3, "Signup: Cart: Created Invoice Entry '".$invoiceEntry->m_EntryID."'. For: ".$params['m_Description'].' '.$params['m_Detail']);
                        }
                    }
                }
            } elseif ($productGroupInfo[$package->planid]->fields['type'] == PACKAGE_TYPE_DOMAIN && in_array($key['params']['domainType'], array(0, 1))) {
                $domainPricing = $package->isCurrencyTermValid($this->currency, $key['params']['term']);

                $productGroupName = $productGroupInfo[$package->planid]->fields['name'];

                // Add the domain to the title if required
                if (isset($key['params']['sld']) && isset($key['params']['tld'])) {
                    $extraText = ": ".$key['params']['sld'].".".$key['params']['tld'];
                } else {
                    $extraText = '';
                }

                $productDescription = $productGroupName.$extraText;

                // Get values to determine if the domain will be free or not
                $workingItemDomainName = $key['params']['sld'].".".$key['params']['tld'];
                if ($workingItemDomainName != '' && $workingItemDomainName != null && isset($freeDomainOptions[$workingItemDomainName])) {
                    $cartParentPackageFreeDomain = $freeDomainOptions[$workingItemDomainName]['freedomain'];
                    $cartParentPackageDomainExtension = $freeDomainOptions[$workingItemDomainName]['domainextension'];
                    $cartParentPackageDomainCycle = $freeDomainOptions[$workingItemDomainName]['domaincycle'];
                } else {
                    $cartParentPackageFreeDomain = 0;
                    $cartParentPackageDomainExtension = array();
                    $cartParentPackageDomainCycle = array();
                }

                // If free domain, set price to 0
                if ($cartParentPackageFreeDomain > 0 && in_array($key['productId'], $cartParentPackageDomainExtension) && in_array($key['params']['term'], $cartParentPackageDomainCycle)) {
                    $domainPricing['price'] = 0;
                    $domainPricing['transfer'] = 0;
                    if ($cartParentPackageFreeDomain == 2) {
                        $domainPricing['renew'] = 0;
                        $userPackage->use_custom_price = 1;
                        $userPackage->custom_price = 0;
                        $userPackage->save();
                    }
                }

                // Handle the type of domain, transfer or registration
                $invoiceEntryDetail = '';
                $m_Price = 0;
                if ($key['params']['domainType'] == 0) {
                    $m_Price = $domainPricing['price'];
                    $invoiceEntryDetail = "Domain registration";
                } elseif ($key['params']['domainType'] == 1) {
                    $m_Price = $domainPricing['transfer'];
                    $invoiceEntryDetail = "Domain transfer";
                }

                // If we have 0 then it's one time
                if ($key['params']['term'] == 0) {
                    // Create some recurring work, even if not recurring
                    $params = array(
                        'customerid'      => $customerId,
                        'paymentterm'     => 0,
                        'description'     => $productDescription,
                        'detail'          => RecurringEntryGateway::getTermText($key['params']['term']),
                        'billingtypeid'   => BILLINGTYPE_PACKAGE,
                        'appliestoid'     => $userPackageId,
                        'quantity'        => 1,
                        'disablegenerate' => 0,
                        'recurring'       => 0,
                        'subscriptionId'  => '',
                        'auto_charge_cc'  => $this->session->autochargecc
                    );
                    $recurringWork = new RecurringWork($params);
                    $recurringWork->update();

                    $m_Taxable = ($package->isCurrencyTaxable($this->currency))? 1 : 0;
                    $tax1 = $m_Price * ($customertax / 100);
                    $m_TaxAmount = $m_Taxable*($tax1 + ($m_Price + $customertax2compound * $tax1) * ($customertax2 / 100));

                    if ($createInvoice) {
                        // Create invoice item for this here and now
                        $tParams = array(
                            'm_CustomerID'         => $customerId,
                            'm_Description'        => $productDescription,
                            'm_Detail'             => RecurringEntryGateway::getTermText($key['params']['term']),
                            'm_InvoiceID'          => 0,
                            'm_Date'               => date("Y-m-d"),
                            'm_BillingTypeID'      => BILLINGTYPE_PACKAGE,
                            'm_IsProrating'        => 0,
                            'm_Price'              => $m_Price,
                            'm_Quantity'           => 1,
                            'm_Recurring'          => 0,
                            'm_AppliesToID'        => $userPackageId,
                            'm_Setup'              => 0,
                            'm_Taxable'            => $m_Taxable,
                            'm_TaxAmount'          => $m_TaxAmount
                        );
                        $invoiceEntry = new InvoiceEntry($tParams);
                        $invoiceEntry->updateRecord();

                        $invoiceEntries .= $invoiceEntry->m_EntryID." ";
                        CE_Lib::log(3, "Signup: Cart: Created Invoice Entry '".$invoiceEntry->m_EntryID."'. For: ".$tParams['m_Description'].' '.$tParams['m_Detail']);
                    }
                } else {
                    // future recurring work
                    $nextBillDate = $this->generate_next_bill_date($key['params']['term']);
                    $params = array(
                        'customerid'      => $customerId,
                        'paymentterm'     => $key['params']['term'],
                        'description'     => $productDescription,
                        'detail'          => "Domain renewal".' '."for".' '.$productDescription.' x '.strtolower(RecurringEntryGateway::getTermText($key['params']['term']))
                            ."<br>"."Every".' '.strtolower(RecurringEntryGateway::getTermText($key['params']['term'])),
                        'billingtypeid'   => BILLINGTYPE_PACKAGE,
                        'nextbilldate'    => $nextBillDate['nextBilling'],
                        'appliestoid'     => $userPackageId,
                        'amount'          => $domainPricing['renew'],
                        'quantity'        => 1,
                        'disablegenerate' => $disableGenerate,
                        'taxable'         => ($package->isCurrencyTaxable($this->currency))? 1 : 0,
                        'recurring'       => ($key['params']['term'])? 1 : 0,
                        'subscriptionId'  => '',
                        'auto_charge_cc'  => $this->session->autochargecc
                    );
                    $recurringWork = new RecurringWork($params);
                    $recurringWork->update();
                    CE_Lib::log(3, "Signup: Cart: Created Recurring work. Paymentterm: '".$key['params']['term']."' Year(s). For: ".$params['description'].' '.$params['detail']);

                    $secondsInADay = 60*60*24;

                    $m_Taxable = ($package->isCurrencyTaxable($this->currency))? 1 : 0;
                    $tax1 = $m_Price * ($customertax / 100);
                    $m_TaxAmount = $m_Taxable*($tax1 + ($m_Price + $customertax2compound * $tax1) * ($customertax2 / 100));

                    if ($createInvoice) {
                        // Create invoice item for this here and now
                        $tParams = array(
                            'm_CustomerID'         => $customerId,
                            'm_Description'        => $productDescription,
                            'm_Detail'             => $invoiceEntryDetail.' '."for".' '.$productDescription.' x '.strtolower(RecurringEntryGateway::getTermText($key['params']['term']))
                                ."<br>"."Every".' '.strtolower(RecurringEntryGateway::getTermText($key['params']['term'])),
                            'm_InvoiceID'          => 0,
                            'm_Date'               => date("Y-m-d"),
                            'm_PeriodStart'        => date('Y-m-d', mktime(0, 0, 0, date("m"), date("d"), date("Y"))),
                            'm_PeriodEnd'          => date('Y-m-d', mktime(0, 0, 0, date("m", strtotime($nextBillDate['nextBilling'])), date("d", strtotime($nextBillDate['nextBilling'])), date("Y", strtotime($nextBillDate['nextBilling']))) - $secondsInADay),
                            'm_BillingTypeID'      => BILLINGTYPE_PACKAGE,
                            'm_IsProrating'        => 0,
                            'm_Price'              => $m_Price,
                            'm_Quantity'           => 1,
                            'm_Recurring'          => ($key['params']['term'])? 1 : 0,
                            'm_AppliesToID'        => $userPackageId,
                            'm_Setup'              => 0,
                            'm_Taxable'            => $m_Taxable,
                            'm_RecurringAppliesTo' => $recurringWork->vars['id']['data'],
                            'm_BillingCycle'       => $key['params']['term'],
                            'm_TaxAmount'          => $m_TaxAmount
                        );
                        $invoiceEntry = new InvoiceEntry($tParams);
                        $invoiceEntry->updateRecord();

                        $invoiceEntries .= $invoiceEntry->m_EntryID." ";
                        CE_Lib::log(3, "Signup: Cart: Created Invoice Entry '".$invoiceEntry->m_EntryID."'. For: ".$tParams['m_Description'].' '.$tParams['m_Detail']);
                    }
                }
            } else {
                // Handle SSL Certs
            }

            // Handle addon invoice Entries
            if (isset($key['params']['addons']) && $key['params']['addons']) {
                $this->add_staff_notification_event("");
                $this->add_staff_notification_event("--------------");
                $this->add_staff_notification_event("Package Addons:");
                $this->add_staff_notification_event("--------------");

                $addon_term = $key['params']['term'];
                $packageAddons = $this->getAddons($key['productId'], $addon_term);

                // Loop the addons in the array
                foreach ($key['params']['addons'] as $addonId => $addonValue) {
                    // Explode the addon value
                    $addonExplode = explode("_", $addonValue);

                    // Check we have enough info from the post
                    if (count($addonExplode) == 4) {
                        // Get the addon
                        foreach ($packageAddons as $addon) {
                            // Do we have the right addon?
                            if ($addon['id'] == $addonId) {
                                // Get the price
                                foreach ($addon['prices'] as $price) {
                                    if ($price['price_id'] == $addonExplode[2] && $price['recurringprice_cyle'] == $addonExplode[3]) {
                                        $addonDescription = $packageAddonGateway->getAddonNameAndOption($price['price_id']).' ('.$productDescription.')';
                                        $addonDetail = "Every".' '.strtolower(RecurringEntryGateway::getTermText($price['recurringprice_cyle']));

                                        $m_Quantity = $this->verifyAddonQuantity($key['params']['addonsQuantities'][$addonId]);
                                        $quantityLogString = "'";
                                        $quantityEventString = '';
                                        if ($m_Quantity > 1 || $m_Quantity == 0) {
                                            $quantityLogString = "'. Quantity: ".$m_Quantity;
                                            $quantityEventString = '/'."each".' x '.$m_Quantity;
                                        }

                                        // Check for something above or equal to 0
                                        if (isset($price['item_price'])&& $price['item_price'] >= 0 && isset($price['recurringprice_cyle']) && $price['recurringprice_cyle'] > 0) {
                                            if ($newUser->getUsePaypalSubscriptions()) {
                                                $disableGenerate = 1;
                                            } else {
                                                $disableGenerate = 0;
                                            }

                                            // Process prorating
                                            $linePrice = $price['item_price'];
                                            $nextBillDate = $this->generate_next_bill_date($price['recurringprice_cyle']);
                                            $proratedDays = 0;

                                            list($itemPrice, $proRatedPrice, $proratedDays, $proIncFollowing, $tmpnextBill, $proNextBill) = $this->processProrating($key['productId'], $price['recurringprice_cyle'], $linePrice);

                                            $linePrice = $itemPrice + $proRatedPrice;

                                            if ($proNextBill !== 0) {
                                                $nextBillDate['nextBilling'] = date("Y-m-d", $proNextBill);
                                            }

                                            $recurringEntry = new RecurringWork(
                                                array(
                                                   'description'          => $addonDescription,
                                                   'detail'               => $addonDetail,
                                                   'billingtypeid'        => BILLINGTYPE_PACKAGE_ADDON,
                                                   'packageAddonPricesId' => $price['price_id'],
                                                   'amount'               => $price['item_price'],
                                                   'quantity'             => $m_Quantity,
                                                   'disablegenerate'      => $disableGenerate,
                                                   'customerid'           => $customerId,
                                                   'paymentterm'          => $price['recurringprice_cyle'],
                                                   'nextbilldate'         => $nextBillDate['nextBilling'],
                                                   'appliestoid'          => $userPackageId,
                                                   'taxable'              => ($addon['taxable'])? 1 : 0,
                                                   'subscriptionId'       => ''
                                                )
                                            );
                                            $recurringEntry->update();
                                            CE_Lib::log(3, "Signup: Cart: Created Recurring work. Paymentterm: '".$price['recurringprice_cyle'].$quantityLogString.". For: ".$addonDescription);

                                            $secondsInADay = 60*60*24;

                                            $m_Taxable = ($addon['taxable'])? 1 : 0;
                                            $m_Price = $linePrice;
                                            $tax1 = $m_Price * ($customertax / 100);
                                            $m_TaxAmount = $m_Taxable*($tax1 + ($m_Price + $customertax2compound * $tax1) * ($customertax2 / 100));

                                            if ($createInvoice) {
                                                $tParams = array(
                                                    'm_CustomerID'         => $customerId,
                                                    'm_Description'        => $addonDescription,
                                                    'm_Detail'             => $addonDetail,
                                                    'm_InvoiceID'          => 0,
                                                    'm_Date'               => date("Y-m-d"),
                                                    'm_PeriodStart'        => date('Y-m-d', mktime(0, 0, 0, date("m"), date("d"), date("Y"))),
                                                    'm_PeriodEnd'          => date('Y-m-d', mktime(0, 0, 0, date("m", strtotime($nextBillDate['nextBilling'])), date("d", strtotime($nextBillDate['nextBilling'])), date("Y", strtotime($nextBillDate['nextBilling']))) - $secondsInADay),
                                                    'm_BillingTypeID'      => BILLINGTYPE_PACKAGE_ADDON,
                                                    'm_IsProrating'        => ($proratedDays) ? 1 : 0,
                                                    'm_Price'              => $m_Price,
                                                    'm_Quantity'           => $m_Quantity,
                                                    'm_Recurring'          => 1,
                                                    'm_AppliesToID'        => $userPackageId,
                                                    'm_Setup'              => 0,
                                                    'm_Taxable'            => $m_Taxable,
                                                    'm_RecurringAppliesTo' => $recurringEntry->vars['id']['data'],
                                                    'm_BillingCycle'       => $price['recurringprice_cyle'],
                                                    'm_TaxAmount'          => $m_TaxAmount
                                                );
                                                $invoiceEntry = new InvoiceEntry($tParams);
                                                $invoiceEntry->updateRecord();

                                                $invoiceEntries .= $invoiceEntry->m_EntryID." ";
                                                CE_Lib::log(3, "Signup: Cart: Created Invoice Entry '".$invoiceEntry->m_EntryID.$quantityLogString.". For :".$tParams['m_Description']);
                                            }
                                        }

                                        // Handle the addon setup fee
                                        if (isset($price['item_has_setup']) && $price['item_has_setup'] && isset($price['item_setup']) && $price['item_setup'] >= 0) {
                                            $m_Taxable = ($addon['taxable'])? 1 : 0;
                                            $m_Price = $price['item_setup'];
                                            $tax1 = $m_Price * ($customertax / 100);
                                            $m_TaxAmount = $m_Taxable*($tax1 + ($m_Price + $customertax2compound * $tax1) * ($customertax2 / 100));

                                            if ($createInvoice) {
                                                $tParams = array(
                                                    'm_CustomerID'    => $customerId,
                                                    'm_Description'   => $addonDescription,
                                                    'm_Detail'        => "Setup fee",
                                                    'm_InvoiceID'     => 0,
                                                    'm_Date'          => date("Y-m-d"),
                                                    'm_BillingTypeID' => BILLINGTYPE_PACKAGE_ADDON,
                                                    'm_IsProrating'   => 0,
                                                    'm_Price'         => $m_Price,
                                                    'm_Quantity'      => $m_Quantity,
                                                    'm_Recurring'     => 0,
                                                    'm_AppliesToID'   => $userPackageId,
                                                    'm_Setup'         => 0,
                                                    'm_AddonSetup'    => 1,
                                                    'm_Taxable'       => $m_Taxable,
                                                    'm_TaxAmount'     => $m_TaxAmount
                                                );
                                                $invoiceEntry = new InvoiceEntry($tParams);
                                                $invoiceEntry->updateRecord();

                                                $invoiceEntries .= $invoiceEntry->m_EntryID." ";
                                                CE_Lib::log(3, "Signup: Cart: Created Invoice Entry '".$invoiceEntry->m_EntryID.$quantityLogString.". For :".$tParams['m_Description']);
                                            }
                                        }

                                        $this->add_staff_notification_event($addonDescription." (".$price['item_price'].$quantityEventString.")");

                                        // Check if we should be opening a ticket for this addon.
                                        $this->checkAddonOpenTicket($price['price_id'], $userPackageId, $addonDescription);
                                    }
                                }
                            }
                        }
                    }
                }
            }

            // Handle coupon code invoice entries
            if (isset($key['couponCode']) && $key['couponCode']) {
                // Get the coupon info
                $coupon = $cartItems['couponCodes'][$key['couponCode']];

                $this->add_staff_notification_event("<br>\n<br>\n--------------<br>\nCoupon<br>\n--------------\n");
                $this->add_staff_notification_event("Coupon Code: ".$coupon['code']);

                if ($coupon['type'] == 1) {
                    $this->add_staff_notification_event("Coupon Discount: ".($coupon['discount']*100) . '%');
                } else {
                    $currency = new Currency($this->user);
                    $this->add_staff_notification_event("Coupon Discount: ". $currency->format($this->currency, $coupon['discount'], true));
                }

                // Generate the descriptions
                $m_Description = 'Discount coupon'.' ('.$productDescription.')';
                $m_Detail = "Code: ".$coupon['code'];
                include_once 'modules/admin/models/CouponGateway.php';
                $applicableToDetails = CouponGateway::getCouponAppliesTo($coupon['type'], $coupon['applicableTo']);

                if ($applicableToDetails == "all") {
                    $applicableToDetails = "";
                } else {
                    $applicableToDetails = "Applies to:".' '.$applicableToDetails."<br>";
                }

                $m_CouponRecurringAppliesTo = 0;

                $periodStart = null;
                $periodEnd = null;
                if (isset($coupon['recurring']) && $coupon['recurring'] && ($coupon['type'] == 0 || (COUPON_APPLIESTO_PACKAGE & $coupon['applicableTo']) || (COUPON_APPLIESTO_ADDONS & $coupon['applicableTo']) || (COUPON_APPLIESTO_OTHER & $coupon['applicableTo']))) {
                    if ($newUser->getUsePaypalSubscriptions()) {
                        $disableGenerate = 1;
                    } else {
                        $disableGenerate = 0;
                    }

                    // create recurring entry
                    $nextBillDate = $this->generate_next_bill_date($key['params']['term']);
                    if ($couponNextBilling !== false) {
                        $nextBillDate['nextBilling'] = $couponNextBilling;
                    }

                    $couponPaymentterm = $key['params']['term'];

                    //Monthly usage should be used only for Billing Cycles which have their Time Unit in Months or Years.
                    $monthlyusage = '';

                    $billingCycle = new BillingCycle($couponPaymentterm);

                    if ($billingCycle->amount_of_units > 0 && in_array($billingCycle->time_unit, array('m', 'y'))) {
                        $couponPaymenttermInMonths = 0;

                        switch ($billingCycle->time_unit) {
                            case 'm':
                                $couponPaymenttermInMonths = $billingCycle->amount_of_units;
                                break;
                            case 'y':
                                $couponPaymenttermInMonths = $billingCycle->amount_of_units * 12;
                                break;
                        }

                        $monthlyusage = $couponPaymenttermInMonths.'|'.$coupon['recurringmonths'];
                    }
                    //Monthly usage should be used only for Billing Cycles which have their Time Unit in Months or Years.

                    $recurringEntry = new RecurringWork(
                        array(
                            'description'        => $m_Description,
                            'detail'             => $applicableToDetails.$m_Detail,
                            'billingtypeid'      => BILLINGTYPE_COUPON_DISCOUNT,
                            'amount'             => -$coupon['discount'],
                            'amountPercent'      => $coupon['type']? $coupon['discount'] : 0,
                            'quantity'           => 1,
                            'disablegenerate'    => $disableGenerate,
                            'customerid'         => $customerId,
                            'paymentterm'        => $couponPaymentterm,
                            'nextbilldate'       => $nextBillDate['nextBilling'],
                            'appliestoid'        => $userPackageId,
                            'couponApplicableTo' => $coupon['applicableTo'],
                            'taxable'            => ($coupon['taxable'] && $package->isCurrencyTaxable($this->currency))? 1 : 0,
                            'monthlyusage'       => $monthlyusage,
                            'subscriptionId'     => ''
                        )
                    );
                    $recurringEntry->update();
                    $m_CouponRecurringAppliesTo = $recurringEntry->vars['id']['data'];
                    CE_Lib::log(3, "Signup: Cart: Created Recurring work. Paymentterm: '".$couponPaymentterm."'. For: ".$m_Description);

                    $periodStart = date('Y-m-d', mktime(0, 0, 0, date("m"), date("d"), date("Y")));
                    $secondsInADay = 60*60*24;
                    $periodEnd = date('Y-m-d', mktime(0, 0, 0, date("m", strtotime($nextBillDate['nextBilling'])), date("d", strtotime($nextBillDate['nextBilling'])), date("Y", strtotime($nextBillDate['nextBilling']))) - $secondsInADay);
                }

                $m_Taxable = ($coupon['taxable'] && $package->isCurrencyTaxable($this->currency))? 1 : 0;
                $m_Price = -$coupon['discount'];
                $tax1 = $m_Price * ($customertax / 100);
                $m_TaxAmount = $m_Taxable*($tax1 + ($m_Price + $customertax2compound * $tax1) * ($customertax2 / 100));

                if ($createInvoice) {
                    $discountInvEntry = new InvoiceEntry(
                        array(
                            'm_CustomerID'         => $customerId,
                            'm_Description'        => $m_Description,
                            'm_Detail'             => $applicableToDetails.$m_Detail,
                            'm_InvoiceID'          =>  0,
                            'm_Date'               => date("Y-m-d"),
                            'm_PeriodStart'        => $periodStart,
                            'm_PeriodEnd'          => $periodEnd,
                            'm_BillingTypeID'      => BILLINGTYPE_COUPON_DISCOUNT,
                            'm_IsProrating'        => 0,
                            'm_Price'              => $m_Price,
                            'm_PricePercent'       => $coupon['type']? $coupon['discount'] : 0,
                            'm_Quantity'           => 1,
                            'm_Recurring'          => ($coupon['recurring'])? $coupon['recurring'] : 0,
                            'm_RecurringAppliesTo' => $m_CouponRecurringAppliesTo,
                            'm_BillingCycle'       => $couponPaymentterm,
                            'm_AppliesToID'        => $userPackageId,
                            'm_CouponApplicableTo' => $coupon['applicableTo'],
                            'm_Taxable'            => $m_Taxable,
                            'm_TaxAmount'          => $m_TaxAmount
                        )
                    );
                    $discountInvEntry->updateRecord();

                    $invoiceEntries .= $discountInvEntry->m_EntryID." ";
                    CE_Lib::log(3, "Signup: Cart: Created Invoice Entry '".$discountInvEntry->m_EntryID."'. For :".$m_Description);

                    //Due to this code about coupon depends on the invoice entry, then it can only be used if the invoice entry is actually created
                    $query = "INSERT INTO coupons_usage (invoiceentryid,couponid,isrecurring) VALUES(?, ?, ?) ";
                    $this->db->query($query, $discountInvEntry->m_EntryID, $coupon['id'], 0);

                    Coupon::updateQuantity($coupon['code']);
                }
            }

            // Save the user package incase we made any changes
            $userPackage->save();
            CE_Lib::trigger(
                'Order-NewPackage',
                $this,
                [
                    'userid' => $customerId,
                    'userPackage' => $userPackage,
                    'userPackageId' => $userPackage->id
                ]
            );
            CE_Lib::log(3, "Signup: Cart: Saved Product!");
        } //end for loop

        // Handle the bundling of products
        // Not the most efficient way of doing things but we don't always know what
        // order products will be saved in the cart so we have to handle the bundling
        // outside of the main product creating loop
        foreach ($productsToBundle as $value => $key) {
            if (isset($cartItems[$key]['savedProductId']) && is_numeric($cartItems[$key]['savedProductId'])) {
                // Get the product
                $userPackage = new UserPackage($cartItems[$key]['savedProductId']);

                // Push the bundle ID
                $userPackage->parentPackageId = $value;

                CE_Lib::log(3, "Signup: Cart: Bundling Product '".$userPackage->fields['id']."' to '".$value."'");

                // Save the product
                $userPackage->save();
            }
        }

        return $invoiceEntries;
    }

    public function verifyAddonQuantity($quantity)
    {
        $addonQuantity = 1;

        if (isset($quantity)) {
            $addonQuantity = (int) $quantity;

            if ($addonQuantity < 0) {
                $addonQuantity = 0;
            }
        }

        return $addonQuantity;
    }

    /**
    * Create new account from order
    * This also creates new packages from existing customers
    */
    public function create_new_account($passedUserId = null)
    {
        if (isset($this->session->cartContents)) {
            $newCartItems = unserialize(base64_decode($this->session->cartContents));
        } else {
            $newCartItems = array();
        }

        include_once "modules/billing/models/RecurringWork.php";
        include_once 'modules/billing/models/Coupon.php';
        include_once 'modules/billing/models/BillingGateway.php';
        include_once 'modules/admin/models/StatusAliasGateway.php';

        $existingMember = false;
        $customerId = null;
        $firstCycle = null;

        CE_Lib::log(3, "Signup: STARTING CREATE ACCOUNTS");
        if ($passedUserId != null) {
            CE_Lib::log(3, "For passed UserId:".$passedUserId);
        }
        $this->add_staff_notification_event("<br>\n<br>\n--------------<br>\nCustomer Information<br>\n--------------\n");

        //we need to not process this order if stock is used up
        //don't create the user yet.
        // Handle the customer registration
        if (($this->user->getId() && !$this->user->isAdmin() && $this->user->isRegistered()) || $passedUserId) {
            if ($passedUserId) {
                $customerId = $passedUserId;
            } else {
                $customerId = $this->user->getId();
            }

            // existing user
            $existingMember = true;
            $newUser = new User($customerId);

            CE_Lib::log(3, "Signup: Existing Customer Information: Email: '".$newUser->getEmail()."'. User ID: '".$newUser->getId()."'");
            $this->add_staff_notification_event("Existing Customer: " . $newUser->getFullName(true));
            $this->add_staff_notification_event("Email: ".$newUser->getEmail());
            $this->add_staff_notification_event("User ID: ".$newUser->getId());

            // Un-cancel the user
            $statusGateway = StatusAliasGateway::getInstance($this->user);
            if (in_array($newUser->getStatus(), $statusGateway->getUserStatusIdsFor([USER_STATUS_CANCELLED, USER_STATUS_INACTIVE]))) {
                $newUser->setStatus(USER_STATUS_PENDING);
                $newUser->save();

                CE_Lib::log(3, "Signup: Un-cancelling User account!");
            }
        } else {
            $existingMember = false;

            CE_Lib::log(3, "Signup: NEW Customer, signup information to follow....");

            // New user
            $newUser = new User();
            $newUser->setCurrent(false);
            $newUser->setGroupId(1);

            // User Event Log
            $uEventLog = array();

            // Currency
            if (isset($_REQUEST['selectedcurrency'])) {
                $newUser->setCurrency($_REQUEST['selectedcurrency']);
                $uEventLog['Currency'] = $_REQUEST['selectedcurrency'];
                CE_Lib::log(3, "Signup: Customer: Currency - ".$_REQUEST['selectedcurrency']);
            } else {
                $newUser->setCurrency($_REQUEST['currency']);
                $uEventLog['Currency'] = $_REQUEST['currency'];
                CE_Lib::log(3, "Signup: Customer: Currency - ".$_REQUEST['currency']);
            }

            //  Language: default value
            if (!isset($_REQUEST['language'])) {
                $_REQUEST['language'] = $this->settings->getLanguage();
            }
            if (isset($_REQUEST['language'])) {
                $newUser->setLanguage($_REQUEST['language']);
                $uEventLog['Language'] = $_REQUEST['language'];
                CE_Lib::log(3, "Signup: Customer: Language - ".$_REQUEST['language']);
            }

            // Handle some user based payment items
            if (!isset($_REQUEST['paymentMethod']) || $_REQUEST['paymentMethod'] == 'apply_my_credit') {
                $_REQUEST['paymentMethod'] = $this->settings->get('Default Gateway');
            }
            $newUser->setPaymentType($_REQUEST['paymentMethod']);
            $uEventLog['Payment type'] = $_REQUEST['paymentMethod'];
            CE_Lib::log(3, "Signup: Customer: PaymentType - ".$_REQUEST['paymentMethod']);

            if (isset($_REQUEST[$_REQUEST['paymentMethod'].'_ccMonth'])) {
                $newUser->setCCMonth($_REQUEST[$_REQUEST['paymentMethod'].'_ccMonth']);
                CE_Lib::log(3, "Signup: Customer: ccMonth - ".$_REQUEST[$_REQUEST['paymentMethod'].'_ccMonth']);
            }
            if (isset($_REQUEST[$_REQUEST['paymentMethod'].'_ccYear'])) {
                $newUser->setCCYear($_REQUEST[$_REQUEST['paymentMethod'].'_ccYear']);
                CE_Lib::log(3, "Signup: Customer: ccYear - ".$_REQUEST[$_REQUEST['paymentMethod'].'_ccYear']);
            }

            $newUser->setAutopayment($_REQUEST[$_REQUEST['paymentMethod'].'_autopayment']);
            CE_Lib::log(3, "Signup: Customer: AutoPayment - ".$_REQUEST[$_REQUEST['paymentMethod'].'_autopayment']);
            $this->add_staff_notification_event("Payment Method: ".$_REQUEST['paymentMethod']);
            $this->add_staff_notification_event(" ".($_REQUEST[$_REQUEST['paymentMethod'].'_autopayment'])?"Auto payment?: Yes":"Auto payment?: No");

            // Extra attributes
            $newUser->setDateCreated(date("Y-m-d"));
            $newUser->setStatus(0);
            $newUser->setEmail(
                htmlspecialchars(
                    strip_tags(
                        $_REQUEST['CT_' . $newUser->getCustomFieldsObj()->_getCustomFieldIdByType(typeEMAIL)],
                        ENT_QUOTES
                    )
                )
            );

            if (!$newUser->add()) {
                CE_Lib::log(3, "Signup: FATAL ERROR. SIGNUP LIMIT REACHED.");

                // Installation limit reatched, fail This.
                CE_Lib::addErrorMessage("Installation limit reached. Unable to continue");
                CE_Lib::redirectPage("order.php?step=3");
            }

            // MOVED THIS CODE DOWN HERE, SINCE THE USER ID IS REQUIRED FOR PROPERLY STORING THE CC NUMBER
            if (!$existingMember && isset($_REQUEST[$_REQUEST['paymentMethod'].'_ccNumber']) && $_REQUEST[$_REQUEST['paymentMethod'].'_ccNumber'] != '') {
                $uEventLog[/*T*/'CC number (last 4)'/*/T*/] = mb_substr($_REQUEST[$_REQUEST['paymentMethod'].'_ccNumber'], -4);
                $uEventLog[/*T*/'CC expiration month'/*/T*/] = $_REQUEST[$_REQUEST['paymentMethod'].'_ccMonth'];
                $uEventLog[/*T*/'CC expiration year'/*/T*/] = $_REQUEST[$_REQUEST['paymentMethod'].'_ccYear'];
                $newUser->StoreCreditCardInfo($_REQUEST[$_REQUEST['paymentMethod'].'_ccNumber'], $this->settings);
                $newUser->save();

                CE_Lib::log(3, "Signup: Customer: ccNumber - ".mb_substr($_REQUEST[$_REQUEST['paymentMethod'].'_ccNumber'], -4));
            }

            // Move guest tickets to this user
            $userGateway = new UserGateway($this->user);

            $userGateway->importGuestTickets($newUser);
            if (!is_null($this->settings->get('plugin_paypal_Paypal Subscriptions Option')) && $this->settings->get('plugin_paypal_Paypal Subscriptions Option') == 0) {
                $_REQUEST['paypalSubscription'] = 1;
            }

            //paypaSubscription of 1 means that paypal was selected
            //the plugin custom field of plugin_paypal_Paypal Subscriptions Option is 0 when we want to force subscription though (not 1)

            // set paypal subscriptions preference
            if (isset($_REQUEST['paypalSubscription']) && $_REQUEST['paypalSubscription'] == '1' && $_REQUEST['paymentMethod'] == 'paypal') {
                $query = "SELECT id FROM customuserfields WHERE name = 'Use Paypal Subscriptions' ";
                $result = $this->db->query($query);
                list($customId) = $result->fetch();
                $newUser->UpdateCustomTag($customId, 1);

                CE_Lib::log(3, "Signup: Customer: Using Paypal Subscriptions!");
                $this->add_staff_notification_event("Customer is using PayPal subscription");
            }

            // Process Custom Fields for Profile
            foreach ($_REQUEST as $customTag => $value) {
                // Explode the item and see if it's a CT
                $CT = explode("_", $customTag);
                if ($CT[0] == 'CT') {
                    // Sanitize $value
                    $value = htmlspecialchars(strip_tags($value), ENT_QUOTES);

                    // Don't bother adding a blank value
                    if (trim($value) != '') {
                        if (isset($_REQUEST["CTT_{$CT[1]}"]) && $_REQUEST["CTT_{$CT[1]}"] == typeDATE) {
                            if ($value != '') {
                                $value = CE_Lib::form_to_db($value, $this->settings->get('Date Format'), "/");
                            }
                        }

                        $newUser->UpdateCustomTag($CT[1], $value);

                        // Get the name of the custom field
                        $customFieldName = $newUser->getCustomFieldName($CT[1]);
                        $uEventLog[$customFieldName] = $value;
                        $this->add_staff_notification_event($customFieldName.": ".$value);
                        CE_Lib::log(3, "Signup: Customer: ".$customFieldName." - ".$value);
                    }
                }
            }

            $newUser->setPassword($_REQUEST['password']);
            $newUser->setTaxable(1);
            $uEventLog['Taxable'] = 'Yes';
            $newUser->setLastLogin(date('Y-m-d H:i:s'));
            $newUser->save();

            $userGateway->getFullContact($newUser);

            $clientLog = Client_EventLog::newInstance(false, $newUser->getId(), $newUser->getId(), CLIENT_EVENTLOG_CREATED, $newUser->getId(), serialize($uEventLog));
            $clientLog->save();

            // Set the user ID
            $customerId = $newUser->getId();
            CE_Lib::trigger('Client-Create', $this, ['userid' => $customerId, 'user' => $newUser]);

            CE_Lib::log(3, "Signup: Customer: Saved Customer. ID '".$customerId."'");

            //Logging in user
            //We do this so that if user cancels order or CC fails we don't lose the
            //event by deleting the user and then creating it again on next attemp
            //we need to manually update session for new user so that we don't clear the
            //session for signup.  We might want to later have a signup namespace session
            //so we can keep that one but clear main session
            $this->user = $newUser;
            $this->session->userId = $customerId;
            $this->session->groupId = $this->user->getGroupId();
            $this->session->userAccess['userIsAdmin'] = false;
            $this->session->userAccess['userIsSuperAdmin'] = false;
            $this->session->userAccess['userIsCompanySuperAdmin'] = false;
            $userGateway->logSuccessfulLogin($newUser);

            CE_Lib::debug('ordering new product for id:'.$customerId);
            //end logging in user
        }

        $affiliateGateway = new AffiliateGateway($this->user);
        $affiliateGateway->createAffiliateAccount($customerId);

        $invoiceEntries = "";

        // Get the session
        if (isset($this->session->cartHash) && CE_Lib::generateSignupCartHash() == $this->session->cartHash && $this->session->cartHash != null) {
            //It will be Disallowed only when:
            //- The customer had permission to Allow/Disallow
            //- The customer selected to pay with an Autopayment method
            //- And, the customer decided to not authorize that future charges for these packages will be automatically charged.
            $this->session->autochargecc = ($newUser->hasPermission('billing_automatic_cc_charge') && $newUser->isAutopayment() && !isset($_REQUEST['autochargecc']))? 0 : 1;

            $invoiceEntries = $this->create_new_packages($newUser);
        } else {
            // Redirect to step 1
            CE_Lib::log(3, "Signup: Something went wrong.");
            CE_Lib::addErrorMessage($this->user->lang('Signup error.  Please contact us to see what went wrong'));
            CE_Lib::redirectPage('order.php?step=1');
        }

        if ($this->settings->get('Show Terms and Conditions') == 1) {
            $this->logAgreeToTermsAndService($newUser);
        }

        // Check for fraud message
        if (isset($this->session->fraudmsg)) {
            $this->add_staff_notification_event("<br>--------------<br>Fraud Details<br>--------------");
            $fraudMsg = "Fraud Details:\n\n";
            foreach ($this->session->fraudmsg as $msg) {
                $fraudMsg .= $msg . "\n";
                $this->add_staff_notification_event($msg);
            }
            include_once 'modules/clients/models/ClientNote.php';
            $clientNote = new ClientNote();
            $clientNote->setTargetId($newUser->getId());
            $clientNote->setIsTargetGroup(false);
            $clientNote->setAdminId(-1);
            $clientNote->setDate(date('Y-m-d H:i:s'));
            $clientNote->setNote($fraudMsg);
            $clientNote->setIsVisibleClient(false);
            $clientNote->setTicketTypesIds(array());
            $clientNote->setSubject('Fraud Details');
            $clientNote->save();

            unset($this->session->fraudmsg);
        }

        // Add the IP and host information
        $this->add_staff_notification_event("<br><br>--------------<br>Signup Environment<br>--------------");
        $this->add_staff_notification_event("IP: ".CE_Lib::getRemoteAddr());
        $this->add_staff_notification_event("Hostname: ".gethostbyaddr(CE_Lib::getRemoteAddr()));
        $this->add_staff_notification_event("User Agent: ".$_SERVER['HTTP_USER_AGENT']);

        CE_Lib::log(3, "Signup: STARTING PROCESSING PAYMENT");

        /*  START BILLING PORTION OF NEW CUSTOMER
         *  Process the actual billing & invoice any recurring work OR un-invoiced work created above
         */
        $processInvoice = true;
        $billingGateway = new BillingGateway($newUser);
        $createInvoice = true;

        if ($invoiceEntries == '') {
            $createInvoice = false;
        }

        if ($createInvoice) {
            $invoiceData = $billingGateway->createInvoiceWithEntries($invoiceEntries, null, 1);
            $tInvoiceID = $invoiceData['InvoiceID'];

            CE_Lib::log(3, "Signup: Payment: Merged Invoice Entries '".$invoiceEntries."' & created Invoice: ".$tInvoiceID);

            // Get the total invoice amount
            $query = "SELECT id, balance_due, subtotal FROM invoice WHERE status = 0 AND customerid = ? ORDER BY id DESC ";
            $result = $this->db->query($query, $customerId);
            list($tinvoiceid,$invoiceAmount,$subtotal) = $result->fetch();

            // This information can be required for affiliates URL
            $invoice_information = array(
                'invoice_id'            => $tinvoiceid,
                'invoice_amount_no_tax' => $subtotal,
                'invoice_amount'        => $invoiceAmount
            );
            $this->session->invoice_information = base64_encode(serialize($invoice_information));
        }

        //time to send staff notifications
        try {
            $this->send_staff_notification_email();
        } catch (Exception $e) {
            CE_Lib::log(3, 'Signup: Failed to send staff notification email');
        }

        if ($createInvoice) {
            // Handle null invoices. Set to paid.
            if ($invoiceAmount == 0) {
                CE_Lib::log(3, "Signup: Payment: Invoice '".$tinvoiceid."' amount was 0, marking as paid!");
                $billingGateway->setPayInvoice($tinvoiceid);
                $processInvoice = false;
            }

            //let's create new plugin
            include_once "modules/admin/models/PluginGateway.php";
            $plugin_gateway = new PluginGateway($this->user);
            $plugininternalname = filter_var($_REQUEST['paymentMethod'], FILTER_SANITIZE_STRING);

            $apply_my_credit = isset($_POST['applymycredit']);

            //When paying the invoice with account credit
            if ($plugininternalname == 'apply_my_credit') {
                $processInvoice = false;
                $plugin_accepts_cc = 0;
                $plugin_pays_with_cc = 0;
            } else {
                $plugin = $plugin_gateway->getPluginByName("gateways", $plugininternalname);
                $plugin_accepts_cc = $plugin->getVariable("Accept CC Number");
                $plugin_pays_with_cc = $plugin->getVariable("Auto Payment");
            }

            //If existing member, do not run the charge right now because of the CC number
            if ($plugin_accepts_cc && !$plugin_pays_with_cc) {
                $processInvoice = false;
            }

            // special case: quantum gateway is autopayment, but may redirect at signup when using VbV or MC SecureCode verifications,
            // so we need to send the signup notification before it gets redirected,
            // but when it's an existing member, there's no redirection
            if (!$plugin_pays_with_cc || !$processInvoice || $this->settings->get('Forward To Gateway') != 1
              || ($plugininternalname == 'quantum'
              && ($this->settings->get('plugin_quantum_Use Verify By Visa and MasterCard SecureCode') || $this->settings->get('plugin_quantum_Use DialVerify Service')) && !isset($existingMember))) {
                // Send out the notification before the user is redirected
                CE_Lib::log(3, "Signup: Payment: Selected gateway may take us off this page, so send relevant emails now.");
                $this->send_account_creation_email($newUser, $existingMember);
                CE_Lib::log(3, "Signup: Sent account creation email");
            }

            //Always send invoice, no matter if later forwarding to gateway or not
            if ($invoiceAmount == 0) {
                $billingGateway->sendInvoiceEmail($tinvoiceid, "Payment Receipt Template");
            } else {
                if ($this->settings->get('Send Invoice Immediately')) {
                    $billingGateway->sendInvoiceEmail($tinvoiceid, "Invoice Template");
                }
            }
        }

        //for stock enabled items this call removes the items
        //from active_orders so that the items are not readded
        //as stock when the expires datetime is reached.  Since this
        //order is complete we don't need to worry about it's cart contents
        $this->orderCompleted();

        if ($createInvoice) {
            //This option is when the Credit Balance can pay only part of the invoice. Later, a gateway can take care of the rest of the payment
            if ($apply_my_credit) {
                $billingGateway->processApplyAccountCredits($tinvoiceid, true);
            }

            if ($plugininternalname == 'apply_my_credit') {
                //This option is when the Credit Balance can pay the the full invoice.
                $billingGateway->processApplyAccountCredits($tinvoiceid, true);
            } elseif (($this->settings->get('Forward To Gateway') == 1) && $processInvoice) {
                // lets remove cart items if it isn't autopayment
                // or if it is quantum with forward to gateway instead of gateway processing
                // when it's an existing member, there's no redirection
                if (!$plugin_pays_with_cc || (    $plugininternalname == 'quantum'
                  && ($this->settings->get('plugin_quantum_Use Verify By Visa and MasterCard SecureCode') || $this->settings->get('plugin_quantum_Use DialVerify Service')) && !isset($existingMember))) {
                    //$this->destroyCart();
                    //$this->destroyCurrency();
                    //$this->destroyCouponCode();
                    //$this->destroyInvoiceInformation(); // This line will cause issues with affiliate
                }

                CE_Lib::log(3, "Signup: Payment: Fowarding to selected gateway: '".$plugininternalname."'");

                //Need to get invoice and then forward to gateway
                //prep variables needed for sendToBill
                $actOnInvoicesParams = array();

                $actOnInvoicesParams['cc_num'] = $_REQUEST[$_REQUEST['paymentMethod'].'_ccNumber'];
                if (isset($_REQUEST[$_REQUEST['paymentMethod'].'_ccMonth']) && isset($_REQUEST[$_REQUEST['paymentMethod'].'_ccYear'])) {
                    $actOnInvoicesParams['cc_exp'] = sprintf("%02d", $_REQUEST[$_REQUEST['paymentMethod'].'_ccMonth'])."/".$_REQUEST[$_REQUEST['paymentMethod'].'_ccYear'];
                    if (isset($_REQUEST[$_REQUEST['paymentMethod'].'_ccCVV2'])) {
                        $actOnInvoicesParams['cc_CVV2'] = htmlspecialchars($_REQUEST[$_REQUEST['paymentMethod'].'_ccCVV2']);
                    }
                }

                $this->session->on_back_send_to_invoice_id = $tinvoiceid;
                $actOnInvoicesParams['invoiceId'] = $tinvoiceid;
                $actOnInvoicesParams['isSignup'] = true;
                $actOnInvoicesParams['plugin'] = $_REQUEST['paymentMethod'];

                if (isset($_REQUEST[$_REQUEST['paymentMethod'].'_plugincustomfields'])) {
                    $actOnInvoicesParams['plugincustomfields'] = $_REQUEST[$_REQUEST['paymentMethod'].'_plugincustomfields'];
                }

                $retArray = $billingGateway->actOnInvoices($actOnInvoicesParams);

                if ($retArray['error']) {
                    // ** create a support ticket if there was a paypal error!! **
                    if ($_REQUEST['paymentMethod'] == 'paypal') {
                        $subject = 'Paypal gateway payment returned error';
                        $message = "Warning: Paypal gateway plugin returned error. Admin please check events and log for more details. ";
                        $message .= "Signup: Payment: ERROR: ".$tError;
                        $tUser = new User($customerId);
                        include_once 'modules/billing/models/class.gateway.plugin.php';
                        $cPlugin = new Plugin($tinvoiceid, 'paypal', $this->user);
                        $cPlugin->createTicket(false, $subject, $message, $tUser);
                    }

                    CE_Lib::log(3, "Signup: Payment: ERROR: ".$retArray['rawMessage']);

                    // Throw the error
                    CE_Lib::addErrorMessage($retArray['rawMessage']);
                    CE_Lib::redirectPage("order.php?step=3");
                } elseif ($plugin_pays_with_cc) {
                    //$this->sendOutNotifications($tFirstName,$tLastName,$this->domain,$strMessageOutput,$bMember);
                    $this->send_account_creation_email($newUser, $existingMember);
                    CE_Lib::log(3, "Signup: Sent account creation email");
                }
                CE_Lib::log(3, "Signup: Payment: Gateway payment went without errors!");
            }
        } else {
            $this->send_account_creation_email($newUser, $existingMember);
            CE_Lib::log(3, "Signup: Sent account creation email");
        }

        CE_Lib::log(3, "******* SIGNUP COMPLETE ********");

        if ($createInvoice) {
            if (isset($retArray['gatewayForm'])) {
                echo $retArray['gatewayForm'];
                return;
            }
        }

        // Actually handle the signup URL setting
        if ($this->settings->get('Signup Completion URL') != '') {
            CE_Lib::redirectPage($this->settings->get('Signup Completion URL'). '?success=1');
        }

        // For now just faux send people to step 5
        CE_Lib::redirectPage('order.php?step=complete&pass=1');
    }

    function logAgreeToTermsAndService($user)
    {
        if (isset($_POST['agree']) && $_POST['agree'] == 1) {
            $last4 = '';
            if (isset($_REQUEST[$_REQUEST['paymentMethod'].'_ccNumber'])) {
                $last4 = mb_substr($_REQUEST[$_REQUEST['paymentMethod'].'_ccNumber'], -4);
            }
            $clientLog = Client_EventLog::newInstance(false, $user->getId(), $user->getId(), CLIENT_EVENTLOG_AGREE_TERMS_AND_SERVICE, $user->getId(), $last4);
            $clientLog->save();
        }
    }

    /**
     * Function to send the account creation email to the customer. Need to do this in a function
     * so that we can call it before we redirect to the gateway but not if we're deleting a user
     */
    function send_account_creation_email($newUser, $existingMember)
    {
         /*
         * Handle sending of account welcome email if required
         * No need to send it to existing members
         */
        if ($this->settings->get('Send Account Welcome E-mail') == 0 && $existingMember == false) {
            // Get the template
            $templategateway = new AutoresponderTemplateGateway();
            $template = $templategateway->getEmailTemplateByName("Account Creation");
            $strMessage = $template->getContents();
            $strSubjectEmailString = $template->getSubject();
            $templateID = $template->getId();
            if ($templateID !== false) {
                include_once 'modules/admin/models/Translations.php';
                $languages = CE_Lib::getEnabledLanguages();
                $translations = new Translations();
                $languageKey = ucfirst(strtolower($newUser->getRealLanguage()));
                CE_Lib::setI18n($languageKey);

                if (count($languages) > 1) {
                    $strSubjectEmailString = $translations->getValue(EMAIL_SUBJECT, $templateID, $languageKey, $strSubjectEmailString);
                    $strMessage = $translations->getValue(EMAIL_CONTENT, $templateID, $languageKey, $strMessage);
                }
            }

            $strMessage = str_replace("[CLIENTEMAIL]", $newUser->getEmail(), $strMessage);
            $strMessage = str_replace("[CLIENTNAME]", $newUser->getFullName(true), $strMessage);
            $strMessage = str_replace("[FIRSTNAME]", $newUser->getFirstName(), $strMessage);
            $strMessage = str_replace("[LASTNAME]", $newUser->getLastName(), $strMessage);
            $strMessage = str_replace("[COMPANYNAME]", $this->settings->get("Company Name"), $strMessage);
            $strMessage = str_replace("[COMPANYURL]", $this->settings->get('Company URL'), $strMessage);
            $strMessage = str_replace("[CLIENTAPPLICATIONURL]", CE_Lib::getSoftwareURL(), $strMessage);
            $strMessage = str_replace("[FORGOTPASSWORDURL]", CE_Lib::getForgotUrl(), $strMessage);

            // Send the email
            if ($newUser->getEmail() != "") {
                $fromEmail = $this->settings->get('Support E-mail');
                if ($template->getOverrideFrom() != '') {
                    $fromEmail = $template->getOverrideFrom();
                }

                $mailer = new NE_MailGateway();
                try {
                    $mailer->mailMessageEmail(
                        $strMessage,
                        $fromEmail,
                        $this->settings->get("Company Name"),
                        $newUser->getEmail(),
                        '',
                        $strSubjectEmailString,
                        3,
                        0,
                        '',
                        '',
                        MAILGATEWAY_CONTENTTYPE_HTML
                    );
                } catch (Exception $e) {
                    CE_Lib::log(3, 'Signup : Failed to send account welcome email');
                }

                // Log the email sending
                $clientLog = Client_EventLog::newInstance(false, $newUser->getId(), $newUser->getId(), CLIENT_EVENTLOG_SENTACCTEMAIL, -2);
                $clientLog->save();
            }
        }
    }

    /**
     * Function to send new order email to staff
     *
     * @param User $newUser passes User object
     *
     * @return void
     */
    private function send_staff_notification_email()
    {
        //check if we have any staff emails we need to notify
        $emailAddresses = $this->settings->get('E-mail For New Signups');
        $emailAddresses = trim($emailAddresses);
        if ($emailAddresses == '') {
            return;
        }

        // Get the template
        $templategateway = new AutoresponderTemplateGateway();
        $template = $templategateway->getEmailTemplateByName("New Order Notification");
        $strMessage = $template->getContents();
        $strSubjectEmailString = $template->getSubject();

        //Replace the non-standard
        //Need to add order information
        $strMessage = str_replace("[ORDERINFO]", $this->signupEmail, $strMessage);

        $fromEmail = $this->settings->get('Support E-mail');
        if ($template->getOverrideFrom() != '') {
            $fromEmail = $template->getOverrideFrom();
        }

        $mailer = new NE_MailGateway();
        $aSupportEmail = explode("\r\n", $emailAddresses);
        foreach ($aSupportEmail as $email) {
            $mailer->mailMessageEmail(
                $strMessage,
                $fromEmail,
                $this->settings->get('Billing Name'),
                $email,
                '',
                $strSubjectEmailString,
                3,
                0,
                '',
                '',
                MAILGATEWAY_CONTENTTYPE_HTML
            );
        }
    }

    /*
 * Generate the next recurring billing date when given the term
 * and type
 */
    function generate_next_bill_date($paymentterm)
    {
        $billingCycle = new BillingCycle($paymentterm);
        $nextbilldate = time();

        switch ($billingCycle->time_unit) {
            case 'd':
                $nextbilling = strftime("%Y-%m-%d", mktime(0, 0, 0, date("m", $nextbilldate), date("d", $nextbilldate) + $billingCycle->amount_of_units, date("Y", $nextbilldate)));
                $nextbillingNice = date($this->settings->get('Date Format'), mktime(0, 0, 0, date("m", $nextbilldate), date("d", $nextbilldate) + $billingCycle->amount_of_units, date("Y", $nextbilldate)));
                break;
            case 'w':
                $nextbilling = strftime("%Y-%m-%d", mktime(0, 0, 0, date("m", $nextbilldate), date("d", $nextbilldate) + ($billingCycle->amount_of_units * 7), date("Y", $nextbilldate)));
                $nextbillingNice = date($this->settings->get('Date Format'), mktime(0, 0, 0, date("m", $nextbilldate), date("d", $nextbilldate) + ($billingCycle->amount_of_units * 7), date("Y", $nextbilldate)));
                break;
            case 'm':
                $nextbilling = strftime("%Y-%m-%d", mktime(0, 0, 0, date("m", $nextbilldate) + $billingCycle->amount_of_units, date("d", $nextbilldate), date("Y", $nextbilldate)));
                $nextbillingNice = date($this->settings->get('Date Format'), mktime(0, 0, 0, date("m", $nextbilldate) + $billingCycle->amount_of_units, date("d", $nextbilldate), date("Y", $nextbilldate)));
                break;
            case 'y':
                $nextbilling = strftime("%Y-%m-%d", mktime(0, 0, 0, date("m", $nextbilldate), date("d", $nextbilldate), date("Y", $nextbilldate) + $billingCycle->amount_of_units));
                $nextbillingNice = date($this->settings->get('Date Format'), mktime(0, 0, 0, date("m", $nextbilldate), date("d", $nextbilldate), date("Y", $nextbilldate) + $billingCycle->amount_of_units));
                break;
        }

        return array(
            'nextBilling'      => $nextbilling,
            'formattedBilling' => $nextbillingNice
        );
    }

    /**
    * Function to check an array of packages from sign up, and see if we have any savings.
    *
    * @param $packages array Array of packages from sign up
    *
    * @return boolean
    */
    public function doWeHaveAnySavings($packages)
    {
        $hasSavings = false;

        foreach ($packages as $package) {
            foreach ($package['pricing'] as $price) {
                if (isset($price['save']) && $price['save'] != '-') {
                    $hasSavings = true;
                }
            }
        }
        return $hasSavings;
    }

    public function checkProductOpenTicket($userPackageId)
    {
        $userPackage = new UserPackage($userPackageId);
        $package = new Package($userPackage->Plan);
        if ($package->openticket == 1) {
            $user = new User($userPackage->CustomerId);
            $subject = 'Review Package Order #' . $userPackage->getId();
            $message = "The following package has been ordered: \n\n" . $userPackage->getReference(true);
            $this->createSupportTicket($subject, $message, $user, $userPackage->getId());
        }
    }

    public function checkAddonOpenTicket($priceId, $userPackageId, $desc)
    {
        $addonGateway = new AddonGateway();
        $openTicket = $addonGateway->getOpenTicketFromPriceId($priceId);
        if ($openTicket == 1) {
            $userPackage = new UserPackage($userPackageId);
            $user = new User($userPackage->CustomerId);
            $subject = 'Review Addon Order for Package #' . $userPackage->getId();
            $message = "The following addon has been ordered: \n\n" . $desc;
            $this->createSupportTicket($subject, $message, $user, $userPackage->getId());
        }
    }

    public function createSupportTicket($subject, $message, $user, $userPackageId)
    {
        $cTickets = new TicketGateway();
        $tTimeStamp = date('Y-m-d H-i-s');
        $cTicket = new Ticket();
        if ($cTickets->GetTicketCount() == 0) {
            $cTicket->setForcedId($this->settings->get('Support Ticket Start Number'));
        }
        $cTicket->setUser($user);
        $cTicket->setSubject($subject);
        $cTicket->setPriority(1);
        $cTicket->setMethod(1);
        $cTicket->setStatus(TICKET_STATUS_WAITINGONTECH);
        $cTicket->setAssignedToDeptId(1);
        $cTicket->setDomainId($userPackageId);
        include_once 'modules/support/models/TicketTypeGateway.php';
        $ticketTypeGateway = new TicketTypeGateway();
        $externallyCreatedTicketType = $ticketTypeGateway->getExternallyCreatedTicketType();
        $cTicket->setMessageType($externallyCreatedTicketType);
        $cTicket->SetDateSubmitted($tTimeStamp);
        $depGateway = new DepartmentGateway();
        $generalDept = $depGateway->getGeneralDep();
        $cTicket->setAssignedToDept($generalDept);

        $cTicket->SetLastLogDateTime($tTimeStamp);

        if ($targetDeptId = $externallyCreatedTicketType->getTargetDept()) {
            include_once 'modules/support/models/Department.php';
            $dep = new Department($targetDeptId);
            $staff = null;
            if ($targetStaffId = $externallyCreatedTicketType->getTargetStaff()) {
                $staff = new User($targetStaffId);
            } elseif ($dep->getAssignToMember()) {
                $staff = $dep->getMember();
            }
            $cTicket->assign($dep, $staff);
        }

        $cTicket->save();
        $cTicket->addInitialLog($message, $tTimeStamp, $user, false, true);

        try {
            $cTicket->notifyAssignation($user);
        } catch (Exception $ex) {
            CE_Lib::log(1, "Failed sending ticket creation notification");
            CE_Lib::log(1, $ex->getMessage());
        }
    }

    public function checkInvalidProducts()
    {
        //As we are in the summary, we can now reset these values so that if the customer wants to continue shopping, the new Product starts with a new absolute parent and domain name.
        $this->session->absoluteCartParentPackage = null;
        unset($this->session->absoluteCartParentPackage);
        $this->session->absoluteCartParentDomainName = null;
        unset($this->session->absoluteCartParentDomainName);

        $summary = $this->getCartSummary();

        if (isset($summary['cartItems']) && is_array($summary['cartItems'])) {
            //We need to know the Products that have been already verified that were properly bundled to another Product, to not validate its own bundled Products, as signup is not requesting for nested bundles
            $bundledCartItemIds = array();

            //We need a list of all the cart item ids so that we can verify easily later if a given item exists or not
            $cartItemIdArray = array();
            foreach ($summary['cartItems'] as $item) {
                $cartItemIdArray[] = $item['cartItemId'];
            }

            foreach ($summary['cartItems'] as $item) {
                $package = new Package($item['productId']);

                //We need a list of the Product Group Ids that were bundled to the Product, to verify later if all of them were configured
                $packageBundledProducts = $package->getBundledProducts();

                //If the Product had bundled Product Groups, then we need to validate if they were configured
                if ($packageBundledProducts !== false && count($packageBundledProducts) > 0) {
                    foreach ($packageBundledProducts as $value) {
                        //We will not validate the bundles of a Product that was bundled to another one, as signup is not requesting for nested bundles
                        //There must be a reference in isBundle to the Product Group that was bundled, it should not be false, and the reference should exist in the cart items
                        //'IGNORE VALIDATION' is used to ignore validation of Selfmanage Domains, and Sub Domains, as they do not have a real product
                        if (!in_array($item['cartItemId'], $bundledCartItemIds) && (!isset($item['isBundle'][$value]) || $item['isBundle'][$value] === false || ($item['isBundle'][$value] !== 'IGNORE VALIDATION' && !in_array($item['isBundle'][$value], $cartItemIdArray)))) {
                            $this->removeFromCart($item['cartItemId']);
                            $string = $this->user->lang('%s was removed from your cart since you did not finish completing it', $item['safeName']);
                            CE_Lib::addMessage($string);
                        } else {
                            //The bundled Product exist in the cart, so it will be added to the list to not validate its own bundled Products, as signup is not requesting for nested bundles
                            $bundledCartItemIds[] = $item['isBundle'][$value];
                        }
                    }
                }
            }
        }
    }
}
