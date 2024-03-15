<?php

/**
 * Dreamscape Networks Reseller API
 * 06 July, 2020
 *
 * @copyright Dreamscape Networks International Pte Ltd http://dreamscapenetworks.com
 *
 * @version 1.3.0
 */
class dreamscape_reseller_api
{
    // WARNING: set to FALSE in production
    const DEBUG = false; // if TRUE, all requests will print out XML of request and response, see $this::print_xml()

    // uses this settings if in constructor $is_test == FALSE
    const LIVE_WSDL = 'http://soap.secureapi.com.au/wsdl/API-2.1.wsdl';
    const LIVE_NAMESPACE = 'http://soap.secureapi.com.au/API-2.1';

    // use these settings if $is_test == TRUE in constructor
    const TEST_WSDL = 'http://soap-test.secureapi.com.au/wsdl/API-2.1.wsdl';
    const TEST_NAMESPACE = 'http://soap-test.secureapi.com.au/API-2.1';

    protected $reseller_id; // reseller ID, set in constructor
    protected $reseller_api_key; // reseller API Key, set in constructor
    protected $is_test; // flag sets WSDL endpoint, if TRUE uses test, else LIVE, sets in constructor

    private $errors = array();  // text of errors
    private $last_response = false; // object of last response
    private $api_soap_client = false; // singleton of SoapClient
    private $user_agent = 'Clientexec: Dreamscape Reseller API'; // User Agent header value

    /**
     * Constructor
     *
     * @param string $reseller_id - if omitted uses RESELLER_API_ID global constant
     * @param string $api_key - if omitted uses RESELLER_API_KEY global constant
     * @param bool $is_test - default is test mode
     */
    function __construct($reseller_id = null, $api_key = null, $is_test = true)
    {
        if (empty($reseller_id) && defined('RESELLER_API_ID')) {
            $reseller_id = RESELLER_API_ID;
        }

        if (empty($api_key) && defined('RESELLER_API_KEY')) {
            $api_key = RESELLER_API_KEY;
        }

        $this->reseller_id = $reseller_id;
        $this->reseller_api_key = $api_key;
        $this->is_test = (bool)$is_test;
    }

    /**
     * Returns balance of reseller.
     * Formatted number with currency name, e.g $982.32 NZD, or false if error occurred. See $this->errors().
     *
     * @return string
     */
    public function get_balance()
    {
        $response = $this->call('GetBalance');

        if ($response && isset($response->APIResponse->Balance)) {
            return $response->APIResponse->Balance;
        }

        return false;
    }

    /**
     * Check the availability of several domain names and return array
     * array (
     *     'domain_name_01.com' => array (
     *         'is_available' => true,
     *         'buy_price' => 100,
     *         'renew_price' => buy_price,
     *         'currency_code' => 'USD',
     *         'is_premium' => false,
     *     ),
     *    'domain_name_02.net' => array (
     *         'is_available' => false,
     *         'buy_price' => 100,
     *         'renew_price' => buy_price,
     *         'currency_code' => 'USD',
     *         'is_premium' => false,
     *     ),
     *     ...
     * )
     * If error occurred return false, errors list in $this->get_errors()
     *
     * @param string | array $domain_names - one or several domain names
     *
     * @return array
     */
    public function domain_check($domain_names)
    {
        if (!is_array($domain_names)) {
            $domain_names = [ $domain_names ];
        }

        $request = [
            'DomainNames' => $domain_names,
        ];

        $response = $this->call('DomainCheck', $request);

        if ($response && isset($response->APIResponse->AvailabilityList)) {
            $result = [];

            foreach ($response->APIResponse->AvailabilityList as $item) {
                $result[$item->Item] = [
                    'is_available' => $item->Available == 'true',
                    'buy_price' => $item->Price,
                    'renew_price' => $item->RenewPrice,
                    'currency_code' => $item->CurrencyCode,
                    'is_premium' => $item->IsPremium,
                ];
            }

            return $result;
        }

        return false;
    }

