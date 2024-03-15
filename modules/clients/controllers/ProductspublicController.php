<?php

require_once 'modules/clients/models/UserPackage.php';
require_once 'modules/clients/models/Client_EventLog.php';
require_once 'modules/clients/models/Package_EventLog.php';
require_once 'modules/admin/models/Package.php';
require_once 'modules/admin/models/StatusAliasGateway.php';
require_once 'modules/billing/models/BillingCycle.php';

/**
 * Packages Controller
 *
 * @category   Action
 * @package    Clients
 * @author     Alberto Vasquez <alberto@clientexec.com>
 * @license    http://www.clientexec.com  ClientExec License Agreement
 * @link       http://www.clientexec.com
 */
class Clients_ProductspublicController extends CE_Controller_Action
{
    public $moduleName = "clients";


    public function sendwelcomeemailAction()
    {
        $this->checkPermissions('clients_send_welcome_email');

        $id = $this->getParam('id');
        $userPackage = new UserPackage($id);

        if ($this->user->getId() == $userPackage->CustomerId) {
            $userPackageGateway = new UserPackageGateway($this->user);
            $userPackageGateway->sendWelcomeEmailForPackage($userPackage);

            CE_Lib::redirectPage(
                $_SERVER['HTTP_REFERER'],
                $this->user->lang('Successfully sent welcome email')
            );
        } else {
            CE_Lib::redirectPermissionDenied(
                $this->user->lang('You can not send the welcome email for this package')
            );
        }
    }

    public function generaterenewinvoiceAction()
    {
        $this->checkPermissions('billing_renew_package');

        $id = $this->getParam('id');
        $userPackage = new UserPackage($id);

        if ($userPackage->isPaid()) {
            $recurringWork = $userPackage->getRecurringFeeEntry();
            if ($recurringWork->GetRecurring()) {
                if ($this->user->getId() == $userPackage->CustomerId) {
                    $billingGateway = new BillingGateway($this->user);
                    $invoicesData = $billingGateway->generateNextInvoice($this->user->getId(), $id);

                    if ($invoicesData !== false && isset($invoicesData["invoiceIDs"]) && count($invoicesData["invoiceIDs"]) > 0) {
                        //if there are many invoices, merge them.
                        if (count($invoicesData["invoiceIDs"]) > 1) {
                            $billingGateway->mergeInvoices($invoicesData["invoiceIDs"], $invoicesData["invoiceIDs"][0]);
                        }

                        $billing_EventLog = Invoice_EventLog::newInstance(
                            false,
                            $this->customer->getId(),
                            $invoicesData["invoiceIDs"][0],
                            null,
                            $this->user->getId()
                        );
                        $billing_EventLog->setAction(INVOICE_EVENTLOG_REQUESTED_RENEW);
                        $billing_EventLog->setParams($id);
                        $billing_EventLog->save();

                        CE_Lib::redirectPage('index.php?fuse=billing&controller=invoice&view=invoice&id=' . $invoicesData["invoiceIDs"][0], $this->user->lang('Please pay this invoice'));
                        return;
                    }
                }
            }
        }
        CE_Lib::redirectPermissionDenied($this->user->lang('You can not renew this package'));
    }

    public function toogleautomaticccchargeAction()
    {
        $this->checkPermissions('billing_automatic_cc_charge');

        $product_id = $this->getParam('id', FILTER_SANITIZE_NUMBER_INT);
        $userPackage = new UserPackage($product_id);

        if ($this->user->getId() != $userPackage->getCustomerId()) {
            $this->error = true;
            $this->message = $this->user->lang('You can not modify automatic credit card charging for this package');
            $this->send();
            return;
        }

        $gateway = new UserPackageGateway($this->user);
        $gateway->toogleAutomaticCCChargeForPackage($userPackage);

        $this->message = $this->user->lang('Successfully modified automatic credit card charging');
        $this->send();
    }

