<?php

class PluginVirtfusiondirect extends ServerPlugin
{
    public $features = array(
        'packageName' => false,
        'testConnection' => true,
        'showNameservers' => false,
        'directlink' => true,
        'upgrades' => false
    );

    public function getVariables()
    {
        $variables = [
            lang("Name") => [
                "type" => "hidden",
                "description" => "Used By CE to show plugin - must match how you call the action function names",
                "value" => "VirtFusionDirect"
            ],
            lang("Description") => [
                "type" => "hidden",
                "description" => lang("Description viewable by admin in server settings"),
                "value" => lang("VirtFusion Direct control panel integration")
            ],
            lang("API Token") => [
                "type" => "textarea",
                "description" => lang("API Token"),
                "value" => "",
                "encryptable" => true
            ],
            lang("Actions") => [
                "type" => "hidden",
                "description" => lang("Current actions that are active for this plugin per server"),
                "value" => "Create,Delete,Suspend,UnSuspend"
            ],
            lang('reseller')  => [
                'type' => 'hidden',
                'description' => lang('Whether this server plugin can set reseller accounts'),
                'value' => '0',
            ],
            'package_vars_values' => [
                'type' => 'hidden',
                'description' => lang('VirtFusion Settings'),
                'value' => [
                    'hypervisor_id' => [
                        'type' => 'text',
                        'label' => lang('Hypervisor Group ID'),
                        'description' => lang('Enter the default Hypervisor Group ID.'),
                        'value' => '',
                    ],
                    'package_id' => [
                        'type' => 'text',
                        'label' => lang('Package ID'),
                        'description' => lang('Enter the package ID.'),
                        'value' => '',
                    ],
                    'num_of_ips' => [
                        'type' => 'text',
                        'label' => lang('Number of IPs'),
                        'description' => lang('Enter the number of IPs for this package.'),
                        'value' => '1',
                    ],
                ]
            ]
        ];

        return $variables;
    }

    public function doCreate($args)
    {
        $userPackage = new UserPackage($args['userPackageId']);
        $this->create($this->buildParams($userPackage));
        return 'Package has been created.';
    }

    public function create($args)
    {
        $userId = $this->findOrCreateUser($args);

        $response = $this->call(
            '/servers',
            $args,
            'POST',
            json_encode([
                'packageId' => $args['package']['variables']['package_id'],
                'userId' => $userId,
                'hypervisorId' => $args['package']['variables']['hypervisor_id'],
                'ipv4' => $args['package']['variables']['num_of_ips'],
            ])
        );

        if ($response['info']['http_code'] === 201) {
            $userPackage = new UserPackage($args['package']['id']);
            $userPackage->setCustomField('Server Acct Properties', $response['json']->data->id);
            $userPackage->setCustomField('Shared', 0);
            $userPackage->setCustomField(
                'IP Address',
                $response['json']->data->network->interfaces[0]->ipv4[0]->address
            );
        } else {
            if (isset($response['json']->errors)) {
                throw new CE_Exception($response['json']->errors[0]);
            } else {
                throw new CE_Exception('An unknown error occurred');
            }
        }
    }

    public function doUpdate($args)
    {
        $userPackage = new UserPackage($args['userPackageId']);
        $this->update($this->buildParams($userPackage, $args));
        // return 'Package has been updated.';
    }

    public function update($args)
    {
    }

    public function doDelete($args)
    {
        $userPackage = new UserPackage($args['userPackageId']);
        $this->delete($this->buildParams($userPackage));
        return 'Package has been deleted.';
    }

    public function delete($args)
    {
        $response = $this->call(
            "/servers/{$args['package']['ServerAcctProperties']}",
            $args,
            'DELETE'
        );
        if ($response['info']['http_code'] === 204) {
            $userPackage = new UserPackage($args['package']['id']);
            $userPackage->setCustomField('Server Acct Properties', '');
        } else {
            if (isset($response['json']->errors)) {
                throw new CE_Exception($response['json']->errors[0]);
            } elseif (isset($response['json']->message)) {
                throw new CE_Exception($response['json']->message);
            } elseif ($response['info']['http_code'] === 404) {
                if ($response['json']->msg == 'server not found') {
                    $userPackage = new UserPackage($args['package']['id']);
                    $userPackage->setCustomField('Server Acct Properties', '');
                }
                throw new CE_Exception('Server Not Found');
            } else {
                throw new CE_Exception('An unknown error occurred');
            }
        }
    }

    public function doSuspend($args)
    {
        $userPackage = new UserPackage($args['userPackageId']);
        $this->suspend($this->buildParams($userPackage));
        return 'Package has been suspended.';
    }

