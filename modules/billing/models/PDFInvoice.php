<?php
/**
 * @category Models
 * @package  Billing
 * @author   Alberto Vasquez <alberto@clientexec.com>
 * @license  ClientExec License
 * @link     http://www.clientexec.com
 */

require_once 'modules/billing/models/InvoiceEntry.php';
require_once 'modules/billing/models/Invoice.php';
require_once 'modules/billing/models/Currency.php';
require_once 'modules/clients/models/UserPackage.php';
require_once 'library/CE/NE_PluginCollection.php';

class PDFInvoice extends NE_Model
{

    public $user;
    public $invoice;
    public $pdf;
    public $html;
    public $view;
    protected $template = "";
    private $invoice_customer = null;

    function __construct($tuser, $invoiceID)
    {
        parent::__construct();

        $this->view = new CE_View();
        $settings = new CE_Settings();

        $this->invoice = new Invoice($invoiceID);
        $this->invoice_customer = new User($this->invoice->getUserID());
        //get path and add to path

        $this->template = $this->invoice_customer->getInvoiceTemplate();
        if ($this->template == "") {
            $this->template = $settings->get('Invoice Template');
        }

        if (is_dir(APPLICATION_PATH.'/../plugins/invoices/'.$this->template)) {
            $tplDir = APPLICATION_PATH.'/../plugins/invoices/'.$this->template;
        } else {
            $tplDir = APPLICATION_PATH.'/../plugins/invoices/default';
        }

        // keep old paths, so that templates in controllers still work
        $paths = $this->view->getScriptPaths();
        $paths[] = $tplDir;
        $this->view->setScriptPath($paths);

        $this->user = $tuser;
        $this->generateHtml();
        $this->generatePdf();
    }

    function generatePDF()
    {
        @ini_set("memory_limit", "128M");
        $config = [
            'mode'             => '+aCJK',
            'autoScriptToLang' => true,
            'autoLangToFont'   => true,
            'setAutoTopMargin' => 'pad',
            'setAutoBottomMargin' => 'pad',
            'tempDir' => __DIR__ . '/../../../uploads/cache/'
        ];
        $this->pdf = new \Mpdf\Mpdf($config);
        $this->pdf->useSubstitutions = false;
        $this->pdf->simpleTables = true;

        if (file_exists('plugins/invoices/'.$this->template.'/style.css')) {
            $stylesheet = file_get_contents('plugins/invoices/'.$this->template.'/style.css');
            $this->pdf->WriteHTML($stylesheet, 1);
            $this->pdf->WriteHTML($this->html, 2);
        } else {
            $this->pdf->WriteHTML($this->html);
        }

        // set document information
        $this->pdf->SetCreator('Clientexec');
        $this->pdf->SetAuthor('Clientexec');
        $this->pdf->SetTitle('Invoice #' . $this->invoice->getId());
        $this->pdf->SetSubject('Invoice #' . $this->invoice->getId());
        $this->pdf->SetKeywords('Clientexec, Invoice, Billing');
    }

