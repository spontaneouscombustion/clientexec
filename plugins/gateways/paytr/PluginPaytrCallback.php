<?php

require_once 'modules/billing/models/class.gateway.plugin.php';

class PluginPaytrCallback extends PluginCallback
{
    public function processCallback()
    {
        $merchantKey = $this->settings->get('plugin_paytr_Merchant Key');
        $merchantSalt = $this->settings->get('plugin_paytr_Merchant Salt');

        $hash = base64_encode(
            hash_hmac(
                'sha256',
                $_POST['merchant_oid'].$merchantSalt.$_POST['status'].$_POST['total_amount'],
                $merchantKey,
                true
            )
        );

        if ($hash != $_POST['hash']) {
            die('PAYTR notification failed: bad hash');
        }

        if ($_POST['status'] == 'success') {
            $orderId = explode('CE', $_POST["merchant_oid"]);
            $invoiceId = explode('SP', $orderId[0])[1];
            $transactionId = $_POST['merchant_oid'];
            $amount = sprintf("%01.2f", round(($_POST['total_amount'] / 100), 2));

            $cPlugin = new Plugin($invoiceId, 'paytr', $this->user);
            $cPlugin->m_TransactionID = $transactionId;
            $cPlugin->setAmount($amount);
            $cPlugin->setAction('charge');
            $cPlugin->m_Last4 = "NA";
            $cPlugin->PaymentAccepted(
                $amount,
                "PayTR payment of {$amount} was accepted. (Transaction ID: {$transactionId})",
                $transactionId
            );
        }

        echo "OK";
        exit;
    }
}
