<?php

require_once 'modules/admin/models/StatusAliasGateway.php';
require_once 'modules/clients/models/ObjectCustomFields.php';

/**
 * Home Module's Action Controller
 *
 * @category   Action
 * @package    Home
 * @author     Alberto Vasquez <alberto@clientexec.com>
 * @license    http://www.clientexec.com  ClientExec License Agreement
 * @link       http://www.clientexec.com
 */
class Home_IndexpublicController extends CE_Controller_Action
{
    public $moduleName = "home";


    protected function loginAction()
    {
        if (is_object($this->user) && ($this->user->getId() != 0)) {
            if ($this->user->isAdmin()) {
                CE_Lib::redirectPage(CE_Lib::getSoftwareURL() . NE_CONTROLLER_ADMIN_DIR . '/index.php');
            } else {
                CE_Lib::redirectPage('index.php?fuse=home&view=dashboard');
            }
        }

        $this->title = $this->user->lang('Login');
        $this->view->showCaptcha = false;

        $captchaPlugin = $this->settings->get('Enabled Captcha Plugin');
        if ($this->settings->get('Show Captcha on Login Page') == 1 && $captchaPlugin != '' && $captchaPlugin != 'disabled') {
            $pluginGateway = new PluginGateway($this->user);
            $this->view->showCaptcha = true;

            $plugin = $pluginGateway->getPluginByName('captcha', $captchaPlugin);
            $plugin->setTemplate($this->view);
            $this->view->captchaHtml = $plugin->view();
        }

        //do not know what I really need to do here just yet
        if (isset($_REQUEST['needstologin'])) {
            $this->view->showloginneededwarning = true;
        } else {
            $this->view->showloginneededwarning = false;
        }

        if (@$_GET['return']) {
            $this->session->redirectUserVoice = $_GET['return'];
        } elseif (@$_GET['return_ext_url']) {
            $this->session->return_ext_url = $_GET['return_ext_url'];
        }

        $this->view->allowRegistration = $this->settings->get('Allow Registration');

        $this->view->redirectURL = '';
        if (isset($this->session->cartContents)) {
            $cartItems = unserialize(base64_decode($this->session->cartContents));
            if (is_array($cartItems)) {
                $this->view->redirectURL = 'order.php?step=3';
            }
        }
    }

    protected function invalidlicenseAction()
    {
        $licenseDefender = new LicenseDefender();
        $licenseDefender->resetLicenseIfNecessary();
        if ($licenseDefender->validateLicense()) {
            CE_Lib::redirectPage("index.php");
            return;
        }

        $this->title = $this->user->lang('Invalid License');
        $this->cssPages = array("templates/default/views/home/indexpublic/invalidlicense.css");
        $this->view->gHideStyle = true;
    }

    protected function forgotpasswordAction()
    {
        $this->title = $this->user->lang('Reset Password');
        $this->view->showCaptcha = false;

        if (is_object($this->user) && ($this->user->getId() != 0)) {
            CE_Lib::redirectPage('index.php?fuse=home&view=dashboard');
        }

        $captchaPlugin = $this->settings->get('Enabled Captcha Plugin');
        if ($this->settings->get('Show Captcha on Forgot Password Page') == 1 && $captchaPlugin != '' && $captchaPlugin != 'disabled') {
            $pluginGateway = new PluginGateway($this->user);
            $this->view->showCaptcha = true;

            $plugin = $pluginGateway->getPluginByName('captcha', $captchaPlugin);
            $plugin->setTemplate($this->view);
            $this->view->captchaHtml = $plugin->view();
        }
        $this->view->success = $this->getParam('success', null, 0, false);
    }