    /**
     * Handles viewing the public product
     * @return [type] [description]
     */
    protected function productAction()
    {
        include_once 'modules/billing/models/Currency.php';
        include_once 'modules/admin/models/Translations.php';
        include_once 'modules/admin/models/PluginGateway.php';
        include_once 'modules/clients/models/SSLGateway.php';

        $product_id = $this->getParam('id', FILTER_SANITIZE_NUMBER_INT);

        $userPackage = new UserPackage($product_id);
        $userPackageGateway = new UserPackageGateway($this->user);
        $package = new Package($userPackage->Plan);
        $currency = new Currency($this->user);
        $translations = new Translations();

        $languages = CE_Lib::getEnabledLanguages();

        $this->view->package = [];
        $this->view->package['reference'] = '#' . $userPackage->getId();
        $this->view->package['customFields'] = [];
        $this->view->product_id = $product_id;
        $this->view->package['status'] = $userPackageGateway->getStyledStatus($userPackageGateway->getProductStatus($userPackage));


        $languageKey = ucfirst(strtolower($this->user->getLanguage()));
        if (count($languages) > 1) {
            $this->view->package['product']      = $translations->getValue(PRODUCT_NAME, $package->getId(), $languageKey, $package->planname);
            $this->view->package['productGroup'] = $translations->getValue(PRODUCT_GROUP_NAME, $package->productGroup->getId(), $languageKey, $package->productGroup->name);
        } else {
            $this->view->package['product']      = $package->planname;
            $this->view->package['productGroup'] = $package->productGroup->name;
        }

        $this->view->package['productType'] = $userPackage->getProductType();

        $recurringFee = $userPackage->getRecurringFeeEntry();
        if ($userPackage->isPaid()) {
            $this->view->package['nextBillDateText'] = $this->user->lang('Next Billing Date');
            $this->view->package['nextBillDate'] = $recurringFee->getNextBillDate();
            $this->view->package['nextBillDate'] = $this->view->dateRenderer($this->view->package['nextBillDate']);
        } else {
            $this->view->package['nextBillDateText'] = $this->user->lang('Due Date');
            $this->view->package['nextBillDate'] = $this->view->dateRenderer($userPackage->getLastInvoiceDate());
        }
        if ($this->view->package['nextBillDate'] == "") {
            $this->view->package['nextBillDate'] = $this->user->lang("Not Applicable");
        }

        $this->view->showRecurring = true;

        $billingCycle = new BillingCycle($recurringFee->getPaymentTerm());
        $this->view->package['billingCycle'] = CE_Lib::getMonthLabel(
            $recurringFee->getPaymentTerm(),
            $this->user
        );
        $this->view->showAutomaticRenewal = false;
        $this->view->automaticRenewalChecked = '';

        try {
            $this->view->package['recurringAmount'] = $currency->format(
                $this->user->getCurrency(),
                $userPackage->getPrice(false),
                true,
                'NONE',
                false
            );
        } catch (Exception $e) {
            $this->view->package['recurringAmount'] = $this->user->lang('N/A');
            $this->view->package['billingCycle'] = $this->user->lang('N/A');
        }

        if ($this->user->hasPermission('billing_automatic_cc_charge') && $this->user->isAutopayment()) {
            if ($recurringFee->GetRecurring()) {
                $this->view->showAutomaticRenewal = true;
                if ($recurringFee->GetAutoChargeCC() == true) {
                    $this->view->automaticRenewalChecked = ' checked="checked"';
                }
            }
        }

        // get any package custom fields
        $packageCustomFields = $userPackage->customFields["package"];
        while ($row = $packageCustomFields->fetch()) {
            if (!$row['isadminonly']) {
                $data = array();
                $data['name'] = $row['name'];
                $data['value'] = $row['value'];
                $data['type'] = $row['fieldtype'];
                if ($data['value'] == '') {
                    $data['value'] = $this->user->lang("Left Blank");
                }
                $data['isClientChangeable'] = $row['isClientChangeable'];
                if ($row['fieldtype'] == typeDATE) {
                    $data['value'] = ($row['value'] != '0000-00-00') ? CE_Lib::db_to_form($row['value'], $this->settings->get('Date Format'), '/') : '';
                } elseif ($row['fieldtype'] == typeDROPDOWN) {
                    $options = explode(",", trim($row['dropdownoptions']));
                    foreach ($options as $option) {
                        if (preg_match('/(.*)(?<!\\\)\((.*)(?<!\\\)\)/', $option, $matches)) {
                            if ($data['value'] == $matches[2]) {
                                $data['value'] = $matches[1];
                            }
                        }
                        $selectOptions[] = array($value,$label);
                    }
                } elseif ($row['fieldtype'] == typeCOUNTRY) {
                    $countries = new Countries($this->user);
                    $data['value'] = $countries->validCountryCode($data['value'], false, 'name');
                }
                $this->view->package['customFields'][] = $data;
            }
        }

        if ($userPackage->getProductType() == PACKAGE_TYPE_HOSTING) {
            // Server
            include_once 'modules/admin/models/ServerGateway.php';
            $serverGateway = new ServerGateway();
            $serverID = $userPackage->getCustomField('Server Id');

            $advancedSettings = @unserialize($package->advanced);
            if (@$advancedSettings['hostingcustomfields'] == 0) {
                $serverName = $serverGateway->getServerNameById($serverID);
                $this->view->package['serverName'] = ($serverName == "") ? $this->user->lang("None selected") : $serverName;

                $isShared = $userPackage->getCustomField("Shared");
                if ($isShared === null) {
                    $isShared = true;
                }
                $server = $serverGateway->getServer($serverID);
                $sharedip = $server['sharedip'];

                if ($serverID != 0 && $isShared) {
                    $ipaddress = $sharedip;
                } else {
                    $ipaddress = $userPackage->getCustomField("IP Address");
                }

                $this->view->package['serverIp'] = ($ipaddress == "") ? $this->user->lang("Not defined") : $ipaddress;
                $this->view->package['username'] = $userPackage->getCustomField('User Name');

                $activeStatuses = StatusAliasGateway::getInstance($this->user)->getPackageStatusIdsFor(array(PACKAGE_STATUS_ACTIVE));
                if ($this->user->hasPermission('clients_change_package_password') && in_array($userPackage->status, $activeStatuses)) {
                    $this->view->package['password'] = '<a href="#" id="passwordChange">' . $this->user->lang('Click To Change') . '</a>';
                }

                $this->view->package['domain'] = $userPackage->getCustomField('Domain Name');

                $this->view->package['nameservers']  = [];
                foreach ($userPackage->getNameServers() as $nameServer) {
                    $data = [];
                    $data['name'] = $this->user->lang('Name Server');
                    $data['value'] = $nameServer;
                    $this->view->package['nameservers'][] = $data;
                }
            }

            $this->view->pluginOutput = '';
            if ($userPackageGateway->hasPlugin($userPackage, $pluginName)) {
                $pluginGateway = new PluginGateway($this->user);
                $plugin = $pluginGateway->getPluginByUserPackage($userPackage, $pluginName);
                if ($plugin->supports('publicView')) {
                    $this->view->pluginOutput = $plugin->show_publicviews($userPackage, $this);
                }
            }
        } elseif ($userPackage->getProductType() == PACKAGE_TYPE_DOMAIN) {
            $product_id = $this->getParam('id', FILTER_SANITIZE_NUMBER_INT);
            $userPackage = new UserPackage($product_id);

            $this->view->package['domain'] = $userPackage->getCustomField('Domain Name');

            $domainNameGateway = new DomainNameGateway($this->user);
            $isRegistered = $domainNameGateway->isDomainRegistered($userPackage);

            $transferId = $userPackage->getCustomField("Transfer Status");
            if ($transferId == "") {
                $transferId = $this->user->lang("Unknown");
            }

            if ($userPackage->getCustomField("Registration Option") == 1) {
                $this->view->package['isTransfer'] = true;
                $this->view->package['eppCode'] = '';
                $this->view->package['transferStatus'] = '';
                $this->view->package['transferId'] = '';
            } else {
                $this->view->package['isTransfer'] == false;
            }

            $this->view->package['registrar'] = '';
            $this->view->package['expirationDate'] = '';
            $this->view->package['registrarLock'] = '';

            // non-completed transfer
            if ($userPackage->getCustomField("Registration Option") == 1 && $userPackage->getCustomField('Transfer Status') != 'Completed') {
                $this->view->package['transferId'] = $transferId;
                try {
                    $transferStatus = $domainNameGateway->getTransferStatus($userPackage);
                } catch (MethodNotImplemented $e) {
                    $transferStatus = '';
                } catch (Exception $e) {
                    $transferStatus = $e->getMessage();
                }
                $this->view->package['transferStatus'] = $transferStatus;
            }

            // Only show expiration, registration and purchase status if it's a normal reg, or a completed transfer
            if (($userPackage->getCustomField("Registration Option") != 1) || ($userPackage->getCustomField("Registration Option") == 1 && $userPackage->getCustomField('Transfer Status') == 'Completed')) {
                try {
                    $generalInfo = $domainNameGateway->getGeneralInfoViaPlugin($userPackage);
                } catch (Exception $e) {
                }
                $this->view->package['expirationDate'] = $this->view->dateRenderer($generalInfo['expires']);
                $this->view->package['canToggleRegLock'] = $this->user->hasPermission('domains_lock');
                try {
                    $this->view->package['registrarLock'] = $domainNameGateway->getRegistrarLockViaPlugin($userPackage);

                    $this->view->package['regLockValue'] = '';
                    // we need to check the value through the language parser as the function returns the value like that.
                    if ($this->view->package['registrarLock'] == $this->user->lang('Enabled')) {
                        $this->view->package['regLockValue'] = ' checked="checked"';
                    }
                } catch (Exception $e) {
                }
            }

            if ($this->settings->get('Hide Registrar Information') != 1 || $this->user->isAdmin()) {
                $this->view->package['registrar'] = $userPackage->getCustomField("Registrar");
            }

            // Do not show certain information for no registrar, as we can not get certain information.
            $this->view->cWhois = false;
            if ($userPackage->getCustomField("Registrar") == '') {
                $this->view->cWhois = true;
            }

            $period = $domainNameGateway->getPeriod($userPackage);
            $billingCycle = new BillingCycle($period);
            $this->view->registrationLength = $billingCycle->name;
            $this->view->package['eppCode'] = '';
            if ($this->user->hasPermission('domains_transfer_key')) {
                $this->view->package['eppCode'] = $domainNameGateway->getEPPCodeViaPlugin($userPackage);
            }
        } elseif ($userPackage->getProductType() == PACKAGE_TYPE_SSL) {
            $this->title = $this->user->lang('SSL Certificate Information');
            $pluginGateway = new PluginGateway($this->user);
            $sslGateway = new SSLGateway();
            $this->view->cert = [];

            // use this to determine if we can change CSR and admin e-mail
            $this->view->cert['isChangeable'] = !$sslGateway->isCertificateIssued($userPackage);
            $certRegistrar = $userPackage->getCustomField("Registrar");

            $sslPlugin = $pluginGateway->getPluginByUserPackage($userPackage, $certRegistrar);
            if ($this->settings->get('Hide Registrar Information') != 1 || $this->user->isAdmin()) {
                $this->view->cert['registrar'] = ($certRegistrar == "" || $certRegistrar == "0") ? "None" : strtolower($certRegistrar);
            }

            $status = $userPackage->getCustomField('Certificate Status');
            $certid = $userPackage->getCustomField("Certificate Id");

            if ($status != SSL_CERT_ISSUED_STATUS) {
                if ($certid != '') {
                    // Need to catch any Exceptions from here so we still show the Certificate Information
                    try {
                        $status = $sslGateway->callPlugin($userPackage, 'doGetCertStatus');
                    } catch (Exception $e) {
                        $status =  $this->user->lang("Unknown");
                    }
                } else {
                    $status = $this->user->lang('Unknown');
                }
            }
            $this->view->cert['status'] = $status;

            if ($certid == '') {
                $certid = $this->user->lang("Unknown");
            }
            $this->view->cert['id'] = $certid;

            $domain = $userPackage->getCustomField("Certificate Domain");
            $csr = $userPackage->getCustomField("Certificate CSR");
            if ($domain == '') {
                if ($csr != '' && $certid != $this->user->lang("Unknown")) {
                    $gateway = new SSLGateway();

                    // Need to catch any Exceptions from here so we still show the Certificate Information
                    try {
                        $parsedCSR = $gateway->callPlugin($userPackage, 'doParseCSR');
                        $domain = $parsedCSR['domain'];
                        $userPackage->setCustomField("Certificate Domain", $domain);
                    } catch (Exception $e) {
                        $domain =  $this->user->lang("Unknown");
                    }
                } else {
                    $domain = $this->user->lang('Unknown');
                }
            }

            $this->view->cert['csr'] = '';
            $this->view->cert['adminEmail'] = '';
            if (is_object($sslPlugin) && $sslPlugin->usingInviteURL == false) {
                $this->view->cert['csr'] = $csr;
                $this->view->cert['adminEmail'] = $userPackage->getCustomField("Certificate Admin Email");
            }
            $this->view->cert['domain'] = $domain;

            $this->view->cert['expirationDate'] = $userPackage->getCustomField("Certificate Expiration Date");
            if ($this->view->cert['expirationDate'] == "") {
                $this->view->cert['expirationDate'] = $this->user->lang('Unknown');
            }
            $this->view->cert['cert'] = $userPackage->getCustomField('SSL Certificate');
        }

        $addons = $userPackageGateway->getUserPackageAddonFields($userPackage, $languageKey);
        $this->view->package['addons'] = array_filter($addons, function ($addon) {
            if ($addon['id'] == 'updateviaplugin') {
                return false;
            }
            return true;
        });
    }

    protected function savedomainhostrecordsAction()
    {
        $this->checkPermissions('domains_updatedns');

        $productId = $this->getParam('id', FILTER_SANITIZE_NUMBER_INT);
        $userPackage = new UserPackage($productId);

        if ($this->user->getId() != $userPackage->getCustomerId()) {
            throw new CE_Exception($this->user->lang('You can not update this domain'));
        }

        $zoneArray = array();
        foreach (array_keys($_REQUEST) as $key) {
            if ($key === "hostname_blankrecord") {
                continue;
            }

            if ("hostname_" === substr($key, 0, 9)) {
                $hostnameidpair = explode("_", $key);
                if ($hostnameidpair[2] == 'blankrecord') {
                    continue;
                }

                $record = array(
                    'id'       => filter_var($hostnameidpair[2], FILTER_SANITIZE_STRING),
                    'hostname' => filter_var($_REQUEST['hostname_CT_' . $hostnameidpair[2]], FILTER_SANITIZE_STRING),
                    'address'  => filter_var($_REQUEST['hostaddress_CT_' . $hostnameidpair[2]], FILTER_SANITIZE_STRING),
                    'type'     => filter_var($_REQUEST['hosttype_CT_' . $hostnameidpair[2]], FILTER_SANITIZE_STRING)
                );

                // if the field exists, it means it's a current dns entry
                if (isset($_REQUEST[$hostnameidpair[2]])) {
                    $record['new'] = false;
                } else {
                    // this is a new dns entry
                    $record['new'] = true;
                }

                //let's validate
                if ((trim($record['hostname']) == "") || (trim($record['address']) == "")) {
                    throw new CE_Exception("Hostname nor Address can be left blank");
                }

                $zoneArray[] = $record;
            }
        }

        $gateway = new DomainNameGateway($this->user);
        $gateway->callPlugin($userPackage, 'setDNS', array('records' => $zoneArray));

        $this->message = $this->user->lang('Successfully updated host records');
        $this->send();
    }

    protected function savedomainnameserversAction()
    {
        $this->checkPermissions('domains_editns');

        $productId = $this->getParam('id', FILTER_SANITIZE_NUMBER_INT);
        $userPackage = new UserPackage($productId);

        if ($this->user->getId() != $userPackage->getCustomerId()) {
            throw new CE_Exception($this->user->lang('You can not update this domain'));
        }

        $useDefaults = $this->getParam('ns_usedefaults', FILTER_SANITIZE_NUMBER_INT, 0);
        $useDefaults = ($useDefaults == 1) ? true : false;

        $ns = [];
        foreach ($_REQUEST['nameservers'] as $key => $nameserver) {
            if ($nameserver != '') {
                $ns[$key + 1] = filter_var($nameserver, FILTER_SANITIZE_STRING);
            }
        }

        $gateway = new DomainNameGateway($this->user);
        $gateway->callPlugin($userPackage, 'setNameServers', array('ns' => $ns, 'default' => $useDefaults));

        $this->message = $this->user->lang('Successfully updated name servers');
        $this->send();
    }

