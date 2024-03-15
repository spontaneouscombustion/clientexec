<?php
/**
 * Monthly Income By Type Report
 *
 * @category Report
 * @package  ClientExec
 * @author   Jason Yates <jason@clientexec.com>
 * @license  ClientExec License
 * @version  1.2
 * @link     http://www.clientexec.com
 *
 *************************************************
 *   1.0 Initial Report Released.  - Alberto Vasquez
 *   1.2 Updated report to include a title & PEAR commenting
 ************************************************
 */

require_once 'modules/billing/models/Currency.php';
require_once 'modules/billing/models/BillingType.php';

/**
 * Monthly_Income_By_Type Report Class
 *
 * @category Report
 * @package  ClientExec
 * @author   Jason Yates <jason@clientexec.com>
 * @license  ClientExec License
 * @version  1.2
 * @link     http://www.clientexec.com
 */
class Monthly_Income_By_Type extends Report
{
    private $lang;

    protected $featureSet = 'billing';
    public $hasgraph = true;

    function __construct($user = null, $customer = null)
    {
        $this->lang = lang('Monthly Income By Type');
        parent::__construct($user, $customer);
    }

    /**
     * Report Process Method
     *
     * @return null - direct output
     */
    function process()
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

        include_once 'modules/admin/models/StatusAliasGateway.php' ;

        // Set the report information
        $this->SetDescription($this->user->lang('Displays total recurring transactions broken down by package types with the sum and expected monthly income from each.'));

        // Load the currency information
        $currency = new Currency($this->user);

        $currencyCode = ((isset($_REQUEST['currencycode']))? $_REQUEST['currencycode'] : $this->settings->get('Default Currency'));
        $currencyName = $currency->getName($currencyCode);

        //Get all currencies of all users
        $currenciesSQL = "SELECT DISTINCT c.`abrv`, c.`name` "
            ."FROM `users` u "
            ."INNER JOIN `currency` c ON c.`abrv` = u.`currency` "
            ."ORDER BY c.`name` ASC ";
        $currenciesResult = $this->db->query($currenciesSQL);

        $filter = '<form id="report" method="GET">'
            .'    <div style="text-align:center">'
            .'        '.$this->user->lang('Currency').': '
            .'        <select name="currencycode" id="currencycode" value="'.CE_Lib::viewEscape($currencyCode).'" onChange="ChangeTable(this.value);"> ';

        $isSelectedCurrencyInTheList = false;
        while (list($singleCurrencyCode, $singleCurrencyName) = $currenciesResult->fetch()) {
            if (!$isSelectedCurrencyInTheList && $currencyName < $singleCurrencyName) {
                $filter .= '<option value="'.$currencyCode.'" selected>'.$currencyName.'</option>';
                $isSelectedCurrencyInTheList = true;
            } elseif ($currencyCode == $singleCurrencyCode) {
                $isSelectedCurrencyInTheList = true;
            }
            $filter .= '<option value="'.$singleCurrencyCode.'" '.(($currencyCode == $singleCurrencyCode)? 'selected' : '').'>'.$singleCurrencyName.'</option>';
        }
        if (!$isSelectedCurrencyInTheList) {
            $filter .= '<option value="'.$currencyCode.'" selected>'.$currencyName.'</option>';
            $isSelectedCurrencyInTheList = true;
        }

        $filter .= '</select>';
        $filter .= '    </div>'
            .'</form>';

        $graphdata = @$_GET['graphdata'];

        $userStatuses = StatusAliasGateway::userActiveAliases($this->user);
        $packageStatuses = StatusAliasGateway::packageActiveAliases($this->user);

