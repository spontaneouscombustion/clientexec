<?php

/**
 * Client Module's Action Controller
 *
 * @category   Action
 * @package    Clients
 * @author     Alberto Vasquez <alberto@clientexec.com>
 * @license    http://www.clientexec.com  ClientExec License Agreement
 * @link       http://www.clientexec.com
 */
class Clients_UserprofilepublicController extends CE_Controller_Action
{
    public $moduleName = "clients";

    /**
     * update payment method by customer
     * @return [type] [description]
     */
    protected function updatepaymentmethodAction()
    {
        $returnMsg = '';

        $new_paymenttype = $this->getParam('paymenttype', FILTER_SANITIZE_STRING);

        // NOT SURE HOW TO SANITIZE AN ARRAY
        $plugincustomfields = array();
        $plugincustomfields = $this->getParam($new_paymenttype . '_plugincustomfields', null, $plugincustomfields);

        $ccnumber = $this->getParam('ccnumber', FILTER_SANITIZE_STRING, "");
        $newccnumber = $this->getParam('newccnumber', FILTER_SANITIZE_STRING, "");
        if ($newccnumber != "") {
            // CC number is being updated, so check this permission first
            $this->checkPermissions('clients_edit_credit_card');
            $ccnumber = $newccnumber;
        }
        $ccmonth = $this->getParam('ccmonth', FILTER_SANITIZE_STRING, "");
        $ccyear = $this->getParam('ccyear', FILTER_SANITIZE_STRING, "");

        require_once 'modules/clients/models/UserGateway.php';
        $userGateway = new UserGateway($this->user);
        $userGateway->updateGatewayInformation($this->customer, $new_paymenttype, $plugincustomfields, $ccnumber, $ccmonth, $ccyear);

        CE_Lib::addSuccessMessage($this->user->lang('Your payment method was updated successfully'));
        CE_Lib::redirectPage("index.php?fuse=clients&controller=userprofile&view=paymentmethod");
    }

