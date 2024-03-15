<?php
require_once 'modules/admin/models/AddonGateway.php';
require_once 'modules/admin/models/ExportPlugin.php';
require_once 'modules/admin/models/PackageGateway.php';
require_once 'modules/admin/models/PackageTypeGateway.php';
require_once 'modules/billing/models/InvoiceListGateway.php';
require_once 'modules/billing/models/InvoiceEntriesGateway.php';
require_once 'modules/clients/models/ClientListGateway.php';
require_once 'modules/admin/models/ServerGateway.php';
require_once 'modules/support/models/DepartmentGateway.php';
require_once 'modules/support/models/TicketGateway.php';
require_once 'modules/support/models/TicketLogIterator.php';
require_once 'modules/admin/models/server.php';
include_once "modules/admin/models/PackageGateway.php";
include_once "modules/admin/models/PackageType.php";

/**
* @package Plugins
*/
class PluginCeformat extends ExportPlugin
{
    /**
     * Header columns names.
     */
    protected $_columns = array(
        'domains' => array('clientid', 'activateddate', 'domainname', 'recurring', 'registrationperiod', 'status', 'nextduedate', 'registrar', 'subscription_id'),
        'hosting' => array('id', 'clientid', 'username', 'plan', 'regdate', 'domain', 'status', 'nextinvoicedate', 'paymentterm', 'price', 'server', 'acctproperties', 'coupon', 'subscription_id'),
        'hosting_addons' => array('clientid', 'packageid', 'addonid', 'addonoptionid', 'nextinvoicedate', 'paymentterm', 'price', 'name', 'subscription_id', 'quantity'),
        'invoices' => array('id', 'clientid', 'amount', 'datedue', 'datepaid', 'description', 'detail', 'tax', 'subtotal', 'status', 'currency'),
        'invoices_entries' => array('id', 'clientid', 'invoiceid', 'relid', 'amount', 'tax', 'datedue', 'detail', 'description'),
        'invoices_transaction' => array('id', 'invoiceid', 'accepted', 'response', 'transactiondate', 'transactionid', 'action', 'last4', 'amount'),
        'packages' => array('id', 'name', 'description', 'packagetype', 'tax', 'pricing', 'servers', 'show', 'name_on_server', 'bundled_domain'),
        'packages_addons' => array('id', 'name', 'description', 'products'),
        'packages_addons_options' => array('id', 'packageaddonid', 'detail', 'pricing'),
        'packages_groups' => array('id', 'description', 'insignup', 'name', 'type', 'canDelete', 'groupOrder', 'style', 'advanced'),
        'servers' => array('id', 'name', 'hostname', 'ipaddress', 'assignedips', 'statusaddress', 'maxaccounts', 'type', 'username', 'password', 'accesshash', 'secure', 'nameserver1', 'nameserver1ip', 'nameserver2', 'nameserver2ip', 'nameserver3', 'nameserver3ip', 'nameserver4', 'nameserver4ip'),
        'users' => array('id', 'firstname', 'lastname', 'address', 'email', 'city', 'state', 'zip', 'phone', 'country', 'company', 'status', 'language', 'cardnum', 'expdate', 'billing_profile_id', 'password', 'balance', 'currency', 'vat_number', 'date_created', 'gateway'),
        'departments' => array('id', 'name'),
        'tickets' => array('id', 'userid', 'datesubmitted', 'subject', 'message', 'status', 'priority', 'name', 'email'),
        'ticket_logs' => array('id', 'tid', 'userid', 'mydatetime', 'message', 'email', 'is_staff'),

        //These ones are currently not exported
        /*
        'coupons' => array('id', 'code', 'type', 'recurring', 'value', 'appliesto', 'startdate', 'expirationdate'),
        'staff' => array('id', 'firstname', 'lastname', 'email', 'status', 'password'),
        'alternate_accounts' => array('userid', 'email', 'sendnotifications', 'sendinvoice', 'sendsupport'),
        'tax_rule' => array('countryiso', 'state', 'tax', 'vat', 'name', 'level', 'compound'),
        'kb_categories' => array('id', 'parent_id', 'name', 'description', 'staffonly', 'catid', 'language'),
        'kb_articles' => array('id', 'title', 'content', 'access', 'myorder', 'categoryid', 'tags', 'parentid', 'language'),
        'kb_articles_files' => array('id', 'filename', 'dateadded', 'filekey'),
        'canned_response' => array('name', 'response'),
        'smtp' => array('host', 'username', 'password', 'port'),
        */
        //These ones are currently not exported

        'email' => array('id', 'userid', 'subject', 'content_encrypted', 'content', 'date', 'to', 'sender', 'fromName'),
        'credit_history' => array('date', 'user_id', 'subject', 'description', 'amount')
    );

