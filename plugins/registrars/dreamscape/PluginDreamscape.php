<?php

require_once 'modules/admin/models/RegistrarPlugin.php';
require_once 'lib/dreamscape_reseller_api.php';

class PluginDreamscape extends RegistrarPlugin
{
    public $features = [
        'nameSuggest' => true,
        'importDomains' => true,
        'importPrices' => true,
    ];

    private $api;

    public function __construct($user)
    {
        parent::__construct($user);

        $this->api = new dreamscape_reseller_api(
            $this->getVariable('Reseller ID'),
            $this->getVariable('API Key'),
            $this->getVariable('Use Testing Server')
        );
    }

    public function getVariables()
    {
        $variables = [
            'Plugin Name' => [
                'type' => 'hidden',
                'description' => 'How CE sees this plugin (not to be confused with the Signup Name)',
                'value' => 'Dreamscape'
            ],
            'Reseller ID' => [
                'type' => 'text',
                'description' => lang('Your Dreamscape Reseller ID'),
                'value' => '',
            ],
            'API Key' => [
                'type' => 'password',
                'description' => lang('Your Dreamscape API Key'),
                'value' => '',
                'encryptable' => true
            ],
            'Use Testing Server' => [
                'type' => 'yesno',
                'description' => lang('Select Yes if you wish to use the Dreamscape testing environment.'),
                'value' => 0
            ],
            'Supported Features' => [
                'type' => 'label',
                'description' => '* '.lang('TLD Lookup').'<br>* '.lang('Domain Registration').' <br>* '.lang('Domain Registration with ID Protect').' <br>* '.lang('Existing Domain Importing').' <br>* '.lang('Get / Set Auto Renew Status').' <br>* '.lang('Get / Set DNS Records').' <br>* '.lang('Get / Set Nameserver Records').' <br>* '.lang('Get / Set Contact Information').' <br>* '.lang('Get / Set Registrar Lock').' <br>* '.lang('Initiate Domain Transfer'),
                'value' => ''
            ],
            'Actions' => [
                'type' => 'hidden',
                'description' => 'Current actions that are active for this plugin (when a domain isn\'t registered)',
                'value' => 'Register'
            ],
            'Registered Actions' =>[
                'type'  => 'hidden',
                'description' => 'Current actions that are active for this plugin (when a domain is registered)',
                'value'       => 'Renew (Renew Domain),DomainTransferWithPopup (Initiate Transfer)',
            ],
            'Registered Actions For Customer' => [
                'type' => 'hidden',
                'description' => 'Current actions that are active for this plugin (when a domain is registered)',
                'value' => '',
            ]
        ];
        return $variables;
    }

    public function getTLDsAndPrices($params)
    {
        $tlds = [];
        $response = $this->api->getDomainPriceList();
        foreach ($response as $product) {
            $tld = $product->Product;
            $price = $product->Price;

            $tlds[$tld]['pricing']['register'] = $price;
            $tlds[$tld]['pricing']['transfer'] = $price;
            $tlds[$tld]['pricing']['renew'] = $price;
        }
        return $tlds;
    }


    public function checkDomain($params)
    {
        $domainNameGateway = new DomainNameGateway($this->user);
        $checkDomains = [];
        $domainName = $params['sld'] . '.' . $params['tld'];

        $checkDomains[$domainName] = [
            'name' => $domainName,
            'sld' => $params['sld'],
            'tld' => $params['tld']
        ];

        if (isset($params['namesuggest']) && count($params['namesuggest']) > 0) {
            foreach ($params['namesuggest'] as $key => $value) {
                $domainName = $params['sld'] . '.' . $value;
                $checkDomains[$domainName] = [
                'name' => $domainName,
                'sld' => $params['sld'],
                'tld' => $value
                ];
            }
        }

        $response = $this->api->domain_check(
            array_column($checkDomains, 'name')
        );

        foreach ($response as $domainName => $r) {
            if ($r['is_available'] == true && $r['is_premium'] != true) {
                $status = 0;
            } else {
                $status = 1;
            }
            list($sld, $tld) = $domainNameGateway->splitDomain($domainName);
            $result = [
                'tld'    => $tld,
                'domain' => $sld,
                'status' => $status
            ];
            $domains[] = $result;
        }

        return ['result' => $domains];
    }

