<?php
require_once 'modules/admin/models/GatewayPlugin.php';
require_once 'modules/billing/models/class.gateway.plugin.php';

/**
 * @package Plugins
 */
class PluginMollie extends GatewayPlugin
{
    function getVariables()
    {
        $variables = array(
            lang("Plugin Name") => array(
                "type"          => "hidden",
                "description"   => lang("How CE sees this plugin (not to be confused with the Signup Name)"),
                "value"         => lang("Mollie")
            ),
            lang('API Key') => array(
                'type'          => 'password',
                'description'   => lang('Mollie API Key. For test mode, use the test API key from your Mollie account.'),
                'value'         => ''
            ),
            lang("Signup Name") => array(
                "type"          => "text",
                "description"   => lang("Select the name to display in the signup process for this payment type. Example: Mollie iDeal."),
                "value"         => "Mollie"
            )
        );
        return $variables;
    }

    function credit($params)
    {

        $mollie = new \Mollie\Api\MollieApiClient();
        $mollie->setApiKey($this->getVariable('API Key'));

        $transactionID = $params['invoiceRefundTransactionId'];
        $payment = $mollie->payments->get($transactionID);

        if ($payment->canBeRefunded() && $payment->amountRemaining->currency === $params['currencytype'] && $payment->amountRemaining->value >= sprintf("%01.2f", round($params['invoiceTotal'], 2))) {

            $cPlugin = new Plugin($params['invoiceNumber'], "mollie", $this->user);
            $cPlugin->setAmount($params['invoiceTotal']);
            $cPlugin->setTransactionID($transactionID);
            $cPlugin->setAction("refund");
            $cPlugin->setLast4("NA");

            $payment->refund([
                "amount" => [
                    "currency" => $params['currencytype'],
                    "value" => sprintf("%01.2f", round($params['invoiceTotal'], 2)),
                ]
            ]);

            $transaction = "Mollie payment " . $params['invoiceTotal'] . " was refunded. Original Signup Invoice: " . $params['invoiceNumber'] . " (OrderID: " . $transactionID . ")";
            $cPlugin->PaymentAccepted($params['invoiceTotal'], $transaction, $refund->id);
            return array('AMOUNT' => $params['invoiceTotal']);
        }
    }

    function singlepayment($params)
    {

        $mollie = new \Mollie\Api\MollieApiClient();
        $mollie->setApiKey($this->getVariable('API Key'));

        $payment = $mollie->payments->create([
            "amount" => [
                "currency" => $params['userCurrency'],
                "value" => sprintf("%01.2f", round($params['invoiceTotal'], 2)),
            ],
            "description" => $this->user->lang('Invoice') . " #" . $params['invoiceNumber'],
            "redirectUrl" => $params['invoiceviewURLSuccess'],
            "webhookUrl"  => $params['clientExecURL'] . '/plugins/gateways/mollie/callback.php',
            "metadata" => [
                "invoice_id" => $params['invoiceNumber'],
            ],
        ]);

        $cPlugin = new Plugin($params['invoiceNumber'], "mollie", $this->user);
        $cPlugin->setAmount($params['invoiceTotal']);
        $cPlugin->setAction('charge');
        $cPlugin->m_Last4 = "NA";
        $pluginName = 'Mollie';
        $payAmount = sprintf("%01.2f", round($params['invoiceTotal'], 2));
        $invoiceID = $params['invoiceNumber'];
        $transactionId = $payment->id;
        $cPlugin->setTransactionID($payment->id);
        $transaction = "$pluginName payment of $payAmount was marked 'pending' by $pluginName. Original Signup Invoice: $invoiceID (OrderID: " . $transactionId . ")";
        $cPlugin->PaymentPending($transaction, $transactionId);

        header("Location: " . $payment->getCheckoutUrl(), true, 303);
        exit();
    }
}
