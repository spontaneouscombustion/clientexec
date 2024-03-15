<?php

require_once 'modules/admin/models/GatewayPlugin.php';
require_once 'modules/billing/models/class.gateway.plugin.php';
require_once 'plugins/gateways/pesapal/OAuth.php';

class PluginPesapal extends GatewayPlugin
{
    function getVariables()
    {
        $variables = array (
            lang('Plugin Name') => array (
                'type'        => 'hidden',
                'description' => lang('How CE sees this plugin (not to be confused with the Signup Name)'),
                'value'       => lang('Pesapal')
            ),
            lang('Consumer Key') => array (
                'type'        => 'password',
                'description' => lang('Please enter your pesapal consumer key.'),
                'value'       => ''
            ),
            lang('Consumer Secret') => array (
                'type'        => 'password',
                'description' => lang('Please enter your pesapal consumer secret.'),
                'value'       => ''
            ),
            lang('Test Mode') => array(
                'type'        => 'yesno',
                'description' => lang('Enable this to use the test API.'),
                'value'       => '0'
            ),

            lang('Invoice After Signup') => array (
                'type'        => 'yesno',
                'description' => lang('Select YES if you want an invoice sent to the client after signup is complete.'),
                'value'       => '1'
            ),
            lang('Signup Name') => array (
                'type'        => 'text',
                'description' => lang('Select the name to display in the signup process for this payment type. Example: eCheck or Credit Card.'),
                'value'       => 'Pay with Pesapal'
            ),
            lang('Dummy Plugin') => array (
                'type'        => 'hidden',
                'description' => lang('1 = Only used to specify a billing type for a client. 0 = full fledged plugin requiring complete functions'),
                'value'       => '0'
            ),
            lang('Auto Payment') => array (
                'type'        => 'hidden',
                'description' => lang('No description'),
                'value'       => '0'
            ),
            lang('Check CVV2') => array (
                'type'        => 'hidden',
                'description' => lang('Select YES if you want to accept CVV2 for this plugin.'),
                'value'       => '0'
            ),
            lang('Update Gateway') => array (
                'type'        => 'hidden',
                'description' => lang('1 = Create, update or remove Gateway client information through the function UpdateGateway when client choose to use this gateway, client profile is updated, client is deleted or client status is changed. 0 = Do nothing.'),
                'value'       => '0'
            )
        );

        return $variables;
    }

    function singlepayment($params)
    {
        return $this->autopayment($params);
    }

    function autopayment($params)
    {

        $cPlugin = new Plugin($params['invoiceNumber'], "pesapal", $this->user);
        $cPlugin->setAmount($params['invoiceTotal']);

        if (isset($params['refund']) && $params['refund']) {
            $isRefund = true;
            $cPlugin->setAction('refund');
        } else {
            $isRefund = false;
            $cPlugin->setAction('charge');
        }

        return $this->useForm($params);
    }



    function useForm($params)
    {
        $consumerKey = $params['plugin_pesapal_Consumer Key'];
        $consumerSecret = $params['plugin_pesapal_Consumer Secret'];
        $signatureMethod = new OAuthSignatureMethod_HMAC_SHA1();

        $iFrameLink = 'https://www.pesapal.com/API/PostPesapalDirectOrderV4';
        if ($params['plugin_pesapal_Test Mode'] == '1') {
            $iFrameLink = 'https://demo.pesapal.com/api/PostPesapalDirectOrderV4';
        }

        $amount = number_format($params['invoiceTotal'], 2);
        $desc = $params['invoiceDescription'];
        $type = 'MERCHANT';
        $reference = $params['invoiceNumber'];
        $firstName = $params['userFirstName'];
        $lastName = $params['userLastName'];
        $email = $params['userEmail'];
        $phone = $params['userPhone'];
        $currency = $params['userCurrency'];
        $callbackUrl = $params['invoiceviewURLSuccess'];

        $postXML = "<?xml version=\"1.0\" encoding=\"utf-8\"?><PesapalDirectOrderInfo xmlns:xsi=\"http://www.w3.org/2001/XMLSchemainstance\" xmlns:xsd=\"http://www.w3.org/2001/XMLSchema\" Amount=\"".$amount."\" Currency=\"".$currency."\" Description=\"".$desc."\" Type=\"".$type."\" Reference=\"".$reference."\" FirstName=\"".$firstName."\" LastName=\"".$lastName."\" Email=\"".$email."\" PhoneNumber=\"".$phone."\" xmlns=\"http://www.pesapal.com\" />";
        $postXML = htmlentities($postXML);

        $consumer = new OAuthConsumer($consumerKey, $consumerSecret);
        $iframeSrc = OAuthRequest::from_consumer_and_token($consumer, $token, 'GET', $iFrameLink);
        $iframeSrc->set_parameter('oauth_callback', $callbackUrl);
        $iframeSrc->set_parameter('pesapal_request_data', $postXML);
        $iframeSrc->sign_request($signatureMethod, $consumer, $token);

        echo <<<IFRAME
            <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
            <html xmlns="http://www.w3.org/1999/xhtml">
                <head>
                    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
                    <title>Pay with Pesapal</title>
                </head>
                <body>
                    <iframe src="$iframeSrc" width="100%" height="720px" scrolling="auto" frameBorder="0">
                        <p>Unable to load the payment page</p>
                    </iframe>
                </body>
            </html>
IFRAME;
        exit;
    }

    function credit($params)
    {
    }

    function ShowURL($params)
    {
    }
}