    /**
     * viewing payment method
     * @return [type] [description]
     */
    protected function paymentmethodAction()
    {
        $this->title = $this->user->lang('Update Payment Method');

        if ($this->user->getId() == 0) {
            CE_Lib::addErrorMessage($this->user->lang('You must be logged in to access that feature'));
            CE_Lib::redirectPage("index.php?fuse=home&view=login");
        }

        include_once "library/CE/NE_PluginCollection.php";
        $this->jsLibs = array("templates/default/views/clients/userprofilepublic/paymentmethod.js");
        $this->cssPages = array("templates/default/views/clients/userprofilepublic/paymentmethod.css");

        $plugins = new NE_PluginCollection("gateways", $this->user);

        $selectedPlugin = "";

        $pluginsArray = array();
        while ($tplugin = $plugins->getNext()) {
            $tvars = $tplugin->getVariables();
            $tvalue = $this->user->lang($tvars['Plugin Name']['value']);
            $pluginsArray[$tvalue] = $tplugin;
        }
        uksort($pluginsArray, "strnatcasecmp");

        $this->view->paymentmethods = array();

        foreach ($pluginsArray as $value => $plugin) {
            $paymentmethod = array();

            if (($plugin->getVariable("In Signup") == "1") || ($plugin->getVariable("One-Time Payments") == "1") || ( $this->user->getPaymentType() === $plugin->getInternalName())) {
                $paymentmethod['paymentTypeOptionValue'] = $plugin->getInternalName();
                $paymentmethod['paymentTypeOptionLabel'] = $plugin->getVariable("Signup Name");
                if ($this->user->getPaymentType() == $plugin->getInternalName()) {
                    $selectedPlugin = $plugin->getVariable("Signup Name");
                    $paymentmethod['paymentTypeOptionSelected'] = 'selected="selected"';
                } else {
                    $paymentmethod['paymentTypeOptionSelected'] = '';
                }
                $this->view->paymentmethods[] = $paymentmethod;
            }
        }

        //Billing-Profile-ID
        $Billing_Profile_ID = '';
        $profile_id_array = array();

        if ($this->user->getCustomFieldsValue('Billing-Profile-ID', $Billing_Profile_ID) && $Billing_Profile_ID != '') {
            $profile_id_array = unserialize($Billing_Profile_ID);

            if (!is_array($profile_id_array)) {
                $profile_id_array = array();
            }
        }

        $this->view->profile_id_array = $profile_id_array;
        //Billing-Profile-ID

        $this->view->selectedpluginname = $this->user->getPaymentType();
        $this->view->selectedpluginrealname = $selectedPlugin;

        if ($this->user->hasPermission('clients_edit_payment_type')) {
            $this->view->canEditPaymentType = true;
        } else {
            $this->view->canEditPaymentType = false;
            $this->view->paymentTypeOptionLabel = $this->user->lang($selectedPlugin);
        }

        if ($this->user->hasPermission('clients_edit_credit_card')) {
            $this->view->canEditCreditCard = true;
            $this->view->helpCCChange = $this->user->lang("Only enter a credit card if you want to change the one on file");
            $this->view->requiredMsg = $this->user->lang("Invalid Credit Card Format");
            $this->view->ccRequiredMsg = $this->user->lang("Credit Card Number Required");

            $tempCCMonth = $this->user->getCCMonth();

            $months = array(
            1   => $this->user->lang('January'),
            2   => $this->user->lang('February'),
            3   => $this->user->lang('March'),
            4   => $this->user->lang('April'),
            5   => $this->user->lang('May'),
            6   => $this->user->lang('June'),
            7   => $this->user->lang('July'),
            8   => $this->user->lang('August'),
            9   => $this->user->lang('September'),
            10  => $this->user->lang('October'),
            11  => $this->user->lang('November'),
            12  => $this->user->lang('December'),
            );

            $this->view->months = array();
            foreach ($months as $number => $month) {
                $this->view->months[] = array(
                'monthOptionsValue'     => $number,
                'monthOptionsSelected'   => @$tempCCMonth == $number ? 'selected="selected"' : '',
                'monthOptionsLabel'      => "$number - $month",
                );
            }

            $tempCCYear = $this->user->getCCYear();
            $curYear = date("Y");

            $this->view->years = array();

            if (@$tempCCYear != 0 && @$tempCCYear < $curYear) {
                $this->view->years[] = array(
                'yearOptionsValue'      => @$tempCCYear,
                'yearOptionsSelected'   => 'selected="selected"',
                'yearOptionsLabel'      => @$tempCCYear,
                );
            }

            for ($theYear = $curYear; $theYear <= ($curYear + 15); $theYear++) {
                $this->view->years[] = array(
                'yearOptionsValue'      => $theYear,
                'yearOptionsSelected'   => @$tempCCYear == $theYear ? 'selected="selected"' : '',
                'yearOptionsLabel'      => $theYear,
                );
            }
        } else {
            $this->view->canEditCreditCard = false;
        }

        $pluginCollection = new NE_PluginCollection('gateways', $this->user);
        $pluginCollection->setTemplate($this->view);
        $params = array();
        $params['from'] = 'paymentmethod';
        $params['panellabel'] = $this->user->lang("Update");
        $params['userZipcode'] = $this->user->getZipCode();

        include_once("modules/admin/models/PluginGateway.php");
        $plugingateway = new PluginGateway($this->user);
        $GatewayWithFormPlugins = $plugingateway->getGatewayWithVariablePlugins('Form');
        $gatewayForms = array();
        foreach ($GatewayWithFormPlugins as $GatewayWithForm) {
            $gatewayForms[$GatewayWithForm] = $pluginCollection->callFunction($GatewayWithForm, 'getForm', $params);
        }
        $this->view->gatewayForms = $gatewayForms;
        $this->view->gatewayIframes = $plugingateway->getGatewayWithVariablePlugins('Iframe Configuration', true);
    }

