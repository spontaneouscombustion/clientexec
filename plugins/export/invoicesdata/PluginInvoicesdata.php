<?php
require_once 'modules/admin/models/ExportPlugin.php';

/**
* @package Plugins
*/
class PluginInvoicesdata extends ExportPlugin
{
    protected $_description = 'This export plugin exports invoice data to a CSV file.';
    protected $_title = 'Invoice Data CSV';
    protected $taxNames = array();

    function getForm()
    {
        $this->view->fields = array();
        $fields = array(
            'Invoice ID',
            'Currency',
            'Taxable Amount',
            'Tax Details',
            'Total Amount Before Taxes',
            'Tax amount',
            'Total Amount After Taxes',
            'Balance Due',
            'Client ID',
            'Client Name',
            'Client Last Name',
            'Organization',
            'Country',
            'State',
            'City',
            'Description',
            'Bill Date',
            'Date Paid',
            'Payment Reference',
            'Payment Method',
        );

        for ($i = 0; $i < count($fields); $i++) {
            $this->view->fields[$i]['inputName'] = str_replace(array(' ', '_', '.'), array('_', '__', '___'), $fields[$i]);
            $this->view->fields[$i]['fieldName'] = $this->user->lang($fields[$i]);
            $this->view->fields[$i]['checked'] = 'checked';
        }

        return $this->view->render('PluginInvoicesdata.phtml');
    }

    function process($post)
    {
        $fields = array();
        $filter = array();

        foreach ($post as $fieldname => $value) {
            if (strpos($fieldname, 'invoices_field_') === 0) {
                $fields[] = str_replace(array('___', '__', '_'), array('.', '_', ' '), mb_substr($fieldname, 15));
            } else {
                //check to see if any dates were passed
                if ($fieldname == 'startdate' && $value != '') {
                    $startDateArray = explode('/', $value);

                    if ($this->settings->get('Date Format') == 'm/d/Y' && (bool)preg_match("/^(0[1-9]|1[0-2])\/(0[1-9]|[1-2][0-9]|3[0-1])\/[0-9]{4}$/", $value)) {
                        $temp2StartDate = mktime(0, 0, 0, $startDateArray[0], $startDateArray[1], $startDateArray[2]);
                        $filter['startdate'] = $temp2StartDate;
                    } elseif ($this->settings->get('Date Format') == 'd/m/Y' && (bool)preg_match("/^(0[1-9]|[1-2][0-9]|3[0-1])\/(0[1-9]|1[0-2])\/[0-9]{4}$/", $value)) {
                        $temp2StartDate = mktime(0, 0, 0, $startDateArray[1], $startDateArray[0], $startDateArray[2]);
                        $filter['startdate'] = $temp2StartDate;
                    }
                }

                if ($fieldname == 'enddate' && $value != '') {
                    $endDateArray = explode('/', $value);

                    if ($this->settings->get('Date Format') == 'm/d/Y' && (bool)preg_match("/^(0[1-9]|1[0-2])\/(0[1-9]|[1-2][0-9]|3[0-1])\/[0-9]{4}$/", $value)) {
                        $temp2EndDate = mktime(0, 0, 0, $endDateArray[0], $endDateArray[1], $endDateArray[2]);
                        $filter['enddate'] = $temp2EndDate;
                    } elseif ($this->settings->get('Date Format') == 'd/m/Y' && (bool)preg_match("/^(0[1-9]|[1-2][0-9]|3[0-1])\/(0[1-9]|1[0-2])\/[0-9]{4}$/", $value)) {
                        $temp2EndDate = mktime(0, 0, 0, $endDateArray[1], $endDateArray[0], $endDateArray[2]);
                        $filter['enddate'] = $temp2EndDate;
                    }
                }

                if ($fieldname == 'startdate2' && $value != '') {
                    $startDate2Array = explode('/', $value);

                    if ($this->settings->get('Date Format') == 'm/d/Y' && (bool)preg_match("/^(0[1-9]|1[0-2])\/(0[1-9]|[1-2][0-9]|3[0-1])\/[0-9]{4}$/", $value)) {
                        $temp2StartDate2 = mktime(0, 0, 0, $startDate2Array[0], $startDate2Array[1], $startDate2Array[2]);
                        $filter['startdate2'] = $temp2StartDate2;
                    } elseif ($this->settings->get('Date Format') == 'd/m/Y' && (bool)preg_match("/^(0[1-9]|[1-2][0-9]|3[0-1])\/(0[1-9]|1[0-2])\/[0-9]{4}$/", $value)) {
                        $temp2StartDate2 = mktime(0, 0, 0, $startDate2Array[1], $startDate2Array[0], $startDate2Array[2]);
                        $filter['startdate2'] = $temp2StartDate2;
                    }
                }

                if ($fieldname == 'enddate2' && $value != '') {
                    $endDate2Array = explode('/', $value);

                    if ($this->settings->get('Date Format') == 'm/d/Y' && (bool)preg_match("/^(0[1-9]|1[0-2])\/(0[1-9]|[1-2][0-9]|3[0-1])\/[0-9]{4}$/", $value)) {
                        $temp2EndDate2 = mktime(0, 0, 0, $endDate2Array[0], $endDate2Array[1], $endDate2Array[2]);
                        $filter['enddate2'] = $temp2EndDate2;
                    } elseif ($this->settings->get('Date Format') == 'd/m/Y' && (bool)preg_match("/^(0[1-9]|[1-2][0-9]|3[0-1])\/(0[1-9]|1[0-2])\/[0-9]{4}$/", $value)) {
                        $temp2EndDate2 = mktime(0, 0, 0, $endDate2Array[1], $endDate2Array[0], $endDate2Array[2]);
                        $filter['enddate2'] = $temp2EndDate2;
                    }
                }
            }
        }

        if (!$fields) {
            CE_Lib::redirectPage("index.php?fuse=reports&action=ViewExport");
        }

        $csv = $this->_getInvoicesCSV($fields, $filter, $_POST['invoice_status']);
        CE_Lib::download($csv, $this->user->lang("invoices").'.csv');
    }

