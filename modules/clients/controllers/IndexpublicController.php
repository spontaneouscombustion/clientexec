<?php
include_once 'modules/clients/models/UserPackageGateway.php';

/**
 * Client Module's Action Controller
 *
 * @category   Action
 * @package    Clients
 * @author     Matt Grandy <matt@clientexec.com>
 * @license    http://www.clientexec.com  ClientExec License Agreement
 * @link       http://www.clientexec.com
 */
class Clients_IndexpublicController extends CE_Controller_Action
{

    var $moduleName = "clients";

    protected function deleteprofileidAction()
    {
        $this->checkPermissions(array("clients_edit_customers","clients_edit_credit_card"));

        try {
            $paymenttype = $this->getParam('paymenttype');

            if ($this->settings->get('plugin_'.$paymenttype.'_Update Gateway')) {
                $userInformation = array(
                    'User ID' => $this->customer->getId(),
                    'Status'  => $this->customer->getStatus(),
                    'Gateway' => $paymenttype,
                    'Action'  => 'delete'
                );

                include_once 'library/CE/NE_PluginCollection.php';
                $pluginCollection = new NE_PluginCollection('gateways', $this->user);
                $response = $pluginCollection->callFunction($paymenttype, 'UpdateGateway', $userInformation);
            }

            if ($response['error'] == true) {
                $this->error = true;
                $this->message = $response['detail'];
            } else {
                $this->message = $this->user->lang('Your Billing Profile ID was deleted successfully.');
            }
        } catch (Exception $ex) {
            $this->error = true;
            $this->message = $ex->getMessage();
        }

        $this->send();
    }

    protected function deleteccnumberAction()
    {
        $this->checkPermissions(array("clients_edit_customers","clients_edit_credit_card"));

        include_once 'modules/clients/models/Client_EventLog.php';

        $customerid =  $this->customer->getId();
        $customer = new User($customerid);

        try {
            $last4 = $customer->getCCLastFour();
            $customer->clearCreditCardInfo(false);
            $customer->save();
            $eventLog = Client_EventLog::newInstance(false, $customer->getId(), $customer->getId());
            $eventLog->setSubject($this->user->getId());
            $eventLog->setAction(CLIENT_EVENTLOG_DELETEDCC);
            $eventLog->setParams($last4);
            CE_Lib::addEventLog($eventLog);

            if ($customer->getPaymentType() == 'buypass') {
                //Needs to delete the previous account.
                $newGateway = $customer->getPaymentType();

                if ($this->settings->get('plugin_'.$newGateway.'_Update Gateway')) {
                    $userInformation = array();
                    $userInformation['User ID'] = $customer->getId();
                    $userInformation['Status'] = $customer->getStatus();
                    $userInformation['Gateway'] = $newGateway;
                    $userInformation['Action'] = 'delete';
                    include_once 'library/CE/NE_PluginCollection.php';
                    $pluginCollection = new NE_PluginCollection('gateways', $this->user);
                    $pluginCollection->callFunction($newGateway, 'UpdateGateway', $userInformation);
                }
            }

            $this->message = $this->user->lang('Your credit card was deleted successfully.');
        } catch (Exception $ex) {
            $this->error = true;
            $this->message = $ex->getMessage();
        }

        $this->send();
    }

