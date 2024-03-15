<?php
require_once 'modules/admin/models/PluginCallback.php';
require_once 'modules/billing/models/class.gateway.plugin.php';

class PluginMollieCallback extends PluginCallback
{
    function processCallback()
    {

        $mollie = new \Mollie\Api\MollieApiClient();
        $mollie->setApiKey($this->settings->get('plugin_mollie_API Key'));

        $payment = $mollie->payments->get($_POST["id"]);

        CE_Lib::log(4, 'PLUGIN CALLBACK PAYMENT VALUES: ' . print_r($payment, true));

        $pluginName = 'mollie';
        $payAmount = $payment->amount->value;
        $transactionId = $payment->id;
        $invoiceNO = $payment->metadata->invoice_id;
        $cPlugin = new Plugin($invoiceNO, $pluginName, $this->user);
        $newInvoice = $cPlugin->retrieveInvoiceForTransaction($transactionId);
        if ($newInvoice) {
            $invoiceID = $cPlugin->m_ID;
        }
        $cPlugin->setAmount($payAmount);
        $cPlugin->m_TransactionID = $transactionId;
        $cPlugin->m_Action = "charge";


        if ($payment->isPaid() && !$payment->hasRefunds() && !$payment->hasChargebacks()) {

            $transaction = "$pluginName payment of $payAmount was accepted. Original Signup Invoice: $invoiceID (OrderID: " . $transactionId . ")";
            $cPlugin->PaymentAccepted($payAmount, $transaction, $transactionId);
        } elseif ($payment->isOpen() || $payment->isPending()) {

            $transaction = "$pluginName payment of $payAmount was marked 'pending'. Original Signup Invoice: $invoiceID (OrderID: " . $transactionId . ")";
            $cPlugin->PaymentPending($transaction, $transactionId);
        } elseif ($payment->isFailed() || $payment->isExpired() || $payment->isCanceled() || $payment->hasChargebacks()) {

            $transaction = "$pluginName payment of $payAmount was not accepted. Original Signup Invoice: $invoiceID (OrderID: " . $transactionId . ")";
            $cPlugin->PaymentRejected($payAmount, $transaction, $transactionId);
        } elseif ($payment->hasRefunds()) {

            $transaction = "$pluginName payment of $payAmount was refunded. Original Signup Invoice: $invoiceID (OrderID: " . $payment->id . ")";
            $cPlugin->PaymentRefunded($payAmount, $transaction, $transactionId);
        }
    }
}
