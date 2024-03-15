<?php

require_once 'library/CE/NE_MailGateway.php';
require_once 'modules/admin/models/ServerPlugin.php';

class PluginHostingcontroller extends ServerPlugin
{

    public $features = array(
        'packageName' => true,
        'testConnection' => true,
        'showNameservers' => false,
        'directlink' => false
    );

    function getVariables()
    {

        $variables = array (
            lang("Name") => array (
                "type"=>"hidden",
                "description"=>"Used by CE to show plugin - must match how you call the action function names",
                "value"=>"HostingController"
            ),
            lang("Description") => array (
                "type"=>"hidden",
                "description"=>lang("Description viewable by admin in server settings"),
                "value"=>lang("Hosting Controller integration")
            ),
            lang("Username") => array (
                "type"=>"text",
                "description"=>lang("Username"),
                "value"=>""
            ),
            lang("Password") => array (
                "type"=>"text",
                "description"=>lang("Password"),
                "value"=>"",
                "encryptable"=>true
            ),
            lang("Port") => array (
                "type"=>"text",
                "description"=>lang("Port"),
                "value"=>"8798"
            ),
            lang("Use SSL") => array (
                    "type"=>"yesno",
                    "description"=>lang("Set NO if you do not have PHP compiled with cURL.  YES if your PHP is compiled with cURL<br><b>NOTE:</b>It is suggested that you keep this as YES"),
                    "value"=>"1"
                   ),
            lang("Panel Login Name Custom Field") => array(
                "type"        => "text",
                "description" => lang("Enter the name of the package custom field that will hold the panel login username (not used when product type is website"),
                "value"       => ""
            ),
            lang("Panel Login Password Custom Field") => array(
                "type"        => "text",
                "description" => lang("Enter the name of the package custom field that will hold the panel login password (not used when product type is website"),
                "value"       => ""
            ),
            lang("Website FTP Username Custom Field") => array(
                "type"        => "text",
                "description" => lang("Enter the name of the package custom field that will hold the FTP username (to be shown in welcome emails)"),
                "value"       => ""
            ),
            lang("Actions") => array (
                "type"=>"hidden",
                "description"=>lang("Current actions that are active for this plugin per server"),
                "value"=>"Create,Delete,Suspend,UnSuspend"
            ),
            lang('Registered Actions For Customer') => array(
                "type"=>"hidden",
                "description"=>lang("Current actions that are active for this plugin per server for customers"),
                "value"=>""
            ),
            lang("reseller") => array (
                "type"=>"hidden",
                "description"=>lang("Whether this server plugin can set reseller accounts"),
                "value"=>"0",
            ),
            lang("package_addons") => array (
                "type"=>"hidden",
                "description"=>lang("Supported signup addons variables"),
                "value"=>"",
            ),
            lang('package_vars')  => array(
                'type'            => 'hidden',
                'description'     => lang('Whether package settings are set'),
                'value'           => '1',
            ),
            lang('package_vars_values') => array(
                'type'            => 'hidden',
                'description'     => lang('HostingController Settings'),
                'value'           => array(
                    'cresource_name' => array(
                        'type'            => 'text',
                        'label'           => 'Composite Resource Name',
                        'description'     => lang('This Composite Resource will be used if no custom fields for resource are provided'),
                        'value'           => '',
                    ),
                    'product_type' => array(
                        'type' => 'dropdown',
                        'multiple' => false,
                        'getValues' => 'getProductTypes',
                        'label' => lang('Product Type'),
                        'description' => lang('Select the Product Type you are selling'),
                        'value' => 'website',
                    )
                )
            )
        );

        return $variables;
    }

    function getProductTypes()
    {
        return [
            'Website'
            // 'Virtual Machine',
            // 'Exchange Organization',
            // 'Exchange Mailbox',
            // 'CSP Customer',
            // 'Skype for Business',
            // 'Microsoft Sharepoint'
        ];
    }

    function validateCredentials($args)
    {
    }

    function doDelete($args)
    {
        $userPackage = new UserPackage($args['userPackageId']);
        $args = $this->buildParams($userPackage);
        $this->delete($args);
        return 'Package has been deleted.';
    }