    protected $_columnsBuffer = array();

    public $_description = 'This export plugin exports all your data to the default ClientExec format, which is a compressed CSV file.';

    protected $_filename = null;

    /**
     * Lines buffer. For performance propulse.
     */
    protected $_linesBuffer = array();

    /**
     * This controls the amount of lines needed to be processed before writting to the file.
     */
    protected $_linesBufferLimit = 100;

    public $_title = 'ClientExec Format';

    /**
     * Zlib file pointer
     */
    protected $_zp;

    protected function _addColumn($value, $skipEscaping = false)
    {
        $value = str_replace("\r\n", "\n", $value);

        if (!$skipEscaping) {
            $value = json_encode($value);
            $value = str_replace(',', '\c', $value);
        }

        $this->_columnsBuffer[] = $value;
    }

    protected function _addHeader($section)
    {
        if (!array_key_exists($section, $this->_columns)) {
            throw new Exception("Invalid section '{$section}'.");
        }

        $this->_addLine("; {$section}");

        foreach ($this->_columns[$section] as $column) {
            $this->_addColumn($column, true);
        }

        $this->_addLine();
    }

    protected function _addLine($lineContents = null)
    {
        if ($lineContents === null) {
            if (count($this->_columnsBuffer) < 1) {
                throw new Exception('Cannot add a line without columns.');
            }

            $lineContents = implode(',', $this->_columnsBuffer);
            $this->_columnsBuffer = array();
        }

        $lineContents = trim($lineContents);

        if (empty($lineContents)) {
            throw new Exception('Cannot add an empty line to the file.');
        }

        $lineContents .= "\n";

        $this->_linesBuffer[] = $lineContents;

        if (count($this->_linesBuffer) >= $this->_linesBufferLimit) {
            $this->_clearLinesBuffer();
        }
    }

    protected function _clearLinesBuffer()
    {
        foreach ($this->_linesBuffer as $line) {
            if (!gzwrite($this->_zp, $line)) {
                throw new Exception("Unable to write to the file '{$this->_filename}'.");
            }
        }

        $this->_linesBuffer = array();
    }

    protected function _export()
    {
        $this->_setupFile();

        if (!($this->_zp = gzopen($this->_filename, 'w9'))) {
            throw new Exception("Unable to open the file '{$this->_filename}'.");
        }

        $this->_exportUsers();
        $this->_exportServers();
        $this->_exportPackagesGroups();
        $this->_exportPackages();
        $this->_exportPackagesAddons();
        $this->_exportHosting();
        $this->_exportInvoices();
        $this->_exportInvoicesEntries();
        $this->_exportDomains();
        $this->_exportHostingAddons();
        $this->_exportDepartments();
        $this->_exportTickets();
        $this->_exportTicketLogs();

         $this->_clearLinesBuffer();

        if (!gzclose($this->_zp)) {
            throw new Exception("Unable to close the file '{$this->_filename}'.");
        }

        $contents = @file_get_contents($this->_filename);
        @unlink($this->_filename);

        return $contents;
    }

    function _exportDepartments()
    {
        $this->_addHeader('departments');

        $gateway = new DepartmentGateway($this->user);
        $deparments = $gateway->getDepartments();
        while ($department = $deparments->fetch()) {
            $this->_addColumn($department->getId());
            $this->_addColumn($department->getName());
            $this->_addLine();
        }
    }

