<?php
/**
 * Country Yearly Income Report
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
 * Country_Yearly_Income Report Class
 *
 * @category Report
 * @package  ClientExec
 * @author   Juan Bolivar <juan@clientexec.com>
 * @license  ClientExec License
 * @version  1.0
 * @link     http://www.clientexec.com
 */
class Country_Yearly_Income extends Report
{
    private $lang;

    protected $featureSet = 'billing';

    function __construct($user = null, $customer = null)
    {
        $this->lang = lang('Country Yearly Income');
        parent::__construct($user, $customer);
    }

    /**
     * Report Process Method
     *
     * @return null - direct output
     */
    function process()
    {
        @set_time_limit(0);

        // Set the report information
        $this->SetDescription($this->user->lang('Displays the yearly income from clients of the selected country.'));

        // Load the country information
        $countries = new Countries($this->user);
        $countryCode = ((isset($_REQUEST['countrycode']))? $_REQUEST['countrycode'] : $this->settings->get('Default Country'));
        $countryName = $countries->validCountryCode($countryCode, false, 'name');

        $year = ((isset($_REQUEST['year']))? $_REQUEST['year'] : date('Y'));

        // Load the currency information
        $currency = new Currency($this->user);
        $currencyCode = ((isset($_REQUEST['currencycode']))? $_REQUEST['currencycode'] : $this->settings->get('Default Currency'));
        $currencyName = $currency->getName($currencyCode);

        //Get id of the country custom field
        $countryCustomFieldIdSQL = "SELECT `id` "
            ."FROM `customuserfields` "
            ."WHERE `type` = ? ";
        $countryCustomFieldIdResult = $this->db->query($countryCustomFieldIdSQL, typeCOUNTRY);
        list($countryCustomFieldId) = $countryCustomFieldIdResult->fetch();

        //Get all countries with paid invoices
        $countriesSQL = "SELECT DISTINCT c.`name`, c.`iso` "
            ."FROM `country` c "
            ."INNER JOIN `user_customuserfields` ucuf ON ucuf.`value` = c.`iso` AND ucuf.`customid` = ? "
            ."INNER JOIN `invoice` i ON i.`customerid` = ucuf.`userid` AND i.`status` = ? "
            ."ORDER BY c.`name` ASC ";
        $countriesResult = $this->db->query($countriesSQL, $countryCustomFieldId, INVOICE_STATUS_PAID);

        //Get all years when were paid invoices from clients of the selected country
        $yearsSQL = "SELECT DISTINCT YEAR(i.`datepaid`) "
            ."FROM `invoice` i "
            ."INNER JOIN `users` u ON u.`id` = i.`customerid` "
            ."INNER JOIN `user_customuserfields` ucuf ON ucuf.`userid` = u.`id` "
            ."WHERE i.`status` = ? "
            ."AND ucuf.`customid` = ? "
            ."AND ucuf.`value` = ? "
            ."ORDER BY i.`datepaid` DESC ";
        $yearsResult = $this->db->query($yearsSQL, INVOICE_STATUS_PAID, $countryCustomFieldId, $countryCode);

        //Get all currencies of all years when were paid invoices from clients of the selected country
        $currenciesSQL = "SELECT DISTINCT c.`abrv`, c.`name` "
            ."FROM `invoice` i "
            ."INNER JOIN `users` u ON u.`id` = i.`customerid` "
            ."INNER JOIN `user_customuserfields` ucuf ON ucuf.`userid` = u.`id` "
            ."INNER JOIN `currency` c ON c.`abrv` = i.`currency` "
            ."WHERE i.`status` = ? "
            ."AND ucuf.`customid` = ? "
            ."AND ucuf.`value` = ? "
            ."ORDER BY c.`name` ASC ";
        $currenciesResult = $this->db->query($currenciesSQL, INVOICE_STATUS_PAID, $countryCustomFieldId, $countryCode);

        $filter = '<form id="report" method="GET">'
            .'    <div style="text-align:center">'
            .'        '.$this->user->lang('Country').': '
            .'        <select name="countrycode" id="countrycode" value="'.CE_Lib::viewEscape($countryCode).'" onChange="ChangeTable(this.value, \''.$year.'\', \''.$currencyCode.'\');"> ';

        $isSelectedCountryInTheList = false;
        while (list($singleCountryName, $singleCountryISO) = $countriesResult->fetch()) {
            if (!$isSelectedCountryInTheList && $countryName < $singleCountryName) {
                $filter .= '<option value="'.$countryCode.'" selected>'.$countryName.'</option>';
                $isSelectedCountryInTheList = true;
            } elseif ($countryCode == $singleCountryISO) {
                $isSelectedCountryInTheList = true;
            }
            $filter .= '<option value="'.$singleCountryISO.'" '.(($countryCode == $singleCountryISO)? 'selected' : '').'>'.$singleCountryName.'</option>';
        }
        if (!$isSelectedCountryInTheList) {
            $filter .= '<option value="'.$countryCode.'" selected>'.$countryName.'</option>';
            $isSelectedCountryInTheList = true;
        }

        $filter .= '</select>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
        $filter .= '        '.$this->user->lang('Year').': '
            .'        <select name="year" id="year" value="'.CE_Lib::viewEscape($year).'" onChange="ChangeTable(\''.$countryCode.'\', this.value, \''.$currencyCode.'\');"> ';

        $isSelectedYearInTheList = false;
        while (list($singleYear) = $yearsResult->fetch()) {
            if (!$isSelectedYearInTheList && $year > $singleYear) {
                $filter .= '<option value="'.$year.'" selected>'.$year.'</option>';
                $isSelectedYearInTheList = true;
            } elseif ($year == $singleYear) {
                $isSelectedYearInTheList = true;
            }
            $filter .= '<option value="'.$singleYear.'" '.(($year == $singleYear)? 'selected' : '').'>'.$singleYear.'</option>';
        }
        if (!$isSelectedYearInTheList) {
            $filter .= '<option value="'.$year.'" selected>'.$year.'</option>';
            $isSelectedYearInTheList = true;
        }

        $filter .= '</select>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
        $filter .= '        '.$this->user->lang('Currency').': '
            .'        <select name="currencycode" id="currencycode" value="'.CE_Lib::viewEscape($currencyCode).'" onChange="ChangeTable(\''.$countryCode.'\', \''.$year.'\', this.value);"> ';

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
            .'    function ChangeTable(countrycode, year, currencycode){'
            .'        location.href="index.php?fuse=reports&view=viewreport&controller=index&report=Country+Yearly+Income&type=Income&countrycode="+countrycode+"&year="+year+"&currencycode="+currencycode;'
            .'    }'
            .'</script>';
        echo $filter;

        //Get all paid invoices amount from clients of the selected country paid the selected year with the selected currency
        $reportSQL = "SELECT i.`customerid`, SUM(i.`amount`) AS `totalAmount` "
            ."FROM `invoice` i "
            ."INNER JOIN `users` u ON u.`id` = i.`customerid` "
            ."INNER JOIN `user_customuserfields` ucuf ON ucuf.`userid` = u.`id` "
            ."INNER JOIN `currency` c ON c.`abrv` = i.`currency` "
            ."WHERE i.`status` = ? "
            ."AND ucuf.`customid` = ? "
            ."AND ucuf.`value` = ? "
            ."AND YEAR(i.`datepaid`) = ? "
            ."AND i.`currency` = ? "
            ."GROUP BY i.`customerid` "
            ."ORDER BY `totalAmount` DESC ";
        $reportResult = $this->db->query($reportSQL, INVOICE_STATUS_PAID, $countryCustomFieldId, $countryCode, $year, $currencyCode);

        $subGroup = array();
        $totalAmount = 0;

        while (list($customerid, $amount) = $reportResult->fetch()) {
            $totalAmount += $amount;
            $user = new User($customerid);
            $clientFullName = $user->getFullName(true);
            $stateProvince = $user->getState(true);
            $formattedAmount = $currency->format($currencyCode, $amount, true, false);

            $subGroup[] = array(
                "<a href=\"index.php?frmClientID=".$customerid."&fuse=clients&controller=userprofile&view=profilecontact\">".$clientFullName."</a>",
                (isset($stateProvince))? $stateProvince : '---',
                $formattedAmount
            );
        }

        $subGroup[] = array('', '', '');
        $formattedTotalAmount = $currency->format($currencyCode, $totalAmount, true, false);
        $subGroup[] = array('<b>'.$this->user->lang('Totals').'</b>', '', '<b>'.$formattedTotalAmount.'</b>');

        $this->reportData[] = array(
            "group" => $subGroup,
            "groupname" => "",
            "label" => array(
                $this->user->lang('Client Name'),
                $this->user->lang('State/Province'),
                $this->user->lang('Amount')
            )
        );
    }
}