    function doCreate($args)
    {
        $userPackage = new UserPackage($args['userPackageId']);
        $args = $this->buildParams($userPackage);
        $this->create($args);
        return 'Package has been created.';
    }

    function doSuspend($args)
    {
        $userPackage = new UserPackage($args['userPackageId']);
        $args = $this->buildParams($userPackage);
        $this->suspend($args);
        return 'Package has been suspended.';
    }

    function doUnSuspend($args)
    {
        $userPackage = new UserPackage($args['userPackageId']);
        $args = $this->buildParams($userPackage);
        $this->unsuspend($args);
        return 'Package has been unsuspended.';
    }

    function unsuspend($args)
    {
        $username = $args['package']['username'];

        $apiURL = $this->getAPIURL($args). "/panel-users?ExactNameMatch=True&UserName=".$username;
        $response = $this->makeRequest($apiURL, $args, 'GET', $data);
        if ($response["code"] != 200) {
            throw new CE_Exception("Failed to retrieve user details: " . $this->formatErrorMsg($response));
        }

        $userDetailsJson = json_decode($response["body"]);
        $userId = $userDetailsJson->Users[0]->UserId;

        if ($userId > 0) {
            $apiURL = $this->getAPIURL($args) . "/panel-users/" . $userId . "/enable";
            $response = $this->makeRequest($apiURL, $args, 'PUT', $data);
            if ($response["code"] != 200) {
                throw new CE_Exception("Failed to suspend user: " . $this->formatErrorMsg($response));
            }
        } else {
            throw new CE_Exception('User not found');
        }
    }

    function suspend($args)
    {
        $username = $args['package']['username'];

        $apiURL = $this->getAPIURL($args). "/panel-users?ExactNameMatch=True&UserName=".$username;
        $response = $this->makeRequest($apiURL, $args, 'GET', $data);
        if ($response["code"] != 200) {
            throw new CE_Exception("Failed to retrieve user details: " . $this->formatErrorMsg($response));
        }

        $userDetailsJson = json_decode($response["body"]);
        $userId = $userDetailsJson->Users[0]->UserId;

        if ($userId > 0) {
            $data = json_encode([
                'DisableOption'=> 'restrictpanelaccessandsuspenduser'
            ]);

            $apiURL = $this->getAPIURL($args) . "/panel-users/" . $userId . "/disable";
            $response = $this->makeRequest($apiURL, $args, 'PUT', $data);
            if ($response["code"] != 200) {
                throw new CE_Exception("Failed to suspend user: " . $this->formatErrorMsg($response));
            }
        } else {
            throw new CE_Exception('User not found');
        }
    }

    function delete($args)
    {
        $username = $args['package']['username'];

        $apiURL = $this->getAPIURL($args). "/panel-users?ExactNameMatch=True&UserName=".$username;
        $response = $this->makeRequest($apiURL, $args, 'GET', $data);
        if ($response["code"] != 200) {
            throw new CE_Exception("Failed to retrieve user details: " . $this->formatErrorMsg($response));
        }

        $userDetailsJson = json_decode($response["body"]);
        $userId = $userDetailsJson->Users[0]->UserId;

        if ($userId > 0) {
            $apiURL = $this->getAPIURL($args) . "/panel-users/" . $userId . "?advanceDelete=True";
            $response = $this->makeRequest($apiURL, $args, 'DELETE', $data);
            if ($response["code"] != 200) {
                throw new CE_Exception("Failed to delete user: " . $this->formatErrorMsg($response));
            }
        } else {
            throw new CE_Exception('User not found');
        }
    }

    function getAvailableActions($userPackage)
    {
        $args = $this->buildParams($userPackage);

        $actions = [];
        $username = $args['package']['username'];

        $apiURL = $this->getAPIURL($args). "/panel-users?exactNameMatch=true&userName=".$username;
        $response = $this->makeRequest($apiURL, $args, 'GET', $data);
        if ($response["code"] != 200) {
            throw new CE_Exception("Failed to retrieve user details: " . $this->formatErrorMsg($response));
        }

        $userDetailsJson = json_decode($response["body"]);
        if (count($userDetailsJson->Users) == 0) {
            $actions[] = 'Create';
        } else {
            $user = $userDetailsJson->Users[0];

            if ($user->IsDisabled == 3) {
                $actions[] = 'UnSuspend';
            } else {
                $actions[] = 'Suspend';
            }
            $actions[] = 'Delete';
        }

        return $actions;
    }

