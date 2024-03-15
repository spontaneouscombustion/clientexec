<?php
require_once 'modules/admin/models/GatewayPlugin.php';
class Pluginshurjopay extends GatewayPlugin
{
    function getVariables()
    {
        $variables = array(
            lang("Plugin Name") => array(
                "type"          => "hidden",
                "description"   => "",
                "value"         => "ShurjoPay"
            ),
            lang('Signup Name') => array(
                'type'        => 'text',
                'description' => lang('Select the name to display in the signup process for this payment type. Example: eCheck or Credit Card.'),
                'value'       => 'Shurjopay'
            ),
            lang("API Username") => array(
                "type"          => "text",
                "description"   => "Enter you API Username Here",
                "value"         => ""
            ),
            lang("API Password") => array(
                "type"          => "text",
                "description"   => "Enter you API Password Here",
                "value"         => ""
            ),
            lang("Transaction Prefix") => array(
                "type"          => "text",
                "description"   => "Enter you Transaction Prefix Here",
                "value"         => ""
            ),
            lang("Test Mode") => array(
                "type"          => "yesno",
                "description"   => "Enable Test Mode",
                "value"         => ""
            ),
        );
        return $variables;
    }
    function singlepayment($params)
    {
        $query = "SELECT * FROM currency WHERE abrv = '" . $params['userCurrency'] . "'";
        $result = $this->db->query($query);
        $row = $result->fetch();
        $prefix = $row['symbol'];

        $invoiceId = $params['invoiceNumber'];
        $description = $params['invoiceDescription'];
        $amount = sprintf("%01.2f", round($params["invoiceTotal"], 2));
        $systemUrl = $params['companyURL'];
        $firstname = $params['userFirstName'];
        $lastname = $params['userLastName'];
        $email = $params['userEmail'];

        $bar = "/";
        if (substr(CE_Lib::getSoftwareURL(), -1) == "/") {
            $bar = "";
        }
        $baseURL = CE_Lib::getSoftwareURL() . $bar;
        $CallbackURL = $baseURL . "plugins/gateways/shurjopay/callback.php";

        $currencyCode = $params['userCurrency'];

        $APIUsername = $params['plugin_shurjopay_API Username'];
        $APIUsername = $params['plugin_shurjopay_API Password'];
        $TransactionPrefix = $params['plugin_shurjopay_Transaction Prefix'];
        $TestMode = $params['plugin_shurjopay_Test Mode'];

        $cancel_url = $params['invoiceviewURLCancel'];

        $sanbox_url  = 'https://sandbox.shurjopayment.com/';
        $live_url    = 'https://engine.shurjopayment.com/';

        if ($TestMode == 1) {
            $payment_url = $sanbox_url;
        } else {
            $payment_url = $live_url;
        }

        $token_url = $payment_url . "api/get_token";
        $payment_url = $payment_url . "api/secret-pay";
        $verification_url = $payment_url . "api/verification/";

        $params['token_url'] = $token_url;

        $token = json_decode($this->getToken($params), true);

        $createpaybody = json_encode(
            array(
                // store information
                'token' => $token['token'],
                'store_id' => $token['store_id'],
                'prefix' => $prefix,
                'currency' => $currencyCode,
                'return_url' => $CallbackURL,
                'cancel_url' => $CallbackURL,
                'amount' => $amount,
                // Order information
                'order_id' => $invoiceId,
                'discsount_amount' => '0.00',
                'disc_percent' => '',
                // Customer information
                'client_ip' => $this->get_client_ip(),
                'customer_name' => $firstname . " " . $lastname,
                'customer_phone' => $params['userPhone'],
                'customer_email' => $params['userEmail'],
                'customer_address' => $params['userAddress'],
                'customer_city' => $params['userCity'],
                'customer_state' => $params['userState'],
                'customer_postcode' => $params['userZipcode'],
                'customer_country' => $params['userCountry'],
                'value1' => $invoiceId,
            ),
            true
        );

        $header = array(
            'Content-Type:application/json',
            'Authorization: Bearer ' . $token['token']
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $payment_url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $createpaybody);
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

        $urlData = json_decode($response);
        curl_close($ch);
        if (isset($urlData->checkout_url) && !empty($urlData->checkout_url)) {
            header('Location: ' . $urlData->checkout_url);
            exit;
        } else {
            $errors = "";
            foreach ($urlData as $values) {
                $errors .= $values[0] . "<br>";
            }
            return 'error';
        }
    }
    function getToken($params)
    {
        $token_url = $params['token_url'];
        $APIUsername = $params['plugin_shurjopay_API Username'];
        $APIPassword = $params['plugin_shurjopay_API Password'];

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
    function credit($params)
    {
    }
    function get_client_ip()
    {
        $ipaddress = '';
        if (getenv('HTTP_CLIENT_IP')) {
            $ipaddress = getenv('HTTP_CLIENT_IP');
        } elseif (getenv('HTTP_X_FORWARDED_FOR')) {
            $ipaddress = getenv('HTTP_X_FORWARDED_FOR');
        } elseif (getenv('HTTP_X_FORWARDED')) {
            $ipaddress = getenv('HTTP_X_FORWARDED');
        } elseif (getenv('HTTP_FORWARDED_FOR')) {
            $ipaddress = getenv('HTTP_FORWARDED_FOR');
        } elseif (getenv('HTTP_FORWARDED')) {
            $ipaddress = getenv('HTTP_FORWARDED');
        } elseif (getenv('REMOTE_ADDR')) {
            $ipaddress = getenv('REMOTE_ADDR');
        } else {
            $ipaddress = 'UNKNOWN';
        }
        return $ipaddress;
    }
}