    public function doDomainTransferWithPopup($params)
    {
        $userPackage = new UserPackage($params['userPackageId']);
        $transferId = $this->initiateTransfer($this->buildTransferParams($userPackage, $params));
        $userPackage->setCustomField(
            'Registrar Order Id',
            $userPackage->getCustomField('Registrar') . '-' . $transferId
        );
        $userPackage->setCustomField('Transfer Status', $transferId);
        return "Transfer has been initiated.";
    }

    public function doRegister($params)
    {
        $userPackage = new UserPackage($params['userPackageId']);
        $orderId = $this->registerDomain($this->buildRegisterParams($userPackage, $params));
        $userPackage->setCustomField(
            'Registrar Order Id',
            $userPackage->getCustomField('Registrar') . '-' . $params['userPackageId']
        );
        return $userPackage->getCustomField('Domain Name') . ' has been registered.';
    }

    public function doRenew($params)
    {
        $userPackage = new UserPackage($params['userPackageId']);
        $orderId = $this->renewDomain($this->buildRenewParams($userPackage, $params));
        $userPackage->setCustomField(
            'Registrar Order Id',
            $userPackage->getCustomField('Registrar') . '-' . $params['userPackageId']
        );
        return $userPackage->getCustomField('Domain Name') . ' has been renewed.';
    }

    public function getTransferStatus($params)
    {
        $userPackage = new UserPackage($params['userPackageId']);

        $transferInfo = $this->api->transfer_info($params['sld'] . '.' . $params['tld']);
        if (!$transferInfo) {
            throw new CE_Exception(
                'Error getting transfer status: ' . implode(', ', $this->api->get_errors())
            );
        }

        return $transferInfo->Status;

        // check status at registrar
        // if transfer has completed:
        // $userPackage->setCustomField('Transfer Status', 'Completed');

        // return status string from registrar to show in UI
    }

    public function initiateTransfer($params)
    {
        if (!$this->api->transfer_check($params['sld'] . '.' . $params['tld'], $params['eppCode'])) {
            throw new CE_Exception(
                'Domain not available to transfer: ' . implode(', ', $this->api->get_errors())
            );
        }

        $contactData = [
            'FirstName' => $params['RegistrantFirstName'],
            'LastName' => $params['RegistrantLastName'],
            'Address' => $params['RegistrantAddress1'],
            'City' => $params['RegistrantCity'],
            'Country' => $params['RegistrantCountry'],
            'State' => $params['RegistrantStateProvince'],
            'PostCode' => $params['RegistrantPostalCode'],
            'CountryCode' => $this->getCountryCode($params['RegistrantCountry']),
            'Phone' => $this->validatePhone($params['RegistrantPhone'], $params['RegistrantCountry']),
            'Mobile' => '',
            'Email' => $params['RegistrantEmailAddress'],
            'AccountType' => 'personal',
        ];

        $contactResult = $this->api->contact_create($contactData);
        if (!$contactResult) {
            throw new CE_Exception('Error creating contact: ' . implode(', ', $this->api->get_errors()));
        }
        $contactId = $contactResult->ContactIdentifier;


        $data = [
            'ContactIdentifier' => $contactId,
            'DomainName' => $params['sld'] . '.' . $params['tld'],
            'AuthKey' => $params['eppCode'],
            'RenewalPeriod' => $params['NumYears'],
        ];

        if (!$this->api->transfer_start($data)) {
            if (!$contactResult) {
                throw new CE_Exception('Failed to transfer domain: ' . implode(', ', $this->api->get_errors()));
            }
        }
        return '';
    }

    public function renewDomain($params)
    {
        if (!$this->api->domain_renew($params['sld'] . '.' . $params['tld'], $params['NumYears'])) {
            throw new CE_Exception(
                'Failed to renew domain: ' . implode(', ', $this->api->get_errors())
            );
        }
    }

