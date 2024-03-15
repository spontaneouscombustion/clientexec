<?php

include_once 'modules/billing/models/InvoiceListGateway.php';
include_once 'modules/billing/models/Currency.php';

/**
 * Billing Module's Invoice Controller
 *
 * @category   Action
 * @package    Billing
 * @author     Alberto Vasquez <alberto@clientexec.com>
 * @license    http://www.clientexec.com  ClientExec License Agreement
 * @link       http://www.clientexec.com
 */
class Billing_InvoicepublicController extends CE_Controller_Action
{

    var $moduleName = "billing";

    public function generatepdfinvoiceAction()
    {
        require_once 'modules/billing/models/PDFInvoice.php';

        $this->disableLayout();

        $invoiceId = $this->getParam('invoiceid', FILTER_SANITIZE_NUMBER_INT);
        $invoice = new Invoice($invoiceId);

        if ($this->user->isAdmin() || $this->user->getId() == $invoice->getUserID()) {
            $pdfInvoice = new PDFInvoice($this->user, $invoiceId);
            $pdfInvoice->show();
        }
    }

    protected function payinvoiceAction()
    {

        $params = array();
        $params['fromDirectLink'] = false;

        $this->disableLayout();

        $invoiceid = $this->getParam('id', FILTER_SANITIZE_NUMBER_INT);
        $passedHash = $this->getParam('hash', FILTER_SANITIZE_STRING, '');

        $invoice = new Invoice($invoiceid);
        $customerid = $invoice->getUserID();

        if ($passedHash != '') {
            $hash = md5(SALT . $invoiceid . $customerid);

            //Validate the hash passed
            if ($hash != $passedHash) {
                CE_Lib::redirectPage("index.php", $this->user->lang("We are sorry. You have either reached this page incorrectly or your invoice can not be paid."));
            }

            $this->user = new User($customerid);
            $params['fromDirectLink'] = true;
        }

        $plugin = $this->getParam('paymentMethod', FILTER_SANITIZE_STRING);
        if ($this->user->hasPermission('clients_edit_payment_type')) {
            $makeDefault = (int) $this->getParam('makeDefault', FILTER_SANITIZE_NUMBER_INT, 0);
        } else {
            $makeDefault = 0;
        }

        if ($customerid != $this->user->getId()) {
            CE_Lib::log(1, $this->user->getId()." is trying to pay for an invoice (".$invoiceid.") that doesn't belong to them");
            CE_Lib::addErrorMessage($this->user->lang('Invoice was not found'));
            CE_Lib::redirectPage("index.php?fuse=billing&controller=invoice&view=allinvoices");
            exit;
        }

        $gateway = new BillingGateway($this->user);
        $params['invoiceId'] = $invoiceid;
        $params['isSignup'] = false;
        $params['plugin'] = $plugin;

        // NOT SURE HOW TO SANITIZE AN ARRAY
        $plugincustomfields = array();
        $params['plugincustomfields'] = $this->getParam($plugin.'_plugincustomfields', null, $plugincustomfields);

        $params['cc_num'] = @$_REQUEST[@$plugin.'_ccNumber'];
        $params['cc_month'] = @$_REQUEST[@$plugin.'_ccMonth'];
        $params['cc_year'] = @$_REQUEST[@$plugin.'_ccYear'];
        if (isset($_REQUEST[@$plugin.'_ccMonth']) && isset($_REQUEST[@$plugin.'_ccYear'])) {
            $params['cc_exp'] = sprintf("%02d", $_REQUEST[@$plugin.'_ccMonth'])."/".$_REQUEST[@$plugin.'_ccYear'];
            if (isset($_REQUEST[@$plugin.'_ccCVV2'])) {
                $params['cc_CVV2'] = htmlspecialchars($_REQUEST[@$plugin.'_ccCVV2']);
            }

            if ($params['cc_num'] == '') {
                CE_Lib::addErrorMessage($this->user->lang("Please enter a valid credit card."));
                CE_Lib::redirectPage("index.php?fuse=billing&controller=invoice&view=invoice&id={$invoiceid}");
                exit;
            }

            $validCC = new Zend_Validate_CreditCard();
            if (!$validCC->isValid($params['cc_num'])) {
                CE_Lib::addErrorMessage($this->user->lang("Please enter a valid credit card."));
                CE_Lib::redirectPage("index.php?fuse=billing&controller=invoice&view=invoice&id={$invoiceid}");
                exit;
            }
        }

        if ($makeDefault) {
            //Call here a function to update the payment method
            require_once 'modules/clients/models/UserGateway.php';
            $userGateway = new UserGateway($this->user);
            $plugincustomfields = array();
            $userGateway->updateGatewayInformation($this->user, $params['plugin'], $plugincustomfields, $params['cc_num'], $params['cc_month'], $params['cc_year']);
        }

        $retArray = $gateway->actOnInvoices($params);

        if ($retArray['error']) {
            CE_Lib::addErrorMessage("<b>".$this->user->lang("There was an error processing this invoice.")."</b><br/>".$this->user->lang("If the issue persists please contact us."));

            if ($passedHash != '') {
                $url = 'index.php';
            } else {
                $url = "index.php?fuse=billing&controller=invoice&view=invoice&id={$invoiceid}";
            }

            CE_Lib::redirectPage($url);
            exit;
        } else {
            $strMsg = $this->user->lang("Invoice(s) were processed successfully");

            // Redirect to the invoice if there's still a balance due.
            if ($passedHash != '') {
                $url = 'index.php';
            } elseif ($invoice->getBalanceDue() > 0) {
                $url = 'index.php?fuse=billing&controller=invoice&view=invoice&id=' . $invoiceid;
            } else {
                $url = 'index.php?fuse=billing&controller=invoice&view=allinvoices';
            }

            CE_Lib::redirectPage($url, $strMsg);
            exit;
        }
    }

