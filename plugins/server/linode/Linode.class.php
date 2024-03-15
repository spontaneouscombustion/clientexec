<?php
class Linode
{
    private $curl;
    private $curlOptions  = array();
    private $httpHeader = array();
    private $baseUrl;
    private $ApiKey;
    private $LicenseKey;

    public function __construct($ApiKey)
    {
        $this->baseUrl = rtrim('https://api.linode.com/v4/', '/');
        $this->curl = curl_init();
        $this->ApiKey = $ApiKey;
        $this->setCurlOption(CURLOPT_RETURNTRANSFER, true);
        $this->setCurlOption(CURLOPT_VERBOSE, false);
        $this->setHttpHeader('Content-Type', 'application/json');
        $this->setHttpHeader('Authorization', 'Bearer ' . $this->ApiKey);
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
        $this->setCurlOption(CURLOPT_URL, $this->baseUrl.'/'.$url);
        $this->setCurlOption(CURLOPT_HTTPGET, true);
        $this->setCurlOption(CURLOPT_CUSTOMREQUEST, 'GET');

        return $this->executeRequest();
    }


    protected function post($url, $data = null)
    {
        $this->setCurlOption(CURLOPT_URL, $this->baseUrl.'/'.$url);
        $this->setCurlOption(CURLOPT_POST, true);
        $this->setCurlOption(CURLOPT_CUSTOMREQUEST, 'POST');
        if ($data) {
            $this->setCurlOption(CURLOPT_POSTFIELDS, json_encode($data));
        }

        return $this->executeRequest();
    }


    protected function put($url, $data = null)
    {
        $this->setCurlOption(CURLOPT_URL, $this->baseUrl.'/'.$url);
        $this->setCurlOption(CURLOPT_HTTPGET, true);
        $this->setCurlOption(CURLOPT_CUSTOMREQUEST, 'PUT');
        if ($data) {
            $this->setCurlOption(CURLOPT_POSTFIELDS, json_encode($data));
        }

        return $this->executeRequest();
    }


    protected function delete($url, $data = null)
    {
        $this->setCurlOption(CURLOPT_URL, $this->baseUrl.'/'.$url);
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
        $response = curl_exec($this->curl);
        return json_decode($response, true);
        CE_Lib::log(4, 'Linode Response: ' .   $response);
    }


  /*Linode Instances v4.137.0*/

    public function countInstances()
    {
        return $this->get('linode/instances')['results'];
    }

    public function listInstances($page, $per_page)
    {
        return $this->get('linode/instances?page='.$page.'&page_size='.$per_page);
    }

    //Linodes List

    public function getListInstance($instanceId = null)
    {
        return $this->get('linode/instances/'.$instanceId);
    }

    //List Jobs
    public function getJobList($instanceId = null)
    {
        $getJobList = $this->get('account/events');
        $joblist = [];
        foreach ($getJobList['data'] as $key => $job) {
            if ($key <= 15) {
                if ($job['entity']['id'] == $instanceId) {
                    $joblist[] = [
                        "id" => $job['id'],
                        "action" => str_replace('linode', 'instance', $job['action']),
                        'date' => $job['created'],
                        'status' => $job['status']
                    ];
                }
            }
        }

        return $joblist;
    }

    #list all disks
    public function getDiskList($instanceId, $diskId = null)
    {
        return $this->get('linode/instances/'.$instanceId.'/disks/'.$diskId);
    }

    public function rescueDisk($instanceId, $data)
    {
        return $this->post("linode/instances/".$instanceId."/rescue", $data);
    }


    # List Packages/ Plans
    public function getLinodesPlans()
    {
        return $this->get('linode/types');
    }

    # Get Kernal details
    public function getKernals()
    {
        return $this->get('linode/kernels');
    }

    # Get datacenter locations/regions
    public function getDataCenters($region = null)
    {
        return $this->get('regions/'.$region);
    }


    # Get Templates (All)
    public function getAllTemplates($image = null)
    {
        return $this->get('images/'.$image);
    }

    # Get Config Lists
    public function getInstanceConfigList($instanceId, $configId = null)
    {

        return $this->get('linode/instances/'.$instanceId.'/configs/'.$configId);
    }

    # Get IP lists (All)
    public function getAllIpList($address = null)
    {
        return $this->get('networking/ips/'.$address);
    }

    #Get Ip List By Instance Id

    public function getIPListByInstanceId($instanceId, $address = null)
    {
        return $this->get('linode/instances/'.$instanceId.'/ips/'.$address);
    }

    public function deleteIPAddress($instanceId, $address)
    {
        return $this->delete('linode/instances/'.$instanceId.'/ips/'.$address);
    }

    #getgraph
    public function getinstancegraph($instanceId, $range = null)
    {
        return $this->get('linode/instances/'.$instanceId.'/stats/'.$range);
    }

