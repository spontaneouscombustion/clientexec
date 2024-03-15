<?php
/**
 * Monthly Income Report
 *
 * @category Report
 * @package  ClientExec
 * @author   Jason Yates <jason@clientexec.com>
 * @license  ClientExec License
 * @version  1.5
 * @link     http://www.clientexec.com
 *
 *************************************************
 *   1.0 Initial Report Released.  - Bart Wegrzyn
 *   1.1 Fixed to account for yearly, semi-yearly,  quarterly, and monthly pricing.  - Shane Sammons
 *   1.2 Only show those packages that have recurring prices not all packages.  - Alberto Vasquez
 *   1.3 Only show those packages for active customers.  - Alberto Vasquez
 *   1.4 Refactored to show income from addons as well - Alejandro Pedraza
 *   1.5 Refactored report to adhere to Pear standards & Updated the package names that are shown - Jason Yates
 ************************************************
 */

require_once 'modules/billing/models/Currency.php';
require_once 'modules/billing/models/BillingType.php';
require_once('modules/clients/models/DomainNameGateway.php');

/**
 * Monthly_Income Report Class
 *
 * @category Report
 * @package  ClientExec
 * @author   Jason Yates <jason@clientexec.com>
 * @license  ClientExec License
 * @version  1.5
 * @link     http://www.clientexec.com
 */
class Monthly_Income extends Report
{
    private $lang;

    protected $featureSet = 'billing';

    function __construct($user = null, $customer = null)
    {
        $this->lang = lang('Monthly Income');
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
        $this->SetDescription($this->user->lang('Displays total recurring transactions broken down by packages with the sum and expected monthly income from each.'));

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
            .'</form>'
            .'</br>'
            .'<script type="text/javascript">'
            .'    function ChangeTable(currencycode){'
            .'        location.href="index.php?fuse=reports&view=viewreport&controller=index&report=Monthly+Income&type=Income&currencycode="+currencycode;'
            .'    }'
            .'</script>';
        echo $filter;

        $userStatuses = StatusAliasGateway::userActiveAliases($this->user);
        $packageStatuses = StatusAliasGateway::packageActiveAliases($this->user);

        //SQL to generate the the result set of the report
        $sql = "SELECT COUNT(*) AS counted, "
            ."d.Plan, "
            ."rf.paymentterm, "
            ."d.use_custom_price, "
            ."d.custom_price, "
            ."ocf.value AS domain_name "
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
            ."AND u.currency = ? "
            ."GROUP BY rf.paymentterm, d.Plan, d.use_custom_price, d.custom_price ";
        $result = $this->db->query($sql, $currencyCode);
        $expectedincomeTotal = 0;
        $sumpertotalpackagesTotal = 0;

        include_once 'modules/billing/models/Prices.php';
        $prices = new Prices();

        // Just in case we have domains
        $dng = new DomainNameGateway($this->user);
        while ($row = $result->fetch()) {
            $inSQL = "SELECT p.planname as planname, "
                ."g.name as groupname, "
                ."p.pricing, "
                ."g.type "
                ."FROM package p, "
                ."promotion g "
                ."WHERE p.id = ? "
                ."AND p.planid = g.id ";
            $result2 = $this->db->query($inSQL, $row['Plan']);

            while ($row2 = $result2->fetch()) {
                $append = ' ('.$billingcycles[$row['paymentterm']]['name'].')';

                $row2['pricing'] = $prices->getPricing(PRODUCT_PRICE, $row['Plan'], $currencyCode, $row2['pricing']);
                $pricing = unserialize($row2['pricing']);

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
                        if ($row['paymentterm'] != 0 && $billingcycles[$row['paymentterm']]['amount_of_units'] != 0 && isset($pricing['price'.$row['paymentterm']]) && is_numeric($pricing['price'.$row['paymentterm']])) {
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

                    $append .= " (overridden)";
                }

                $tMonthlyIncome = $packagePrice * $row['counted'];
                //echo "Plan Name: ".
                $tPackageName = $row2['planname']." / ".$row2['groupname'].$append;
                //echo "Quantity: ".
                $tPackageCount = $row['counted'];

                $tExpectedIncome = $currency->format($currencyCode, $tMonthlyIncome, true);
                $expectedincomeTotal += $tMonthlyIncome;
                $sumpertotalpackagesTotal += $tPackageCount;
                $aGroup[] = array($tPackageName,$tPackageCount,$tExpectedIncome);

                // NOTE: remember the addon cycle can be different than the package's
                $sql = "SELECT SUM(rf.amount * rf.quantity) AS total, "
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
                $result3 = $this->db->query($sql, BILLINGTYPE_PACKAGE_ADDON, $row['Plan'], $row['paymentterm'], $currencyCode);

                while ($row3 = $result3->fetch()) {
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

                    $append = ' ('.$billingcycles[$row3['paymentterm']]['name'].')';
                    $tExpectedIncome = $currency->format($currencyCode, $row3['total'], true);
                    $expectedincomeTotal += $row3['total'];
                    $aGroup[] = array("&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Add-ons $append", '', $tExpectedIncome);
                }
            }
        }

        if (isset($aGroup)) {
            $aGroup[] = array("","","");

            $expectedincomeTotal = $currency->format($currencyCode, $expectedincomeTotal, true);
            $aGroup[] = array("<b>".$this->user->lang("Totals")."</b>","<b>".$sumpertotalpackagesTotal."</b>", "<b>".$expectedincomeTotal."</b>");

            $this->reportData[] = array(
              "group" => $aGroup,
              "groupname" => $this->user->lang("Package Names"),
              "label" => array($this->user->lang('Package Name'),$this->user->lang('Total Packages'),$this->user->lang('Expected Monthly Income')),
              "groupId" => "",
              "isHidden" => false);
            unset($aGroup);
        }
    }
}