    public function registerDomain($params)
    {
        $contactData = [
            'FirstName' => $params['RegistrantFirstName'],
            'LastName' => $params['RegistrantLastName'],
            'Address' => $params['RegistrantAddress1'],
            'City' => $params['RegistrantCity'],
            'Country' => $params['RegistrantCountry'],
            'State' => $params['RegistrantStateProvince'],
            'PostCode' => $params['RegistrantPostalCode'],
            'CountryCode' => $this->getCountryCode($params['RegistrantCountry']),
            'Phone' => $this->validatePhone($params['RegistrantPhone'], $params['RegistrantCountry']),
            'Mobile' => '',
            'Email' => $params['RegistrantEmailAddress'],
            'AccountType' => 'personal',
        ];

        $contactResult = $this->api->contact_create($contactData);
        if (!$contactResult) {
            throw new CE_Exception('Error creating contact: ' . implode(', ', $this->api->get_errors()));
        }
        $contactId = $contactResult->ContactIdentifier;

        $registrantData = $this->api->contact_clone_to_registrant($contactId);
        if (!$registrantData) {
            throw new CE_Exception('Error creating registrant: ' . implode(', ', $this->api->get_errors()));
        }
        $registrantId = $registrantData->ContactIdentifier;

        $domainData = [
            'DomainName' => $params['sld'] . '.' . $params['tld'],
            'RegistrantContactIdentifier' => $registrantId,
            'AdminContactIdentifier' => $contactId,
            'BillingContactIdentifier' => $contactId,
            'TechContactIdentifier' => $contactId,
            'RegistrationPeriod' => $params['NumYears'],
        ];

        if (isset($params['NS1'])) {
            for ($i = 1; $i <= 4; $i++) {
                if (isset($params["NS$i"])) {
                    $ip = gethostbyname($params["NS$i"]['hostname']);
                    $domainData['NameServers'][] = [
                        'Host' => $params["NS$i"]['hostname'],
                        'IP' => $ip,
                    ];
                } else {
                    break;
                }
            }
        }
        if (count($domainData['NameServers']) == 0) {
            $domainData['NameServers'][] = [
                'Host' => 'ns1.secureparkme.com',
                'IP' => gethostbyname('ns1.secureparkme.com'),
            ];
            $domainData['NameServers'][] = [
                'Host' => 'ns2.secureparkme.com',
                'IP' => gethostbyname('ns2.secureparkme.com'),
            ];
        }

        $result = $this->api->domain_create($domainData);
        if (!$result) {
            throw new CE_Exception('Error registering domain: ' . implode(', ', $this->api->get_errors()));
        }
        return '';
    }

    public function getContactInformation($params)
    {
        $domainName = $params['sld'] . '.' . $params['tld'];
        $domainInfo = $this->api->domain_info($domainName);
        $contactInfo = $this->api->contact_info($domainInfo->RegistrantContactIdentifier);

        $info = [];
        // only Registrant is supported in UI, but we return all for future releases
        foreach (array('Registrant', 'AuxBilling', 'Admin', 'Tech') as $type) {
            $info[$type]['First Name'] = [
                $this->user->lang('First Name'),
                $contactInfo->FirstName
            ];

            $info[$type]['Last Name'] = [
                $this->user->lang('Last Name'),
                $contactInfo->LastName
            ];

            $info[$type]['Address'] = [
                $this->user->lang('Address'),
                $contactInfo->Address
            ];

            $info[$type]['City'] = [
                $this->user->lang('City'),
                $contactInfo->City
            ];

            $info[$type]['StateProvince'] = [
                $this->user->lang('Province / State'),
                $contactInfo->State
            ];

            $info[$type]['Country'] = [
                $this->user->lang('Country'),
                $contactInfo->Country
            ];

            $info[$type]['PostalCode'] = [
                $this->user->lang('Postal Code'),
                $contactInfo->PostCode
            ];

            $info[$type]['EmailAddress'] = [
                $this->user->lang('E-mail'),
                $contactInfo->Email
            ];

            $info[$type]['Phone'] = [
                $this->user->lang('Phone'),
                $contactInfo->Phone
            ];
        }

        return $info;
    }

    public function setContactInformation($params)
    {
        // update/set contact info at registrar
        $domainName = $params['sld'] . '.' . $params['tld'];
        $domainInfo = $this->api->domain_info($domainName);
        // $contactInfo = $this->api->contact_info();

        $registrantInfo = [];
        $registrantInfo['ContactIdentifier'] = $domainInfo->RegistrantContactIdentifier;
        $registrantInfo['FirstName'] = $params['Registrant_First_Name'];
        $registrantInfo['LastName'] = $params['Registrant_Last_Name'];
        $registrantInfo['Address'] = $params['Registrant_Address'];
        $registrantInfo['City'] = $params['Registrant_City'];
        $registrantInfo['State'] = $params['Registrant_StateProvince'];
        $registrantInfo['Country'] = $params['Registrant_Country'];
        $registrantInfo['PostCode'] = $params['Registrant_PostalCode'];
        $registrantInfo['Email'] = $params['Registrant_EmailAddress'];
        $registrantInfo['Phone'] = $this->validatePhone($params['Registrant_Phone'], $params['Registrant_Country']);

        if (!$this->api->contact_update($registrantInfo)) {
            throw new CE_Exception('Failed to update registrant details: ' . implode(', ', $reseller_api->get_errors()));
        }

        return $this->user->lang('Contact Information updated successfully.');
    }

