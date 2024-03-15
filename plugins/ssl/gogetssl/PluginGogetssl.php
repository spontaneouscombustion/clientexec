<?php

require_once 'modules/admin/models/SSLPlugin.php';
require_once 'plugins/ssl/gogetssl/lib/GoGetSSLApi.php';

class PluginGogetssl extends SSLPlugin
{
    public $mappedTypeIds = [
        SSL_GOGETSSL_TRIAL => 65,
        SSL_SECTIGO_POSITIVESSL => 45,
        SSL_SECTIGO_POSITIVESSL_WILDCARD => 46,
        SSL_SECTIGO_PREMIUM_WILDCARD => 50,
        SSL_SECTIGO_INSTANTSSL_PREMIUM => 49,
        SSL_SECTIGO_INSTANTSSL_PRO => 48,
        SSL_SECTIGO_EV => 55,
        SSL_SECTIGO_CODE_SIGNING => 78,
        SSL_SECTIGO_INSTANTSSL => 47,
        SSL_CERT_RAPIDSSL => 31,
        SSL_CERT_RAPIDSSL_WILDCARD => 32,
        SSL_CERT_THAWTE_SSL123 => 36,
        SSL_CERT_THAWTE_SSL_WEBSERVER => 35,
        SSL_CERT_THAWTE_SSL_WEBSERVER_EV => 37,
        SSL_CERT_THAWTE_SSL_WEBSERVER_WILDCARD => 38,
        SSL_CERT_THAWTE_CODE_SIGNING => 33,
        SSL_CERT_GEOTRUST_QUICKSSL_PREMIUM => 26,
        SSL_CERT_GEOTRUST_TRUE_BUSINESSID => 27,
        SSL_CERT_GEOTRUST_TRUE_BUSINESSID_WILDCARD => 28,
        SSL_CERT_GEOTRUST_TRUE_BUSINESSID_EV => 29,
        SSL_SECTIGO_SSL => 82,
        SSL_SECTIGO_ESSENTIAL => 75,
        SSL_SECTIGO_ESSENTIAL_WILDCARD => 76,
        SSL_SECTIGO_TRIAL => 70,
        SSL_CERT_RAPIDSSL_TRIAL => 83,
        SSL_SECTIGO_SSL_WILDCARD => 105,
        SSL_GOGETSSL_DOMAIN_SSL => 66,
        SSL_GOGETSSL_WILDCARD_SSL => 67
    ];

    public $usingInviteURL = false;

    public function getVariables()
    {
        $variables = [
            lang('Plugin Name') => [
                'type' => 'hidden',
                'description' => lang('How CE sees this plugin (not to be confused with the Signup Name)'),
                'value' => lang('GoGetSSL')
            ],
            lang('API Username') => [
                'type' => 'text',
                'description' => lang('Enter your username for your eNom reseller account.'),
                'value' => ''
            ],
            lang('API Password') => [
                'type' => 'password',
                'description' => lang('Enter the password for your eNom reseller account.'),
                'value' => '',
            ],
            lang('Actions') => [
                'type' => 'hidden',
                'description' => lang('Current actions that are active for this plugin'),
                'value' => 'Purchase,ResendApproverEmail (Resend Approver Email)'
            ],
        ];

        return $variables;
    }

