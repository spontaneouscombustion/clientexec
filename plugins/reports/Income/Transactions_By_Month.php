<?php
/**
 * Transactions By Month Report
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
 * Transactions_By_Month Report Class
 *
 * @category Report
 * @package  ClientExec
 * @author   Juan Bolivar <juan@clientexec.com>
 * @license  ClientExec License
 * @version  1.0
 * @link     http://www.clientexec.com
 */
class Transactions_By_Month extends Report
{
    private $lang;

    protected $featureSet = 'billing';

    function __construct($user = null, $customer = null)
    {
        $this->lang = lang('Transactions By Month');
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
        $this->SetDescription($this->user->lang('Displays the total transactions by month.'));

        @set_time_limit(0);

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

        $subGroup = array();
        $labels = array(
            'Date',
            'User',
            'Invoice',
            'Action',
            'Transaction ID',
            'Description',
            'Gateway',
            'Amount'
        );

        $amountOfMonths = (int) ((isset($_REQUEST['amountOfMonths']))? $_REQUEST['amountOfMonths'] : 1);

        // get accepted transactions
        $query = "SELECT it.`transactiondate`, i.`customerid`, it.`invoiceid`, it.`action`, it.`transactionid`, it.`response`, i.`pluginused`, it.`amount` "
            ."FROM `invoicetransaction` it, `invoice` i "
            ."WHERE it.`accepted` = 1 AND (it.`action` != 'none' OR it.`response` LIKE '%paid%') AND i.`id` = it.`invoiceid` "
            ."AND DATE_FORMAT(it.`transactiondate`, '%Y%m') <= DATE_FORMAT(CURDATE(), '%Y%m') "
            ."AND DATE_FORMAT(it.`transactiondate`, '%Y%m') >= DATE_FORMAT(DATE_ADD(CURDATE(), INTERVAL -".($amountOfMonths - 1)." MONTH), '%Y%m') "
            ."AND i.`currency` = ? "
            ."ORDER BY it.`transactiondate` DESC, it.`id` DESC ";
        $result = $this->db->query($query, $currencyCode);

        $currentMonthAndYear = '';
        $currentMonthAndYearTotal = '';

        //Used to ignore duplicated transactions about refunds
        $refundsReferences = array();

        while (list($transdate, $customerid, $invoiceid, $action, $transactionid, $response, $pluginused, $amount) = $result->fetch()) {
            $transdate = strtotime($transdate);

            if ($action === 'refund' && isset($refundsReferences[$customerid][$invoiceid][$transactionid][$pluginused][$amount])) {
                $previousDate = $refundsReferences[$customerid][$invoiceid][$transactionid][$pluginused][$amount]['transdate'];

                //If the refund transactiondate is on the same hour, ignore it, but update the description
                if (abs($previousDate - $transdate) <= (60*60)) {
                    $refundsReferences[$customerid][$invoiceid][$transactionid][$pluginused][$amount]['response'] = $response;
                    continue;
                }
            }

            if ($customerid != 0) {
                $user = new User($customerid);

                if ($user->IsOrganization()) {
                    $dCustomerName = $user->getOrganization();
                } else {
                    $dCustomerName = $user->getFirstName() . " " . $user->getLastName();
                }

                // build a link to the customer profile
                $CustomerLink = "<a href='index.php?fuse=clients&controller=userprofile&frmClientID=".$customerid."&view=profileinvoices' target=blank>".CE_Lib::viewEscape($dCustomerName)."</a>";

                // build the Print to PDF Link
                $printInvoiceLink = "<a href='index.php?fuse=billing&frmClientID=".$customerid."&controller=invoice&view=invoice&profile=1&invoiceid=".$invoiceid."' target=blank>#".$invoiceid."</a>";
            } else {
                $dCustomerName = $this->user->lang('DELETED');
                $CustomerLink = CE_Lib::viewEscape($dCustomerName);
                $printInvoiceLink = $invoiceid;
            }

            $monthAndYear = date("Y M", mktime(0, 0, 0, date("m", $transdate), 1, date("Y", $transdate)));

            if ($currentMonthAndYear !== $monthAndYear) {
                if ($currentMonthAndYearTotal !== '') {
                    if ($currentMonthAndYearTotal >= 0) {
                        $fontOpen = "<font color='Green'>";
                    }else {
                        $fontOpen = "<font color='Red'>";
                    }
                    $currentMonthAndYearTotal = '<b>'.$fontOpen.$currency->format($currencyCode, $currentMonthAndYearTotal, true, 'NONE', true)."</font>".'</b>';
                }

                if ($currentMonthAndYear !== '') {
                    $data = array(
                        '&nbsp;',
                        '',
                        '',
                        '',
                        '',
                        '',
                        '',
                        '',
                    );

                    $subGroup[] = $data;
                }

                $currentMonthAndYear = $monthAndYear;

                $data = array(
                    '<b>'.$monthAndYear.'</b>',
                    '',
                    '',
                    '',
                    '',
                    '<b>'.$this->user->lang('TOTAL').'</b>',
                    '',
                    0.00,
                );

                $currentMonthAndYearTotal = &$data[7];

                $subGroup[] = $data;
            }

            $formattedAmount = $currency->format($currencyCode, $amount, true, 'NONE', true);

            if (in_array($action, array('refund', 'credit'))) {
                $amountString = "<font color='Red'>- ".$formattedAmount."</font>";
                $fixedAmount = -$amount;
            } else {
                $amountString = "<font color='Green'>".$formattedAmount."</font>";
                $fixedAmount = $amount;
            }

            $currentMonthAndYearTotal += $fixedAmount;

            $data = array(
                date( $this->settings->get( 'Date Format' ), $transdate )." ".date( "h:i:s A", $transdate ),
                $CustomerLink,
                $printInvoiceLink,
                $action,
                $transactionid,
                $response,
                $pluginused,
                $amountString
            );

            //Used to ignore duplicated transactions about refunds
            if ($action === 'refund') {
                $refundsReferences[$customerid][$invoiceid][$transactionid][$pluginused][$amount] = array(
                    'transdate' => $transdate,
                    'response'  => &$data[5]
                );
            }

            $subGroup[] = $data;
        }

        //Need to format the last group
        if ($currentMonthAndYearTotal !== '') {
            if ($currentMonthAndYearTotal >= 0) {
                $fontOpen = "<font color='Green'>";
            }else {
                $fontOpen = "<font color='Red'>- ";
            }

            $currentMonthAndYearTotal = abs($currentMonthAndYearTotal);
            $currentMonthAndYearTotal = '<b>'.$fontOpen.$currency->format($currencyCode, $currentMonthAndYearTotal, true, 'NONE', true)."</font>".'</b>';
        }

        if (isset($_REQUEST['download']) && $_REQUEST['download'] == 1) {
            //csv file will exxclude any line having an empty invoice id
            $exclude = array('2' => '');
            $this->download($labels, $subGroup, 'Transactions_By_Month.csv', $exclude);
        }

        $this->SetDescription($this->user->lang('Displays transactions by month'));

        $MonthsToDisplay =
            '<form id="report" method="GET">'
            .'    <table width=100%>'
            .'        <tr>'
            .'            <td style="text-align:center">'
            .'                Months to display: '
            .'                <input type="text" name="amountOfMonths" id="amountOfMonths" size="2" value="'.CE_Lib::viewEscape($amountOfMonths).'" onkeydown="if (event.keyCode == 13) { event.preventDefault(); }"> '
            .$filter
            .'                <input type=button name=search class="btn" value=\''.$this->user->lang('Display').'\' onclick="ChangeTable(document.getElementById(\'amountOfMonths\').value, document.getElementById(\'currencycode\').value, 0);">'
            .'            </td>'
            .'            <td width=250px align=right>'
            .'                <button class="btn" type="button" data-loading-text="Loading..." onclick="ChangeTable(document.getElementById(\'amountOfMonths\').value, document.getElementById(\'currencycode\').value, 1);">'.$this->user->lang("Download .csv").'</button>'
            .'            </td>'
            .'        </tr>'
            .'    </table>'
            .'</form>'
            .'</br>'
            .'<script type="text/javascript">'
            .'    function ChangeTable(amountOfMonths, currencycode, download){'
            .'        location.href="index.php?fuse=reports&view=viewreport&controller=index&report=Transactions+By+Month&type=Income&amountOfMonths="+amountOfMonths+"&currencycode="+currencycode+"&download="+download;'
            .'    }'
            .'</script>';
        echo $MonthsToDisplay;

        $this->reportData[] = array(
            "group" => $subGroup,
            "groupname" => "",
            "label" => $labels
        );
    }
}
?>