    public function suspend($args)
    {
        $response = $this->call(
            "/servers/{$args['package']['ServerAcctProperties']}/suspend",
            $args,
            'POST'
        );
        if ($response['info']['http_code'] !== 204) {
            if (isset($response['json']->errors)) {
                throw new CE_Exception($response['json']->errors[0]);
            } elseif (isset($response['json']->msg)) {
                throw new CE_Exception($response['json']->msg);
            } elseif ($response['info']['http_code'] === 404) {
                throw new CE_Exception('Server Not Found');
            } else {
                throw new CE_Exception('An unknown error occurred');
            }
        }
    }

    public function doUnSuspend($args)
    {
        $userPackage = new UserPackage($args['userPackageId']);
        $this->unsuspend($this->buildParams($userPackage));
        return 'Package has been unsuspended.';
    }

    public function unsuspend($args)
    {
        $response = $this->call(
            "/servers/{$args['package']['ServerAcctProperties']}/unsuspend",
            $args,
            'POST'
        );
        if ($response['info']['http_code'] !== 204) {
            if (isset($response['json']->errors)) {
                throw new CE_Exception($response['json']->errors[0]);
            } elseif (isset($response['json']->msg)) {
                throw new CE_Exception($response['json']->msg);
            } elseif ($response['info']['http_code'] === 404) {
                throw new CE_Exception('Server Not Found');
            } else {
                throw new CE_Exception('An unknown error occurred');
            }
        }
    }

    public function testConnection($args)
    {
        CE_Lib::log(4, 'Testing connection to VirtFusion');
        $response = $this->call('/users/1/byExtRelation', $args);
        if ($response['info']['http_code'] === 401) {
            throw new CE_Exception("Connection to server failed.");
        }
    }

    public function getAvailableActions($userPackage)
    {
        $args = $this->buildParams($userPackage);
        $serverId = $args['package']['ServerAcctProperties'];

        if ($args['package']['ServerAcctProperties'] == '') {
            $actions[] = 'Create';
            return $actions;
        }

        $response = $this->call(
            "/servers/{$args['package']['ServerAcctProperties']}",
            $args
        );

        if ($response['info']['http_code'] === 200) {
            if ($response['json']->data->suspended) {
                $actions[] = 'UnSuspend';
            } else {
                $actions[] = 'Suspend';
            }
            $actions[] = 'Delete';
        }

        return $actions;
    }

    public function getDirectLink($userPackage, $getRealLink = true, $fromAdmin = false, $isReseller = false)
    {
        $args = $this->buildParams($userPackage);

        $linkText = $this->user->lang('Login to Panel');

        if ($fromAdmin) {
            $cmd = 'panellogin';
            return [
                'cmd' => $cmd,
                'label' => $linkText
            ];
        } elseif ($getRealLink) {
            $response = $this->call(
                '/users/' . $args['customer']['id'] . '/serverAuthenticationTokens/' . $args['package']['ServerAcctProperties'],
                $args,
                'POST'
            );
            $ssoUrl = 'https://' . $args['server']['variables']['ServerHostName'] . $response['json']->data->authentication->endpoint_complete;

            return array(
                'fa' => 'fa fa-user fa-fw',
                'link' => $ssoUrl,
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

    private function findUser($args)
    {
        $response = $this->call(
            '/users/' . $args['customer']['id'] . '/byExtRelation',
            $args
        );
        if ($response['info']['http_code'] == 404) {
            return false;
        }

        return $response['json']->data->id;
    }

    private function findOrCreateUser($args)
    {
        $userId = $this->findUser($args);
        if ($userId !== false) {
            return $userId;
        }

        $response = $this->call(
            '/users',
            $args,
            'POST',
            json_encode([
                'name' => $args['customer']['first_name'] . ' ' . $args['customer']['last_name'],
                'email' => $args['customer']['email'],
                'extRelationId' => $args['customer']['id']
            ])
        );

        if ($response['info']['http_code'] != 201) {
            throw new CE_Exception($this->user->lang('Unable to create user!'));
        }

        return $response['json']->data->id;
    }

    private function call($url, $args, $method = 'GET', $postData = [])
    {
        $host = 'https://' . $args['server']['variables']['ServerHostName'] . '/api/v1' . $url;

        $headers = [
            'Accept: application/json',
            'Content-type: application/json; charset=utf-8',
            'authorization: Bearer ' . $args['server']['variables']['plugin_virtfusiondirect_API_Token']
        ];

        $ch = curl_init($host);
        $caPathOrFile = \Composer\CaBundle\CaBundle::getSystemCaRootBundlePath();
        if (is_dir($caPathOrFile)) {
            curl_setopt($ch, CURLOPT_CAPATH, $caPathOrFile);
        } else {
            curl_setopt($ch, CURLOPT_CAINFO, $caPathOrFile);
        }
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if ($postData) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        }

        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            CE_Lib::log(4, curl_error($ch));
            throw new CE_Exception(curl_error($ch));
        }
        $curlInfo = curl_getinfo($ch);
        curl_close($ch);

        return [
            'info' => $curlInfo,
            'json' => json_decode($response)
        ];
    }
}