        //SQL to generate the the result set of the report
        $sql = "SELECT COUNT(*) AS counted, "
            ."d.Plan, "
            ."rf.paymentterm, "
            ."d.use_custom_price, "
            ."d.custom_price, "
            ."ocf.value AS domain_name, "
            ."u.currency "
            ."FROM domains d "
            ."LEFT JOIN `recurringfee` rf ON rf.appliestoid = d.id AND rf.billingtypeid = -1 "
            ."LEFT JOIN object_customField ocf ON ocf.objectid = d.id AND ocf.customFieldId = (SELECT cf.id "
                ."FROM customField cf "
                ."WHERE groupId = 2 "
                ."AND subGroupId  = 3 "
                ."AND name  = 'Domain Name'), "
            ."users u "
            ."WHERE rf.paymentterm <> 0 "
            ."AND IFNULL(rf.recurring, 0) <> 0 "
            ."AND d.customerid = u.id "
            ."AND u.status IN(".implode(', ', $userStatuses).") "
            ."AND d.status IN(".implode(', ', $packageStatuses).") "
            ."GROUP BY u.currency, rf.paymentterm, d.Plan, d.use_custom_price, d.custom_price "
            ."ORDER BY u.currency ";
        $result = $this->db->query($sql);

        $expectedincomeTotal = array();
        $sumpertotalpackagesTotal = array();

        include_once 'modules/billing/models/Prices.php';
        $prices = new Prices();

        // Just in case we have domains
        $dng = new DomainNameGateway($this->user);

        $aGroup = array();

