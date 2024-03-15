<?php
/**
 * TopLevelDomainGateway File
 *
 * @category Model
 * @package  Admin
 * @author   Jason Yaes <jason@clientexec.com>
 * @license  ClientExec License
 * @version  [someversion]
 * @link     http://www.clientexec.com
 */

/**
 * TopLevelDomainGateway Model Class
 *
 * @category Model
 * @package  Admin
 * @author   Jason Yates <jason@clientexec.com>
 * @license  ClientExec License
 * @version  [someversion]
 * @link     http://www.clientexec.com
 */
class TopLevelDomainGateway extends NE_Model
{

    /**
     * Returns false if the tld has not been defined for this product group
     *
     * @param string $tld       tld extension
     * @param string $productGroupId id of the product group
     *
     * @return true
     */
    function isTLDDefindedInProduct($needle, $productGroupId)
    {
        $needle = strtolower($needle);
        $query = "SELECT * FROM package WHERE LOWER(planname)=? AND planid=?";
        $result = $this->db->query($query, $needle, $productGroupId);
        if ($result->getAffectedRows() == 1) {
            return true;
        }

        return false;
    }

    function getPackageIdOfTLD($needle, $productGroupId)
    {
        $needle = strtolower($needle);
        $query = "SELECT id FROM package WHERE LOWER(planname)=? AND planid=?";
        $result = $this->db->query($query, $needle, $productGroupId);
        list($id) = $result->fetch();
        return $id;
    }

    /**
     * Return list of all prices with tld fields for grouping
     * @param  int $productId
     * @return array
     */
    function getPricingForAllTlds($productId, $currency = 'default')
    {
        $defaultCurrency = $this->settings->get('Default Currency');

        if ($currency === 'default') {
            $currency = $defaultCurrency;
        }

        $results = $this->_getDomainPricingForProduct($productId, $currency);
        $finalData = array();
        $lbleditgroup = "Edit TLD";
        $lbldeletegroup = "Delete TLD";
        $lbladdproduct = "Add Period";

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

        //Lets use this to make sure the results keep the order
        foreach ($billingcycles as $billingCycleId => $billingCycleData) {
            $finalData[$billingCycleId] = false;
        }


        // Get the results
        if (isset($results['pricedata'])) {
            foreach ($results['pricedata'] as $value) {
                foreach ($value as $key => $tldPrice) {
                    if (!isset($tldPrice['period']) || $tldPrice['period'] == '' || (!is_array($tldPrice))) {
                        continue;
                    } else {
                        $finalData[$key] = array_merge(
                            $tldPrice,
                            array(
                                'id'     => $tldPrice['period'],
                                'tldraw' => $key,
                                'name'   => $billingcycles[$key]['name']
                            )
                        );
                    }
                }
            }
        }

        //Remove any unused value
        foreach ($billingcycles as $billingCycleId => $billingCycleData) {
            if ($finalData[$billingCycleId] === false) {
                unset($finalData[$billingCycleId]);
            }
        }

        return array_values($finalData);
    }

    /**
     * Returns array of price fields for the given period and product
     * @param  int $productId [description]
     * @param  string $period    [description]
     * @return array
     */
    function getPeriodPricesForTld($productId, $period, $currency = 'default')
    {
        $defaultCurrency = $this->settings->get('Default Currency');

        if ($currency === 'default') {
            $currency = $defaultCurrency;
        }

        $results = $this->_getDomainPricingForProduct($productId, $currency);

        if (!isset($results['pricedata'])) {
            return array();
        }

        $keys = array_keys($results['pricedata']);

        if (!isset($results['pricedata'][$keys[0]]) || !isset($results['pricedata'][$keys[0]][$period])) {
            return array();
        }

        $retArray = array(
            "price"    => number_format((float)$results['pricedata'][$keys[0]][@$period]['price'], 2, '.', ''),
            "transfer" => number_format((float)$results['pricedata'][$keys[0]][@$period]['transfer'], 2, '.', ''),
            "renew"    => number_format((float)$results['pricedata'][$keys[0]][@$period]['renew'], 2, '.', '')
        );

        // allow transfer to be nothing, so we ignore it.
        if ($results['pricedata'][$keys[0]][@$period]['transfer'] == '') {
            $retArray['transfer'] = '';
        }

        return $retArray;
    }