    protected function productdomainnameserversAction()
    {
        $this->title = $this->user->lang('Domain Name Servers');
        $this->checkPermissions('domains_nameservers');

        $product_id = $this->getParam('id', FILTER_SANITIZE_NUMBER_INT);
        $userPackage = new UserPackage($product_id);

        $this->view->domainNotActive = false;
        $this->view->domainNotRegistered = false;
        $this->view->packageId = $product_id;
        $this->view->domain = $userPackage->getCustomField('Domain Name');

        $domainNameGateway = new DomainNameGateway($this->user);
        $isRegistered = $domainNameGateway->isDomainRegistered($userPackage);

        $statusPending = StatusAliasGateway::getInstance($this->user)->getPackageStatusIdsFor(PACKAGE_STATUS_PENDING);
        if (!$isRegistered) {
            $this->view->domainNotRegistered = true;
        } elseif ($isRegistered && !in_array($userPackage->status, $statusPending)) {
            include_once 'modules/clients/models/ObjectCustomFields.php';

            $datalist = array();

            // $data = array();
            // $data["id"]           = 'blankrecord';
            // $data["name"]         = "";
            // $data["ischangeable"] = true;
            // $data["isrequired"]   = false;
            // $data["ishidden"]     = true;
            // $data["fieldtype"]    = (string)typeNAMESERVER;
            // $data["value"]        = "";
            // ObjectCustomFields::parseFieldToArray($data, $this->user, $this->settings);
            // $datalist[] = $data;

            $gateway = new DomainNameGateway($this->user);
            $NSArray = $gateway->getNameServersViaPlugin($userPackage);

            //hide the nameservers if uses default
            $hidefields = (isset($NSArray['usesDefault']) && $NSArray['usesDefault'] == 1) ? true : false;

            // if ($NSArray['hasDefault'] == true) {
            //     $data = array();
            //     $data["id"]           = "ns_usedefaults";
            //     $data["name"]         = $this->user->lang("Use Defaults");
            //     $data["isrequired"]   = true;
            //     $data["ischangeable"] = $this->user->hasPermission('domains_editns');
            //     ;
            //     $data["fieldtype"]    = (string)typeYESNO;
            //     $data["value"]        = @$NSArray['usesDefault'];
            //     $data["listener"]     = array("onselect"=>"nameservers_ChangeUseDefaults");
            //     ObjectCustomFields::parseFieldToArray($data, $this->user, $this->settings);
            //     $datalist[] = $data;
            // }

            $cNameServer = 0;
            if (isset($NSArray['nameservers']) && is_array($NSArray['nameservers'])) {
                foreach ($NSArray['nameservers'] as $nameserver) {
                    $cNameServer++;
                    $data = array();
                    $data["id"]           = "ns_" . $cNameServer;
                    $data["name"]         = $this->user->lang("Name Server") . " " . $cNameServer;
                    $data["isrequired"]   = false;
                    $data["ischangeable"] =  $this->user->hasPermission('domains_editns');
                    ;
                    $data["ishidden"]     = $hidefields;
                    $data["fieldtype"]    = (string)typeNAMESERVER;
                    $data["value"]        = $nameserver;
                    ObjectCustomFields::parseFieldToArray($data, $this->user, $this->settings);
                    $datalist[] = $data;
                }
            }

            // $data = array();
            // $data["id"]        = "addnameserver";
            // $data["ishidden"]  = $hidefields;
            // $data["fieldtype"] = (string)TYPE_UI_BUTTON;
            // $data["value"]     = $this->user->lang("Add Name Server");
            // $data["listener"]  = array("onclick"=>"nameservers_addnameserver");
            // ObjectCustomFields::parseFieldToArray($data, $this->user, $this->settings);
            // $datalist[] = $data;

            $this->view->nameservers = $datalist;
        } else {
            $this->view->domainNotActive = true;
        }
    }

    protected function productdomainhostsAction()
    {
        $this->checkPermissions('domains_dnssettings');

        $this->title = $this->user->lang('Domain Host Records');
        $product_id = $this->getParam('id', FILTER_SANITIZE_NUMBER_INT);
        $userPackage = new UserPackage($product_id);

        $this->view->domainNotActive = false;
        $this->view->domainNotRegistered = false;
        $this->view->hostRecordsNotSupported = false;
        $this->view->packageId = $product_id;
        $this->view->domain = $userPackage->getCustomField('Domain Name');

        $this->view->hostTypes = [
            'A',
            'AAAA',
            'MX',
            'MXE',
            'CNAME',
            'URL',
            'FRAME',
            'TXT'
        ];

        $domainNameGateway = new DomainNameGateway($this->user);
        $isRegistered = $domainNameGateway->isDomainRegistered($userPackage);

        $statusPending = StatusAliasGateway::getInstance($this->user)->getPackageStatusIdsFor(PACKAGE_STATUS_PENDING);
        if (!$isRegistered) {
            $this->view->domainNotRegistered = true;
        } elseif ($isRegistered && !in_array($userPackage->status, $statusPending)) {
            $this->view->hostRecords = array();

            include_once 'modules/clients/models/ObjectCustomFields.php';
            $dnsArray = array();
            $dnsArray['records'] = array();
            $dnsArray['default'] = 0;

            try {
                $dnsArray = $domainNameGateway->getDNSViaPlugin($userPackage);
            } catch (Exception $e) {
                $this->view->hostRecordsNotSupported = true;
            }

            if (is_array($dnsArray['types']) && count($dnsArray['types']) > 0) {
                $this->view->hostTypes = $dnsArray['types'];
            }


            foreach ($dnsArray['records'] as $DNS) {
                $data = [
                    'id' => $DNS['id'],
                    'hostname' => $DNS['hostname'],
                    'type' => $DNS['type'],
                    'address' => $DNS['address']
                ];
                $datalist[] = $data;
            }
            $this->view->hostRecords = $datalist;
        } else {
            $this->view->domainNotActive = true;
        }
    }

    protected function updateregistrarlockAction()
    {
        $this->checkPermissions('domains_lock');

        $product_id = $this->getParam('id', FILTER_SANITIZE_NUMBER_INT);
        $userPackage = new UserPackage($product_id);

        if ($this->user->getId() != $userPackage->getCustomerId()) {
            CE_Lib::redirectPage("index.php?fuse=clients&controller=products&view=products");
        }

        $value = $this->getParam('value', FILTER_SANITIZE_NUMBER_INT);
        $gateway = new UserPackageGateway($this->user);
        $this->message = $gateway->callPluginAction($userPackage, 'SetRegistrarLock', $value);
        $this->send();
    }

    protected function savedomaincontactinfoAction()
    {
        $this->checkPermissions('domains_updatecontactinfo');

        $productId = $this->getParam('id', FILTER_SANITIZE_NUMBER_INT);
        $userPackage = new UserPackage($productId);

        if ($this->user->getId() != $userPackage->getCustomerId()) {
            CE_Lib::redirectPage("index.php?fuse=clients&controller=products&view=products");
        }

        $contactArray = array();
        foreach ($_REQUEST as $key => $value) {
            if ("Registrant_" === substr($key, 0, 11)) {
                $contactArray[$key] = $value;
            }
        }

        $contactArray['type'] = 'Registrant';
        $dom = DomainNameGateway::splitDomain($userPackage->getCustomField("Domain Name"));
        $contactArray['tld'] = $dom[1];
        $contactArray['sld'] = $dom[0];

        // Make the call
        $gateway = new DomainNameGateway($this->user);
        $gateway->callPlugin($userPackage, 'setContactInformation', $contactArray);

        CE_Lib::redirectPage(
            'index.php?fuse=clients&controller=products&view=productdomaincontactinfo&id=' .  $productId,
            $this->user->lang('Successfully saved contact information')
        );
    }

    protected function productdomaincontactinfoAction()
    {
        $this->checkPermissions('domains_viewcontactinfo');
        $this->title = $this->user->lang('Domain Contact Information');
        $product_id = $this->getParam('id', FILTER_SANITIZE_NUMBER_INT);
        $userPackage = new UserPackage($product_id);

        $this->view->domainNotActive = false;
        $this->view->domainNotRegistered = false;
        $this->view->contactInformation = [];
        $this->view->packageId = $product_id;
        $this->view->domain = $userPackage->getCustomField('Domain Name');

        $domainNameGateway = new DomainNameGateway($this->user);
        $isRegistered = $domainNameGateway->isDomainRegistered($userPackage);

        $statusPending = StatusAliasGateway::getInstance($this->user)->getPackageStatusIdsFor(PACKAGE_STATUS_PENDING);
        if (!$isRegistered) {
            $this->view->domainNotRegistered = true;
        } elseif ($isRegistered && !in_array($userPackage->status, $statusPending)) {
            include_once 'modules/clients/models/ObjectCustomFields.php';
            $contactInfoArray = $domainNameGateway->getContactInfoViaPlugin($userPackage);

            foreach ($contactInfoArray as $contactInfo) {
                $data = array();
                $data["id"]           = $contactInfo['field'];
                $data["name"]         = $contactInfo['name'];
                $data["isrequired"]   = false;
                $data["ischangeable"] = "1";
                $data["ishidden"]     = false;
                $data["fieldtype"]    = (string)typeTEXTFIELD;
                $data["value"]        = $contactInfo['value'];
                ObjectCustomFields::parseFieldToArray($data, $this->user, $this->settings);
                $this->view->contactInformation[] = $data;
            }
        } else {
            $this->view->domainNotActive = true;
        }
    }

    public function openpackagedirectlinkAction()
    {
        $this->disableLayout();

        $packageId = $this->getParam('packageId', FILTER_SANITIZE_NUMBER_INT);
        $isReseller = ($this->getParam('isReseller', FILTER_SANITIZE_NUMBER_INT, 0) == 1 ? true : false);

        $gateway = new UserPackageGateway($this->user, $this->customer);
        try {
            $directLink = $gateway->getPackageDirectLink($packageId, $isReseller);
        } catch (CE_Exception $e) {
            CE_Lib::redirectPage(
                'index.php?fuse=clients&controller=products&view=product&id=' . $packageId,
                $e->getMessage()
            );
        }

        if (!empty($directLink['rawlink'])) {
            header("Location: " . $directLink['rawlink']);
        } elseif (!empty($directLink['link'])) {
            header("Location: " . $directLink['link']);
        }
        exit;
    }