    private function createNewClient($args, $userPackage)
    {
        $data = json_encode(array(
            'UserName'=> $args['package']['username'],
            'Password'=> $args['package']['password'],
            'Description'=> "",
            'EmailAddress'=> $args['customer']['email'],
            'RoleId'=> 3,
        ));

        $apiURL = $this->getAPIURL($args);
        $response = $this->makeRequest($apiURL . "/panel-users", $args, 'POST', $data);
        if ($response["code"] != 200) {
            throw new CE_Exception('Failed to add user');
        }

        $userDetailsJSON = json_decode($response['body']);
        $userId = $userDetailsJSON->UserId;
        $user = new User($args['customer']['id']);

        // add user profile.
        $countryID  = $this->getCountryIdByName($user->getCountry(true), $args);
        $addProfileData = json_encode(array(
            'FirstName'=> $user->getFirstName(),
            'LastName'=> $user->getLastName(),
            'Country'=> $countryID,
            'State'=> $user->getState(),
            'City'=> $user->getCity(),
            'StreetAddress'=> $user->getAddress(),
            'StreetAddress2'=> '',
            'EmailAddress'=> $user->getEmail(),
            'PostalCode'=> $user->getZipCode(),
            'PhoneNo'=> $user->getPhone(),
            'FaxNo'=> "",
            'Company'=> $user->getOrganization(),
            'SocialSecurityNo'=> ""
        ));

        $this->makeRequest($apiURL . '/panel-users/' . $userId . '/general-profile', $args, 'PUT', $addProfileData);

        return $userId;
    }