    public function getNameServers($params)
    {
        $info = [];
        $info['hasDefault'] = false;
        $info['usesDefault'] = false;

        $domainName = $params['sld'] . '.' . $params['tld'];
        $domainInfo = $this->api->domain_info($domainName);

        foreach ($domainInfo->NameServers as $ns) {
            $info[] = $ns->Host;
        }

        return $info;
    }

    public function setNameServers($params)
    {
        $domainName = $params['sld'] . '.' . $params['tld'];
        $domainInfo = $this->api->domain_info($domainName);

        $nameServers = [];
        foreach ($params['ns'] as $key => $value) {
            $nameServers[] = [
                'Host' => $value,
                'IP' => gethostbyname($value),
            ];
        }

        $data = [
            'DomainName' => $domainInfo->DomainName,
            'AdminContactIdentifier' => $domainInfo->AdminContactIdentifier,
            'BillingContactIdentifier' => $domainInfo->BillingContactIdentifier,
            'TechContactIdentifier' => $domainInfo->TechContactIdentifier,
            'NameServers' => $nameServers,
        ];

        $result = $this->api->domain_update($data);

        if (!$result) {
            throw new CE_Exception(
                'Failed to update name servers: ' . implode(', ', $this->api->get_errors())
            );
        }
    }

    public function getGeneralInfo($params)
    {
        $data = [];

        $domainName = $params['sld'] . '.' . $params['tld'];
        $domainInfo = $this->api->domain_info($domainName);

        if (!$domainInfo) {
            foreach ($this->api->get_errors() as $error) {
                if (stripos($error, 'does not belong to this reseller')) {
                    throw new CE_Exception($domainName . ' not in DreamScape account');
                }
            }
        }
        $data['domain'] = $domainName;
        $data['expiration'] = $domainInfo->Expiry;
        $data['registrationstatus'] = $domainInfo->Status;
        $data['purchasestatus'] = 'N/A';

        if ($domainInfo->Status === 'Registered') {
            $data['is_registered'] = true;
            $data['is_expired'] = false;
        } else {
            $data['is_registered'] = false;
            $data['is_expired'] = true;
        }

        return $data;
    }

    public function fetchDomains($params)
    {
        $domainNameGateway = new DomainNameGateway();

        $response = $this->api->getDomainList();
        $domainsList = [];

        foreach ($response as $domain) {
            $split = $domainNameGateway->splitDomain($domain->DomainName);
            $data = [
                'sld' => $split[0],
                'tld' => $split[1],
                'exp' => date('m/d/Y', strtotime($domain->Expiry))
            ];
            $domainsList[] = $data;
        }

        return [$domainsList, []];
    }

    public function getRegistrarLock($params)
    {
        // get registrar lock
        $domainInfo = $this->api->domain_info($params['sld'] . '.' . $params['tld']);
        if ($domainInfo->LockStatus == 'Unlocked') {
            return false;
        }
        return true;
    }

    public function doSetRegistrarLock($params)
    {
        $userPackage = new UserPackage($params['userPackageId']);
        $this->setRegistrarLock($this->buildLockParams($userPackage, $params));
        return "Updated Registrar Lock.";
    }

    public function setRegistrarLock($params)
    {
        $domainInfo = $this->api->domain_info($params['sld'] . '.' . $params['tld']);

        $updateData = [
            'DomainName' => $params['sld'] . '.' . $params['tld'],
            'AdminContactIdentifier' => $domainInfo->AdminContactIdentifier,
            'BillingContactIdentifier' => $domainInfo->BillingContactIdentifier,
            'TechContactIdentifier' => $domainInfo->TechContactIdentifier,
            'LockStatus' => $params['lock'] == 1 ? 'Locked' : 'Unlocked',
        ];

        $result = $this->api->domain_update($updateData);

        if (!$result) {
            throw new CE_Exception('Failed to set registrar lock: ' . implode(', ', $this->api->get_errors()));
        }
    }

