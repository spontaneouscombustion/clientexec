<?php

class PluginStratumspheremtm extends ServerPlugin
{

    public $features = array(
        'packageName' => false,
        'testConnection' => true,
        'showNameservers' => false,
        'directlink' => false,
        'publicView' => true
    );

    function getVariables()
    {

        $variables = array (
            lang("Name") => array (
                "type"=>"hidden",
                "description"=>"Used by CE to show plugin - must match how you call the action function names",
                "value"=>"Stratumsphere (Miner-To-Miner)"
            ),
            lang("Description") => array (
                "type"=>"hidden",
                "description"=>lang("Description viewable by admin in server settings"),
                "value"=>lang("Stratumsphere Miner to Miner Integration")
            ),
            lang("API Key") => array (
                "type"=>"text",
                "description"=>lang("API Key"),
                "value"=>"",
                "encryptable"=>true
            ),
            lang("Primary Pool Hostname Custom Field") => array(
                "type"        => "text",
                "description" => "",
                "value"       => ""
            ),
            lang("Primary Pool Username Custom Field") => array(
                "type"        => "text",
                "description" => "",
                "value"       => ""
            ),
            lang("Primary Pool Password Custom Field") => array(
                "type"        => "text",
                "description" => "",
                "value"       => ""
            ),
            lang("Secondary Pool Hostname Custom Field") => array(
                "type"        => "text",
                "description" => "",
                "value"       => ""
            ),
            lang("Secondary Pool Username Custom Field") => array(
                "type"        => "text",
                "description" => "",
                "value"       => ""
            ),
            lang("Secondary Pool Password Custom Field") => array(
                "type"        => "text",
                "description" => "",
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
                'description'     => lang('Stratumsphere Settings'),
                'value'           => array(
                    'schedulerId' => array(
                        'type'            => 'text',
                        'label'           => 'Scheduler ID',
                        'description'     => lang('Enter the ID of the scheduler for this product.'),
                        'value'           => '',
                    ),
                    'numberWorkers' => array(
                        'type'            => 'text',
                        'label'           => 'Number of Workers',
                        'description'     => lang('Enter the number of works for this package.'),
                        'value'           => '',
                    ),
                    'appendWorkerNames' => array(
                        'type'            => 'yesno',
                        'label'            => 'Append Worker Names',
                        'description'     => '',
                        'value'           => '0',
                    ),
                )
            )
        );

        return $variables;
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
        $this->delete($args);
        return 'Package has been suspended (deleted).';
    }

    function doUnSuspend($args)
    {
        $userPackage = new UserPackage($args['userPackageId']);
        $args = $this->buildParams($userPackage);
        $this->create($args);
        return 'Package has been unsuspended (re-created).';
    }

    function doUpdate($args)
    {
        $userPackage = new UserPackage($args['userPackageId']);
        $this->update($this->buildParams($userPackage, $args));
        return 'Package has been updated.';
    }

    function update($args)
    {
        $userPackage = new UserPackage($args['package']['id']);
        $rentalId = $args['package']['ServerAcctProperties'];

        $url = "scheduler-rental/" . $rentalId;
        $params = [
            'title' => $userPackage->getReference(true),
            'host' => $userPackage->getCustomField($args['server']['variables']['plugin_stratumspheremtm_Primary_Pool_Hostname_Custom_Field'], CUSTOM_FIELDS_FOR_PACKAGE),
            'user' => $userPackage->getCustomField($args['server']['variables']['plugin_stratumspheremtm_Primary_Pool_Username_Custom_Field'], CUSTOM_FIELDS_FOR_PACKAGE),
            'password' => $userPackage->getCustomField($args['server']['variables']['plugin_stratumspheremtm_Primary_Pool_Password_Custom_Field'], CUSTOM_FIELDS_FOR_PACKAGE),
            'failoverHost' => $userPackage->getCustomField($args['server']['variables']['plugin_stratumspheremtm_Secondary_Pool_Hostname_Custom_Field'], CUSTOM_FIELDS_FOR_PACKAGE),
            'failoverUser' => $userPackage->getCustomField($args['server']['variables']['plugin_stratumspheremtm_Secondary_Pool_Password_Custom_Field'], CUSTOM_FIELDS_FOR_PACKAGE),
            'failoverPassword' => $userPackage->getCustomField($args['server']['variables']['plugin_stratumspheremtm_Secondary_Pool_Password_Custom_Field'], CUSTOM_FIELDS_FOR_PACKAGE),
        ];
        $response = $this->call($url, $params, $args, 'PUT');
        if ($response['success'] == false) {
            throw new CE_Exception($response['message']);
        }
    }

    function delete($args)
    {
        $userPackage = new UserPackage($args['package']['id']);
        $rentalId = $args['package']['ServerAcctProperties'];
        $url = "scheduler-rental/{$rentalId}";
        $response = $this->call($url, [], $args, 'DELETE');
        if ($response['success'] == 1) {
            $userPackage->setCustomField('Server Acct Properties', $response['data']['id']);
        }
    }

    function getAvailableActions($userPackage)
    {
        $actions = [];
        $args = $this->buildParams($userPackage);

        if ($args['package']['ServerAcctProperties'] == '') {
            $actions[] = 'Create';
        } else {
            $actions[] = 'Delete';
        }
        return $actions;
    }