    function create($args)
    {
        $userPackage = new UserPackage($args['package']['id']);
        $productType = $args['package']['variables']['product_type'];
        $apiURL = $this->getAPIURL($args);

        $cResourceId = 0;
        if ($args['package']['variables']['cresource_name'] != "") {
            $cResourceId = $this->getCompositeResourceIdByName($args['package']['variables']['cresource_name'], $args);
        }

        if ($productType != 'Exchange Mailbox') {
            $userId = $this->createNewClient($args, $userPackage);
        }

        // sell plan
        $planId = $this->getPlanIdByName($args['package']['name_on_server'], $args);
        $planDetails = $this->getPlanDetails($planId, $args);
        if ($planDetails["ownerId"] != $userId) {
            $sellPlanData = json_encode(array(
                'UserId'=> $userId,
                'PlanId'=> $planId,
                'Quantity'=> 1,
            ));

            $sellplanresponse = $this->makeRequest($apiURL . '/sold-plans', $args, "POST", $sellPlanData);
            if ($sellplanresponse["code"] != 200) {
                throw new CE_Exception('User created successfully but failed to sell plan.');
            }
        }

        // Creating Web Hosting
        if ($productType == 'Website') {
            $websiteProvider = "";
            $dotNETEnabled = false;
            $phpEnabled = false;
            $coldFusionEnabled = false;
            $perlEnabled = false;
            $statSitesEnabled = false;

            if ($cResourceId > 0) {
                $cresdetails = $this->getCompositeResourceDetails($cResourceId, $args);
                if (sizeof($cresdetails) <= 0) {
                    throw new CE_Exception('User added successfully,CResource details not found.');
                } else {
                    $websiteProvider = $cresdetails["provider"];
                }
            } else {
                if (isset($planDetails["WebProvider"]) && $planDetails["WebProvider"] != "") {
                    $websiteProvider = $planDetails["WebProvider"];
                } else {
                    throw new CE_Exception('User added successfully, Web server provider not found.');
                }

                if (isset($planDetails["DotNetEnabled"]) && $planDetails["DotNetEnabled"] == "true") {
                    $dotNETEnabled = true;
                }
                if (isset($planDetails["ColdFusionEnabled"]) && $planDetails["ColdFusionEnabled"] == "true") {
                    $coldFusionEnabled = true;
                }
                if (isset($planDetails["PerlEnabled"]) && $planDetails["PerlEnabled"] == "true") {
                    $perlEnabled = true;
                }
                if (isset($planDetails["PHPEnabled"]) && $planDetails["PHPEnabled"] == "true") {
                    $phpEnabled = true;
                }
                if (isset($planDetails["StatsEnabled"]) && $planDetails["StatsEnabled"] == "true") {
                    $statSitesEnabled = true;
                }
            }
            $serverRoleId = $this->getServerRoleIdByServerName($servername, "webserver", $args);

            //Get FTP user details
            $arr = explode(".", $args['package']['domain_name']);
            $site_name = $arr[0];
            $FtpUserName = $site_name."_ftp";
            $FtpUserPassword = $args['package']['password'];

            $websitedata = json_encode([
                'OwnerId'=> $userId,
                'WebsiteName'=> $args['package']['domain_name'],
                'ProviderName' => $websiteProvider,
                'ServerRoleId' => $serverRoleId,
                'IsNameBased' => true,
                'CResourceId' => $cResourceId,
                'DotNetEnabled' => $dotNETEnabled,
                'PhpEnabled' => $phpEnabled,
                'PerlEnabled' => $perlEnabled,
                'ColdFusionEnabled' => $coldFusionEnabled,
                'StatsEnabled' => $statSitesEnabled,
                'DefaultDocuments' => 'index.php,default.html,default.htm,default.asp,default.aspx,index.htm,index.html,index.cfm,index.asp,index.aspx,awstats.pl',
                'CreateFtpUser' => true,
                'FtpUserName' => $FtpUserName,
                'FtpUserPassword' => $FtpUserPassword,
                'AllowAnonymous' => true,
                'DefaultDocUpdate' => true,
                'HandlerReadPermission' => true,
                'IntegratedAuthentication' => true,
                'ReadPermission' => true,
                'ScriptPermission' => true
            ]);

            $websiteresponse = $this->makeRequest($apiURL . '/websites', $args, "POST", $websitedata);
            if ($websiteresponse["code"] != 200) {
                throw new CE_Exception('User added successfully, failed to create website.');
            }
            $webRespjson = json_decode($websiteresponse["body"]);

            //Create Email data
            $siteCreationEmaildata = [
                'NameBased'=> true,
                'WebsiteOwnerId'=> $userId,
                'ServerId' => $webRespjson->ServerId,
                'WebsiteName' => $args['package']['domain_name'],
                'IpAddress' => $webRespjson->IpAddress,
                'VirtualDirectoryName' => "",
                'FtpSiteCreated' => true,
                'FtpUserName' => $FtpUserName,
                'FtpUserPassword' => $FtpUserPassword
            ];

            $userPackage->setCustomField($args['server']['variables']['plugin_hostingcontroller_Website_FTP_Username_Custom_Field'], $FtpUserName, CUSTOM_FIELDS_FOR_PACKAGE);

            //Create MailDomain
            if (isset($planDetails["MailProvider"]) && $planDetails["MailProvider"] != "") {
                $maildomaindata = json_encode(array(
                    'MailDomainName'=> $args['package']['domain_name'],
                    'OwnerId'=> $userId,
                    'ProviderName' => $planDetails["MailProvider"],
                    'ServerRoleId' => $serverRoleId
                ));
                $mdomainresponse = $this->makeRequest($apiURL . '/mail-domains', $args, "POST", $maildomaindata);
                if ($mdomainresponse["code"] != 200) {
                    CE_Lib::log(4, $mdomainresponse);
                } else {
                    $mailRespjson = json_decode($mdomainresponse["body"]);
                    $siteCreationEmaildata["MailDomainName"] = $args['package']['domain_name'];
                    $siteCreationEmaildata["MailServerIp"] = $mailRespjson->MailServerIp;
                    $siteCreationEmaildata["MailServerId"] = $mailRespjson->MailServerId;
                }
            }

            //Create DNS Zone
            if (isset($planDetails["DNSProvider"]) && $planDetails["DNSProvider"] != "") {
                $dnszonedata = json_encode(array(
                    'DnsZoneName'=> $args['package']['domain_name'],
                    'OwnerId'=> $userId,
                    'ProviderName' => $planDetails["DNSProvider"],
                    'ServerRoleId' => $serverRoleId,
                    'MailConfiguredIp' => $mailRespjson->MailServerIp,
                    'MailDomainName' => $args['package']['domain_name']
                ));
                $dnsresponse = $this->makeRequest($apiURL . '/dns-zones', $args, "POST", $dnszonedata);
                if ($dnsresponse["code"] != 200) {
                    CE_Lib::log(4, $dnsresponse);
                } else {
                    $dnsRespjson = json_decode($mdomainresponse["body"]);
                    $siteCreationEmaildata["DnsZoneName"] = $args['package']['domain_name'];
                    $siteCreationEmaildata["DnsServerIp"] = $dnsRespjson->DnsServerIp;
                    $siteCreationEmaildata["NameServer1"] = $dnsRespjson->NameServer1;
                    $siteCreationEmaildata["NameServer2"] = $dnsRespjson->NameServer2;
                    $siteCreationEmaildata["NameServer3"] = $dnsRespjson->NameServer3;
                    $siteCreationEmaildata["NameServer4"] = $dnsRespjson->NameServer4;
                }
            }

            //$emailresponse = $this->makeRequest($apiURL . "/panel-email-conf/emails/website", $args, "PUT", $siteCreationEmaildata);
        }
    }

