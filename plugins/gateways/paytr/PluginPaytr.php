<?php

require_once 'modules/billing/models/class.gateway.plugin.php';

class PluginPaytr extends GatewayPlugin
{
    public function getVariables()
    {
        $variables = [
            lang('Plugin Name') => [
                'type' => 'hidden',
                'description' => lang('How CE sees this plugin ( not to be confused with the Signup Name )'),
                'value' => 'PayTR'
            ],
            lang('Merchant ID') => [
                'type' => 'text',
                'description' => lang('Enter your Merchant ID.'),
                'value' => ''
            ],
            lang('Merchant Key') => [
                'type' => 'text',
                'description' => lang('Enter your Merchant Key.'),
                'value' => ''
            ],
            lang('Merchant Salt') => [
                'type' => 'text',
                'description' => lang('Enter your Merchant Salt.'),
                'value' => ''
            ],
            lang('Test Mode?') => [
                'type' => 'yesno',
                'description' => lang('Select YES if you want to use PayTR\'s test mode.'),
                'value' => '0'
            ],
            lang('Invoice After Signup') => [
                'type' => 'yesno',
                'description' => lang('Select YES if you want an invoice sent to the client after signup is complete.'),
                'value' => '1'
            ],
            lang('Signup Name') => [
                'type' => 'text',
                'description' => lang('Select the name to display in the signup process for this payment type. Example: eCheck or Credit Card.'),
                'value' => 'PayTR'
            ],
            lang('Dummy Plugin') => [
                'type' => 'hidden',
                'description' => lang('1 = Only used to specify a billing type for a client. 0 = full fledged plugin requiring complete functions'),
                'value' => '0'
            ],
            lang('Auto Payment') => [
                'type' => 'hidden',
                'description' => lang('No description'),
                'value' => '0'
            ],
            lang('Form') => [
                'type' => 'hidden',
                'description' => lang('Has a form to be loaded?  1 = YES, 0 = NO'),
                'value' => '0'
            ]
        ];
        return $variables;
    }