    protected function applyaccountcreditAction()
    {
        include_once 'modules/billing/models/Invoice_EventLog.php';
        $this->disableLayout();

        $invoiceid = $this->getParam('id', FILTER_SANITIZE_NUMBER_INT);
        $invoice = new Invoice($invoiceid);

        $customerid = $invoice->getUserID();
        if ($customerid != $this->user->getId()) {
            CE_Lib::log(1, $this->user->getId()." is trying to apply account credit to an invoice (".$invoiceid.") that doesn't belong to them");
            CE_Lib::addErrorMessage($this->user->lang('Invoice was not found'));
            CE_Lib::redirectPage("index.php?fuse=billing&controller=invoice&view=allinvoices");
        }


        if ($this->user->hasPermission('billing_apply_account_credit')) {
            $billingGateway = new BillingGateway($this->user);
            $invoicesData = $billingGateway->processApplyAccountCredits($invoiceid);

            if (isset($invoicesData[trim($invoiceid)])) {
                $invoiceLog = Invoice_EventLog::newInstance(false, $invoicesData[trim($invoiceid)]['userID'], trim($invoiceid), INVOICE_EVENTLOG_APPLY_ACCOUNT_CREDIT, $this->user->getId(), serialize($invoicesData[trim($invoiceid)]));
                $invoiceLog->save();
                $strMsg = $this->user->lang("Invoice(s) were processed successfully");
                if ($invoice->getBalanceDue() > 0) {
                    $url = 'index.php?fuse=billing&controller=invoice&view=invoice&id=' . $invoiceid;
                } else {
                    $url = 'index.php?fuse=billing&controller=invoice&view=allinvoices';
                }
                CE_Lib::redirectPage($url, $strMsg);
            }
        }
    }

