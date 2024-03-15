<?php

require_once 'modules/admin/models/GatewayPlugin.php';
require_once 'modules/billing/models/Invoice.php';

use Alipay\EasySDK\Kernel\Factory;
use Alipay\EasySDK\Kernel\Config;

/**
 * @package Plugins
 */
class PluginAlipay extends GatewayPlugin
{

    private $host = "";
    private $sandbox = "0";

    public function getVariables()
    {
        $variables = array(
            lang('Plugin Name') => array(
                'type'        => 'hidden',
                'description' => lang('How CE sees this plugin (not to be confused with the Signup Name)'),
                'value'       => lang('Alipay')
            ),
            lang('Signup Name') => array(
                'type'        => 'text',
                'description' => lang('用户支付时看到的付款方式.'),
                'value'       => '支付宝（AliPay）'
            ),
            lang('Alipay Sandbox') => array(
                'type'        => 'yesno',
                'description' => lang('沙箱模式'),
                'value'       => '0'
            ),
            lang('Sign Type') => array(
                'type'        => 'hidden',
                'description' => lang('签名类型 - RSA|RSA2'),
                'value'       => 'RSA2'
            ),
            lang('App ID') => array(
                'type'        => 'text',
                'description' => lang('应用 ID'),
                'value'       => ''
            ),
            lang('App Public') => array(
                'type'        => 'textarea',
                'description' => lang('应用公钥'),
                'value'       => ''
            ),
            lang('App Private') => array(
                'type'        => 'textarea',
                'description' => lang('应用私钥'),
                'value'       => ''
            ),
            lang('Public Key') => array(
                'type'        => 'textarea',
                'description' => lang('支付宝私钥'),
                'value'       => ''
            ),
        );

        return $variables;
    }

  // 读取配置项
    private function loadVars()
    {
      // 站点域名
        $this->host = $this->settings->get("Company URL");
        $this->sandbox = $this->settings->get('plugin_alipay_Alipay Sandbox');

        $options = new Config();
        $options->protocol = 'https';
        if ($this->sandbox == "1") {
            $options->gatewayHost = 'openapi.alipaydev.com';
        } else {
            $options->gatewayHost = 'openapi.alipay.com';
        }
        $options->signType = 'RSA2';
        $options->appId = $this->settings->get('plugin_alipay_App ID');
        $options->merchantPrivateKey = $this->settings->get('plugin_alipay_App Private');
        $options->alipayPublicKey = $this->settings->get('plugin_alipay_Public Key');
        $options->notifyUrl = "{$this->host}/plugins/gateways/alipay/callback.php";
        return $options;
    }


    public function credit($params)
    {
    }

    public function payPC($invoiceInfo)
    {
        $config = $this->loadVars();
        Factory::setOptions($config);

        $result = Factory::payment()->Page()->pay(
            $invoiceInfo["Name"],
            $invoiceInfo["ID"],
            number_format($invoiceInfo["Price"], 2),
            $invoiceInfo["Success"]
        );
        return $result->body;
    }


    public function singlePayment($params)
    {
        $ID = $params["invoiceNumber"];
        $Price = $params["invoiceRawAmount"];
        $Success = $params["invoiceviewURLSuccess"];
        $Description = $params["invoiceDescription"];

        $invoiceInfo = array("ID" => $ID, "Price" => $Price, "Name" => $Description, "Success" => $Success);

        echo $this->payPC($invoiceInfo);

        die();
    }

    public function getForm($params)
    {
        if ($params['from'] == 'paymentmethod') {
            return '';
        }

        if ($params['from'] == 'signup') {
            $fakeForm = '<a style="margin-left:0px;cursor:pointer;" class="app-btns primary customButton" onclick="cart.submit_form(' . $params['loggedIn'] . ');"  id="submitButton"></a>';
        } else {
            $fakeForm = '<button class="app-btns primary" id="submitButton">' . $this->user->lang('Pay Invoice') . '</button>';
        }

        return $fakeForm;
    }
}
