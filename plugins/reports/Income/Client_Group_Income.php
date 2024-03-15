<?php
/**
 * Client Group Income Report
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

use Illuminate\Database\Capsule\Manager as Db;

/**
 * Client_Group_Income Report Class
 *
 * @category Report
 * @package  ClientExec
 * @author   Juan Bolívar <juan@clientexec.com>
 * @license  ClientExec License
 * @version  1.0
 * @link     http://www.clientexec.com
 */
class Client_Group_Income extends Report
{
    private $lang;

    protected $featureSet = 'billing';

    function __construct($user=null,$customer=null)
    {
        $this->lang = lang('Client Group Income');
        parent::__construct($user,$customer);
    }

    /**
     * Report Process Method
     *
     * @return null - direct output
     */
    function process()
    {
        // Set the report information
        $this->SetDescription($this->user->lang('Displays total paid by Client Group.'));

        $yearpaid = 'allperyear';

        if (isset($_REQUEST['yearpaid'])) {
            $yearpaid = $_REQUEST['yearpaid'];
        }

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
            .'        <select name="currencycode" id="currencycode" value="'.CE_Lib::viewEscape($currencyCode).'" onChange="ChangeTable(\''.$yearpaid.'\', this.value);"> ';

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

        switch ($yearpaid) {
            case 'allperyear':
                $AllPerYearSelected = 'selected';
                $AllSelected = '';
                break;
            case 'all':
                $AllPerYearSelected = '';
                $AllSelected = 'selected';
                break;
            default:
                $AllPerYearSelected = '';
                $AllSelected = '';
                break;
        }

        $yearOptions = '<option value="allperyear" ' . $AllPerYearSelected . '>All Per Year</option>';
        $yearOptions .= '<option value="all" ' . $AllSelected . '>All</option>';

        $invoices = Db::table('invoice')
            ->select(Db::raw('YEAR (datepaid) AS year'))
            ->distinct()
            ->where('status', '=', 1)
            ->orderBy('year', 'DESC')
            ->get();

        foreach ($invoices as $invoice) {
            $optionSelected = '';

            if ($invoice->year == $yearpaid) {
                $optionSelected = 'selected';
            }

            $yearOptions .= '<option value="' . $invoice->year . '" ' . $optionSelected . '>' . $invoice->year . '</option>';
        }

        $MonthsToDisplay =
             '<form id="report" method="GET">'
            .'    <div style="text-align:center">'
            .'        Select Year: '
            .'        <select id="yearpaid" name="yearpaid" onChange="ChangeTable(this.value, \''.$currencyCode.'\');">'
            .$yearOptions
            .'        </select>'
            .$filter
            .'    </div>'
            .'</form>'
            .'</br>'
            .'<script type="text/javascript">'
            .'    function ChangeTable(yearpaid, currencycode){'
            .'        location.href="index.php?fuse=reports&view=viewreport&controller=index&report=Client+Group+Income&type=Income&yearpaid="+yearpaid+"&currencycode="+currencycode;'
            .'    }'
            .'</script>';
        echo $MonthsToDisplay;

        switch ($yearpaid) {
            case 'allperyear':
                $reportValues = Db::table('invoice')
                    ->leftJoin('user_groups', 'user_groups.user_id', '=', 'invoice.customerid')
                    ->leftJoin('groups', 'groups.id', '=', 'user_groups.group_id')
                    ->select(Db::raw('IFNULL(user_groups.group_id, 0) AS groupId, IFNULL(groups.name, "--none--") AS groupName, YEAR(invoice.datepaid) AS yearDatePaid, SUM(invoice.amount) AS groupSum'))
                    ->where('invoice.status', '=', 1)
                    ->where('invoice.currency', '=', $currencyCode)
                    ->groupBy('user_groups.group_id' , 'yearDatePaid')
                    ->orderByRaw('user_groups.group_id ASC, yearDatePaid DESC')
                    ->get();

                $totalIncome = 0;
                $oldGroupName = '';

                foreach ($reportValues as $reportValue) {
                    $totalIncome += $reportValue->groupSum;

                    if($oldGroupName != $reportValue->groupName) {
                        if (isset($aGroup)) {
                            //add previous group before getting next group
                            $this->reportData[] = array(
                                "group" => $aGroup,
                                "groupname" => $oldGroupName,
                                "label" => array(
                                    $this->user->lang('Year'),
                                    $this->user->lang('Sum'),
                                ),
                                'colStyle' => 'width:200px',
                                "groupId" => "",
                                "isHidden" => false
                            );

                            unset($aGroup);
                        }

                        $aGroup = array();
                        $oldGroupName = $reportValue->groupName;
                    }

                    $aGroup[] = array(
                        $reportValue->yearDatePaid,
                        $currency->format($currencyCode, $reportValue->groupSum, true)
                    );
                }

                //add final group
                if (isset($aGroup)) {
                    //add previous group before getting next group
                    $this->reportData[] = array(
                        "group" => $aGroup,
                        "groupname" => $oldGroupName,
                        "label" => array(
                            $this->user->lang('Year'),
                            $this->user->lang('Sum'),
                        ),
                        'colStyle' => 'width:200px',
                        "groupId" => "",
                        "isHidden" => false
                    );

                    unset($aGroup);
                }

                $this->reportData[] = array(
                    "group" => array(
                        array(
                            '',
                            $currency->format($currencyCode, $totalIncome, true)
                        )
                    ),
                    "groupname" => $this->user->lang('Totals'),
                    "label" => array(
                        '',
                        ''
                    ),
                    'colStyle' => 'width:200px',
                    "groupId" => "",
                    "isHidden" => false
                );

                break;
            default:
                if ($yearpaid === 'all') {
                    $reportValues = Db::table('invoice')
                        ->leftJoin('user_groups', 'user_groups.user_id', '=', 'invoice.customerid')
                        ->leftJoin('groups', 'groups.id', '=', 'user_groups.group_id')
                        ->select(Db::raw('IFNULL(user_groups.group_id, 0) AS groupId, IFNULL(groups.name, "--none--") AS groupName, SUM(invoice.amount) AS groupSum'))
                        ->where('invoice.status', '=', 1)
                        ->where('invoice.currency', '=', $currencyCode)
                        ->groupBy('user_groups.group_id')
                        ->orderBy('user_groups.group_id', 'ASC')
                        ->get();
                } else {
                    $reportValues = Db::table('invoice')
                        ->leftJoin('user_groups', 'user_groups.user_id', '=', 'invoice.customerid')
                        ->leftJoin('groups', 'groups.id', '=', 'user_groups.group_id')
                        ->select(Db::raw('IFNULL(user_groups.group_id, 0) AS groupId, IFNULL(groups.name, "--none--") AS groupName, SUM(invoice.amount) AS groupSum'))
                        ->where('invoice.status', '=', 1)
                        ->where('invoice.currency', '=', $currencyCode)
                        ->whereYear('invoice.datepaid', $yearpaid)
                        ->groupBy('user_groups.group_id')
                        ->orderBy('user_groups.group_id', 'ASC')
                        ->get();
                }

                $totalIncome = 0;

                foreach ($reportValues as $reportValue) {
                    $totalIncome += $reportValue->groupSum;

                    $this->reportData[] = array(
                        "group" => array(
                            array(
                                $currency->format($currencyCode, $reportValue->groupSum, true)
                            )
                        ),
                        "groupname" => $reportValue->groupName,
                        "label" => array(
                            $this->user->lang('Sum'),
                        ),
                        'colStyle' => 'width:200px',
                        "groupId" => "",
                        "isHidden" => false
                    );
                }

                $this->reportData[] = array(
                    "group" => array(
                        array(
                            $currency->format($currencyCode, $totalIncome, true)
                        )
                    ),
                    "groupname" => $this->user->lang('Totals'),
                    "label" => array(
                        ''
                    ),
                    'colStyle' => 'width:200px',
                    "groupId" => "",
                    "isHidden" => false
                );

                break;
        }
    }

    //*********************************************
    // Custom Function Definitions for this report
    //*********************************************
}

?>
