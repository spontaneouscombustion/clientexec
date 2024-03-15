<?php
require_once 'modules/admin/models/PluginCallback.php';
require_once 'modules/billing/models/class.gateway.plugin.php';
require_once 'library/CE/NE_PluginCollection.php';
require_once 'plugins/gateways/pesapal/OAuth.php';

class PluginPesapalCallback extends PluginCallback
{

    function processCallback()
    {
        $consumerKey = $this->settings->get('plugin_pesapal_Consumer Key');
        $consumerSecret = $this->settings->get('plugin_pesapal_Consumer Secret');

        $statusrequestAPI = 'https://www.pesapal.com/api/querypaymentstatus';
        if ($this->settings->get('plugin_pesapal_Test Mode') == '1') {
            $statusrequestAPI = 'https://demo.pesapal.com/api/querypaymentstatus';
        }

        $pesapalNotification = $_GET['pesapal_notification_type'];
        $pesapalTrackingId = $_GET['pesapal_transaction_tracking_id'];
        $pesapal_merchant_reference = $_GET['pesapal_merchant_reference'];

        if ($pesapalNotification == "CHANGE" && $pesapalTrackingId != '') {
            $token = $params = NULL;
            $consumer = new OAuthConsumer($consumerKey, $consumerSecret);
            $signature_method = new OAuthSignatureMethod_HMAC_SHA1();

            //get transaction status
            $request_status = OAuthRequest::from_consumer_and_token($consumer, $token, "GET", $statusrequestAPI);
            $request_status->set_parameter("pesapal_merchant_reference", $pesapal_merchant_reference);
            $request_status->set_parameter("pesapal_transaction_tracking_id", $pesapalTrackingId);
            $request_status->sign_request($signature_method, $consumer, $token);

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $request_status);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HEADER, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            $response = curl_exec($ch);
            $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $raw_header  = substr($response, 0, $header_size - 4);
            $headerArray = explode("\r\n\r\n", $raw_header);
            $header      = $headerArray[count($headerArray) - 1];
            //transaction status
            $elements = preg_split("/=/",substr($response, $header_size));
            $status = $elements[1];
            curl_close($ch);

            $invoice = new Invoice($pesapal_merchant_reference);
            $cPlugin = new Plugin($pesapal_merchant_reference, "pesapal", $this->user);
            $cPlugin->setTransactionID($pesapalTrackingId);
            $cPlugin->setAmount($invoice->getPrice());
            $cPlugin->setAction('charge');

            if ($status == 'COMPLETED') {
                $cPlugin->PaymentAccepted($invoice->getPrice(), "Pesapal payment was accepted.", $pesapalTrackingId);
            }

            $resp="pesapal_notification_type=$pesapalNotification&pesapal_transaction_tracking_id=$pesapalTrackingId&pesapal_merchant_reference=$pesapal_merchant_reference";
            echo $resp;
        }
    }
}