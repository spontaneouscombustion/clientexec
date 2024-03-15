<?php

require_once 'plugins/server/ispmanager/helper.ispmanager.php';
require_once 'modules/admin/models/ServerPlugin.php';
require_once 'library/CE/NE_Network.php';

class PluginISPManager extends ServerPlugin
{
    public $settings;
    public $features = [
        'packageName' => true,
        'testConnection' => false,
        'showNameservers' => true,
        'upgrades' => true
    ];

    public function getVariables()
    {
        $variables = [
            lang("Name") => [
                "type" => "hidden",
                "description" => lang("Used By CE to show plugin - must match how you call the action function names"),
                "value" => "ISPmanager"
            ],
            lang("Description") => [
                "type" => "hidden",
                "description" => lang("Description viewable by admin in server settings"),
                "value" => lang("ISPmanager Panel Integration")
            ],
            lang("Username") => [
                "type" => "text",
                "description" => lang("Username used to connect to server"),
                "value" => ""
            ],
            lang("Password") => [
                "type" => "password",
                "description" => lang("Password used to connect to server"),
                "value" => "",
                "encryptable" => true
            ],
            lang('Port') => [
                'type' => 'text',
                'description' => lang('Port used to connect to server'),
                'value' => '1500'
            ],
            lang("Actions") => [
                "type" => "hidden",
                "description" => lang("Current actions that are active for this plugin per server"),
                "value" => "Create,Delete,Suspend,UnSuspend"
            ],
            lang('package_vars')  => [
                'type'          => 'hidden',
                'description'   => lang('Whether package settings are set'),
                'value'         => '0',
            ],
            lang('package_vars_values') => [
                'type'          => 'hidden',
                'description'   => lang('Hosting account parameters'),
                'value'         => [
                    'ftplimit'       => [
                        'type'           => 'text',
                        'description'    => lang('Maximum number of ftp-users'),
                        'value'          => '',
                    ],
                    'maillimit'           => [
                        'type'           => 'text',
                        'description'    => lang('Maximum number of mail boxes'),
                        'value'          => '1',
                    ],
                    'domainlimit'    => [
                        'type'           => 'text',
                        'description'    => lang('Maximum number of domain name zones'),
                        'value'          => '',
                    ],
                    'disklimit'    => [
                        'type'           => 'text',
                        'description'    => lang('Disk space (in bytes, leave empty for unlimited)'),
                        'value'          => '',
                    ],
                    'webdomainlimit'   => [
                        'type'           => 'text',
                        'description'    => lang('Maximum number of web sites'),
                        'value'          => '',
                    ],
                    'maildomainlimit'        => [
                        'type'           => 'text',
                        'description'    => lang('Maximum number of mail domains'),
                        'value'          => '',
                    ],
                    'baselimit'        => [
                        'type'           => 'text',
                        'description'    => lang('Maximum number of databases'),
                        'value'          => '',
                    ],
                    'baseuserlimit'       => [
                        'type'           => 'text',
                        'description'    => lang('Maximum number of database users'),
                        'value'          => '',
                    ],
                    'bandwidthlimit'    => [
                        'type'           => 'text',
                        'description'    => lang('Traffic quota (in kbytes)'),
                        'value'          => '',
                    ],
                    'ssl'           => [
                        'type'          => 'yesno',
                        'description'   => lang('SSL support'),
                        'value'         => '0',
                    ],
                    'shell'         => [
                        'type'          => 'yesno',
                        'description'   => lang('System shell'),
                        'value'         => '',
                    ],
                    'phpmod'           => [
                        'type'          => 'yesno',
                        'description'   => lang('PHP as Apache Module support'),
                        'value'         => '0',
                    ],
                    'phpcgi'           => [
                        'type'          => 'yesno',
                        'description'   => lang('PHP as CGI support'),
                        'value'         => '0',
                    ],
                    'phpfcgi'           => [
                        'type'          => 'yesno',
                        'description'   => lang('PHP as FastCGI support'),
                        'value'         => '0',
                    ],
                    'ssi'           => [
                        'type'          => 'yesno',
                        'description'   => lang('SSI support'),
                        'value'         => '0',
                    ],
                    'cgi'           => [
                        'type'          => 'yesno',
                        'description'   => lang('CGI support'),
                        'value'         => '0',
                    ],
                ],
            ],
            lang('package_addons') => [
                'type'          => 'hidden',
                'description'   => lang('Supported signup addons variables'),
                'value'         => [
                    'DISKSPACE', 'BANDWIDTH', 'SSH_ACCESS', 'SSL'
                ],
            ]
        ];
        return $variables;
    }