    public function getproductsAction()
    {

        include_once 'modules/billing/models/Currency.php';

        if (isset($this->session->product_cache)) {
            unset($this->session->product_cache);
        }

        $currency = new Currency($this->user);
        $gateway = new UserPackageGateway($this->user, $this->customer);

        $args = array();
        $args['limit'] = $this->getParam('limit', FILTER_SANITIZE_NUMBER_INT, 0);
        $originalLimit = $args['limit'];
        $args['start'] = $this->getParam('start', FILTER_SANITIZE_NUMBER_INT, 0);
        $originalStart = $args['start'];
        $args['dir'] = $this->getParam('dir', FILTER_SANITIZE_STRING, 'desc');
        $args['sort'] = $this->getParam('sort', FILTER_SANITIZE_STRING, 'id');
        if ($args['sort'] == 'name') {
            $args['start'] = 0;
            $args['limit'] = 0;
        }

        $validSorts = array('id', 'name', 'nextDueDate', 'term', 'status');
        if (!in_array($args['sort'], $validSorts)) {
            $args['sort'] = 'id';
        }

        $products = array();

        $activeStatuses = StatusAliasGateway::getInstance($this->user)->getPackageStatusIdsFor(array(PACKAGE_STATUS_ACTIVE));
        $iterator = $gateway->getUserPackagesIterator($this->user->getId(), $args);
        while ($product = $iterator->fetch()) {
            $statusInfo = $gateway->getStyledStatus($product->status);
            $a_product = $product->toArray();
            $languageKey = ucfirst(strtolower($this->user->getLanguage()));
            $a_product['id'] = $product->getId();
            $a_product['name'] = $product->getReference(true, false, '', $languageKey, true, true);
            $a_product['status'] = $statusInfo['statusText'];
            if ($product->getRecurringFeeEntry()->GetRecurring() && in_array($product->status, $activeStatuses)) {
                $a_product['nextDueDate'] = $this->view->dateRenderer($product->getRecurringFeeEntry()->getNextBillDate());
                $a_product['nextDueDateTS'] = strtotime($product->getRecurringFeeEntry()->getNextBillDate());
                $a_product['price'] = $currency->format($this->user->getCurrency(), $product->getPrice(false), true, 'NONE', false);
                $a_product['term'] = CE_Lib::getMonthLabel($product->getPaymentTerm(), $this->user);
            } else {
                $a_product['nextDueDate'] = '----';
                $a_product['price'] = '';
                $a_product['term'] = '----';
            }

            $gateway->getFieldsByProductType($product, $a_product, true);
            $actions = $gateway->getPackageActions($a_product['id']);
            $actionsHtml = '';
            $additionalHTML = '';
            if (count($actions) > 0) {
                $actionsHtml = '<div class="btn-group"><a class="btn btn-default dropdown-toggle" data-toggle="dropdown" href="#"><i class="fa fa-cog"></i>&nbsp;<span class="caret"></span></a><ul class="dropdown-menu ce-dropdown-menu dropdown-inverse">';
                foreach ($actions as $action) {
                    if (isset($action['url'])) {
                        $actionsHtml .= '<li><a href="' . $action['url'] . '">' . $action['name'] . '</a></li>';
                    } elseif (isset($action['form'])) {
                        $actionsHtml .= $action['link'];
                        $additionalHTML .= $action['form'];
                    } else {
                        $actionsHtml .= "<li><a onclick=\"productview.callPluginAction('" . $action['command'] . "','" . $a_product['id'] . "');\">" . $action['name'] . '</a></li>';
                    }
                }
                $actionsHtml .= '</ul></div>';
                $a_product['actions'] = $actionsHtml . $additionalHTML;
            }
            if ($args['sort'] == 'name') {
                //The package name will be used to sort the array later. The package id is also attached to avoid issues when having different packages with the same name.
                $products[strtolower($a_product['name'] . $a_product['id'])] = $a_product;
            } else {
                $products[] = $a_product;
            }
        }

        if ($args['sort'] == 'name') {
            if ($args['dir'] == 'desc') {
                krsort($products);
            } else {
                ksort($products);
            }
            $products = array_slice($products, $originalStart, $originalLimit);
            $products2 = array();
            foreach ($products as $singleProduct) {
                $products2[] = $singleProduct;
            }
            $products = $products2;
        }

        $this->send(array('data' => $products, 'total' => $iterator->getTotalNumItems()));
    }

     /**
      * Public view for products
      *
      * @access protected
      * @return void
      */
    protected function productsAction()
    {
        $this->checkPermissions();
        $this->title = $this->user->lang('My Packages');

        $userPackageGateway = new UserPackageGateway($this->user, $this->customer);
        $this->view->packages = $userPackageGateway->getClientPackagesList(true);
    }

    /**
      * Public view for domains list
      *
      * @access protected
      * @return void
      */
    protected function domainsAction()
    {
        $this->checkPermissions();
        $this->title = $this->user->lang('My Domains');

        $userPackageGateway = new UserPackageGateway($this->user, $this->customer);
        $this->view->domains = $userPackageGateway->getClientDomainsList(false);
    }

    /**
     * return only the custom fields for a given product
     * @return json
     */
    protected function getproductcustomfieldsAction()
    {

        $packageid = $this->getParam('packageId', FILTER_SANITIZE_NUMBER_INT);

        //we want to get back customfields for a plan we haven't yet saved so we update the field without saving
        $userPackage = new UserPackage($packageid);

        $gateway = new UserPackageGateway($this->user, $this->customer);

        $datalist = $gateway->getUserPackageGeneralFields($userPackage);

        $this->send(
            array(
            'totalcount' => count($datalist),
            'productFields' => $datalist
            )
        );
    }

    /**
     * Return the type fields for a product
     *
     * @return json
     */
    protected function getclientproductfieldsAction()
    {

        $packageid = $this->getParam('packageId', FILTER_SANITIZE_NUMBER_INT);
        $settingtype = $this->getParam('settingtype', FILTER_SANITIZE_STRING);

        $userPackage = new UserPackage($packageid);
        // if the user isn't an admin, we need to check to make sure they are looking at their package and not someone elses
        if (!$this->user->isAdmin()) {
            if ($this->user->getId() != $userPackage->getCustomerId()) {
                throw new CE_ExceptionPermissionDenied("Package does not exist");
            }
        }

        $gateway = new UserPackageGateway($this->user, $this->customer);

        //we should return global errors here that should be visible in all panels
        //Example .. account not created on server (if plugin)
        //Example .. domain not tied to registrar
        //Merge into product fields
        $errorlist = $gateway->getProductError($userPackage);

        //determine what type of fields we want to return
        switch ($settingtype) {
            case 0: //general
                $datalist = array();
                break;
            case 1: //hosting
                //include addons
                $datalist1 = $gateway->getUserPackageHostingFields($userPackage);
                $datalist2 = $gateway->getUserPackageAddonFields($userPackage);
                $datalist = array_merge($datalist1, $datalist2);
                break;
            case 2: //ssl certifications
                $datalist1 = array();
                //$datalist1 = $gateway->getUserPackageCertificateInfoFields($userPackage);
                $datalist2 = $gateway->getUserPackageAddonFields($userPackage);
                $datalist = array_merge($datalist1, $datalist2);
                break;
            case 3: //domain info
                $datalist1 = $gateway->getUserPackageDomainInfoFields($userPackage);
                $datalist2 = $gateway->getUserPackageAddonFields($userPackage);
                $datalist = array_merge($datalist1, $datalist2);
                break;
            case "domaincontactinfo":
                //not sure if we are going to use
                //refactor into plugin itself
                $datalist = $gateway->getUserPackageDomainContactInfoFields($userPackage);
                break;
            case "hostrecords":
                //not sure if we are going to use
                //refactor into plugin itself
                $datalist = $gateway->getUserPackageHostRecordsFields($userPackage);
                break;
            case "nameservers":
                //not sure if we are going to use
                //refactor into plugin itself
                $datalist = $gateway->getUserPackageNameServers($userPackage);
                break;
            case "groupinfo":
                $datalist1 = $gateway->getProductStatus($userPackage, true);
                $datalist2 = $gateway->getProductGroupInformation($userPackage, true);
                $datalist = array_merge($datalist1, $datalist2);
                break;
            case "addons":
                //not sure if we are going to use
                $datalist = $gateway->getUserPackageAddonFields($userPackage);
                break;
            case "billing":
                //not sure if we are going to use
                $datalist = $gateway->getUserPackageBillingFields($userPackage);
                break;
            default:
                throw new Exception("Passing setting type that doesn't exist");
        }

        $products = $gateway->getUserPackages($this->customer->getId(), $packageid);

        $datalist = array_merge($datalist, $errorlist);

        $this->send(
            array(
            'totalcount' => count($datalist),
            'productFields' => $datalist,
            'productinfo' => $products['results'][0]
            )
        );
    }