    /**
     * Get information about domain or false if error occurred. See $this->errors().
     *    stdClass Object
     *    (
     *        [DomainName] => example.com
     *        [AuthKey] => xxxxxxxx
     *        [Status] => Registered
     *        [LockStatus] => Locked
     *        [Expiry] => 2019-11-08
     *        [RegistrantContactIdentifier] => R-000000000-SN
     *        [AdminContactIdentifier] => C-000000000-SN
     *        [BillingContactIdentifier] => C-000000000-SN
     *        [TechContactIdentifier] => C-000000000-SN
     *        [NameServers] => Array
     *            (
     *                [0] => stdClass Object
     *                    (
     *                        [Host] => ns1.secureparkme.com
     *                        [IP] => 27.124.125.248
     *                    )
     *
     *                [1] => stdClass Object
     *                    (
     *                        [Host] => ns2.secureparkme.com
     *                        [IP] => 27.124.125.249
     *                    )
     *
     *            )
     *        [DNSRecords] => stdClass Object
     *            (
     *                [A] => Array
     *                    (
     *                        [0] => Array
     *                            (
     *                                [Subdomain] => www
     *                                [Content] => 27.124.125.143
     *                            )
     *                    )
     *                [AAAA] => Array
     *                    (
     *                        [0] => Array
     *                            (
     *                                [Subdomain] => www
     *                                [Content] => 2404:8280:a222:bbbc:bba1:1:ffff:ffff
     *                            )
     *                    )
     *                [CNAME] => Array
     *                    (
     *                        [0] => Array
     *                            (
     *                                [Subdomain] => alias
     *                                [Content] => 27.124.125.143
     *                            )
     *                    )
     *                [MX] => Array
     *                    (
     *                        [0] => Array
     *                            (
     *                                [Subdomain] =>
     *                                [Content] => mail.example.com
     *                                [Priority] => 10
     *                            )
     *                    )
     *                [CAA] => Array
     *                    (
     *                        [0] => Array
     *                            (
     *                                [Flag] => 0
     *                                [Tag] => issue
     *                                [Content] => ca-example.com
     *                            )
     *                    )
     *                [SRV] => Array
     *                    (
     *                        [0] => Array
     *                            (
     *                                [Subdomain] => sipserver
     *                                [Priority] => 0
     *                                [Weight] => 5
     *                                [Port] => 5060
     *                                [Target] => sipserver.example.com
     *                            )
     *                    )
     *                [TXT] => Array
     *                    (
     *                        [0] => Array
     *                            (
     *                                [Subdomain] => key
     *                                [Content] => value
     *                            )
     *                    )
     *                [WEBFWD] => Array
     *                    (
     *                        [0] => Array
     *                            (
     *                                [Subdomain] => subdomain
     *                                [Content] => http://example.com
     *                                [Cloak] => false
     *                            )
     *                    )
     *                [MAILFWD] => Array
     *                    (
     *                        [0] => Array
     *                            (
     *                                [Email] => help
     *                                [Content] => support@example.com
     *                            )
     *                    )
     *            )
     *
     *    )
     *
     * @param string $domain_name
     *
     * @return object DomainInfo
     */
    public function domain_info($domain_name)
    {
        $request = array(
            'DomainName' => $domain_name,
        );

        $response = $this->call('DomainInfo', $request);

        if ($response && isset($response->APIResponse->DomainDetails)) {
            return $response->APIResponse->DomainDetails;
        }

        return false;
    }

