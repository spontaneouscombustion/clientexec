<?php

class Admin_IndexpublicController extends CE_Controller_Action
{

    var $moduleName = "admin";

    /*
     * When calling a heartbeat function, the method name must match the name of the heartbeat function to be called.
     * If returning data, the return data from the heartbeat function must be in a single dimension array of key => value pairs. These will be returned
     * to the javascript callback function as object key.value pairs.
     *
     * DUE TO THE WAY DATA IS PASSED, YOU MUST INDEPENDENTLY SANITIZE ALL VARIABLES USING $this->checkVar();
     */
    protected function pulseAction()
    {
        $return = array();
        if (!isset($this->session->heartbeatroutes)) {
            $this->session->heartbeatroutes = array();
        }

        $beats = get_object_vars(json_decode($this->getParam('args')));

        foreach ($beats as $beat => $args) {
            $vars = array();

            //let's parse this beat into it's parts and cache it
            //so we don't have to do this for each pulse for each beat
            $cleanbeat = str_replace("index.php?", "", $beat);
            $cleanbeat = str_replace("&amp;", "&", $cleanbeat);

            parse_str($cleanbeat, $vars);
            //empty controller assumes index
            if (!isset($vars['controller'])) {
                $vars['controller'] = "index";
            }

            if (NE_PUBLIC) {
                //classname of controller we want to access
                $vars["controller"] = ucfirst($vars['controller'])."publicController";
            } else {
                $vars["controller"] = ucfirst($vars['controller'])."Controller";
            }

            //if we don't have an action or fuse let's continue
            if ((!isset($vars['action'])) || (!isset($vars['fuse']))) {
                CE_Lib::log(1, "Passing incomplete heartbeat:".$beat);
                $return[$beat] = null;
                continue;
            } else {
                //classname of controller we want to access
                $vars['classname'] = ucfirst($vars["fuse"])."_".$vars["controller"];

                //method to call
                $vars['method'] = $vars["action"]."Action";
            }

            include_once 'modules/admin/controllers/IndexController.php';
            if (!in_array(array(strtolower($vars['fuse']), strtolower($vars['controller']), strtolower($vars['action'])), Admin_IndexController::getPulseWhitelistedRequests())) {
                CE_Lib::securityEvent("*** Hack attempt? Invalid pulse fuse/controller/action ({$vars['fuse']}/{$vars['controller']}/{$vars['action']}) at index.php?fuse=admin&controller=indexpublic&action=pulse. Currently logged in user:".$this->user->getId().'('.$this->user->getFullName().')');
                exit;
            }

            //let's get a callback to send back to the heartbeat js
            include_once "modules/".$vars["fuse"]."/controllers/".$vars["controller"].".php";
            $actionclass = new $vars['classname']($this->getRequest(),$this->getResponse(), array("pulsing"=>true));

            $_GET = array_merge($_GET, get_object_vars($args));
            $_REQUEST = array_merge($_REQUEST, get_object_vars($args));

            if (is_callable(array($actionclass, $vars['method'])) && method_exists($actionclass, $vars['method'])) {
                $return[$beat] = call_user_func(array($actionclass, $vars['method']), true);
            } else {
                $return[$beat] = null;
            }
        }

        $this->send(array('returnArgs' => $return));
    }


    protected function logoutAction()
    {
        CE_Lib::trigger('Account-Logoff', $this->user, [
            'userid' => $this->user->getId(),
            'type' => 'client'
        ]);

        //session might have expired while customer was viewing page
        if ($this->user) {
            $this->user->setLoggedIn(0);
            $this->user->save();
        }

        $template = '';
        if (isset($this->session->template)) {
            $template = '?template='.$this->session->template;
        }

        if (DEBUG && (isset($this->session)) && (isset($this->session->actionAvgTimes))) {
            foreach ($this->session->actionAvgTimes as $key => $actionTime) {
                $add = 0;
                foreach ($actionTime as $time) {
                    $add += $time;
                }
                $avg = $add / count($actionTime);
                CE_Lib::debug("Action:".$key." Average:".sprintf('%01.4f', $avg));
            }
        }

        //Let's clear session
        CE_Lib::destroySession($this->user);

        // Only redirect if it's a regular logout
        if ($this->settings->get('Custom Logout URL') != '') {
            // Needs the http:// in order to redirect to other websites.
            if (strpos(mb_substr($this->settings->get('Custom Logout URL'), 0, 7), 'http://') === false
                && strpos(mb_substr($this->settings->get('Custom Logout URL'), 0, 8), 'https://') === false) {
                CE_Lib::redirectPage('http://'.$this->settings->get('Custom Logout URL'));
            }
            CE_Lib::RedirectPage($this->settings->get('Custom Logout URL'));
        }

        CE_Lib::redirectPage("index.php".$template);
    }