    function create($args)
    {
        $userPackage = new UserPackage($args['package']['id']);
        $schedulerId = $args['package']['variables']['schedulerId'];

        $url = "scheduler/{$schedulerId}/scheduler-rentals";
        $params = [
            'title' => $userPackage->getReference(true),
            'host' => $userPackage->getCustomField($args['server']['variables']['plugin_stratumspheremtm_Primary_Pool_Hostname_Custom_Field'], CUSTOM_FIELDS_FOR_PACKAGE),
            'user' => $userPackage->getCustomField($args['server']['variables']['plugin_stratumspheremtm_Primary_Pool_Username_Custom_Field'], CUSTOM_FIELDS_FOR_PACKAGE),
            'password' => $userPackage->getCustomField($args['server']['variables']['plugin_stratumspheremtm_Primary_Pool_Password_Custom_Field'], CUSTOM_FIELDS_FOR_PACKAGE),
            'workerNumberMaintained' => $args['package']['variables']['numberWorkers'],
            'appendWorkerNames' => (isset($args['package']['variables']['appendWorkerName']) && $args['package']['variables']['appendWorkerName'] == '1' ? 1 : 0),
            'failoverHost' => $userPackage->getCustomField($args['server']['variables']['plugin_stratumspheremtm_Secondary_Pool_Hostname_Custom_Field'], CUSTOM_FIELDS_FOR_PACKAGE),
            'failoverUser' => $userPackage->getCustomField($args['server']['variables']['plugin_stratumspheremtm_Secondary_Pool_Password_Custom_Field'], CUSTOM_FIELDS_FOR_PACKAGE),
            'failoverPassword' => $userPackage->getCustomField($args['server']['variables']['plugin_stratumspheremtm_Secondary_Pool_Password_Custom_Field'], CUSTOM_FIELDS_FOR_PACKAGE),
            'isPersonal' => 'false',
            'useWorkerPassword' => 'true',
            'enableExtranonceSubscribe' => 'true',
            'workerNameSeparator' => '.'
        ];

        $response = $this->call($url, $params, $args);
        if ($response['success'] == 1) {
            $userPackage->setCustomField('Server Acct Properties', $response['data']['id']);
        }
    }

    function testConnection($args)
    {
        CE_Lib::log(4, 'Testing connection to stratumsphere');
        $response = $this->call('schedulers', [], $args);
        CE_Lib::log(4, $response);
        if ($response->error || strtolower($response->message) == 'invalid key') {
            throw new CE_Exception("Connection to server failed.");
        }
    }


    function call($url, $params, $args, $request = 'POST')
    {
        if (!function_exists('curl_init')) {
            throw new CE_Exception('cURL is required in order to connect to SolusVM');
        }
        $apiKey = $args['server']['variables']['plugin_stratumspheremtm_API_Key'];
        $url = "https://api.stratumsphere.io/v1/" . $url;

        CE_Lib::log(4, $url);
        CE_Lib::log(4, 'Stratumsphere Params: ' . json_encode($params));

        $ch = curl_init();
        curl_setopt_array($ch, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $request,
            CURLOPT_POSTFIELDS => json_encode($params),
            CURLOPT_HTTPHEADER => array(
                "Content-Type: application/json",
                "authorization: {$apiKey}"
            )
        ));
        $caPathOrFile = \Composer\CaBundle\CaBundle::getSystemCaRootBundlePath();
        if (is_dir($caPathOrFile)) {
            curl_setopt($ch, CURLOPT_CAPATH, $caPathOrFile);
        } else {
            curl_setopt($ch, CURLOPT_CAINFO, $caPathOrFile);
        }

        $response = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        CE_Lib::log(4, "cURL Response: " . $response);

        if ($err) {
            throw new CE_Exception($err);
        } else {
            return json_decode($response, true);
        }
    }


    function show_publicviews($userPackage, $action)
    {
        $args = $this->buildParams($userPackage);
        if ($args['package']['ServerAcctProperties'] == '') {
            return '';
        }
        $rentalId = $args['package']['ServerAcctProperties'];

        $action->view->addScriptPath(APPLICATION_PATH.'/../plugins/server/stratumspheremtm/');

        $url = 'scheduler-rental/' . $rentalId;
        $details = $this->call($url, [], $args, 'GET');
        $action->view->isStable = $details['isStable'];
        $action->view->hashRate = $this->formattedHashRate($details['hashrate']);
        $action->view->rejectedHashesPerSeconds = $this->formattedHashRate($details['rejectedHashesPerSeconds']);
        $action->view->connections = $details['connections_count'];

        return $action->view->render('main.phtml');
    }

    function formattedHashRate($hashrate)
    {
        if ($hashrate == '') {
            return '-';
        }

        $i = 0;
        $byteUnits = [' H', ' KH', ' MH', ' GH', ' TH', ' PH' ];
        do {
            $hashrate = $hashrate / 1024;
            $i++;
        } while ($hashrate > 1024);
        return $i > 0 ? number_format($hashrate, 2) . $byteUnits[$i] : number_format($hashrate, 0) . $byteUnits[$i];
    }
}