    protected function registerAction()
    {
        if (!$this->settings->get('Allow Registration')) {
            CE_Lib::redirectPermissionDenied($this->user->lang('Account registration is disabled'));
        }

        if (is_object($this->user) && ($this->user->getId() != 0)) {
            CE_Lib::redirectPage('index.php?fuse=home&view=dashboard');
        }

        $this->title = $this->user->lang('Create Account');
        $this->view->showCaptcha = false;

        $captchaPlugin = $this->settings->get('Enabled Captcha Plugin');
        if ($this->settings->get('Show Captcha on Register Page') == 1 && $captchaPlugin != '' && $captchaPlugin != 'disabled') {
            $pluginGateway = new PluginGateway($this->user);
            $this->view->showCaptcha = true;

            $plugin = $pluginGateway->getPluginByName('captcha', $captchaPlugin);
            $plugin->setTemplate($this->view);
            $this->view->captchaHtml = $plugin->view();
        }

        // Handle T&C's
        if (@$this->settings->get('Show Terms and Conditions') == 1) {
             // Site URL for T&Cs
            if (@$this->settings->get('Terms and Conditions URL')) {
                 $this->view->termsConditions = '-1';
                 $this->view->termsConditionsUrl = $this->settings->get('Terms and Conditions URL');
            } else {
                 $this->view->termsConditions = 1;

                 $termsAndConditions = $this->settings->get('Terms and Conditions');
                 $termsAndConditions = str_replace('&quot;', '"', $termsAndConditions);
                 $termsAndConditions = str_replace('&#039;', '\'', $termsAndConditions);
                 $this->view->termsConditionsText = $termsAndConditions;
            }
        }

        $breakPoints = [
            // typeORGANIZATION,
            // typePHONENUMBER,
            // typeEMAIL,
            // typeCOUNTRY,
            // typeVATNUMBER
        ];

        $preBreakPoints = [
           // TYPEPASSWORD
        ];

        require_once 'modules/admin/models/ActiveOrderGateway.php';
        $activeOrderGateway = new ActiveOrderGateway($this->user);

        $customFields = $activeOrderGateway->getCustomFields(
            'profile',
            true,
            $this->session->oldFields,
            false,
            null,
            true
        );

        $this->view->stateVarId = $customFields['state_var_id'];
        $this->view->countryVarId = $customFields['country_var_id'];
        $this->view->vatVarId = $customFields['vat_var_id'];
        $this->view->customFieldValues = [];
        $this->view->selectCustomFields = [];
        $arrCustomFields = [];

        $this->view->customFields = [];

        foreach ($customFields['customFields'] as $key => $field) {
            // we do not show password on edit profile.
            if (in_array($field['fieldtype'], array(TYPEPASSWORD))) {
                continue;
            }

            if (in_array($field['fieldtype'], $preBreakPoints)) {
                $fieldBreak = [
                    'fieldtype' => 'break'
                ];
                $this->view->customFields[] = $fieldBreak;
            }

            $this->view->customFields[] = $field;
            if (in_array($field['fieldtype'], $breakPoints)) {
                $field = [
                    'fieldtype' => 'break'
                ];
                $this->view->customFields[] = $field;
            }
        }
    }

    protected function resetpasswordAction()
    {

        $captchaPlugin = $this->settings->get('Enabled Captcha Plugin');
        if ($this->settings->get('Show Captcha on Forgot Password Page') == 1 && $captchaPlugin != '' && $captchaPlugin != 'disabled') {
            $pluginGateway = new PluginGateway($this->user);

            $plugin = $pluginGateway->getPluginByName('captcha', $captchaPlugin);
            if (!$plugin->verify($_REQUEST)) {
                CE_Lib::addErrorMessage($this->user->lang('Failed Captcha'));
                CE_Lib::redirectPage('index.php?fuse=home&view=forgotpassword');
                exit;
            }
        }

        $emailAddress = htmlspecialchars($this->getParam('email', FILTER_SANITIZE_STRING), ENT_QUOTES);

        try {
            $emailSent = $this->sendConfirmationEmail($emailAddress, 'reset');
            if (!$emailSent) {
                CE_Lib::log(4, "Unable to reset password because the provided email address (%s) is not a registered one.", $emailAddress);
            }
            CE_Lib::redirectPage('index.php?fuse=home&view=forgotpassword&success=1');
            exit;
        } catch (Exception $ex) {
            CE_Lib::log(4, $ex->getMessage());
            CE_Lib::addErrorMessage($ex->getMessage());
        }
        CE_Lib::redirectPage('index.php?fuse=home&view=forgotpassword');
    }

