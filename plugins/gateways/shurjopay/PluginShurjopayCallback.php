<?php
require_once 'modules/admin/models/PluginCallback.php';
require_once 'modules/billing/models/class.gateway.plugin.php';
require_once 'modules/billing/models/Invoice.php';

class PluginshurjopayCallback extends PluginCallback
{
    function processCallback()
    {
        if (isset($_REQUEST['order_id']) && !empty($_REQUEST['order_id'])) {
            $cPlugin = new Plugin('', 'shurjopay', $this->user);
            $TestMode = trim($cPlugin->GetPluginVariable("plugin_shurjopay_Test Mode"));

            $sanbox_url  = 'https://sandbox.shurjopayment.com/';
            $live_url    = 'https://engine.shurjopayment.com/';

            if ($TestMode == 1) {
                $payment_url = $sanbox_url;
            } else {
                $payment_url = $live_url;
            }

            $verification_url = $payment_url . "api/verification/";
            $order_id = $_REQUEST['order_id'];
            $response = json_decode($this->decrypt_and_validate($order_id, $verification_url));
            
            $amount = trim($response[0]->amount);
            $order_id = trim($response[0]->order_id);
            $invoiceId = trim($response[0]->value1);
            $currencyCode = $response[0]->currency;
            $price = $amount . " " . $currencyCode;

            $cPlugin = new Plugin($invoiceId, 'shurjopay', $this->user);
            $cPlugin->setAmount($amount);
            $cPlugin->setAction('charge');

            if (trim($response[0]->sp_massage) == 'Success') {
                //Create plug in class to interact with CE
                if ($cPlugin->IsUnpaid() == 1) {
                    $transaction = " Shurjopay payment of $price Successful (Order ID: " . $order_id . ")";
                    $cPlugin->PaymentAccepted($amount, $transaction);
                    $returnURL = CE_Lib::getSoftwareURL() . "/index.php?fuse=billing&paid=1&controller=invoice&view=invoice&id=" . $invoiceId;
                    header("Location: " . $returnURL);
                    exit;
                } else {
                    return;
                }
            } else {
                $transaction = " Shurjopay payment of $price Failed (Order ID: " . $order_id . ")";
                $cPlugin->PaymentRejected($transaction);
                $returnURL = CE_Lib::getSoftwareURL() . "/index.php?fuse=billing&cancel=1&controller=invoice&view=invoice&id=" . $invoiceId;
                header("Location: " . $returnURL);
                exit;
            }
            return;
        }
        return;
    }
    function decrypt_and_validate($order_id, $verification_url)
    {
        $token = json_decode($this->getToken(), true);
        $header = array(
            'Content-Type:application/json',
            'Authorization: Bearer ' . $token['token']
        );
        $postFields = json_encode(
            array(
                'order_id' => $order_id
            )
        );
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $verification_url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/0 (Windows; U; Windows NT 0; zh-CN; rv:3)");
        $caPathOrFile = \Composer\CaBundle\CaBundle::getSystemCaRootBundlePath();
        if (is_dir($caPathOrFile)) {
            curl_setopt($ch, CURLOPT_CAPATH, $caPathOrFile);
        } else {
            curl_setopt($ch, CURLOPT_CAINFO, $caPathOrFile);
        }
        $response = curl_exec($ch);
        if ($response === false) {
            echo json_encode(curl_error($ch));
        }
        curl_close($ch);
        return $response;
    }
    function getToken()
    {
        $cPlugin = new Plugin('', 'shurjopay', $this->user);
        $APIUsername = trim($cPlugin->GetPluginVariable("plugin_shurjopay_API Username"));
        $APIPassword = trim($cPlugin->GetPluginVariable("plugin_shurjopay_API Password"));
        $TestMode = trim($cPlugin->GetPluginVariable("plugin_shurjopay_Test Mode"));

        $sanbox_url  = 'https://sandbox.shurjopayment.com/';
        $live_url    = 'https://engine.shurjopayment.com/';

        if ($TestMode == 1) {
            $payment_url = $sanbox_url;
        } else {
            $payment_url = $live_url;
        }
        $token_url = $payment_url . "api/get_token";
        $postFields = array(
            'username' => $APIUsername,
            'password' => $APIPassword,
        );
        if (empty($token_url) || empty($postFields)) {
            return null;
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $token_url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $caPathOrFile = \Composer\CaBundle\CaBundle::getSystemCaRootBundlePath();
        if (is_dir($caPathOrFile)) {
            curl_setopt($ch, CURLOPT_CAPATH, $caPathOrFile);
        } else {
            curl_setopt($ch, CURLOPT_CAINFO, $caPathOrFile);
        }
        $response = curl_exec($ch);
        if ($response === false) {
            echo json_encode(curl_error($ch));
        }
        curl_close($ch);
        return $response;
    }
}