    //Add IP Address
    public function addIPAddress($instanceId, $type)
    {
        if ($type == 'private') {
            $data = [  "type" => "ipv4",  "public" => false] ;
        } else {
            $data = [  "type" => "ipv4",  "public" => true] ;
        }
        return $this->post('linode/instances/'.$instanceId.'/ips', $data);
    }

    //Set RDNS
    public function reverseHostname($ipaddressid, $hostname)
    {
        return $this->put('networking/ips/'.$ipaddressid, ['rdns' => $hostname]);
    }

    # List Stackscripts
    public function getStackScripts()
    {
        $result =  $this->get('linode/stackscripts/');
        if ($result['pages'] > 1) {
            $result = array();
            for ($i=1; $i>=$result['pages']; $i++) {
                $result[] = $this->get('linode/stackscripts/?page'.$i);
            }
        }
        return $result;
    }


    # Create Instance
    public function createInstance($label, $datacenterId, $planId, $backup, $privateIP, $image, $swap_size, $root_pass)
    {

        $data = ['region' => $datacenterId,
            'type' => $planId,
            'label' => $label,
            'backups_enabled' => $backup,
            "booted"=> true,
            "image" => $image,
            "private_ip" => $privateIP,
            "swap_size" => $swap_size,
            "root_pass" => $root_pass];
        return $this->post('linode/instances', $data);
    }

    public function createStackInstance($label, $datacenterId, $planId, $backup, $privateIP, $image, $swap_size, $root_pass, $stackdata)
    {
        $data = ['region' => $datacenterId,
            'type' => $planId,
            'label' => $label,
            'backups_enabled' => $backup,
            "booted" => true,
            "image" => $image,
            "private_ip" => $privateIP,
            "swap_size" => $swap_size,
            "root_pass" => $root_pass,
            "stackscript_data" => $stackdata];
          return $this->post('linode/instances', $data);
    }

    # Create Swap Disk

    public function createSwapDisk($instanceId, $label, $type, $size)
    {
        $data = array(
            'label' => $label,
            'filesystem' => $type,
            'size' => (int)$size,
        );
        return $this->post('linode/instances/'.$instanceId.'/disks', $data);
    }

    # Create Disk
    public function createDisk($instanceId, $distributionId, $label, $size, $rootPw)
    {
        $data = array(
            'image' => $distributionId,
            'label' => $label,
            'size' => $size,
            'root_pass' => $rootPw,
        );
        return $this->post('linode/instances/'.$instanceId.'/disks', $data);
    }

    #Create Instance Config

    public function createInstanceConfig($instanceId, $label, $diskList, $kernalId = null)
    {
        $data = array(
           // 'kernel' => $kernalId,
            'label' => $label,
            'devices' => $diskList,
        );
        return $this->post('linode/instances/'.$instanceId.'/configs', $data);
    }

    # Boot Instance

    public function bootInstance($instanceId)
    {
        return $this->post('linode/instances/'.$instanceId.'/boot');
    }

    # Reboot Instance

    public function rebootInstance($instanceId)
    {
        return $this->post('linode/instances/'.$instanceId.'/reboot');
    }

    # Shutdown Instance
    public function shutdownInstance($instanceId)
    {
        return $this->post('linode/instances/'.$instanceId.'/shutdown', []);
    }


    # Delete Disk
    public function deleteDisk($instanceId, $diskId)
    {
        return $this->delete('linode/instances/'.$instanceId.'/disks/'.$diskId, []);
    }

    # Delete Config
    public function deleteConfig($instanceId, $configId)
    {
        return $this->delete('linode/instances/'.$instanceId.'/configs/'.$configId, []);
    }

    # Delete instance
    public function deleteinstance($instanceId, $skipChecks = null)
    {
        return $this->delete('linode/instances/'.$instanceId, ['skipChecks' => $skipChecks]);
    }

    # Resize instance
    public function resizeinstance($instanceId, $planId)
    {
        return $this->post('linode/instances/'.$instanceId.'/resize', ['type' => $planId]);
    }

    #create backup
    public function enablebackup($instanceId)
    {
        return $this->post('linode/instances/'.$instanceId.'/backups/enable');
    }

    # cancel Backup
    public function cancelBackup($instanceId)
    {
        return $this->post('linode/instances/'.$instanceId.'/backups/cancel');
    }

    # rebuild OS
    public function rebuildinstance($instanceId, $image, $rootPassword)
    {
        return $this->post('linode/instances/'.$instanceId.'/rebuild', ["image" => $image, "root_pass" => $rootPassword]);
    }

    # snapshot list
    public function getSnapshotList($instanceId, $snapshotid = null)
    {
        return $this->get('linode/instances/'.$instanceId.'/backups/'.$snapshotid);
    }