    /**
     * viewing an invoice
     * @return [type] [description]
     */
    protected function invoiceAction()
    {
        $currency = new Currency($this->user);
        $languages = CE_Lib::getEnabledLanguages();
        include_once 'modules/admin/models/Translations.php';
        $translations = new Translations();
        $languageKey = ucfirst(strtolower($this->user->getLanguage()));

        $this->view->setLfiProtection(false);
        $this->checkPermissions();

        $this->title = $this->user->lang('Invoice');
        $invoice_id = $this->getParam('id', FILTER_SANITIZE_NUMBER_INT);

        $canceled = $this->getParam('cancel', FILTER_SANITIZE_NUMBER_INT, 0);
        $paid = $this->getParam('paid', FILTER_SANITIZE_NUMBER_INT, 0);

        //this is only passed when we get to this view right after a payment was made
        if ($paid) {
            CE_Lib::addSuccessMessage("<b>".$this->user->lang("Thank you very much for your payment.")."</b><br/>".$this->user->lang("Please be advised that your invoice might not reflect payment until we receive notification from gateway."));
        }
        //this is only passed when we get to this view right after a payment was canceled
        if ($canceled) {
            CE_Lib::addMessage("<b>".$this->user->lang("You have canceled payment on your invoice.")."</b><br/>".$this->user->lang("Please submit a support ticket if you need any assistance."));
        }

        $this->view->invoice_id = $invoice_id;
        include_once("modules/billing/models/BillingGateway.php");
        $billingGateway = new BillingGateway($this->user);
        $data = $billingGateway->getinvoice($invoice_id, $languageKey);

        $invoice = new Invoice($invoice_id);
        $this->view->subscription_id = $invoice->getSubscriptionID();
        $userid = $invoice->getCustomerId();
        if ($userid != $this->user->getId()) {
            CE_Lib::log(1, $this->user->getId()." is trying to view an invoice (".$invoice_id.") that doesn't belong to them");
            CE_Lib::addErrorMessage($this->user->lang('Invoice was not found'));
            CE_Lib::redirectPage("index.php?fuse=billing&controller=invoice&view=allinvoices");
        }

        $this->view->invoice_paid = $invoice->isPaid();
        $this->view->invoice_status_name = $this->user->lang($invoice->getStatusName());
        $this->view->invoice_status_class = $invoice->getStatusClass();
        $this->view->invoice_sent_date = $invoice->getSentDate();
        $this->view->invoice_date = $invoice->getDate();
        $this->view->invoiceNotes = $invoice->getNote();
        $this->view->invoiceBalanceDue = $invoice->getBalanceDue();
        $this->view->creditBalance = $this->user->getCreditBalance();
        $this->view->canApplyAccountCredit = ($invoice->getCurrency() == $this->user->getCurrency() && $invoice->getBalanceDue() > 0 && $this->user->hasPermission('billing_apply_account_credit') && $this->user->getCreditBalance() > 0)? true : false;
        $this->view->formattedCreditBalance = $currency->format($this->user->getCurrency(), $this->user->getCreditBalance(), true);
        $this->view->invoicePaidOn = $invoice->getDatePaid();
        $this->view->includenextpayment = ($invoice->isIncludeNextPayment())? $this->user->lang('This charge will automatically be included in your next invoice') : '';
        $this->view->pmtSuccessfulTransactions = ($this->settings->get("Display Successful Invoice Payment Transactions") == 1)? $billingGateway->ParseInvoiceTransactions($invoice_id, 0, true) : array();

        $this->view->title = $this->user->lang("Invoice");
        if ($invoice->isDraft()) {
            $this->view->title = $this->user->lang('Draft Invoice');
        }

        if ($invoice->isPaid()) {
            $this->view->metaBoxCSS = 'ce-alert-success';
        } elseif ($invoice->isDraft()) {
            $this->view->metaBoxCSS = 'ce-alert-info';
        } else {
            $this->view->metaBoxCSS = 'ce-alert-error';
        }

        if ($this->view->invoice_sent_date == '') {
            $this->view->invoice_sent_date = $this->user->lang('Not Sent');
        }

        //let's keep tax array
        $this->view->invoice_tax = array();

        $total = $invoice->getPrice();

        //if we are viewing invoiced items show tax information
        $invoicetax=$invoice->GetTax();
        $invoicetaxname=$invoice->GetTaxName();
        if ($invoicetax > 0) {
            //only show the tax if the invoice
            //had a tax price greater than 0
            $this->view->invoice_tax['tax1'] = array("name"=>$invoicetaxname,"rate"=>$invoicetax);
        }

        $invoicetax2=$invoice->GetTax2();
        $invoicetax2name=$invoice->GetTax2Name();
        if ($invoicetax2 > 0) {
            //only show the tax if the invoice
            //had a tax price greater than 0
            $this->view->invoice_tax['tax2'] = array("name"=>$invoicetax2name,"rate"=>$invoicetax2);
        }

        //should currency when paid
        $this->view->subtotalprice = $currency->format($invoice->getCurrency(), $data['meta']['subtotalprice'], true);
        $this->view->subtotalquantityprice = $currency->format($invoice->getCurrency(), $data['meta']['subtotalquantityprice'], true);
        $this->view->totaltax = $currency->format($invoice->getCurrency(), $data['meta']['totaltax'], true);
        $this->view->totalquantitytax = $currency->format($invoice->getCurrency(), $data['meta']['totalquantitytax'], true);
        $this->view->totalprice = $currency->format($invoice->getCurrency(), ($data['meta']['totaltax'] + $data['meta']['subtotalprice']), true);
        $this->view->totalquantityprice = $currency->format($invoice->getCurrency(), ($data['meta']['totalquantitytax'] + $data['meta']['subtotalquantityprice']), true);
        $this->view->totalpaid = $currency->format($invoice->getCurrency(), ($invoice->getPrice() - $invoice->getBalanceDue()), true);
        $this->view->totalbalance = $currency->format($invoice->getCurrency(), $invoice->getBalanceDue(), true);

        $this->view->company_address = $this->settings->get("Company Address");
        $this->view->company_email = $this->settings->get("Billing E-mail");
        $this->view->company_name = $this->settings->get("Company Name");
        $this->view->company_url = $this->settings->get("Company URL");

        $AdditionalNotesForInvoicesSettingValue = $this->settings->get('Additional Notes For Invoices');
        $AdditionalNotesForInvoicesSettingId = $this->settings->getSettingIdForName('Additional Notes For Invoices');
        if ($AdditionalNotesForInvoicesSettingId !== false) {
            if (count($languages) > 1) {
                $AdditionalNotesForInvoicesSettingValue = $translations->getValue(SETTING_VALUE, $AdditionalNotesForInvoicesSettingId, $languageKey, $AdditionalNotesForInvoicesSettingValue);
            }
        }

        //Replace tags here
        $AdditionalNotesForInvoicesSettingValue = str_replace("[DIRECTPAYMENTLINK]", $billingGateway->createDirectPaymentLink($this->user, $invoice_id), $AdditionalNotesForInvoicesSettingValue);
        //Replace tags here

        $this->view->additionalinfo = str_replace("\n", "<br/>", $AdditionalNotesForInvoicesSettingValue);

        $this->view->payment_method = $this->user->getPaymentType();

        $this->view->show_change_of_billing_options = false;

        //show options for selecting new payment method
        include_once "modules/admin/models/PluginGateway.php";
        $plugin_gateway = new PluginGateway($this->user);
        $plugin = $plugin_gateway->getPluginByName("gateways", $this->user->getPaymentType());
        $this->view->accepts_cc = $plugin->getVariable("Accept CC Number");
        $this->view->payment_type_name = $plugin->getVariable("Signup Name");
        $this->view->pays_with_cc = $plugin->getVariable("Auto Payment");

        if ($this->view->accepts_cc) {
            if ($this->user->getCCLastFour() == "") {
                $this->view->cc_added = false;
            } else {
                $this->view->cc_added = true;
                $this->view->payment_type_name = $plugin->getVariable("Signup Name")." ".$this->user->lang("ending in")." ".$this->user->getCCLastFour();
            }
        }

        if (count($billingGateway->get_optional_processors_user_can_switch_to($this->view->payment_method)) > 0) {
            $this->view->show_change_of_billing_options = true;
        }

        /*
         *  Only show pay button under the follow circumstances:
         *  - Invoice is Not Paid
         *  - Invoice is Not Voided
         *  - Invoice is Not Pending
         *  - Invoice is Not Draft
         *  - There's no subscription id for the invoice
         *  - There's no active subscription transcations for the invoice
         *
         */
        if (!$invoice->isPaid() && !$invoice->isVoid() && !$invoice->isPending() && !$invoice->isDraft() && $invoice->getSubscriptionID() == '' && !$invoice->hasActiveSubscriptionTranscations()) {
            $this->view->showpaybutton = true;
        } else {
            $this->view->showpaybutton = false;
        }

        $this->view->assign($data);

        $this->view->paymentMethods = array();
        // only get plugin details if we can show pay button
        if ($this->view->showpaybutton === true) {
            $plugins = new NE_PluginCollection("gateways", $this->user);

            // Get a list of valid payment processors
            $pluginsArray = array();
            while ($tplugin = $plugins->getNext()) {
                $tvars = $tplugin->getVariables();
                $tvalue = $this->user->lang($tvars['Plugin Name']['value']);
                $pluginsArray[$tvalue] = $tplugin;
            }
            uksort($pluginsArray, "strnatcasecmp");

            foreach ($pluginsArray as $value => $plugin) {
                if (( $this->user->getPaymentType() == $plugin->getInternalName() || $plugin->getVariable("One-Time Payments") ) && !$plugin->getVariable('Dummy Plugin')) {
                    $paymentmethod = array();
                    $paymentmethod['paymentTypeOptionValue'] = $plugin->getInternalName();
                    $paymentmethod['paymentTypeOptionLabel'] = $plugin->getVariable("Signup Name"). '&nbsp;&nbsp;&nbsp;';
                    $paymentmethod['description'] = "";

                    // Select the plugin if it's default
                    if ($this->user->getPaymentType() == $plugin->getInternalName()) {
                        $paymentmethod['paymentTypeOptionSelected'] = true;
                        $retArray['defaultGateway'] = $plugin->getInternalName();
                        $this->view->defaultGateway = $plugin->getInternalName();
                    } else {
                        $paymentmethod['paymentTypeOptionSelected'] = false;
                    }

                    if (@$this->settings->get("plugin_".$plugin->getInternalName()."_Auto Payment") == 1) {
                        $paymentmethod['autoPayment'] = 1;
                    } else {
                        $paymentmethod['autoPayment'] = 0;
                    }

                    // Handle credit card plugins
                    if (@$this->settings->get("plugin_".$plugin->getInternalName()."_Accept CC Number") == 1) {
                        // Start the fields
                        $paymentmethod['extraFields'] = array();
                        $paymentmethod['iscreditcard'] = true;

                        // Handle the we accept list
                        $weAccept = array();
                        if (!is_null($this->settings->get("plugin_".$plugin->getInternalName()."_Visa")) && $this->settings->get("plugin_".$plugin->getInternalName()."_Visa") == 1) {
                            $weAccept[] = array('id' => 'visa_logo', 'alt' => 'Visa', 'img' => 'images/creditcards/visa.gif');
                        }
                        if (!is_null($this->settings->get("plugin_".$plugin->getInternalName()."_MasterCard")) && $this->settings->get("plugin_".$plugin->getInternalName()."_MasterCard") == 1) {
                            $weAccept[] = array('id' => 'mastercard_logo', 'alt' => 'MasterCard', 'img' => 'images/creditcards/mc.gif');
                        }
                        if (!is_null($this->settings->get("plugin_".$plugin->getInternalName()."_AmericanExpress")) && $this->settings->get("plugin_".$plugin->getInternalName()."_AmericanExpress") == 1) {
                            $weAccept[] = array('id' => 'americanexpress_logo', 'alt' => 'American Express', 'img' => 'images/creditcards/amex1.gif');
                        }
                        if (!is_null($this->settings->get("plugin_".$plugin->getInternalName()."_Discover")) && $this->settings->get("plugin_".$plugin->getInternalName()."_Discover") == 1) {
                            $weAccept[] = array('id' => 'discover_logo', 'alt' => 'Discover', 'img' => 'images/creditcards/discover.gif');
                        }
                        if (!is_null($this->settings->get("plugin_".$plugin->getInternalName()."_LaserCard")) && $this->settings->get("plugin_".$plugin->getInternalName()."_LaserCard") == 1) {
                            $weAccept[] = array('id' => 'lasercard_logo', 'alt' => 'LaserCard', 'img' => 'images/creditcards/laser.gif');
                        }
                        if (!is_null($this->settings->get("plugin_".$plugin->getInternalName()."_DinersClub")) && $this->settings->get("plugin_".$plugin->getInternalName()."_DinersClub") == 1) {
                            $weAccept[] = array('id' => 'dinersclub_logo', 'alt' => 'DinersClub', 'img' => 'images/creditcards/diners.gif');
                        }
                        if (!is_null($this->settings->get("plugin_".$plugin->getInternalName()."_Switch")) && $this->settings->get("plugin_".$plugin->getInternalName()."_Switch") == 1) {
                            $weAccept[] = array('id' => 'switch_logo', 'alt' => 'Switch', 'img' => 'images/creditcards/switch.gif');
                        }

                        $paymentmethod['weaccept'] = array(
                            'fieldType' => 'weaccept',
                            'fieldName' => 'ccWeAccept',
                            'fieldTitle'=> $this->user->lang('We Accept'),
                            'options'=> $weAccept
                        );

                        $paymentmethod['extraFields'][] = array(
                            'fieldType' => 'grouplabel',
                            'fieldName' => $this->user->lang("Card Information"),
                            'fieldTitle'=> $this->user->lang("Card Information")
                        );

                        // Card Number
                        $paymentmethod['extraFields'][] = array(
                            'fieldType' => 'text',
                            'fieldName' => $plugin->getInternalName().'_ccNumber',
                            'labelpos' => "low",
                            'fieldTitle'=> $this->user->lang('Credit Card Number'),
                            'fieldSize' => '207'
                        );

                        // Expiration month
                        $paymentmethod['extraFields'][] = array(
                            'fieldType' => 'dropdown',
                            'fieldName' => $plugin->getInternalName().'_ccMonth',
                            'labelpos' => "low",
                            'fieldTitle'=> $this->user->lang('Expiration Month'),
                            'fieldValue' => array(
                                array('value' => 1, 'text' => "01 - ".$this->user->lang("January")),
                                array('value' => 2, 'text' => "02 - ".$this->user->lang("February")),
                                array('value' => 3, 'text' => "03 - ".$this->user->lang("March")),
                                array('value' => 4, 'text' => "04 - ".$this->user->lang("April")),
                                array('value' => 5, 'text' => "05 - ".$this->user->lang("May")),
                                array('value' => 6, 'text' => "06 - ".$this->user->lang("June")),
                                array('value' => 7, 'text' => "07 - ".$this->user->lang("July")),
                                array('value' => 8, 'text' => "08 - ".$this->user->lang("August")),
                                array('value' => 9, 'text' => "09 - ".$this->user->lang("September")),
                                array('value' => 10, 'text' => "10 - ".$this->user->lang("October")),
                                array('value' => 11, 'text' => "11 - ".$this->user->lang("November")),
                                array('value' => 12, 'text' => "12 - ".$this->user->lang("December")))
                        );

                        // Expiration Year
                        $yearValues = array();
                        $currentYear = date("Y");
                        for ($i = 0; $i <= 15; $i++) {
                            $yearValues[] = array('value' => $currentYear, 'text' => $currentYear);
                            $currentYear++;
                        }
                        $paymentmethod['extraFields'][] = array(
                            'fieldType' => 'dropdown',
                            'fieldName' => $plugin->getInternalName().'_ccYear',
                            'labelpos' => "low",
                            'fieldTitle'=> $this->user->lang('Expiration Year'),
                            'fieldValue' => $yearValues
                        );

                        // Handle CVV2 - Check if we're allowed to ask for it
                        if ($this->settings->get('plugin_'.$plugin->getInternalName().'_Check CVV2')) {
                            $paymentmethod['extraFields'][] = array(
                                'fieldType' => 'text',
                                'fieldName' => $plugin->getInternalName().'_ccCVV2',
                                'labelpos' => "low",
                                'fieldTitle'=> $this->user->lang('CVV2'),
                                'fieldSize' => '40',
                                'fieldDescription' => $this->user->lang("<b>Visa/MasterCard/Discover:</b><br />The CVV2 number is the 3 digit value printed on the back of the card.  It follows the credit card number in the signature area of the card.")."<br/><br/>".$this->user->lang("<b>American Express:</b><br />The CVV2 number is the 4 digit value printed on the front of the card above the credit card number.")
                             );
                        }
                    }
                    $this->view->paymentMethods[] = $paymentmethod;
                }
            }
            $this->view->Currency = $invoice->getCurrency();

            $pluginCollection = new NE_PluginCollection('gateways', $this->user);
            $pluginCollection->setTemplate($this->view);

            $params = array();
            $params['companyName'] = $this->view->company_name;
            $params['invoiceId'] = $this->view->invoice_id;
            $params['currency'] = $this->view->Currency;
            $params['invoiceBalanceDue'] = $this->view->invoiceBalanceDue;
            $params['from'] = 'invoice';
            $params['panellabel'] =  $this->user->lang("Pay");
            $params['userZipcode'] = $this->user->getZipCode();

            include_once("modules/admin/models/PluginGateway.php");
            $plugingateway = new PluginGateway($this->user);
            $GatewayWithFormPlugins = $plugingateway->getGatewayWithVariablePlugins('Form');
            $gatewayForms = array();
            foreach ($GatewayWithFormPlugins as $GatewayWithForm) {
                $gatewayForms[$GatewayWithForm] = $pluginCollection->callFunction($GatewayWithForm, 'getForm', $params);
            }
            $this->view->gatewayForms = $gatewayForms;
            $GatewayWithOpenHandlerPlugins = $plugingateway->getGatewayWithVariablePlugins('openHandler');
            $GatewayWithIframeConfigurationPlugins = $plugingateway->getGatewayWithVariablePlugins('Iframe Configuration');
            $this->view->gatewayUpdate = array_merge($GatewayWithOpenHandlerPlugins, $GatewayWithIframeConfigurationPlugins);
        }
    }

