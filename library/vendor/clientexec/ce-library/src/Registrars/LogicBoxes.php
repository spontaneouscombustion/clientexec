<?php

namespace Clientexec\Registrars;

class LogicBoxes
{
    private $apiUserId;
    private $apiKey;
    private $apiPassword;
    private $testMode;
    private $user;
    private $recordTypes;

    public function __construct($apiUserId, $apiKey, $apiPassword, $user, $recordTypes, $testMode = 0)
    {
        $this->apiUserId = $apiUserId;
        $this->apiKey = $apiKey;
        $this->apiPassword = $apiPassword;
        $this->user = $user;
        $this->testMode = $testMode;
        $this->recordTypes = $recordTypes;
    }

    public function setDNS($params)
    {
        $params['sld'] = strtolower($params['sld']);
        $params['tld'] = strtolower($params['tld']);
        $domain = $params['sld'] . "." . $params['tld'];

        // add some validation
        foreach ($params['records'] as $record) {
            $type = $record['type'];
            $host = $record['hostname'];
            $ip = $record['address'];

            // CE has hard-coded types right now, and does not support types from the plugin, so lets error if they use certain types
            if (in_array($type, ['MXE', 'URL', 'FRAME'])) {
                throw new \CE_Exception($this->user->lang('At the present time, a %s record is not supported with ResellerClub.', $record['type']));
            }

            if ($type == 'A') {
                if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                    throw new \CE_Exception($this->user->lang('Invalid IP address "%s" for record "%s".', $ip, $host));
                }
            }

            if ($type == 'AAAA') {
                if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                    throw new \CE_Exception($this->user->lang('Invalid IP address "%s" for record "%s".', $ip, $host));
                }
            }
        }

        // need to delete all records first, then re-add any that we are supplied with.
        foreach ($this->recordTypes as $type) {
            $result = $this->searchHosts($params, $type);

            if ($result->recsonpage > 0) {
                $numberOfRecords = $result->recsonpage;
                $recordNumber = 1;
                while ($recordNumber <= $numberOfRecords) {
                    $arguments = [
                        'domain-name'   => $domain,
                        'host'          => $result->$recordNumber->host,
                        'value'         => $result->$recordNumber->value
                    ];
                    $this->makePostRequest($this->getAddDNSURL($type, 'delete'), $arguments);
                    $recordNumber++;
                }
            }
        }

        $errors = [];
        // re-add all that we are supplied with
        foreach ($params['records'] as $record) {
            $value = str_replace('&#34;', '', $record['address']);
            $arguments = array(
                'domain-name'           => $domain,
                'value'                 => $value,
                'host'                  => $record['hostname'],
                'ttl'                   => 14400
            );

            $result = $this->makePostRequest($this->getAddDNSURL($record['type'], 'add'), $arguments);
            if (strtolower($result->status) == 'failed') {
                $errors[] = "Error adding {$record['type']} record for\n {$record['hostname']} ($value):\n " . $result->msg;
            }
        }
        if (count($errors) > 0) {
            throw new \CE_Exception(implode("\n\n", $errors));
        }
    }

    private function getAddDNSURL($type, $action)
    {
        switch ($type) {
            case 'A':
                return "/dns/manage/{$action}-ipv4-record";
            case 'AAAA':
                return "/dns/manage/{$action}-ipv6-record";
            case 'MX':
                return "/dns/manage/{$action}-mx-record";
            case 'CNAME':
                return "/dns/manage/{$action}-cname-record";
            case 'TXT':
                return "/dns/manage/{$action}-txt-record";
        }
    }

    public function getDNS($params, $recordTypes)
    {
        $records = [];
        foreach ($recordTypes as $type) {
            $result = $this->searchHosts($params, $type);
            if ($result->recsonpage > 0) {
                $numberOfRecords = $result->recsonpage;
                $recordNumber = 1;
                while ($recordNumber <= $numberOfRecords) {
                    $records[] = [
                        'id' => count($records) + 1,
                        'hostname' => $result->$recordNumber->host,
                        'address' => $result->$recordNumber->value,
                        'type' => $result->$recordNumber->type
                    ];
                    $recordNumber++;
                }
            }
        }
        return $records;
    }

    private function searchHosts($params, $type)
    {
        $params['sld'] = strtolower($params['sld']);
        $params['tld'] = strtolower($params['tld']);
        $domain = $params['sld'] . "." . $params['tld'];

        $arguments = [
            'domain-name' => $domain,
            'type' => $type,
            'no-of-records' => '50',
            'page-no' => '1'
        ];
        $result = $this->makeGetRequest('/dns/manage/search-records', $arguments);
        if ($result === false) {
            throw new \Exception('A connection issued occurred while connecting.', EXCEPTION_CODE_CONNECTION_ISSUE);
        } elseif (strtolower($result->status) == 'error' && $result->message == 'You need to activate your FREE DNS Services before you can perform this action') {
            if ($this->activateDNSService($params) === true) {
                return $this->searchHosts($params, $type);
            }
        }
        return $result;
    }

    private function activateDNSService($params)
    {
        $params['sld'] = strtolower($params['sld']);
        $params['tld'] = strtolower($params['tld']);
        $domain = $params['sld'] . "." . $params['tld'];

        $orderId = $this->lookupDomainId($domain);
        $arguments = ['order-id' => $orderId];
        $result = $this->makePostRequest('/dns/activate', $arguments);
        if ($result === false) {
            throw new \Exception('A connection issued occurred while connecting.', EXCEPTION_CODE_CONNECTION_ISSUE);
        }

        if (strtolower($result->status) == 'success') {
            return true;
        }
        return false;
    }


    public function setRegistrarLock($params)
    {
        $params['sld'] = strtolower($params['sld']);
        $params['tld'] = strtolower($params['tld']);
        $domain = $params['sld'] . "." . $params['tld'];

        $domainId = $this->lookupDomainId($domain);

        $arguments = [
            'order-id'      => $domainId,
        ];

        if ($params['lock'] == true) {
            $url = '/domains/enable-theft-protection';
        } else {
            $url = '/domains/disable-theft-protection';
        }
        $result = $this->makePostRequest($url, $arguments);
        if ($result === false) {
            throw new \Exception('A connection issued occurred while connecting.');
        }

        if (isset($result->status) && $result->status == 'Success') {
            return;
        } elseif (isset($result->status) && $result->status == 'ERROR') {
            \CE_Lib::log(4, 'ERROR: Domain registrar lock failed: ' . $result->message);
            throw new \Exception('Error Domain registrar lock failed: ' . $result->message);
        } else {
            \CE_Lib::log(4, 'ERROR: Domain registrar lock failed');
            throw new Exception('Error Domain registrar lock failed');
        }
    }

    public function getRegistrarLock($params)
    {
        $params['sld'] = strtolower($params['sld']);
        $params['tld'] = strtolower($params['tld']);
        $domain = $params['sld'] . "." . $params['tld'];

        $domainId = $this->lookupDomainId($domain);

        $arguments = [
            'order-id' => $domainId
        ];

        $result = $this->makeGetRequest('/domains/locks', $arguments);
        if ($result === false) {
            throw new \Exception('A connection issued occurred while connecting.');
        }

        if (isset($result->transferlock)) {
            // transfer lock is enabled
            return $result->transferlock;
        } elseif (isset($result->status) && $result->status == 'ERROR') {
            \CE_Lib::log(4, 'ERROR: Domain registrar lock fetch failed with error: ' . $result->message);
            throw new \Exception('Error fetching domain registrar lock: ' . $result->message);
        } else {
            // empty result, means it's disabled
            return false;
        }
    }

    public function togglePrivacy($params)
    {
        $params['sld'] = strtolower($params['sld']);
        $params['tld'] = strtolower($params['tld']);
        $domain = $params['sld'] . "." . $params['tld'];

        $domainId = $this->lookupDomainId($domain);
        if (is_a($domainId, 'CE_Error')) {
            throw new \Exception($domainId, EXCEPTION_CODE_CONNECTION_ISSUE);
        }

        $arguments = [
            'order-id' => $domainId,
            'options' => array('All'),
        ];

        $result = $this->_makeGetRequest('/domains/details', $arguments);

        if ($result === false) {
            throw new \Exception('A connection issued occurred while connecting.', EXCEPTION_CODE_CONNECTION_ISSUE);
        }
        if (isset($result->isprivacyprotected)) {
            $privacyStatus = $result->isprivacyprotected;

            if ($privacyStatus == 'false') {
                $toggled = 'true';
            } else {
                $toggled = 'false';
            }

            $arguments = [
                'order-id' => $domainId,
                'protect-privacy' => $toggled,
                'reason' => 'Requested by admin in Clientexec'
            ];

            $result = $this->makePostRequest('/domains/modify-privacy-protection', $arguments);
            if ($result === false) {
                throw new Exception('A connection issued occurred while connecting.', EXCEPTION_CODE_CONNECTION_ISSUE);
            }
            if (isset($result->status) && strtolower($result->status) == 'error') {
                \CE_Lib::log(4, 'Error toggling privacy protection: ' . $result->message);
                throw new \Exception('Error toggling privacy protection: ' . $result->message);
            } elseif (isset($result->actionstatus) && strtolower($result->actionstatus) == 'success') {
                if ($toggled == 'true') {
                    return 'on';
                } else {
                    return 'off';
                }
            } else {
                \CE_Lib::log(4, 'Error toggling privacy protection: Unknown Reason');
                throw new \Exception('Error toggling privacy protection.');
            }
        } elseif (isset($result->status) && $result->status == 'ERROR') {
            \CE_Lib::log(4, 'ERROR: Domain details fetch failed with error: ' . $result->message);
            throw new \Exception('Error fetching domain details.: ' . $result->message);
        } else {
            \CE_Lib::log(4, 'ERROR: Domain details fetch failed with error');
            throw new \Exception('Error fetching domain details.');
        }
    }

    public function fetchDomains($params)
    {
        $page = 1;
        if ($params['next'] > 25) {
            $page = ceil($params['next'] / 25);
        }

        $arguments = [
            'no-of-records' => 100,
            'page-no' => $page,
            'status' => 'Active',
            'order-by' => 'domainname',
        ];

        $result = $this->makeGetRequest('/domains/search', $arguments);

        if ($result === false) {
            throw new Exception('A connection issued occurred while connecting.');
        }
        if (isset($result->status) && $result->status == 'ERROR') {
            \CE_Lib::log(4, 'ERROR: Domain search failed with error: ' . $result->message);
            throw new \Exception('Error during domain search command: ' . $result->message);
        } elseif (!isset($result->recsonpage)) {
            \CE_Lib::log(4, 'ERROR: Domain search failed with an error.');
            throw new Exception('Error during domain search command.');
        }
        return $result;
    }

    public function setNameServers($params)
    {
        $params['sld'] = strtolower($params['sld']);
        $params['tld'] = strtolower($params['tld']);
        $domain = $params['sld'] . "." . $params['tld'];

        $domainId = $this->lookupDomainId($domain);
        if (is_a($domainId, 'CE_Error')) {
            return $domainId;
        }

        $ns = [];
        foreach ($params['ns'] as $value) {
            $ns[] = $value;
        }

        $arguments = [
            'order-id' => $domainId,
            'ns' => $ns,
        ];

        $result = $this->makePostRequest('/domains/modify-ns', $arguments);

        if ($result === false) {
            throw new Exception('A connection issued occurred while connecting.');
        }

        if (isset($result->actionstatus) && $result->actionstatus == 'Success') {
            return;
        }

        if (isset($result->status) && $result->status == 'ERROR') {
            if ($result->message == 'Same value for new and old NameServers.') {
                \CE_Lib::log(4, 'Modify name servers for domain ' . $domain . ' resulted in no changes.');
                return;
            }
            \CE_Lib::log(4, 'ERROR: Modify name servers failed with error: ' . $result->message);
            throw new \CE_Exception('Error during modify name servers command: ' . $result->message);
        } else {
            \CE_Lib::log(4, 'ERROR: Modify name servers failed with error');
            throw new \CE_Exception('Error during modify name servers command.');
        }
        \CE_Lib::log(4, 'Modify name servers for domain ' . $domain . ' has been completed successfully.');
    }

    public function getNameServers($params)
    {
        $params['sld'] = strtolower($params['sld']);
        $params['tld'] = strtolower($params['tld']);
        $domain = $params['sld'] . "." . $params['tld'];

        $domainId = $this->lookupDomainId($domain);
        if (is_a($domainId, 'CE_Error')) {
            return $domainId;
        }

        $arguments = [
            'order-id' => $domainId,
            'options' => 'NsDetails'
        ];

        $result = $this->makeGetRequest('/domains/details', $arguments);

        if ($result === false) {
            throw new \Exception('A connection issued occurred while connecting.');
        }
        if (isset($result->classname)) {
            $i = 1;
            $ns = [];
            // There are no such thing as "default" name servers, with reseller club
            // Each reseller has their own branded name servers they must use.
            $ns['usesDefault'] = false;
            $ns['hasDefault'] = 0;
            $current = 'ns' . $i;
            while (isset($result->$current)) {
                $ns[] = $result->$current;
                $current = 'ns' . ++$i;
            }
            return $ns;
        }
        if (isset($result->status) && $result->status == 'ERROR') {
            \CE_Lib::log(4, 'ERROR: Get name servers failed with error: ' . $result->message);
            throw new \Exception('Error fetching name servers.: ' . $result->message);
        } else {
            \CE_Lib::log(4, 'ERROR: Get name servers failed with error');
            throw new \Exception('Error fetching name servers.');
        }
    }

    public function setContactInformation($params)
    {
        $params['sld'] = strtolower($params['sld']);
        $params['tld'] = strtolower($params['tld']);
        $domain = $params['sld'] . "." . $params['tld'];
        $telno = $this->validatePhone($params['Registrant_Phone'], $params['countrycode']);

        $domainId = $this->lookupDomainId($domain);
        $customerId = $this->lookupCustomerId($params['Registrant_EmailAddress']);
        if ($customerId === false) {
            $arguments = array(
                'username'              => $params['Registrant_EmailAddress'],
                'passwd'                => \CE_Lib::generatePassword(),
                'name'                  => $params['Registrant_FirstName'] . " " . $params['Registrant_LastName'],
                'company'               => $params['Registrant_OrganizationName'],
                'address-line-1'        => $params['Registrant_Address1'],
                'city'                  => $params['Registrant_City'],
                'state'                 => $params['Registrant_StateProv'],
                'country'               => $params['Registrant_Country'],
                'zipcode'               => strtoupper($params['Registrant_PostalCode']),
                'phone-cc'              => $params['countrycode'],
                'phone'                 => $telno,
                'lang-pref'             => 'en'
            );
            $result = $this->makePostRequest('/customers/signup', $arguments);

            if (is_numeric($result)) {
                $customerId = $result;
            } elseif (isset($result->status) && $result->status == 'ERROR') {
                \CE_Lib::log(4, 'Error creating ResellerClub customer: ' . $result->message);
                throw new \Exception('Error creating ResellerClub customer: ' . $result->message);
            } else {
                \CE_Lib::log(4, 'Error creating ResellerClub customer: Unknown Reason');
                throw new \Exception('Error creating ResellerClub customer.');
            }
        }

        $contactId = 0;
        $arguments = array(
            'name'                  => $params['Registrant_FirstName'] . " " . $params['Registrant_LastName'],
            'company'               => $params['Registrant_OrganizationName'],
            'email'                 => $params['Registrant_EmailAddress'],
            'address-line-1'        => $params['Registrant_Address1'],
            'city'                  => $params['Registrant_City'],
            'state'                 => $params['Registrant_StateProv'],
            'country'               => $params['Registrant_Country'],
            'zipcode'               => $params['Registrant_PostalCode'],
            'phone-cc'              => $params['countrycode'],
            'phone'                 => $telno,
            'customer-id'           => $customerId,
            'type'                  => $this->getContactType($params),
        );
        // Handle any extra attributes needed
        if (isset($params['ExtendedAttributes']) && is_array($params['ExtendedAttributes'])) {
            if ($params['tld'] == 'ca') {
                $arguments['attr-name1'] = 'CPR';
                $arguments['attr-value1'] = $params['ExtendedAttributes']['cira_legal_type'];

                // If Corporation, the name should be blank.
                if ($arguments['attr-value1'] == 'CCO') {
                    $arguments['name'] = $params['RegistrantOrganizationName'];
                    $arguments['company'] = 'N/A';
                }

                $arguments['attr-name2'] = 'AgreementVersion';
                $arguments['attr-value2'] = $params['ExtendedAttributes']['cira_agreement_version'];

                $arguments['attr-name3'] = 'AgreementValue';
                $arguments['attr-value3'] = $params['ExtendedAttributes']['cira_agreement_value'];
            } elseif ($params['tld'] == 'us') {
                $arguments['attr-name1'] = 'purpose';
                $arguments['attr-value1'] = $params['ExtendedAttributes']['us_purpose'];

                $arguments['attr-name2'] = 'category';
                $arguments['attr-value2'] = $params['ExtendedAttributes']['us_nexus'];
            } else {
                $i = 0;
                foreach ($params['ExtendedAttributes'] as $name => $value) {
                    // only pass extended attributes if they have a value.
                    if ($value != '') {
                        $arguments['attr-name' . $i] = $name;
                        $arguments['attr-value' . $i] = $value;
                        $i++;
                    }
                }
            }
        }

        $result = $this->makePostRequest('/contacts/add', $arguments);

        if (is_numeric($result)) {
            \CE_Lib::log(4, 'ResellerClub contact id created with a value of ' . $result);
            $contactId = $result;
        } elseif (isset($result->status) && $result->status == 'ERROR') {
            \CE_Lib::log(4, 'ERROR: ResellerClub customer contact creation failed with error: ' . $result->message);
            throw new \CE_Exception('Error creating ResellerClub customer contact: ' . $result->message);
        } else {
            \CE_Lib::log(4, 'ERROR: ResellerClub customer contact creation failed: Unknown Reason.');
            throw new \Exception('Error creating ResellerClub customer contact.');
        }

        $arguments = [
            'order-id' => $domainId,
            'reg-contact-id' => $contactId,
            'admin-contact-id'      => $this->getAdminContactId($params['tld'], $contactId),
            'tech-contact-id'       => $this->getTechContactId($params['tld'], $contactId),
            'billing-contact-id'    => $this->getBillingContactId($params['tld'], $contactId),
        ];

        $result = $this->makePostRequest('/domains/modify-contact', $arguments);
    }

    public function getContactInformation($params)
    {
        $params['sld'] = strtolower($params['sld']);
        $params['tld'] = strtolower($params['tld']);
        $domain = $params['sld'] . "." . $params['tld'];

        $domainId = $this->lookupDomainId($domain);
        if (is_a($domainId, 'CE_Error')) {
            return $domainId;
        }

        $arguments = [
            'order-id' => $domainId,
            'options' => 'RegistrantContactDetails'
        ];

        $result = $this->makeGetRequest('/domains/details', $arguments);

        if ($result === false) {
            throw new \Exception('A connection issued occurred while connecting.');
        }
        if (isset($result->registrantcontact)) {
            $name = explode(' ', $result->registrantcontact->name, 2);

            $info = [];
            // some info might not be available when the privacy protection is enabled for the domain
            $info['Registrant']['OrganizationName']  = array($this->user->lang('Organization'), $result->registrantcontact->company);
            $info['Registrant']['FirstName'] = array($this->user->lang('First Name'), $name[0]);
            $info['Registrant']['LastName'] = array($this->user->lang('Last Name'), isset($name[1]) ? $name[1] : '');
            $info['Registrant']['Address1']  = array($this->user->lang('Address') . ' 1', $result->registrantcontact->address1);
            $info['Registrant']['Address2']  = array($this->user->lang('Address') . ' 2', isset($result->registrantcontact->address2) ? $result->registrantcontact->address2 : '');
            $info['Registrant']['Address3']  = array($this->user->lang('Address') . ' 3', isset($result->registrantcontact->address3) ? $result->registrantcontact->address3 : '');
            $info['Registrant']['City']      = array($this->user->lang('City'), $result->registrantcontact->city);
            $info['Registrant']['StateProv']  = array($this->user->lang('Province') . '/' . $this->user->lang('State'), isset($result->registrantcontact->state) ? $result->registrantcontact->state : '');
            $info['Registrant']['Country']   = array($this->user->lang('Country'), $result->registrantcontact->country);
            $info['Registrant']['PostalCode']  = array($this->user->lang('Postal Code') . '/' . $this->user->lang('Zip'), $result->registrantcontact->zip);
            $info['Registrant']['EmailAddress']     = array($this->user->lang('E-mail'), $result->registrantcontact->emailaddr);
            $info['Registrant']['Phone']  = array($this->user->lang('Phone Country Code'), $result->registrantcontact->telnocc . $result->registrantcontact->telno);

            return $info;
        }
        if (isset($result->status) && $result->status == 'ERROR') {
            \CE_Lib::log(4, 'ERROR: Domain registrant contact fetch failed with error: ' . $result->message);
            throw new \Exception('Error fetching domain registrant details.: ' . $result->message);
        } else {
            \CE_Lib::log(4, 'ERROR: Domain registrant contact fetch failed with error');
            throw new \Exception('Error fetching domain registrant details.');
        }
    }

    public function getTransferStatus($params, $orderId)
    {
        $arguments = [
            'order-id' => $orderId,
            'no-of-records' => 1,
            'page-no' => 1,
        ];

        $result = $this->makeGetRequest('/actions/search-current', $arguments);

        if ($result === false) {
            throw new \Exception('Error transfering domain: A communication problem occurred.');
        }
        if (isset($result->status) && strtolower($result->status) == 'error') {
            // If there's an error, we need to search the archived section now.
            $arguments = [
                'order-id' => $orderId,
                'no-of-records' => 1,
                'page-no' => 1,
            ];

            $result = $this->makeGetRequest('/actions/search-archived', $arguments);
            if ($result === false) {
                throw new \Exception('Error transfering domain: A communication problem occurred.');
            }
            if (isset($result->status) && strtolower($result->status) == 'error') {
                \CE_Lib::log(4, 'ERROR: Domain transfer failed with error: ' . $result->error);
                throw new \Exception('Error transfering domain: ' . $result->error);
            } elseif (isset($result->status) && strtolower($result->status) == 'failed') {
                \CE_Lib::log(4, 'ERROR: ResellerClub domain transfer failed with error: ' . $result->actiontypedesc);
                throw new \Exception('Error transfering ResellerClub domain: ' . $result->actiontypedesc);
            }
            $status = $result->{1}->actionstatusdesc;
            return $status;
        } elseif (isset($result->status) && strtolower($result->status) == 'failed') {
            \CE_Lib::log(4, 'ERROR: ResellerClub domain transfer failed with error: ' . $result->actiontypedesc);
            throw new \Exception('Error transfering ResellerClub domain: ' . $result->actiontypedesc);
        }
        $status = $result->{1}->actionstatusdesc;
        return $status;
    }

    public function initiateTransfer($params)
    {
        if ($params['eppCode'] == '') {
            throw new \Exception('Can not start transfer with no EPP Code.');
        }

        $contactType = $this->getContactType($params);

        $newCustomer = false;
        $telno = $this->validatePhone($params['RegistrantPhone'], $params['countrycode']);
        if ($params['RegistrantOrganizationName'] == "") {
            $params['RegistrantOrganizationName'] = "N/A";
        }
        $customerId = $this->lookupCustomerId($params['RegistrantEmailAddress']);
        if (is_a($customerId, 'CE_Error')) {
            \CE_Lib::log(4, 'Error creating customer: ' . $customerId->getMessage());
            throw new \Exception('Error creating customer: ' . $customerId->getMessage());
        }
        if ($customerId === false) {
            // Customer doesn't already exist so create one.
            $newCustomer = true;

            $arguments = array(
                'username'              => $params['RegistrantEmailAddress'],
                'passwd'                => substr($params['DomainPassword'], 0, 15),
                'name'                  => $params['RegistrantFirstName'] . " " . $params['RegistrantLastName'],
                'company'               => $params['RegistrantOrganizationName'],
                'address-line-1'        => $params['RegistrantAddress1'],
                'city'                  => $params['RegistrantCity'],
                'state'                 => $params['RegistrantStateProvince'],
                'country'               => $params['RegistrantCountry'],
                'zipcode'               => strtoupper($params['RegistrantPostalCode']),
                'phone-cc'              => $params['countrycode'],
                'phone'                 => $telno,
                'lang-pref'             => 'en'
            );
            $result = $this->makePostRequest('/customers/signup', $arguments);

            if (is_numeric($result)) {
                $customerId = $result;
            } elseif (isset($result->status) && $result->status == 'ERROR') {
                \CE_Lib::log(4, 'Error creating ResellerClub customer: ' . $result->message);
                throw new \Exception('Error creating ResellerClub customer: ' . $result->message);
            } else {
                \CE_Lib::log(4, 'Error creating ResellerClub customer: Unknown Reason');
                throw new \Exception('Error creating ResellerClub customer.');
            }
        }

        $contactId = 0;
        $arguments = array(
            'name'                  => $params['RegistrantFirstName'] . " " . $params['RegistrantLastName'],
            'company'               => $params['RegistrantOrganizationName'],
            'email'                 => $params['RegistrantEmailAddress'],
            'address-line-1'        => $params['RegistrantAddress1'],
            'city'                  => $params['RegistrantCity'],
            'state'                 => $params['RegistrantStateProvince'],
            'country'               => $params['RegistrantCountry'],
            'zipcode'               => $params['RegistrantPostalCode'],
            'phone-cc'              => $params['countrycode'],
            'phone'                 => $telno,
            'customer-id'           => $customerId,
            'type'                  => $contactType,
        );

        if ($params['tld'] == 'ca') {
            $arguments['attr-name1'] = 'CPR';
            $arguments['attr-value1'] = $params['ExtendedAttributes']['cira_legal_type'];

            $arguments['attr-name2'] = 'AgreementVersion';
            $arguments['attr-value2'] = $params['ExtendedAttributes']['cira_agreement_version'];

            $arguments['attr-name3'] = 'AgreementValue';
            $arguments['attr-value3'] = $params['ExtendedAttributes']['cira_agreement_value'];
        } elseif ($params['tld'] == 'us') {
            $arguments['attr-name1'] = 'purpose';
            $arguments['attr-value1'] = $params['ExtendedAttributes']['us_purpose'];

            $arguments['attr-name2'] = 'category';
            $arguments['attr-value2'] = $params['ExtendedAttributes']['us_nexus'];
        } else {
            // Handle any extra attributes needed
            if (isset($params['ExtendedAttributes']) && is_array($params['ExtendedAttributes'])) {
                $i = 1;
                foreach ($params['ExtendedAttributes'] as $name => $value) {
                    // only pass extended attributes if they have a value.
                    if ($value != '') {
                        $arguments['attr-name' . $i] = $name;
                        $arguments['attr-value' . $i] = $value;
                        $i++;
                    }
                }
            }
        }

        $result = $this->makePostRequest('/contacts/add', $arguments);

        if (is_numeric($result)) {
            \CE_Lib::log(4, 'ResellerClub contact id created (or retrieved) with a value of ' . $result);
            $contactId = $result;
        } elseif (isset($result->status) && $result->status == 'ERROR') {
            \CE_Lib::log(4, 'ERROR: ResellerClub customer contact creation failed with error: ' . $result->message);
            throw new \Exception('Error creating ResellerClub customer contact: ' . $result->message);
        } else {
            \CE_Lib::log(4, 'ERROR: ResellerClub customer contact creation failed: Unknown Reason.');
            throw new \Exception('Error creating ResellerClub customer contact.');
        }

        // Finally, it's time to actualy transfer the domain.
        $domain = $params['sld'] . "." . $params['tld'];

        $arguments = array(
            'domain-name'           => $domain,
            'customer-id'           => $customerId,
            'reg-contact-id'        => $contactId,
            'admin-contact-id'      => $this->getAdminContactId($params['tld'], $contactId),
            'tech-contact-id'       => $this->getTechContactId($params['tld'], $contactId),
            'billing-contact-id'    => $this->getBillingContactId($params['tld'], $contactId),
            'invoice-option'        => 'NoInvoice',
            'protect-privacy'       => false, // needs support in the future
            'auth-code'             => $params['eppCode']
        );

        $result = $this->makePostRequest('/domains/transfer', $arguments);

        if ($result === false) {
            // Already logged
            throw new \Exception('Error transfering domain: A communication problem occurred.');
        }

        if (isset($result->status) && strtolower($result->status) == 'adminapproved' || strtolower($result->status) == 'success') {
            \CE_Lib::log(4, 'Domain transfer of ' . $domain . ' successful.  EntityId: ' . $result->entityid);
            return $result->entityid;
        } elseif (isset($result->status) && strtolower($result->status) == 'error') {
            \CE_Lib::log(4, 'ERROR: Domain transfer failed with error: ' . $result->error);
            throw new \CE_Exception('Error transfering domain: ' . $result->error);
        } elseif (isset($result->status) && strtolower($result->status) == 'failed') {
            \CE_Lib::log(4, 'ERROR: Domain transfer failed with error: ' . $result->actiontypedesc);
            throw new \CE_Exception('Error transfering domain: ' . $result->actiontypedesc);
        } else {
            \CE_Lib::log(4, 'ERROR: Domain transfer failed with error: Unknown Reason.');
            throw new \CE_Exception('Error transfering domain.');
        }
    }

    public function getGeneralInfo($params)
    {
        $params['sld'] = strtolower($params['sld']);
        $params['tld'] = strtolower($params['tld']);
        $domain = $params['sld'] . "." . $params['tld'];

        $domainId = $this->lookupDomainId($domain);
        if (is_a($domainId, 'CE_Error')) {
            throw new \Exception($domainId, EXCEPTION_CODE_CONNECTION_ISSUE);
        }

        $arguments = [
            'order-id' => $domainId,
            'options' => ['OrderDetails', 'DomainStatus'],
        ];

        $result = $this->makeGetRequest('/domains/details', $arguments);

        if ($result === false) {
            throw new \Exception('A connection issued occurred while connecting to ResellerClub.', EXCEPTION_CODE_CONNECTION_ISSUE);
        }
        if (isset($result->orderid)) {
            $data = [
                'endtime' => $result->endtime,
                'expiration' => date('m/d/Y', $result->endtime),
                'domain' => $result->domainname,
                'id' => $result->orderid,
                'registrationstatus' => isset($result->orderstatus[0]) ? $result->orderstatus[0] : $this->user->lang('Registered'),
                'purchasestatus' => isset($result->domainstatus[0]) ? $result->domainstatus[0] : $this->user->lang('Unable to Obtain'),
                'autorenew' => $result->isOrderSuspendedUponExpiry == 'false' ? false : true,
                'domsecret' => $result->domsecret
            ];
            return $data;
        }
        if (isset($result->status) && $result->status == 'ERROR') {
            \CE_Lib::log(4, 'ERROR: Domain details fetch failed with error: ' . $result->message);
            if ($result->message == 'Access Denied: You are not authorized to perform this action') {
                throw new \Exception($result->message, EXCEPTION_CODE_CONNECTION_ISSUE);
            }
            throw new \Exception('Error fetching domain details: ' . $result->message);
        } else {
            \CE_Lib::log(4, 'ERROR: Domain details fetch failed with error');
            throw new \Exception('Error fetching domain details.');
        }
    }

    public function renewDomain($params)
    {
        $domain = $params['sld'] . "." . $params['tld'];

        $generalInformation = $this->getGeneralInfo($params);

        $arguments = [
            'order-id' => $generalInformation['id'],
            'years' => $params['NumYears'],
            'exp-date' => $generalInformation['endtime'],
            'invoice-option' => 'NoInvoice'
        ];

        $result = $this->makePostRequest('/domains/renew', $arguments);

        if ($result === false) {
            // Already logged
            throw new \Exception('Error registering domain: A communication problem occurred.');
        }
        if (isset($result->status) && $result->status == 'Success') {
            \CE_Lib::log(4, 'Domain renewal of ' . $domain . ' successful.  EntityId: ' . $result->entityid);
            return [1, [$result->entityid]];
        }
        if (isset($result->status) && $result->status == 'ERROR') {
            \CE_Lib::log(4, 'ERROR: Domain renewal failed with error: ' . $result->message);
            throw new \CE_Exception('Error renewing domain: ' . $result->message);
        } else {
            \CE_Lib::log(4, 'ERROR: Domain renewal failed with error: Unknown Reason.');
            throw new \Exception('Error renewing domain.');
        }
    }

    public function registerDomain($params)
    {

        $contactType = $this->getContactType($params);

        $newCustomer = false;
        $telno = $this->validatePhone($params['RegistrantPhone'], $params['countrycode']);
        if ($params['RegistrantOrganizationName'] == "") {
            $params['RegistrantOrganizationName'] = "N/A";
        }
        $customerId = $this->lookupCustomerId($params['RegistrantEmailAddress']);
        if (is_a($customerId, 'CE_Error')) {
            \CE_Lib::log(4, 'Error creating ResellerClub customer: ' . $customerId->getMessage());
            throw new \CE_Exception('Error creating ResellerClub customer: ' . $customerId->getMessage());
        }
        if ($customerId === false) {
            // Customer doesn't already exist so create one.
            $newCustomer = true;

            $arguments = array(
                'username'              => $params['RegistrantEmailAddress'],
                'passwd'                => substr($params['DomainPassword'], 0, 15),
                'name'                  => $params['RegistrantFirstName'] . " " . $params['RegistrantLastName'],
                'company'               => $params['RegistrantOrganizationName'],
                'address-line-1'        => $params['RegistrantAddress1'],
                'city'                  => $params['RegistrantCity'],
                'state'                 => $params['RegistrantStateProvince'],
                'country'               => $params['RegistrantCountry'],
                'zipcode'               => strtoupper($params['RegistrantPostalCode']),
                'phone-cc'              => $params['countrycode'],
                'phone'                 => $telno,
                'lang-pref'             => 'en'
            );
            $result = $this->makePostRequest('/customers/signup', $arguments);

            if (is_numeric($result)) {
                $customerId = $result;
            } elseif (isset($result->status) && $result->status == 'ERROR') {
                \CE_Lib::log(4, 'Error creating ResellerClub customer: ' . $result->message);
                throw new \CE_Exception('Error creating ResellerClub customer: ' . $result->message);
            } else {
                \CE_Lib::log(4, 'Error creating ResellerClub customer: Unknown Reason');
                throw new \Exception('Error creating ResellerClub customer.');
            }
        }

        $contactId = 0;
        $arguments = array(
            'name'                  => $params['RegistrantFirstName'] . " " . $params['RegistrantLastName'],
            'company'               => $params['RegistrantOrganizationName'],
            'email'                 => $params['RegistrantEmailAddress'],
            'address-line-1'        => $params['RegistrantAddress1'],
            'city'                  => $params['RegistrantCity'],
            'state'                 => $params['RegistrantStateProvince'],
            'country'               => $params['RegistrantCountry'],
            'zipcode'               => $params['RegistrantPostalCode'],
            'phone-cc'              => $params['countrycode'],
            'phone'                 => $telno,
            'customer-id'           => $customerId,
            'type'                  => $contactType,
        );
        // Handle any extra attributes needed
        if (isset($params['ExtendedAttributes']) && is_array($params['ExtendedAttributes'])) {
            if ($params['tld'] == 'ca') {
                $arguments['attr-name1'] = 'CPR';
                $arguments['attr-value1'] = $params['ExtendedAttributes']['cira_legal_type'];

                // If Corporation, the name should be blank.
                if ($arguments['attr-value1'] == 'CCO') {
                    $arguments['name'] = $params['RegistrantOrganizationName'];
                    $arguments['company'] = 'N/A';
                }

                $arguments['attr-name2'] = 'AgreementVersion';
                $arguments['attr-value2'] = $params['ExtendedAttributes']['cira_agreement_version'];

                $arguments['attr-name3'] = 'AgreementValue';
                $arguments['attr-value3'] = $params['ExtendedAttributes']['cira_agreement_value'];
            } elseif ($params['tld'] == 'us') {
                $arguments['attr-name1'] = 'purpose';
                $arguments['attr-value1'] = $params['ExtendedAttributes']['us_purpose'];

                $arguments['attr-name2'] = 'category';
                $arguments['attr-value2'] = $params['ExtendedAttributes']['us_nexus'];
            } else {
                $i = 0;
                foreach ($params['ExtendedAttributes'] as $name => $value) {
                    // only pass extended attributes if they have a value.
                    if ($value != '') {
                        $arguments['attr-name' . $i] = $name;
                        $arguments['attr-value' . $i] = $value;
                        $i++;
                    }
                }
            }
        }

        $result = $this->makePostRequest('/contacts/add', $arguments);

        if (is_numeric($result)) {
            \CE_Lib::log(4, 'ResellerClub contact id created (or retrieved) with a value of ' . $result);
            $contactId = $result;
        } elseif (isset($result->status) && $result->status == 'ERROR') {
            \CE_Lib::log(4, 'ERROR: ResellerClub customer contact creation failed with error: ' . $result->message);
            throw new \CE_Exception('Error creating ResellerClub customer contact: ' . $result->message);
        } else {
            \CE_Lib::log(4, 'ERROR: ResellerClub customer contact creation failed: Unknown Reason.');
            throw new \Exception('Error creating ResellerClub customer contact.');
        }

        // Finally, it's time to actualy register the domain.
        $domain = $params['sld'] . "." . $params['tld'];

        if ($params['Use testing server'] || !isset($params['NS1']) || !isset($params['NS2'])) {
            // Required nameservers for test server
            $nameservers = array(
            'dns1.parking-page.net',
            'dns2.parking-page.net'
            );
        } else {
            for ($i = 1; $i <= 12; $i++) {
                if (isset($params["NS$i"])) {
                    $nameservers[] = $params["NS$i"]['hostname'];
                } else {
                    break;
                }
            }
        }

        $purchasePrivacy = false;
        if (isset($params['package_addons']['IDPROTECT']) && $params['package_addons']['IDPROTECT'] == 1) {
            $purchasePrivacy = true;
        }

        $arguments = array(
        'domain-name'           => $domain,
        'years'                 => $params['NumYears'],
        'ns'                    => $nameservers,
        'customer-id'           => $customerId,
        'reg-contact-id'        => $contactId,
        'admin-contact-id'      => $this->getAdminContactId($params['tld'], $contactId),
        'tech-contact-id'       => $this->getTechContactId($params['tld'], $contactId),
        'billing-contact-id'    => $this->getBillingContactId($params['tld'], $contactId),
        'invoice-option'        => 'NoInvoice',
        'purchase-privacy'      => $purchasePrivacy,
        'protect-privacy'       => $purchasePrivacy
        );

        $result = $this->makePostRequest('/domains/register', $arguments);

        if ($result === false) {
            // Already logged
            throw new \Exception('Error registering ResellerClub domain: A communication problem occurred.');
        }
        if (isset($result->status) && $result->status == 'Success') {
            \CE_Lib::log(4, 'ResellerClub domain registration of ' . $domain . ' successful.  EntityId: ' . $result->entityid);
            return [1, [$result->entityid]];
        }
        if (isset($result->status) && strtolower($result->status) == 'error') {
            if (isset($result->message)) {
                $errorMessage = $result->message;
            }
            if (isset($result->error)) {
                $errorMessage = $result->error;
            }

            \CE_Lib::log(4, 'ERROR: ResellerClub domain registration failed with error: ' . $errorMessage);
            throw new \CE_Exception('Error registering ResellerClub domain: ' . $errorMessage);
        } else {
            \CE_Lib::log(4, 'ERROR: ResellerClub domain registration failed with error: Unknown Reason.');
            throw new \Exception('Error registering ResellerClub domain.');
        }
    }

    private function getContactType($params)
    {
        switch ($params['tld']) {
            case 'ca':
                $contactType = 'CaContact';
                break;
            case 'cn':
                $contactType = 'CnContact';
                break;
            case 'co':
                $contactType = 'CoContact';
                break;
            case 'de':
                $contactType = 'DeContact';
                break;
            case 'es':
                $contactType = 'EsContact';
                break;
            case 'eu':
                $contactType = 'EuContact';
                break;
            case 'ru':
                $contactType = 'RuContact';
                break;
            case 'co.uk':
            case 'org.uk':
            case 'me.uk':
            case 'uk':
                $contactType = 'UkContact';
                break;
            case 'coop':
                $contactType = 'CoopContact';
                break;
            default:
                $contactType = 'Contact';
                break;
        }
        return $contactType;
    }

    public function getPrices($params)
    {
        $tlds = [];

        $result = $this->makeGetRequest('/products/category-keys-mapping');
        foreach ($result->domorder as $order) {
            $mapping[] = $order;
        }

        $result = $this->makeGetRequest('/products/reseller-cost-price');
        foreach ($mapping as $map) {
            $key = array_key_first(get_object_vars($map));
            if (isset($result->$key)) {
                foreach ($this->getTLds($mapping, $key) as $tld) {
                    $tlds[$tld]['pricing']['register'] = $result->$key->addnewdomain->{1};
                    $tlds[$tld]['pricing']['transfer'] = $result->$key->addtransferdomain->{1};
                    $tlds[$tld]['pricing']['renew'] = $result->$key->renewdomain->{1};
                }
            }
        }
        return $tlds;
    }

    private function getTlds($mapping, $key)
    {
        $tlds = [];
        foreach ($mapping as $map) {
            if (isset($map->$key)) {
                foreach ($map->$key as $tld) {
                    if (substr($tld, 0, 4) == 'xn--') {
                        continue;
                    }
                    $tlds[] = $tld;
                }
            }
        }
        return $tlds;
    }

    public function checkDomain($params)
    {
        if (isset($params['namesuggest'])) {
            array_unshift($params['namesuggest'], $params['tld']);
            $tlds = $params['namesuggest'];
            $suggestAlternative = true;
        } else {
            $tlds = $params['tld'];
            $suggestAlternative = false;
        }
        $arguments = [
        'domain-name' => $params['sld'],
        'tlds' => $tlds,
        'suggest-alternative' => $suggestAlternative
        ];

        $domain = strtolower($params['sld'] . '.' . $params['tld']);
        $results = $this->makeGetRequest('/domains/available', $arguments);

        $domains = [];
        if ($results == false) {
            $status = 5;
            $domains[] = array('tld' => $params['tld'], 'domain' => $params['sld'], 'status' => $status);
        } else {
            foreach ($results as $domain => $result) {
                if (isset($result->status) && $result->status == 'ERROR') {
                    \CE_Lib::log(4, 'ERROR: ResellerClub check domain failed with error: ' . $result->message);
                    $status = 2;
                } elseif ($result->status == 'regthroughus' || $result->status == 'regthroughothers') {
                    \CE_Lib::log(4, 'ResellerClub check domain result for domain ' . $domain . ': Registered');
                    $status = 1;
                } elseif ($result->status == 'available') {
                    \CE_Lib::log(4, 'ResellerClub check domain result for domain ' . $domain . ': Available');
                    $status = 0;
                } else {
                    \CE_Lib::log(4, 'ERROR: ResellerClub check domain failed.');
                    $status = 5;
                }

                $domain = $this->splitDomain($domain);
                $domains[] = ['tld' => $domain[1], 'domain' => $domain[0], 'status' => $status];
            }
        }
        return ['result' => $domains];
    }

    public function lookupCustomerId($email)
    {
        $arguments = [
           'username' => $email,
        ];
        $result = $this->makeGetRequest('/customers/details', $arguments);
        if ($result === false) {
            throw new \Exception('A connection issued occurred while connecting to ResellerClub.');
        }

        if (isset($result->customerid) && $result->customerid > 0) {
            \CE_Lib::log(4, 'ResellerClub customer "' . $email . '" already exists: ' . $result->customerid);
            return $result->customerid;
        }
        \CE_Lib::log(4, 'ResellerClub customer "' . $email . '" does not already exist.');
        return false;
    }

    public function lookupDomainId($domain)
    {
        $arguments = [
        'domain-name' => $domain,
        ];
        $result = $this->makeGetRequest('/domains/orderid', $arguments);
        if ($result === false) {
            throw new \Exception('A connection issued occurred while connecting to ResellerClub.', EXCEPTION_CODE_CONNECTION_ISSUE);
        }
        if (is_numeric($result)) {
            \CE_Lib::log(4, 'ResellerClub domain id "' . $result . '" found for domain ' . $domain . '.');
            return $result;
        }
        if (isset($result->status) && $result->status == 'ERROR') {
            if (isset($result->message) && strpos($result->message, 'An unexpected error has occurred') !== false) {
                throw new \Exception('An error occurred while connecting to ResellerClub.  Error: ' . $result->message, EXCEPTION_CODE_CONNECTION_ISSUE);
            }

            if (isset($result->message) && $result->message == 'Required authentication parameter missing') {
                \CE_Lib::log(4, 'ERROR: ResellerClub error occurred while looking up domain id for ' . $domain . '.  Error: ' . $result->message);
                throw new \Exception('An error occurred while connecting to ResellerClub.  Error: ' . $result->message, EXCEPTION_CODE_CONNECTION_ISSUE);
            } elseif (isset($result->message) && strpos($result->message, "Website doesn't exist for") !== false) {
                \CE_Lib::log(4, "ERROR: $domain does not exist at ResellerClub.");
                throw new \Exception("$domain does not exist anymore");
            } else {
                \CE_Lib::log(4, 'ERROR: ResellerClub error occurred while looking up domain id for ' . $domain . '.  Error: ' . $result->message);
                throw new \CE_Exception('An error occurred while connecting to ResellerClub.  Error: ' . $result->message);
            }
        }
        \CE_Lib::log(4, 'ERROR: ResellerClub error occurred while looking up domain id for ' . $domain . '.  Error: Unknown Error.');
        throw new \Exception('An error occurred while connecting to ResellerClub.  Error: Unknown');
    }

    private function makeGetRequest($servlet, $arguments = [])
    {
        return $this->makeRequest($servlet, $arguments, false);
    }

    private function makePostRequest($servlet, $arguments = [])
    {
        return $this->makeRequest($servlet, $arguments, true);
    }
    private function makeRequest($servlet, $arguments, $isPost = false)
    {
        $arguments['auth-userid'] = $this->apiUserId;
        if ($this->apiKey != '') {
            $arguments['api-key'] = $this->apiKey;
        } else {
            $arguments['auth-password'] = $this->apiPassword;
        }

        $request = 'https://';
        if ($this->testMode) {
            $request .= 'test.';
        }
        $request .= 'httpapi.com/api';
        $request .= $servlet . '.json';

        \CE_Lib::log(4, 'Parsing arguments.');

        $data = '';
        foreach ($arguments as $name => $value) {
            $name = urlencode($name);
            if (is_array($value)) {
                // Need to handle arrays
                foreach ($value as $multivalue) {
                    if ($multivalue === true) {
                        $multivalue = 'true';
                    } elseif ($multivalue === false) {
                        $multivalue = 'false';
                    }
                    $data .= $name . '=' . urlencode($multivalue) . '&';
                }
            } else {
                if ($value === true) {
                    $value = 'true';
                } elseif ($value === false) {
                    $value = 'false';
                }
                $data .= $name . '=' . urlencode($value) . '&';
            }
        }

        $data = rtrim($data, '&');

        $postData = false;
        if ($isPost) {
            $postData = $data;
        } else {
            $request .= '?' . $data;
        }

        \CE_Lib::log(4, 'Request: ' . $request);

        $requestType = ($isPost) ? 'POST' : 'GET';
        $response = \NE_Network::curlRequest($this->settings, $request, $postData, false, true, false, $requestType);

        if (is_a($response, 'CE_Error')) {
            \CE_Lib::log(4, 'Error communicating: ' . $response->getMessage());
            throw new \Exception('Error communicating: ' . $response->getMessage());
        } elseif (!$response) {
            \CE_Lib::log(4, 'Error communicating: No response found.');
            throw new \Exception('Error communicating: No response found.');
        }

        return json_decode($response);
    }

    private function validatePhone($phone, $code)
    {
        // strip all non numerical values
        $phone = preg_replace('/[^\d]/', '', $phone);
        // check if code is already there and delete it
        return preg_replace("/^($code)(\\d+)/", '\2', $phone);
    }

    private function getAdminContactId($tld, $contactId)
    {
        switch ($tld) {
            case 'eu':
            case 'nz':
            case 'ru':
            case 'uk':
            case 'co.uk':
            case 'org.uk':
            case 'me.uk':
                return -1;
        }
        return $contactId;
    }

    private function getTechContactId($tld, $contactId)
    {
        // Tech & Admin have the same restrictions.
        return $this->getAdminContactId($tld, $contactId);
    }

    private function getBillingContactId($tld, $contactId)
    {
        switch ($tld) {
            case 'berlin':
            case 'ca':
            case 'eu':
            case 'nl':
            case 'nz':
            case 'ru':
            case 'co.uk':
            case 'org.uk':
            case 'me.uk':
            case 'uk':
                return -1;
        }
        return $contactId;
    }

    private function splitDomain($domain)
    {
        if (($position = mb_strpos($domain, '.')) === false) {
            return array($domain, '');
        }
        return array(mb_substr($domain, 0, $position), mb_substr($domain, $position + 1));
    }
}
