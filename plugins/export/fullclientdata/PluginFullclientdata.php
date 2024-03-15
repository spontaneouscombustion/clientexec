<?php
require_once 'modules/admin/models/ExportPlugin.php';
require_once 'modules/admin/models/StatusAliasGateway.php' ;
require_once 'library/encrypted/Clientexec.php';

/**
* @package Plugins
*/
class PluginFullclientdata extends ExportPlugin
{
    protected $_description = 'This export plugin exports full client data to a file.';
    protected $_title = 'Full Client Data JSON';

    function getForm()
    {
        if (isset($_REQUEST['exportdata']) && $_REQUEST['exportdata'] > 0) {
            $this->view->userid = $_REQUEST['exportdata'];
        } else {
            $this->view->userid = '';
        }

        return $this->view->render('PluginFullclientdata.phtml');
    }

    function process($post)
    {
        $userid = $post['userid'];
        $fileName = 'export_data_client_'.$post['userid'].'.json';
        $json = $this->_getClientJSON($userid);
        CE_Lib::download($json, $fileName, false);
    }

    function _getClientJSON($userid)
    {
        $data = array();

        $tempUser = new user($userid);
        $languageKey = ucfirst(strtolower($tempUser->getLanguage()));
        $countries = new Countries($this->user);
        $invoiceGateway = new InvoiceListGateway($this->user);
        $userPackageGateway = new UserPackageGateway($this->user);
        $recurringEntryGateway = new RecurringEntryGateway($this->user);
        $billingGateway = new BillingGateway($this->user);
        $ticketGateway = new TicketGateway($this->user);
        $userGateway = new UserGateway($this->user);

        $noteGateway = new ClientNoteGateway($this->user);

        //User Data
        $data[$tempUser->lang('User')] = array();
        $data[$tempUser->lang('User')][$tempUser->lang('Id')] = $tempUser->getId();
        $data[$tempUser->lang('User')][$tempUser->lang('Status')] = $tempUser->lang(StatusAliasGateway::getInstance($this->user)->getUserStatus($tempUser->getStatus())->name);
        $data[$tempUser->lang('User')][$tempUser->lang('Date Created')] = $tempUser->getDateActivated();

            //Custom Fields
        $query = "SELECT cuf.`name`, "
            ."cuf.`type`, "
            ."cuf.`dropdownoptions`, "
            ."IFNULL(u_cuf.`value`, '') "
            ."FROM `customuserfields` cuf "
            ."LEFT JOIN `user_customuserfields` u_cuf "
            ."ON u_cuf.`customid` = cuf.`id` AND u_cuf.`userid` = ? "
            ."WHERE (cuf.`inSignup` = 1 OR cuf.`inSettings` = 1) "
            ."ORDER BY cuf.`myOrder`";
        $result = $this->db->query($query, $userid);

        while (list($fieldName, $fieldType, $fieldDropdownOptions, $fieldValue) = $result->fetch()) {
            switch ($fieldType) {
                case typeLANGUAGE:
                    if ($fieldValue == '') {
                        $fieldValue = $this->settings->getLanguage();
                    }

                    $fieldValue = ucfirst($tempUser->lang(strtolower($fieldValue)));
                    break;
                case typeCOUNTRY:
                    if ($fieldValue == '') {
                        $fieldValue = $this->settings->get('Default Country');
                    }

                    $fieldValue = $countries->validCountryCode($fieldValue, false, 'name');
                    break;
                case typeDROPDOWN:
                    $script_name_array = explode(',', trim($fieldDropdownOptions));

                    foreach ($script_name_array as $option) {
                        if (preg_match('/(.*)(?<!\\\)\((.*)(?<!\\\)\)/', $option, $matches)) {
                            $value = $matches[2];
                            $label = $matches[1];
                        } else {
                            $value = $label = $option;
                        }

                        $label = str_replace(array('\\(', '\\)'), array('(', ')'), $label);

                        if ($value == $fieldValue) {
                            $fieldValue = $label;
                            break;
                        }
                    }
                    break;
                case typeYESNO:
                case TYPE_ALLOW_EMAIL:
                    if ($fieldValue == 1) {
                        $fieldValue = 'Yes';
                    } else {
                        $fieldValue = 'No';
                    }
                    break;
            }

            $data[$tempUser->lang('User')][$tempUser->lang($fieldName)] = $tempUser->lang($fieldValue);
        }

        //Billing Data
        $data[$tempUser->lang('Billing')] = array();
        $data[$tempUser->lang('Billing')][$tempUser->lang('Credit Balance')] = $tempUser->getCreditBalance();
        $data[$tempUser->lang('Billing')][$tempUser->lang('Taxable')] = ($tempUser->isTaxable())? $tempUser->lang('Yes') : $tempUser->lang('No');
        $data[$tempUser->lang('Billing')][$tempUser->lang('Currency')] = $tempUser->getCurrency();
        $data[$tempUser->lang('Billing')][$tempUser->lang('Payment Type')] = $tempUser->lang($this->settings->get('plugin_'.$tempUser->getPaymentType().'_Plugin Name'));

        if ($tempUser->getPaymentType() == 'paypal') {
            $data[$tempUser->lang('Billing')][$tempUser->lang('Use Paypal Subscription')] = ($tempUser->getUsePaypalSubscriptions())? $tempUser->lang('Yes') : $tempUser->lang('No');
        }

        if ($this->settings->get('plugin_'.$tempUser->getPaymentType().'_Accept CC Number')) {
            $data[$tempUser->lang('Billing')][$tempUser->lang('Credit Card Last 4 Digits')] = $tempUser->getCCLastFour();
            $data[$tempUser->lang('Billing')][$tempUser->lang('Credit Card Expiration Month')] = $tempUser->getCCMonth();
            $data[$tempUser->lang('Billing')][$tempUser->lang('Credit Card Expiration Year')] = $tempUser->getCCYear();
        }

        //Package Data
        $data[$tempUser->lang('Packages')] = array();
        $userPackagesDataList = $userPackageGateway->getUserPackages($tempUser->getId());

        foreach ($userPackagesDataList['results'] as $userPackagesData) {
            $userPackageData = array();
            $userPackageData[$tempUser->lang('Id')] = $userPackagesData['productid'];
            $userPackageData[$tempUser->lang('Status')] = $tempUser->lang($userPackagesData['status']);
            $userPackageData[$tempUser->lang('Product Type')] = $tempUser->lang($userPackagesData['producttypename']);
            $userPackageData[$tempUser->lang('Product Group')] = $tempUser->lang($userPackagesData['productgroupname']);
            $userPackageData[$tempUser->lang('Product')] = $tempUser->lang($userPackagesData['productname']);
            $userPackageData[$tempUser->lang('Date Activated')] = $userPackagesData['dateactivated'];
            $userPackageData = array_merge($userPackageData, $userPackagesData['customfields']);

            //Addon Data
            $userPackage = new UserPackage($userPackagesData['productid']);
            $userPackageAddons = $userPackageGateway->getUserPackageAddonFields($userPackage, $languageKey);
            $userPackageData[$tempUser->lang('Addons')] = array();

            foreach ($userPackageAddons as $userPackageAddon) {
                $userPackageAddonData = array();
                $userPackageAddonData[$tempUser->lang('Id')] = $userPackageAddon['id'];
                $userPackageAddonData[$tempUser->lang('Name')] = $userPackageAddon['namelanguage'];
                $userPackageAddonData[$tempUser->lang('Description')] = $userPackageAddon['descriptionlanguage'];
                $userPackageAddonData[$tempUser->lang('Option Name')] = $userPackageAddon['optionnamelanguage'];
                $userPackageAddonData[$tempUser->lang('Billing Cycle')] = $tempUser->lang($userPackageAddon['optioncycle']);
                $userPackageAddonData[$tempUser->lang('Quantity')] = $userPackageAddon['optioncustomerquantity'];
                $userPackageAddonData[$tempUser->lang('Setup Price Per Unit')] = $userPackageAddon['optionsetupprice'];
                $userPackageAddonData[$tempUser->lang('Price Per Unit')] = $userPackageAddon['optioncustomerprice'];
                $userPackageData[$tempUser->lang('Addons')][] = $userPackageAddonData;
            }

            //Package Detail Data
            switch ($userPackagesData['producttype']) {
                case PACKAGE_TYPE_HOSTING:
                    //Hosting Data
                    $userPackageDetail = $userPackageGateway->getUserPackageHostingFields($userPackage);
                    $userPackageData[$tempUser->lang('Hosting Account')] = $this->parseUserPackageDetails($userPackageDetail, $tempUser);
                    break;
                case PACKAGE_TYPE_DOMAIN:
                    break;
                case PACKAGE_TYPE_SSL:
                    //SSL Data
                    $userPackageDetail = $userPackageGateway->getUserPackageCertificateInfoFields($userPackage);
                    $userPackageData[$tempUser->lang('Certificate Information')] = $this->parseUserPackageDetails($userPackageDetail, $tempUser);
                    break;
            }
            $data[$tempUser->lang('Packages')][] = $userPackageData;
        }

        //Invoice Data
        $data[$tempUser->lang('Invoices')] = array();
        $invoiceList = $invoiceGateway->getInvoices(false, 0, 'id', 'asc', 'billing invoice list', 2, $tempUser->getId());
        $invoicesData = $invoiceList['invoicelistiterator'];

        while ($invoice = $invoicesData->fetch()) {
            $tinvoice = new Invoice($invoice->getId());

            //If the invoice can be edited show the current gateway used by the customer, if can't be edited show the plugin used on the invoice
            $paymentType = $this->settings->get('plugin_'.(($tinvoice->canInvoiceBeChanged())? $invoice->getPaymentType() : $invoice->getPluginUsed()).'_Plugin Name');

            //If the invoice is in a subscription then can't be edited, so will show the plugin used on the invoice, but it is 'none'.
            //If instead tries to show the current gateway used by the customer, it can also be a different plugin that the one with the subscription.
            //So, as there is no way to get the real plugin here, and as we currently only support subscriptions on paypal plugin, then we will display paypal as payment type.
            if ($invoice->getSubscriptionID() != '') {
                $paymentType = $this->settings->get('plugin_paypal_Plugin Name');
            }

            if ($paymentType == null) {
                $paymentType = 'None';
            }

            $invoiceData = array();
            $invoiceData[$tempUser->lang('Id')] = $invoice->getId();
            $invoiceData[$tempUser->lang('Status')] = $tempUser->lang($tinvoice->getStatusName($tinvoice->m_Status));
            $invoiceData[$tempUser->lang('Date Created')] = date('Y-m-d', $tinvoice->getDateCreated('timestamp'));
            $invoiceData[$tempUser->lang('Due Date')] = $invoice->getBillDate();
            $invoiceData[$tempUser->lang('Sent Date')] = ($tinvoice->getSentDate('timestamp') != '')? date('Y-m-d', $tinvoice->getSentDate('timestamp')) : '';
            $invoiceData[$tempUser->lang('Date Paid')] = ($tinvoice->getDatePaid('timestamp') != '')? date('Y-m-d', $tinvoice->getDatePaid('timestamp')) : '';
            $invoiceData[$tempUser->lang('Payment Type')] = $tempUser->lang($paymentType);
            $invoiceData[$tempUser->lang('Payment Reference')] = ($invoice->getCheckNum() != '')? $invoice->getCheckNum() : $tempUser->lang('None');
            $invoiceData[$tempUser->lang('Subscription Id')] = $invoice->getSubscriptionID();
            $invoiceData[$tempUser->lang('Failed Reason')] = $tempUser->lang($tinvoice->failedReason());
            $invoiceData[$tempUser->lang('Balance Due')] = $invoice->getBalanceDue();
            $invoiceData[$tempUser->lang('Total')] = $invoice->getPrice();
            $invoiceData[$tempUser->lang('Currency')] = $invoice->getCurrency();

            //Invoice Entry Data
            $invoiceData[$tempUser->lang('Entries')] = array();

            foreach ($tinvoice->getInvoiceEntries() as $tInvoiceEntry) {
                $billingTypeName = 'Product';
                $appliesToPackage = 'None';

                if ($tInvoiceEntry->getBillingTypeID() != -1) {
                    $billingType = new BillingType($tInvoiceEntry->getBillingTypeID());
                    $billingTypeName = $billingType->getName();
                }

                if ($tInvoiceEntry->AppliesTo() != 0) {
                    $userPackage = new UserPackage($tInvoiceEntry->AppliesTo());
                    if ($userPackage->existsInDB()) {
                        $entryrow = $recurringEntryGateway->getRecurringEntryLongPackageName($tInvoiceEntry->AppliesTo());

                        $reference = '';
                        $packagename = '#'.$tInvoiceEntry->AppliesTo();

                        if ($userPackage->getCustomField('Domain Name') != null) {
                            $reference = ' - '.$userPackage->getCustomField('Domain Name');
                        }

                        if ($entryrow['domainname'] != '') {
                            $packagename .= ' '.$entryrow['domainname'];
                        }

                        if ($userPackage->getProductType() == PACKAGE_TYPE_DOMAIN) {
                            $appliesToPackage = $packagename.' ('.$entryrow['name'].')'.$reference;
                        } else {
                            $appliesToPackage = $packagename.' ('.$entryrow['name'].' - '.$entryrow['planname'].')'.$reference;
                        }
                    }
                }

                $invoiceEntryData = array();
                $invoiceEntryData[$tempUser->lang('Id')] = $tInvoiceEntry->getId();
                $invoiceEntryData[$tempUser->lang('Billing Type')] = $tempUser->lang($billingTypeName);
                $invoiceEntryData[$tempUser->lang('Period Start Date')] = ($tInvoiceEntry->getPeriodStart() != null)? $tInvoiceEntry->getPeriodStart() : '';
                $invoiceEntryData[$tempUser->lang('Period End Date')] = ($tInvoiceEntry->getPeriodEnd() != null)? $tInvoiceEntry->getPeriodEnd() : '';
                $invoiceEntryData[$tempUser->lang('Charge Name')] = $tInvoiceEntry->getDescription();
                $invoiceEntryData[$tempUser->lang('Charge Description')] = $tInvoiceEntry->getDetail();
                $invoiceEntryData[$tempUser->lang('Applies To Package')] = $tempUser->lang($appliesToPackage);
                $invoiceEntryData[$tempUser->lang('Price Per Unit')] = $tInvoiceEntry->getPrice();
                $invoiceEntryData[$tempUser->lang('Price Percent')] = ($tInvoiceEntry->getPricePercent() * 100).' %';
                $invoiceEntryData[$tempUser->lang('Quantity')] = $tInvoiceEntry->getQuantity();
                $invoiceEntryData[$tempUser->lang('Taxable')] = ($tInvoiceEntry->getTaxable())? $tempUser->lang('Yes') : $tempUser->lang('No');
                $invoiceEntryData[$tempUser->lang('Tax Amount')] = $tInvoiceEntry->getTaxAmount();
                $invoiceData[$tempUser->lang('Entries')][] = $invoiceEntryData;
            }
            //Invoice Transaction Data
            $invoiceData[$tempUser->lang('Transactions')] = array();
            $invoiceTransactions = $billingGateway->getInvoiceTransactions($invoice->getId());

            foreach ($invoiceTransactions as $invoiceTransaction) {
                $invoiceTransactionData = array();
                $invoiceTransactionData[$tempUser->lang('Id')] = $invoiceTransaction['transid'];
                $invoiceTransactionData[$tempUser->lang('Accepted')] = ($invoiceTransaction['accepted'])? $tempUser->lang('Yes') : $tempUser->lang('No');
                $invoiceTransactionData[$tempUser->lang('Response')] = $tempUser->lang($invoiceTransaction['response']);
                $invoiceTransactionData[$tempUser->lang('Transaction Date')] = $invoiceTransaction['transdate'];
                $invoiceTransactionData[$tempUser->lang('Transaction Id')] = $invoiceTransaction['transactionid'];
                $invoiceTransactionData[$tempUser->lang('Action')] = $tempUser->lang($invoiceTransaction['action']);
                $invoiceTransactionData[$tempUser->lang('Credit Card Last 4 Digits')] = $invoiceTransaction['last4'];
                $invoiceTransactionData[$tempUser->lang('Amount')] = $invoiceTransaction['amount'];
                $invoiceData[$tempUser->lang('Transactions')][] = $invoiceTransactionData;
            }

            $data[$tempUser->lang('Invoices')][] = $invoiceData;
        }

        //Recurring Fee Data
        $data[$tempUser->lang('Recurring Fees')] = array();
        $recurringFeeDataList = array();

        //List all recurring charges from recurringfee table
        $recurringResult = $recurringEntryGateway->getRecurringEntries($tempUser->getId());

        while ($row = $recurringResult->fetch()) {
            $recurringFeeDataList = $recurringEntryGateway->addRowToDataList($recurringFeeDataList, $row, true, $tempUser);
        }

        //list all the domains (packages)
        $packageResult = $recurringEntryGateway->getPackages($tempUser->getId());

        while (($row = $packageResult->fetch())) {
            $recurringFeeDataList = $recurringEntryGateway->addPackageToDataList($row, $recurringFeeDataList, $tempUser);
        }

        //lets sort the list
        $recurringEntryGateway->sortRecurringCharges($recurringFeeDataList, 'nextdate', 'asc');

        foreach ($recurringFeeDataList as $recurringFeeDataValues) {
            $recurringFeeData = array();
            $recurringFeeData[$tempUser->lang('Id')] = $recurringFeeDataValues['id'];
            $recurringFeeData[$tempUser->lang('Billing Type')] = $tempUser->lang($recurringFeeDataValues['billingtype']);
            $recurringFeeData[$tempUser->lang('Next Bill Date')] = date('Y-m-d', $recurringFeeDataValues['date']);
            $recurringFeeData[$tempUser->lang('Charge Name')] = $tempUser->lang($recurringFeeDataValues['name']);
            $recurringFeeData[$tempUser->lang('Charge Description')] = $tempUser->lang($recurringFeeDataValues['desc']);
            $recurringFeeData[$tempUser->lang('Applies To Package')] = $tempUser->lang($recurringFeeDataValues['package']);
            $recurringFeeData[$tempUser->lang('Price Per Unit')] = $recurringFeeDataValues['price'];
            $recurringFeeData[$tempUser->lang('Quantity')] = $recurringFeeDataValues['quantity'];
            $recurringFeeData[$tempUser->lang('Taxable')] = ($recurringFeeDataValues['taxable'] == 'on')? $tempUser->lang('Yes') : $tempUser->lang('No');
            $recurringFeeData[$tempUser->lang('Billing Cycle')] = $tempUser->lang($recurringFeeDataValues['paymentterm_word']);
            $recurringFeeData[$tempUser->lang('Duration In Months')] = $recurringFeeDataValues['paymentterm_mashup'];
            $recurringFeeData[$tempUser->lang('Subscription')] = ($recurringFeeDataValues['paymentSubscription'] && $recurringFeeDataValues['subscriptionid'] != '')? $tempUser->lang('Yes') : $tempUser->lang('No');
            $recurringFeeData[$tempUser->lang('Subscription Id')] = $recurringFeeDataValues['subscriptionid'];
            $data[$tempUser->lang('Recurring Fees')][] = $recurringFeeData;
        }

        //Support Data
        $data[$tempUser->lang('Support Tickets')] = array();

        $ticketPriority = array(
            1 => $tempUser->lang('High'),
            2 => $tempUser->lang('Medium'),
            3 => $tempUser->lang('Low')
        );

        $_REQUEST['filter'] = 'all';
        $_REQUEST['customerid'] = $tempUser->getId();
        $ticketsDataList = $ticketGateway->getTicketList();

        foreach ($ticketsDataList['items'] as $ticketDataValues) {
            $ticketMessagesData = array();
            $ticket = $ticketGateway->getTicket($ticketDataValues['id'], true);

            foreach ($ticket['comments'] as $ticketComment) {
                //If the comment is internal it must not be included
                if (!isset($ticketComment['action_performed_class']) || $ticketComment['action_performed_class'] != 'ticketaction-internal') {
                    $ticketCommentData = array();
                    $ticketCommentData[$tempUser->lang('Id')] = $ticketComment['logid'];
                    $ticketCommentData[$tempUser->lang('Submitted By')] = $ticketComment['authorName'];
                    $ticketCommentData[$tempUser->lang('Date Created')] = date('Y-m-d  H:i:s', $ticketComment['createdAt_unix']);
                    $ticketCommentData[$tempUser->lang('Message')] = $ticketComment['message'];
                    $ticketMessagesData[] = $ticketCommentData;
                }
            }

            //If there are no comments to show, the ticket is internal and must not be included
            if (count($ticketMessagesData) == 0) {
                continue;
            }

            $ticketData = array();
            $ticketData[$tempUser->lang('Id')] = $ticketDataValues['id'];
            $ticketData[$tempUser->lang('Submitted By')] = $ticketDataValues['submittedby'];
            $ticketData[$tempUser->lang('Priority')] = $ticketPriority[$ticketDataValues['priority']];
            $ticketData[$tempUser->lang('Subject')] = $ticketDataValues['subject'];
            $ticketData[$tempUser->lang('Message Type')] = $tempUser->lang($ticketDataValues['messagetypename']);
            $ticketData[$tempUser->lang('Date Submitted')] = $ticketDataValues['datesubmitted'];
            $ticketData[$tempUser->lang('Status')] = $tempUser->lang($ticketDataValues['statusname']);
            $ticketData[$tempUser->lang('Package Reference')] = $tempUser->lang($ticket['metadata']['userPackageReference']);
            $ticketData[$tempUser->lang('Comments')] = $ticketMessagesData;
            $data[$tempUser->lang('Support Tickets')][] = $ticketData;
        }

        unset($_REQUEST['filter']);
        unset($_REQUEST['customerid']);

        //Alternate Account Data
        $data[$tempUser->lang('Alternate Accounts')] = array();
        $alternateAccounts = $userGateway->getAlternateEmails($tempUser->getId());

        while ($alternateAccount = $alternateAccounts->fetch()) {
            $alternateAccountData = array();
            $alternateAccountData[$tempUser->lang('Id')] = $alternateAccount['id'];
            $alternateAccountData[$tempUser->lang('Email')] = $alternateAccount['email'];
            $alternateAccountData[$tempUser->lang('Send Notifications')] = ($alternateAccount['sendnotifications'])? $tempUser->lang('Yes') : $tempUser->lang('No');
            $alternateAccountData[$tempUser->lang('Send Invoice')] = ($alternateAccount['sendinvoice'])? $tempUser->lang('Yes') : $tempUser->lang('No');
            $alternateAccountData[$tempUser->lang('Send Support')] = ($alternateAccount['sendsupport'])? $tempUser->lang('Yes') : $tempUser->lang('No');
            $data[$tempUser->lang('Alternate Accounts')][] = $alternateAccountData;
        }

        //Notes Data
        $data[$tempUser->lang('Notes')] = array();
        $clientNotes = $noteGateway->getClientNotes($tempUser->getId());

        while ($note = $clientNotes->fetch()) {
            if ($note->isVisibleClient()) {
                if ($note->isAssignedToAllTicketTypes()) {
                    $ticketTypeText = $tempUser->lang('All Types');
                } else {
                    $ticketTypeText = '';
                    $ticketTypesIt = $note->getTicketTypesIterator();

                    while ($ticketType = $ticketTypesIt->fetch()) {
                        $ticketTypeText .= ' '.$tempUser->lang($ticketType->getName());
                    }

                    if ($ticketTypeText == '') {
                        $ticketTypeText = $tempUser->lang('None');
                    }
                }

                $noteSubject = $note->getSubject();

                if ($noteSubject == '') {
                    $noteSubject = $tempUser->lang('N/A');
                }

                $noteData = array();
                $noteData[$tempUser->lang('Id')] = $note->getId();
                $noteData[$tempUser->lang('Staff')] = $note->getAdminFullName();
                $noteData[$tempUser->lang('Ticket Type')] = $ticketTypeText;
                $noteData[$tempUser->lang('Date Submitted')] = $note->getDate();
                $noteData[$tempUser->lang('Subject')] = $noteSubject;
                $noteData[$tempUser->lang('Description')] = $note->getNote();
                $data[$tempUser->lang('Notes')][] = $noteData;
            }
        }

        $json = json_encode($data);
        return $json;
    }

    function parseUserPackageDetails($userPackageDetail, $tempUser)
    {
        $userPackageData = array();

        foreach ($userPackageDetail as $userPackageDetailField) {
            if (in_array($userPackageDetailField['id'], array('availableips', 'registartionstatus', 'purchasestatus'))) {
                continue;
            }

            $value = $userPackageDetailField['value'];

            if (isset($userPackageDetailField['dropdownoptions'])) {
                foreach ($userPackageDetailField['dropdownoptions'] as $dropdownoption) {
                    if ($dropdownoption[0] == $userPackageDetailField['value']) {
                        $value = $dropdownoption[1];
                        break;
                    }
                }
            }

            $userPackageDetailData = array();
            $userPackageDetailData[$tempUser->lang('Id')] = $userPackageDetailField['id'];
            $userPackageDetailData[$tempUser->lang('Name')] = $tempUser->lang($userPackageDetailField['name']);
            $userPackageDetailData[$tempUser->lang('Description')] = (isset($userPackageDetailField['description']))? $tempUser->lang($userPackageDetailField['description']) : '';
            $userPackageDetailData[$tempUser->lang('Value')] = $value;
            $userPackageData[] = $userPackageDetailData;
        }

        return $userPackageData;
    }
}