    /**
     * Updates pricing for packageId
     * @param  productId $productId promotionId that
     * @return void
     */
    function updateTldForPackageId($productId, $plugin)
    {

        $results = $this->_getDomainPricingForProduct($productId);
        if (is_array($results['pricedata'])) {
            $keys = array_keys($results['pricedata']);
        } else {
            $keys = array(0);
        }

        //though we have -1 as index for none we store "" in the serialized array
        if ($plugin == -1) {
            $plugin = "";
        }

        if (!isset($results['pricedata'][$keys[0]])) {
            $results['pricedata'][$keys[0]] = array();
        }

        $results['pricedata'][$keys[0]]['registrar'] = $plugin;

        include_once "modules/admin/models/Package.php";
        $package = new Package($productId);
        $package->pricing = serialize($results);
        $package->save();
    }

    /**
     * saving price of new period
     * @param  int $productId
     * @param  string $tld
     * @param  string $period
     * @param  float $renew
     * @param  float $transfer
     * @param  float $price
     * @return void
     */
    function savePricesForPeriod($productId, $period, $renew, $transfer, $price, $currency = 'default')
    {
        $defaultCurrency = $this->settings->get('Default Currency');

        if ($currency === 'default') {
            $currency = $defaultCurrency;
        }

        $results = $this->_getDomainPricingForProduct($productId, $currency);

        if (isset($results['pricedata'])) {
            $keys = array_keys($results['pricedata']);
        } else {
            $keys = array();
        }

        if (isset($results['pricedata'][$keys[0]][$period])) {
            unset($results['pricedata'][$keys[0]][$period]);
        }

        //let's add the new price
        $newPrice = array();
        $newPrice['period']    = $period;
        $newPrice['period_id'] = $period;
        $newPrice['price']     = $price;
        $newPrice['transfer']  = $transfer;
        $newPrice['renew']     = $renew;

        $results['pricedata'][$keys[0]][$period] = $newPrice;

        include_once 'modules/billing/models/Prices.php';
        $prices = new Prices();
        $prices->setPricing(PRODUCT_PRICE, $productId, $currency, serialize($results));

        if ($currency == $defaultCurrency) {
            include_once "modules/admin/models/Package.php";
            $package = new Package($productId);
            $package->pricing = serialize($results);
            $package->save();
        }
    }