    public function requestupgradeAction()
    {
        $this->disableLayout();

        $this->checkPermissions('clients_upgrade_customer_packages');

        $chargeSetupPrices = $this->settings->get("Charge Setup Prices");
        $provideProratedCredit = $this->settings->get("Provide Prorated Credit");
        $addCreditBalance = $this->settings->get("Add Credit Balance");

        $packageId = $this->getParam('id', FILTER_SANITIZE_NUMBER_INT);
        $userPackage = new UserPackage($packageId, array(), $this->user);
        $upgradeDowngradeStatus = $userPackage->getUpgradeDowngradeStatus($this->user);

        if (!$upgradeDowngradeStatus['canUpgrade']) {
            CE_Lib::redirectPage('index.php?fuse=clients&controller=products&view=products', $upgradeDowngradeStatus['upgradeMessage']);
        }

        $upgradingToProductIdArray = array();

        $upgradingToProductId = $userPackage->getCustomField("Upgrading to product id");

        if (!isset($upgradingToProductId) || $upgradingToProductId == '') {
            $upgradingToProductIdArray['Time'] = time();
            $upgradingToProductIdArray['Prorated Credit'] = $upgradeDowngradeStatus['upgradeValue'];
        } else {
            $upgradingToProductIdArrayOld = unserialize($upgradingToProductId);

            if (!is_array($upgradingToProductIdArrayOld)) {
                $upgradingToProductIdArray['Time'] = time();
                $upgradingToProductIdArray['Prorated Credit'] = $upgradeDowngradeStatus['upgradeValue'];
            } else {
                if (!isset($upgradingToProductIdArrayOld['Time']) || time() - $upgradingToProductIdArrayOld['Time'] > 24 * 60 * 60 || !isset($upgradingToProductIdArrayOld['Prorated Credit'])) {
                    $upgradingToProductIdArray['Time'] = time();
                    $upgradingToProductIdArray['Prorated Credit'] = $upgradeDowngradeStatus['upgradeValue'];
                } else {
                    $upgradingToProductIdArray['Time'] = $upgradingToProductIdArrayOld['Time'];
                    $upgradingToProductIdArray['Prorated Credit'] = $upgradingToProductIdArrayOld['Prorated Credit'];

                    //This line is to preserve the discount we displayed to the client, even if the day changed
                    $upgradeDowngradeStatus['upgradeValue'] = $upgradingToProductIdArray['Prorated Credit'];
                }
            }
        }

        $upgradePackage = $this->getParam('upgradePackage', FILTER_SANITIZE_NUMBER_INT);
        $upgradePackageArray = array();
        $upgradeAddonsArray = array();
        $upgradePackageTerm = $this->getParam('priceTerm_' . $upgradePackage, FILTER_SANITIZE_NUMBER_INT);
        $addonsSelected = array();

        foreach ($_POST as $key => $value) {
            $key2 = strstr($key, 'addonSelect_' . $upgradePackage . '_' . $upgradePackageTerm . '_');

            if ($key === $key2) {
                $valueParts = explode('_', $value);
                $addonsSelected['addons'][$valueParts[1]] = $value;
            } else {
                $key3 = strstr($key, 'addonQuantity_' . $upgradePackage . '_' . $upgradePackageTerm . '_');

                if ($key === $key3) {
                    $keyParts = explode('_', $key);
                    $addonsSelected['addonsQuantities'][$keyParts[3]] = $value;
                }
            }
        }

        $package = new Package($upgradePackage);
        $package->getProductPricing();
        $package->getProductPricingAllCurrencies();
        $aogateway = new ActiveOrderGateway($this->user);
        $packageData = $aogateway->getPackageForSelectedGroup(
            array(
                "isUpgradePackage" => true,
                "productGroup"     => $package->planid,
                "selectedProduct"  => $upgradePackage,
                "paymentterm"      => $upgradePackageTerm,
                "productsToGet"    => array($upgradePackage),
                "useIdAsKey"       => true
            )
        );

        $customerTax          = 0;  //default
        $customerTax2         = 0;  //default
        $customerTax2Compound = 0;  //default

        if ($this->user->IsTaxable() == 1) {
            //determine country and state and see if there is a tax in the rules to match
            $customerTax      = $this->user->GetTaxRate();
            //-1 is returned when we don't have a taxrate
            $customerTax     = ($customerTax == -1) ? 0 : $customerTax;

            $customerTax2     = $this->user->GetTaxRate(2);
            //-1 is returned when we don't have a taxrate
            $customerTax2     = ($customerTax2 == -1) ? 0 : $customerTax2;

            if ($this->user->isTax2Compound()) {
                $customerTax2Compound = 1;
            }
        }

        $invoiceEntries = array();

        $productFullName = $packageData[$upgradePackage]['groupname'] . " / " . $packageData[$upgradePackage]['planname'];

        //Need to get it from the original package
        $packageDomainName = $userPackage->getCustomField("Domain Name");

        // Add the domain to the title if required
        if (isset($packageDomainName)) {
            $extraText = ": " . $packageDomainName;
        } else {
            $extraText = '';
        }

        $productDescription = $productFullName . $extraText;

        $m_Taxable = ($package->isCurrencyTaxable($this->user->getCurrency())) ? 1 : 0;

        $m_Price = $package->getCurrencyPrice($this->user->getCurrency(), $upgradePackageTerm);
        $tax1 = $m_Price * ($customerTax / 100);
        $m_TaxAmount = $m_Taxable * ($tax1 + ($m_Price + $customerTax2Compound * $tax1) * ($customerTax2 / 100));

        $itemSetupFee = 0;
        $realItemSetupFee = '';

        if ($chargeSetupPrices) {
            $realItemSetupFee = $package->getCurrencySetupFee($this->user->getCurrency(), $upgradePackageTerm);
            $itemSetupFee = (float) $realItemSetupFee;
        }

        $setupTax1 = $itemSetupFee * ($customerTax / 100);
        $m_SetupTaxAmount = $m_Taxable * ($setupTax1 + ($itemSetupFee + $customerTax2Compound * $setupTax1) * ($customerTax2 / 100));

        $secondsInADay = 60 * 60 * 24;
        $packageAddonGateway = new PackageAddonGateway();

        $upgradePackageArray['New Product Id'] = $upgradePackage;

        // If we have 0 then it's one time
        if ($upgradePackageTerm == 0) {
            // Create an invoice entry for a one time fee
            $params = array(
                'm_CustomerID'    => $this->user->getId(),
                'm_Description'   => $productDescription,
                'm_Detail'        => RecurringEntryGateway::getTermText($upgradePackageTerm),
                'm_InvoiceID'     => 0,
                'm_Date'          => date("Y-m-d"),
                'm_BillingTypeID' => BILLINGTYPE_PACKAGE_UPGRADE,
                'm_IsProrating'   => 0,
                'm_Price'         => $m_Price,
                'm_Quantity'      => 1,
                'm_Recurring'     => 0,
                'm_AppliesToID'   => $packageId,
                'm_Setup'         => 0,
                'm_Taxable'       => $m_Taxable,
                'm_TaxAmount'     => $m_TaxAmount
            );

            $invoiceEntry = new InvoiceEntry($params);
            $invoiceEntry->updateRecord();

            $invoiceEntries[] = $invoiceEntry->m_EntryID;
            $upgradePackageArray['Invoice Entry Id'] = $invoiceEntry->m_EntryID;
        } else {
            if ($chargeSetupPrices && ($realItemSetupFee || $realItemSetupFee == '0' || $realItemSetupFee == '0.00')) {
                // Create setup fee
                $params = array(
                    'm_CustomerID'    => $this->user->getId(),
                    'm_Description'   => $productDescription,
                    'm_Detail'        => "Setup fee",
                    'm_InvoiceID'     => 0,
                    'm_Date'          => date("Y-m-d"),
                    'm_BillingTypeID' => BILLINGTYPE_PACKAGE_UPGRADE,
                    'm_IsProrating'   => 0,
                    'm_Price'         => $realItemSetupFee,
                    'm_Quantity'      => 1,
                    'm_Recurring'     => 0,
                    'm_AppliesToID'   => $packageId,
                    'm_Setup'         => 1,
                    'm_Taxable'       => $m_Taxable,
                    'm_TaxAmount'     => $m_SetupTaxAmount
                );

                $invoiceEntry = new InvoiceEntry($params);
                $invoiceEntry->updateRecord();

                $invoiceEntries[] = $invoiceEntry->m_EntryID;
            }

            if ($m_Price || $m_Price == '0' || $m_Price == '0.00') {
                $nextBillDate = $aogateway->generate_next_bill_date($upgradePackageTerm);

                // Create invice entry
                $params = array(
                    'm_CustomerID'         => $this->user->getId(),
                    'm_Description'        => $productDescription,
                    'm_Detail'             => "Every" . ' ' . strtolower(RecurringEntryGateway::getTermText($upgradePackageTerm)),
                    'm_InvoiceID'          => 0,
                    'm_Date'               => date("Y-m-d"),
                    'm_PeriodStart'        => date('Y-m-d', mktime(0, 0, 0, date("m"), date("d"), date("Y"))),
                    'm_PeriodEnd'          => date('Y-m-d', mktime(0, 0, 0, date("m", strtotime($nextBillDate['nextBilling'])), date("d", strtotime($nextBillDate['nextBilling'])), date("Y", strtotime($nextBillDate['nextBilling']))) - $secondsInADay),
                    'm_BillingTypeID'      => BILLINGTYPE_PACKAGE_UPGRADE,
                    'm_IsProrating'        => 0,
                    'm_Price'              => $m_Price,
                    'm_Quantity'           => 1,
                    'm_Recurring'          => 1,
                    'm_AppliesToID'        => $packageId,
                    'm_Setup'              => 0,
                    'm_Taxable'            => $m_Taxable,
                    'm_RecurringAppliesTo' => 0,
                    'm_BillingCycle'       => $upgradePackageTerm,
                    'm_TaxAmount'          => $m_TaxAmount
                );
                $invoiceEntry = new InvoiceEntry($params);
                $invoiceEntry->updateRecord();

                $invoiceEntries[] = $invoiceEntry->m_EntryID;
                $upgradePackageArray['Invoice Entry Id'] = $invoiceEntry->m_EntryID;
                $upgradePackageArray['Next Due Date'] = $nextBillDate['nextBilling'];
            }
        }

        // Handle addon invoice Entries
        $packageAddons = $aogateway->getAddons($upgradePackage, $upgradePackageTerm, true);

        // Loop the addons in the array
        foreach ($addonsSelected['addons'] as $addonId => $addonValue) {
            // Explode the addon value
            $addonExplode = explode("_", $addonValue);

            // Check we have enough info from the post
            if (count($addonExplode) == 4) {
                // Get the addon
                foreach ($packageAddons as $addon) {
                    // Do we have the right addon?
                    if ($addon['id'] == $addonId) {
                        // Get the price
                        foreach ($addon['prices'] as $price) {
                            if ($price['price_id'] == $addonExplode[2] && $price['recurringprice_cyle'] == $addonExplode[3]) {
                                $addonDescription = $packageAddonGateway->getAddonNameAndOption($price['price_id']) . ' (' . $productDescription . ')';
                                $addonDetail = "Every" . ' ' . strtolower(RecurringEntryGateway::getTermText($price['recurringprice_cyle']));
                                $m_Quantity = $aogateway->verifyAddonQuantity($addonsSelected['addonsQuantities'][$addonId]);

                                $upgradeAddonElementArray = array();
                                $upgradeAddonElementArray['Price Id'] = $price['price_id'];

                                // Check for something above or equal to 0
                                if (isset($price['item_price']) && $price['item_price'] >= 0 && isset($price['recurringprice_cyle']) && $price['recurringprice_cyle'] > 0) {
                                    $nextBillDate = $aogateway->generate_next_bill_date($price['recurringprice_cyle']);
                                    $m_Taxable = ($addon['taxable']) ? 1 : 0;
                                    $m_Price = $price['item_price'];
                                    $tax1 = $m_Price * ($customerTax / 100);
                                    $m_TaxAmount = $m_Taxable * ($tax1 + ($m_Price + $customerTax2Compound * $tax1) * ($customerTax2 / 100));

                                    $tParams = array(
                                        'm_CustomerID'         => $this->user->getId(),
                                        'm_Description'        => $addonDescription,
                                        'm_Detail'             => $addonDetail,
                                        'm_InvoiceID'          => 0,
                                        'm_Date'               => date("Y-m-d"),
                                        'm_PeriodStart'        => date('Y-m-d', mktime(0, 0, 0, date("m"), date("d"), date("Y"))),
                                        'm_PeriodEnd'          => date('Y-m-d', mktime(0, 0, 0, date("m", strtotime($nextBillDate['nextBilling'])), date("d", strtotime($nextBillDate['nextBilling'])), date("Y", strtotime($nextBillDate['nextBilling']))) - $secondsInADay),
                                        'm_BillingTypeID'      => BILLINGTYPE_PACKAGE_ADDON_UPGRADE,
                                        'm_IsProrating'        => 0,
                                        'm_Price'              => $m_Price,
                                        'm_Quantity'           => $m_Quantity,
                                        'm_Recurring'          => 1,
                                        'm_AppliesToID'        => $packageId,
                                        'm_Setup'              => 0,
                                        'm_Taxable'            => $m_Taxable,
                                        'm_RecurringAppliesTo' => 0,
                                        'm_BillingCycle'       => $price['recurringprice_cyle'],
                                        'm_TaxAmount'          => $m_TaxAmount
                                    );
                                    $invoiceEntry = new InvoiceEntry($tParams);
                                    $invoiceEntry->updateRecord();

                                    $invoiceEntries[] = $invoiceEntry->m_EntryID;
                                    $upgradeAddonElementArray['Invoice Entry Id'] = $invoiceEntry->m_EntryID;
                                    $upgradeAddonElementArray['Next Due Date'] = $nextBillDate['nextBilling'];
                                }

                                // Handle the addon setup fee
                                if ($chargeSetupPrices && isset($price['item_has_setup']) && $price['item_has_setup'] && isset($price['item_setup']) && $price['item_setup'] >= 0) {
                                    $m_Taxable = ($addon['taxable']) ? 1 : 0;
                                    $m_Price = $price['item_setup'];
                                    $tax1 = $m_Price * ($customerTax / 100);
                                    $m_TaxAmount = $m_Taxable * ($tax1 + ($m_Price + $customerTax2Compound * $tax1) * ($customerTax2 / 100));

                                    $tParams = array(
                                        'm_CustomerID'    => $this->user->getId(),
                                        'm_Description'   => $addonDescription,
                                        'm_Detail'        => "Setup fee",
                                        'm_InvoiceID'     => 0,
                                        'm_Date'          => date("Y-m-d"),
                                        'm_BillingTypeID' => BILLINGTYPE_PACKAGE_ADDON_UPGRADE,
                                        'm_IsProrating'   => 0,
                                        'm_Price'         => $m_Price,
                                        'm_Quantity'      => $m_Quantity,
                                        'm_Recurring'     => 0,
                                        'm_AppliesToID'   => $packageId,
                                        'm_Setup'         => 0,
                                        'm_AddonSetup'    => 1,
                                        'm_Taxable'       => $m_Taxable,
                                        'm_TaxAmount'     => $m_TaxAmount
                                    );
                                    $invoiceEntry = new InvoiceEntry($tParams);
                                    $invoiceEntry->updateRecord();

                                    $invoiceEntries[] = $invoiceEntry->m_EntryID;

                                    if (!isset($upgradeAddonElementArray['Invoice Entry Id'])) {
                                        $upgradeAddonElementArray['Invoice Entry Id'] = $invoiceEntry->m_EntryID;
                                    }
                                }

                                $upgradeAddonsArray[] = $upgradeAddonElementArray;
                            }
                        }
                    }
                }
            }
        }

        $billingGateway = new BillingGateway($this->user);

        //Create the invoice without the discount, to get the full value of the new package
        $invoiceData = $billingGateway->createInvoiceWithEntries($invoiceEntries);
        $tInvoiceID = $invoiceData['InvoiceID'];
        $addCreditToClient = 0;

        if ($provideProratedCredit) {
            $invoice = new Invoice($tInvoiceID);
            $newPackagePrice = $invoice->getPrice();

            //Calculate if need to add some credit to the client
            if ($addCreditBalance && $upgradeDowngradeStatus['upgradeValue'] > $newPackagePrice) {
                $addCreditToClient = $upgradeDowngradeStatus['upgradeValue'] - $newPackagePrice;
            }

            // Handle coupon code invoice entries
            // Generate the descriptions
            $m_Description = 'Prorated Credit' . ' (' . $productDescription . ')';
            $m_Detail = 'Package Upgrade';
            $m_Price = -$upgradeDowngradeStatus['upgradeValue'];

            $discountInvEntry = new InvoiceEntry(
                array(
                    'm_CustomerID'         => $this->user->getId(),
                    'm_Description'        => $m_Description,
                    'm_Detail'             => $m_Detail,
                    'm_InvoiceID'          =>  0,
                    'm_Date'               => date("Y-m-d"),
                    'm_BillingTypeID'      => BILLINGTYPE_COUPON_DISCOUNT_UPGRADE,
                    'm_IsProrating'        => 0,
                    'm_Price'              => $m_Price,
                    'm_PricePercent'       => 0,
                    'm_Quantity'           => 1,
                    'm_Recurring'          => 0,
                    'm_RecurringAppliesTo' => 0,
                    'm_AppliesToID'        => $packageId,
                    'm_CouponApplicableTo' => 0,
                    'm_Taxable'            => 0,
                    'm_TaxAmount'          => 0
                )
            );
            $discountInvEntry->updateRecord();

            $addInvoiceEntries = array($discountInvEntry->m_EntryID);

            //Update the invoice with the discount entry
            $billingGateway->addWorksToInvoice($addInvoiceEntries, $tInvoiceID);
        }

        $invoice = new Invoice($tInvoiceID);

        //Add Note
        $currentPackage = new Package($userPackage->Plan);
        $currentProductName = $currentPackage->planname;
        $currentProductDescription = $currentProductName . $extraText;
        $newProductName = $packageData[$upgradePackage]['planname'];
        $newProductDescription = $newProductName . $extraText;
        $invoiceNote = "Package Upgrade/Downgrade Details" . "\n"
            . "\n"
            . "Original Package: " . $currentProductDescription . "\n"
            . "Original Billing Cycle: " . RecurringEntryGateway::getTermText($userPackage->getPaymentTerm()) . "\n"
            . "\n"
            . "New Package: " . $newProductDescription . "\n"
            . "New Billing Cycle: " . RecurringEntryGateway::getTermText($upgradePackageTerm);
        $invoice->setNote($invoiceNote);
        $invoice->update();
        //Add Note

        if ($invoice->getPrice() == 0.00) {
            $billingGateway->setPayInvoice($invoice->getId());

            //Add the credit to the client, if any available
            if ($addCreditToClient > 0) {
                $currency = new Currency($this->user);
                $amount = $addCreditToClient;
                $newAmount = ($this->user->getCreditbalance() + $amount);
                $desc = 'Added remaining amount after Upgrading/Downgrading the package #[link]';
                $this->user->setCreditBalance($newAmount);
                $this->user->save();

                $eventLog = Client_EventLog::newInstance(false, $this->user->getId(), $this->user->getId());
                $eventLog->setSubject($this->user->getId());
                $eventLog->setAction(CLIENT_EVENTLOG_ADDEDCREDITALANCE);
                $eventLog->setParams(serialize([$currency->format($this->user->getCurrency(), $amount, true), $desc, $packageId]));
                $eventLog->save();
            }
        }

        //Set the new product id to the value of "Upgrading to product id"
        $upgradingToProductIdArray['Package'] = $upgradePackageArray;
        $upgradingToProductIdArray['Addons'] = $upgradeAddonsArray;
        $userPackage->setCustomField("Upgrading to product id", serialize($upgradingToProductIdArray));

        //- Create Event
        $packageLog = Package_EventLog::newInstance(false, $userPackage->getCustomerId(), $userPackage->getId(), PACKAGE_EVENTLOG_UPGRADE_REQUESTED, $this->user->getId());
        $packageLog->save();

        CE_Lib::redirectPage('index.php?fuse=billing&controller=invoice&view=invoice&id=' . $tInvoiceID, $this->user->lang('Your request to upgrade/downgrade has been submitted.') . ' ' . $this->user->lang('The upgrade/downgrade invoice needs to be paid today.'));
    }

