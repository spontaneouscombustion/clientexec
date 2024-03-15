<?php

require_once 'modules/admin/models/ImportPlugin.php';

/**
* @package Plugins
*/
class PluginClients_csv extends ImportPlugin
{
    public $_title;
    public $_description;

    public $userGateway;
    public $customFields;

    public $creditCardFields = array(
        'Credit_Card_Number' => 'Credit Card Number',
        'Expiration_Month'   => 'Expiration Month',
        'Expiration_Year'    => 'Expiration Year',
        'Currency'           => 'Currency'
    );

    public function __construct($user, $typeOfFetch = 1)
    {
        $this->_title = lang('Customer Data CSV');
        $this->_description = lang('This import plugin imports client personal information from a comma separated value (.csv) file.');
        parent::__construct($user, $typeOfFetch);
    }

    public function getForm()
    {
        $this->view->customFields = array();

        $this->getCustomFields();
        foreach ($this->customFields as $t_id => $t_values) {
            $this->view->customFields[$t_id]['name'] = $this->user->lang($t_values['name']);
            $this->view->customFields[$t_id]['inputName'] = "CT_$t_id";
            $this->view->customFields[$t_id]['inputRequired'] = "CTR_$t_id";
            if ($t_values['isRequired']) {
                $this->view->customFields[$t_id]['checked'] = 'checked="checked"';
                $this->view->customFields[$t_id]['disabled'] = 'disabled';
            } else {
                $this->view->customFields[$t_id]['checked'] = '';
                $this->view->customFields[$t_id]['disabled'] = '';
            }
        }

        //Credit Card Number, Expiration Month & Expiration Year
        foreach ($this->creditCardFields as $t_id => $t_values_name) {
            $this->view->customFields[$t_id]['name'] = $this->user->lang($t_values_name);
            $this->view->customFields[$t_id]['inputName'] = "CT_$t_id";
            $this->view->customFields[$t_id]['inputRequired'] = "CTR_$t_id";
            $this->view->customFields[$t_id]['checked'] = '';
            $this->view->customFields[$t_id]['disabled'] = '';
        }

        return $this->view->render('PluginClientcsv.phtml');
    }

    public function process()
    {
        include_once 'library/CE/NE_Upload.php';
        include_once 'modules/clients/models/UserGateway.php';

        $this->userGateway = new UserGateway();

        if (@$_FILES['file']['name'] == '') {
            CE_Lib::redirectPage('index.php?fuse=admin&view=viewimportplugins&plugin=clients_csv&controller=importexport', $this->user->lang('You didn\'t upload any files'));
        }

        $filename = $_FILES['file']['name'];
        $file = new NE_Upload('file');

        if (!$file->isValid()) {
            CE_Lib::redirectPage('index.php?fuse=admin&view=viewimportplugins&plugin=clients_csv&controller=importexport', $this->user->lang("File %s upload failed", $filename) . ". " . $this->user->lang("Please ensure that the file is not empty and try uploading again"));
        }

        $fp = fopen($_FILES['file']['tmp_name'], 'r');
        $firstLine = true;
        while (!feof($fp)) {
            $line = fgetcsv($fp, 4096, $_POST['fieldDelimiter'], $_POST['fieldEnclosure']);
            if (!$line || $firstLine && isset($_POST['skipFirstRow'])) {
                $firstLine = false;
                continue;
            }
            $line[-1] = '';

            if (!$this->createUser($line)) {
                CE_Lib::addMessage($this->user->lang('User %s couldn\'t be added because you have reached your user number limit', "{$line[0]} {$lastName[1]}"));
                break;
            }
        }
        fclose($fp);

        CE_Lib::redirectPage('index.php?fuse=admin&view=viewimportplugins&plugin=clients_csv&controller=importexport');
    }

    private function createUser($line)
    {
        $missed_fields = array();
        $firstName = '';
        $lastName = '';
        $email = '';
        $organization = '';

        $this->getCustomFields();

        foreach ($this->customFields as $t_id => $t_values) {
            switch ($t_values['name']) {
                case 'First Name':
                    $firstName = $line[$_POST["CT_$t_id"] - 1];
                    break;
                case 'Last Name':
                    $lastName = $line[$_POST["CT_$t_id"] - 1];
                    break;
                case 'Email':
                    $email = $line[$_POST["CT_$t_id"] - 1];
                    break;
                case 'Organization':
                    $organization = $line[$_POST["CT_$t_id"] - 1];
                    break;
            }

            if (isset($_POST["CTR_$t_id"]) && ($line[$_POST["CT_$t_id"] - 1] == '')) {
                $missed_fields[] = $t_values['name'];
            }
        }

        //Credit Card Number, Expiration Month & Expiration Year
        foreach ($this->creditCardFields as $t_id => $t_values_name) {
            if (isset($_POST["CTR_$t_id"]) && ($line[$_POST["CT_$t_id"] - 1] == '')) {
                $missed_fields[] = $this->user->lang($t_values_name);
            }
        }

        if (count($missed_fields)) {
            $missed_fields_list = implode(', ', $missed_fields);
            CE_Lib::addMessage($this->user->lang('User %s couldn\'t be added because they are missing the following required fields: %s ', "$firstName $lastName", $missed_fields_list));
            return true;
        }

        try {
            $verify = $this->userGateway->VerifyEmailDuplicate($email);
        } catch (Exception $e) {
            CE_Lib::addMessage($this->user->lang('User %s couldn\'t be added.', "$firstName $lastName") . ' ' . $e->getMessage());
            return true;
        }

        $user = new User();
        $user->setEmail($email);
        $user->setDirty(true);
        if (!$user->add()) {
            return false;
        }
        $user->setFirstName($firstName);
        $user->setLastName($lastName);
        $user->setDateCreated(date('Y-m-d'));
        if ($_POST['CT_Currency'] != '') {
            $user->setCurrency($_POST['CT_Currency']);
        } else {
            $user->setCurrency($this->settings->get('Default Currency'));
        }
        $user->setGroupId(1);

        foreach ($this->customFields as $t_id => $t_values) {
            $user->setCustomFieldById($t_id, $line[$_POST["CT_$t_id"] - 1]);
        }

        //Credit Card Number
        if ($_POST["CT_Credit_Card_Number"] != '') {
            $user->StoreCreditCardInfo($line[$_POST["CT_Credit_Card_Number"] - 1], $this->settings);
            $user->SetAutoPayment(1);
        }

        //Expiration Month
        if ($_POST["CT_Expiration_Month"] != '') {
            $user->setCCMonth($line[$_POST["CT_Expiration_Month"] - 1]);
        }

        //Expiration Year
        if ($_POST["CT_Expiration_Year"] != '') {
            $user->setCCYear($line[$_POST["CT_Expiration_Year"] - 1]);
        }

        CE_Lib::addMessage($this->user->lang('User %s was successfully added', "$firstName $lastName"));

        $user->activate();
        $user->save();

        return true;
    }

    private function getCustomFields()
    {
        if (!isset($this->customFields)) {
            $query = "SELECT id, name, isRequired FROM customuserfields WHERE (inSignup = 1 OR inSettings = 1) ORDER BY myOrder";
            $result = $this->db->query($query);
            while (list($tID,$tName,$tisRequired) = $result->fetch()) {
                $this->customFields[$tID] = [
                    'name'       => $tName,
                    'isRequired' => $tisRequired
                ];
            }
        }
    }
}