    /**
     * Create domain and return domain details the same as in $this->domain_info().
     * Required input parameters:
     * DomainName, RegistrantContactIdentifier, AdminContactIdentifier, BillingContactIdentifier, TechContactIdentifier, RegistrationPeriod
     * Optional:
     * NameServers
     *
     * NameServers is an array with format:
     * array (
     *   array('Host' => 'first nameserver host', 'IP' => 'first name server IP'),
     *   array('Host' => 'second nameserver host', 'IP' => 'second name server IP'),
     *   ...
     * )
     *
     * @param array $domain_data
     *
     * @return DomainInfo
     */
    public function domain_create($domain_data)
    {
        // order fields in request
        $request = array(
            'DomainName' => isset($domain_data['DomainName']) ? $domain_data['DomainName'] : '',
            'RegistrantContactIdentifier' => isset($domain_data['RegistrantContactIdentifier']) ? $domain_data['RegistrantContactIdentifier'] : '',
            'AdminContactIdentifier' => isset($domain_data['AdminContactIdentifier']) ? $domain_data['AdminContactIdentifier'] : '',
            'BillingContactIdentifier' => isset($domain_data['BillingContactIdentifier']) ? $domain_data['BillingContactIdentifier'] : '',
            'TechContactIdentifier' => isset($domain_data['TechContactIdentifier']) ? $domain_data['TechContactIdentifier'] : '',
            'RegistrationPeriod' => isset($domain_data['RegistrationPeriod']) ? $domain_data['RegistrationPeriod'] : '',
            'NameServers' => array(),
        );

        if (isset($domain_data['Eligibility']) && is_array($domain_data['Eligibility'])) {
            $request['Eligibility'] = $domain_data['Eligibility'];
        }

        if (isset($domain_data['NameServers']) && is_array($domain_data['NameServers'])) {
            foreach ($domain_data['NameServers'] as $ns) {
                if (isset($ns['Host']) && isset($ns['IP'])) {
                    $request['NameServers'][] = array('Host' => $ns['Host'], 'IP' => $ns['IP']);
                }
            }
        }

        if (isset($domain_data['PrivateRegistration'])) {
            $request['PrivateRegistration'] = $domain_data['PrivateRegistration'];
        }

        if (isset($domain_data['Premium'])) {
            $request['Premium'] = $domain_data['Premium'];
        }

        $response = $this->call('DomainCreate', $request);

        if ($response && isset($response->APIResponse->DomainDetails)) {
            return $response->APIResponse->DomainDetails;
        }

        return false;
    }

    /**
     * Update the details of a domain owned by the reseller.
     * Required input parameters:
     * DomainName, RegistrantContactIdentifier, AdminContactIdentifier, BillingContactIdentifier,
     * TechContactIdentifier, NameServers, PrivateRegistration
     *
     * NameServers is array with format:
     * array (
     *   array('Host' => 'first nameserver host', 'IP' => 'first name server IP'),
     *   array('Host' => 'second nameserver host', 'IP' => 'second name server IP'),
     *   ...
     * )
     *
     * It is recommended to edit necessary data from $this->domain_info()
     *
     * @param array $domain_data
     *
     * @return DomainInfo
     */
    public function domain_update($domain_data)
    {
        // order fields in request
        $request = array(
            'DomainName' => isset($domain_data['DomainName']) ? $domain_data['DomainName'] : '',
            'RegistrantContactIdentifier' => isset($domain_data['RegistrantContactIdentifier']) ? $domain_data['RegistrantContactIdentifier'] : '',
            'AdminContactIdentifier' => isset($domain_data['AdminContactIdentifier']) ? $domain_data['AdminContactIdentifier'] : '',
            'BillingContactIdentifier' => isset($domain_data['BillingContactIdentifier']) ? $domain_data['BillingContactIdentifier'] : '',
            'TechContactIdentifier' => isset($domain_data['TechContactIdentifier']) ? $domain_data['TechContactIdentifier'] : '',
            'LockStatus' => isset($domain_data['LockStatus']) ? $domain_data['LockStatus'] : '',
        );

        if (isset($domain_data['NameServers']) && is_array($domain_data['NameServers'])) {
            $request['NameServers'] = [];

            foreach ($domain_data['NameServers'] as $ns) {
                if (isset($ns['Host']) && isset($ns['IP'])) {
                    $request['NameServers'][] = array('Host' => $ns['Host'], 'IP' => $ns['IP']);
                }
            }
        }

        if (isset($domain_data['PrivateRegistration'])) {
            $request['PrivateRegistration'] = $domain_data['PrivateRegistration'];
        }

        $response = $this->call('DomainUpdate', $request);

        if ($response && isset($response->APIResponse->DomainDetails)) {
            return $response->APIResponse->DomainDetails;
        }

        return false;
    }

    /**
     * Delete domain from reseller's account. If operation fails, it returns false. See $this->get_errors() for getting errors.
     *
     * @param string $domain_name
     *
     * @return bool
     */
    public function domain_delete($domain_name)
    {
        $request = array(
            'DomainName' => $domain_name,
        );

        $response = $this->call('DomainDelete', $request);

        if ($response && isset($response->APIResponse->Success)) {
            return true;
        }

        return false;
    }

