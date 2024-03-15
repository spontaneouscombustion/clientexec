<?php

require_once 'modules/admin/models/ServerPlugin.php';
require 'api/vendor/autoload.php';

class PluginEnhance extends ServerPlugin
{
    public $features = [
        'packageName' => true,
        'testConnection' => true,
        'showNameservers' => false,
        'directlink' => true,
        'upgrades' => true
    ];

    public function getVariables()
    {
        $variables = [
            'Name' => [
                'type' => 'hidden',
                'description' => 'Used by CE to show plugin',
                'value' => 'Enhance'
            ],
            'Description' => [
                'type' => 'hidden',
                'description' => 'Description viewable by admin in server settings',
                'value' => lang('Enhance Plugin')
            ],
            'Access Token' => [
                'type' => 'text',
                'description' => lang('Enter your Access token'),
                'value' => ''
            ],
            'orgId' => [
                'type' => 'text',
                'description' => lang('Enter your Enhance Organization ID'),
                'value' => '',
            ],
            "OrgId Custom Field" => [
                "type" => "text",
                "description" => lang("Enter the name of the package custom field that will hold the User Org Id."),
                "value" => ""
            ],
            "SubId Custom Field" => [
                "type" => "text",
                "description" => lang("Enter the name of the package custom field that will hold the User website subscription Id."),
                "value" => ""
            ],
            'Actions' => [
                'type' => 'hidden',
                'description' => 'Current actions that are active for this plugin per server',
                'value' => 'Create,Delete,Suspend,UnSuspend'
            ],
            'Registered Actions For Customer' => [
                'type' => 'hidden',
                'description' => 'Current actions that are active for this plugin per server for customers',
                'value' => ''
            ],
            'package_vars' => [
                'type' => 'hidden',
                'description' => 'Whether package settings are set',
                'value' => '0',
            ],
            'package_vars_values' => [
                'type'  => 'hidden',
                'description' => lang('Package Settings'),
                'value' => []
            ]
        ];

        return $variables;
    }

    public function validateCredentials($args)
    {
    }

    public function doDelete($args)
    {
        $userPackage = new UserPackage($args['userPackageId']);
        $args = $this->buildParams($userPackage);
        $orgId = $userPackage->getCustomField($args['server']['variables']['plugin_enhance_OrgId_Custom_Field'], CUSTOM_FIELDS_FOR_PACKAGE);
        $existing_sub = $userPackage->getCustomField($args['server']['variables']['plugin_enhance_SubId_Custom_Field'], CUSTOM_FIELDS_FOR_PACKAGE);
        $api = $this->getApiClient($args);
        $api['subscriptionsClient']->deleteSubscription($orgId, $existing_sub, "false");
        $userPackage->setCustomField($args['server']['variables']['plugin_enhance_SubId_Custom_Field'], "", CUSTOM_FIELDS_FOR_PACKAGE);
        return $userPackage->getCustomField("Domain Name") . ' has been deleted.';
    }

    public function doCreate($args)
    {
        $userPackage = new UserPackage($args['userPackageId']);
        $args = $this->buildParams($userPackage);

        $api = $this->getApiClient($args);
        $orgId = $userPackage->getCustomField(
            $args['server']['variables']['plugin_enhance_OrgId_Custom_Field'],
            CUSTOM_FIELDS_FOR_PACKAGE
        );
        if (!$orgId) {
            $orgId = $this->createCustomer($api, $args);
            $userPackage->setCustomField(
                $args['server']['variables']['plugin_enhance_OrgId_Custom_Field'],
                $orgId,
                CUSTOM_FIELDS_FOR_PACKAGE
            );
        }

        $planId = $this->getPlanId(
            $api,
            $args['server']['variables']['plugin_enhance_orgId'],
            $args['package']['name_on_server']
        );

        $new_subscription = new \OpenAPI\Client\Model\NewSubscription();
        $new_subscription->setPlanId(intval($planId));
        $subscription = $api['subscriptionsClient']->createCustomerSubscription(
            $args['server']['variables']['plugin_enhance_orgId'],
            $orgId,
            $new_subscription
        );

        $userPackage->setCustomField(
            $args['server']['variables']['plugin_enhance_SubId_Custom_Field'],
            $subscription['id'],
            CUSTOM_FIELDS_FOR_PACKAGE
        );

        $userPackage->setCustomField('User Name', $args['customer']['email']);

        // create the website for the customer
        $new_website = new \OpenAPI\Client\Model\NewWebsite();
        $new_website->setSubscriptionId($subscription['id']);
        $new_website->setDomain($args['package']['domain_name']);
        $website = $api['websitesClient']->createWebsite($orgId, $new_website);

        $update_website = new \OpenAPI\Client\Model\UpdateWebsite();
        $update_website->setPhpVersion(\OpenAPI\Client\Model\PhpVersion::PHP74);

        $api['websitesClient']->updateWebsite($orgId, $website['id'], $update_website);

        return $userPackage->getCustomField("Domain Name") . ' has been created.';
    }