    public function saveprofileAction()
    {
        if (DEMO) {
            CE_Lib::redirectPage("index.php?fuse=clients&view=editprofile&controller=userprofile", $this->user->lang("No profile updates available in demo mode"));
        }

        $this->checkPermissions('clients_edit_customers');

        require_once 'modules/clients/models/UserGateway.php';

        $error = '';
        $userGateway = new UserGateway($this->user);
        $gAlertMessage = '';

        $clientLog = Client_EventLog::newInstance(false, $this->user->getId(), $this->user->getId());
        $clientLog->setSubject($this->user->getId());

        $userInformation = array();

        $emailId = $this->user->getCustomFieldsObj()->_getCustomFieldIdByType(typeEMAIL);

        if (isset($_REQUEST["CT_$emailId"])) {
            try {
                $userGateway->VerifyEmailDuplicate($_REQUEST["CT_$emailId"], false, $this->user->getId());
            } catch (Exception $ex) {
                $gAlertMessage = $ex->getMessage();
                CE_Lib::redirectPage(
                    "index.php?fuse=clients&controller=userprofile&view=editprofile&error=" . errDuplicateEmail,
                    $gAlertMessage
                );
            }

            if ($userGateway->SupportEmailExist($_REQUEST["CT_$emailId"])) {
                $gAlertMessage = $this->user->lang("The email %s can not be used because it is already in use for support by another user.", $_REQUEST["CT_$emailId"]);
                CE_Lib::redirectPage(
                    "index.php?fuse=clients&controller=userprofile&view=editprofile&error=" . errDuplicateEmail,
                    $gAlertMessage
                );
            }

            if ($this->user->getEmail() != $_REQUEST["CT_$emailId"]) {
                $bolEmailChanged = true;
            } else {
                $bolEmailChanged = false;
            }
        } else {
            $bolEmailChanged = false;
        }

        $userInformation['Gateway'] = $this->user->getPaymentType();
        $userInformation['Action'] = 'update';
        $userInformation['User ID'] = $this->user->getId();

        $eventLog = array();

        foreach ($_POST as $key => $post) {
            if (substr($key, 0, 3) == 'CT_') {
                $exploded = explode('_', $key);
                $customTag = $exploded[1];
                if ($this->user->isCustomFieldChangeable($customTag)) {
                    $value = htmlspecialchars($post, ENT_QUOTES);
                    $oldValue == '';
                    $this->user->getCustomFieldsValue($customTag, $oldValue);
                    if ($oldValue != $value) {
                        $eventLog[$this->user->getCustomFieldName($customTag)] = $value;
                    }
                    // If it's a date field convert it database format
                    if (isset($_POST["CTT_$customTag"]) && $_POST["CTT_$customTag"] == typeDATE) {
                        if ($value != '') {
                            $value = CE_Lib::form_to_db($value, $this->settings->get('Date Format'), "/");
                        }
                    }
                    $this->user->updateCustomTag($customTag, $value);
                    $userInformation[$this->user->getCustomFieldName($customTag)] = $value;
                }
            }
        }

        if (count($eventLog)) {
            $clientLog->setAction(CLIENT_EVENTLOG_UPDATEDPROFILECONTACT);
            $clientLog->setParams(serialize($eventLog));
            CE_Lib::addEventLog($clientLog);
        }
        $gAlertMessage = $this->user->lang("Your profile has been updated");

        $passEventLog = Client_EventLog::newInstance(false, $this->user->getId(), $this->user->getId());
        $passEventLog->setSubject($this->user->getId());

        $password = $this->getParam('password', null, '', null);
        if ($password != '') {
            if ($this->settings->get('Enforce Password Strength')) {
                if ($this->user->isAdmin() && $this->settings->get('Allow Admins Override Enforce Password Strength')) {
                } else {
                    include_once 'modules/admin/models/PasswordStrength.php';
                    $passwordStrength = new PasswordStrength($this->settings, $this->user);
                    $passwordStrength->setPassword($password);
                    if (!$passwordStrength->validate()) {
                        $error .= $this->user->lang('Error updating customer profile:');
                        foreach ($passwordStrength->getMessages() as $message) {
                            $error .= "\n" . $this->user->lang($message);
                        }
                    }
                }
            }
            if ($error == "") {
                $this->user->setPassword($password);
                $passEventLog->setAction(CLIENT_EVENTLOG_CHANGEDPASSWORD);
                $passEventLog->save();
                unset($password);
            }
        }
        $this->user->setProfileUpdated(1);
        $this->user->save();

        CE_Lib::trigger('Client-Update', $this, ['userid' => $this->user->getId(), 'user' => $this->user]);

        $errorstr = "";
        if ($error != "") {
            $errorstr = "error=$error";
        }

        $url = "index.php?fuse=clients&controller=userprofile&view=editprofile&$errorstr";
        CE_Lib::redirectPage($url, $gAlertMessage);
    }

