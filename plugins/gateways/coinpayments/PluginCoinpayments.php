<?php

require_once 'modules/billing/models/class.gateway.plugin.php';

class PluginCoinpayments extends GatewayPlugin
{
    public function getVariables()
    {
        $variables = array (
            lang("Plugin Name") => array (
                "type"          => "hidden",
                "description"   => lang("How CE sees this plugin (not to be confused with the Signup Name)"),
                "value"         => lang("CoinPayments")
            ),
            lang("Merchant ID") => array (
                "type"          => "text",
                "description"   => lang("Enter your Merchant ID from your <a target='_blank' href='https://www.coinpayments.net/index.php?ref=219a5b5f2eacd21f82e48d267f5bd7dc'>coinpayments.net</a> account"),
                "value"         => ""
            ),
            lang("IPN Secert") => array (
                "type"          => "text",
                "description"   => lang("Enter your IPN Secret from your <a target='_blank' href='https://www.coinpayments.net/index.php?ref=219a5b5f2eacd21f82e48d267f5bd7dc'>coinpayments.net</a> account"),
                "value"         => ""
            ),
            lang("Signup Name") => array (
                "type"          => "text",
                "description"   => lang("Select the name to display in the signup process for this payment type. Example: eCheck or Credit Card."),
                "value"         => "CoinPayments"
            )

        );
        return $variables;
    }

    public function credit($params)
    {
    }

    public function singlepayment($params, $test = false)
    {
        if ($params['isSignup']==1) {
            if ($this->settings->get('Signup Completion URL') != '') {
                $returnURL = $this->settings->get('Signup Completion URL'). '?success=1';
                $returnURLCancel = $this->settings->get('Signup Completion URL');
            } else {
                $returnURL = $params["clientExecURL"]."/order.php?step=complete&pass=1";
                $returnURLCancel = $params["clientExecURL"]."/order.php?step=3";
            }
        } else {
            $returnURL = $params["invoiceviewURLSuccess"];
            $returnURLCancel = $params["invoiceviewURLCancel"];
        }
        $data = [
            'cmd' => '_pay',
            'merchant' => $this->settings->get('plugin_coinpayments_Merchant ID'),
            'reset' => '1',
            'invoice' => $params['invoiceNumber'],
            'item_name' => $params['invoiceDescription'],
            'amountf' => $params['invoiceTotal'],
            'currency' => $params['userCurrency'],
            'email' => $params['userEmail'],
            'first_name' => $params['userFirstName'],
            'last_name' => $params['userLastName'],
            'want_shipping' => '',
            'ipn_url' => $params['clientExecURL'] . '/plugins/gateways/coinpayments/callback.php',
            'success_url' => $returnURL,
            'cancel_url' =>$returnURLCancel
        ];

        $html = '<form id="frmCoinPayments" action="https://www.coinpayments.net/index.php" method="POST">';
        foreach ($data as $name => $value) {
            $html .= '<input type="hidden" name="' . $name . '" value="' . htmlspecialchars($value) . '" />';
        }
        $html .= '</form>';
        $html .= "<script>document.forms['frmCoinPayments'].submit();</script>";
        echo $html;
        exit;
    }
}