    public function singlepayment($params, $test = false)
    {
        if ($this->getVariable('Merchant ID') == '' || $this->getVariable('Merchant Key') == '') {
            return '';
        }

        $invoice = new Invoice($params['invoiceNumber']);
        $user = new User($invoice->getUserID());
        $amount = sprintf("%01.2f", round($params['invoiceRawAmount'], 2));
        $totalAmount = $amount * 100;
        $oId = 'SP'.$params['invoiceNumber'].'CE'.time();
        $basket = base64_encode(json_encode([["Invoice #{$params['invoiceNumber']}", $amount, 1]]));

        $hash = [
            $this->getVariable('Merchant ID'),
            CE_Lib::getRemoteAddr(),
            $oId,
            $user->getEmail(),
            $totalAmount,
            $basket,
            '0',
            '0',
            $user->getCurrency(),
            $this->getVariable('Test Mode?')

        ];
        $hash = implode('', $hash);
        $token = base64_encode(
            hash_hmac(
                'sha256',
                $hash . $this->getVariable('Merchant Salt'),
                $this->getVariable('Merchant Key'),
                true
            )
        );

        if ($params['isSignup'] == 1) {
            if ($this->settings->get('Signup Completion URL') != '') {
                $returnURL = $this->settings->get('Signup Completion URL'). '?success=1';
                $returnURL_Cancel = $this->settings->get('Signup Completion URL');
            } else {
                $returnURL = $params["clientExecURL"]."/order.php?step=complete&pass=1";
                $returnURL_Cancel = $params["clientExecURL"]."/order.php?step=3";
            }
        } else {
            $clientExecURL = CE_Lib::getSoftwareURL();
            $returnURL = $clientExecURL . '/index.php?fuse=billing&paid=1&controller=invoice&view=invoice&id=' . $params['invoiceNumber'];
            $returnURL_Cancel = $clientExecURL . '/index.php?fuse=billing&cancel=1&controller=invoice&view=invoice&id=' . $params['invoiceNumber'];
        }

        $post = [
            'merchant_id' => $this->getVariable('Merchant ID'),
            'user_ip' => CE_Lib::getRemoteAddr(),
            'merchant_oid' => $oId,
            'email' => $user->getEmail(),
            'payment_amount' => $totalAmount,
            'paytr_token' => $token,
            'user_basket' => $basket,
            'debug_on' => 0,
            'no_installment' => 0,
            'max_installment' => 0,
            'user_name' => $user->getFirstName() . ' ' . $user->getLastName(),
            'user_address' => $user->getAddress() . ' ' . $user->getCity() . ' ' . $user->getState() . ' ' . $user->getZipCode() . ' ' . $user->getCountry(),
            'user_phone' => $user->getPhone(),
            'merchant_ok_url' => $returnURL,
            'merchant_fail_url' => $returnURL_Cancel,
            'timeout_limit' => 30,
            'currency' => $user->getCurrency(),
            'test_mode' => $this->getVariable('Test Mode?'),
            'lang' => 'en',
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://www.paytr.com/odeme/api/get-token");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1) ;
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        $caPathOrFile = \Composer\CaBundle\CaBundle::getSystemCaRootBundlePath();
        if (is_dir($caPathOrFile)) {
            curl_setopt($ch, CURLOPT_CAPATH, $caPathOrFile);
        } else {
            curl_setopt($ch, CURLOPT_CAINFO, $caPathOrFile);
        }

        $result = curl_exec($ch);

        if (curl_errno($ch)) {
            throw new CE_Exception('PayTR connection error: ' . curl_error($ch));
        }
        curl_close($ch);

        $result = json_decode($result, 1);
        if ($result['status'] == 'success') {
            $token = $result['token'];
        } else {
            CE_Lib::log(4, $result);
            throw new CE_Exception('PayTR error: '. $result['reason']);
        }

        $strRet = "<html>\n";
        $strRet .= "<head></head>\n";
        $strRet .= "<body>\n";
        $strRet .= "<form name='frmPaytr' action=\"plugins/gateways/paytr/iframe.php\" method=\"POST\">\n";
        $strRet .= "<input type=hidden name=\"token\" value=\"".$token."\">\n";
        $strRet .= "<script language=\"JavaScript\">\n";
        $strRet .= "document.forms['frmPaytr'].submit();\n";
        $strRet .= "</script>\n";
        $strRet .= "</form>\n";
        $strRet .= "</body>\n</html>\n";

        echo $strRet;
        die();
    }

    public function credit($params)
    {
        $merchantId = $params['plugin_paytr_Merchant ID'];
        $merchantKey = $params['plugin_paytr_Merchant Key'];
        $merchantSalt = $params['plugin_paytr_Merchant Salt'];
        $oId = $params['invoiceRefundTransactionId'];
        $amount = $params['invoiceTotal'];
        $token = base64_encode(
            hash_hmac(
                'sha256',
                $merchantId.$oId.$amount.$merchantSalt,
                $merchantKey,
                true
            )
        );

        $postVals =  [
            'merchant_id' => $merchantId,
            'merchant_oid' => $oId,
            'return_amount' => $amount,
            'paytr_token' => $token
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://www.paytr.com/odeme/iade");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1) ;
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postVals);
        curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 90);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 90);
        $caPathOrFile = \Composer\CaBundle\CaBundle::getSystemCaRootBundlePath();
        if (is_dir($caPathOrFile)) {
            curl_setopt($ch, CURLOPT_CAPATH, $caPathOrFile);
        } else {
            curl_setopt($ch, CURLOPT_CAINFO, $caPathOrFile);
        }

        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            throw new CE_Exception(curl_error($ch));
        }
        curl_close($ch);
        $result = json_decode($result, 1);

        $cPlugin = new Plugin($params['invoiceNumber'], 'paytr', $this->user);
        $cPlugin->setAmount($params['invoiceTotal']);
        $cPlugin->setAction('refund');

        if ($result['status'] == 'success') {
            $cPlugin->PaymentAccepted(
                $amount,
                "PayTR refund of {$amount} was successfully processed.",
                $result['merchant_oid']
            );
            return array('AMOUNT' => $amount);
        } else {
            $cPlugin->PaymentRejected($result['err_no']." - ".$result['err_msg']);
            throw new CE_Exception($result['err_no']." - ".$result['err_msg']);
        }
    }
}
