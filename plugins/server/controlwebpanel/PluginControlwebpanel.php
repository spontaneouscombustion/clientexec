<?php

require_once 'modules/admin/models/ServerPlugin.php';

class PluginControlwebpanel extends ServerPlugin
{

    public $features = [
        'packageName' => true,
        'testConnection' => true,
        'showNameservers' => true,
        'directlink' => false
    ];

    public function getVariables()
    {
        $variables = [
            'Name' => [
                'type' => 'hidden',
                'description' => 'Used by CE to show plugin',
                'value' => 'Control Web Panel'
            ],
            'Description' => [
                'type' => 'hidden',
                'description' => 'Description viewable by admin in server settings',
                'value' => lang('Control Web Panel Plugin')
            ],
            'API Key' => [
                'type' => 'text',
                'description' => lang('API Key'),
                'value' => '',
                'encryptable' => true
            ],
            'inode' => [
                'type' => 'text',
                'description' => lang('Max number of inodes'),
                'value' => '0',
            ],
            'nofile' => [
                'type' => 'text',
                'description' => lang('Max number of files'),
                'value' => '100',
            ],
            'noproc' => [
                'type' => 'text',
                'description' => lang('Max number of procs'),
                'value' => '50',
            ],
            'Actions' => [
                'type' => 'hidden',
                'description' => 'Current actions that are active for this plugin per server',
                'value'=>'Create,Delete,Suspend,UnSuspend'
            ],
            'Registered Actions For Customer' => [
                'type' => 'hidden',
                'description' => 'Current actions that are active for this plugin per server for customers',
                'value' => ''
            ],
            'package_addons' => [
                'type' => 'hidden',
                'description' => 'Supported signup addons variables',
                'value' => []
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
        $this->delete($args);
        return $userPackage->getCustomField("Domain Name") . ' has been deleted.';
    }

    public function doCreate($args)
    {
        $userPackage = new UserPackage($args['userPackageId']);
        $args = $this->buildParams($userPackage);
        $this->create($args);
        return $userPackage->getCustomField("Domain Name") . ' has been created.';
    }

    public function doSuspend($args)
    {
        $userPackage = new UserPackage($args['userPackageId']);
        $args = $this->buildParams($userPackage);
        $this->suspend($args);
        return $userPackage->getCustomField("Domain Name") . ' has been suspended.';
    }

    public function doUnSuspend($args)
    {
        $userPackage = new UserPackage($args['userPackageId']);
        $args = $this->buildParams($userPackage);
        $this->unsuspend($args);
        return $userPackage->getCustomField("Domain Name") . ' has been unsuspended.';
    }

    public function unsuspend($args)
    {
        $data = [
            'action' => 'unsp',
            'user' => $args['package']['username'],
        ];

        $response = $this->makeRequest('/v1/account', $args, 'POST', $data);
        if (strpos($response, 'OK') === false) {
            $response = json_decode($response);
            throw new CE_Exception($response->msj);
        }
    }

    public function suspend($args)
    {
        $data = [
            'action' => 'susp',
            'user' => $args['package']['username'],
        ];

        $response = $this->makeRequest('/v1/account', $args, 'POST', $data);
        if (strpos($response, 'OK') === false) {
            $response = json_decode($response);
            throw new CE_Exception($response->msj);
        }
    }

    public function delete($args)
    {
        $data = [
            'action' => 'del',
            'user' => $args['package']['username'],
        ];

        $response = $this->makeRequest('/v1/account', $args, 'POST', $data);
        if (strpos($response, 'OK') === false) {
            $response = json_decode($response);
            throw new CE_Exception($response->msj);
        }
    }

    public function create($args)
    {
        $userPackage = new UserPackage($args['package']['id']);

        $data = [
            'package' => strtolower($args['package']['name_on_server']),
            'domain' => $args['package']['domain_name'],
            'action' => 'add',
            'username' => $args['package']['username'],
            'user' => $args['package']['username'],
            'pass' => $args['package']['password'],
            'email' => $args['customer']['email'],
            'inode' => $args['server']['variables']['plugin_controlwebpanel_inode'],
            'limit_nproc' => $args['server']['variables']['plugin_controlwebpanel_nproc'],
            'limit_nofile' => $args['server']['variables']['plugin_controlwebpanel_nofile'],
            'server_ips' => $args['package']['ip']
        ];

        $response = json_decode($this->makeRequest('/v1/account', $args, 'POST', $data));
        if ($response->status != 'OK') {
            throw new CE_Exception($response->msj);
        }
    }

    public function testConnection($args)
    {
        CE_Lib::log(4, 'Testing connection to CentOS Web Panel server');

        $data = [
            'action' => 'list'
        ];
        $response = json_decode($this->makeRequest('/v1/typeserver', $args, 'POST', $data));
        if ($response->status != 'OK') {
            throw new CE_Exception($response->msj);
        }
    }

    function getAvailableActions($userPackage)
    {
        $args = $this->buildParams($userPackage);
        $actions = array();

        if ($args['package']['username'] == '') {
            // no username, so just pass create, and return
            $actions[] = 'Create';
            return $actions;
        }

        $data = [
            'action' => 'list',
            'user' => $args['package']['username']
        ];

        $request = json_decode($this->makeRequest('/v1/accountdetail', $args, 'POST', $data));
        if ($request->status == 'Error') {
            $actions[] = 'Create';
        } else {
            $actions[] = 'Delete';
            if ($request->result->account_info->state == 'suspended') {
                $actions[] = 'UnSuspend';
            } else {
                $actions[] = 'Suspend';
            }
        }

        return $actions;
    }

    private function makeRequest($action, $args, $method = 'GET', $data = [])
    {
        $data['key'] = trim($args['server']['variables']['plugin_controlwebpanel_API_Key']);
        $data = http_build_query($data);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://' . $args['server']['variables']['ServerHostName'] . ':2304' . $action);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $caPathOrFile = \Composer\CaBundle\CaBundle::getSystemCaRootBundlePath();
        if (is_dir($caPathOrFile)) {
            curl_setopt($ch, CURLOPT_CAPATH, $caPathOrFile);
        } else {
            curl_setopt($ch, CURLOPT_CAINFO, $caPathOrFile);
        }
        switch ($method) {
            case 'GET':
                break;
            case 'POST':
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                break;
            case 'PUT':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                break;
            case 'DELETE':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
                break;
        }

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            throw new CE_Exception('CWP connection error: ' . curl_error($ch));
        }

        // We can not json_decode this, it has to be done in the calling function, as the API likes to add garbag to some responses.
        return $response;
    }
}