    public function requestcancellationAction()
    {
        $this->disableLayout();

        $this->checkPermissions('clients_cancel_packages');

        $packageId = $this->getParam('id', FILTER_SANITIZE_NUMBER_INT);
        $type = $this->getParam('type', FILTER_SANITIZE_NUMBER_INT);
        $reason = $this->getParam('reason');
        $userPackage = new UserPackage($packageId, array(), $this->user);

        if ($userPackage->CustomerId != $this->user->getId()) {
            CE_Lib::redirectPermissionDenied($this->user->lang('You can not perform this action on this package'));
        }

        $userPackage->requestCancellation($reason, $type);

        $params = array();
        $params['reason'] = $reason;
        $params['type'] = $type;
        $packageLog = Package_EventLog::newInstance(false, $this->user->getId(), $packageId, PACKAGE_EVENTLOG_CANCELLATIONREQUEST, $this->user->getId(), serialize($params));
        $packageLog->save();

        $customer = new User($userPackage->CustomerId);
        $templategateway = new AutoresponderTemplateGateway();
        $autoresponderTemplate = $templategateway->getEmailTemplateByName("Package Cancellation Requested Template");
        $emailMessage = $autoresponderTemplate->getContents();
        $emailMessageSubject = $autoresponderTemplate->getSubject();
        $autoresponderTemplateID = $autoresponderTemplate->getId();
        if ($autoresponderTemplateID !== false) {
            include_once 'modules/admin/models/Translations.php';
            $languages = CE_Lib::getEnabledLanguages();
            $translations = new Translations();
            $languageKey = ucfirst(strtolower($customer->getRealLanguage()));
            CE_Lib::setI18n($languageKey);
            if (count($languages) > 1) {
                $emailMessageSubject = $translations->getValue(EMAIL_SUBJECT, $autoresponderTemplateID, $languageKey, $emailMessageSubject);
                $emailMessage = $translations->getValue(EMAIL_CONTENT, $autoresponderTemplateID, $languageKey, $emailMessage);
            }
            $emailMessage = str_replace("[PACKAGEREFERENCE]", $userPackage->getReference(true, true, '', $languageKey), $emailMessage);
            $emailMessageSubject = str_replace("[PACKAGEREFERENCE]", $userPackage->getReference(true, true, '', $languageKey), $emailMessageSubject);
            $emailMessage = str_replace("[CLIENTNAME]", $customer->getFullName(true), $emailMessage);
            $emailMessageSubject = str_replace("[CLIENTNAME]", $customer->getFullName(true), $emailMessageSubject);
            $emailMessage = str_replace("[CLIENTAPPLICATIONURL]", CE_Lib::getSoftwareURL(), $emailMessage);
            $emailMessageSubject = str_replace("[CLIENTAPPLICATIONURL]", CE_Lib::getSoftwareURL(), $emailMessageSubject);
            $emailMessage = str_replace("[COMPANYNAME]", $this->settings->get("Company Name"), $emailMessage);
            $emailMessageSubject = str_replace("[COMPANYNAME]", $this->settings->get("Company Name"), $emailMessageSubject);
            $emailMessage = str_replace("[COMPANYADDRESS]", $this->settings->get("Company Address"), $emailMessage);
            $emailMessageSubject = str_replace("[COMPANYADDRESS]", $this->settings->get("Company Address"), $emailMessageSubject);
        }
        include_once 'library/CE/NE_MailGateway.php';
        $MailGateway = new NE_MailGateway();

        $fromEmail = $this->settings->get('Support E-mail');
        if ($autoresponderTemplate->getOverrideFrom() != '') {
            $fromEmail = $autoresponderTemplate->getOverrideFrom();
        }

        $MailGateway->MailMessage(
            $emailMessage,
            $fromEmail,
            $this->settings->get('Company Name'),
            $userPackage->CustomerId,
            '',
            $emailMessageSubject,
            '3',
            false,
            'notifications',
            '',
            '',
            MAILGATEWAY_CONTENTTYPE_HTML
        );

        if ($userPackage->getProductType() == PACKAGE_TYPE_DOMAIN) {
            CE_Lib::redirectPage(
                'index.php?fuse=clients&controller=products&view=domains',
                $this->user->lang('Your request to cancel has been submitted.')
            );
        }
        CE_Lib::redirectPage(
            'index.php?fuse=clients&controller=products&view=products',
            $this->user->lang('Your request to cancel has been submitted.')
        );
    }