    /**
     * Renew the domain registration. If operation fails, it returns false. See $this->get_errors() for getting errors.
     *
     * @param string $domain_name
     * @param int $period_years
     *
     * @return DomainDetails
     */
    public function domain_renew($domain_name, $period_years)
    {
        $period_years = intval($period_years);
        $request = array(
            'DomainName' => $domain_name,
            'RenewalPeriod' => $period_years,
        );

        $response = $this->call('DomainRenew', $request);

        if ($response && isset($response->APIResponse->DomainDetails)) {
            return $response->APIResponse->DomainDetails;
        }

        return false;
    }

    /**
     * Update DNS records for a domain name
     *
     * @param string $domain_name
     * @param array $dns_records
     *
     * Parameter $dns_records is array with format:
     * @see domain_dns_add()
     *
     * @return array|bool
     */
    public function domain_dns_update($domain_name, $dns_records)
    {
        $request = array(
            'DomainName' => $domain_name,
            'Records' => $dns_records,
        );
        $response = $this->call('DomainDNSUpdate', $request);

        if ($response && isset($response->APIResponse->DomainDetails)) {
            return $response->APIResponse->DomainDetails;
        }

        return false;
    }

    /**
     * Add new DNS records for a domain name
     *
     * @param string $domain_name
     * @param array $dns_records
     *
     * Parameter $dns_records is array with format:
     * array(
     *      'A' => array(
     *          array('Subdomain' => 'subdomain', 'Content' => 'content'),
     *          ...
     *      ),
     *      'AAAA' => array(
     *          array('Subdomain' => 'subdomain', 'Content' => 'content'),
     *          ...
     *      ),
     *      'CNAME' => array(
     *          array('Subdomain' => 'subdomain', 'Content' => 'content'),
     *          ...
     *      ),
     *      'CAA' => array(
     *          array('Flag' => 'flag', 'Tag' => 'tag', 'Content' => 'content'),
     *          ...
     *      ),
     *      'SRV' => array(
     *          array('Subdomain' => 'subdomain', 'Priority' => 'priority', 'Weight' => 'weight', 'Port' => 'port', 'Target' => 'target),
     *          ...
     *      ),
     *      'MX' => array(
     *          array('Subdomain' => 'subdomain', 'Content' => 'content', 'Priority' => 'priority'),
     *          ...
     *      ),
     *      'TXT' => array(
     *          array('Subdomain' => 'subdomain', 'Content' => 'content'),
     *          ...
     *      ),
     *      'WEBFWD' => array(
     *          array('Subdomain' => 'subdomain', 'Content' => 'content'),
     *          ...
     *      ),
     *      'MAILFWD' => array(
     *          array('Email' => 'email', 'Content' => 'content'),
     *          ...
     *      )
     * )
     *
     * @return bool
     */
    public function domain_dns_add($domain_name, $dns_records)
    {
        $request = array(
            'DomainName' => $domain_name,
            'Records' => $dns_records,
        );
        $response = $this->call('DomainDNSAdd', $request);

        if ($response && isset($response->APIResponse->Success)) {
            return $response->APIResponse->Success;
        }

        return false;
    }

    /**
     * Remove DNS record for domain name
     *
     * @param string $domain_name
     * @param int $dns_record_id
     *
     * @return bool
     */
    public function domain_dns_remove($domain_name, $dns_record_id)
    {
        $request = array(
            'DomainName' => $domain_name,
            'RecordId' => $dns_record_id,
        );
        $response = $this->call('DomainDNSRemove', $request);

        if ($response && isset($response->APIResponse->Success)) {
            return $response->APIResponse->Success;
        }

        return false;
    }