    private function makeRequest($endpointurl, $args, $method = 'GET', $data = [])
    {
        $endpointurl = rtrim($endpointurl, "/");
        $headers = [
            'Accept: application/json',
            'Content-Type: application/json',
        ];

        $username = trim($args['server']['variables']['plugin_hostingcontroller_Username']);
        $password = trim($args['server']['variables']['plugin_hostingcontroller_Password']);

        $handle = curl_init();
        curl_setopt($handle, CURLOPT_FRESH_CONNECT, true);
        curl_setopt($handle, CURLOPT_URL, $endpointurl);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($handle, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($handle, CURLOPT_USERPWD, "$username:$password");
        curl_setopt($handle, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 5);

        switch ($method) {
            case 'GET':
                break;
            case 'POST':
                curl_setopt($handle, CURLOPT_POST, true);
                curl_setopt($handle, CURLOPT_POSTFIELDS, $data);
                break;
            case 'PUT':
                curl_setopt($handle, CURLOPT_CUSTOMREQUEST, 'PUT');
                curl_setopt($handle, CURLOPT_POSTFIELDS, $data);
                break;
            case 'DELETE':
                curl_setopt($handle, CURLOPT_CUSTOMREQUEST, 'DELETE');
                break;
        }

        $response = curl_exec($handle);
        $httpErrorCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);
        return ["code" => $httpErrorCode, "body" => $response];
    }

    private function getAPIURL($args)
    {
        $protocol = 'http://';
        $port = $args['server']['variables']['plugin_hostingcontroller_Port'];

        if ($args['server']['variables']['plugin_hostingcontroller_Use_SSL'] == 1) {
            $protocol = 'https://';
        }

        $url = $protocol . $args['server']['variables']['ServerHostName'];
        if ($port != '') {
            $url . ':' . $port;
        }
        return $url;
    }


    function testConnection($args)
    {
        CE_Lib::log(4, 'Testing connection to HostingController server');
        $apiurl = $this->getAPIURL($args).'/panel-users/0';
        $httpresponse = $this->makeRequest($apiurl, $args, "GET");
        if ($httpresponse["code"] != 200) {
            throw new CE_Exception("Connection to server failed.");
        }
    }