    # Take SnapShot
    public function takeSnapshot($instanceId, $label)
    {
        return $this->post('linode/instances/'.$instanceId.'/backups', ["label" => $label]);
    }

    # Restore Backup
    public function restoreBackup($instanceId, $backupId)
    {
        return $this->post('linode/instances/'.$instanceId.'/backups/'.$backupId.'/restore', ["linode_id" => (int)$instanceId, "overwrite"=>true]);
    }

    # update Instance label

    function updateInstanceLabel($instanceId, $label)
    {
        return $this->put('linode/instances/'. $instanceId, ["label"=> str_replace(' ', '', $label)]);
    }

    # Network transfer usages
    public function networktransfer($instanceId)
    {
        return $this->get('linode/instances/'.$instanceId.'/transfer');
    }


  //Volumes
  #Linode's Volumes List
    public function listInstanceVolumes($instanceId)
    {
        return $this->get('linode/instances/'.$instanceId.'/volumes');
    }

  #Create Volume
    public function createandattachVolumetoInstance($instanceId, $label, $size)
    {
        $ConfigId = $this->getInstanceConfigList($instanceId)['data'][0]['id'];
        ;
        $data = array(
        'label' => $label,
        'size' => (int)$size,
        'linode_id' => (int)$instanceId,
        'config_id' => (int)$ConfigId,
        'tags' => [],
        );
        return $this->post('volumes', $data);
    }

    public function singleInstanceVolume($volId)
    {
        return $this->get('volumes/'.$volId);
    }


    public function listallVolumes()
    {
        return $this->get('volumes');
    }


  #Linode's Volumes detach
    public function detachVolume($volId)
    {
        return $this->post('volumes/'.$volId.'/detach');
    }

  #Linode's Volumes delete
    public function deleteVolume($volId)
    {
        return $this->delete('volumes/'.$volId);
    }

  //Volumes

      # Update Disk
    public function updateDisk($postData)
    {
        $data = [];
        if (!empty($postData['label'])) {
            $data = array_merge($data, array('label' => $postData['label']));
        }
        $instanceId = $postData['instanceId'];
        $diskid = $postData['diskid'];
        return $this->put('linode/instances/'.$instanceId.'/disks/'.$diskid, $data);
    }

      # Resize disk
    public function resizeDisk($postData)
    {
        $instanceId  = $postData['instanceId'];
        $diskid = $postData['diskid'];
        return $this->post('linode/instances/'.$instanceId.'/disks/'.$diskid.'/resize', ['size' => (int)$postData['size']]);
    }

      # Reset Root password
    public function updateRootPassword($instanceId, $diskid, $password)
    {
        return $this->post('linode/instances/'.$instanceId.'/disks/'.$diskid.'/password', ["password" => $password]);
    }

    public function Instancelish_token($instanceId)
    {
        return $this->post('linode/instances/'.$instanceId.'/lish_token');
    }

    public function linode_random_password($len)
    {
        if ($len < 8) {
            $len = 8;
        }
        $sets = [];
        $sets[] = "ABCDEFGHJKLMNPQRSTUVWXYZ";
        $sets[] = "abcdefghjkmnpqrstuvwxyz";
        $sets[] = "0123456789";
        $sets[] = "!@#$%&*?";
        $password = "";
        foreach ($sets as $set) {
            $password .= $set[array_rand(str_split($set))];
        }
        while (strlen($password) < $len) {
            $randomSet = $sets[array_rand($sets)];
            $password .= $randomSet[array_rand(str_split($randomSet))];
        }
        return str_shuffle($password . "#");
    }

    public function countryCodeToCountry($CountryCode)
    {
        $CountryNames = json_decode(file_get_contents("http://country.io/names.json"), true);
        return ($CountryNames[$CountryCode] ? $CountryNames[$CountryCode] : $CountryCode);
    }

        #Get datacenter label
    public function getDataCentersLabel()
    {
        return [
                    "ap-west" => 'Mumbai',
                    "ca-central" => "Toronto",
                    "ap-southeast" =>"Sydney",
                    'us-central'=>"Dallas",
                    "us-west"=>"Fremont",
                    "us-southeast"=>"Atlanta",
                    "us-east"=>"Newark",
                    "eu-west"=>"London",
                    "ap-south"=>"Singapore",
                    "eu-central"=>"Frankfurt",
                    "ap-northeast"=>"Tokyo"
                  ];
    }

