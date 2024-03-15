<?php

require_once 'plugins/server/cpanel/xmlapl.php';

class CPanel_Accounts extends Report
{
    private $lang;

    protected $featureSet = 'accounts';

    public function __construct($user = null, $customer = null)
    {
        $this->lang = lang('cPanel Accounts Not Created On Servers');
        parent::__construct($user, $customer);
    }

    public function process()
    {
        $this->SetDescription($this->user->lang('cPanel Accounts Not Created On Servers'));
        $_REQUEST['filter'] = 'active';
        $userPackageGateway = new UserPackageGateway($this->user);
        $packages = $userPackageGateway->getUserHostingPackagesIterator(1);

        $data = [];
        if (isset($_GET['server'])) {
            $server = new Server($_GET['server']);
            $vars = $server->getAllServerPluginVariables($this->user, $server->getPluginName());

            $api = new xmlapi($vars['ServerHostName']);
            $api->set_user($vars['plugin_cpanel_Username']);
            $api->set_hash($vars['plugin_cpanel_Access_Hash']);
            $port = ($vars['plugin_cpanel_Use_SSL'] == true) ? 2087 : 2086;
            $api->set_port($port);
            $api->set_output('json');

            $serverAccounts = $api->listaccts();
            $accounts = [];
            foreach ($serverAccounts->acct as $acct) {
                $accounts[] = $acct->user;
            }

            while ($userPackage = $packages->fetch()) {
                if ($userPackageGateway->hasPlugin($userPackage, $pluginName)) {
                    if ($pluginName == 'cpanel' && $userPackage->getCustomField('Server Id') == $_GET['server']) {
                        $userName = $userPackage->getCustomField('User Name');
                        if (!in_array($userName, $accounts)) {
                            $userPackageId = $userPackage->getId();
                            $userId = $userPackage->CustomerId;
                            $data[] = [
                                "<a href='index.php?fuse=clients&controller=userprofile&view=profileproduct&selectedtab=groupinfo&id={$userPackageId}&frmClientID={$userId}'>" . $userPackage->getReference(true) . '</a>',
                                $userPackage->getCustomField('User Name'),
                                $vars['ServerHostName']
                            ];
                        }
                    }
                }
            }

            $this->reportData[] = [
                'group' => $data,
                'groupname' => "",
                'label' => [
                    'Package',
                    'Username',
                    'Server',
                ]
            ];
        }

        $serverGateway = new ServerGateway($this->user);
        $servers = $serverGateway->getServersByPlugin('cpanel');

        echo "<div style='margin-left:20px;'>";
        echo "    <form id='reportdropdown' method='GET' onChange='viewAccounts()'>";
        echo $this->user->lang('Select Server') . ": <br/>";
        echo "        <select id='server' name='server'>";
        echo '        <option value="--">----&nbsp;&nbsp;</option>';
        foreach ($servers as $server) {
                echo "<option value='" . $server['id'] . "'>" . $server['name'] . "</option>";
        }

        echo "        </select>";
        echo "    </form>";
        echo "</div>";
        ?>
    <script type="text/javascript">
    function viewAccounts(changedType)
    {
        location.href='index.php?fuse=reports&report=CPanel_Accounts&controller=index&type=Accounts&view=viewreport&server='+document.getElementById("server").value;
    }
</script>
        <?php
    }
}