    /**
     * viewing an invoice direct link form
     */
    public function invoicedirectlinkformAction()
    {
        $this->title = $this->user->lang('Invoice');
        $invoice_id = $this->getParam('id', FILTER_SANITIZE_NUMBER_INT);
        $passedHash = $this->getParam('hash', FILTER_SANITIZE_STRING);

        $invoice = new Invoice($invoice_id);

        //Do not continue if Invoice is Paid, Voided or Draft, or if there is a subscription id or active subscription transcations for the invoice
        if ($invoice->isPaid() || $invoice->isVoid() || $invoice->isDraft() || $invoice->getSubscriptionID() != '' || $invoice->hasActiveSubscriptionTranscations()) {
            CE_Lib::redirectPage("index.php", $this->user->lang("We are sorry. You have either reached this page incorrectly or your invoice can not be paid."));
        }

        $userid = $invoice->getUserID();
        $hash = md5(SALT . $invoice_id . $userid);

        //Validate the hash passed
        if ($hash != $passedHash) {
            CE_Lib::redirectPage("index.php", $this->user->lang("We are sorry. You have either reached this page incorrectly or your invoice can not be paid."));
        }

        //create user the invoice belongs to obtain their payment plugin
        $user = new User($userid);
        $this->user = $user;

        include_once "modules/admin/models/PluginGateway.php";
        $gateway = new PluginGateway($this->user);
        $plugin = $gateway->getPluginByName("gateways", $user->getPaymentType());

        if ($plugin->getVariable("Auto Payment")) {
            CE_Lib::redirectPage("index.php", $this->user->lang("We are sorry. You have either reached this page incorrectly or your invoice can not be paid."));
        }

        $canceled = $this->getParam('cancel', FILTER_SANITIZE_NUMBER_INT, 0);
        $paid = $this->getParam('paid', FILTER_SANITIZE_NUMBER_INT, 0);

        //this is only passed when we get to this view right after a payment was made
        if ($paid) {
            CE_Lib::addSuccessMessage("<b>".$this->user->lang("Thank you very much for your payment.")."</b><br/>".$this->user->lang("Please be advised that your invoice might not reflect payment until we receive notification from gateway."));
        }
        //this is only passed when we get to this view right after a payment was canceled
        if ($canceled) {
            CE_Lib::addMessage("<b>".$this->user->lang("You have canceled payment on your invoice.")."</b><br/>".$this->user->lang("Please submit a support ticket if you need any assistance."));
        }

        $this->view->invoice_id = $invoice_id;
        $this->view->hash = $passedHash;
        include_once("modules/billing/models/BillingGateway.php");
        $billingGateway = new BillingGateway($this->user);
        $data = $billingGateway->getinvoice($invoice_id, $languageKey);

        if ($userid != $this->user->getId()) {
            CE_Lib::log(1, $this->user->getId()." is trying to view an invoice (".$invoice_id.") that doesn't belong to them");
            CE_Lib::addErrorMessage($this->user->lang('Invoice was not found'));
            CE_Lib::redirectPage("index.php?fuse=billing&controller=invoice&view=allinvoices");
        }

        $this->view->payment_method = $this->user->getPaymentType();

        //show options for selecting new payment method
        include_once "modules/admin/models/PluginGateway.php";
        $plugin_gateway = new PluginGateway($this->user);
        $plugin = $plugin_gateway->getPluginByName("gateways", $this->user->getPaymentType());

        $this->view->assign($data);

        if (( $this->user->getPaymentType() == $plugin->getInternalName() || $plugin->getVariable("One-Time Payments") ) && !$plugin->getVariable('Dummy Plugin')) {
            $paymentmethod = array();
            $paymentmethod['paymentTypeOptionValue'] = $plugin->getInternalName();
            $paymentmethod['paymentTypeOptionLabel'] = $plugin->getVariable("Signup Name"). '&nbsp;&nbsp;&nbsp;';
            $this->view->defaultGateway = $plugin->getInternalName();
            $this->view->paymentMethod = $paymentmethod;
        }

        $params = array();
        $params['companyName'] = $this->settings->get("Company Name");
        $params['invoiceId'] = $this->view->invoice_id;
        $params['currency'] = $invoice->getCurrency();
        $params['invoiceBalanceDue'] = $invoice->getBalanceDue();
        $params['from'] = 'invoice';
        $params['fromDirectLink'] = true;
        $params['panellabel'] =  $this->user->lang("Pay");

        if (isset($this->view)) {
            $plugin->setTemplate($this->view);
        }

        $this->view->gatewayFormName = $plugin->getInternalName();
        $this->view->gatewayForm = $plugin->getForm($params);
    }