    /**
     * Returns array containing the periods we support
     * @param string $tld optional
     * @return array
     */
    function getAvailablePeriods($productId, $period, $currency = 'default')
    {
        $defaultCurrency = $this->settings->get('Default Currency');

        if ($currency === 'default') {
            $currency = $defaultCurrency;
        }

        $retArray = array();
        $usedArray = array();

        if ($productId !== 0) {
            $results = $this->_getDomainPricingForProduct($productId, $currency);

            if (@$results['pricedata']) {
                $keys = array_keys($results['pricedata']);

                foreach ($results['pricedata'][$keys[0]] as $key => $value) {
                    if (is_array($value)) {
                         $usedArray[]= (int)$key;
                    }
                }
            }
        }

        if ($period === "none") {
            $period = -1;
        } else {
            $period = intval($period);
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

        foreach ($billingcycles as $billingCycleId => $billingCycleData) {
            if (($billingCycleId === 0 || $billingCycleData['time_unit'] == 'y') && (!in_array($billingCycleId, $usedArray) || $period == $billingCycleId)) {
                $retArray[] = array(
                    'period' => $billingCycleId,
                    'name'   => $billingCycleData['name']
                );
            }
        }

        return ($retArray);
    }

    /**
     * Returns tld information for a specific TLD and productId
     * @param  int $productId productId
     * @param  string $tld    TLD to get info for
     * @return array()
     */
    function getTLDData($productId, $currency = 'default')
    {
        $defaultCurrency = $this->settings->get('Default Currency');

        if ($currency === 'default') {
            $currency = $defaultCurrency;
        }

        $results = $this->_getDomainPricingForProduct($productId, $currency);

        $keys = array();
        if (isset($results['pricedata']) && is_array($results['pricedata'])) {
            $keys = array_keys($results['pricedata']);
        }

        if (isset($results['pricedata']) && isset($results['pricedata'][$keys[0]])) {
            return $results['pricedata'][$keys[0]];
        } else {
            return array();
        }
    }

    /**
     * Delete TLD pricing periods
     * @param  array $ids tld|period
     * @return void
     */
    function deleteTldPeriod($productId, $periods, $currency = 'default')
    {
        $defaultCurrency = $this->settings->get('Default Currency');

        if ($currency === 'default') {
            $currency = $defaultCurrency;
        }

        $results = $this->_getDomainPricingForProduct($productId, $currency);
        $keys = array_keys($results['pricedata']);
        foreach ($periods as $period) {
            if (isset($results['pricedata'][$keys[0]][$period])) {
                unset($results['pricedata'][$keys[0]][$period]);
            }
        }

        include_once 'modules/billing/models/Prices.php';
        $prices = new Prices();
        $prices->setPricing(PRODUCT_PRICE, $productId, $currency, serialize($results));

        if ($currency == $defaultCurrency) {
            include_once "modules/admin/models/Package.php";
            $package = new Package($productId);
            $package->pricing = serialize($results);
            $package->save();
        }
    }

    /**
     * Update a domain product to set a Late Fee
     * @param  int $productId
     * @param  float $latefee
     * @return void
     */
    public function updateDomainLateFee($productId, $latefee, $currency = 'default')
    {
        $defaultCurrency = $this->settings->get('Default Currency');

        if ($currency === 'default') {
            $currency = $defaultCurrency;
        }

        $results = $this->_getDomainPricingForProduct($productId, $currency);
        $results['latefee'] = $latefee;

        include_once 'modules/billing/models/Prices.php';
        $prices = new Prices();
        $prices->setPricing(PRODUCT_PRICE, $productId, $currency, serialize($results));

        if ($currency == $defaultCurrency) {
            include_once "modules/admin/models/Package.php";
            $package = new Package($productId);
            $package->pricing = serialize($results);
            $package->save();
        }
    }

    /**
     * Update a domain product to specify if they should be taxable
     * @param  int $productId
     * @param  bool $taxable
     * @return void
     */
    public function updateDomainTax($productId, $taxable, $currency = 'default')
    {
        $defaultCurrency = $this->settings->get('Default Currency');

        if ($currency === 'default') {
            $currency = $defaultCurrency;
        }

        $results = $this->_getDomainPricingForProduct($productId, $currency);

        if (!$taxable) {
            unset($results['taxable']);
        } else {
            $results['taxable'] = $taxable;
        }

        include_once 'modules/billing/models/Prices.php';
        $prices = new Prices();
        $prices->setPricing(PRODUCT_PRICE, $productId, $currency, serialize($results));

        if ($currency == $defaultCurrency) {
            include_once "modules/admin/models/Package.php";
            $package = new Package($productId);
            $package->pricing = serialize($results);
            $package->save();
        }
    }

    /**
     * returns the late fee if any for the domain product
     * @param  int $productId
     * @return float $latefee
     */
    public function getLateFeeForProductId($productId, $currency = 'default')
    {
        $defaultCurrency = $this->settings->get('Default Currency');

        if ($currency === 'default') {
            $currency = $defaultCurrency;
        }

        $results = $this->_getDomainPricingForProduct($productId, $currency);
        if (isset($results['latefee'])) {
            return $results['latefee'];
        } else {
            return '';
        }
    }

    /**
     * returns if the domain product is taxable
     * @param  int $productId
     * @return bool
     */
    public function getTaxableForProductId($productId, $currency = 'default')
    {
        $defaultCurrency = $this->settings->get('Default Currency');

        if ($currency === 'default') {
            $currency = $defaultCurrency;
        }

        $results = $this->_getDomainPricingForProduct($productId, $currency);
        if (!isset($results['taxable'])) {
            return false;
        } else {
            return $results['taxable'];
        }
    }

    /**
     * returns unserialized pricing for product for domains
     * @param  [type] $productId [description]
     * @return [type]            [description]
     */
    private function _getDomainPricingForProduct($productId, $currency = 'default')
    {
        $defaultCurrency = $this->settings->get('Default Currency');

        if ($currency === 'default') {
            $currency = $defaultCurrency;
        }

        // Query the product
        $sql      = "SELECT promotion.type as type, package.pricing as pricing FROM package, promotion WHERE package.planid = promotion.id AND promotion.type=3 AND package.id = ?";
        $result   = $this->db->query($sql, $productId);
        $num_rows = $result->getNumRows();

        // Check we got something back only if passing productid
        if (!$num_rows) {
            throw new Exception("Product ID given could not be found");
        }

        // Get the results
        $results = $result->fetch();
        $pricing = @unserialize($results['pricing']);

        if ($currency != $defaultCurrency) {
            include_once 'modules/billing/models/Prices.php';
            $prices = new Prices();
            $currencyPricing = $prices->getPricing(PRODUCT_PRICE, $productId, $currency, $results['pricing']);

            if ($currencyPricing !== false) {
                // Unserialize the array locally
                $pricing = @unserialize($currencyPricing);
            } else {
                $pricing = array();
            }
        }

        return $pricing;
    }

    /**
     * Function to get a list of TLD's and associated pricing data from a given product
     *
     * @param int $productId value of the product ID we want the TLD and pricing
     *
     * @return array
     */
    function getTldAndPricing($productId)
    {
        $results = $this->_getDomainPricingForProduct($productId);

        $finalData          = array();

        // Format the array
        if (@$results['pricedata']) {
            foreach ($results['pricedata'] as $key => $value) {
                // Get the registrar from the array and remove it
                if (isset($value['registrar'])) {
                    $registrar = $value['registrar'];
                } else {
                    $registrar = "";
                    unset($value['registrar']);
                }

                // Fix for invalid TLD's
                // Period = 0 is now a valid value, used for One Time payment.
                $finalTLDs = array();
                foreach ($value as $tldPrice) {
                    if (!isset($tldPrice['period']) || $tldPrice['period'] == '') {
                        continue;
                    } else {
                        $finalTLDs[$tldPrice['period']] = $tldPrice;
                    }
                }

                $finalData[] = array(
                    'tld'       => $key,
                    'pricing'   => @array_values($finalTLDs),
                    'registrar' => $registrar,
                    'isDefault' => @$value['isDefault']
                );
            }
        }

        return array(
            'data' => $finalData
        );
    }


    /**
     * Get list of registrar plugins
     *
     * @return array
     */
    function getRegistrarPlugins()
    {
        $pluginList = array();
        $pluginList[] = array('internalname' => '-1', 'name' => '----');
        include_once 'library/CE/NE_PluginCollection.php';
        $registrar_plugins = new NE_PluginCollection('registrars', $this->user);
        while ($plugin = $registrar_plugins->getNext()) {
            $registrarVariables = $registrar_plugins->callFunction($plugin->getInternalName(), 'getVariables');
            $pluginList[] = array('internalname' => $plugin->getInternalName(), 'name' => $registrarVariables['Plugin Name']['value']);
        }
        return $pluginList;
    }


    function get_all_tlds_in_product_group($currencyCode, $group_id, $allowAll = false)
    {
        include_once 'modules/billing/models/Prices.php';
        $prices = new Prices();

        $result = $this->db->query("SELECT id, planname, pricing FROM package WHERE planid = ?", $group_id);
        $namesuggestTLds = array();
        $rawTlds = array();

        while ($row = $result->fetch()) {
            $package = new Package($row['id']);
            $advanced = unserialize($package->advanced);
            if ($advanced['enableNamesuggest'] == 1 || ( $allowAll == true && $package->showpackage == 1 )) {
                $pricing = $prices->getPricing(PRODUCT_PRICE, $row['id'], $currencyCode, $row['pricing']);
                $namesuggestTLds[$row['planname']] = array(
                    "product_id" => $row['id'],
                    "tld" => $row['planname'],
                    "pricing" => $pricing
                );
                $rawTlds[] = $row['planname'];
            }
        }

        return array(
            "tldmap" => $namesuggestTLds,
            "tlds" => $rawTlds
        );
    }

    /**
    * @return int possible return values:
    *       -1:    Domain name already has an account in this system
    *       0:     Domain available
    *       1:     Domain already registered
    *       2:     Registrar Error, domain extension not recognized or supported
    *       3:     Domain invalid
    *       5:     Could not contact registry to look up domain
    */
    function search_domain($name, $tld, $package_id, $searchType = 'register', $cartParentPackageId = 0, $cartParentPackageTerm = false)
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

        include_once 'modules/admin/models/Package.php';
        // Start logging
        CE_Lib::log(4, "Checking Domain ('".$name."', '".$tld."')");

        $return_array = array();
        $return_array['status'] = "2";
        $return_array['available_count'] = 0;

        //let's see if we have currency setup
        if (isset($this->session->currency)) {
            $currency = base64_decode($this->session->currency);
        } elseif ($this->user->getCurrency() != 0) {
            $currency = $this->user->getCurrency();
        } else {
            $currency = $this->settings->get('Default Currency');
        }

        $currencys = new Currency($this->user);

        // Get the TLD's from the product
        $package = new Package($package_id);
        $package->getProductPricingAllCurrencies();
        $tldData = $package->pricingInformationCurrency[$currency];

        //let's make sure we have pricing configured
        if (!isset($tldData['pricedata']) || !is_array($tldData['pricedata'])) {
            throw new CE_Exception("Pricing for tld has not been configured yet.");
        }

        $tldData = $tldData['pricedata'];
        $pricing_keys = array_keys($tldData);
        $tldPlugin = $tldData[$pricing_keys[0]]['registrar'];

        if ($package->advanced != "") {
            $advancedSettings = unserialize($package->advanced);
        } else {
            $advancedSettings = array();
        }

        // Set the namesuggest return
        $maxNameSuggestResults = (isset($advancedSettings['maxNamesuggest'])) ? $advancedSettings['maxNamesuggest'] : '5';
        $namesuggestTLds = $this->get_all_tlds_in_product_group($currency, $package->planid);
        if ($searchType == 'transfer' || !isset($advancedSettings['enableNamesuggest'])) {
            // since we are doing a transfer, we want to get rid of the rest of the tlds, so we only show the one tld.
            foreach ($namesuggestTLds['tldmap'] as $key => $map) {
                if ($key != $package->planname) {
                    unset($namesuggestTLds['tldmap'][$key]);
                }
            }
            foreach ($namesuggestTLds['tlds'] as $key => $t) {
                if ($t != $package->planname) {
                    unset($namesuggestTLds['tlds'][$key]);
                }
            }
        }

        include_once 'modules/admin/models/ActiveOrderGateway.php';
        $aogateway = new ActiveOrderGateway($this->user);
        $userPackageGateway = new UserPackageGateway($this->user);

        if ($tldPlugin == "") {
            // Check forbidden domain
            if ($userPackageGateway->CheckDomainForbidden($name)) {
                $domains = [
                    'tld' => $tld,
                    'domain' => $name,
                    'status' => -1
                ];
                $domainStatus = [0 => $domains];
            } else {
                // no Plugin, so we check via cWhois
                CE_Lib::log(4, 'Checking domain using cWhois');
                include_once 'library/CE/3rdparty/cWhois/cwhois.php';
                $Reg = "*";
                $domainStatus = cWhois($name, '.' . $tld, $Reg);
                $domains = array('tld' => $tld, 'domain' => $name, 'status' => $domainStatus);
                $domainStatus = array(0 => $domains);
            }
        } else {
            // Check the domain with the plugins function
            CE_Lib::log(4, "Checking domain using ".$tldPlugin);

            // Load the plugin
            include_once 'modules/admin/models/PluginGateway.php';
            $pluginGateway = new PluginGateway();
            $plugin = $pluginGateway->getPluginByName('registrars', $tldPlugin);

            $registrarVariables = $plugin->getVariables();

            $params = array();

            foreach ($registrarVariables as $key => $var) {
                $settingname = "plugin_".$tldPlugin."_".$key;
                $params = array_merge($params, array( $key => $this->settings->get($settingname) ));
            }

            // Build the params
            $allAvailableTLDs = $this->get_all_tlds_in_product_group($currency, $package->planid, true);
            $params = array_merge(
                $params,
                array(
                    'tld'   => $tld,
                    'sld'   => $name,
                    'namesuggest' => $namesuggestTLds['tlds'],
                    'allAvailableTLDs' => $allAvailableTLDs['tlds'],
                    'enableNamespinner' => false
                )
            );

            if ($advancedSettings['enableNamespinner'] == 1) {
                $params['enableNamespinner'] = true;
            }

            // Get the response & catch any exceptions
            $response = $plugin->checkDomain($params);

            if (!isset($advancedSettings['enableNamesuggest']) || $advancedSettings['enableNamesuggest'] == 0 || $searchType == 'transfer') {
                $plugin->features['nameSuggest'] = false;
            }

            // Parse the results
            if (isset($response['result'])) {
                if (isset($advancedSettings['enableNamesuggest']) && $advancedSettings['enableNamesuggest'] == 1 && $searchType == 'register') {
                    $return_array['domainNameSuggest'] = true;
                }
                $domainStatus = $response['result'];
            } else {
                // Log & set an error
                throw new CE_Exception($this->user->lang("There was an error contacting the registrar, please try again!"));
                CE_Lib::log(4, "CheckDomain::callfunction FAILED result: " . print_r($response, true));
            }
        }

        if ($cartParentPackageId != 0 && $cartParentPackageTerm !== false) {
            $parentPackage = new Package($cartParentPackageId);
            $parentPackage->getProductPricing();
            $cartParentPackageFreeDomain = $parentPackage->getFreeDomainInfo($cartParentPackageTerm, 'freedomain');
            $cartParentPackageDomainExtension = $parentPackage->getFreeDomainInfo($cartParentPackageTerm, 'domainextension');
            $cartParentPackageDomainCycle = $parentPackage->getFreeDomainInfo($cartParentPackageTerm, 'domaincycle');
        } else {
            $cartParentPackageFreeDomain = 0;
            $cartParentPackageDomainExtension = array();
            $cartParentPackageDomainCycle = array();
        }

        // Parse the domain status here rather than tiwce above
        if (is_array($domainStatus)) {
             // Loop the returned domains
            $tldCount = 1;
            $return_array['available_options'] = array();
            $allTLDPricing = $this->get_all_tlds_in_product_group($currency, $package->planid, true);

            foreach ($domainStatus as $key) {
                if ($userPackageGateway->CheckDomainForbidden($key['domain'])) {
                    $key['status'] = -1;
                }

                // only show if we are less then max name suggest OR it's the TLD we searched for.
                if ($tldCount <= $maxNameSuggestResults || ( $key['tld'] == $tld && $key['domain'] == $name)) {
                    $pricing_for_tld = $allTLDPricing['tldmap'];
                    $tld_product_id = $pricing_for_tld[$key['tld']]['product_id'];
                    $tld_product = new Package($tld_product_id);

                    if ($pricing_for_tld[$key['tld']]['pricing'] == "") {
                        CE_Lib::log(3, "Domain search result was returned for a tld that doesn't have pricing configured 1");
                        continue;
                    }

                    $pricing_for_tld = unserialize($pricing_for_tld[$key['tld']]['pricing']);

                    $pricing_for_tld = $pricing_for_tld['pricedata'];
                    if (!is_array($pricing_for_tld) || count($pricing_for_tld) == 0) {
                        CE_Lib::log(3, "Domain search result was returned for a tld that doesn't have pricing configured 2");
                        continue;
                    }

                    $workingArray = array();
                    $workingArray['price'] = array();
                    $workingArray['tld'] = $key['tld'];
                    $workingArray['domain_name'] = $key['domain'].".".$key['tld'];

                    //if this is the result for the tld searched
                    //add to root status field
                    if ($key['tld'] == $tld && $name == $key['domain']) {
                        $return_array['status'] = $key['status'];
                    }

                    $workingArray['status'] = $key['status'];
                    if (($searchType == 'register' && $key['status'] == 0) || ($searchType == 'transfer' && $key['status']  == 1)) {
                        $return_array['available_count']++;
                    } else {
                        continue;
                    }

                    // Handle the pricing
                    $pricingArray = array();
                    $registrar = null;

                    foreach ($pricing_for_tld as $keys) {
                        $new_period = array();
                        if (is_array($keys)) {
                            $registrar = $keys['registrar'];
                            unset($keys['registrar']);
                            unset($keys['isDefault']);
                            ksort($keys);

                            foreach ($keys as $period) {
                                $originalPeriod = $period['period'];
                                $period['period'] = $billingcycles[$period['period']]['name'];

                                if ($searchType == 'transfer' && $period['transfer'] == '') {
                                    continue;
                                }

                                // If free domain, set price to 0
                                if ($cartParentPackageFreeDomain > 0 && in_array($tld_product_id, $cartParentPackageDomainExtension) && in_array($originalPeriod, $cartParentPackageDomainCycle)) {
                                    $period['price'] = 0;
                                    $period['transfer'] = "0";

                                    //If set to Recurring, set renew price to 0
                                    if ($cartParentPackageFreeDomain == 2) {
                                        $period['renew'] = 0;
                                    }
                                }

                                $period['formated_price'] = $currencys->format($currency, $period['price'], true);
                                $period['formated_transfer'] = $currencys->format($currency, $period['transfer'], true);
                                $period['formated_renew'] = $currencys->format($currency, $period['renew'], true);

                                $new_period[] = $period;
                            }
                            //$pricingArray[] = array_values($keys);
                            $pricingArray[] = $new_period;
                        }
                    }
                    // if transfer, and no valid pricing, just say it's not available
                    if ($searchType == 'transfer' && count($pricingArray[0]) == 0) {
                        $return_array['available_count'] = 0;
                        $return_array['status'] = 0;
                    }

                    //if we don't have a registrar then let's just continue and not include this one
                    //if ($registrar == "") continue;

                    $workingArray['price'] = array_pop($pricingArray);
                    if (count($workingArray['price']) == 0) {
                        continue;
                    }
                    $workingArray['plugin'] = $registrar;
                    $workingArray['searchedFor'] = false;
                    $workingArray['product_id'] = $tld_product_id;
                    $workingArray['additional_options'] = $aogateway->getCustomFields('package', false, "", $tld_product, $tld_product->productGroup->fields['id']);

                    if ($searchType == 'transfer' && !in_array($key['tld'], ['es', 'uk', 'co.uk', 'org.uk', 'me.uk'])) {
                        // add epp code
                        $eppCodeArray = array();
                        $eppCodeArray['id'] = 'eppCode';
                        $eppCodeArray['name'] = 'EPP Code';
                        $eppCodeArray['fieldtype'] = '0';
                        $eppCodeArray['ischangeable'] = 1;
                        $eppCodeArray['isrequired'] = 0;
                        $eppCodeArray['description'] = $this->user->lang('Please enter the EPP code for this domain');
                        $eppCodeArray['value'] = '';
                        $workingArray['additional_options']['customFields'][] = $eppCodeArray;

                        if ($this->settings->get('Force Transfer Checklist')) {
                            $tempArray = array();
                            $tempArray['id'] = 'domain_unlocked';
                            $tempArray['name'] = $this->user->lang("Is the domain unlocked?");
                            $tempArray['fieldtype'] = '53';
                            $tempArray['ischangeable'] = 1;
                            $tempArray['isrequired'] = 1;
                            $tempArray['description'] = $this->user->lang("Please ensure your domain is not locked.");
                            $tempArray['value'] = '';
                            $workingArray['additional_options']['customFields'][] = $tempArray;

                            $tempArray = array();
                            $tempArray['id'] = 'private_registration';
                            $tempArray['name'] = $this->user->lang("Is private registration turned off?");
                            $tempArray['fieldtype'] = '53';
                            $tempArray['ischangeable'] = 1;
                            $tempArray['isrequired'] = 1;
                            $tempArray['description'] = $this->user->lang("Please ensure private registration is not turned on for your domain.");
                            $tempArray['value'] = '';
                            $workingArray['additional_options']['customFields'][] = $tempArray;

                            $tempArray = array();
                            $tempArray['id'] = 'email_address';
                            $tempArray['name'] = $this->user->lang("Do you have access to the email address listed as the registrant?");
                            $tempArray['fieldtype'] = '53';
                            $tempArray['ischangeable'] = 1;
                            $tempArray['isrequired'] = 1;
                            $tempArray['description'] = $this->user->lang("Please ensure that you have access to the email address that is listed as the registrant.");
                            $tempArray['value'] = '';
                            $workingArray['additional_options']['customFields'][] = $tempArray;
                        }
                    }

                    $firstCycleSet = false;

                    foreach ($billingcycles as $billingCycleId => $billingCycleData) {
                        if ($billingCycleData['time_unit'] == 'y') {
                            if (!$firstCycleSet) {
                                $workingArray['additional_options']['addons'] = $aogateway->getAddons($tld_product_id, $billingCycleId);
                                $firstCycleSet = true;
                            }

                            $workingArray['additional_options']['addons'.$billingCycleId] = $aogateway->getAddons($tld_product_id, $billingCycleId);
                        }
                    }

                    $workingArray['additional_options']['addons0'] = $aogateway->getAddons($tld_product_id, 0);

                    // Handle the extra attributes
                    $extraAttributes = $tld_product->getExtraAttributes($workingArray['tld'], $this->user);
                    $attributesArray = array();
                    if (@is_array($extraAttributes)) {
                        $attributesArray['tld'] = $workingArray['tld'];
                        if ($workingArray['tld'] == $tld) {
                            $attributesArray['visible'] = true;
                        } else {
                            $attributesArray['visible'] = false;
                        }
                        $attributesArray['attributes'] = $extraAttributes;
                    }
                    $workingArray['additional_options']['extra_attributes'] = $attributesArray;

                    // Add the searched for TLD first
                    if ($workingArray['tld'] == $tld && $key['domain'] == $name) {
                        $workingArray['searchedFor'] = true;
                        $return_array['available_options'] = array_pad($return_array['available_options'], -(count($return_array['available_options']) + 1), $workingArray);
                    } else {
                        // Add it normally
                        $return_array['available_options'][] = $workingArray;
                    }
                    // increment name suggest counter
                    $tldCount++;
                }
            }
            // Unlock the submit button
            $return_array['submitButton'] = "";
        }
        return $return_array;
    }
}
