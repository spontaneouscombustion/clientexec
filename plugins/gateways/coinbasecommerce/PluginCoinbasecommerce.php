<?php

require_once 'modules/admin/models/GatewayPlugin.php';

class PluginCoinbasecommerce extends GatewayPlugin
{
    public function getVariables()
    {
        $variables = array(
            lang("Plugin Name") => array(
                "type"          => "hidden",
                "description"   => "",
                "value"         => "Coinbase Commerce"
            ),
            lang("API Key") => array(
                "type"          => "password",
                "description"   => "Generate an API key from here: https://commerce.coinbase.com/dashboard/settings",
                "value"         => ""
            ),
            lang("Webhook Shared Secret") => array(
                "type"          => "password",
                "description"   => "Get your Webhook shared secret from the Webhook subscriptions here: https://commerce.coinbase.com/dashboard/settings <br>Make sure you add the endpoint for your website into your webook subscriptions.",
                "value"         => ""
            ),
            lang("Signup Name") => array (
                "type"          => "text",
                "description"   => lang("Select the name to display in the signup process for this payment type. Example: eCheck or Credit Card."),
                "value"         => "Coinbase Commerce"
            )
        );
        return $variables;
    }

    public function singlepayment($params)
    {
        // Coinbase Commerce Specific Settings
        $ccUrl = "https://api.commerce.coinbase.com/charges";
        $ccPricingType = "fixed_price";
        $ccApiVersion = "2018-03-22";

        $invoiceId = $params['invoiceNumber'];
        $description = $params['invoiceDescription'];
        $amount = sprintf("%01.2f", round($params["invoiceTotal"], 2));
        $systemUrl = $params['companyURL'];
        $firstname = $params['userFirstName'];
        $lastname = $params['userLastName'];
        $email = $params['userEmail'];
        $returnUrl = CE_Lib::getSoftwareURL() . "/index.php?fuse=billing&paid=1&controller=invoice&view=invoice&id=" . $invoiceId;

        $invoiceviewURLCancel = $params['invoiceviewURLCancel'];
        $apiKey = $params['plugin_coinbasecommerce_API Key'];
        $plugin_coinbasecommerce_WebhookSharedSecret = $params['plugin_coinbasecommerce_Webhook Shared Secret'];
        $currencyCode = $params['currencytype'];
        $companyName = $params['companyName'];

        // Compiled Post from Variables
        $postfields = array();
        $postfields['name'] = $description;
        $postfields['description'] = "Invoice - #" . $invoiceId;
        $postfields['local_price'] = array('amount' => $amount, 'currency' => $currencyCode);
        $postfields['pricing_type'] = $ccPricingType;
        $postfields['metadata'] = array('customer_name' => $firstname . " " . $lastname, 'customer_email' => $email, 'invoice_id' => $invoiceId);
        $postfields['redirect_url'] = $returnUrl;

        // Setup request to send json via POST.
        $payload = json_encode($postfields, JSON_UNESCAPED_SLASHES);

        // Contact Coinbase Commerce and get URL data
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $ccUrl);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $caPathOrFile = \Composer\CaBundle\CaBundle::getSystemCaRootBundlePath();
        if (is_dir($caPathOrFile)) {
            curl_setopt($ch, CURLOPT_CAPATH, $caPathOrFile);
        } else {
            curl_setopt($ch, CURLOPT_CAINFO, $caPathOrFile);
        }

        $headers = [
            "Content-Type: application/json",
            "X-CC-Api-Key: $apiKey",
            "X-CC-Version: $ccApiVersion"
        ];

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $server_output = curl_exec($ch);
        curl_close($ch);

        // Convert response to PHP array and print button
        $payment_url = json_decode($server_output, true);

        header("location: " . $payment_url['data']['hosted_url']);
        exit;
    }

    public function credit($params)
    {
    }
}