    public function getinvoicesAction()
    {
        $this->checkPermissions();

        require_once 'modules/admin/models/Translations.php';
        require_once 'modules/billing/models/InvoiceEntriesGateway.php';
        $InvoiceEntriesGateway = new InvoiceEntriesGateway($this->user);

        $languages = CE_Lib::getEnabledLanguages();
        $translations = new Translations();
        $languageKey = ucfirst(strtolower($this->user->getLanguage()));

        $start = $this->getParam('start', FILTER_SANITIZE_NUMBER_INT, 0);
        $statusfilter = $this->getParam('filter', FILTER_SANITIZE_STRING, "all");
        $items = $this->getParam('limit', FILTER_SANITIZE_NUMBER_INT, 10);
        $dir = $this->getParam('dir', FILTER_SANITIZE_STRING, 'desc');
        $sort = $this->getParam('sort', FILTER_SANITIZE_STRING, 'id');

        if ($sort == 'formatedbalancedue') {
            $sort = 'balancedue';
        }

        $validSorts = array('id', 'balancedue', 'billdate', 'amount');
        if (!in_array($sort, $validSorts)) {
            $sort = 'id';
        }

        $filter = array();
        if ($statusfilter == "open") {
            $filter['b.status'] = array(INVOICE_STATUS_UNPAID,INVOICE_STATUS_PARTIALLY_PAID,INVOICE_STATUS_PENDING);
        } elseif ($statusfilter == 'draft') {
            $filter['b.status'] = array(INVOICE_STATUS_DRAFT);
        } elseif ($statusfilter != "all") {
            $filter['b.status'] = array(INVOICE_STATUS_VOID,INVOICE_STATUS_PAID);
        } else { // SHOW ALL
            $filter['b.status'] = array(INVOICE_STATUS_UNPAID,INVOICE_STATUS_PAID,INVOICE_STATUS_VOID,INVOICE_STATUS_REFUNDED,INVOICE_STATUS_PENDING,INVOICE_STATUS_PARTIALLY_PAID,INVOICE_STATUS_CREDITED, INVOICE_STATUS_DRAFT);
        }

        $userid = $this->user->getId();

        $currency = new Currency($this->user);
        $billingGateway = new BillingGateway($this->user);
        $invoiceGateway = new InvoiceListGateway($this->user);
        $invoices_iterator = $invoiceGateway->get_invoices_by_user($userid, $sort . ' ' . $dir, $filter, 0, 0);
        $invoices = array();
        while ($invoice = $invoices_iterator->fetch()) {
            $a_invoice = $invoice->toArray();
            $tinvoice = new Invoice($a_invoice['id']);
            $a_invoice['status_name'] = $this->user->lang($tinvoice->getStatusName());
            $a_invoice['status_class'] = $tinvoice->getStatusClass();
            $a_invoice['subscription_id'] = $tinvoice->getSubscriptionID();

            //default description
            $strDescription = "Invoice #".$a_invoice['id'];
            $invoiceEntries = $tinvoice->getInvoiceEntries('id', 'ASC');

            //let's build a better description
            foreach ($invoiceEntries as $entry) {
                //I really only want the main entry not the coupon so let's filter on type
                if ((count($invoiceEntries) == 2) && ($entry->getBillingTypeID() == BILLINGTYPE_COUPON_DISCOUNT)) {
                    continue;
                } elseif ((count($invoiceEntries) > 2) && in_array($entry->getBillingTypeID(), array(BILLINGTYPE_COUPON_DISCOUNT,BILLINGTYPE_PACKAGE_ADDON))) {
                    continue;
                }

                if ($strDescription == "Invoice #".$a_invoice['id'] || $entry->getBillingTypeID() == BILLINGTYPE_PACKAGE) {
                    $strDescription = $InvoiceEntriesGateway->getFullEntryDescription($entry->getId(), $languageKey);
                }

                if ($entry->getBillingTypeID() == BILLINGTYPE_PACKAGE && !$entry->IsSetup()) {
                    break 1;
                }
            }

            if ($strDescription == "") {
                $a_invoice['detailed_description'] = $a_invoice['description'];
            } else {
                $a_invoice['detailed_description'] = $strDescription;
            }
            $a_invoice['formatedbalancedue'] = $currency->format($tinvoice->getCurrency(), $a_invoice['balancedue'], true);
            $a_invoice['amount'] = $currency->format($tinvoice->getCurrency(), $a_invoice['amount'], true);
            ;
            $a_invoice['billdatesort'] = $a_invoice['billdate'];
            $a_invoice['billdate'] = $this->view->dateRenderer($a_invoice['billdate']);
            $invoices[] = $a_invoice;
        }
        $this->send(array('invoices' => $invoices, 'recordsTotal' => $invoices_iterator->getTotalNumItems(), 'recordsFiltered' => $invoices_iterator->getTotalNumItems()));
    }