    public function getnotesAction()
    {
        $this->checkPermissions();
        $this->title = $this->user->lang('Account Notes');

        require_once 'modules/clients/models/ClientNoteGateway.php';
        $noteGateway = new ClientNoteGateway($this->user);

        $notes = [];
        $clientNotesIt = $noteGateway->getClientNotes($this->user->getId());

        while ($note = $clientNotesIt->fetch()) {
            if ($note->isArchived() || !$note->isVisibleClient()) {
                continue;
            }
            $noteArray = [];
            $noteArray['id'] = $note->getId();
            $noteArray['date'] = CE_Lib::db_to_form(
                $note->getDate(),
                $this->settings->get('Date Format'),
                '-',
                true
            );
            $noteArray['author'] = $note->getAdminFullName();
            $noteArray['content'] = $note->getNote();
            $noteArray['subject'] = $note->getSubject();
            if ($noteArray['subject'] == '') {
                $noteArray['subject'] = 'N/A';
            }
            $notes[] = $noteArray;
        }
        $this->send(['data' => $notes]);
    }

    protected function notesAction()
    {
        $this->checkPermissions();
        $this->title = $this->user->lang('Account Notes');
    }

    protected function editprofileAction()
    {
        $this->title = $this->user->lang('Edit Profile');
        $this->checkPermissions('clients_edit_customers');

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
            false,
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

            // we need to manipulate select boxes differently
            if (in_array($field['fieldtype'], array(typeCOUNTRY, typeSTATE, typeYESNO, typeLANGUAGE, typeDROPDOWN, TYPE_ALLOW_EMAIL))) {
                $this->user->getCustomFieldsValue($field['id'], $value);
            } else {
                $this->user->getCustomFieldsValue($field['id'], $value);
                $value = htmlspecialchars_decode($value, ENT_QUOTES);
            }
            $field['value'] = $value;

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


    public function updatealtemailAction()
    {
        $this->disableLayout();

        require_once 'modules/clients/models/UserGateway.php';
        $userGateway = new UserGateway($this->user);

        $eventLog = Client_EventLog::newInstance(false, $this->customer->getId(), $this->customer->getId());
        $eventLog->setSubject($this->user->getId());

        $gAlertMessage = array();

        foreach ($_REQUEST as $key => $value) {
            $exploded = explode('_', $key);

            // updating
            if (count($exploded) == 2) {
                $emailId = $exploded[1];
                if ($exploded[0] == 'delete') {
                    $eventLog->setAction(CLIENT_EVENTLOG_DELETEDALTERNATEEMAIL);
                    $tempEmail = $userGateway->getAlternateEmailById($emailId);
                    $emailResult = $tempEmail->fetch();
                    $eventLog->setParams($emailResult['email']);
                    CE_Lib::addEventLog($eventLog);
                    $userGateway->DeleteAltEmail($emailId);
                } else {
                    if ($exploded[0] == 'email') {
                        $notificationValue = $_REQUEST['sendnotifications_' . $emailId];
                        $invoiceValue = $_REQUEST['sendinvoice_' . $emailId];
                        $supportValue = $_REQUEST['sendsupport_' . $emailId];
                        $emailValue = $_REQUEST['email_' . $emailId];

                        try {
                            $returnValue = $userGateway->updateAltEmailValueFromPublic($emailValue, $emailId);
                            $returnValue = $userGateway->updateAltEmailFromPublic('sendnotifications', $notificationValue, $emailId);
                            $returnValue = $userGateway->updateAltEmailFromPublic('sendinvoice', $invoiceValue, $emailId);
                            $returnValue = $userGateway->updateAltEmailFromPublic('sendsupport', $supportValue, $emailId);
                        } catch (Exception $ex) {
                            $gAlertMessage[] = $ex->getMessage();
                        }
                    }
                }
            }
        }

        // Insert the new e-mail
        if ($_POST['newaltemail'] != "") {
            $altEmail = $_POST['newaltemail'];
            //check to see if alt email is a duplicate
            $error = false;

            if (!CE_Lib::valid_email($altEmail)) {
                $gAlertMessage[] = $this->user->lang("The email %s is invalid.", $this->view->escape($altEmail));
                CE_Lib::redirectPage('index.php?fuse=clients&controller=userprofile&view=altemails', $gAlertMessage);
                return;
            }

            if ($userGateway->is_email_used_as_alt($altEmail)) {
                CE_Lib::redirectPage('index.php?fuse=clients&controller=userprofile&view=altemails', $this->user->lang("The email %s can not be used because it is already in use as a secondary contact by another user.", $altEmail));
            }

            try {
                $verify = $userGateway->VerifyEmailDuplicate($altEmail);

                if (isset($_POST['newsupport'])) {
                    $verify = $userGateway->SupportEmailExist($altEmail);

                    if ($verify) {
                        $gAlertMessage[] = $this->user->lang("The email %s can not be set for support because there is already one set for that.", $altEmail);
                    }
                }
                $userGateway->addAlternate($this->user->getId(), $altEmail, isset($_POST['newnotify']) ? 1 : 0, isset($_POST['newinvoice']) ? 1 : 0, isset($_POST['newsupport']) ? 1 : 0);
            } catch (Exception $ex) {
                $gAlertMessage[] = $this->user->lang($ex->getMessage());
                $error = true;
            }

            if (!$error) {
                $eventLog->setAction(CLIENT_EVENTLOG_ADDEDALTERNATEEMAIL);
                $eventLog->setNewAlternateEmail($_POST['newaltemail'], isset($_POST['newnotify']) ? 1 : 0, isset($_POST['newinvoice']) ? 1 : 0, isset($_POST['newsupport']) ? 1 : 0);
                CE_Lib::addEventLog($eventLog);
            }
        }

        if (count($gAlertMessage) > 0) {
             $gAlertMessage = implode($gAlertMessage, '<br/>');
        }

        CE_Lib::redirectPage('index.php?fuse=clients&controller=userprofile&view=altemails', $gAlertMessage);
    }

    protected function altemailsAction()
    {
        $this->checkPermissions();
        $this->title = $this->user->lang('Alternate Accounts');

        $arrAltEmailFields = array();

        $altemailresult = $this->db->query("SELECT id, email, sendnotifications, sendinvoice, sendsupport FROM altuseremail WHERE userid = ?", $this->customer->getId());

        $this->view->notificationsHelp = '';
        $this->view->supportHelp = '';
        $this->view->deleteEmailHelp = '';
        $this->view->showBillingColumn = true;
        $this->view->accounts = array();

        while (list($altid, $altemail, $sendnotifications, $sendinvoice, $support) = $altemailresult->fetch()) {
            $aAltEmail = array();
            if ($sendnotifications == 1) {
                $sendnotifications = "checked='checked'";
            } else {
                $sendnotifications = "";
            }

            if ($sendinvoice == 1) {
                $sendinvoice = "checked='checked'";
            } else {
                $sendinvoice = "";
            }

            if ($support == 1) {
                $support = "checked='checked'";
            } else {
                $support = "";
            }

            $arrRemove[] = "sendnotifications_$altid";
            $arrAltEmailFields[] = "sendnotifications_$altid";
            $arrRemove[] = "sendinvoice_$altid";
            $arrAltEmailFields[] = "sendinvoice_$altid";
            $aAltEmail['sendInvoice'] = $sendinvoice;

            $arrRemove[] = "sendinvoice_$altid";
            $arrAltEmailFields[] = "sendsupport_$altid";
            $arrRemove[] = "delete_$altid";
            $arrAltEmailFields[] = "delete_$altid";

            $aAltEmail['altEmail']          = $this->view->escape($altemail);
            $aAltEmail['altEmailId']        = $altid;
            $aAltEmail['sendNotifications'] = $sendnotifications;
            $aAltEmail['disabled']          = $disabled;
            $aAltEmail['support']           = $support;
            $this->view->accounts[] = $aAltEmail;
        }
    }

    public function updatepasswordAction()
    {
        $this->checkPermissions();
        if (DEMO) {
            CE_Lib::redirectPage(
                "index.php?fuse=clients&view=editprofile&controller=editpassword",
                $this->user->lang("You can not update password in demo mode.")
            );
        }

        include_once 'library/CE/PasswordHash.php';

        $currentPassword = $this->getParam('password');
        $newPassword = $this->getParam('new-password');
        $confirmPassword = $this->getParam('confirm-password');


        if (!PasswordHash::validate_password($currentPassword, $this->user->getPassword())) {
            CE_Lib::addErrorMessage($this->user->lang('Existing password does not match'));
            CE_Lib::redirectPage('index.php?fuse=clients&controller=userprofile&view=editpassword');
            return;
        }

        if ($newPassword !== $confirmPassword) {
            CE_Lib::addErrorMessage($this->user->lang('Passwords do not match.'));
            CE_Lib::redirectPage('index.php?fuse=clients&controller=userprofile&view=editpassword');
            return;
        }

        $error = [];
        if ($this->settings->get('Enforce Password Strength')) {
            include_once 'modules/admin/models/PasswordStrength.php';
            $passwordStrength = new PasswordStrength($this->settings, $this->user);
            $passwordStrength->setPassword($newPassword);
            if (!$passwordStrength->validate()) {
                foreach ($passwordStrength->getMessages() as $message) {
                    $error[] = $this->user->lang($message);
                }
            }
        }

        if (count($error) == 0) {
            $this->user->setPassword($newPassword);
            $this->user->save();
            $passEventLog = Client_EventLog::newInstance(false, $this->user->getId(), $this->user->getId());
            $passEventLog->setSubject($this->user->getId());
            $passEventLog->setAction(CLIENT_EVENTLOG_CHANGEDPASSWORD);
            $passEventLog->save();
            CE_Lib::trigger(
                'Client-PasswordChange',
                $this,
                [
                    'userId' => $this->customer->getId(),
                    'password' => $_REQUEST['new_password']
                ]
            );
            CE_Lib::addSuccessMessage($this->user->lang('Successfully updated password'));
        } else {
            CE_Lib::addErrorMessage($error);
        }
        CE_Lib::redirectPage('index.php?fuse=clients&controller=userprofile&view=editpassword');
    }

    public function editpasswordAction()
    {
        $this->jsLibs = array("templates/default/views/clients/userprofilepublic/editpassword.js");
        $this->cssPages = array("templates/default/views/clients/userprofilepublic/editpassword.css");

        $this->title = 'Change Password';
        $this->checkPermissions();
        $this->view->userId = $this->user->getId();
    }
}
