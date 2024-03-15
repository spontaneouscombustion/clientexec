<?php

class Vultr
{
    private $curl;
    private $curlOptions  = array();
    private $httpHeader = array();
    private $baseUrl;
    private $ApiKey;

    public function __construct($ApiKey)
    {
        $this->baseUrl = 'https://api.vultr.com/v2';
        $this->ApiKey = $ApiKey;
        $this->curl = curl_init();
        $this->setCurlOption(CURLOPT_RETURNTRANSFER, true);
        $this->setCurlOption(CURLOPT_VERBOSE, false);
        $this->setHttpHeader('Content-Type', 'application/json');
        $this->setHttpHeader('Authorization', "Bearer " . $this->ApiKey);
    }

    protected function setCurlOption($option, $value)
    {
        $this->curlOptions[$option] = $value;
    }


    protected function getCurlOption($option)
    {
        return isset($this->curlOptions[$option]) ? $this->curlOptions[$option] : null;
    }


    public function setHttpHeader($name, $value)
    {
        $this->httpHeader[] = $name . ': ' . $value;
    }


    protected function get($url)
    {
        $requestURL = $this->baseUrl . '/' . $url;
        $this->setCurlOption(CURLOPT_URL, $requestURL);
        $this->setCurlOption(CURLOPT_HTTPGET, true);
        $this->setCurlOption(CURLOPT_CUSTOMREQUEST, 'GET');
        return $this->executeRequest();
    }


    protected function post($url, $data = null)
    {
        $requestURL = $this->baseUrl . '/' . $url;
        $this->setCurlOption(CURLOPT_URL, $requestURL);
        $this->setCurlOption(CURLOPT_POST, true);
        $this->setCurlOption(CURLOPT_CUSTOMREQUEST, 'POST');
        if ($data) {
            $this->setCurlOption(CURLOPT_POSTFIELDS, json_encode($data));
        }
        return $this->executeRequest();
    }


    protected function put($url, $data = null)
    {
        $requestURL = $this->baseUrl . '/' . $url;
        $this->setCurlOption(CURLOPT_URL, $requestURL);
        $this->setCurlOption(CURLOPT_HTTPGET, true);
        $this->setCurlOption(CURLOPT_CUSTOMREQUEST, 'PUT');
        if ($data) {
            $this->setCurlOption(CURLOPT_POSTFIELDS, json_encode($data));
        }
        return $this->executeRequest();
    }

    protected function patch($url, $data = null)
    {
        $requestURL = $this->baseUrl . '/' . $url;
        $this->setCurlOption(CURLOPT_URL, $requestURL);
        $this->setCurlOption(CURLOPT_HTTPGET, true);
        $this->setCurlOption(CURLOPT_CUSTOMREQUEST, 'PATCH');
        if ($data) {
            $this->setCurlOption(CURLOPT_POSTFIELDS, json_encode($data));
        }
        return $this->executeRequest();
    }


    protected function delete($url, $data = null)
    {
        $requestURL = $this->baseUrl . '/' . $url;
        $this->setCurlOption(CURLOPT_URL, $requestURL);
        $this->setCurlOption(CURLOPT_HTTPGET, true);
        $this->setCurlOption(CURLOPT_CUSTOMREQUEST, 'DELETE');
        if ($data) {
            $this->setCurlOption(CURLOPT_POSTFIELDS, json_encode($data));
        }
        return $this->executeRequest();
    }

    protected function executeRequest()
    {
        $this->setCurlOption(CURLOPT_HTTPHEADER, array_values($this->httpHeader));
        curl_setopt_array($this->curl, $this->curlOptions);
        $caPathOrFile = \Composer\CaBundle\CaBundle::getSystemCaRootBundlePath();
        if (is_dir($caPathOrFile)) {
            $this->setCurlOption($this->curl, CURLOPT_CAPATH, $caPathOrFile);
        } else {
            $this->setCurlOption($this->curl, CURLOPT_CAINFO, $caPathOrFile);
        }
        $data = curl_exec($this->curl);
        $response = json_decode($data, true);
        if ($response['status'] == 400) {
            throw new CE_Exception($response['error']);
        }
        CE_Lib::log(4, 'Vultr VM Response: ' . $data);
        return $response;
    }


    //account, applications, os, iso, snapshots, regions, startup-scripts, instances, backups, blocks, firewalls, iso-public, reserved-ips, ssh-keys
    public function instanceGetRequests($info)
    {
        return $this->get($info . '?per_page=500');
    }

    public function allplans()
    {
        return $this->get('plans?per_page=500');
    }

    public function plans($type) //all, vdc, vhp, vhp, vhf, vc2, voc, vcg, voc-g, voc-s, voc-c, voc-m
    {
        return $this->get('plans?per_page=500&type=' . $type);
    }

    public function regionByPlans($regionId)
    {
        return $this->get('regions/' . $regionId . '/availability');
    }

    public function listInstancesPerPage($per_page, $cursor)
    {
        if ($cursor) {
            return $this->get('instances?per_page=' . $per_page . '&cursor=' . $cursor);
        } else {
            return $this->get('instances?per_page=' . $per_page);
        }
    }

    public function listInstances()
    {
        return $this->get('instances');
    }

    public function getInstance($instanceId)
    {
        return $this->get('instances/' . $instanceId);
    }

    public function createInstance($regionId, $planId, $hostname, $os_id)
    {
        $instanceData = [
            'region' => $regionId,
            'plan' => $planId,
            'label' => $hostname,
            'hostname' => $hostname,
            'os_id' => $os_id,
            'backups' => "disabled",
            'enable_ipv6' => true,
            'ddos_protection' => false,
            'activation_email' => true,
        ];
        return $this->post('instances', $instanceData);
    }

    public function deleteInstance($instanceId)
    {
        return $this->delete('instances/' . $instanceId);
    }

    // reboot/start/halt/ipv4
    public function instancePostAction($instanceId, $actionType)
    {
        return $this->post('instances/' . $instanceId . '/' . $actionType);
    }

    public function reinstallInstance($instanceId, $hostanme)
    {
        return $this->post('instances/' . $instanceId . '/reinstall', ['hostanme' => $hostanme]);
    }
}