    /**
     * display all invoices opened by customer
     * @return [type] [description]
     */
    protected function allinvoicesAction()
    {
        $this->checkPermissions();

        include_once 'modules/billing/models/InvoiceEntriesGateway.php';
        $this->title = $this->user->lang('My Invoices');

        $billingGateway = new BillingGateway($this->user);

        //get payment type
        $this->view->payment_method = $this->user->getPaymentType();
        //$this->view->pays_with_cc = false;
        //$this->view->show_change_of_billing_options = false;
        if (count($billingGateway->get_optional_processors_user_can_switch_to($this->view->payment_method)) > 0) {
            $this->view->show_change_of_billing_options = true;
        }

        $invoiceGateway = new InvoiceEntriesGateway($this->user);
        $this->view->hasDraftInvoices = false;
        if ($invoiceGateway->userHasDraftInvoices($this->user->getId())) {
            $this->view->hasDraftInvoices = true;
        }


        //show options for selecting new payment method
        include_once "modules/admin/models/PluginGateway.php";
        $plugin_gateway = new PluginGateway($this->user);
        $plugin = $plugin_gateway->getPluginByName("gateways", $this->view->payment_method);
        $this->view->payment_type_name = $plugin->getVariable("Signup Name");
        $this->view->pays_with_cc = $plugin->getVariable("Auto Payment");
        $this->view->accepts_cc = $plugin->getVariable("Accept CC Number");

        if ($this->view->accepts_cc) {
            if ($this->user->getCCLastFour() == "") {
                $this->view->cc_added = false;
            } else {
                $this->view->cc_added = true;
                $this->view->payment_type_name = $plugin->getVariable("Signup Name")." ".$this->user->lang("ending in")." ".$this->user->getCCLastFour();
            }
        }

        $this->view->filter = $this->getParam('filter', FILTER_SANITIZE_STRING, "all");
        $this->view->invoices = array();
    }

