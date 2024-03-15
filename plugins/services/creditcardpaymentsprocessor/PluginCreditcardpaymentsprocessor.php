<?php
require_once 'modules/admin/models/ServicePlugin.php';
require_once 'modules/billing/models/BillingType.php';
require_once 'modules/billing/models/Invoice_EventLog.php';
require_once 'modules/billing/models/BillingGateway.php';

/**
* @package Plugins
*/
class PluginCreditcardpaymentsprocessor extends ServicePlugin
{
    protected $featureSet = 'billing';
    public $hasPendingItems = true;
    public $permission = 'billing_view';

    function getVariables()
    {
        $variables = array(
            lang('Plugin Name')   => array(
                'type'          => 'hidden',
                'description'   => '',
                'value'         => lang('Credit Card Payments Processor'),
            ),
            lang('Enabled')       => array(
                'type'          => 'yesno',
                'description'   => lang('When enabled, will process your clients credit cards for invoices that are due or past-due. This will only process your clients whose credit card is stored outside of Clientexec.').$this->supportedPlugins(),
                'value'         => '0',
            ),
            lang('Include invoices previously declined')       => array(
                'type'          => 'yesno',
                'description'   => lang('When enabled, will also process your clients credit cards for invoices that are due or past-due and have declined transactions.'),
                'value'         => '0',
            ),
            lang('Run schedule - Minute')  => array(
                'type'          => 'text',
                'description'   => lang('Enter number, range, list or steps'),
                'value'         => '0',
                'helpid'        => '8',
            ),
            lang('Run schedule - Hour')  => array(
                'type'          => 'text',
                'description'   => lang('Enter number, range, list or steps'),
                'value'         => '0',
            ),
            lang('Run schedule - Day')  => array(
                'type'          => 'text',
                'description'   => lang('Enter number, range, list or steps'),
                'value'         => '*',
            ),
            lang('Run schedule - Month')  => array(
                'type'          => 'text',
                'description'   => lang('Enter number, range, list or steps'),
                'value'         => '*',
            ),
            lang('Run schedule - Day of the week')  => array(
                'type'          => 'text',
                'description'   => lang('Enter number in range 0-6 (0 is Sunday) or a 3 letter shortcut (e.g. sun)'),
                'value'         => '*',
            ),
        );

        return $variables;
    }

    function supportedPlugins()
    {
        $supportedPlugins = '</br></br><b>'.$this->user->lang('Supported plugins').':</b> ';
        $plugingateway = new PluginGateway($this->user);
        $CCStoredOutsidePlugins = $plugingateway->getGatewayWithVariablePlugins('CC Stored Outside');
        
        if ($CCStoredOutsidePlugins > 0) {
            $supportedPluginsNames = array();

            foreach ($CCStoredOutsidePlugins as $CCStoredOutsidePlugin) {
                $supportedPluginsNames[] = $this->settings->get('plugin_'.$CCStoredOutsidePlugin.'_Plugin Name');
            }

            $supportedPluginsList = implode('</li><li>', $supportedPluginsNames);
            $supportedPlugins .= '<ul><li>'.$supportedPluginsList.'</li></ul>';
        } else {
            $supportedPlugins .= $this->user->lang('None');
        }
        
        return $supportedPlugins;
    }

    function execute()
    {
        $messages = array();
        $numClients = 0;

        $billingGateway = new BillingGateway($this->user);
        $initial = 0;
        $includeDeclined = $this->settings->get('plugin_creditcardpaymentsprocessor_Include invoices previously declined');
        $passphrase = '';
        $allAtOnce = true;
        $billingGateway->process_invoice($initial, $includeDeclined, $passphrase, $allAtOnce);
        if (isset($this->session->all_invoices)){
              $numClients = count($this->session->all_invoices);
        }
        $billingGateway->send_process_invoice_summary("process");

        //$this->settings->updateValue("LastDateGenerateInvoices", time());

        array_unshift($messages, "$numClients client(s) were charged");
        return $messages;
    }

    function pendingItems()
    {
        $returnArray = array();
        $returnArray['data'] = array();

        $currency = new Currency($this->user);
        $includeDeclined = $this->settings->get('plugin_creditcardpaymentsprocessor_Include invoices previously declined');
        $billingGateway = new BillingGateway($this->user);
        $result = $billingGateway->get_invoices_who_needs_to_charge_cc($includeDeclined, true);

        while ($row = $result->fetch()) {
            $user = new User($row['user_id']);
            $tmpInfo = array();
            $tmpInfo['invoice'] = '<a href="index.php?controller=invoice&fuse=billing&frmClientID='.$user->getId().'&view=invoice&invoiceid='.$row['invoice_id'].'">#'.$row['invoice_id'].'</a>';
            $tmpInfo['duedate'] = date($this->settings->get('Date Format'), $row['invoiceduedate']);
            $tmpInfo['client'] = '<a href="index.php?fuse=clients&controller=userprofile&view=profilecontact&frmClientID='.$user->getId().'">'.$user->getFullName(true).'</a>';
            $tmpInfo['gateway'] = ($row['paymenttype'] != 'none')? $this->user->lang($this->settings->get('plugin_'.$row['paymenttype'].'_Plugin Name')) : $this->user->lang('None');
            $tmpInfo['balancedue'] = $currency->format($user->getCurrency(), $row['balance_due'], true );
            $returnArray['data'][] = $tmpInfo;
        }

        $returnArray['totalcount'] = count($returnArray['data']);
        $returnArray['headers'] = array (
            $this->user->lang('Invoice').' #',
            $this->user->lang('Due'),
            $this->user->lang('Client Name'),
            $this->user->lang('Gateway'),
            $this->user->lang('Balance Due')
        );

        return $returnArray;
    }

    function output() { }

    function dashboard()
    {
        $count = 0;
        $includeDeclined = $this->settings->get('plugin_creditcardpaymentsprocessor_Include invoices previously declined');
        $billingGateway = new BillingGateway($this->user);
        $result = $billingGateway->get_invoices_who_needs_to_charge_cc($includeDeclined, true);

        while ($row = $result->fetch()) {
            $count++;
        }

        return $this->user->lang('Number of clients to be charged: %d', $count);
    }
}
?>