    /**
     * Create a contact and return its details or false if an error occurred
     * Required input parameters:
     * FirstName, LastName, Address, City, Country, County, PostCode, CountryCode, Phone, Mobile, Email, AccountType
     * if AccountType is "business", these fields are also required:
     * BusinessName
     * and these are optional:
     * BusinessNumberType, BusinessNumber
     * Format of return value is the same as for $this->contact_info()
     *
     * @param array $data
     *
     * @return ContactInfo
     */
    public function contact_create($data)
    {
        $state = isset($data['County']) ? $data['County'] : (isset($data['State']) ? $data['State'] : '');
        // order fields in request
        $request = array(
            'FirstName' => isset($data['FirstName']) ? $data['FirstName'] : '',
            'LastName' => isset($data['LastName']) ? $data['LastName'] : '',
            'Address' => isset($data['Address']) ? $data['Address'] : '',
            'City' => isset($data['City']) ? $data['City'] : '',
            'Country' => isset($data['Country']) ? $data['Country'] : '',
            'County' => $state,
            'State' => $state,
            'PostCode' => isset($data['PostCode']) ? $data['PostCode'] : '',
            'CountryCode' => isset($data['CountryCode']) ? $data['CountryCode'] : '',
            'Phone' => isset($data['Phone']) ? $data['Phone'] : '',
            'Mobile' => isset($data['Mobile']) ? $data['Mobile'] : '',
            'Email' => isset($data['Email']) ? $data['Email'] : '',
            'AccountType' => isset($data['AccountType']) ? $data['AccountType'] : 'personal',
            'BusinessName' => isset($data['BusinessName']) ? $data['BusinessName'] : '',
            'BusinessNumberType' => isset($data['BusinessNumberType']) ? $data['BusinessNumberType'] : '',
            'BusinessNumber' => isset($data['BusinessNumber']) ? $data['BusinessNumber'] : '',
        );

        $response = $this->call('ContactCreate', $request);

        if ($response && isset($response->APIResponse->ContactDetails)) {
            return $response->APIResponse->ContactDetails;
        }

        return false;
    }

    /**
     * Update an existing contact and return its details or false if error occurred.
     * Format of input data is the same as in $this->contact_create(), with required field ContactIdentifier
     * Format of return value is the same as for $this->contact_info()
     *
     * @param mixed $data
     *
     * @return ContactInfo
     */
    public function contact_update($data)
    {
        $state = isset($data['County']) ? $data['County'] : (isset($data['State']) ? $data['State'] : '');
        // order fields in request
        $request = array(
            'ContactIdentifier' => isset($data['ContactIdentifier']) ? $data['ContactIdentifier'] : '',
            'FirstName' => isset($data['FirstName']) ? $data['FirstName'] : '',
            'LastName' => isset($data['LastName']) ? $data['LastName'] : '',
            'Address' => isset($data['Address']) ? $data['Address'] : '',
            'City' => isset($data['City']) ? $data['City'] : '',
            'Country' => isset($data['Country']) ? $data['Country'] : '',
            'County' => $state,
            'State' => $state,
            'PostCode' => isset($data['PostCode']) ? $data['PostCode'] : '',
            'CountryCode' => isset($data['CountryCode']) ? $data['CountryCode'] : '',
            'Phone' => isset($data['Phone']) ? $data['Phone'] : '',
            'Mobile' => isset($data['Mobile']) ? $data['Mobile'] : '',
            'Email' => isset($data['Email']) ? $data['Email'] : '',
            'AccountType' => isset($data['AccountType']) ? $data['AccountType'] : 'personal',
            'BusinessName' => isset($data['BusinessName']) ? $data['BusinessName'] : '',
            'BusinessNumberType' => isset($data['BusinessNumberType']) ? $data['BusinessNumberType'] : '',
            'BusinessNumber' => isset($data['BusinessNumber']) ? $data['BusinessNumber'] : '',
        );

        $response = $this->call('ContactUpdate', $request);

        if ($response && isset($response->APIResponse->ContactDetails)) {
            return $response->APIResponse->ContactDetails;
        }

        return false;
    }

    /**
     * Return registered contact details or false if error occurred. See $this->get_errors().
     *    stdClass Object
     *    (
     *        [ContactIdentifier] => R-000000000-SN
     *        [FirstName] => First Name
     *        [LastName] => Last Name
     *        [Address] => Address Line
     *        [City] => City Name
     *        [Country] => Country
     *        [County] => WA
     *        [PostCode] => 1234
     *        [CountryCode] => 61
     *        [Phone] => 12345678
     *        [Email] => name@example.com
     *        [AccountType] => personal | business
     *    # if AccountType == business
     *        [BusinessName] => Business Name
     *        [BusinessNumberType] => ABN
     *        [BusinessNumber] => 123456789
     *    )
     *
     * @param string $contact_id
     *
     * @return ContactInfo
     */
    public function contact_info($contact_id)
    {
        $request = array(
            'ContactIdentifier' => $contact_id,
        );

        $response = $this->call('ContactInfo', $request);

        if ($response && isset($response->APIResponse->ContactDetails)) {
            return $response->APIResponse->ContactDetails;
        }

        return false;
    }

