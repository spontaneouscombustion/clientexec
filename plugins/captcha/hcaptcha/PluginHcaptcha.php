<?php

class PluginHcaptcha extends CaptchaPlugin
{
    public function __construct($user)
    {
        parent::__construct($user);
    }

    public function getVariables()
    {
        $variables = [
            lang('Plugin Name') => [
                'type' => 'hidden',
                'description' => 'Used by CE to show plugin - must match how you call the action function names',
                'value' => 'hCaptcha'
            ],
            lang('Description') => [
                'type' => 'hidden',
                'description' => lang('Description viewable by admin in server settings'),
                'value' => lang('hCaptcha Integration')
            ],
            lang('Site Key') => [
                'type' => 'text',
                'description' => lang('hCaptcha Site Key'),
                'value' => '',
                'encryptable' => true
            ],
            lang('Secret Key') => [
                'type' => 'text',
                'description' => lang('hCaptcha Secret Key'),
                'value' => '',
                'encryptable' => true
            ],
        ];

        return $variables;
    }


    public function verify($request)
    {
        $data = [
            'secret' => $this->getVariable('Secret Key'),
            'response' => $request['h-captcha-response'],
            'remoteip' => CE_Lib::getRemoteAddr()
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://hcaptcha.com/siteverify');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $caPathOrFile = \Composer\CaBundle\CaBundle::getSystemCaRootBundlePath();
        if (is_dir($caPathOrFile)) {
            curl_setopt($ch, CURLOPT_CAPATH, $caPathOrFile);
        } else {
            curl_setopt($ch, CURLOPT_CAINFO, $caPathOrFile);
        }
        $response = curl_exec($ch);

        $responseData = json_decode($response);
        if ($responseData->success) {
            return true;
        }
        return false;
    }

    public function view()
    {
        $this->view->captchaSiteKey = $this->getVariable('Site Key');
        return $this->view->render('view.phtml');
    }
}