    function _exportTickets()
    {
        $this->_addHeader('tickets');

        $gateway = new TicketGateway($this->user);
        $tickets = $gateway->getAllTickets();
        while ($ticket = $tickets->fetch()) {
            $this->_addColumn($ticket->getId());
            $this->_addColumn($ticket->getUserId());
            $this->_addColumn($ticket->getDateSubmitted());
            $this->_addColumn($ticket->getSubject());
            $message = $ticket->getLogs();
            $this->_addColumn($message[0]->getMessage());
            $this->_addColumn($ticket->getStatusId());
            $this->_addColumn($ticket->getPriority());
            $user = new User($ticket->getUserId());
            $this->_addColumn($user->getFirstName());
            $this->_addColumn($user->getEmail());
            $this->_addLine();
        }
    }

    function _exportTicketLogs()
    {
        $this->_addHeader('ticket_logs');

        $iterator = new TicketLogIterator(new TicketLog());

        while ($ticketLog = $iterator->fetch()) {
            $this->_addColumn($ticketLog->getId());
            $this->_addColumn($ticketLog->getTroubleTicketId());
            $this->_addColumn($ticketLog->getUserId());
            $this->_addColumn($ticketLog->getMyDateTime());
            $this->_addColumn($ticketLog->getMessage());
            $user = new User($ticketLog->getUserId());
            $this->_addColumn($user->getEmail());
            $this->_addColumn(($user->isAdmin() ? 1 : 0));
            $this->_addLine();
        }
    }


    function _exportServers()
    {
        $this->_addHeader('servers');
        $serverGateway = new ServerGateway($this->user);
        $servers = $serverGateway->getServersGridList();
        foreach ($servers['data'] as $data) {
            $nameServers = $serverGateway->getNameServers($data['id']);
            $this->_addColumn($data['id']);
            $this->_addColumn($data['name']);
            $this->_addColumn($data['hostname']);
            $this->_addColumn($data['sharedip']);
            $this->_addColumn('');
            $this->_addColumn($data['statsurl']);
            $this->_addColumn($data['rawAmount']);
            $this->_addColumn(strtolower($data['plugin']));
            $this->_addColumn('');
            $this->_addColumn('');
            $this->_addColumn('');
            $this->_addColumn('');
            $this->_addColumn(@$nameServers['data'][0]['hostname']);
            $this->_addColumn(@$nameServers['data'][0]['ip']);
            $this->_addColumn(@$nameServers['data'][1]['hostname']);
            $this->_addColumn(@$nameServers['data'][1]['ip']);
            $this->_addColumn(@$nameServers['data'][2]['hostname']);
            $this->_addColumn(@$nameServers['data'][2]['ip']);
            $this->_addColumn(@$nameServers['data'][3]['hostname']);
            $this->_addColumn(@$nameServers['data'][3]['ip']);
            $this->_addLine();
        }
    }

    protected function _exportDomains()
    {
        $this->_addHeader('domains');
        // Domains are exported directly with Packages
    }

    protected function _exportHosting()
    {
        $this->_addHeader('hosting');
        $gateway = new UserPackageGateway($this->user);
        $userPackages = $gateway->getUserPackagesIterator();
        while ($userPackage = $userPackages->fetch()) {
            $userPackage->loadCustomFields();

            //need to convert it to months, as in the other exporters
            $paymentTerm = $userPackage->getRecurringFeeEntry()->getPaymentTerm();
            $billingCycle = new BillingCycle($paymentTerm);
            $exportPaymentTerm = $billingCycle->amount_of_units.$billingCycle->time_unit;
            $this->_addColumn($userPackage->getId());
            $this->_addColumn($userPackage->CustomerId);
            $this->_addColumn($userPackage->getCustomField('User Name'));
            $this->_addColumn($userPackage->Plan);
            $this->_addColumn($userPackage->dateActivated);
            $this->_addColumn($userPackage->getCustomField('Domain Name'));
            $this->_addColumn($userPackage->status);
            $this->_addColumn($userPackage->getRecurringFeeEntry()->getNextBillDate());
            $this->_addColumn($exportPaymentTerm);
            $this->_addColumn($userPackage->getRecurringFeeEntry()->GetAmount());
            $this->_addColumn($userPackage->getCustomField('Server Id'));
            $this->_addColumn($userPackage->getCustomField('Server Acct Properties'));
            $this->_addColumn('');
            $this->_addColumn('');
            $this->_addLine();
        }
    }