    /**
     * Sends confirmation email when requesting password change
     *
     * @param string $email           email to send confirmation text to
     * @param string $type            type of email to send: 'reset' or 'activate'
     *
     * @return bool
     */
    protected function sendConfirmationEmail($email, $type = 'reset')
    {
        include_once 'modules/admin/models/StatusAliasGateway.php';
        include_once 'library/CE/NE_MailGateway.php';

        if ($email == '') {
            return false;
        }

        include_once 'modules/support/models/AutoresponderTemplateGateway.php';
        $templategateway = new AutoresponderTemplateGateway();

        if ($type == 'reset') {
            $template = $templategateway->getEmailTemplateByName("Forgot Password Template");
        } else {
            $template = $templategateway->getEmailTemplateByName("Activate Account Template");
        }

        $mailGateway = new NE_MailGateway();

        $bolReturnValue = false;
        $tClientID = 0;

        $ip = CE_Lib::getRemoteAddr();
        $host = @gethostbyaddr($ip);
        if ($host) {
            $host = "($host)";
        }

        $query = 'SELECT userid FROM user_customuserfields uc LEFT JOIN customuserfields c ON uc.customid=c.id WHERE uc.value=? AND c.type=?';
        $result = $this->db->query($query, $email, typeEMAIL);

        $fromEmail = $this->settings->get("Support E-mail");
        if ($fromEmail == '') {
            throw new Exception('Support Email is not defined');
        }

        if ($template->getOverrideFrom() != '') {
            $fromEmail = $template->getOverrideFrom();
        }

        if ($result->getNumRows() > 0) {
            list($tClientID) = $result->fetch();
            $tmyUser = new User($tClientID);
            $statusGateway = StatusAliasGateway::getInstance($this->user);

            // Don't let fraud users reset their password
            if (in_array($tmyUser->getStatus(), $statusGateway->getUserStatusIdsFor(USER_STATUS_FRAUD))) {
                throw new CE_Exception($tmyUser->lang('Your account is not valid'));
            }


            $emailBodyArr = $template->getContents();
            $strSubjectEmailString = $template->getSubject();
            $templateID = $template->getId();
            if ($templateID !== false) {
                include_once 'modules/admin/models/Translations.php';
                $languages = CE_Lib::getEnabledLanguages();
                $translations = new Translations();
                $languageKey = ucfirst(strtolower($tmyUser->getRealLanguage()));
                CE_Lib::setI18n($languageKey);

                if (count($languages) > 1) {
                    $strSubjectEmailString = $translations->getValue(EMAIL_SUBJECT, $templateID, $languageKey, $strSubjectEmailString);
                    $emailBodyArr = $translations->getValue(EMAIL_CONTENT, $templateID, $languageKey, $emailBodyArr);
                }
            }

            if ($type == 'reset') {
                $action = "confirmpasswordlost";
            } else {
                $action = "confirmactivateaccount";
            }

            $emailBodyArr = str_replace(array("[COMPANYNAME]","%5BCOMPANYNAME%5D"), $this->settings->get("Company Name"), $emailBodyArr);
            $emailBodyArr = str_replace(array("[COMPANYADDRESS]","%5BCOMPANYADDRESS%5D"), $this->settings->get("Company Address"), $emailBodyArr);
            $emailBodyArr = str_replace("[CLIENTNAME]", $tmyUser->getFullName(true), $emailBodyArr);
            $emailBodyArr = str_replace("[FIRSTNAME]", $tmyUser->getFirstName(), $emailBodyArr);
            $emailBodyArr = str_replace("[CLIENTEMAIL]", $tmyUser->getEmail(), $emailBodyArr);
            $emailBodyArr = str_replace(array("[REQUESTIP]","%5BREQUESTIP%5D"), "$ip $host", $emailBodyArr);
            $emailBodyArr = str_replace(
                array("[CONFIRMATION URL]","%5BCONFIRMATION%20URL%5D"),
                CE_Lib::getSoftwareURL() . "/index.php?fuse=home&action=" . $action . "&value=" . md5(strtoupper($email)) . "&s=C1Ex" . $tClientID . "x58&public=1",
                $emailBodyArr
            );
            $emailBodyArr = CE_Lib::replaceCustomFields($this->db, $emailBodyArr, $tClientID, $this->settings->get('Date Format'));

            $strSubjectEmailString = str_replace(array("[COMPANYNAME]","%5BCOMPANYNAME%5D"), $this->settings->get("Company Name"), $strSubjectEmailString);
            $strSubjectEmailString = str_replace(array("[COMPANYADDRESS]","%5BCOMPANYADDRESS%5D"), $this->settings->get("Company Address"), $strSubjectEmailString);
            $strSubjectEmailString = str_replace("[CLIENTNAME]", $tmyUser->getFullName(true), $strSubjectEmailString);
            $strSubjectEmailString = str_replace("[FIRSTNAME]", $tmyUser->getFirstName(), $strSubjectEmailString);
            $strSubjectEmailString = str_replace("[CLIENTEMAIL]", $tmyUser->getEmail(), $strSubjectEmailString);
            $strSubjectEmailString = str_replace(array("[REQUESTIP]","%5BREQUESTIP%5D"), "$ip $host", $strSubjectEmailString);
            $strSubjectEmailString = str_replace(
                array("[CONFIRMATION URL]","%5BCONFIRMATION%20URL%5D"),
                CE_Lib::getSoftwareURL() . "/index.php?fuse=home&action=" . $action . "&value=" . md5(strtoupper($email)) . "&s=C1Ex" . $tClientID . "x58&public=1",
                $strSubjectEmailString
            );

            $mailSend = $mailGateway->sendMailMessage(
                $emailBodyArr,
                $fromEmail,
                $this->settings->get("Company Name"),
                $tmyUser->getId(),
                '',
                $strSubjectEmailString,
                3,
                0,
                'notifications',
                '',
                '',
                MAILGATEWAY_CONTENTTYPE_HTML
            );
            if (!is_a($mailSend, 'NE_Error')) {
                if ($type == 'reset') {
                    include_once 'modules/clients/models/Client_EventLog.php';
                    $clientsEventLog = Client_EventLog::newInstance(false, $tClientID, $tClientID, CLIENT_EVENTLOG_RESQUESTED_RESET_PASSWORD, $tClientID);
                    $clientsEventLog->save();
                }
                return true;
            }

            return $mailSend;
        } else {
            $bolReturnValue = false;
        }
        return $bolReturnValue;
    }