    function doPurchase($params)
    {
        $userPackage = new UserPackage($params['userPackageId']);
        $params = $this->buildParams($userPackage);

        $apiClient = new GoGetSSLApi();
        $token = $apiClient->auth($params['API Username'], $params['API Password']);

        if ($params['CSR'] == '' || $params['adminEmail'] == '') {
            throw new CE_Exception('Missing CSR or Admin E-Mail');
        }
        $csr = $this->doParseCSR($params);
        $userPackage->setCustomField("Certificate Domain", $csr['domain']);

        $period = 0;
        if ($params['numMonths'] != 0) {
            $period = $params['numMonths'];
        } elseif ($params['numYears'] != 0) {
            $period = $params['numYears'] * 12;
        } else {
            throw new CE_Exception($this->user->lang('No Billing Setup for SSL Cert'));
        }

        $data = [
            'product_id'       => $this->getServiceNameById($params['typeId']),
            'csr'              => $params['CSR'],
            'server_count'     => "-1",
            'period'           => $period,
            'approver_email'   => $params['adminEmail'],
            'webserver_type'   => "1",
            'admin_firstname'  => $params['FirstName'],
            'admin_lastname'   => $params['LastName'],
            'admin_organization' => '',
            'admin_addressline1' => $params['Address1'],
            'admin_phone'      => $this->_validatePhone($params['Phone'], $params['Country']),
            'admin_title'      => "N/A",
            'admin_city'       => $params['City'],
            'admin_country'    => $params['Country'],
            'admin_postalcode' => $params['PostalCode'],
            'admin_region'     => $params['StateProvince'],
            'admin_email'      => $params['EmailAddress'],
            'dcv_method'       => 'email',
            //'only_validate'    => true
        ];

        if (isset($params['Tech E-Mail']) && $params['Tech E-Mail'] != '') {
            $moreArgs = array(
                'tech_firstname'        => $params['Tech First Name'],
                'tech_lastname'        => $params['Tech Last Name'],
                'tech_organization'      => $params['Tech Organization'],
                'tech_title'      => $params['Tech Job Title'],
                'tech_addressline1'     => $params['Tech Address'],
                'tech_city'         => $params['Tech City'],
                'tech_region'     => $params['Tech State'],
                'tech_postalcode'   => $params['Tech Postal Code'],
                'tech_country'      => $params['Tech Country'],
                'tech_phone'        => $this->_validatePhone($params['Tech Phone'], $params['Tech Country']),
                'tech_email' => $params['Tech E-Mail']
            );
            $data = array_merge($data, $moreArgs);
        } else {
            $moreArgs = array(
                'tech_firstname'        => $params['FirstName'],
                'tech_lastname'        => $params['LastName'],
                'tech_organization'      => $params['OrganizationName'],
                'tech_title'      => 'N/A',
                'tech_addressline1'     => $params['Address1'],
                'tech_city'         => $params['City'],
                'tech_region'     => $params['StateProvince'],
                'tech_postalcode'   => $params['PostalCode'],
                'tech_country'      => $params['Country'],
                'tech_phone'        => $this->_validatePhone($params['Phone'], $params['Country']),
                'tech_email' => $params['EmailAddress'],
            );
            $data = array_merge($data, $moreArgs);
        }

        CE_Lib::log(4, 'GoGetSSL Data: ');
        CE_Lib::log(4, $data);

        $order = $apiClient->addSSLOrder($data);
        if (isset($order['error']) && $order['error'] == 1) {
            throw new CE_Exception($order['description']);
        }

        $userPackage->setCustomField('Certificate Id', $order['order_id']);

        return 'Successfully Purchased Certificate';
    }

    public function doParseCSR($params)
    {
        $userPackage = new UserPackage($params['userPackageId']);
        $params = $this->buildParams($userPackage);

        $apiClient = new GoGetSSLApi();
        $token = $apiClient->auth($params['API Username'], $params['API Password']);
        $response = $apiClient->decodeCSR($params['CSR']);

        $return = [];
        if (is_array($response['csrResult'])) {
            if (isset($response['csrResult']['errorMessage'])) {
                throw new CE_Exception($response['csrResult']['errorMessage']);
            }
            $return['domain'] = $response['csrResult']['CN'];
            $return['email'] = $response['csrResult']['Email'];
            $return['city'] = $response['csrResult']['L'];
            $return['state'] = $response['csrResult']['S'];
            $return['country'] = $response['csrResult']['C'];
            $return['organization'] = $response['csrResult']['O'];
            $return['ou'] = $response['csrResult']['OU'];
        }

        return $return;
    }

    function doGetCertStatus($params)
    {
        $statusMessage = '';
        $userPackage = new UserPackage($params['userPackageId']);
        $params = $this->buildParams($userPackage);

        $apiClient = new GoGetSSLApi();
        $token = $apiClient->auth($params['API Username'], $params['API Password']);

        $status = $apiClient->getOrderStatus($params['certId']);

        if (isset($status['status_description']) && $status['status_description'] != '') {
            $statusMessage = $status['status_description'];
            $userPackage->setCustomField('Certificate Status', $statusMessage);
        } elseif (isset($status['status']) && $status['status'] != '') {
            $statusMessage = $status['status'];
            $userPackage->setCustomField('Certificate Status', $statusMessage);
        }

        if (isset($status['valid_till']) && $status['valid_till'] != '0000-00-00') {
            $expirationDate = date('m/d/Y g:i:s A', strtotime($status['valid_till']));
            $userPackage->setCustomField('Certificate Expiration Date', $expirationDate);
        }

        if (isset($status['status']) && $status['status'] == 'active') {
            $userPackage->setCustomField('Certificate Status', SSL_CERT_ISSUED_STATUS);
        }
        return $statusMessage;
    }

    function doResendApproverEmail($params)
    {
        $userPackage = new UserPackage($params['userPackageId']);
        $params = $this->buildParams($userPackage);

        $apiClient = new GoGetSSLApi();
        $token = $apiClient->auth($params['API Username'], $params['API Password']);
        $response = $apiClient->resendEmail($params['certId']);
        if (isset($response['success']) && $response['success'] == 1) {
            return $response['message'];
        }

        if (isset($response['error']) && $response['error'] == 1) {
            throw new CE_Exception($response['description']);
        }
    }