    function _getInvoicesCSV($fields, $filter, $status)
    {
        include_once 'modules/billing/models/Currency.php';

        $currency = new Currency($this->user);
        $numFields = count($fields);
        $fieldsMap = array(
            'Invoice ID'                => 'id',
            'Total Amount Before Taxes' => 'subtotal',
            'Total Amount After Taxes'  => 'amount',
            'Balance Due'               => 'balance_due',
            'Description'               => 'description',
            'Bill Date'                 => 'billdate',
            'Date Paid'                 => 'datepaid',
            'Payment Reference'         => 'checknum',
            'Payment Method'            => 'pluginused',
        );

        $dbFields = array();

        foreach ($fieldsMap as $human => $machine) {
            if (in_array($human, $fields)) {
                $dbFields[] = 'i.'.$machine;
            }
        }

        if (!in_array('i.id', $dbFields)) {
            $dbFields[] = 'i.id';
        }

        if (in_array('Tax amount', $fields)) {
            if (!in_array('i.tax', $dbFields)) {
                $dbFields[] = 'i.tax';
            }

            if (!in_array('i.subtotal', $dbFields)) {
                $dbFields[] = 'i.subtotal';
            }

            if (!in_array('i.amount', $dbFields)) {
                $dbFields[] = 'i.amount';
            }
        }

        $dbFields[] = 'i.customerid';
        $dbFields[] = 'i.currency';
        $dbFields = implode(", ", $dbFields);
        $query = "SELECT $dbFields FROM invoice i ";
        $where = false;

        if ($status == 'paid') {
            $query .= " WHERE i.status = 1";
            $where = true;
        } elseif ($status == 'unpaid') {
            $query .= " WHERE i.status IN (0, 5)";
            $where = true;
        }

        if (isset($filter['startdate']) && isset($filter['enddate'])) {
            if ($where) {
                $query .= " AND ";
            } else {
                $query .= " WHERE ";
                $where = true;
            }

            $query .= " (i.billdate BETWEEN '".date("Y-m-d 0:0:0", $filter['startdate'])."' AND '".date("Y-m-d 23:59:59", $filter['enddate'])."') ";
        }

        if (isset($filter['startdate2']) && isset($filter['enddate2'])) {
            if ($where) {
                $query .= " AND ";
            } else {
                $query .= " WHERE ";
                $where = true;
            }

            $query .= " (i.datepaid BETWEEN '".date("Y-m-d 0:0:0", $filter['startdate2'])."' AND '".date("Y-m-d 23:59:59", $filter['enddate2'])."') ";
        }

        $query .= " ORDER BY i.id ASC ";

        $result = $this->db->query($query);
        $fieldstranslated = "";
        $numOfTheField = 1;

        foreach ($fields as $field) {
            if ($field == 'Tax Details') {
                //Get the list of tax names from invoice entries
                $this->_getTaxNames();

                if (count($this->taxNames) > 0) {
                    $fieldstranslated .= '"'.implode('","', $this->taxNames).'"';
                }
            } else {
                $fieldstranslated .= '"'.$this->user->lang($field).'"';
            }

            if ($numOfTheField != $numFields) {
                $fieldstranslated .= ',';
            }

            $numOfTheField ++;
        }

        $csv = $fieldstranslated. "\n";

        $fieldTypes = array(
            'Client Name'      => typeFIRSTNAME,
            'Client Last Name' => typeLASTNAME,
            'Organization'       => typeORGANIZATION,
            'Country'            => typeCOUNTRY,
            'State'              => typeSTATE,
            'City'               => typeCITY
        );

        while ($row = $result->fetch()) {
            for ($i = 0; $i < $numFields; $i++) {
                switch ($fields[$i]) {
                    case 'Client Name':
                    case 'Client Last Name':
                    case 'Organization':
                    case 'Country':
                    case 'State':
                    case 'City':
                        $query = "SELECT `value` FROM `user_customuserfields` uc LEFT JOIN `customuserfields` c ON uc.`customid` = c.`id` WHERE uc.`userid` = ? AND c.`type` = ? ";
                        $result2 = $this->db->query($query, $row['customerid'], $fieldTypes[$fields[$i]]);
                        $row2 = $result2->fetch();
                        $value = $row2['value'];

                        //If organization is empty, use Last Name, First Name
                        if ($fields[$i] == 'Organization' && $value == '') {
                            $result2 = $this->db->query($query, $row['customerid'], typeLASTNAME);
                            $row2 = $result2->fetch();
                            $value = $row2['value'];

                            $result2 = $this->db->query($query, $row['customerid'], typeFIRSTNAME);
                            $row2 = $result2->fetch();

                            if ($value == '') {
                                $value = $row2['value'];
                            } else {
                                $value .= ', '.$row2['value'];
                            }
                        }

                        //Repacle &amp; with &, and &#039; with '
                        $value = str_replace(array("&amp;", "&#039;"), array("&", "'"), $value);

                        $csv .= "\"{$value}\"";
                        break;
                    case 'Client ID':
                        $csv .= "\"{$row['customerid']}\"";
                        break;
                    case 'Currency':
                        $csv .= "\"{$row['currency']}\"";
                        break;
                    case 'Taxable Amount':
                        $csv .= "\"".$currency->format($row['currency'], $this->_getTaxableAmount($row['id']))."\"";
                        break;
                    case 'Tax Details':
                        if (count($this->taxNames) > 0) {
                            $csv .= "\"".$this->_getTaxDetails($row['id'], $row['currency'])."\"";
                        } else {
                            continue 2;
                        }

                        break;
                    case 'Total Amount Before Taxes':
                        $csv .= "\"".$currency->format($row['currency'], $row['subtotal'])."\"";
                        break;
                    case 'Tax amount':
                        $taxAmount = $row['amount'] - $row['subtotal'];
                        $csv .= "\"".$currency->format($row['currency'], $taxAmount)."\"";
                        break;
                    case 'Total Amount After Taxes':
                        $csv .= "\"".$currency->format($row['currency'], $row['amount'])."\"";
                        break;
                    case 'Balance Due':
                        $csv .= "\"".$currency->format($row['currency'], $row['balance_due'])."\"";
                        break;
                    case 'Description':
                        $query = "SELECT `description` FROM `invoiceentry` WHERE `invoiceid` = ? ";
                        $result2 = $this->db->query($query, $row['id']);
                        $invoiceEntryDescriptionArray = array();

                        while ($row2 = $result2->fetch()) {
                            $invoiceEntryDescriptionArray[] = $row2['description'];
                        }

                        if (count($invoiceEntryDescriptionArray) > 0) {
                            //Use the Invoice Entry Descriptions instead of the Invoice Description
                            $csv .= '"' . implode(" - ", $invoiceEntryDescriptionArray) . '"';
                        } else {
                            $csv .= '"' . $row[$fieldsMap[$fields[$i]]] . '"';
                        }

                        break;
                    default:
                        $csv .= '"' . $row[$fieldsMap[$fields[$i]]] . '"';
                        break;
                }

                if ($i == ($numFields - 1)) {
                    $csv .= "\n";
                } else {
                    $csv .= ",";
                }
            }
        }

        $csv = str_replace('Invoice #', $this->user->lang('Invoice #'), $csv);
        return $csv;
    }