    protected function confirmactivateaccountAction()
    {
        $this->confirmpasswordlostAction('activate');
    }


    protected function confirmpasswordlostAction($type = 'reset')
    {
        include_once 'library/CE/NE_MailGateway.php';

        $userGateway = new UserGateway($this->user);

        $value = $_REQUEST['value'];

        //convert value2 to a valid userid
        $value2 = $_REQUEST['s'];
        $value2 = mb_substr($value2, 4);
        $pos = strpos($value2, 'x');
        $value2 = mb_substr($value2, 0, $pos);

        $query = 'SELECT userid, value FROM user_customuserfields uc LEFT JOIN customuserfields c ON uc.customid=c.id WHERE userid=? AND c.type=?';
        $result = $this->db->query($query, $value2, typeEMAIL);

        $generatePassword = true;
        // When activating must be a new customer without packages
        if ($type == 'activate') {
            $query2 = 'SELECT COUNT(*) FROM domains WHERE CustomerID=?';
            $result2 = $this->db->query($query2, $value2);
            list($tCountPackages) = $result2->fetch();
            if ($tCountPackages > 0) {
                $generatePassword = false;
            }
        }

        if ($result->getNumRows() > 0 && $generatePassword) {
            list($tUserID,$email) = $result->fetch();
            //confirm that this is a url we sent
            $email = rtrim($email);
            if (md5(strtoupper($email)) == $value) {
                $tUser = new User($tUserID);
                if ($type == 'activate') {
                    $tUser->activate();
                }
                $tNewPassword = CE_Lib::generatePassword();
                $tUser->setPassword($tNewPassword);
                $tUser->save();

                CE_Lib::trigger('Account-GeneratePassword', $this->user, [
                    'userid' => $tUserID,
                    'password' => $tNewPassword
                ]);

                include_once 'modules/clients/models/Client_EventLog.php';
                $clientsEventLog = Client_EventLog::newInstance(false, $tUserID, $tUserID, CLIENT_EVENTLOG_PASSWORD_RESET, $tUserID);
                $clientsEventLog->save();

                include_once 'modules/support/models/AutoresponderTemplateGateway.php';
                $templategateway = new AutoresponderTemplateGateway();

                if ($type == 'reset') {
                    $template = $templategateway->getEmailTemplateByName("Forgot Password Template");
                } else {
                    $template = $templategateway->getEmailTemplateByName("Activate Account Template");
                }

                $fromEmail = $this->settings->get('Support E-mail');
                if ($template->getOverrideFrom() != '') {
                    $fromEmail = $template->getOverrideFrom();
                }

                $mailGateway = new NE_MailGateway();
                $mailSend = $mailGateway->sendMailMessage(
                    $userGateway->getNewPasswordEmail($tUser, $tNewPassword, $type),
                    $fromEmail,
                    $this->settings->get('Company Name'),
                    $tUserID,
                    '',
                    $userGateway->getNewPasswordSubject($tUser, $tNewPassword, $type),
                    3,
                    0,
                    'notifications',
                    '',
                    '',
                    $tUser->isHTMLMails() ? MAILGATEWAY_CONTENTTYPE_HTML : MAILGATEWAY_CONTENTTYPE_PLAINTEXT
                );
                if (!is_a($mailSend, 'NE_Error')) {
                    $message = $this->user->lang("Password has been sent to %s", $email);
                } else {
                    $message = $mailSend->getMessage();
                }
            } else {
                $message = $this->user->lang('Confirmation URL is corrupt');
            }
        } else {
            $message = $this->user->lang('Confirmation URL is corrupt');
        }

        CE_Lib::redirectPage('index.php?fuse=home&view=Login', $message);
    }