    protected function _exportHostingAddons()
    {
        $this->_addHeader('hosting_addons');
    }

    protected function _exportInvoices()
    {
        $this->_addHeader('invoices');
        $gateway = new InvoiceListGateway($this->user);
        $invoices = $gateway->getAllInvoices();

        foreach ($invoices as $invoice) {
            $this->_addColumn($invoice->m_InvoiceID);
            $this->_addColumn($invoice->m_UserID);
            $this->_addColumn($invoice->m_Price);
            $this->_addColumn($invoice->m_Date);
            $this->_addColumn($invoice->m_DatePaid);
            $this->_addColumn($invoice->m_Description);
            $this->_addColumn(null);
            $this->_addColumn($invoice->m_Tax);
            $this->_addColumn($invoice->m_SubTotal);
            $this->_addColumn($invoice->m_Status);
            $this->_addColumn($invoice->m_Currency);
            $this->_addLine();
        }
    }

    protected function _exportInvoicesEntries()
    {
        $this->_addHeader('invoices_entries');
        $gateway = new InvoiceEntriesGateway($this->user);
        $entries = $gateway->getInvoiceEntries('id', 'ASC');
        $entries = $entries['invoiceentriesiterator'];
        while ($entry = $entries->fetch()) {
            $this->_addColumn($entry->getId());
            $this->_addColumn($entry->getCustomerId());
            $this->_addColumn($entry->getInvoiceId());
            $this->_addColumn('');
            $this->_addColumn($entry->getPrice());
            $this->_addColumn($entry->getTaxable());
            $this->_addColumn($entry->getDate());
            $this->_addColumn($entry->getDetail());
            $this->_addColumn($entry->getDescription());
            $this->_addLine();
        }
    }

