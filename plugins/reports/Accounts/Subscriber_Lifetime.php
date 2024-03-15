<?php

/**
 * Subscriber Lifetime Report
 *
 * @category Report
 * @package  ClientExec
 * @author   Sebastian Berm <sebastian@sebsoft.nl>
 * @license  LGPL
 * @version  2.1
 * @link     http://www.sebsoft.nl
 *
 * ************************************************
 *   1.0 Initial report created, written "Dave's "Lifetime value of a Subscriber" Report" for CE 2.8 - Dave
 *   1.1 Added method to sort by different column (still version 2.8) - Sebastian Berm
 *   2.0 Rewritten for engine version 4.1 - Sebastian Berm
 *   2.1 Fixed a few issues with the report when used with strict error reporting & Included report with CE builds - Jason Yates (ClientExec)
 * ***********************************************
 */
require_once 'modules/billing/models/Currency.php';

/**
 * Subscriber Lifetime Report
 *
 * @category Report
 * @package  ClientExec
 * @author   Sebastian Berm <sebastian@sebsoft.nl>
 * @license  LGPL
 * @version  2.1
 * @link     http://www.sebsoft.nl
 */
class Subscriber_Lifetime extends Report {
    private $lang;

    protected $featureSet = 'accounts';

    // Enable the line below to get nice viewing Dutch numbers, or leave it the way it is, for normal CE notations
    //private $oldcurrencysupport = true;
    private $oldcurrencysupport = false;

    function __construct($user=null,$customer=null)
    {
        $this->lang = lang('Subscriber Lifetime');
        parent::__construct($user,$customer);
    }

    /**
     * Report Process Method
     *
     * @return null - direct output
     */
    function process() {

        // Set the report information
        $this->SetDescription($this->user->lang('Display subscriber lifetime since first becoming client'));


        if (isset($_REQUEST['myorder']) && $_REQUEST['myorder'] == "total") {
            $orderbystring = "total DESC";
        } elseif (isset($_REQUEST['myorder']) && $_REQUEST['myorder'] == "paidtotal") {
            $orderbystring = "paidtotal DESC";
        } elseif (isset($_REQUEST['myorder']) && $_REQUEST['myorder'] == "id") {
            $orderbystring = "u.id ASC"; // This is insane :)
        } else {
            $orderbystring = "u.lastname, u.firstname";
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
            .'        location.href="index.php?fuse=reports&view=viewreport&controller=index&report=Subscriber+Lifetime&type=Accounts&currencycode="+currencycode;'
            .'    }'
            .'</script>';
        echo $filter;

        // get all invoice totals by customer
        $strSQL = "SELECT u.id, u.firstname, u.lastname, ROUND(SUM(i.amount),2) AS total,(SELECT ROUND(SUM(inv.amount),2) AS paidtotal FROM invoice inv WHERE inv.customerid = u.id AND inv.status = 1 AND inv.currency = ?) AS paidtotal FROM users u, invoice i WHERE u.id = i.customerid AND i.currency = ? GROUP BY u.id ORDER BY $orderbystring";
        $result = $this->db->query($strSQL, $currencyCode, $currencyCode);

        $grand_total = 0;
        $subscribers = 0;
        $paid_total = 0;

        while ($myrow = $result->fetch()) {
            $firstname = $myrow["firstname"];
            $lastname = $myrow["lastname"];
            $name = $lastname . ", " . $firstname;

            $aGroup[] = array($myrow['id'], $name, $this->currencyConvert($myrow["total"], $currencyCode), $this->currencyConvert($myrow['paidtotal'], $currencyCode));

            $subscribers++;
            $grand_total += $myrow["total"];
            $paid_total += $myrow['paidtotal'];
        }

        $basePage = $_SERVER["REQUEST_URI"]; // Yeah, this is lame
        $link0 = $basePage . "&myorder=id";
        $link1 = $basePage . "&myorder=name";
        $link2 = $basePage . "&myorder=total";
        $link3 = $basePage . "&myorder=paidtotal";

        if ( isset($aGroup) ) {
            $this->reportData[] = array("group"=>$aGroup,
                "groupname"=> $this->user->lang('Totals'),
                "label"=>array("<a href='$link0'>" . $this->user->lang('Id') . "</a>", "<a href='$link1'>" . $this->user->lang('Client name') . "</a>", "<a href='$link2'>" . $this->user->lang('Billed') . "</a>", "<a href='$link3'>" . $this->user->lang("Paid") . "</a>"),
                "groupId"=>"",
                "isHidden"=>false);
        }

        // Start second group
        if ( $subscribers > 0 ) {
            $value_each = round(($grand_total / $subscribers), 2);
            $paid_each = round(($paid_total / $subscribers), 2);

            $aGroup = array(array($this->user->lang("Avarage Lifetime Value of a Subscriber"), $this->currencyConvert($value_each, $currencyCode)),
                    array($this->user->lang("Avarage Paid Lifetime of a Subscriber"), $this->currencyConvert($paid_each, $currencyCode)));
            $this->reportData[] = array("group"=>$aGroup,
                "groupname"=> $this->user->lang('Statistics'),
                "label"=>array($this->user->lang("Type"), $this->user->lang("Amount")),
                "groupId"=>"",
                "isHidden"=>false);

        }
    }

    private function currencyConvert($number, $currencyCode) {
        if ($this->oldcurrencysupport) {
            return "&euro; " . number_format($number, 2, ',', '.');
        }

        // Load the currency information
        static $currency = null;
        if ($currency == null) { // This is just a crappy singleton, but still, it should work....
            $currency = new Currency($this->user);
        }

        return $currency->format($currencyCode, $number, true);
    }
}
?>
