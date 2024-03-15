<?php

require_once 'modules/admin/models/PluginCallback.php';
require_once 'modules/billing/models/class.gateway.plugin.php';
require_once 'modules/billing/models/Invoice.php';

class PluginCoinbasecommerceCallback extends PluginCallback
{
    public function processCallback()
    {
        // Format payload data
        $rawBody = file_get_contents("php://input");
        $decodedBody = json_decode($rawBody, true);

        // Retrieve data returned in payload
        $success = $decodedBody['event']['type'];
        $invoiceId = $decodedBody['event']['data']['metadata']['invoice_id'];
        $transactionId = $decodedBody['event']['data']['payments']['0']['transaction_id'];
        $paymentAmount = $decodedBody['event']['data']['payments']['0']['value']['local']['amount'];
        $hash = $_SERVER["HTTP_X_CC_WEBHOOK_SIGNATURE"];

        $transactionStatus = ($success == 'charge:confirmed') ? 'Success' : 'Failure';

        //Create plug in class to interact with CE
        $cPlugin = new Plugin($invoiceId, 'coinbasecommerce', $this->user);
        $cPlugin->setAmount($paymentAmount);
        $cPlugin->setAction('charge');

        if ($cPlugin->IsUnpaid()  == 1) {
            // Validate that the payload is valid
            $secretKey = trim($cPlugin->GetPluginVariable("plugin_coinbasecommerce_Webhook Shared Secret"));
            if ($hash != hash_hmac('SHA256', $rawBody, $secretKey)) {
                $transactionStatus = 'Hash Verification Failure';
                $success = false;
                $transaction = " CoinbaseCommerce payment of $paymentAmount Failed (" . $transactionStatus . ")";
                $cPlugin->PaymentRejected($transaction);
                return;
            }

            if ($success == 'charge:confirmed') {
                $transaction = " CoinbaseCommerce payment of $paymentAmount Successful (Transaction ID:" . $transactionId . ")";
                $cPlugin->PaymentAccepted($paymentAmount, $transaction);
                $returnURL = CE_Lib::getSoftwareURL() . "/index.php?fuse=billing&paid=1&controller=invoice&view=invoice&id=" . $invoiceId;
                header("Location: " . $returnURL);
                exit;
            } else {
                $transaction = " CoinbaseCommerce payment of $paymentAmount Failed (Transaction ID:" . $transactionId . ")";
                $cPlugin->PaymentRejected($transaction);
                return;
            }
        } else {
            return;
        }
    }
}