    protected function _exportPackages()
    {
        //Billing Cycles
        $billingcycles = array();

        $gateway = new BillingCycleGateway();
        $iterator = $gateway->getBillingCycles(array(), array('order_value', 'ASC'));

        while ($cycle = $iterator->fetch()) {
            $billingcycles[$cycle->amount_of_units.$cycle->time_unit] = $cycle->id;
        }
        //Billing Cycles

        $this->_addHeader('packages');
        $gateway = new PackageGateway($this->user);
        $packages = $gateway->getProductsGrid();

        foreach ($packages as $package) {
            // empty array (since we call get products grid, it has some bad data that we don't need here)
            if ($package['id'] == '0') {
                continue;
            }

            $currency = $this->settings->get('Default Currency');
            $pricing = array();
            $model = new Package($package['id']);
            $productPricing = $model->getProductPricing();
            $productPricingAllCurrencies = $model->getProductPricingAllCurrencies();

            if (is_array($productPricing)) {
                foreach ($billingcycles as $cyclekey => $cycleid) {
                    if (isset($productPricing['price'.$cycleid])) {
                        $pricing['multicurrency'][$currency][$cyclekey]['price'] = ($productPricing['price'.$cycleid] < 0)? '' : $productPricing['price'.$cycleid];
                    }

                    if (isset($productPricing['price'.$cycleid.'_setup'])) {
                        $pricing['multicurrency'][$currency][$cyclekey]['setup'] = ($productPricing['price'.$cycleid.'_setup'] < 0)? '' : $productPricing['price'.$cycleid.'_setup'];
                    }

                    if (isset($productPricing['price'.$cycleid.'included'])) {
                        $pricing['multicurrency'][$currency][$cyclekey]['included'] = $productPricing['price'.$cycleid.'included'];
                    }
                }

                if (isset($productPricing['taxable'])) {
                    $pricing['multicurrency'][$currency]['taxable'] = $productPricing['taxable'];
                } else {
                    $pricing['multicurrency'][$currency]['taxable'] = 0;
                }
            }

            //Code for prices in different currencies
            foreach ($productPricingAllCurrencies as $currencyCode => $currencyPricing) {
                foreach ($billingcycles as $cyclekey => $cycleid) {
                    if (isset($currencyPricing['price'.$cycleid])) {
                        $pricing['multicurrency'][$currencyCode][$cyclekey]['price'] = ($currencyPricing['price'.$cycleid] < 0)? '' : $currencyPricing['price'.$cycleid];
                    }

                    if (isset($currencyPricing['price'.$cycleid.'_setup'])) {
                        $pricing['multicurrency'][$currencyCode][$cyclekey]['setup'] = ($currencyPricing['price'.$cycleid.'_setup'] < 0)? '' : $currencyPricing['price'.$cycleid.'_setup'];
                    }

                    if (isset($currencyPricing['price'.$cycleid.'included'])) {
                        $pricing['multicurrency'][$currencyCode][$cyclekey]['included'] = $currencyPricing['price'.$cycleid.'included'];
                    }
                }

                if (isset($currencyPricing['taxable'])) {
                    $pricing['multicurrency'][$currencyCode]['taxable'] = $currencyPricing['taxable'];
                } else {
                    $pricing['multicurrency'][$currencyCode]['taxable'] = 0;
                }
            }

            $servers = $model->getProductServerIds();

            $pluginName = '';
            $nameOnServer = '';

            foreach($servers as $serverId) {
                $server = new Server($serverId);
                $pluginName = strtolower($server->getPlugin());

                if ($pluginName != '') {
                    $nameOnServer = $model->getVariable('plugin_' . $pluginName);

                    break;
                }
            }

            $packageType = new PackageType($model->planid);
            $bundledDataArray = $gateway->getBundledProducts($packageType->getType(), $model->id);
            $bundledDomain = 0;

            foreach ($bundledDataArray as $bundledData) {
                if ($bundledData['packagetype'] != $this->user->lang("Domain")) {
                    continue;
                }

                if ($bundledData['checked']) {
                    $bundledDomain = 1;

                    break;
                }
            }

            $this->_addColumn($model->id);
            $this->_addColumn($model->planname);
            $this->_addColumn($model->description);
            $this->_addColumn($model->planid);

            if (is_array($productPricing)) {
                $this->_addColumn($model->isTaxable()); // #fix - correct?
            } else {
                $this->_addColumn('');
            }

            $this->_addColumn(serialize($pricing));
            $this->_addColumn(serialize($servers));
            $this->_addColumn($model->showpackage);
            $this->_addColumn($nameOnServer);
            $this->_addColumn($bundledDomain);
            $this->_addLine();
        }
    }

    protected function _exportPackagesAddons()
    {
        //Billing Cycles
        $billingcycles = array();
        $billingcyclesinverse = array();

        $gateway = new BillingCycleGateway();
        $iterator = $gateway->getBillingCycles(array(), array('order_value', 'ASC'));

        while ($cycle = $iterator->fetch()) {
            $billingcycles[$cycle->amount_of_units.$cycle->time_unit] = $cycle->id;
            $billingcyclesinverse[$cycle->id] = $cycle->amount_of_units.$cycle->time_unit;
        }
        //Billing Cycles

        $gateway = new AddonGateway($this->user);
        $addons = $gateway->getAllAddons();
        $aAddon = array();

        foreach ($addons as $addon) {
            $aAddon[$addon['id']]  = $addon;
        }

        $this->_addHeader('packages_addons');

        foreach ($aAddon as $addon) {
            $productsArray = $gateway->getProductsThatUseAddon($addon['id']);
            $productsString = implode(',', $productsArray);

            $this->_addColumn($addon['id']);
            $this->_addColumn($addon['name']);
            $this->_addColumn($addon['description']);
            $this->_addColumn($productsString);
            $this->_addLine();
        }

        $this->_addHeader('packages_addons_options');
        $ids = array();

        foreach ($aAddon as $addon) {
            $tempAddon = new Addon($addon['id']);
            $addonPricesAllCurrencies = $tempAddon->getPricesAllCurrencies(true);

            foreach ($tempAddon->getPrices(true) as $price) {
                $pricing = array();

                $currency = $this->settings->get('Default Currency');

                foreach ($price as $priceKey => $priceValue) {
                    if (strpos($priceKey, 'price') !== false && strpos($priceKey, '_force') === false) {
                        $cycleid = str_replace('price', '', $priceKey);
                        $pricing['multicurrency'][$currency][$billingcyclesinverse[$cycleid]]['price'] = $priceValue;
                    }
                }

                foreach ($addonPricesAllCurrencies as $currencyCode => $currencyPricing) {
                    if ($currencyPricing['id'] == $price['id']) {
                        foreach ($currencyPricing as $priceKey => $priceValue) {
                            if (strpos($priceKey, 'price') !== false && strpos($priceKey, '_force') === false) {
                                $cycleid = str_replace('price', '', $priceKey);
                                $pricing['multicurrency'][$currencyCode][$billingcyclesinverse[$cycleid]]['price'] = $priceValue;
                            }
                        }
                    }
                }

                $this->_addColumn($price['id']);
                $this->_addColumn($addon['id']);
                $this->_addColumn($price['detail']);
                $this->_addColumn(serialize($pricing));
                $this->_addLine();

                $ids[] = $price['id'];
            }
        }
    }