    /**
     * License check from public view
     * @return [type] [description]
     */
    protected function checklicenseAction()
    {
        include_once 'library/CE/LicenseDefender.php';
        $ld = new LicenseDefender();
        $ld->updateLicense("novalue");
        $ld->clearLicenseCache();
        CE_Lib::log(3, '************* LICENSE VERIFICATION REQUESTED ********* From: '.$_SERVER['REMOTE_ADDR']);
        CE_Lib::redirectPage('index.php?fuse=home&view=dashboard');
    }

    /**
     * Auto Login function
     */
    protected function autologinAction()
    {
        $this->disableLayout();

        if ($this->settings->get('Enable Auto Login') && $this->settings->get('CE-APIKEY') && !$this->settings->get('Login Disabled')) {
            $apiKey = sha1($this->settings->get('CE-APIKEY'));
            $email = $this->getParam('email', FILTER_SANITIZE_EMAIL);
            $timeStamp = $this->getParam('timestamp');
            $hash = $this->getParam('hash', FILTER_SANITIZE_STRING);
            $goto = $this->getParam('goto', FILTER_SANITIZE_STRING, '', false);

            if ($timeStamp < time() - 15 * 60 || time() < $timeStamp) {
                CE_Lib::redirectPermissionDenied($this->user->lang('The autologin link has expired'));
                exit;
            }

            if ($hash === sha1($apiKey . $email . $timeStamp)) {
                $userGateway = new UserGateway($this->user);
                $userId = $userGateway->SearchUserByEmail($email);
                if ($userId > 0) {
                    $user = new User($userId);

                    // Only let cancelled, inactive or active users login.
                    if (!in_array($user->getStatus(), StatusAliasGateway::getInstance($user)->getUserStatusIdsFor(array(USER_STATUS_PENDING, USER_STATUS_CANCELLED, USER_STATUS_INACTIVE, USER_STATUS_ACTIVE)))) {
                        $userGateway->logFailedLogin($user);
                            die();
                    }

                    if (isset($this->session->customerId)) {
                        unset($this->session->customerId);
                    }
                    $this->session->userId = $user->getId();
                    $this->session->groupId = $user->getGroupId();
                    $this->session->userAccess['userIsAdmin'] = false;
                    $this->session->userAccess['userIsSuperAdmin'] = false;
                    $this->session->userAccess['userIsCompanySuperAdmin'] = false;
                    $user->setLastLogin(date('Y-m-d H:i:s'));
                    $user->save();
                    $userGateway->logSuccessfulLogin($user);

                    if ($goto != '') {
                        CE_Lib::redirectPage($goto);
                    }
                    CE_Lib::redirectPage('index.php?fuse=home&view=dashboard');
                }
            }
        }
    }