    function getCertificateTypes()
    {
        return [
            65 => 'GoGetSSL Unlimited Trial',
            45 => 'Sectigo PositiveSSL',
            46 => 'Sectigo PositiveSSL Wildcard',
            50 => 'Sectigo Premium Wildcard SSL',
            49 => 'Sectigo InstantSSL Premium',
            48 => 'Sectigo InstantSSL Pro',
            55 => 'Sectigo EV SSL',
            78 => 'Sectigo Code Signing SSL',
            47 => 'Sectigo InstantSSL',
            31 => 'RapidSSL Standard',
            32 => 'RapidSSL WildcardSSL',
            36 => 'Thawte SSL 123',
            35 => 'Thawte Web Server SSL',
            37 => 'Thawte Web Server EV',
            38 => 'Thawte Wildcard SSL Certificate',
            33 => 'Thawte Code Signing SSL',
            26 => 'GeoTrust QuickSSL Premium',
            27 => 'GeoTrust TrueBusinessID',
            28 => 'GeoTrust TrueBusinessID Wildcard',
            29 => 'GeoTrust TrueBusinessID EV',
            82 => 'Sectigo SSL Certificate',
            75 => 'Sectigo Essential SSL',
            76 => 'Sectigo Essential Wildcard SSL',
            70 => 'Sectigo Trial SSL',
            83 => 'RapidSSL Trial',
            105 => 'Sectigo SSL Wildcard',
            66 => 'GoGetSSL Domain SSL',
            67 => 'GoGetSSL Wildcard SSL',
        ];
    }

    private function getServiceNameById($id)
    {
        switch ($id) {
            case SSL_GOGETSSL_TRIAL:
                return 65;
            case SSL_SECTIGO_POSITIVESSL:
                return 45;
            case SSL_SECTIGO_POSITIVESSL_WILDCARD:
                return 46;
            case SSL_SECTIGO_PREMIUM_WILDCARD:
                return 50;
            case SSL_SECTIGO_INSTANTSSL_PREMIUM:
                return 49;
            case SSL_SECTIGO_INSTANTSSL_PRO:
                return 48;
            case SSL_SECTIGO_EV:
                return 55;
            case SSL_SECTIGO_CODE_SIGNING:
                return 78;
            case SSL_SECTIGO_INSTANTSSL:
                return 47;
            case SSL_CERT_RAPIDSSL:
                return 31;
            case SSL_CERT_RAPIDSSL_WILDCARD:
                return 32;
            case SSL_CERT_THAWTE_SSL123:
                return 36;
            case SSL_CERT_THAWTE_SSL_WEBSERVER:
                return 35;
            case SSL_CERT_THAWTE_SSL_WEBSERVER_EV:
                return 37;
            case SSL_CERT_THAWTE_SSL_WEBSERVER_WILDCARD:
                return 38;
            case SSL_CERT_THAWTE_CODE_SIGNING:
                return 33;
            case SSL_CERT_GEOTRUST_QUICKSSL_PREMIUM:
                return 26;
            case SSL_CERT_GEOTRUST_TRUE_BUSINESSID:
                return 27;
            case SSL_CERT_GEOTRUST_TRUE_BUSINESSID_WILDCARD:
                return 28;
            case SSL_CERT_GEOTRUST_TRUE_BUSINESSID_EV:
                return 29;
            case SSL_SECTIGO_SSL:
                return 82;
            case SSL_SECTIGO_ESSENTIAL:
                return 75;
            case SSL_SECTIGO_ESSENTIAL_WILDCARD:
                return 76;
            case SSL_SECTIGO_TRIAL:
                return 70;
            case SSL_CERT_RAPIDSSL_TRIAL:
                return 83;
            case SSL_SECTIGO_SSL_WILDCARD:
                return 105;
            case SSL_GOGETSSL_DOMAIN_SSL:
                return 66;
            case SSL_GOGETSSL_WILDCARD_SSL:
                return 67;
        }
    }

    function getWebserverTypes($type)
    {
        return [];
    }

    function _validatePhone($phone, $country)
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
        return "+$code.$phone";
    }


    function getAvailableActions($userPackage)
    {
        $params = $this->buildParams($userPackage);
        if ($params['certId'] == '') {
            return ['Purchase'];
        }

        $actions = [];
        $apiClient = new GoGetSSLApi();
        $token = $apiClient->auth($params['API Username'], $params['API Password']);
        $response = $apiClient->getOrderStatus($params['certId']);
        $status = $response['status'];

        if ($status == 'cancelled' || $status == 'expired' || $status == 'rejected') {
            $actions[] = 'Purchase';
        } elseif ($status == 'processing') {
            $actions[] = 'ResendApproverEmail (Resend Approver Email)';
        } elseif ($status == 'active') {
            $actions[] = '';
        }

        $this->updateCert($userPackage, $response);

        return $actions;
    }

    private function updateCert($userPackage, $response)
    {
        if ($response['crt_code'] != '') {
            $userPackage->setCustomField('SSL Certificate', $response['crt_code']);
        }
    }
}