    public function getDataCentersLabels($region)
    {
        if (preg_match('/ap-west/', $region)) {
            return 'Mumbai (India)';
        } elseif (preg_match('/us-east/', $region)) {
            return 'Newark (United States)';
        } elseif (preg_match('/ap-south/', $region)) {
            return 'Singapore (Singapore)';
        } elseif (preg_match('/ap-northeast/', $region)) {
            return 'Tokyo (Japan)';
        } elseif (preg_match('/ca-central/', $region)) {
            return 'Toronto (Canada)';
        } elseif (preg_match('/us-southeast/', $region)) {
            return 'Atlanta (United States)';
        } elseif (preg_match('/us-central/', $region)) {
            return 'Dallas (United States)';
        } elseif (preg_match('/us-west/', $region)) {
            return 'Fremont (United States)';
        } elseif (preg_match('/eu-central/', $region)) {
            return 'Frankfurt (Germany)';
        } elseif (preg_match('/eu-west/', $region)) {
            return 'London (United Kingdom)';
        } elseif (preg_match('/ap-southeast/', $region)) {
            return 'Sydney (Australia)';
        } else {
            return "nolocation";
        }
    }

    public function getlishsshgateway($region)
    {
        if (preg_match('/ap-west/', $region)) {
            return 'lish-mumbai1.linode.com';
        } elseif (preg_match('/us-east/', $region)) {
            return 'lish-newark.linode.com';
        } elseif (preg_match('/ap-south/', $region)) {
            return 'lish-singapore.linode.com';
        } elseif (preg_match('/ap-northeast/', $region)) {
            return 'lish-shinagawa1.linode.com';
        } elseif (preg_match('/ca-central/', $region)) {
            return 'lish-tor1.linode.com';
        } elseif (preg_match('/us-southeast/', $region)) {
            return 'lish-atlanta.linode.com';
        } elseif (preg_match('/us-central/', $region)) {
            return 'lish-dallas.linode.com';
        } elseif (preg_match('/us-west/', $region)) {
            return 'lish-fremont.linode.com';
        } elseif (preg_match('/eu-central/', $region)) {
            return 'lish-frankfurt.linode.com';
        } elseif (preg_match('/eu-west/', $region)) {
            return 'lish-london.linode.com';
        } elseif (preg_match('/ap-southeast/', $region)) {
            return 'lish-sydney.linode.com';
        } else {
            return "nolocation";
        }
    }

    public function getwebconsoleweblish($region)
    {
        if (preg_match('/ap-west/', $region)) {
            return 'mumbai1.webconsole.linode.com:8081';
        } elseif (preg_match('/us-east/', $region)) {
            return 'newark.webconsole.linode.com:8081';
        } elseif (preg_match('/ap-south/', $region)) {
            return 'singapore.webconsole.linode.com:8081';
        } elseif (preg_match('/ap-northeast/', $region)) {
            return 'shinagawa1.webconsole.linode.com:8081';
        } elseif (preg_match('/ca-central/', $region)) {
            return 'tor1.webconsole.linode.com:8081';
        } elseif (preg_match('/us-southeast/', $region)) {
            return 'atlanta.webconsole.linode.com:8081';
        } elseif (preg_match('/us-central/', $region)) {
            return 'dallas.webconsole.linode.com:8081';
        } elseif (preg_match('/us-west/', $region)) {
            return 'fremont.webconsole.linode.com:8081';
        } elseif (preg_match('/eu-central/', $region)) {
            return 'frankfurt.webconsole.linode.com:8081';
        } elseif (preg_match('/eu-west/', $region)) {
            return 'london.webconsole.linode.com:8081';
        } elseif (preg_match('/ap-southeast/', $region)) {
            return  'sydney.webconsole.linode.com:8081';
        } else {
            return "nolocation";
        }
    }


    public function getwebconsoleglish($region)
    {
        if (preg_match('/ap-west/', $region)) {
            return 'mumbai1.webconsole.linode.com';
        } elseif (preg_match('/us-east/', $region)) {
            return 'newark.webconsole.linode.com';
        } elseif (preg_match('/ap-south/', $region)) {
            return 'singapore.webconsole.linode.com';
        } elseif (preg_match('/ap-northeast/', $region)) {
            return 'shinagawa1.webconsole.linode.com';
        } elseif (preg_match('/ca-central/', $region)) {
            return 'tor1.webconsole.linode.com';
        } elseif (preg_match('/us-southeast/', $region)) {
            return 'atlanta.webconsole.linode.com';
        } elseif (preg_match('/us-central/', $region)) {
            return 'dallas.webconsole.linode.com';
        } elseif (preg_match('/us-west/', $region)) {
            return 'fremont.webconsole.linode.com';
        } elseif (preg_match('/eu-central/', $region)) {
            return 'frankfurt.webconsole.linode.com';
        } elseif (preg_match('/eu-west/', $region)) {
            return 'london.webconsole.linode.com';
        } elseif (preg_match('/ap-southeast/', $region)) {
            return  'sydney.webconsole.linode.com';
        } else {
            return "nolocation";
        }
    }
}