    protected function createaccountAction()
    {
        if (!$this->settings->get('Allow Registration')) {
            CE_Lib::redirectPermissionDenied($this->user->lang('Account registration is disabled'));
        }

        $this->disableLayout(true);
        $userGateway = new UserGateway($this->user);

        try {
            $captchaPlugin = $this->settings->get('Enabled Captcha Plugin');
            if ($this->settings->get('Show Captcha on Register Page') == 1 && $captchaPlugin != '' && $captchaPlugin != 'disabled') {
                $pluginGateway = new PluginGateway($this->user);
                $plugin = $pluginGateway->getPluginByName('captcha', $captchaPlugin);
                if (!$plugin->verify($_REQUEST)) {
                    throw new CE_Exception($this->user->lang('Failed Captcha'));
                }
            }

            $emailId = $this->user->getCustomFieldsObj()->_getCustomFieldIdByType(typeEMAIL);
            $email = htmlspecialchars($this->getParam("CT_$emailId", FILTER_SANITIZE_STRING), ENT_QUOTES);
            $userId = $userGateway->createUser($email, $_POST);

            if ($this->settings->get('Show Terms and Conditions') == 1) {
                $newUser = new User($userId);
                $this->logAgreeToTermsAndService($newUser);
            }

            $affiliateGateway = new AffiliateGateway($this->user);
            $affiliateGateway->createAffiliateAccount($userId);

            $this->sendConfirmationEmail($email, 'activate');
            $this->message = $this->user->lang('Account created, please check your email for further instructions.');
            $this->send();
            return;
        } catch (Exception $e) {
            $this->error = true;
            $this->message = $e->getMessage();
            $this->send();
            return;
        }
    }