    public function doSuspend($args)
    {
        $userPackage = new UserPackage($args['userPackageId']);
        $args = $this->buildParams($userPackage);
        $orgId = $userPackage->getCustomField($args['server']['variables']['plugin_enhance_OrgId_Custom_Field'], CUSTOM_FIELDS_FOR_PACKAGE);
        $existing_sub = $userPackage->getCustomField($args['server']['variables']['plugin_enhance_SubId_Custom_Field'], CUSTOM_FIELDS_FOR_PACKAGE);
        ;

        $api = $this->getApiClient($args);
        $update_subscription = new \OpenAPI\Client\Model\UpdateSubscription();
        $update_subscription->setIsSuspended(true);

        $api['subscriptionsClient']->updateSubscription($orgId, $existing_sub, $update_subscription);
        return $userPackage->getCustomField("Domain Name") . ' has been suspended.';
    }

    public function doUnSuspend($args)
    {
        $userPackage = new UserPackage($args['userPackageId']);
        $args = $this->buildParams($userPackage);
        $orgId = $userPackage->getCustomField($args['server']['variables']['plugin_enhance_OrgId_Custom_Field'], CUSTOM_FIELDS_FOR_PACKAGE);
        $existing_sub = $userPackage->getCustomField($args['server']['variables']['plugin_enhance_SubId_Custom_Field'], CUSTOM_FIELDS_FOR_PACKAGE);

        $api = $this->getApiClient($args);
        $update_subscription = new \OpenAPI\Client\Model\UpdateSubscription();
        $update_subscription->setIsSuspended(false);

        $api['subscriptionsClient']->updateSubscription($orgId, $existing_sub, $update_subscription);

        return $userPackage->getCustomField("Domain Name") . ' has been unsuspended.';
    }

    public function getApiClient($args)
    {
        $config = OpenAPI\Client\Configuration::getDefaultConfiguration()
            ->setAccessToken($args['server']['variables']['plugin_enhance_Access_Token'])
            ->setHost("https://" . $args['server']['variables']['ServerHostName'] . "/api");

        $guzzleConfig = [
            \GuzzleHttp\RequestOptions::VERIFY => \Composer\CaBundle\CaBundle::getSystemCaRootBundlePath()
        ];

        $customersClient = new OpenAPI\Client\Api\CustomersApi(
            new GuzzleHttp\Client($guzzleConfig),
            $config
        );
        $subscriptionsClient = new OpenAPI\Client\Api\SubscriptionsApi(
            new GuzzleHttp\Client($guzzleConfig),
            $config
        );
        $loginsClient = new OpenAPI\Client\Api\LoginsApi(
            new GuzzleHttp\Client($guzzleConfig),
            $config
        );
        $membersClient = new OpenAPI\Client\Api\MembersApi(
            new GuzzleHttp\Client($guzzleConfig),
            $config
        );
        $orgsClient = new OpenAPI\Client\Api\OrgsApi(
            new GuzzleHttp\Client($guzzleConfig),
            $config
        );

        $websitesClient = new OpenAPI\Client\Api\WebsitesApi(
            new GuzzleHttp\Client($guzzleConfig),
            $config
        );
        $serversClient = new OpenAPI\Client\Api\ServersApi(
            new GuzzleHttp\Client($guzzleConfig),
            $config
        );

        return array(
            "customersClient" => $customersClient,
            "subscriptionsClient" => $subscriptionsClient,
            "loginsClient" => $loginsClient,
            "membersClient" => $membersClient,
            "orgsClient" => $orgsClient,
            "websitesClient" => $websitesClient,
            'serversClient' => $serversClient
        );
    }

    private function createCustomer($api, $args)
    {
        $new_customer = new \OpenAPI\Client\Model\NewCustomer();
        $name = "Customer from CE";

        $user = new User($args['customer']['id']);

        if ($user->getOrganization() && $user->getOrganization() != "") {
            $name = $user->getOrganization();
        } else {
            $name = $user->getFirstName() . ' ' . $user->getLastname();
        }

        $new_customer->setName($name);
        $org = $api['customersClient']->createCustomer(
            $args['server']['variables']['plugin_enhance_orgId'],
            $new_customer
        );

        $new_login = new \OpenAPI\Client\Model\LoginInfo();

        $new_login->setEmail($args['customer']['email']);
        $new_login->setName($name);
        $new_login->setPassword($args['package']['password']);

        try {
            $login = $api['loginsClient']->createLogin($org['id'], $new_login);
        } catch (Exception $e) {
            throw new CE_Exception($e->getMessage());
        }

        $new_member = new \OpenAPI\Client\Model\NewMember();
        $new_member->setLoginId($login['id']);
        $new_member->setRoles([\OpenAPI\Client\Model\Role::OWNER]);

        try {
            $member = $api['membersClient']->createMember($org['id'], $new_member);
        } catch (Exception $e) {
            throw new CE_Exception($e->getMessage());
        }

        return $org['id'];
    }