    public function requestremovecancellationAction()
    {
        $this->disableLayout();

        $this->checkPermissions('clients_cancel_packages');

        $packageId = $this->getParam('id', FILTER_SANITIZE_NUMBER_INT);
        $userPackage = new UserPackage($packageId, array(), $this->user);

        if ($userPackage->CustomerId != $this->user->getId()) {
            CE_Lib::redirectPermissionDenied($this->user->lang('You can not perform this action on this package'));
        }

        $userPackage->requestRemoveCancellation();

        $packageLog = Package_EventLog::newInstance(false, $this->user->getId(), $packageId, PACKAGE_EVENTLOG_REMOVECANCELLATIONREQUEST, $this->user->getId());
        $packageLog->save();

        $customer = new User($userPackage->CustomerId);
        $templategateway = new AutoresponderTemplateGateway();
        $autoresponderTemplate = $templategateway->getEmailTemplateByName("Package Remove Cancellation Requested Template");
        $emailMessage = $autoresponderTemplate->getContents();
        $emailMessageSubject = $autoresponderTemplate->getSubject();
        $autoresponderTemplateID = $autoresponderTemplate->getId();
        if ($autoresponderTemplateID !== false) {
            include_once 'modules/admin/models/Translations.php';
            $languages = CE_Lib::getEnabledLanguages();
            $translations = new Translations();
            $languageKey = ucfirst(strtolower($customer->getRealLanguage()));
            CE_Lib::setI18n($languageKey);
            if (count($languages) > 1) {
                $emailMessageSubject = $translations->getValue(EMAIL_SUBJECT, $autoresponderTemplateID, $languageKey, $emailMessageSubject);
                $emailMessage = $translations->getValue(EMAIL_CONTENT, $autoresponderTemplateID, $languageKey, $emailMessage);
            }
            $emailMessage = str_replace("[PACKAGEREFERENCE]", $userPackage->getReference(true, true, '', $languageKey), $emailMessage);
            $emailMessageSubject = str_replace("[PACKAGEREFERENCE]", $userPackage->getReference(true, true, '', $languageKey), $emailMessageSubject);
            $emailMessage = str_replace("[CLIENTNAME]", $customer->getFullName(true), $emailMessage);
            $emailMessageSubject = str_replace("[CLIENTNAME]", $customer->getFullName(true), $emailMessageSubject);
            $emailMessage = str_replace("[CLIENTAPPLICATIONURL]", CE_Lib::getSoftwareURL(), $emailMessage);
            $emailMessageSubject = str_replace("[CLIENTAPPLICATIONURL]", CE_Lib::getSoftwareURL(), $emailMessageSubject);
            $emailMessage = str_replace("[COMPANYNAME]", $this->settings->get("Company Name"), $emailMessage);
            $emailMessageSubject = str_replace("[COMPANYNAME]", $this->settings->get("Company Name"), $emailMessageSubject);
            $emailMessage = str_replace("[COMPANYADDRESS]", $this->settings->get("Company Address"), $emailMessage);
            $emailMessageSubject = str_replace("[COMPANYADDRESS]", $this->settings->get("Company Address"), $emailMessageSubject);
        }
        include_once 'library/CE/NE_MailGateway.php';
        $MailGateway = new NE_MailGateway();
        $MailGateway->MailMessage(
            $emailMessage,
            $this->settings->get('Support E-mail'),
            $this->settings->get('Company Name'),
            $userPackage->CustomerId,
            '',
            $emailMessageSubject,
            '3',
            false,
            'notifications',
            '',
            '',
            MAILGATEWAY_CONTENTTYPE_HTML
        );

        CE_Lib::redirectPage('index.php?fuse=clients&controller=products&view=products', $this->user->lang('Your request to remove cancellation has been completed.'));
    }

    protected function cancelAction()
    {
        $this->title = $this->user->lang('Request Cancellation');
        $this->checkPermissions('clients_cancel_packages');

        $this->jsLibs = array("templates/default/views/clients/productspublic/cancel.js");

        $packageId = $this->getParam('id', FILTER_SANITIZE_NUMBER_INT);
        $userPackage = new UserPackage($packageId);

        if ($userPackage->CustomerId != $this->user->getId()) {
            CE_Lib::redirectPermissionDenied($this->user->lang('You can not perform this action on this package'));
        }

        $this->view->packageId = $packageId;
        $this->view->packageName = $userPackage->getReference(true);
        $this->view->endOfBillingPeriod = $userPackage->getEndOfBillingPeriod(false);
        $this->view->paidInvoicesCount = $userPackage->getPaidInvoicesCount();
    }