        while ($row = $result->fetch()) {
            $tCurrency = $row['currency'];

            $inSQL="SELECT t.name, "
                ."p.planname, "
                ."p.pricing AS price, "
                ."t.type "
                ."FROM package p, "
                ."promotion t "
                ."WHERE p.id = ? "
                ."AND t.id = p.planid ";
            $result2 = $this->db->query($inSQL, $row['Plan']);

            while ($row2 = $result2->fetch()) {
                //Now we do the math, and add the class array and variables
                $row2['price'] = $prices->getPricing(PRODUCT_PRICE, $row['Plan'], $tCurrency, $row2['price']);
                $pricing = unserialize($row2['price']);

                $packagePrice = 0;

                if (is_array($pricing)) {
                    if ($row2['type'] == 3) {  // Domain Type
                        $aDomainName = $dng->splitDomain($row['domain_name']);
                        $tld = strtolower($aDomainName[1]);

                        $pricingInformation = array();

                        foreach ($pricing as $key => $value) {
                            $pricingInformation[$key] = $value;
                        }

                        $pricingArray = array_pop($pricingInformation['pricedata']);

                        if ($row['paymentterm'] != 0 && $billingcycles[$row['paymentterm']]['time_unit'] == 'y' && $billingcycles[$row['paymentterm']]['amount_of_units'] != 0 && isset($pricingArray[$row['paymentterm']]['price'])) {
                            $packagePrice = ($pricingArray[$row['paymentterm']]['price']/$billingcycles[$row['paymentterm']]['amount_of_units'])/12;
                        }
                    } else {
                        if ($row['paymentterm'] != 0 &&  $billingcycles[$row['paymentterm']]['amount_of_units'] != 0 && isset($pricing['price'.$row['paymentterm']]) && is_numeric($pricing['price'.$row['paymentterm']])) {
                            switch ($billingcycles[$row['paymentterm']]['time_unit']) {
                                case 'd':
                                    $packagePrice = ($pricing['price'.$row['paymentterm']]/$billingcycles[$row['paymentterm']]['amount_of_units'])*30;
                                    break;
                                case 'w':
                                    $packagePrice = (($pricing['price'.$row['paymentterm']]/$billingcycles[$row['paymentterm']]['amount_of_units'])/7)*30;
                                    break;
                                case 'm':
                                    $packagePrice = $pricing['price'.$row['paymentterm']]/$billingcycles[$row['paymentterm']]['amount_of_units'];
                                    break;
                                case 'y':
                                    $packagePrice = ($pricing['price'.$row['paymentterm']]/$billingcycles[$row['paymentterm']]['amount_of_units'])/12;
                                    break;
                            }
                        }
                    }
                }

                //this is for overrided prices
                if ($row['paymentterm'] != 0 && $row["use_custom_price"] && $billingcycles[$row['paymentterm']]['amount_of_units'] != 0) {
                    switch ($billingcycles[$row['paymentterm']]['time_unit']) {
                        case 'd':
                            $packagePrice = ($row["custom_price"]/$billingcycles[$row['paymentterm']]['amount_of_units'])*30;
                            break;
                        case 'w':
                            $packagePrice = (($row["custom_price"]/$billingcycles[$row['paymentterm']]['amount_of_units'])/7)*30;
                            break;
                        case 'm':
                            $packagePrice = $row["custom_price"]/$billingcycles[$row['paymentterm']]['amount_of_units'];
                            break;
                        case 'y':
                            $packagePrice = ($row["custom_price"]/$billingcycles[$row['paymentterm']]['amount_of_units'])/12;
                            break;
                    }
                }

                $tMonthlyIncome = $packagePrice * $row['counted'];
                $tPackageType = $row2['name'];
                $tPackageCount = $row['counted'];

                if (isset($expectedincomeTotal[$tCurrency])) {
                    $expectedincomeTotal[$tCurrency] += $tMonthlyIncome;
                } else {
                    $expectedincomeTotal[$tCurrency] = $tMonthlyIncome;
                }

                if (isset($sumpertotalpackagesTotal[$tCurrency])) {
                    $sumpertotalpackagesTotal[$tCurrency] += $tPackageCount;
                } else {
                    $sumpertotalpackagesTotal[$tCurrency] = $tPackageCount;
                }

                if (isset($aGroup[$tCurrency][$tPackageType])) {
                    $tArray = $aGroup[$tCurrency][$tPackageType];
                    $aGroup[$tCurrency][$tPackageType] = array($tPackageType,$tArray[1]+$tPackageCount,$tArray[2]+$tMonthlyIncome);
                } else {
                    $aGroup[$tCurrency][$tPackageType] = array($tPackageType,$tPackageCount,$tMonthlyIncome);
                }

                // NOTE: remember the addon cycle can be different than the package's
                $sql = "SELECT COUNT(*) AS counted, "
                    ."SUM(rf.amount * rf.quantity) AS total, "
                    ."rf.paymentterm "
                    ."FROM recurringfee rf "
                    ."LEFT JOIN users u ON u.id = rf.customerid "
                    ."LEFT JOIN (domains d "
                        ."LEFT JOIN `recurringfee` rrff ON rrff.appliestoid = d.id AND rrff.billingtypeid = -1) "
                        ."ON rf.appliestoid = d.id "
                    ."WHERE rf.billingtypeid = ? "
                    ."AND d.Plan = ? "
                    ."AND d.status IN(".implode(', ', $packageStatuses).") "
                    ."AND rrff.paymentterm = ? "
                    ."AND u.currency = ? "
                    ."GROUP BY rf.paymentterm ";
                $result3 = $this->db->query($sql, BILLINGTYPE_PACKAGE_ADDON, $row['Plan'], $row['paymentterm'], $tCurrency);

                $groupName = "* $tPackageType add-ons";

                while ($row3 = $result3->fetch()) {
                    $tAddonCount = $row3['counted'];

                    //get expected monthly based on term
                    if ($row3['paymentterm'] != 0 && $billingcycles[$row3['paymentterm']]['amount_of_units'] != 0) {
                        switch ($billingcycles[$row3['paymentterm']]['time_unit']) {
                            case 'd':
                                $row3['total'] = ($row3['total']/$billingcycles[$row3['paymentterm']]['amount_of_units'])*30;
                                break;
                            case 'w':
                                $row3['total'] = (($row3['total']/$billingcycles[$row3['paymentterm']]['amount_of_units'])/7)*30;
                                break;
                            case 'm':
                                $row3['total'] = $row3['total']/$billingcycles[$row3['paymentterm']]['amount_of_units'];
                                break;
                            case 'y':
                                $row3['total'] = ($row3['total']/$billingcycles[$row3['paymentterm']]['amount_of_units'])/12;
                                break;
                        }
                    } else {
                        continue;
                    }
                    $tExpectedIncome = $currency->format($tCurrency, $row3['total'], true, false);

                    if (isset($expectedincomeTotal[$tCurrency])) {
                        $expectedincomeTotal[$tCurrency] += $row3['total'];
                    } else {
                        $expectedincomeTotal[$tCurrency] = $row3['total'];
                    }

                    if (isset($aGroup[$tCurrency][$groupName])) {
                        $tArray = $aGroup[$tCurrency][$groupName];
                        $aGroup[$tCurrency][$groupName] = array($groupName, $tArray[1] + $tAddonCount, $tArray[2] + $row3['total']);
                    } else {
                        $aGroup[$tCurrency][$groupName] = array($groupName, $tAddonCount, $row3['total']);
                    }
                }
            }
        }

