<?php

require_once 'modules/admin/models/PluginCallback.php';
require_once 'modules/billing/models/class.gateway.plugin.php';


use Alipay\EasySDK\Kernel\Factory;
use Alipay\EasySDK\Kernel\Config;

class PluginAlipayCallback extends PluginCallback
{
    private $host = "";
    private $sandbox = "0";

    public function loadVars()
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
        $options->merchantPrivateKey = $this->settings->get('plugin_alipay_App Private');
        $options->alipayPublicKey = $this->settings->get('plugin_alipay_Public Key');
        $options->notifyUrl = "{$this->host}/plugins/gateways/alipay/callback.php";
        return $options;
    }

    public function loadPost()
    {
        $Info = array();
        $Info["ID"] = $_POST["out_trade_no"];
        $Info["Amount"] = $_POST["invoice_amount"];
        $Info["Status"] = $_POST["trade_status"]; // "TRADE_SUCCESS"
      // $TradeNo = $_POST["trade_no"];

        return $Info;
    }

    public function processCallback()
    {
        $config = $this->loadVars();
        Factory::setOptions($config);
        $result = Factory::payment()->common()->verifyNotify($_POST);

        if ($result && $_POST["trade_status"] === "TRADE_SUCCESS") {
            $InvoiceInfo = $this->loadPost();
            $cPlugin = new Plugin($InvoiceInfo["ID"], $_GET['plugin'], $this->user);
            $cPlugin->m_TransactionID = $InvoiceInfo["ID"];
            $cPlugin->setAmount($InvoiceInfo["Amount"]);
            $cPlugin->setAction('charge');
            $transaction = "{$_GET['plugin']} Payment of {$InvoiceInfo["Amount"]} was accepted";
            $cPlugin->PaymentAccepted($InvoiceInfo["Amount"], $transaction, $InvoiceInfo["ID"]);
        } else {
            $transaction = 'Invalid Transaction';
            CE_Lib::log(4, $transaction . print_r($_POST, true));
        }
    }
}