    protected function _exportPackagesGroups()
    {
        $this->_addHeader('packages_groups');
        $gateway = new PackageTypeGateway($this->user);
        $groups = $gateway->getPackageTypes();

        while ($group = $groups->fetch()) {
            $this->_addColumn($group->getId());
            $this->_addColumn($group->getDescription());
            $this->_addColumn($group->getInSignup());
            $this->_addColumn($group->getName());
            $this->_addColumn($group->getType());
            $this->_addColumn($group->getCanDelete());
            $this->_addColumn($group->getOrder());
            $this->_addColumn($group->getListStyle());
            $this->_addColumn($group->getAdvanced());
            $this->_addLine();
        }
    }

    protected function _exportUsers()
    {
        $this->_addHeader('users');
        $gateway = new ClientListGateway($this->user);
        $clients = $gateway->getClients(false, 0, 'id', 'asc', null, '', 'allclients', false);

        while ($client = $clients->fetch()) {
            $model = new User($client->getId());
            $this->_addColumn($model->getId());
            $this->_addColumn($model->getFirstName());
            $this->_addColumn($model->getLastName());
            $this->_addColumn($model->getAddress());
            $this->_addColumn($model->getEmail());
            $this->_addColumn($model->getCity());
            $this->_addColumn($model->getState());
            $this->_addColumn($model->getZipCode());
            $this->_addColumn($model->getPhone());
            $this->_addColumn($model->getCountry());
            $this->_addColumn($model->getOrganization());
            $this->_addColumn($model->getStatus());
            $this->_addColumn($model->getRealLanguage());
            $this->_addColumn('');
            $this->_addColumn('');
            $this->_addColumn('');
            $this->_addColumn('');
            $this->_addColumn($model->getCreditBalance());
            $this->_addColumn($model->getCurrency());
            $this->_addColumn($model->getVatNumber());
            $this->_addColumn($model->getDateCreated());
            $this->_addColumn($model->getPaymentType());
            $this->_addLine();
        }
    }

    protected function _setupFile()
    {
        $this->_filename = tempnam(sys_get_temp_dir(), 'PHP');

        if (!file_exists($this->_filename)) {
            // Attempt to create the file
            if (!touch($this->_filename)) {
                throw new Exception('Unable to create the temporary file.');
            }
        }

        if (!is_writable($this->_filename)) {
            // Attempt to give write permissions
            if (!chmod($this->_filename, 0666)) {
                throw new Exception('Unable to set temporary file permissions.');
            }
        }
    }

    public function getFileContentsUncompressed()
    {
        $fileContents = '';

        $zd = gzopen($this->_filename, 'r');

        while (!gzeof($zd)) {
            $buffer = gzgets($zd);
            $fileContents .= $buffer;
        }

        gzclose($zd);

        return $fileContents;
    }

    public function getForm()
    {
        return $this->view->render('PluginCeformat.phtml');
    }

    public function process($post)
    {
        $contents = $this->_export();
        $filename = 'clientexec.csv.gz';
        CE_Lib::download($contents, $filename);
    }
}
