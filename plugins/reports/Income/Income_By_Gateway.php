<?php
/**
 * Income By Gateway Report
 *
 * @category Report
 * @package  ClientExec
 * @author   Juan Bolivar <juan@clientexec.com>
 * @license  ClientExec License
 * @version  1.0
 * @link     http://www.clientexec.com
 */

require_once 'modules/admin/models/Package.php';
require_once 'modules/clients/models/UserPackage.php';
require_once 'modules/billing/models/Currency.php';
require_once 'modules/clients/models/User.php';
require_once 'modules/billing/models/Invoice.php';

/**
 * Income_By_Gateway Report Class
 *
 * @category Report
 * @package  ClientExec
 * @author   Juan Bolivar <juan@clientexec.com>
 * @license  ClientExec License
 * @version  1.0
 * @link     http://www.clientexec.com
 */
class Income_By_Gateway extends Report
{
    private $lang;

    protected $featureSet = 'billing';

    function __construct($user = null, $customer = null)
    {
        $this->lang = lang('Income By Gateway');
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
        $this->SetDescription($this->user->lang('Displays the total income by gateway per month.'));

        @set_time_limit(0);

        // Load the currency information
        $amountOfMonths = ((isset($_REQUEST['amountOfMonths']))? $_REQUEST['amountOfMonths'] : 12);

        // Load the currency information
        $currency = new Currency($this->user);

        $currencyCode = ((isset($_REQUEST['currencycode']))? $_REQUEST['currencycode'] : $this->settings->get('Default Currency'));
        $currencyName = $currency->getName($currencyCode);

        //Get all currencies of all invoices
        $currenciesSQL = "SELECT DISTINCT c.`abrv`, c.`name` "
            ."FROM `invoice` i "
            ."INNER JOIN `currency` c ON c.`abrv` = i.`currency` "
            ."ORDER BY c.`name` ASC ";
        $currenciesResult = $this->db->query($currenciesSQL);

        $filter = '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'
            .'        '.$this->user->lang('Currency').': '
            .'        <select name="currencycode" id="currencycode" value="'.CE_Lib::viewEscape($currencyCode).'" > ';

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

        //Get Gateways Used
        $reportSQL1 = "SELECT DISTINCT IF(i.`pluginused` != 'none' OR u.`paymenttype` = '0', i.`pluginused`, u.`paymenttype`) AS gatewayused "
            ."FROM `invoice` i "
            ."LEFT JOIN `users` u ON i.`customerid` = u.`id` "
            ."WHERE DATE_FORMAT(i.`datepaid`, '%Y%m') <= DATE_FORMAT(CURDATE(), '%Y%m') "
            ."AND DATE_FORMAT(i.`datepaid`, '%Y%m') >= DATE_FORMAT(DATE_ADD(CURDATE(), INTERVAL -".($amountOfMonths - 1)." MONTH), '%Y%m') "
            ."AND i.`status` = ? "
            ."AND i.`currency` = ? "
            ."ORDER BY `gatewayused` ASC ";
        $result1 = $this->db->query($reportSQL1, INVOICE_STATUS_PAID, $currencyCode);

        $gatewaysUsed = array();

        while (list($pluginused) = $result1->fetch()) {
            $gatewaysUsed[$pluginused] = 0;
        }

        $gatewaysUsed[$this->user->lang('TOTAL')] = 0;

        $newMonthTotals  = array();

        //Initialize months income
        for ($monthsBackward = 0; $monthsBackward < $amountOfMonths; $monthsBackward++) {
            $newMonthTotals[date("Y M", mktime(0, 0, 0, date("m") - $monthsBackward, 1, date("Y")))] = $gatewaysUsed;
        }

        $newMonthTotals[$this->user->lang('TOTAL')] = $gatewaysUsed;

        //Get Paid Invoices
        $reportSQL2 = "SELECT i.`datepaid`, "
            ."IFNULL(i.`amount`, 0), "
            ."IF(i.`pluginused` != 'none' OR u.`paymenttype` = '0', i.`pluginused`, u.`paymenttype`) "
            ."FROM `invoice` i "
            ."LEFT JOIN `users` u ON i.`customerid` = u.`id` "
            ."WHERE DATE_FORMAT(i.`datepaid`, '%Y%m') <= DATE_FORMAT(CURDATE(), '%Y%m') "
            ."AND DATE_FORMAT(i.`datepaid`, '%Y%m') >= DATE_FORMAT(DATE_ADD(CURDATE(), INTERVAL -".($amountOfMonths - 1)." MONTH), '%Y%m') "
            ."AND i.`status` = ? "
            ."AND i.`currency` = ? ";
        $result2 = $this->db->query($reportSQL2, INVOICE_STATUS_PAID, $currencyCode);

        //Add invoices already generated
        while (list($datepaid, $amount, $pluginused) = $result2->fetch()) {
            $monthAndYear = date("Y M", mktime(0, 0, 0, date("m", strtotime($datepaid)), 1, date("Y", strtotime($datepaid))));

            if (isset($newMonthTotals[$monthAndYear][$pluginused])) {
                $newMonthTotals[$monthAndYear][$pluginused] += $amount;
                $newMonthTotals[$monthAndYear][$this->user->lang('TOTAL')] += $amount;
                $newMonthTotals[$this->user->lang('TOTAL')][$pluginused] += $amount;
                $newMonthTotals[$this->user->lang('TOTAL')][$this->user->lang('TOTAL')] += $amount;
            }
        }

        $this->SetDescription($this->user->lang('Displays income by gateway'));
        
        $MonthsToDisplay =
             '<form id="report" method="GET">'
            .'    <div style="text-align:center">'
            .'        Months to display: '
            .'        <input type="text" name="amountOfMonths" id="amountOfMonths" size="2" value="'.CE_Lib::viewEscape($amountOfMonths).'" onkeydown="if (event.keyCode == 13) { event.preventDefault(); }"> '
            .$filter
            .'        <input type=button name=search class="btn" value=\''.$this->user->lang('Display').'\' onclick="ChangeTable(document.getElementById(\'amountOfMonths\').value, document.getElementById(\'currencycode\').value);">'
            .'    </div>'
            .'</form>'
            .'</br>'
            .'<script type="text/javascript">'
            .'    function ChangeTable(amountOfMonths, currencycode){'
            .'        location.href="index.php?fuse=reports&view=viewreport&controller=index&report=Income+By+Gateway&type=Income&amountOfMonths="+amountOfMonths+"&currencycode="+currencycode;'
            .'    }'
            .'</script>';
        echo $MonthsToDisplay;

        $subGroup = array();

        foreach ($newMonthTotals as $monthAndYear => $monthData) {
            $prepend = '';
            $append = '';

            if ($monthAndYear == $this->user->lang('TOTAL')) {
                $prepend = '<b>';
                $append = '</b>';
            }

            $subGroupValues = array(
                $prepend.$monthAndYear.$append
            );

            foreach ($monthData as $gatewayName => $gatewayTotal) {
                $formattedGatewayTotal = $currency->format($currencyCode, $gatewayTotal, true, false);

                if ($gatewayTotal == 0) {
                    $formattedGatewayTotal = '<font color="grey">'.$formattedGatewayTotal.'</font>';
                }

                if ($gatewayName == $this->user->lang('TOTAL')) {
                    $prepend = '<b>';
                    $append = '</b>';
                }

                $subGroupValues[] = $prepend.$formattedGatewayTotal.$append;
            }

            $subGroup[] = $subGroupValues;
        }

        $labels = array(
            $this->user->lang('Month and Year')
        );

        foreach ($gatewaysUsed as $gatewayName => $gatewayZero) {
            switch ($gatewayName) {
                case $this->user->lang('TOTAL'):
                    $labels[] = $gatewayName;
                    break;
                case 'none':
                    $labels[] = $this->user->lang('None');
                    break;
                default:
                    $labels[] = ($gatewayName != 'none')? $this->user->lang($this->settings->get('plugin_'.$gatewayName.'_Plugin Name')) : $this->user->lang('None');
                    break;
            }
        }

        $this->reportData[] = array(
            "group" => $subGroup,
            "groupname" => "",
            "label" => $labels
        );
    }
}
?>