    private function getPlanDetails($planId, $args)
    {
        $plandetails = [];
        $apiurl = $this->getAPIURL($args) . "/plans/" . $planId;
        $httpresponse = $this->makeRequest($apiurl, $args, "GET");
        if ($httpresponse["code"] != 200) {
            return $plandetails;
        }
        $respjson = json_decode($httpresponse["body"]);

        $plandetails["PlanId"] = $respjson->PlanId;
        $plandetails["OwnerId"] = $respjson->OwnerId;

        foreach ($respjson->Resources as $value) {
            if ($value->SystemName == 'websites' && ($value->IsComposite == false || $value->IsComposite == 'false')) {//websites
                if ($value->Quantity == '-1' || $value->Quantity > '0') {
                    $plandetails["WebProvider"] = $value->ProviderName;
                }
            }
            if ($value->SystemName == 'dotnet'  && ($value->IsComposite == false || $value->IsComposite == 'false')) {//websites
                if ($value->Quantity == '-1' || $value->Quantity > '0') {
                    $plandetails["DotNetEnabled"] = "true";
                }
            }
            if ($value->SystemName == 'php'  && ($value->IsComposite == false || $value->IsComposite == 'false')) {//websites
                if ($value->Quantity == '-1' || $value->Quantity > '0') {
                    $plandetails["PHPEnabled"] = "true";
                }
            }
            if ($value->SystemName == 'perl'  && ($value->IsComposite == false || $value->IsComposite == 'false')) {//websites
                if ($value->Quantity == '-1' || $value->Quantity > '0') {
                    $plandetails["PerlEnabled"] = "true";
                }
            }
            if ($value->SystemName == 'coldfusion'  && ($value->IsComposite == false || $value->IsComposite == 'false')) {//websites
                if ($value->Quantity == '-1' || $value->Quantity > '0') {
                    $plandetails["ColdFusionEnabled"] = "true";
                }
            }
            if ($value->SystemName == 'statsdomains'  && ($value->IsComposite == false || $value->IsComposite == 'false')) {//websites
                if ($value->Quantity == '-1' || $value->Quantity > '0') {
                    $plandetails["StatsEnabled"] = "true";
                }
            }
            if ($value->SystemName == 'dnszones'  && ($value->IsComposite == false || $value->IsComposite == 'false')) {//DNS
                if ($value->Quantity == '-1' || $value->Quantity > '0') {
                    $plandetails["DNSProvider"] = $value->ProviderName;
                }
            }
            if ($value->SystemName == 'maildomains'  && ($value->IsComposite == false || $value->IsComposite == 'false')) {//Mail
                if ($value->Quantity == '-1' || $value->Quantity > '0') {
                    $plandetails["MailProvider"] = $value->ProviderName;
                }
            }
            if ($value->SystemName == 'virtualmachines'  && ($value->IsComposite == false || $value->IsComposite == 'false')) {//VirtualMachine
                if ($value->Quantity == '-1' || $value->Quantity > '0') {
                    $plandetails["VMProvider"] = $value->ProviderName;
                    $plandetails["VMProviderId"] = $value->ProviderId;
                }
            }
            if ($value->SystemName == 'exchangemaildomains'  && ($value->IsComposite == false || $value->IsComposite == 'false')) {//ExgMailDomains
                if ($value->Quantity == '-1' || $value->Quantity > '0') {
                    $plandetails["ExchangeProvider"] = $value->ProviderName;
                }
            }
            if ($value->SystemName == 'sipdomains'  && ($value->IsComposite == false || $value->IsComposite == 'false')) {//SIPDomains(Skype)
                if ($value->Quantity == '-1' || $value->Quantity > '0') {
                    $plandetails["SkypeProvider"] = $value->ProviderName;
                }
            }
            if ($value->SystemName == 'sharepointsites'  && ($value->IsComposite == false || $value->IsComposite == 'false')) {//SharePointSites
                if ($value->Quantity == '-1' || $value->Quantity > '0') {
                    $plandetails["SharePointProvider"] = $value->ProviderName;
                }
            }
        }

        if (isset($respjson->ResourceComponents)) {
            foreach ($respjson->ResourceComponents as $value) {
                if ($value->ComponentName == 'baseos') {
                    $plandetails["baseostype"] = $value->Value;
                    $plandetails["baseostypeid"] = $value->ComponentPropId;
                }
            }
        }
        return $plandetails;
    }