    protected function callpluginactionAction()
    {

        $userPackage = new UserPackage(filter_var($_REQUEST['id'], FILTER_SANITIZE_NUMBER_INT));
        // if the user isn't an admin, we need to check to make sure they are looking at their package and not someone elses
        if (!$this->user->isAdmin()) {
            if ($this->user->getId() != $userPackage->CustomerId) {
                CE_Lib::securityEvent('** HACK ATTEMPT ** Execution aborted.');
                CE_Lib::redirectPage("index.php?fuse=admin&amp;action=Logout", 'Security breach attempt has been logged.  Your session has been logged.');
                die();
            }
        }

        $gateway = new UserPackageGateway($this->user);
        $cmd = filter_var($_REQUEST['actioncmd'], FILTER_SANITIZE_STRING);

        include_once "modules/admin/models/PluginGateway.php";
        $pluginGateway = new PluginGateway($this->user);

        // ensure that the command is valid
        if (!$gateway->hasPlugin($userPackage, $pluginName)) {
            CE_Lib::log(3, "looking for available actions on userPackage without a plugin");
            $this->error = $this->user->lang('No plugin defined for this package');
            $this->send();
        }
        $plugin = $pluginGateway->getPluginByUserPackage($userPackage, $pluginName);
        if ($userPackage->getProductType() == PACKAGE_TYPE_DOMAIN) {
            $vars = $plugin->getVariables();
            $allowedActions = $vars['Registered Actions For Customer'];
            $allowedActions = explode(",", $allowedActions['value']);

            $allowedCommands = array();
            foreach ($allowedActions as $action) {
                if (preg_match('/(.*)(?<!\\\)\((.*)(?<!\\\)\)/', $action, $matches)) {
                    $allowedCommands[] = trim($matches[1]);
                } else {
                    $allowedCommands[] = $action;
                }
            }

            if (in_array($cmd, $allowedCommands)) {
                $this->message = $gateway->callPluginAction($userPackage, $cmd, $additionalArgs);
                $gateway->unsetProductCache();
            } else {
                $this->error = $this->user->lang('Invalid Command');
            }
        } elseif ($userPackage->getProductType() == PACKAGE_TYPE_HOSTING) {
            $vars = $plugin->getVariables();
            $allowedActions = $vars['Registered Actions For Customer'];
            $allowedActions = explode(",", $allowedActions['value']);

            $allowedCommands = array();
            foreach ($allowedActions as $action) {
                if (preg_match('/(.*)(?<!\\\)\((.*)(?<!\\\)\)/', $action, $matches)) {
                    $allowedCommands[] = trim($matches[1]);
                } else {
                    $allowedCommands[] = $action;
                }
            }

            if (in_array($cmd, $allowedCommands)) {
                $return = $gateway->callPluginAction($userPackage, $cmd, $additionalArgs);
                $this->message = $return;
            }
        }
        $this->send();
    }

    /**
     * Function to check if a domain is registered or not.
     *
     * @return int
     *     -1:    Domain name already has an account in this system
     *      0:     Domain available
     *      1:     Domain already registered
     *      2:     Registrar Error, domain extension not recognized or supported
     *      3:     Domain invalid
     *      5:     Could not contact registry to look up domain
     */
    public function checkdomainAction()
    {
        include_once "modules/admin/models/TopLevelDomainGateway.php";
        include_once 'modules/admin/models/PackageGateway.php';

        $name = strtolower($this->getParam('name', FILTER_SANITIZE_STRING));
        $tld = strtolower($this->getParam('tld', FILTER_SANITIZE_STRING));
        $group = $this->getParam('group', FILTER_SANITIZE_NUMBER_INT);

        $packageGateway = new PackageGateway($this->user);
        $packageId = $packageGateway->getPackageIdFromTLD($group, $tld);

        $tldGateway = new TopLevelDomainGateway($this->user);

        $return_array = array();
        $return_array['domainName'] = htmlentities($name.".".$tld);
        $return_array['domainNameSuggest'] = false;
        $return_array2 = $tldGateway->search_domain($name, $tld, $packageId);
        $return_array = array_merge($return_array, $return_array2);
        $this->send(array("search_results"=>$return_array));
    }

    protected function emailhistoryAction()
    {
        $this->checkPermissions('clients_view_emails');
        $this->title = $this->user->lang('Email History');
    }

    protected function getemailsAction()
    {
        $this->checkPermissions('clients_view_emails');
        $emails = [];
        /// This should likely go into an iterator at a later date.
        $query = "SELECT * FROM email WHERE userid = ? ORDER BY date DESC";
        $result = $this->db->query($query, $this->customer->getId());
        while ($row = $result->fetch()) {
            if ($row['sender'] == '') {
                $from = $this->user->lang('N/A');
            } else {
                $from = $row['fromName'] . ' (' . $row['sender'] . ')';
            }
            $obj = [
                'subject' => $row['subject'],
                'content' => utf8_encode(Clientexec::decryptString($row['content'])),
                'date' => $row['date'],
                'to' => $row['to'],
                'from' => $from
            ];
            $emails[] = $obj;
        }
        $this->send(['data' => $emails]);
    }
}