    /**
     * Login action
     */
    protected function loginAction()
    {
        $nextURL = $this->getParam('redirct_url', FILTER_SANITIZE_STRING, 'index.php?fuse=home&view=dashboard');
        $loginURL = $this->getParam('redirct_url', FILTER_SANITIZE_STRING, "index.php?fuse=home&view=login");

        $captchaPlugin = $this->settings->get('Enabled Captcha Plugin');
        if ($this->settings->get('Show Captcha on Login Page') == 1 && $captchaPlugin != '' && $captchaPlugin != 'disabled') {
            $pluginGateway = new PluginGateway($this->user);
            $this->view->showCaptcha = true;

            $plugin = $pluginGateway->getPluginByName('captcha', $captchaPlugin);
            if (!$plugin->verify($_REQUEST)) {
                CE_Lib::addErrorMessage($this->user->lang('Failed Captcha'));
                CE_Lib::redirectPage($loginURL);
            }
        }

        $languages = CE_Lib::getEnabledLanguages();
        include_once 'modules/admin/models/Translations.php';
        $translations = new Translations();
        $languageKey = ucfirst(strtolower($this->user->getLanguage()));

        include_once 'modules/clients/models/UserGateway.php';
        $userGateway = new UserGateway();

        $configuration = Zend_Registry::get('configuration');
        if (!isset($configuration['modules']['newedge']['installedVersion'])
            || CE_Lib::compareVersions($configuration['modules']['newedge']['installedVersion'], $configuration['framework']['appVersion'], $configuration['framework']['appVersions']) > 0
        ) {
            CE_Lib::redirectPage($loginURL, $this->user->lang('Access disabled. Software upgrade in progress.'));
        }

        if (!isset($_POST['email'])
            || !isset($_POST['passed_password'])
            || $_POST['email'] == '' || $_POST['passed_password'] == ''
        ) {
            // invalid user, so just pass a new user, so we can still log IPs.
            $userGateway->logFailedLogin(new User());
            // LINE UNCOMMENTED BECAUSE WE HAVE A BUG WITH THIS.
            CE_Lib::addErrorMessage($this->user->lang('Incorrect Email and/or password')); // referred to in config.php
            CE_Lib::redirectPage($loginURL);
        }

        $genericUser = new User();
        if (!$user = $genericUser->getAuthedUser($userGateway, $_POST['email'], $_POST['passed_password'])) {
            $userGateway->logFailedLogin($genericUser);
            CE_Lib::addErrorMessage($this->user->lang('Incorrect email or password.&nbsp;&nbsp;If you do not have an account with us please register first.')); // refered to in config.php
            CE_Lib::redirectPage($loginURL);
        }

        //don't allow admin to login in public
        if ($user->isAdmin()) {
            return $this->_forward("login", "index", "admin");
        }

        // Only let cancelled, inactive or active users login.
        if (!in_array($user->getStatus(), StatusAliasGateway::getInstance($user)->getUserStatusIdsFor(array(USER_STATUS_PENDING, USER_STATUS_CANCELLED, USER_STATUS_INACTIVE, USER_STATUS_ACTIVE)))) {
            $userGateway->logFailedLogin($user);
            CE_Lib::addErrorMessage($this->user->lang('Your account is not active'));
            CE_Lib::redirectPage($loginURL);
        }

        if ($this->settings->get('Login Disabled')) {
            $userGateway->logFailedLogin($user);

            $LoginDisabledSystemMessageSettingValue = $this->settings->get('Login disabled system message');
            $LoginDisabledSystemMessageSettingId = $this->settings->getSettingIdForName('Login disabled system message');
            if (count($languages) > 1) {
                if ($LoginDisabledSystemMessageSettingId !== false) {
                    $LoginDisabledSystemMessageSettingValue = $translations->getValue(SETTING_VALUE, $LoginDisabledSystemMessageSettingId, $languageKey, $LoginDisabledSystemMessageSettingValue);
                }
            }

            CE_Lib::redirectPage($loginURL, $LoginDisabledSystemMessageSettingValue);
            exit;
        }

        if (isset($this->session->customerId)) {
            unset($this->session->customerId);
        }
        //$this->session = new Zend_Session_Namespace('admin');
        $this->session->userId = $user->getId();
        $this->session->groupId = $user->getGroupId();
        $this->session->userAccess['userIsAdmin'] = false;
        $this->session->userAccess['userIsSuperAdmin'] = false;
        $this->session->userAccess['userIsCompanySuperAdmin'] = false;
        $user->setLastLogin(date('Y-m-d H:i:s'));
        $user->save();

        // log event to say we've successfully logged in
        $userGateway->logSuccessfulLogin($user);

        CE_Lib::trigger('Account-Login', $this->user, [
            'userid' => $user->getId(),
            'type' => 'client'
        ]);

        if (isset($_REQUEST['rememberMe'])) {
            CE_Lib::setRememberMeCookie($user);
        }

        if (isset($this->session->return_ext_url)) {
            //we want to set user so that Shared Session has access to use information
            $this->_setUser();
            CE_Lib::redirectPage();
        } elseif (isset($this->session->redirectUserVoice)) {
            CE_Lib::redirectPage('index.php?fuse=admin&view=snapin&controller=snapins&plugin=uservoice&v=view');
        }

        //if we had a saved url attempt before they logged in take them there
        if (isset($this->session->nextURL)) {
            $nextURL = 'index.php?'.$this->session->nextURL;
            unset($this->session->nextURL);
        }
        CE_Lib::redirectPage($nextURL);
    }