    public function testConnection($args)
    {
        CE_Lib::log(4, 'Testing connection to Enhance server');
        $api = $this->getApiClient($args);
        try {
            $response = $api['serversClient']->getServers();
        } catch (Exception $e) {
            throw new CE_Exception($e->getMessage());
        }
    }

    public function getAvailableActions($userPackage)
    {
        $args = $this->buildParams($userPackage);
        $subId =  $userPackage->getCustomField(
            $args['server']['variables']['plugin_enhance_SubId_Custom_Field'],
            CUSTOM_FIELDS_FOR_PACKAGE
        );
        $orgId = $userPackage->getCustomField(
            $args['server']['variables']['plugin_enhance_OrgId_Custom_Field'],
            CUSTOM_FIELDS_FOR_PACKAGE
        );
        $actions = [];

        if (empty($subId)) {
            $actions[] = 'Create';
            return $actions;
        }

        $api = $this->getApiClient($args);

        $result = $api['subscriptionsClient']->getSubscription($orgId, $subId);
        if (empty($result['suspended_by'])) {
            $actions[] = 'Suspend';
        } else {
            $actions[] = 'UnSuspend';
        }
        $actions[] = 'Delete';

        return $actions;
    }

    public function getDirectLink($userPackage, $getRealLink = true, $fromAdmin = false, $isReseller = false)
    {
        $args = $this->buildParams($userPackage);
        $api = $this->getApiClient($args);
        $linkText = $this->user->lang('Login to Panel');

        if ($fromAdmin) {
            $cmd = 'panellogin';
            return [
                'cmd' => $cmd,
                'label' => 'Login to Enhance'
            ];
        } elseif ($getRealLink) {
            $orgId = $userPackage->getCustomField(
                $args['server']['variables']['plugin_enhance_OrgId_Custom_Field'],
                CUSTOM_FIELDS_FOR_PACKAGE
            );

            $members = $api['membersClient']->getMembers($orgId)->getItems();

            $owners = array_filter($members, function ($member) {
                $roles = $member->getRoles();
                return is_numeric(array_search("Owner", $roles));
            });

            $owner = $owners[0];

            if (!$owner) {
                throw new Exception("Unable to locate organization owner for direct login");
            }

            $ownerId = $owner->getId();

            $link = $api['membersClient']->getOrgMemberLogin($orgId, $ownerId);
            $link = trim($link, '"');

            return array(
                'fa' => 'fa fa-user fa-fw',
                'link' => $link,
                'text' => $linkText,
                'form' => ''
            );
        } else {
            $link = 'index.php?fuse=clients&controller=products&action=openpackagedirectlink&packageId=' . $userPackage->getId() . '&sessionHash=' . CE_Lib::getSessionHash();

            return [
                'fa' => 'fa fa-user fa-fw',
                'link' => $link,
                'text' => $linkText,
                'form' => ''
            ];
        }
    }

    public function dopanellogin($args)
    {
        $userPackage = new UserPackage($args['userPackageId']);
        $response = $this->getDirectLink($userPackage);
        return $response['link'];
    }

    public function doUpdate($args)
    {
        $userPackage = new UserPackage($args['userPackageId']);
        $this->update($this->buildParams($userPackage, $args));
        return $userPackage->getCustomField("Domain Name") . ' has been updated.';
    }

    public function update($args)
    {
        $api = $this->getApiClient($args);

        $subId =  $userPackage->getCustomField(
            $args['server']['variables']['plugin_enhance_SubId_Custom_Field'],
            CUSTOM_FIELDS_FOR_PACKAGE
        );
        $orgId = $userPackage->getCustomField(
            $args['server']['variables']['plugin_enhance_OrgId_Custom_Field'],
            CUSTOM_FIELDS_FOR_PACKAGE
        );

        foreach ($args['changes'] as $key => $value) {
            switch ($key) {
                case 'package':
                    $planId = $this->getPlanId(
                        $api,
                        $args['server']['variables']['plugin_enhance_orgId'],
                        $args['package']['name_on_server']
                    );

                    $api['subscriptionsClient']->updateSubscription(
                        $orgId,
                        $subId,
                        ['planId' => intval($planId)]
                    );

                    break;
            }
        }
    }

    private function getPlanId($api, $orgId, $planName)
    {
        $plans = $api['orgsClient']->getPlans($orgId)->getItems();

        foreach ($plans as $plan) {
            if ($planName == $plan->getName()) {
                return $plan->getId();
            }
        }
    }
}
