<?php

class PluginRecaptcha extends CaptchaPlugin
{
    private $globalSiteKey = '6LcFBRAUAAAAAEldd6qeKfixg2HTEw4n7tUb0AAH';
    private $globalSecretKey = '6LcFBRAUAAAAAEfj_K9tzddySFYkJjqEPqedfM5C';


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
                'value' => 'ReCaptcha'
            ],
            lang('Description') => [
                'type' => 'hidden',
                'description' => lang('Description viewable by admin in server settings'),
                'value' => lang('ReCaptcha v2 Integration')
            ],
            lang('Site Key') => [
                'type' => 'text',
                'description' => lang('ReCaptcha Site Key'),
                'value' => '',
                'encryptable' => true
            ],
            lang('Secret Key') => [
                'type' => 'text',
                'description' => lang('ReCaptcha Secret Key'),
                'value' => '',
                'encryptable' => true
            ],
        ];

        return $variables;
    }


    public function verify($request)
    {
        require_once 'library/CE/CurlPostCE.php';
        $secretKey = $this->getVariable('Secret Key');
        if ($secretKey == '') {
            $secretKey = $this->globalSecretKey;
        }

        $recaptcha = new \ReCaptcha\ReCaptcha($secretKey, new \ReCaptcha\RequestMethod\CurlPostCE());
        $resp = $recaptcha->verify($request['g-recaptcha-response'], CE_Lib::getRemoteAddr());

        if ($resp->isSuccess()) {
            return true;
        }
        return false;
    }

    public function view()
    {
        $this->view->captchaSiteKey = $this->getVariable('Site Key');
        if ($this->view->captchaSiteKey == '') {
            $this->view->captchaSiteKey = $this->globalSiteKey;
        }
        return $this->view->render('view.phtml');
    }
}