    protected function upgradeAction()
    {
        $this->checkPermissions('clients_upgrade_customer_packages');

        $packageId = $this->getParam('id', FILTER_SANITIZE_NUMBER_INT);
        $userPackage = new UserPackage($packageId, array(), $this->user);
        $upgradeDowngradeStatus = $userPackage->getUpgradeDowngradeStatus($this->user);

        if (!$upgradeDowngradeStatus['canUpgrade']) {
            CE_Lib::redirectPage('index.php?fuse=clients&controller=products&view=products', $upgradeDowngradeStatus['upgradeMessage']);
        }

        $this->title = $this->user->lang('Request Upgrade/Downgrade');

        $this->jsLibs = ['templates/default/views/clients/productspublic/upgrade.js', 'javascript/accounting.js'];
        $this->cssPages = ['templates/default/views/clients/productspublic/upgrade.css'];

        $this->view->upgradeDowngradeStatus = $upgradeDowngradeStatus;

        if ($userPackage->CustomerId != $this->user->getId()) {
            CE_Lib::redirectPermissionDenied($this->user->lang('You can not perform this action on this package'));
        }

        $this->view->packageId = $packageId;
        $this->view->packageName = $userPackage->getReference(true);

        $package = new Package($userPackage->Plan);

        $upgradingToProductIdArray = array();

        $upgradingToProductId = $userPackage->getCustomField("Upgrading to product id");

        if (!isset($upgradingToProductId) || $upgradingToProductId == '') {
            $upgradingToProductIdArray['Time'] = time();
            $upgradingToProductIdArray['Prorated Credit'] = $upgradeDowngradeStatus['upgradeValue'];
        } else {
            $upgradingToProductIdArrayOld = unserialize($upgradingToProductId);

            if (!is_array($upgradingToProductIdArrayOld)) {
                $upgradingToProductIdArray['Time'] = time();
                $upgradingToProductIdArray['Prorated Credit'] = $upgradeDowngradeStatus['upgradeValue'];
            } else {
                $upgradingToProductIdArray = $upgradingToProductIdArrayOld;
                $upgradingToProductIdArray['Time'] = time();
                $upgradingToProductIdArray['Prorated Credit'] = $upgradeDowngradeStatus['upgradeValue'];
            }
        }

        $userPackage->setCustomField("Upgrading to product id", serialize($upgradingToProductIdArray));

        $upgradePackagesAvailable = array();

        foreach ($package->upgrades as $packageUpgrade) {
            $upgradePackagesAvailable[] = $packageUpgrade[1];
        }

        $aogateway = new ActiveOrderGateway($this->user);
        $packageData = $aogateway->getPackageForSelectedGroup(
            array(
            "isUpgradePackage" => true,
            "productGroup"     => $package->planid,
            "selectedProduct"  => 0,
            "paymentterm"      => -1,
            "productsToGet"    => $upgradePackagesAvailable,
            "useIdAsKey"       => true
            )
        );

        $this->view->upgradePackages = $packageData;

        $packageAddons = array();

        foreach ($packageData as $packageItem) {
            //Exclude packages out of stock
            if ($packageItem['stockControl'] == 1) {
                unset($this->view->upgradePackages[$packageItem["id"]]);
                continue;
            }

            foreach ($packageItem["pricing"] as $packageItemPricing) {
                $packageAddons[$packageItem["id"]][$packageItemPricing["termId"]] = array_map(function ($value) {
                    // the addon name is passed through user->lang() in the template,
                    // so gotta guard against percentage signs
                    $value['name'] = str_replace('%', '%%', $value['name']);
                    $value['namelanguage'] = str_replace('%', '%%', $value['namelanguage']);
                    return $value;
                }, $aogateway->getAddons($packageItem["id"], $packageItemPricing["termId"], true));
            }
        }

        // check if we have savings to show.
        $hasSavings = $aogateway->doWeHaveAnySavings($this->view->upgradePackages);
        $this->view->showSaved = ($this->settings->get('Include Saved Percentage') && $hasSavings == true) ? 1 : 0;
        $this->view->hideSetupFees = ($this->settings->get('Hide Setup Fees') || !$this->settings->get('Charge Setup Prices'));
        $this->view->packageAddons = $packageAddons;

        $customerTax          = 0;  //default
        $customerTaxName      = ''; //default
        $customerTax2         = 0;  //default
        $customerTax2name     = ''; //default
        $customerTax2Compound = 0;  //default

        if ($this->user->IsTaxable() == 1) {
            //determine country and state and see if there is a tax in the rules to match
            $customerTax      = $this->user->GetTaxRate();
            //-1 is returned when we don't have a taxrate
            $customerTax     = ($customerTax == -1) ? 0 : $customerTax;
            $customerTaxName  = $this->user->GetTaxName();

            $customerTax2     = $this->user->GetTaxRate(2);
            //-1 is returned when we don't have a taxrate
            $customerTax2     = ($customerTax2 == -1) ? 0 : $customerTax2;
            $customerTax2Name = $this->user->GetTaxName(2);

            if ($this->user->isTax2Compound()) {
                $customerTax2Compound = 1;
            }
        }

        $this->view->customerTax = $customerTax;
        $this->view->customerTaxName = $customerTaxName;
        $this->view->customerTax2 = $customerTax2;
        $this->view->customerTax2Name = $customerTax2Name;
        $this->view->customerTax2Compound = $customerTax2Compound;

        $currency = new Currency($this->user);

        $decimalssep = $currency->getDecimalsSeparator($this->user->getCurrency());
        $thousandssep = $currency->getThousandsSeparator($this->user->getCurrency());
        $this->view->currency = array(
        'symbol'       => $currency->ShowCurrencySymbol($this->user->getCurrency(), "NONE", true),
        'decimalssep'  => ($decimalssep === ' ') ? '&nbsp;' : $decimalssep,
        'thousandssep' => ($thousandssep === ' ') ? '&nbsp;' : $thousandssep,
        'alignment'    => ($currency->getAlignment($this->user->getCurrency()) == 'left') ? "%s%v" : "%v%s",
        'precision'    => $currency->getPrecision($this->user->getCurrency()),
        'abrv'         => $currency->getAbbr($this->user->getCurrency()),
        'showabrv'     => ($this->settings->get('Show Currency Code') == 1) ? ' ' . $currency->getAbbr($this->user->getCurrency()) : ''
        );
    }

    public function updatecustomfieldAction()
    {
        include_once 'modules/clients/models/UserPackageGateway.php';

        $packageId = $this->getParam('id', FILTER_SANITIZE_NUMBER_INT);
        $fieldName = $this->getParam('fieldName', FILTER_SANITIZE_STRING);
        $fieldValue = $this->getParam('value');

        $userPackage = new UserPackage($packageId);
        $userPackageGateway = new UserPackageGateway($this->user);

        if ($userPackage->getCustomerId() != $this->user->getId()) {
            CE_Lib::redirectPermissionDenied($this->user->lang('You can not perform this action on this package'));
        }

        $userPackage->setCustomField($fieldName, $fieldValue, CUSTOM_FIELDS_FOR_PACKAGE);

        $params = [];
        $params[$fieldName] = $fieldValue;
        $packageLog = Package_EventLog::newInstance(false, $this->user->getId(), $packageId, PACKAGE_EVENTLOG_CHANGEDCUSTOMFIELD, $this->user->getId(), serialize($params));
        $packageLog->save();

        if ($userPackageGateway->hasPlugin($userPackage, $pluginName)) {
            $changes = [];
            $userPackageGateway->callPluginAction($userPackage, 'Update', $changes);
        }

        $this->message = $this->user->lang('You have successfully %s.', $fieldName);
        $this->send();
    }

    public function updatecsrAction()
    {

        $packageId = $this->getParam('id', FILTER_SANITIZE_NUMBER_INT);
        $csr = $this->getParam('csr', FILTER_SANITIZE_STRING);

        $userPackage = new UserPackage($packageId);

        if ($userPackage->getCustomerId() != $this->user->getId()) {
            CE_Lib::redirectPermissionDenied($this->user->lang('You can not perform this action on this package'));
        }
        $userPackage->setCustomField("Certificate CSR", $csr);

        $this->message = $this->user->lang('You have successfully updated your CSR.');
        $this->send();
    }

    public function updatehostingpasswordAction()
    {
        include_once 'modules/clients/models/UserPackageGateway.php';

        $packageId = $this->getParam('id', FILTER_SANITIZE_NUMBER_INT);
        $userPackage = new UserPackage($packageId);

        if ($userPackage->getCustomerId() != $this->user->getId()) {
            CE_Lib::redirectPermissionDenied($this->user->lang('You can not perform this action on this package'));
        }

        $userPackageGateway = new UserPackageGateway($this->user);
        $userPackageGateway->adminUpdateUserPackageHosting($userPackage);

        $params = array();
        $params['Updated Password'] = 'XXX MASKED PASSWORD XXX';
        $packageLog = Package_EventLog::newInstance(false, $this->user->getId(), $packageId, PACKAGE_EVENTLOG_UPDATED, $this->user->getId(), serialize($params));
        $packageLog->save();

        $this->message = $this->user->lang('You have successfully updated your password.');
        $this->send();
    }

    protected function isvalidsubdomainAction()
    {
        $subDomainName = $this->getParam('subDomainName', FILTER_SANITIZE_STRING, "");
        $subDomainTld  = $this->getParam('subDomainTld', FILTER_SANITIZE_STRING, "");
        $gateway = new UserPackageGateway($this->user, $this->customer);
        $result = $gateway->isValidSubDomain($subDomainName, $subDomainTld);
        $this->send(array("results" => $result));
    }

    protected function productsnapinviewAction()
    {
        include_once "modules/admin/models/PluginGateway.php";
        $pluginGateway = new PluginGateway($this->user);
        $userPackageGateway = new UserPackageGateway($this->user);
        UserGateway::ensureCustomerIsValid($this->customer);

        $publicPanel = $this->getParam('publicPanel', FILTER_VALIDATE_INT, 0, false);
        if ($publicPanel == 1) {
            $key = $this->getParam('key');
            $id = $this->getParam('id', FILTER_VALIDATE_INT);
            $userPackage = new UserPackage($id);


            if (!$userPackageGateway->hasPlugin($userPackage, $pluginName)) {
                CE_Lib::redirectPage('index.php?fuse=clients&controller=products&view=product&id=' . $id);
            }

            $plugin = $pluginGateway->getPluginByUserPackage($userPackage, $pluginName);
            if ($plugin == null) {
                CE_Lib::redirectPage('index.php?fuse=clients&controller=products&view=product&id=' . $id);
            }

            if (!method_exists($plugin, $key)) {
                CE_Lib::redirectPage('index.php?fuse=clients&controller=products&view=product&id=' . $id);
            }
            $this->view->output = $plugin->$key($userPackage, $this->view);
            $this->title = $plugin->features['publicPanels'][$key];
        } else {
            $snapinName = $this->getParam('name');
            $key = $this->getParam('key');
            $plugin = $pluginGateway->getSnapinContent($snapinName, $this->view);

            $view = $pluginGateway->getHookByKey($snapinName, 'public_profileproducttab', $key);

            $matched_mapping = array();
            $matched_mapping['type'] = "hooks";
            $matched_mapping['loc'] = "public_profileproducttab";
            $matched_mapping['tpl'] = $view['tpl'];

            $this->title = $view['title'];

            $_GET['selectedtab'] = $snapinName;

            $plugin->setMatching($matched_mapping);

            $loadassets = false;
            $output = $plugin->mapped_view($loadassets);

            $this->view->output = $output;
        }
        if ($loadassets) {
            //let's see if we have js file and css file for this tab
            if (file_exists("plugins/snapin/" . $snapinName . "/" . $view['tpl'] . ".js")) {
                $this->jsLibs[] = "plugins/snapin/" . $snapinName . "/" . $view['tpl'] . ".js";
            }
            if (file_exists("plugins/snapin/" . $snapinName . "/" . $view['tpl'] . ".css")) {
                $this->cssPages[] = "plugins/snapin/" . $snapinName . "/" . $view['tpl'] . ".css";
            }
        }
    }

    public function getupgradedowngradestatusAction()
    {
        $userPackage = new UserPackage($_POST['id']);
        $upgradeDowngradeStatus = $userPackage->getUpgradeDowngradeStatus($this->user);
        $this->send($upgradeDowngradeStatus);
    }

    public function cancelupgradedowngradeAction()
    {
        $userPackage = new UserPackage($_POST['id']);
        $userPackage->cancelUpgradeDowngrade();
        $this->send();
    }
}