    public function sendRequest($args, $req, $outtype = 'xml')
    {
        $url = "https://" . $args['server']['variables']['ServerHostName'] . ':' . $args['server']['variables']['plugin_ispmanager_Port'] . "/manager/ispmgr?authinfo=" . $args['server']['variables']['plugin_ispmanager_Username'] . ":" . $args['server']['variables']['plugin_ispmanager_Password'] . "&out=" . $outtype . "&" . $req;
        return NE_Network::curlRequest($this->settings, $url, '', '', true, false);
    }

    public function restart($args)
    {
        $res = false;
        $result = $this->sendRequest($args, "func=restart", "text");
        if ($result == "OK ") {
            $res = true;
        }
        return $res;
    }

    public function checkPreset($args, $preset)
    {
        $preset_found = false;
        $ret = $this->sendRequest($args, "func=preset", "xml");
        $objXML = new xml2Array();
        $arrOutput = $objXML->parse($ret);
        if (isset($arrOutput[0]['children'])) {
            foreach ($arrOutput[0]['children'] as $el) {
                if (!isset($el['children'])) {
                    continue;
                }
                foreach ($el['children'] as $elem) {
                    if ($elem['name'] == "NAME" && $elem['tagData'] == $preset) {
                        $preset_found = true;
                    }
                }
            }
        }
        // Making Preset if not exists
        if (!$preset_found) {
            $preset_found = $this->createPreset($args, $preset);
        }

        return $preset_found;
    }

    public function createPreset($args, $name)
    {
        $ssi = "off";
        $ssl = "off";
        $shell = "off";
        $cgi = "off";
        $phpmod = "off";
        $phpcgi = "off";
        $phpfcgi = "off";
        $ptype = "user";
        $pv = $args['package']['variables'];
        $res = false;
        if (isset($pv['ssi']) && $pv['ssi'] == 1) {
            $ssi = "on";
        }
        if (isset($pv['ssl']) && $pv['ssl'] == 1) {
            $ssl = "on";
        }
        if (isset($pv['cgi']) && $pv['cgi'] == 1) {
            $cgi = "on";
        }
        if (isset($pv['shell']) && $pv['shell'] == 1) {
            $shell = "on";
        }
        if (isset($pv['phpcgi']) && $pv['phpcgi'] == 1) {
            $phpcgi = "on";
        }
        if (isset($pv['phpmod']) && $pv['phpmod'] == 1) {
            $phpmod = "on";
        }
        if (isset($pv['phpfcgi']) && $pv['phpfcgi'] == 1) {
            $phpfcgi = "on";
        }
        $q = "name=" . $name . "&ptype=" . $ptype . "&disklimit=" . $pv['disklimit'] . "&ftplimit=" . $pv['ftplimit'] . "&maillimit=" . $pv['maillimit'] . "&domainlimit=" . $pv['domainlimit'] . "&webdomainlimit=" . $pv['webdomainlimit'] . "&maildomainlimit=" . $pv['maildomainlimit'] . "&baselimit=" . $pv['baselimit'] . "&baseuserlimit=" . $pv['baseuserlimit'] . "&bandwidthlimit=" . $pv['bandwidthlimit'] . "&ssi=" . $ssi . "&ssl=" . $ssl . "&shell=" . $shell . "&cgi=" . $cgi . "&phpfcgi=" . $phpfcgi . "&phpcgi=" . $phpcgi . "&phpmod=" . $phpmod . "&func=preset.edit&elid=&sok=ok&suok=++++Ok++++";
        $result = $this->sendRequest($args, $q, "text");
        if ($result == "OK ") {
            $res = true;
        } else {
            throw new CE_Exception("ISPmanager can't create Preset");
        }
        return $res;
    }