    public function getDNS($params)
    {
        $returnRecords = [];
        $domainInfo = $this->api->domain_info($params['sld'] . '.' . $params['tld']);
        $dnsRecords = $domainInfo->DNSRecords;

        foreach ($dnsRecords as $type => $records) {
            foreach ($records as $record) {
                $returnRecord = [
                    'id' => $record->Id,
                    'hostname' => $record->Subdomain,
                    'address' => $record->Content,
                    'type' => $type
                ];
                $returnRecords[] = $returnRecord;
            }
        }

        $types = [
            'A',
            'AAAA',
            'MX',
            'CNAME',
            'TXT',
            'URL',
            'FRAME'
        ];

        return [
            'records' => $returnRecords,
            'types'   => $types,
            'default' => true
        ];
    }

    public function setDNS($params)
    {
        $newDnsRecords = [];

        // set host records
        foreach ($params['records'] as $index => $record) {
            switch ($record['type']) {
                case 'A':
                case 'AAAA':
                case 'CNAME':
                case 'TXT':
                    if ($record['hostname'] == $params['sld'] . '.' . $params['tld'] ||
                        $record['hostname'] == $params['sld'] . '.' . $params['tld'] . '.') {
                        $record['hostname'] = '';
                    }
                    $newDnsRecords[] = [
                        'type' => $record['type'],
                        'data' => [
                            'Subdomain' => $record['hostname'],
                            'Content' => $record['address'],
                        ]
                    ];
                    break;

                case 'MX':
                    $newDnsRecords[] = [
                        'type' => $record['type'],
                        'data' => [
                            'Subdomain' => $record['hostname'],
                            'Content' => $record['address'],
                            // default to 10 until we support this
                            'Priority' => 10
                        ]
                    ];
                    break;

                case 'URL':
                case 'FRAME':
                    $newDnsRecords[] = [
                        'type' => 'WEBFWD',
                        'data' => [
                            'Subdomain' => $record['hostname'],
                            'Content' => $record['address'],
                            'Cloak' => $record['type'] === 'FRAME',
                        ]
                    ];
                    break;
            }
        }

        $dnsRecords = [];
        if (count($newDnsRecords) > 0) {
            foreach ($newDnsRecords as $record) {
                if (!isset($dnsRecords[$record['type']])) {
                    $dnsRecords[$record['type']] = [];
                }
                $dnsRecords[$record['type']][] = $record['data'];
            }
        }

        $result = $this->api->domain_dns_update($params['sld'] . '.' . $params['tld'], $dnsRecords);
        if (!$result) {
            throw new CE_Exception('Failed to update host records: ' . implode(', ', $this->api->get_errors()));
        }
        return $this->user->lang("Host information updated successfully");
    }


    public function getEPPCode($params)
    {
        $domainInfo = $this->api->domain_info($params['sld'] . '.' . $params['tld']);
        return $domainInfo->AuthKey;
    }

    public function sendTransferKey($params)
    {
        throw new Exception('This function is not supported');
    }

    public function disablePrivateRegistration($params)
    {
        throw new Exception('This function is not supported');
    }

    public function setAutorenew($params)
    {
        throw new Exception('This function is not supported');
    }

    private function getCountryCode($country)
    {
        $query = "SELECT phone_code FROM country WHERE iso=? AND phone_code != ''";
        $result = $this->db->query($query, $country);
        $row = $result->fetch();
        $code = $row['phone_code'];
        return $code;
    }

    private function validatePhone($phone, $country)
    {
        // strip all non numerical values
        $phone = preg_replace('/[^\d]/', '', $phone);

        if ($phone == '') {
            return $phone;
        }

        $query = "SELECT phone_code FROM country WHERE iso=? AND phone_code != ''";
        $result = $this->db->query($query, $country);
        if (!$row = $result->fetch()) {
            return $phone;
        }

        // check if code is already there
        $code = $row['phone_code'];
        $phone = preg_replace("/^($code)(\\d+)/", '+\1.\2', $phone);
        if ($phone[0] == '+') {
            return $phone;
        }

        // if not, prepend it
        return "$phone";
    }
}
