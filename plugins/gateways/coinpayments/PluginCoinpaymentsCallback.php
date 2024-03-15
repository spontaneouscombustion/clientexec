<?php
require_once 'modules/admin/models/PluginCallback.php';
require_once 'modules/admin/models/StatusAliasGateway.php' ;
require_once 'modules/billing/models/class.gateway.plugin.php';
require_once 'modules/billing/models/Invoice_EventLog.php';
require_once 'modules/admin/models/Error_EventLog.php';

class PluginCoinpaymentsCallback extends PluginCallback
{

    function processCallback()
    {
        $merchantId = $this->settings->get('plugin_coinpayments_Merchant ID');
        $ipnSecret = $this->settings->get('plugin_coinpayments_IPN Secert');
        if (!isset($_POST['ipn_mode']) || $_POST['ipn_mode'] != 'hmac') {
            CE_Lib::log(4, "** No IPN Mode, or not HMAC from CoinPayments callback");
            return;
        }

        if (!isset($_SERVER['HTTP_HMAC']) || empty($_SERVER['HTTP_HMAC'])) {
            CE_Lib::log(4, "** No HMAC from CoinPayments callback");
            return;
        }

        $request = file_get_contents('php://input');
        if ($request === FALSE || empty($request)) {
            CE_Lib::log(4, "** No Request from CoinPayments callback");
            return;
        }

        if (!isset($_POST['merchant']) || $_POST['merchant'] != trim($merchantId)) {
            CE_Lib::log(4, "** Invalid Merchant Id");
            return;
        }

        $hmac = hash_hmac("sha512", $request, trim($ipnSecret));
        if (!hash_equals($hmac, $_SERVER['HTTP_HMAC'])) {
            CE_Lib::log(4, "** HMAC does not match");
            return;
        }

        $txn_id = $_POST['txn_id'];
        $item_name = $_POST['item_name'];
        $item_number = $_POST['item_number'];
        $amount1 = floatval($_POST['amount1']);
        $amount2 = floatval($_POST['amount2']);
        $currency1 = $_POST['currency1'];
        $currency2 = $_POST['currency2'];
        $status = intval($_POST['status']);
        $status_text = $_POST['status_text'];
        $invoiceNumber = $_POST['invoice'];

        $cPlugin = new Plugin($invoiceNumber, "coinpayments", $this->user);
        $cPlugin->setAmount($amount1);
        $cPlugin->setAction('charge');
        $cPlugin->setTransactionID($txn_id);

        if ($status >= 100 || $status == 2) {
            $transaction = "Coinpayments payment of {$amount1} has been completed.";
            if ($status_text != '') {
                $transaction = "Coinpayments payment of {$amount1} has been completed. ($status_text)";
            }
            $cPlugin->PaymentAccepted($amount1, $transaction, $invoiceNumber);
        } else if ($status < 0) {
            $transaction = 'Invalid Transaction';
            if ($status_text != '') {
                $transaction = "Invalid Transaction. ($status_text)";
            }
            $cPlugin->PaymentRejected($transaction);
        } else {
            $transaction = "Coinpayments payment of {$amount1} has been received.";
            if ($status_text != '') {
                $transaction = "Coinpayments payment of {$amount1} has been received. ($status_text)";
            }
            $cPlugin->PaymentPending($transaction, $invoiceNumber);
        }
    }
}