    function generateHtml()
    {
        $languages = CE_Lib::getEnabledLanguages();
        include_once 'modules/admin/models/Translations.php';
        $translations = new Translations();
        $languageKey = ucfirst(strtolower($this->invoice_customer->getRealLanguage()));
        CE_Lib::setI18n($languageKey);

        $currency = new Currency($this->user);

        $invoiceCurrency = $this->invoice->getCurrency();
        $paidLabel = "";
        $paidDate = "";
        $paymentLabel = "";
        $paymentMethod = "";
        $pmtRef = "";
        $vatNumber = "";
        $vatInvoice = false;

        if ($this->invoice->isPaid() && $this->invoice->getDatePaid("timestamp") != '') {
            $paidDate = date($this->settings->get('Date Format'), $this->invoice->getDatePaid("timestamp"));
        }

        $paymentLabel = $this->user->lang('Payment Method');
        if ($this->invoice->m_PluginUsed != 'none') {
            $paymentMethod = $this->invoice->m_PluginUsed;
        }

        if ($paymentMethod != "") {
            $pluginCollection = new NE_PluginCollection('gateways', $this->user);
            $variables = $pluginCollection->callFunction($paymentMethod, 'getVariables');
            if (isset($variables['Plugin Name']['value'])) {
                $paymentMethod = $variables['Plugin Name']['value'];
            }
        }

        if ($this->invoice->isPaid() && $this->invoice->m_CheckNum != '') {
            $pmtRef = $this->invoice->m_CheckNum;
        }

        //Add spaces based on logo size
        //if user has logo add spacer
        $invoicelogo = "";
        if (file_exists('images/invoicelogo.jpg')) {
            $invoicelogo = 'images/invoicelogo.jpg';
        } elseif (file_exists('images/invoicelogo.png')) {
            $invoicelogo = 'images/invoicelogo.png';
        }

        //If invoice is paid, void, refunded or credited then add stamp image
        $this->view->status = "";
        if ($this->invoice->isPaid()) {
            $this->view->status = "paid";
        } elseif ($this->invoice->isVoid()) {
            $this->view->status = "void";
        } elseif ($this->invoice->isRefunded()) {
            $this->view->status = "refund";
        } elseif ($this->invoice->isCredited()) {
            $this->view->status = "credited";
        } elseif ($this->invoice->isDraft()) {
            $this->view->status = "draft";
        }

        $customerAddress = array();
        if ($this->invoice_customer->getAddress() != "") {
            $customerAddress[] = $this->invoice_customer->getAddress();
        }
        if ($this->invoice_customer->getCity() != "" && $this->invoice_customer->getState(true) != "") {
            $customerAddress[] = $this->invoice_customer->getCity() .", " .$this->invoice_customer->getState(true);
        } elseif ($this->invoice_customer->getCity() != "") {
            $customerAddress[] = $this->invoice_customer->getCity();
        } elseif ($this->invoice_customer->getState(true) != "") {
            $customerAddress[] = $this->invoice_customer->getState(true);
        }
        if ($this->invoice_customer->getZipCode() != "") {
            $customerAddress[] = $this->invoice_customer->getZipCode();
        }
        if ($this->invoice_customer->getCountry(true) != "") {
            $customerAddress[] = $this->invoice_customer->getCountry(true);
        }
        $cAddr = implode("<br />", $customerAddress);

        $customerOrg = "";
        if (trim($this->invoice_customer->getOrganization()) != "") {
            $customerOrg = $this->invoice_customer->getOrganization();
        }

        //If the client has a valid VAT number, show it, even if it is not a VAT Invoice
        if ($this->invoice_customer->getVatValidation() != '') {
            $vatNumber = $this->invoice_customer->getVatValidation();
        }

        //If the default country is part of the European Union and there are tax rules set as VAT, show it as a VAT Invoice
        include_once 'modules/billing/models/BillingGateway.php';
        $billingGateway = new BillingGateway($this->user);

        if ($billingGateway->isEUcountry($this->settings->get("Default Country")) && $billingGateway->haveVATrules()) {
            $vatInvoice = true;
        }

        //If the client is under a Tax Rule that uses VAT, show it as a VAT Invoice
        if ($this->invoice_customer->withinVATCountry()) {
            $vatInvoice = true;
        }

        $AdditionalNotesForInvoicesSettingValue = $this->settings->get('Additional Notes For Invoices');
        $AdditionalNotesForInvoicesSettingId = $this->settings->getSettingIdForName('Additional Notes For Invoices');

        $InvoiceFooterSettingValue = $this->settings->get('Invoice Footer');
        $InvoiceFooterSettingId = $this->settings->getSettingIdForName('Invoice Footer');

        $InvoiceDisclaimerSettingValue = $this->settings->get('Invoice Disclaimer');
        $InvoiceDisclaimerSettingId = $this->settings->getSettingIdForName('Invoice Disclaimer');

        if (count($languages) > 1) {
            if ($AdditionalNotesForInvoicesSettingId !== false) {
                $AdditionalNotesForInvoicesSettingValue = $translations->getValue(SETTING_VALUE, $AdditionalNotesForInvoicesSettingId, $languageKey, $AdditionalNotesForInvoicesSettingValue);
            }

            if ($InvoiceFooterSettingId !== false) {
                $InvoiceFooterSettingValue = $translations->getValue(SETTING_VALUE, $InvoiceFooterSettingId, $languageKey, $InvoiceFooterSettingValue);
            }

            if ($InvoiceDisclaimerSettingId !== false) {
                $InvoiceDisclaimerSettingValue = $translations->getValue(SETTING_VALUE, $InvoiceDisclaimerSettingId, $languageKey, $InvoiceDisclaimerSettingValue);
            }
        }

        $this->view->companyEmail              = $this->settings->get("Billing E-mail");
        $this->view->user                      = $this->invoice_customer;
        $this->view->companyName               = $this->settings->get("Company Name");
        $this->view->companyAddress            = trim($this->settings->get("Company Address"));
        $this->view->companyURL                = $this->settings->get("Company URL");
        $this->view->invoice                   = ($vatInvoice)? $this->user->lang('VAT Invoice') : $this->user->lang('Invoice');
        $this->view->invoiceNum                = $this->invoice->getId();
        $this->view->duedate                   = date($this->settings->get('Date Format'), $this->invoice->getDate("timestamp"));
        $this->view->duedateMonth              = $this->invoice->getDate("timestamp");
        $this->view->datecreated               = date($this->settings->get('Date Format'), $this->invoice->getDateCreated("timestamp"));
        $this->view->invoicelogo               = $invoicelogo;
        $this->view->paidDate                  = $paidDate;
        $this->view->paymentLabel              = $paymentLabel;
        $this->view->paymentMethod             = $paymentMethod;
        $this->view->pmtRef                    = $pmtRef;
        $this->view->pmtTransactions           = $this->ParseInvoiceTransactions($this->invoice->getId());
        $this->view->pmtLastTransaction        = $this->ParseInvoiceTransactions($this->invoice->getId(), 1);
        $this->view->pmtSuccessfulTransactions = ($this->settings->get("Display Successful Invoice Payment Transactions") == 1)? $this->ParseInvoiceTransactions($this->invoice->getId(), 0, true) : "";
        $this->view->vatNumber                 = $vatNumber;
        $this->view->customerNum               = $this->invoice->getUserID();
        $this->view->customerOrg               = $customerOrg;
        $this->view->customerName              = $this->invoice_customer->getFirstName().' '.$this->invoice_customer->getLastName();
        $this->view->customerAddress           = $cAddr;
        $this->view->customerPhone             = $this->invoice_customer->getPhone();
        $this->view->customerEmail             = $this->invoice_customer->getEmail();
        $this->view->footerContent             = trim($InvoiceFooterSettingValue);
        $this->view->disclaimerContent         = trim($InvoiceDisclaimerSettingValue);
        $additionalnotes                       = trim($this->invoice->getNote());

        //Replace tags here
        $AdditionalNotesForInvoicesSettingValue = str_replace("[DIRECTPAYMENTLINK]", $billingGateway->createDirectPaymentLink($this->invoice_customer, $this->invoice->getId()), $AdditionalNotesForInvoicesSettingValue);
        //Replace tags here

        if ($additionalnotes == '') {
            $additionalnotes = trim($AdditionalNotesForInvoicesSettingValue);
        } else {
            $additionalnotes .= "\n".trim($AdditionalNotesForInvoicesSettingValue);
        }

        $this->view->additionalnotes      = $additionalnotes;

        // If the customer is taxable show the tax header
        $taxName = $this->invoice->getTaxName();
        if ($taxName == '') {
            $taxName = $this->user->lang('Tax');
        }

        $tax2Name = $this->invoice->getTax2Name();
        if ($tax2Name == '') {
            $tax2Name = $this->user->lang('Tax');
        }

        $invoiceheaders = array();
        $columnWidth = array();
        $totalHeaders = 2;

        //Defining variables to know which tax columns to display
        $showTax1 = false;
        $showTax2 = false;
        if ($this->invoice_customer->isTaxable()) {
            $taxRate1 = $this->invoice_customer->getTaxRate(1);
            $taxRate2 = $this->invoice_customer->getTaxRate(2);
            $withinVATCountry = $this->invoice_customer->withinVATCountry();
            $vat1 = $this->invoice_customer->vat;
            $vat2 = $this->invoice_customer->vat2;

            if (($withinVATCountry && $vat1) || $taxRate1 > 0) {
                $showTax1 = true;
            }

            if (($withinVATCountry && $vat2) || $taxRate2 > 0) {
                $showTax2 = true;
            }
        }

        if ($showTax1 && $showTax2) {
            $invoiceheaders = array($this->user->lang('Invoice Items'),$this->user->lang('Price'),$taxName,$tax2Name);
            $columnWidth = array("55%","15%","15%","15%");
            $totalHeaders = 4;
        } elseif ($showTax1) {
            $invoiceheaders = array($this->user->lang('Invoice Items'),$this->user->lang('Price'),$taxName);
            $columnWidth = array("70%","15%","15%");
            $totalHeaders = 3;
        } elseif ($showTax2) {
            $invoiceheaders = array($this->user->lang('Invoice Items'),$this->user->lang('Price'),$tax2Name);
            $columnWidth = array("70%","15%","15%");
            $totalHeaders = 3;
        } else {
            $invoiceheaders = array($this->user->lang('Invoice Items'),$this->user->lang('Price'));
            $columnWidth = array("85%","15%");
            $totalHeaders = 2;
        }

        $this->view->invoiceheaders = array();
        for ($i = 0; $i < count($invoiceheaders); $i++) {
            $width = $columnWidth[$i];
            if ($columnWidth[$i] == "15%") {
                $align = "right";
            } else {
                $align = "left";
            }
            $header = array();
            $header['width'] = $width;
            $header['align'] = $align;
            $header['text'] = $invoiceheaders[$i];
            $this->view->invoiceheaders[] = $header;
        }

        $totalTaxed = 0;
        $totalTaxed2 = 0;

        $this->view->invoiceEntries = array();

        include_once 'modules/billing/models/InvoiceEntriesGateway.php';
        $InvoiceEntriesGateway = new InvoiceEntriesGateway($this->user);

        foreach ($this->invoice->getInvoiceEntries('id', 'asc') as $invoiceEntry) {
            $entry = array();
            $entry['data'] = array();

            $invoice_label = $InvoiceEntriesGateway->getFullEntryDescription($invoiceEntry->getId(), $languageKey);

            $tPrice = $invoiceEntry->getPrice() * $invoiceEntry->getQuantity();
            // If the customer is taxable show the tax pricing

            $addonQuantity = '';
            if ($invoiceEntry->getQuantity() != 1 && $invoiceEntry->getQuantity() >= 0) {
                $addonQuantity = (float)$invoiceEntry->getQuantity().' x '.$currency->format($invoiceCurrency, $invoiceEntry->getPrice(), true, "NONE", false, true).'/'.$this->user->lang("each").'<br/>';
            }

            $details = $this->user->lang(nl2br($invoiceEntry->getDetail()));

            $invoice_description = $addonQuantity . $this->getPeriod($invoiceEntry, $this->user) . (($details != '')? $details : '');
            $invoice_description = str_replace("<br>", "<br/>", $invoice_description);
            $invoice_label = $billingGateway->translateText($invoice_label, $this->user);
            $invoice_description = $billingGateway->translateText($invoice_description, $this->user);
            $dataLabel = array(trim($invoice_label), trim($invoice_description));

            $taxPrice = 0;
            if ($invoiceEntry->getTaxable() && $showTax1) {
                $taxPrice = $invoiceEntry->getPrice() * $invoiceEntry->getQuantity() * $this->invoice->getTax() / 100;

                if ($invoiceEntry->isCoupon()) {
                    $taxPrice = $this->invoice->getTaxesDiscount($invoiceEntry->AppliesTo());
                }

                $totalTaxed += $taxPrice;
                $strTaxPrice = $currency->format($invoiceCurrency, $taxPrice, true, "NONE", false, true);
            } else {
                $strTaxPrice = $this->user->lang('Not Taxable');
            }

            $tax2Price = 0;
            if ($invoiceEntry->getTaxable() && $showTax2) {
                if ($taxPrice > 0 && $this->invoice->isTax2Compound()) {
                    $tax2Price = ($invoiceEntry->getPrice() * $invoiceEntry->getQuantity() + $taxPrice) * $this->invoice->GetTax2() / 100;
                } else {
                    $tax2Price = $invoiceEntry->getPrice() * $invoiceEntry->getQuantity() * $this->invoice->GetTax2() / 100;
                }

                if ($invoiceEntry->isCoupon()) {
                    $tax2Price = $this->invoice->getTaxesDiscount($invoiceEntry->AppliesTo(), 2);
                }

                $totalTaxed2 += $tax2Price;
                $strTax2Price = $currency->format($invoiceCurrency, $tax2Price, true, "NONE", false, true);
            } else {
                $strTax2Price = $this->user->lang('Not Taxable');
            }

            if ($showTax1 && $showTax2) {
                $price = $currency->format($invoiceCurrency, $tPrice, true, "NONE", false, true);
                $data = array($dataLabel,$price,$strTaxPrice,$strTax2Price);
                $columnWidth = array("55%","15%","15%","15%");

                for ($i = 0; $i < count($data); $i++) {
                    $innerData = array();
                    $innerData['width'] = $columnWidth[$i];
                    $innerData['data'] = $data[$i];

                    if ($columnWidth[$i] == "15%") {
                        $innerData['align'] = 'right';
                    } else {
                        $innerData['align'] = 'left';
                    }

                    $entry['data'][] = $innerData;
                }
            } elseif ($showTax1) {
                $price = $currency->format($invoiceCurrency, $tPrice, true, "NONE", false, true);
                $data = array();
                $columnWidth = array();
                $data = array($dataLabel,$price,$strTaxPrice);
                $columnWidth = array("70%","15%","15%");

                for ($i = 0; $i < count($columnWidth); $i++) {
                    $innerData = array();

                    if ($columnWidth[$i] == "15%") {
                        $innerData['align'] = 'right';
                    } else {
                        $innerData['align'] = 'left';
                    }

                    $innerData['width'] = $columnWidth[$i];
                    $innerData['data'] = $data[$i];
                    $entry['data'][] = $innerData;
                }
            } elseif ($showTax2) {
                $price = $currency->format($invoiceCurrency, $tPrice, true, "NONE", false, true);
                $data = array($dataLabel,$price,$strTax2Price);
                $columnWidth = array("70%","15%","15%");

                for ($i = 0; $i < count($data); $i++) {
                    $innerData = array();

                    if ($columnWidth[$i] == "15%") {
                        $innerData['align'] = 'right';
                    } else {
                        $innerData['align'] = 'left';
                    }

                    $innerData['width'] = $columnWidth[$i];
                    $innerData['data'] = $data[$i];
                    $entry['data'][] = $innerData;
                }
            } else {
                $price = $currency->format($invoiceCurrency, $tPrice, true, "NONE", false, true);
                $data = array();
                $data = array($dataLabel,$price);
                $columnWidth = array("85%","15%");

                for ($i = 0; $i < count($data); $i++) {
                    $innerData = array();

                    if ($columnWidth[$i] == "15%") {
                        $innerData['align'] = 'right';
                    } else {
                        $innerData['align'] = 'left';
                    }

                    $innerData['width'] = $columnWidth[$i];
                    $innerData['data'] = $data[$i];
                    $entry['data'][] = $innerData;
                }
            }

            $this->view->invoiceEntries[] = $entry;
        }

        $totalLabels = array();
        $totalPrices = array();
        $tTaxTotal = $totalTaxed;
        $tTax2Total = $totalTaxed2;
        $tDisplaySubTotal =  $this->invoice->getSubTotal();
        $tTax = $this->invoice->GetTax();
        $tTax2 = $this->invoice->GetTax2();
        $tPrice =  $this->invoice->getPrice();

        // If the customer is taxable show the tax information
        if ($showTax1 || $showTax2) {
            $totalLabels[] = $this->user->lang('Subtotal').':';
            $totalPrices[] = $currency->format($invoiceCurrency, $tDisplaySubTotal, true, "NONE", false, true);

            if ($showTax1) {
                $totalLabels[] = $taxName . ' (' . (float)$tTax . '%):';
                $totalPrices[] = $currency->format($invoiceCurrency, $tTaxTotal, true, "NONE", false, true);
            }

            if ($showTax2) {
                $totalLabels[] = $tax2Name . ' (' . (float)$tTax2 . '%):';
                $totalPrices[] = $currency->format($invoiceCurrency, $tTax2Total, true, "NONE", false, true);
            }
        }

        if ($this->invoice->isPaid()) {
            $totalLabels[] = $this->user->lang('Total Paid').':';
        } else {
            $totalLabels[] = $this->user->lang('Total Due').':';
        }

        $totalPrices[] = $currency->format($invoiceCurrency, $tPrice, true, "NONE", false, true);

        //  ADDED FOR: VARIABLE_PAYMENTS - Balance Due  //
        if ($this->invoice->isPartiallyPaid()) {
            $tBalanceDue = $this->invoice->getBalanceDue();
            $PartiallyPaid = $tPrice - $tBalanceDue;
            $totalLabels[] = $this->user->lang('Paid').':';
            $totalPrices[] = $currency->format($invoiceCurrency, $PartiallyPaid, true, "NONE", false, true);
            $totalLabels[] = $this->user->lang('Balance Due').':';
            $totalPrices[] = $currency->format($invoiceCurrency, $tBalanceDue, true, "NONE", false, true);
        }

        $this->view->totalLabels = array();

        for ($i = 0; $i < count($totalLabels); $i++) {
            $total = array();

            if ($totalHeaders == 4) {
                $total["colspan"] = "colspan='3' ";
            } elseif ($totalHeaders == 3) {
                $total["colspan"] = "colspan='2' ";
            } else {
                $total["colspan"] = " ";
            }

            $total["totalLabel"] = $totalLabels[$i];
            $total["totalPrice"] = $totalPrices[$i];
            $this->view->totalLabels[] = $total;
        }

        $this->html = $this->view->render('invoice.phtml');
    }