    function _getTaxableAmount($invoiceId)
    {
        $total = 0;

        $quantityString = '1 AS `quantity`';
        $configuration = Zend_Registry::get('configuration');

        if (CE_Lib::compareVersions(CE_Lib::getAppVersion($this->db), '5.6.1a3', $configuration['framework']['appVersions'], false) >= 0) {
            $quantityString = '`quantity`';
        }

        $query = "SELECT `price`, ".$quantityString.", `taxable` FROM `invoiceentry` WHERE `invoiceid` = ? ";
        $result = $this->db->query($query, $invoiceId);

        while ($row = $result->fetch()) {
            if ($row['taxable'] == '1') {
                $total += $row['price']*$row['quantity'];
            }
        }

        return $total;
    }

    function _getTaxNames()
    {
        if (count($this->taxNames) > 0) {
            return;
        }

        $query = "SELECT DISTINCT `taxname`, `tax`, `tax2name`, `tax2` FROM `invoice` ORDER BY `taxname` ASC, `tax` ASC, `tax2name` ASC, `tax2` ASC ";
        $result = $this->db->query($query);

        while ($row = $result->fetch()) {
            if ($row['taxname'] != '') {
                $this->taxNames[$row['taxname'].' ('.(float) $row['tax'].'%)'] = $row['taxname'].' ('.(float) $row['tax'].'%)';
            }

            if ($row['tax2name'] != '') {
                $this->taxNames[$row['tax2name'].' ('.(float) $row['tax2'].'%)'] = $row['tax2name'].' ('.(float) $row['tax2'].'%)';
            }
        }
    }

    function _getTaxDetails($invoiceId, $currencyCode)
    {
        if (count($this->taxNames) > 0) {
            $invoice = new Invoice($invoiceId);
            $taxDetails = $invoice->getTaxDetails();
            $currency = new Currency($this->user);

            $allTaxesArray = array();

            foreach ($this->taxNames as $taxName) {
                $allTaxesArray[$taxName] = '';

                if ($taxDetails['1']['Name'].' ('.(float) $taxDetails['1']['Percentage'].'%)' == $taxName) {
                    $allTaxesArray[$taxName] = $currency->format($currencyCode, $taxDetails['1']['Amount']);
                } elseif ($taxDetails['2']['Name'].' ('.(float) $taxDetails['2']['Percentage'].'%)' == $taxName) {
                    $allTaxesArray[$taxName] = $currency->format($currencyCode, $taxDetails['2']['Amount']);
                }
            }

            $allTaxesString = implode('","', $allTaxesArray);
            return $allTaxesString;
        } else {
            return '';
        }
    }
}