    private function GetCompositeResourceIdByName($name, $args)
    {
        $apiurl = $this->getAPIURL($args) . "/composite-resources?IncludePurchased=true&ExactMatch=true&DisplayName=".$name;
        $response = $this->makeRequest($apiurl, $args, "GET");
        if ($response["code"] != 200) {
            return 0;
        }
        $respjson = json_decode($response["body"]);
        return $respjson->CompositeResources[0]->CompositeResourceId;
    }

    private function getPlanIdByName($planName, $args)
    {
        $planName = urlencode($planName);
        $apiurl = $this->getAPIURL($args) . "/plans?WithBasicDetail=true&ExactMatch=true&DisplayName=".$planName;
        $response = $this->makeRequest($apiurl, $args, "GET");
        if ($response["code"] != 200) {
            return 0;
        }
        $jsonResponse = json_decode($response["body"]);
        return $jsonResponse->Plans[0]->PlanId;
    }

    private function getCompositeResourceDetails($cresourceid, $args)
    {
        $cresourcedetails = [];

        $httpresponse = $this->makeRequest($this->getAPIURL($args) . "/composite-resources/" . $cresourceid, $args, "GET");
        if ($httpresponse["code"] != 200) {
            return $cresourcedetails;
        }
        $respjson = json_decode($httpresponse["body"]);
        $provider =  $respjson->ProviderName;
        $providerid =  $respjson->ProviderId;
        $cresourcedetails["provider"] = $provider;
        $cresourcedetails["providerid"] = $providerid;
        if (isset($respjson->ResourceComponents)) {
            foreach ($respjson->ResourceComponents as $value) {
                if ($value->ComponentName == 'baseos') {
                    $cresourcedetails["baseostype"] = $value->Value;
                    $cresourcedetails["baseostypeid"] = $value->ComponentPropId;
                }
            }
        }
        return $cresourcedetails;
    }

    private function getCountryIdByName($name, $args)
    {
        $apiurl = $this->getAPIURL($args) . "/system-entities/countries?CountryName=".$name;
        $httpresponse = $this->makeRequest($apiurl, $args, "GET");
        if ($httpresponse["code"] != 200) {
            return 1;
        }
        $respjson = json_decode($httpresponse["body"]);
        return $respjson->Countries[0]->CountryId;
    }

    private function getServerRoleIdByServerName($servername, $role, $args)
    {
        $serverRoleId = 0;
        if ($servername == "") {
            return $serverRoleId;
        }

        $httpresponse = $this->makeRequest($this->getAPIURL($args) ."/servers", $args, "GET");
        if ($httpresponse["code"] != 200) {
            return $serverRoleId;
        }
        $respjson = json_decode($httpresponse["body"]);
        if (isset($respjson->Servers)) {
            foreach ($respjson->Servers as $value) {
                if ($value->DisplayName == $servername) {
                    $serverid = $value->ServerId;
                    if ($serverid > 0) {
                        $roleapiendpoint = $apiurl;
                        $httpresponse = $this->makeRequest($this->getAPIURL($args) . "/Server-Roles?roleName=".$role, $args, "GET");
                        if ($httpresponse["code"] != 200) {
                            return $serverRoleId;
                        }
                        $respjson = json_decode($httpresponse["body"]);
                        $roleid = $respjson->Roles[0]->RoleId;

                        $httpresponse = $this->makeRequest($this->getAPIURL($args) . "/servers/".$serverid."/roles", $args, "GET");
                        if ($httpresponse["code"] != 200) {
                            return $serverRoleId;
                        }
                        $respjson = json_decode($httpresponse["body"]);
                        foreach ($respjson->ServerRoles as $value) {
                            if ($value->RoleId == $roleid) {
                                $serverRoleId = $value->ServerRoleId;
                                break;
                            }
                        }
                        break;
                    }
                }
            }
        }
        return $serverRoleId;
    }

    private function formatErrorMsg($response)
    {
        $respJson = json_decode($response["body"]);
        $errorDesc = $respJson->ErrorDesc;
        $errorCode = $respJson->ErrorCode;

        if (json_last_error() != JSON_ERROR_NONE || !isset($errorDesc) || $errorDesc == "") {
            return " Failed to connect to API.";
        }

        return " Error Code: $errorDesc ($errorCode).";
    }
}
