<?php

require_once 'modules/admin/models/FraudPlugin.php';
require_once 'library/CE/RestRequest.php';

class PluginFraudlabspro extends FraudPlugin
{
    function getVariables()
    {
        $variables = array(
            lang('Plugin Name')   => array(
                'type'          => 'hidden',
                'description'   => '',
                'value'         => lang('FraudLabs Pro'),
            ),
            lang('Enabled')       => array(
                'type'          => 'yesno',
                'description'   => lang('Setting allows FraudLabs Pro slients to check orders for fraud.'),
                'value'         => '0',
            ),
            lang('API Key')       => array(
                'type'          => 'text',
                'description'   => lang('Enter your API Key here.<br>You can obtain a license at <a href="http://www.fraudlabspro.com/?ref=1614" target="_blank">https://www.fraudlabspro.com/</a>'),
                'value'         => '',
            )
        );

        return $variables;
    }

    function grabDataFromRequest($request)
    {
        //get email custom id for user
        $query = "SELECT id FROM customuserfields WHERE type=".typeEMAIL;
        $result = $this->db->query($query);
        list($tEmailID) = $result->fetch();
        //get city custom id for user
        $query = "SELECT id FROM customuserfields WHERE type=".typeCITY;
        $result = $this->db->query($query);
        list($tCityID) = $result->fetch();
        //get state custom id for user
        $query = "SELECT id FROM customuserfields WHERE type=".typeSTATE;
        $result = $this->db->query($query);
        list($tStateID) = $result->fetch();
        //get country custom id for user
        $query = "SELECT id FROM customuserfields WHERE type=".typeCOUNTRY;
        $result = $this->db->query($query);
        list($tCountryID) = $result->fetch();
        //get zipcode custom id for user
        $query = "SELECT id FROM customuserfields WHERE type=".typeZIPCODE;
        $result = $this->db->query($query);
        list($tZipcodeID) = $result->fetch();
        //get phone custom id for user
        $query = "SELECT id FROM customuserfields WHERE type=".typePHONENUMBER;
        $result = $this->db->query($query);
        list($tPhoneNumberID) = $result->fetch();
        // first name
        $query = "SELECT id FROM customuserfields WHERE type=".typeFIRSTNAME;
        $result = $this->db->query($query);
        list($firstNameId) = $result->fetch();
        // last name
        $query = "SELECT id FROM customuserfields WHERE type=".typeLASTNAME;
        $result = $this->db->query($query);
        list($lastNameId) = $result->fetch();
        // address
        $query = "SELECT id FROM customuserfields WHERE type=".typeADDRESS;
        $result = $this->db->query($query);
        list($addressId) = $result->fetch();

        $this->input["city"] = $request['CT_'.$tCityID];
        $this->input["region"] = $request['CT_'.$tStateID];
        $this->input["postal"] = $request['CT_'.$tZipcodeID];
        $this->input["country"] = $request['CT_'.$tCountryID];
        $this->input["phone"] = $request['CT_'.$tPhoneNumberID];
        $this->input["email"] = $request['CT_'.$tEmailID];
        $this->input["firstName"] = $request['CT_'.$firstNameId];
        $this->input["lastName"] = $request['CT_'.$lastNameId];
        $this->input["address"] = $request['CT_'.$addressId];
        $this->input['amount'] = $request['totalPay_raw'];
        $this->input['currency'] = $request['currency'];

        if (!is_null($this->settings->get("plugin_".@$_REQUEST['paymentMethod']."_Accept CC Number"))
                && $this->settings->get("plugin_".@$_REQUEST['paymentMethod']."_Accept CC Number")) {
            $this->input["bin"] = mb_substr(@$_REQUEST[@$_REQUEST['paymentMethod'].'_ccNumber'], 0, 6);
        }
    }

    function execute()
    {
        FraudLabsPro\Configuration::apiKey($this->settings->get('plugin_fraudlabspro_API Key'));

        $orderDetails = [
            'order'     => [
                'currency'      => $this->input['currency'],
                'amount'        => $this->input['amount'],
            ],
            'billing'   => [
                'firstName' => $this->input['firstName'],
                'lastName'  => $this->input['lastName'],
                'email'     => $this->input['email'],
                'phone'     => $this->input['phone'],
                'address'   => $this->input['address'],
                'city'      => $this->input['city'],
                'state'     => $this->input['region'],
                'postcode'  => $this->input['postal'],
                'country'   => $this->input['country'],
            ],
        ];

        $this->result = get_object_vars(FraudLabsPro\Order::validate($orderDetails));
        return $this->result;
    }

    public function isOrderAccepted()
    {
        if ($this->result['fraudlabspro_status'] == 'REJECT') {
            $this->failureMessages[] = $this->user->lang('Your overall risk is too high, please contact our sales office for more information.');
            return false;
        }
        return true;
    }
}
