<?php

class PluginCurrencyconverter extends ServicePlugin
{
    public $hasPendingItems = false;
    protected $featureSet = 'products';

    private $url = 'https://www.ecb.europa.eu/stats/eurofxref/eurofxref-daily.xml';

    public function getVariables()
    {
        $variables = array(
            lang('Plugin Name') => array(
                'type'        => 'hidden',
                'description' => '',
                'value'       => 'Automatic Currency Rate Converter',
            ),
            lang('Enabled') => array(
                'type'        => 'yesno',
                'description' => 'When enabled, this service will automatically update the conversion rate of currencies .',
                'value'       => '0',
            ),
            lang('Run schedule - Minute') => array(
                'type'        => 'text',
                'description' => lang('Enter number, range, list or steps'),
                'value'       => '0',
                'helpid'      => '8',
            ),
            lang('Run schedule - Hour') => array(
                'type'        => 'text',
                'description' => lang('Enter number, range, list or steps'),
                'value'       => '0',
            ),
            lang('Run schedule - Day') => array(
                'type'        => 'text',
                'description' => lang('Enter number, range, list or steps'),
                'value'       => '*',
            ),
            lang('Run schedule - Month') => array(
                'type'        => 'text',
                'description' => lang('Enter number, range, list or steps'),
                'value'       => '*',
            ),
            lang('Run schedule - Day of the week') => array(
                'type'        => 'text',
                'description' => lang('Enter number in range 0-6 (0 is Sunday) or a 3 letter shortcut (e.g. sun)'),
                'value'       => '*',
            ),
        );

        return $variables;
    }

    public function execute()
    {
        $currencyGateway = new CurrencyGateway();
        $xml = simplexml_load_string($this->getXML());

        $currencies = [];
        $currencies['EUR'] = 1;
        foreach ($xml->Cube->Cube->Cube as $cube) {
            $code = $cube->attributes()->currency;
            $rate = $cube->attributes()->rate;
            $currencies[(string)$code] = (float)$rate;
        }
        $defaultCurrency = $this->settings->get('Default Currency');
        $baseRate = isset($currencies[$defaultCurrency]) ? $currencies[$defaultCurrency] : "";

        $response = CE_Lib::trigger(
            'Service-CurrencyRateUpdate',
            $this,
            [
                'currencies' => $currencies
            ]
        );

        foreach ($response as $currencyCode => $rate) {
            $currencies[$currencyCode] = $rate;
        }

        foreach ($currencies as $code => $rate) {
            if ($code == $defaultCurrency) {
                // do not update default currency
                continue;
            }

            if ($rate) {
                $newRateRatio = $baseRate / $rate;
                if ($newRateRatio) {
                    $newRate = round(1 / $newRateRatio, 5);
                    $currencies[$code] = $newRate;
                    $currencyGateway->updateCurrencyRate($code, $newRate);
                }
            }
        }
        $currencyGateway->updateCurrencyRate($defaultCurrency, 1);
    }

    private function getXML()
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        $caPathOrFile = \Composer\CaBundle\CaBundle::getSystemCaRootBundlePath();
        if (is_dir($caPathOrFile)) {
            curl_setopt($ch, CURLOPT_CAPATH, $caPathOrFile);
        } else {
            curl_setopt($ch, CURLOPT_CAINFO, $caPathOrFile);
        }
        $response = curl_exec($ch);
        if (curl_error($ch)) {
            throw new CE_Exception(curl_error($ch));
        }

        curl_close($ch);
        return $response;
    }

    public function pendingItems()
    {
    }

    public function output()
    {
    }

    public function dashboard()
    {
    }
}