    //plugin function called after new account is activated ( approved )
    public function create($args)
    {
        if ($this->checkPreset($args, $args['package']['name_on_server'])) {
            $ssi = "off";
            $ssl = "off";
            $shell = "off";
            $cgi = "off";
            $phpmod = "off";
            $phpcgi = "off";
            $phpfcgi = "off";
            $ptype = "user";
            $pv = $args['package_vars'];
            if (isset($args['package']['addons']['DISKSPACE'])) {
                $pv['disklimit'] += ((int)$args['package']['addons']['DISKSPACE']);
            }
            if (isset($args['package']['addons']['BANDWIDTH'])) {
                $pv['bandwidthlimit'] += ((int)$args['package']['addons']['BANDWIDTH']);
            }
            if (isset($args['package']['addons']['SSH_ACCESS']) && $args['package']['addons']['SSH_ACCESS'] == 1) {
                $pv['shell'] = 1;
            }
            if (isset($args['package']['addons']['SSL']) && $args['package']['addons']['SSL'] == 1) {
                $pv['ssl'] = 1;
            }
            if (isset($pv['ssi']) && $pv['ssi'] == 1) {
                $ssi = "on";
            }
            if (isset($pv['ssl']) && $pv['ssl'] == 1) {
                $ssl = "on";
            }
            if (isset($pv['cgi']) && $pv['cgi'] == 1) {
                $cgi = "on";
            }
            if (isset($pv['shell']) && $pv['shell'] == 1) {
                $shell = "on";
            }
            if (isset($pv['phpcgi']) && $pv['phpcgi'] == 1) {
                $phpcgi = "on";
            }
            if (isset($pv['phpmod']) && $pv['phpmod'] == 1) {
                $phpmod = "on";
            }
            if (isset($pv['phpfcgi']) && $pv['phpfcgi'] == 1) {
                $phpfcgi = "on";
            }
            $q = "name=" . $args['package']['username'] . "&passwd=" . $args['package']['password'] . "&confirm=" . $args['package']['password'] . "&ptype=" . $ptype . "&domain=" . $args['package']['domain_name'] . "&ip=" . $args['package']['ip'] . "&preset=" . urlencode($args['package']['name_on_server']) . "&disklimit=" . $pv['disklimit'] . "&ftplimit=" . $pv['ftplimit'] . "&maillimit=" . $pv['maillimit'] . "&domainlimit=" . $pv['domainlimit'] . "&webdomainlimit=" . $pv['webdomainlimit'] . "&maildomainlimit=" . $pv['maildomainlimit'] . "&baselimit=" . $pv['baselimit'] . "&baseuserlimit=" . $pv['baseuserlimit'] . "&bandwidthlimit=" . $pv['bandwidthlimit'] . "&ssi=" . $ssi . "&ssl=" . $ssl . "&shell=" . $shell . "&cgi=" . $cgi . "&phpfcgi=" . $phpfcgi . "&phpcgi=" . $phpcgi . "&phpmod=" . $phpmod . "&func=user.edit&elid=&sok=ok&suok=++++Ok++++";
            $result = $this->sendRequest($args, $q, "xml");
            $objXML = new xml2Array();
            $arrOutput = $objXML->parse($result);
            foreach ($arrOutput[0]['children'] as $el) {
                if ($el['name'] == 'ERROR') {
                    throw new CE_Exception("ISPManager error #" . $el['attrs']['CODE'] . " in object \"" . $el['attrs']['OBJ'] . "\"");
                }
                if ($el['name'] == 'OK' && $el['tagData'] == 'restart') {
                    return $this->restart($args);
                }
            }
        }
        return false;
    }

    public function delete($args)
    {
        $result = $this->sendRequest($args, "func=user.delete&elid=" . $args['package']['username'], "xml");
        $objXML = new xml2Array();
        $arrOutput = $objXML->parse($result);
        foreach ($arrOutput[0]['children'] as $el) {
            if ($el['name'] == 'OK' && $el['tagData'] == 'restart') {
                return $this->restart($args);
            }
        }
        return false;
    }