    protected function actoninvoiceAction()
    {

        include_once 'modules/billing/models/BillingGateway.php';

        $this->featureSet = 'billing';

        $items = $this->getParam('items', null, "");
        $itemstype = $this->getParam('itemstype', null, ""); // invoices
        $actionbutton = $this->getParam('actionbutton', null, "");

        if ($itemstype == "invoices") {
            if ($actionbutton == "inv-send-smart" && $this->user->hasPermission('billing_send_invoices')) {
                $billingGateway = new BillingGateway($this->user);
                $mailStatus = $billingGateway->sendInvoicesBySmartEmail($items);

                if (is_a($mailStatus, 'CE_Error')) {
                    $this->error = true;
                    $this->message = $this->user->lang('Error sending emails')."<br>".$mailStatus->getMessage();
                }
            }
        }

        $this->send();
    }

    protected function masspayAction()
    {
        $this->featureSet = 'billing';
        $this->checkPermissions('billing_masspay');
        $billingGateway = new BillingGateway($this->user);
        $invoices = $this->getParam('invoices', null, '');
        $mainInvoice = new Invoice($invoices[0]);
        $currency = $mainInvoice->getCurrency();

        if (count($invoices) > 1) {
            foreach ($invoices as $i) {
                $invoice = new Invoice($i);
                if ($invoice->getCustomerId() != $this->customer->getId()) {
                    exit;
                }

                if (!$invoice->isUnpaid()) {
                    $this->error = true;
                    $this->message = $this->customer->lang('Please select only unpaid invoices');
                    $this->send();
                    exit;
                }

                if ($invoice->getSubscriptionID() != '') {
                    $this->error = true;
                    $this->message = $this->customer->lang('Please select only invoices without a subscription');
                    $this->send();
                    exit;
                }

                if ($invoice->hasUpgradeDowngradeItems()) {
                    $this->error = true;
                    $this->message = $this->customer->lang('Please select only invoices without Upgrade/Downgrade charges');
                    $this->send();
                    exit;
                }

                //Avoid merging invoices if they have a different currency
                if ($invoice->getCurrency() !== $currency) {
                    $this->error = true;
                    $this->message = $this->customer->lang('Please select only invoices with the same currency');
                    $this->send();
                    exit;
                }

                // Make $mainInvoice the invoice that is due first.
                if ($invoice->getDate('timestamp') < $mainInvoice->getDate('timestamp')) {
                    $mainInvoice = $invoice;
                }
            }

            $invoiceId = $billingGateway->mergeInvoices($invoices, $mainInvoice->getId());
            $this->send(['invoiceId' => $invoiceId]);
            exit;
        } elseif (count($invoices) == 1) {
            $this->send(['invoiceId' => $invoices[0]]);
            exit;
        }

        $this->error = true;
        $this->message = $this->customer->lang('Not enough invoices');
        $this->send();
    }
}