    function ParseInvoiceTransactions($invoiceid, $limit = 0, $onlySuccessfulTransactions = false)
    {
        $invoicetransactions = "";

        if ($invoiceid != 0) {
            //get all transactions and loop thru to show them
            $query = "SELECT `id`, `accepted`, `response`, `transactiondate` AS transdate, `transactionid` "
                ."FROM `invoicetransaction` "
                ."WHERE `invoiceid` = ? "
                .(($onlySuccessfulTransactions)? "AND `accepted` = 1 " : "")
                ."ORDER BY `transactiondate` DESC "
                .(($limit > 0)? "LIMIT $limit " : "");
            $result = $this->db->query($query, $invoiceid);
            $num = $result->getNumRows();

            while (list($transid, $accepted, $response, $transdate, $transactionid) = $result->fetch()) {
                $transdate = strtotime($transdate);
                $invoicetransactions .= date($this->settings->get('Date Format'), $transdate)." ".date("h:i:s A", $transdate)." - Transaction: ".$transid." - ";

                $classResponse = '';
                if ($accepted == 0) {
                    $classResponse = 'red';
                }

                $gatewayTransId = '';
                if (!in_array($transactionid, array('', 'NA')) && strpos($response, 'Payment Reference') === false) {
                    $gatewayTransId = " (Payment Reference: $transactionid)";
                }

                //$response = NE_Lib::convertCurrencyToHTML($response);
                $invoicetransactions .= "<font class='.$classResponse.'>".$response.$gatewayTransId."</font>";
                $invoicetransactions .= "<br/>";
            }

            if ($num==0) {
                $invoicetransactions = "<font class=red>".$this->user->lang("There are no recorded transactions for this invoice")."</font>";
            }

            $invoicetransactions = "<p style='padding-left:25px;'>".$invoicetransactions."</p>";
        }

        return $invoicetransactions;
    }

    function show()
    {
        $filename = 'invoice-'.$this->invoice->getId().'.pdf';
        $this->pdf->Output($filename, 'I');
    }

    function save()
    {
        $filename = 'uploads/cache/invoice-'.$this->invoice->getId().'.pdf';
        $this->pdf->Output($filename, 'F');
    }

    function delete()
    {
        $filename = 'uploads/cache/invoice-'.$this->invoice->getId().'.pdf';
        unlink($filename);
    }

    function get()
    {
        return $this->pdf->Output('', 'S');
    }

    private function getPeriod($invoiceEntry, $user)
    {
        $period = '';
        $daterangearray = unserialize($this->settings->get('Invoice Entry Date Range Format'));

        if ($invoiceEntry->getPeriodStart() && $daterangearray[0] != '') {
            $period = CE_Lib::formatDateWithPHPFormat($invoiceEntry->getPeriodStart(), $daterangearray[0]);

            if ($invoiceEntry->getPeriodEnd() && $daterangearray[1] != '') {
                $period .= ' '.$user->lang('thru').' ';
                $period .=  CE_Lib::formatDateWithPHPFormat($invoiceEntry->getPeriodEnd(), $daterangearray[1]);
            }

            $period .= '<br />';
        }

        return $period;
    }
}