    private function logAgreeToTermsAndService($user)
    {
        if (isset($_POST['agree']) && $_POST['agree'] == 1) {
            $last4 = '';
            $clientLog = Client_EventLog::newInstance(false, $user->getId(), $user->getId(), CLIENT_EVENTLOG_AGREE_TERMS_AND_SERVICE, $user->getId(), $last4);
            $clientLog->save();
        }
    }

    protected function mainAction()
    {
        $languages = CE_Lib::getEnabledLanguages();
        include_once 'modules/admin/models/Translations.php';
        $translations = new Translations();
        $languageKey = ucfirst(strtolower($this->user->getLanguage()));

        // used to figure module in translations
        $_REQUEST['fuse'] = $_GET['fuse'] = 'home';

        $settings = new CE_Settings();
        $this->title = $this->user->lang('Home');

        $this->cssPages = array("templates/default/views/home/indexpublic/main.css");
        $this->jsLibs = array("templates/default/views/home/indexpublic/main.js");

        //let's get list of faqs
        include_once 'modules/knowledgebase/models/KBArticleListGateway.php';
        include_once 'modules/admin/models/ActiveOrderGateway.php';
        $aogateway = new ActiveOrderGateway($this->user);
        $this->view->summary = $aogateway->getCartSummary();

        $sort = [
            'field' => 'modified',
            'dir' => 'desc'
        ];
        $gateway = new KBArticleListGateway();
        $faqs = $gateway->getArticleList($this->user, 0, 15, "faq", 0, $sort, true, null, $languageKey);
        $this->view->faqs = $faqs['data'];
        $this->view->announcements = array();
        $this->view->latestarticles = array();

        if ($this->user->hasPermission('clients_view_announcements')) {
            // Get latest announcement
            include_once 'modules/admin/models/Announcements.php';
            $announcements = new Announcements($this->user);
            $this->view->announcements = $announcements->getLastPublicAnnouncements($this->settings->get('Number of Announcements To Show On Main Page'));
        }

        include_once 'modules/knowledgebase/models/KB_ArticleGateway.php';
        //get latest articles
        $catGateway = new KB_CategoryGateway($this->user);
        $this->view->latestarticles = $catGateway->parseLatestArticles($languageKey);

        $articleGW = new KB_ArticleGateway($this->user);
        $this->view->populararticles = $articleGW->getMostPopularArticles(5, $languageKey);

        $this->view->show_series_name = "";
        if ($settings->get('Show Global Series Separately')) {
            $series = $articleGW->getAllSeries(false);
            if (count($series) > 0) {
                //let's pull the first article for first category in global series
                $articles = current($series);
                if (count($articles) > 0) {
                    $article = $articles['articles'][0];

                    $name = $settings->get('Global Series Name');
                    $GlobalSeriesNameSettingId = $settings->getSettingIdForName('Global Series Name');

                    $subname = $settings->get('Global Series Subtitle');
                    $GlobalSeriesSubtitleSettingId = $settings->getSettingIdForName('Global Series Subtitle');

                    if (count($languages) > 1) {
                        if ($GlobalSeriesNameSettingId !== false) {
                            $name = $translations->getValue(SETTING_VALUE, $GlobalSeriesNameSettingId, $languageKey, $name);
                        }
                        if ($GlobalSeriesSubtitleSettingId !== false) {
                            $subname = $translations->getValue(SETTING_VALUE, $GlobalSeriesSubtitleSettingId, $languageKey, $subname);
                        }
                    }

                    if (trim($name) == "") {
                        $name = $this->user->lang("Documentation");
                    }
                    if (trim($subname) == "") {
                        $subname = '';
                    }

                    $kbArticle = new KB_Article($article['art_id']);

                    $this->view->show_series_name = $name;
                    $this->view->show_series_subname = $subname;
                    $this->view->seriesURL = $kbArticle->generateLink();
                }
            }
        }

        $this->view->showLatestArticles = ($settings->get('Number of Latest Articles') > 0 && count($this->view->latestarticles) > 0 );

        $articleGateway = new KB_ArticleGateway($this->user);
        $this->view->kbArticleCount = $articleGateway->getCountOfViewableArticles();
    }