    /**
     * Create a registrant based on a contact and return its details or return false if an error occurred.
     * Format of return value is the same as for $this->contact_info()
     *
     * @param string $contact_id
     *
     * @return ContactInfo
     */
    public function contact_clone_to_registrant($contact_id)
    {
        $request = array(
            'ContactIdentifier' => $contact_id,
        );

        $response = $this->call('ContactCloneToRegistrant', $request);

        if ($response && isset($response->APIResponse->ContactDetails)) {
            return $response->APIResponse->ContactDetails;
        }

        return false;
    }

    /**
     * Get the details of the specified host object
     *    stdClass Object
     *    (
     *        [Host] => ns1.somedomain.com
     *        [IP] => 000.000.000.000
     *    )
     *
     * @param string $domain_name
     * @param string $host
     *
     * @return HostDetails
     */
    public function host_info($domain_name, $host)
    {
        $request = array(
            'DomainName' => $domain_name,
            'Host' => $host,
        );

        $response = $this->call('HostInfo', $request);

        if ($response && isset($response->APIResponse->HostDetails)) {
            return $response->APIResponse->HostDetails;
        }

        return false;
    }

    /**
     * Creates a host object for the domain.
     * Required input parameters:
     * DomainName, Host, IP
     * Format of return value is the same as for $this->host_info()
     *
     * @param array $data
     *
     * @return HostDetails
     */
    public function host_create($data)
    {
        // order fields in request
        $request = array(
            'DomainName' => isset($data['DomainName']) ? $data['DomainName'] : '',
            'Host' => isset($data['Host']) ? $data['Host'] : '',
            'IP' => isset($data['IP']) ? $data['IP'] : '',
        );

        $response = $this->call('HostCreate', $request);

        if ($response && isset($response->APIResponse->HostDetails)) {
            return $response->APIResponse->HostDetails;
        }

        return false;
    }

    /**
     * Update the details of a host object owned by the reseller.
     * Required input parameters:
     * DomainName, Host, OldIP, NewIP
     * Format of return value is the same as for $this->contact_info()
     *
     * @param mixed $data
     *
     * @return HostDetails
     */
    public function host_update($data)
    {
        // order fields in request
        $request = array(
            'DomainName' => isset($data['DomainName']) ? $data['DomainName'] : '',
            'Host' => isset($data['Host']) ? $data['Host'] : '',
            'OldIP' => isset($data['OldIP']) ? $data['OldIP'] : '',
            'NewIP' => isset($data['NewIP']) ? $data['NewIP'] : '',
        );

        $response = $this->call('HostUpdate', $request);

        if ($response && isset($response->APIResponse->HostDetails)) {
            return $response->APIResponse->HostDetails;
        }

        return false;
    }

    /**
     * Delete a host object. If operation fails, it returns false. See $this->get_errors() for getting errors.
     *
     * @param string $domain_name
     * @param string $host
     *
     * @return bool
     */
    public function host_delete($domain_name, $host)
    {
        $request = array(
            'DomainName' => $domain_name,
            'Host' => $host,
        );

        $response = $this->call('HostDelete', $request);

        if ($response && isset($response->APIResponse->Success)) {
            return true;
        }

        return false;
    }

    /**
     * Verify if a domain is available for transfer
     *
     * @param string $domain_name
     * @param string $auth_key
     *
     * @return bool
     */
    public function transfer_check($domain_name, $auth_key = '')
    {
        $request = array(
            'DomainName' => $domain_name,
            'AuthKey' => $auth_key,
        );

        $response = $this->call('TransferCheck', $request);

        if ($response && isset($response->APIResponse->Success)) {
            return true;
        }

        return false;
    }

    /**
     * Return current status of a transfer
     *    stdClass Object
     *    (
     *        [DomainName] => somedomain.com
     *        [Status] => Ex: Transfer Pending, Transfer Not Found.
     *    )
     *
     * @param string $domain_name
     * @param string $auth_key
     *
     * @return TransferDetails
     */
    public function transfer_info($domain_name, $auth_key = '')
    {
        $request = array(
            'DomainName' => $domain_name,
            'AuthKey' => $auth_key,
        );

        $response = $this->call('TransferInfo', $request);

        if ($response && isset($response->APIResponse->TransferDetails)) {
            return $response->APIResponse->TransferDetails;
        }

        return false;
    }

