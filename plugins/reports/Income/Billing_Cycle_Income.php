<?php
/**
 * Billing Cycle Income Report
 *
 * @category Report
 * @package  ClientExec
 * @author   Jason Yates <jason@clientexec.com>
 * @license  ClientExec License
 * @version  1.1
 * @link     http://www.clientexec.com
 *
 *************************************************
 *   1.0 Initial Report Released
 *   1.1 Updated report to include a title & PEAR commenting
 ************************************************
 */

require_once 'modules/billing/models/Currency.php';
require_once 'modules/billing/models/BillingType.php';
require_once('modules/clients/models/DomainNameGateway.php');
require_once 'modules/billing/models/BillingCycle.php';

/**
 * Billing_Cycle_Income Report Class
 *
 * @category Report
 * @package  ClientExec
 * @author   Jason Yates <jason@clientexec.com>
 * @license  ClientExec License
 * @version  1.1
 * @link     http://www.clientexec.com
 */
class Billing_Cycle_Income extends Report
{
    private $lang;

    protected $featureSet = 'billing';

    function __construct($user=null,$customer=null)
    {
        $this->lang = lang('Billing Cycle Income');
        parent::__construct($user,$customer);
    }

    /**
     * Report Process Method
     *
     * @return null - direct output
     */
    function process()
    {
        include_once 'modules/admin/models/StatusAliasGateway.php' ;

        // Set the report information
        $this->SetDescription($this->user->lang('Displays total recurring transactions broken down by billing cycles with the sum and expected yearly income from each.'));

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
            .'        location.href="index.php?fuse=reports&view=viewreport&controller=index&report=Billing+Cycle+Income&type=Income&currencycode="+currencycode;'
            .'    }'
            .'</script>';
        echo $filter;

        // Array to store all the totals
        //Billing Cycles
        $billingCycleIncome = array();

        include_once 'modules/billing/models/BillingCycleGateway.php';
        $gateway = new BillingCycleGateway();
        $iterator = $gateway->getBillingCycles(array(), array('order_value', 'ASC'));

        while ($cycle = $iterator->fetch()) {
            $billingCycleIncome[$cycle->id] = false;
        }
        //Billing Cycles

        $userStatuses = StatusAliasGateway::userActiveAliases($this->user);
        $packageStatuses = StatusAliasGateway::getInstance($this->user)->getPackageStatusIdsFor(array(PACKAGE_STATUS_PENDING, PACKAGE_STATUS_ACTIVE));

        // Get the totals for recurring charges that are not package prices
        $reportSQL = "SELECT COUNT(r.id), "
            ."SUM(r.amount * r.quantity), "
            ."r.paymentterm "
            ."FROM users u, "
            ."recurringfee r "
            ."LEFT JOIN domains d "
            ."ON r.appliestoid = d.id "
            ."WHERE r.customerid = u.id "
            ."AND u.status IN (".implode(', ', $userStatuses).") "
            ."AND (r.appliestoid = 0 "
            ."OR (r.appliestoid <> 0 "
            ."AND d.status IN (".implode(', ', $packageStatuses).") "
            ."AND r.billingtypeid <> ".BILLINGTYPE_PACKAGE.")) "
            ."AND r.recurring = 1 "
            ."AND r.paymentterm != 0 "
            ."AND u.currency = ? "
            ."GROUP BY r.paymentterm "
            ."ORDER BY r.paymentterm ";
        $result = $this->db->query($reportSQL, $currencyCode);

        // Fill array with the totals for recurring charges that are not package prices
        while (list($tNumberOfRecurringItems, $tSumPerBillingCycle, $tBillingCycle) = $result->fetch()) {
            if (isset($billingCycleIncome[$tBillingCycle]["NumberOfRecurringItems"])) {
                $billingCycleIncome[$tBillingCycle]["NumberOfRecurringItems"] += $tNumberOfRecurringItems;
            } else {
                $billingCycleIncome[$tBillingCycle]["NumberOfRecurringItems"] = $tNumberOfRecurringItems;
            }

            if (isset($billingCycleIncome[$tBillingCycle]["SumPerBillingCycle"])) {
                $billingCycleIncome[$tBillingCycle]["SumPerBillingCycle"] += $tSumPerBillingCycle;
            } else {
                $billingCycleIncome[$tBillingCycle]["SumPerBillingCycle"] = $tSumPerBillingCycle;
            }
        }

        // Get the totals for recurring charges that are package prices
        $reportSQL = "SELECT r.paymentterm, "
            ."d.use_custom_price, "
            ."d.custom_price, "
            ."ocf.value AS domain_name, "
            ."p.id, "
            ."p.pricing, "
            ."g.type "
            ."FROM users u, "
            ."recurringfee r, "
            ."domains d "
            ."LEFT JOIN object_customField ocf "
            ."ON ocf.objectid = d.id "
            ."AND ocf.customFieldId = (SELECT cf.id "
            ."FROM customField cf "
            ."WHERE groupId = 2 "
            ."AND subGroupId  = 3 "
            ."AND name  = 'Domain Name'), "
            ."package p, "
            ."promotion g "
            ."WHERE r.appliestoid = d.id "
            ."AND r.customerid = u.id "
            ."AND u.status IN (".implode(', ', $userStatuses).") "
            ."AND r.appliestoid != 0 "
            ."AND d.status IN (".implode(', ', $packageStatuses).") "
            ."AND r.billingtypeid = ".BILLINGTYPE_PACKAGE." "
            ."AND r.recurring = 1 "
            ."AND r.paymentterm != 0 "
            ."AND p.id = d.Plan "
            ."AND p.planid = g.id "
            ."AND u.currency = ? "
            ."ORDER BY r.paymentterm ";
        $result = $this->db->query($reportSQL, $currencyCode);

        // Just in case we have domains
        $dng = new DomainNameGateway($this->user);

        include_once 'modules/billing/models/Prices.php';
        $prices = new Prices();

        // Fill array with the totals for recurring charges that are package prices
        while (list($tBillingCycle, $tUseCustomPrice, $tCustomPrice, $tDomainName, $tProductId, $tPricing, $tType) = $result->fetch()) {
            $tPricing = $prices->getPricing(PRODUCT_PRICE, $tProductId, $currencyCode, $tPricing);

            if ($tUseCustomPrice) {
                 $packagePrice = $tCustomPrice;
            } else {
                $pricing = @unserialize($tPricing);

                if (is_array($pricing)) {
                    if ($tType == 3) {  // Domain Type
                        $aDomainName = $dng->splitDomain($tDomainName);
                        $tld = $aDomainName[1];
                        $packagePrice = 0;

                        $keys = array_keys($pricing['pricedata']);

                        if (isset($pricing['pricedata'][$keys[0]][$tBillingCycle]['price'])) {
                            $packagePrice = $pricing['pricedata'][$keys[0]][$tBillingCycle]['price'];
                        }
                    } else {
                        $packagePrice = 0;

                        if(isset($pricing['price'.$tBillingCycle])){
                            $packagePrice = $pricing['price'.$tBillingCycle];
                        }
                    }
                }
            }

            if (isset($billingCycleIncome[$tBillingCycle]["NumberOfRecurringItems"])) {
                $billingCycleIncome[$tBillingCycle]["NumberOfRecurringItems"] += 1;
            } else {
                $billingCycleIncome[$tBillingCycle]["NumberOfRecurringItems"] = 1;
            }

            if (isset($billingCycleIncome[$tBillingCycle]["SumPerBillingCycle"])) {
                $billingCycleIncome[$tBillingCycle]["SumPerBillingCycle"] += $packagePrice;
            } else {
                $billingCycleIncome[$tBillingCycle]["SumPerBillingCycle"] = $packagePrice;
            }
        }

        //initialize
        $oldBillingCycle = -1;
        $expectedincomeTotal = 0;
        $sumperbillingcycleTotal = 0;
        $itemTotal = 0;

        foreach ($billingCycleIncome AS $tBillingCycle => $billingCycleIncomeData) {
            if ($billingCycleIncomeData !== false) {
                $tNumberOfRecurringItems = $billingCycleIncomeData["NumberOfRecurringItems"];
                $tSumPerBillingCycle = $billingCycleIncomeData["SumPerBillingCycle"];

                if ($oldBillingCycle != $tBillingCycle) {
                    if (isset($aGroup)) {
                        //add previous group before getting next group
                        $this->reportData[] = array(
                            "group" => $aGroup,
                            "groupname" => $this->GetExtendedName($oldBillingCycle),
                            "label" => array(
                                $this->user->lang('Items'),
                                $this->user->lang('Sum'),
                                $this->user->lang('Expected Yearly Income')
                            ),
                            'colStyle' => 'width:200px',
                            "groupId" => "",
                            "isHidden" => false
                        );

                        unset($aGroup);
                    }

                    $aGroup = array();
                    $oldBillingCycle = $tBillingCycle;
                }

                //truncate
                $tExpectedIncome = $currency->format($currencyCode, $this->GetExpectedYearlyIncome($tBillingCycle, $tSumPerBillingCycle), true);
                $expectedincomeTotal += $this->GetExpectedYearlyIncome($tBillingCycle, $tSumPerBillingCycle);
                $sumperbillingcycleTotal += $tSumPerBillingCycle;
                $tSumPerBillingCycle = $currency->format($currencyCode, $tSumPerBillingCycle, true);
                $aGroup[] = array($tNumberOfRecurringItems,$tSumPerBillingCycle,$tExpectedIncome);
                $itemTotal += $tNumberOfRecurringItems;
            }
        }

        //add final group
        if (isset($aGroup)) {

            $this->reportData[] = array(
                "group" => $aGroup,
                "groupname" => $this->GetExtendedName($oldBillingCycle),
                "label" => array(
                    $this->user->lang('Items'),
                    $this->user->lang('Sum'),
                    $this->user->lang('Expected Yearly Income')
                ),
                'colStyle' => 'width:200px',
                "groupId" => "",
                "isHidden" => false
            );

            unset($aGroup);
        }

        $expectedincomeTotal = $currency->format($currencyCode, $expectedincomeTotal, true);
        $sumperbillingcycleTotal = $currency->format($currencyCode, $sumperbillingcycleTotal, true);
        $aGroup[] = array(
            $itemTotal,
            $sumperbillingcycleTotal,
            $expectedincomeTotal
        );

        $this->reportData[] = array(
            "group" => $aGroup,
            "groupname" => $this->user->lang('Totals'),
            "label" => array(
                "",
                "",
                ""
            ),
            'colStyle' => 'width:200px',
            "groupId" => "",
            "isHidden" => false
        );
    }

    //*********************************************
    // Custom Function Definitions for this report
    //*********************************************

    /**
     * function get the expected yearly income per billing cycle
     *
     * @return var - estimated income
     */
    function GetExpectedYearlyIncome($billingCycle, $tempSum)
    {
        $billingCycle = new BillingCycle($billingCycle);

        if ($billingCycle > 0 && $billingCycle->amount_of_units > 0) {
            switch ($billingCycle->time_unit) {
                case 'd':
                    return ($tempSum/$billingCycle->amount_of_units)*365;
                    break;
                case 'w':
                    return (($tempSum/$billingCycle->amount_of_units)/7)*365;
                    break;
                case 'm':
                    return ($tempSum/$billingCycle->amount_of_units)*12;
                    break;
                case 'y':
                    return $tempSum/$billingCycle->amount_of_units;
                    break;
            }
        } else {
            return 0;
        }
    }

    /**
     * function to translate the digit billing cycle to the preferred header ( e.g. 1 returns Monthly )
     *
     * @return var - header item
     */
    function GetExtendedName($billingCycle)
    {
        $billingCycle = new BillingCycle($billingCycle);
        return $this->user->lang('Every').' '.$this->user->lang($billingCycle->name);
    }
}

?>