    /**
     * Dashboard
     * @publicview true
     * @return void
     */
    protected function dashboardAction()
    {
        $this->title = $this->user->lang('Dashboard');
        include_once 'modules/support/models/TicketGateway.php';
        include_once "modules/billing/models/InvoiceListGateway.php";
        include_once 'modules/billing/models/Currency.php';
        include_once "modules/admin/models/PluginGateway.php";

        $currency = new Currency($this->user);
        $invoiceGateway = new InvoiceListGateway($this->user);
        $ticketGateway = new TicketGateway($this->user);
        $userPackageGateway = new UserPackageGateway($this->user, $this->customer);

        //if not logged in show login instead
        if ($this->user->getId() == 0 || $this->user->isAdmin()) {
            $_REQUEST['needstologin'] = true;
            $this->_forward("login");
            return;
        }

        list($tickets_iterator) = $ticketGateway->GetTickets($this->user, 10, 0, 'datesubmitted', 'DESC', 'open', $this->user->getId());
        $customCols = [];
        $this->view->customCols = [];
        foreach (ObjectCustomFields::getCustomFieldsByType('tickettypes') as $obj) {
            if (!$obj['isAdminOnly'] && $obj['isChangeable'] && $obj['showingridportal']) {
                $customCols[$obj['id']] = $obj['text'];
            }
        }

        $tickets = [];
        while ($ticket = $tickets_iterator->fetch()) {
            // check if all repsonses are private, and do not show this if they are.
            if ($ticketGateway->getCountOfNonPrivateMsgsForTicketId($ticket->id) == 0) {
                 continue;
            }

            $a_ticket = $ticket->toArray();
            $a_ticket['ticketStatus'] = $this->user->lang($ticket->getStatus());
            $a_ticket['ticketStatusClass'] = $ticket->getStatusClass();
            $a_ticket['datesubmitted'] = date('F j, Y', strtotime($a_ticket['datesubmitted']));

            $a_ticket['customfields'] = array();

            $customFieldsIds = $ticket->getCustomFieldsIds();
            foreach ($customCols as $id => $label) {
                if ($customFieldsIds) {
                    $ids = explode(chr(29), $customFieldsIds);

                    $values = explode(chr(29), $ticket->getCustomFieldsValues());
                    if (($key = array_search($id, $ids)) !== false) {
                        if (!isset($this->view->customCols[$id])) {
                            $this->view->customCols[$id] = $label;
                        }
                        $a_ticket['customfields'][$id] = $values[$key];
                    } elseif (isset($this->view->customCols[$id])) {
                        $a_ticket['customfields'][$id] = '';
                    }
                } elseif (isset($this->view->customCols[$id])) {
                    $a_ticket['customfields'][$id] = '';
                }
            }
            $tickets[] = $a_ticket;
        }
        $this->view->tickets = $tickets;

        $filter = [];
        $filter['b.status'] = [0, 5];
        $invoices_iterator = $invoiceGateway->get_invoices_by_user($this->user->getId(), 'id desc', $filter);
        $invoices = [];
        while ($invoice = $invoices_iterator->fetch()) {
            $a_invoice = $invoice->toArray();
            $tinvoice = new Invoice($a_invoice['id']);
            $a_invoice['status_name'] = $this->user->lang($tinvoice->getStatusName());
            $a_invoice['status_class'] = $tinvoice->getStatusClass();
            $a_invoice['balancedueraw'] = $a_invoice['balancedue'];
            $a_invoice['balancedue'] = $currency->format($tinvoice->getCurrency(), $a_invoice['balancedue'], true);
            $invoices[] = $a_invoice;
        }
        $this->view->invoices = $invoices;

        $this->view->packages = [];
        $this->view->domains = [];
        if ($this->view->templateOptions['Show Package List in Dashboard']['value'] == 'Yes') {
            $this->view->packages = $userPackageGateway->getClientPackagesList(true);
        }
        if ($this->view->templateOptions['Show Domain List in Dashboard']['value'] == 'Yes') {
            $this->view->domains = $userPackageGateway->getClientDomainsList(false);
        }
    }
}