    /**
     * Initiate the transfer of a domain name to the reseller's account.
     * The TransferCheck operation should be called prior to this operation to ensure that the domain can be transferred.
     * Fields in input $data array:
     * ContactIdentifier
     * DomainName
     * AuthKey (optional)
     * RenewalPeriod (optional)
     *
     * @param array $data
     *
     * @return TransferDetails
     */
    public function transfer_start($data)
    {
        // order fields in request
        $request = array(
            'ContactIdentifier' => isset($data['ContactIdentifier']) ? $data['ContactIdentifier'] : '',
            'DomainName' => isset($data['DomainName']) ? $data['DomainName'] : '',
            'AuthKey' => isset($data['AuthKey']) ? $data['AuthKey'] : '',
            'RenewalPeriod' => isset($data['RenewalPeriod']) ? $data['RenewalPeriod'] : '',
        );

        $response = $this->call('TransferStart', $request);

        if ($response && isset($response->APIResponse->TransferDetails)) {
            return $response->APIResponse->TransferDetails;
        }

        return false;
    }

    /**
     * Cancel a transfer that has been initiated with TransferStart.
     *
     * @param string $domain_name
     * @param string $auth_key
     *
     * @return bool
     */
    public function transfer_cancel($domain_name, $auth_key = '')
    {
        $request = array(
            'DomainName' => $domain_name,
            'AuthKey' => $auth_key,
        );

        $response = $this->call('TransferCancel', $request);

        if ($response && isset($response->APIResponse->Success)) {
            return true;
        }

        return false;
    }


    /**
     * Get information about product or false if error occurred. See $this->errors().
     *    stdClass Object
     *    (
     *        [ProductId] => 123
     *        [PlanId] => 123
     *        [Username] => ****
     *        [DomainName] => example.com
     *        [ProductName] => Some Product
     *        [Status] => Registered
     *        [Expires] => true
     *        [Expiry] => 2019-11-08
     *    )
     *
     * @param int $product_id
     * @param string $domain_name
     *
     * @return object|bool
     */
    public function product_info($product_id, $domain_name)
    {
        $request = [
            'ProductId' => $product_id,
            'DomainName' => $domain_name,
        ];

        $response = $this->call('ProductInfo', $request);

        if ($response && isset($response->APIResponse->Success)) {
            return $response->APIResponse->ProductDetails;
        }

        return false;
    }

    /**
     * Create product and return product details the same as in $this->product_info().
     *
     * @param array $product_data
     *    Array
     *    (
     *        DomainName => example.com
     *        PlanId => 123
     *        MemberId => 123
     *        Period => 1
     *        // optional
     *        Username => ***
     *        // optional
     *        Password => ***
     *        // optional
     *        SSLInformation => Array
     *            (
     *                HostedWith => Hosted with
     *                CSR => CSR string
     *                ServerSoftware => Server software
     *            )
     *        // optional
     *        OperatingSystem => OS
     *        // optional
     *        ServerLocation => Server Location
     *    )
     *
     * @return object|bool
     */
    public function product_create($product_data)
    {
        $response = $this->call('ProductCreate', $product_data);

        if ($response && isset($response->APIResponse->Success)) {
            return $response->APIResponse->ProductDetails;
        }

        return false;
    }

    /**
     * Renew the product. If operation fails, it returns false. See $this->get_errors() for getting errors.
     *
     * @param int $product_id
     * @param null|int $period
     *
     * @return bool|array
     */
    public function product_renew($product_id, $period = null)
    {
        $request = [
            'ProductId' => $product_id,
        ];

        if ($period !== null) {
            $request['Period'] = $period;
        }

        $response = $this->call('ProductRenew', $request);

        if ($response && isset($response->APIResponse->Success)) {
            return $response->APIResponse->ProductDetails;
        }

        return false;
    }