    /**
     * getting plugin information for available payment processors for a customer
     * @return [type] [description]
     */
    protected function getpaymentplugindetailsAction()
    {

        include_once "modules/admin/models/PluginGateway.php";
        $plugingateway = new PluginGateway($this->user, $this->customer);

        $pluginid = $this->getParam('plugin', FILTER_SANITIZE_STRING);

        $data = $plugingateway->get_payment_plugin_data($pluginid);

        $this->send($data);
    }

    protected function rssannouncementsAction()
    {
        $this->disableLayout(false);
        $this->view->encoding = "utf-8";
        header("Content-Type: text/xml;charset={$this->view->encoding}");

        $this->view->xmlTag = '<?xml version="1.0" encoding="' . $this->view->encoding . '"?>';

        $this->view->title = $this->settings->get("Company Name").' '.$this->user->lang("Announcements");
        $this->view->link = CE_Lib::getSoftwareURL();
        $this->view->description = htmlspecialchars($this->settings->get('Title'));
        $this->view->generator = 'CLIENTEXEC ' . CE_Lib::getAppVersion();

        $this->view->announcements = array();
        $result = $this->db->query("SELECT * FROM announcement WHERE recipient = '0' AND publish='1' AND postdate <= NOW() ORDER BY postdate DESC");
        if ($result->getNumRows() > 0) {
            include_once 'library/CE/NE_MailGateway.php';
            $mailGateway = new NE_MailGateway();
            while ($row = $result->fetch()) {
                $announcement = array();
                $announcement['title'] = htmlspecialchars($row['title']);
                $announcement['link'] = CE_Lib::getSoftwareURL() ."/index.php?fuse=home&amp;view=announcement&amp;controller=announcements&amp;ann_id=" . $row['id'];

                if (!$this->user->isAdmin() && !$this->user->isAnonymous()) {
                    $row['excerpt'] = $mailGateway->replaceMailTags($row['excerpt'], $this->user);
                }

                $announcement['description'] = htmlspecialchars(stripslashes($row["excerpt"]));
                $announcement['date'] = date("r", strtotime($row["postdate"]));
                $announcement['guid'] = "tag:" . $this->settings->get('Company URL').":".$row["id"];
                $this->view->announcements[] = $announcement;
            }
        }
    }

    protected function viewpluginurlAction()
    {
        $this->disableLayout();
        if (!isset($_REQUEST['plugintoshow']) || !isset($this->user)) {
            return;
        }

        if (isset($this->customer)) {
            $CustomerID = $this->customer->getId();
        } else {
            $CustomerID = $this->user->getId();
        }

        include_once 'library/CE/NE_PluginCollection.php';
        $pluginCollection = new NE_PluginCollection('gateways', $this->user);
        echo $pluginCollection->callFunction($_REQUEST['plugintoshow'], 'ShowURL', array(
            'CustomerID' => $CustomerID,
            'returnURL'  => (mb_substr(CE_Lib::getSoftwareURL(), -1, 1) == "//" )? CE_Lib::getSoftwareURL().'index.php?fuse=clients&controller=userprofile&view=paymentmethod' : CE_Lib::getSoftwareURL().'/index.php?fuse=clients&controller=userprofile&view=paymentmethod'
        ));
    }
}