    public function update($args, $userPackage = null)
    {
        $package = $args['CHANGE_PACKAGE'];
        if ($this->checkPreset($args, $package)) {
            $ssi = "off";
            $ssl = "off";
            $shell = "off";
            $cgi = "off";
            $phpmod = "off";
            $phpcgi = "off";
            $phpfcgi = "off";
            $ptype = "user";
            $pv = $args['package']['variables'];
            if (isset($args['package']['addons']['DISKSPACE'])) {
                $pv['disklimit'] += ((int)$args['package']['addons']['DISKSPACE']);
            }
            if (isset($args['package']['addons']['BANDWIDTH'])) {
                $pv['bandwidthlimit'] += ((int)$args['package']['addons']['BANDWIDTH']);
            }
            if (isset($args['package']['addons']['SSH_ACCESS']) && $args['package']['addons']['SSH_ACCESS'] == 1) {
                $pv['shell'] = 1;
            }
            if (isset($args['package']['addons']['SSL']) && $args['package']['addons']['SSL'] == 1) {
                $pv['ssl'] = 1;
            }
            if (isset($pv['ssi']) && $pv['ssi'] == 1) {
                $ssi = "on";
            }
            if (isset($pv['ssl']) && $pv['ssl'] == 1) {
                $ssl = "on";
            }
            if (isset($pv['cgi']) && $pv['cgi'] == 1) {
                $cgi = "on";
            }
            if (isset($pv['shell']) && $pv['shell'] == 1) {
                $shell = "on";
            }
            if (isset($pv['phpcgi']) && $pv['phpcgi'] == 1) {
                $phpcgi = "on";
            }
            if (isset($pv['phpmod']) && $pv['phpmod'] == 1) {
                $phpmod = "on";
            }
            if (isset($pv['phpfcgi']) && $pv['phpfcgi'] == 1) {
                $phpfcgi = "on";
            }
            $q = "name=" . $args['package']['username'] . "&passwd=" . $args['changes']['password'] . "&confirm=" . $args['changes']['password'] . "&ptype=" . $ptype . "&ip=" . $args['package']['ip'] . "&preset=" . $args['package']['name_on_server'] . "&disklimit=" . $pv['disklimit'] . "&ftplimit=" . $pv['ftplimit'] . "&maillimit=" . $pv['maillimit'] . "&domainlimit=" . $pv['domainlimit'] . "&webdomainlimit=" . $pv['webdomainlimit'] . "&maildomainlimit=" . $pv['maildomainlimit'] . "&baselimit=" . $pv['baselimit'] . "&baseuserlimit=" . $pv['baseuserlimit'] . "&bandwidthlimit=" . $pv['bandwidthlimit'] . "&ssi=" . $ssi . "&ssl=" . $ssl . "&shell=" . $shell . "&cgi=" . $cgi . "&phpfcgi=" . $phpfcgi . "&phpcgi=" . $phpcgi . "&phpmod=" . $phpmod . "&func=user.edit&elid=" . $args['package']['username'] . "&sok=ok&suok=++++Ok++++";
            $result = $this->sendRequest($args, $q, "xml");
            $objXML = new xml2Array();
            $arrOutput = $objXML->parse($result);
            foreach ($arrOutput[0]['children'] as $el) {
                if ($el['name'] == 'ERROR') {
                    throw new CE_Exception("ISPManager error #" . $el['attrs']['CODE'] . " in object \"" . $el['attrs']['OBJ'] . "\"");
                }
                if ($el['name'] == 'OK' && (isset($el['tagData']) && $el['tagData'] == 'restart')) {
                    return $this->restart($args);
                }
            }
        }
        return false;
    }

    public function suspend($args)
    {
        $result = $this->sendRequest($args, "func=user.suspend&elid=" . $args['package']['username'], "xml");
        $objXML = new xml2Array();
        $arrOutput = $objXML->parse($result);
        foreach ($arrOutput[0]['children'] as $el) {
            if ($el['name'] == 'OK' && $el['tagData'] == 'restart') {
                return $this->restart($args);
            }
        }
        return false;
    }

    public function unsuspend($args)
    {
        $result = $this->sendRequest($args, "func=user.resume&elid=" . $args['package']['username'], "xml");
        $objXML = new xml2Array();
        $arrOutput = $objXML->parse($result);
        foreach ($arrOutput[0]['children'] as $el) {
            if ($el['name'] == 'OK' && $el['tagData'] == 'restart') {
                return $this->restart($args);
            }
        }
        return false;
    }

    public function doCreate($args)
    {
        $userPackage = new UserPackage($args['userPackageId']);
        $this->create($this->buildParams($userPackage));
        return $userPackage->getCustomField("Domain Name") .  ' has been created.';
    }

    public function doSuspend($args)
    {
        $userPackage = new UserPackage($args['userPackageId']);
        $this->suspend($this->buildParams($userPackage));
        return $userPackage->getCustomField("Domain Name") .  ' has been suspended.';
    }

    public function doUnSuspend($args)
    {
        $userPackage = new UserPackage($args['userPackageId']);
        $this->unsuspend($this->buildParams($userPackage));
        return $userPackage->getCustomField("Domain Name") .  ' has been unsuspended.';
    }

    public function doDelete($args)
    {
        $userPackage = new UserPackage($args['userPackageId']);
        $this->delete($this->buildParams($userPackage));
        return $userPackage->getCustomField("Domain Name") . ' has been deleted.';
    }

    public function doUpdate($args)
    {
    }
}