    /**
     * Delete product. If operation fails, it returns false. See $this->get_errors() for getting errors.
     *
     * @param int $product_id
     *
     * @return bool
     */
    public function product_delete($product_id)
    {
        $response = $this->call('ProductDelete', [
            'ProductId' => $product_id,
        ]);

        if ($response && isset($response->APIResponse->Success)) {
            return $response->APIResponse->ProductDetails;
        }

        return false;
    }

    public function getDomainPriceList()
    {
        $response = $this->call('GetDomainPriceList');

        if ($response && isset($response->APIResponse->DomainPriceList)) {
            return $response->APIResponse->DomainPriceList;
        }

        return false;
    }

    public function getDomainList()
    {
        $response = $this->call('GetDomainList');

        if ($response && isset($response->APIResponse->DomainList)) {
            return $response->APIResponse->DomainList;
        }

        return false;
    }

    /**
     * Call Reseller API method by name and return response object or false if error occurred
     *
     * @param string $method
     * @param array $data
     *
     * @return bool|object
     */
    public function call($method, array $data = array())
    {
        $this->reset(); // clear errors and last response object

        if (count($data) > 0) {
            $data = array($data);
        }

        try {

            $client = $this->soap_client();
            $response = $client->__soapCall($method, $data);


            $this->last_response = $response;

            if ($response) {
                if (isset($response->APIResponse->Errors)) {
                    foreach ($response->APIResponse->Errors as $error) {
                        $this->errors[] = $error->Message;
                    }

                    $response = false;
                }
            } else {
                $this->errors[] = 'Reseller API returns undefined answer.';
                $response = false;
            }

            if (self::DEBUG) {
                $this->print_xml($client->__getLastRequest());
                $this->print_xml($client->__getLastResponse());
            }
        } catch (SoapFault $response) {
            $this->errors[] = $response->getMessage();

            $response = false;
        } catch (Exception $ex) {
            $this->errors[] = $ex->getMessage();
            $response = false;
        }

        return $response;
    }

    /**
     * Return an array of error messages
     *
     * @return array
     */
    public function get_errors()
    {
        return $this->errors;
    }

    /**
     * Return an object of the last response
     *
     * @return object
     */
    public function get_last_response()
    {
        return $this->last_response;
    }

    /**
     * Reset object before calling a SOAP method
     *
     */
    private function reset()
    {
        $this->errors = array();
        $this->last_response = false;
    }

    /**
     * Build SOAP Client and set authentication headers
     *
     * @return SoapClient
     * @throws Exception if reseller ID or API is missing
     */
    private function soap_client()
    {
        if ($this->api_soap_client === false) {
            if (empty($this->reseller_id)) {
                throw new Exception('Reseller ID is omitted.');
            }

            if (empty($this->reseller_api_key)) {
                throw new Exception('Reseller API Key is omitted.');
            }

            $wsdl = ($this->is_test) ? self::TEST_WSDL : self::LIVE_WSDL;
            $namespace = ($this->is_test) ? self::TEST_NAMESPACE : self::LIVE_NAMESPACE;

            $authenticate = new stdClass();
            $authenticate->AuthenticateRequest = new stdClass();
            $authenticate->AuthenticateRequest->ResellerID = $this->reseller_id;
            $authenticate->AuthenticateRequest->APIKey = $this->reseller_api_key;

            $header = new SoapHeader($namespace, 'Authenticate', $authenticate, false);

            $this->api_soap_client = new SoapClient($wsdl, array(
                'trace' => (self::DEBUG) ? 1 : 0,
                'soap_version' => SOAP_1_2,
                'cache_wsdl' => WSDL_CACHE_NONE,
                'user_agent' => $this->user_agent,
            ));

            $this->api_soap_client->__setSoapHeaders(array($header));
        }

        return $this->api_soap_client;
    }

    /**
     * Debug tool. Prints out formatted XML.
     *
     * @param string $xml_string
     *
     * @return bool
     */
    private function print_xml($xml_string)
    {
        CE_Lib::log(4, $xml_string);

        return true;

        if (empty($xml_string)) {
            return false;
        }

        $dom_xml = new DOMDocument('1.0');
        $dom_xml->preserveWhiteSpace = false;
        $dom_xml->formatOutput = true;
        $dom_xml->loadXML($xml_string);

        $xml = $dom_xml->saveXML();

        echo '<pre>' . htmlentities($xml) . '</pre>';

        return true;
    }
}