        if ($graphdata) {
            //this supports lazy loading and dynamic loading of graphs
            $this->reportData = $this->GraphData($aGroup[$currencyCode], $currencyCode);
            return;
        }

        $aGroupIds = array();

        if (isset($aGroup)) {
            foreach ($aGroup as $currencyKey => $aCurrency) {
                $grouphidden = true;
                $groupid = "id-" . $currencyKey;

                if ($currencyCode == $currencyKey) {
                    $grouphidden = false;
                }

                //Loop through group array and change price format now that all the sums have been made
                $aGroupWithCurrency = array();

                foreach ($aCurrency as $tGroup) {
                    $aGroupWithCurrency[] = array($tGroup[0], $tGroup[1], $currency->format($currencyKey, $tGroup[2], true));
                }

                $aNewGroup = $aGroupWithCurrency;

                $aNewGroup[] = array("","","");

                $expectedincomeTotal[$currencyKey] = $currency->format($currencyKey, $expectedincomeTotal[$currencyKey], true);
                $aNewGroup[] = array("<b>".$this->user->lang("Totals")."</b>","<b>".$sumpertotalpackagesTotal[$currencyKey]."</b>", "<b>".$expectedincomeTotal[$currencyKey]."</b>");

                $this->reportData[] = array(
                    "group" => $aNewGroup,
                    "groupname" => $this->user->lang("Package Types").' ('.$currencyKey.')',
                    "label" => array($this->user->lang('Package Type'),$this->user->lang('Total Packages'),$this->user->lang('Expected Monthly Income')),
                    "groupId" => $groupid,
                    "isHidden" => $grouphidden
                );

                $aGroupIds[] = $groupid;

                unset($aNewGroup);
            }
        }

        $filter .= '<script type="text/javascript">'
            .'    function ChangeTable(currencycode){';

        foreach ($aGroupIds as $groupId) {
            $filter .= "if (document.getElementById('$groupId') != null) {\n"
                ."    document.getElementById('$groupId').style.display='none';\n"
                ."}\n";
        }

        $filter .= "        if (document.getElementById('id-'+currencycode) != null) {\n"
            ."            document.getElementById('id-'+currencycode).style.display='';\n"
            ."        };\n"
            ."        clientexec.populate_report('Monthly_Income_By_Type-Income','#myChart',{currencycode:currencycode});\n"
            .'    }'
            .'</script>';
        echo $filter;
    }


    /**
     * Function to output the xml for the graph data
     *
     * @return XML - graph data
     */
    function GraphData($aGroups, $currencyCode)
    {
        //get default currency symbol
        $currency = new Currency($this->user);

        $graph_data = array(
              "xScale" => "ordinal",
              "yScale" => "exponential",
              "xType" => "number",
              "yType" => "currency",
              "yPre" => $currency->ShowCurrencySymbol($currencyCode),
              "yFormat" => "addcomma",
              "type" => "bar",
              "main" => array());

        $group_data = array();
        $group_data['className'] = ".report";
        $group_data['data'] = array();

        $index = 0;

        foreach ($aGroups as $group) {
            $pretty_total = $currency->format($currencyCode, $group[2], true);

            $data = array();
            $data["x"] = array($index,substr($group[0], 0, 15));
            $data["y"] = $group[2];
            $data["tip"] = "<strong>".$group[0]."</strong><br/>".$group[1]." Packages for ".$pretty_total;
            $group_data["data"][] = $data;
            $index++;
        }
        $graph_data["main"][] = $group_data;
        return json_encode($graph_data);
    }

    /**
     * Function to return the average, used for making the pie chart
     *
     * @return num - average value
     */
    function ReturnAverage($count, $totalCount)
    {
        $avg = ($count/$totalCount);
        $avg =  $avg * 100;
        return ceil($avg);
    }
}
