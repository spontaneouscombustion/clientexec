<?php

require_once 'library/CE/NE_MailGateway.php';
require_once 'modules/clients/models/UserPackageGateway.php';
require_once 'modules/billing/models/Invoice.php';
require_once 'modules/admin/models/ServicePlugin.php';
require_once 'modules/admin/models/StatusAliasGateway.php';

class PluginAutoterminate extends ServicePlugin
{
    public $hasPendingItems = true;
    protected $featureSet = 'products';

    private $body;
    private $gateway;

    private $autoTerminated = [];
    private $manualTerminate = [];
    private $newPreEmailed = [];
    private $preEmailed = [];

    public function getVariables()
    {
        $variables = [
            lang('Plugin Name') => [
                'type'        => 'hidden',
                'description' => '',
                'value'       => lang('Auto Terminate'),
            ],
            lang('Enabled') => [
                'type'        => 'yesno',
                'description' => lang('When enabled, overdue packages will be terminated and removed from the server.'),
                'value'       => '0',
            ],
            lang('Auto Terminate Pending Cancelations?') => [
                'type'        => 'yesno',
                'description' => lang('Do you want pending package cancelations to be automatically terminated and removed from the server?'),
                'value'       => '0',
            ],
            lang('Email Notifications') => [
                'type'        => 'textarea',
                'description' => lang('When a package requires manual termination you will be notified at this email address. If packages are terminated when this service is run, a summary email will be sent to this address.'),
                'value'       => '',
            ],
            lang('Days Overdue Before Terminating') => [
                'type'        => 'text',
                'description' => lang('Only terminate packages that are this many days overdue.'),
                'value'       => '7',
            ],
            lang('Run schedule - Minute') => [
                'type'        => 'text',
                'description' => lang('Enter number, range, list or steps'),
                'value'       => '0',
                'helpid'      => '8',
            ],
            lang('Run schedule - Hour') => [
                'type'        => 'text',
                'description' => lang('Enter number, range, list or steps'),
                'value'       => '0',
            ],
            lang('Run schedule - Day') => [
                'type'        => 'text',
                'description' => lang('Enter number, range, list or steps'),
                'value'       => '*',
            ],
            lang('Run schedule - Month') => [
                'type'        => 'text',
                'description' => lang('Enter number, range, list or steps'),
                'value'       => '*',
            ],
            lang('Run schedule - Day of the week') => [
                'type'        => 'text',
                'description' => lang('Enter number in range 0-6 (0 is Sunday) or a 3 letter shortcut (e.g. sun)'),
                'value'       => '*',
            ],
            lang('Notified Package List') => [
                'type'        => 'hidden',
                'description' => lang('Used to store package IDs of manually terminated packages whose email has already been sent.'),
                'value'       => ''
            ]
        ];

        return $variables;
    }

    public function execute()
    {
        $this->gateway = new UserPackageGateway($this->user);
        $messages = [];
        $this->preEmailed = unserialize($this->settings->get('plugin_autoterminate_Notified Package List'));
        $dueDays = $this->settings->get('plugin_autoterminate_Days Overdue Before Terminating');

        if ($dueDays != 0) {
            $overdueArray = $this->getOverduePackagesReadyToTerminate();

            foreach ($overdueArray as $packageId => $dueDate) {
                $this->suspendPackage($packageId);
            }
        }

        if ($this->settings->get('plugin_autoterminate_Auto Terminate Pending Cancelations?')) {
            $pendingCancellations = $this->getPendingCancelationReadyToTerminate();
            foreach ($pendingCancellations as $packageId) {
                $this->suspendPackage($packageId);
            }
        }

        $this->sendSummary();

        $this->settings->updateValue("plugin_autoterminate_Notified Package List", serialize($this->newPreEmailed));
        array_unshift($messages, $this->user->lang('%s package(s) terminated', count($this->autoTerminated)));
        return $messages;
    }

    private function sendSummary()
    {
        $sendSummary = false;
        $body = $this->user->lang("Auto Terminate Service Summary") . "\n\n";

        if (count($this->autoTerminated) > 0) {
            $sendSummary = true;
            $body .= $this->user->lang("Terminated") . ":\n\n";

            foreach ($this->autoTerminated as $id) {
                $domain = new UserPackage($id, [], $this->user);
                $user = new User($domain->CustomerId);
                $body .= $user->getFullName() . " => " . $domain->getReference(true) . "\n";
            }

            $body .= "\n";
        }

        if (count($this->manualTerminate) > 0) {
            $sendSummary = true;
            $body .= $this->user->lang("Requires Manual Termination") . ":\n\n";

            foreach ($this->manualTerminate as $id) {
                $domain = new UserPackage($id, [], $this->user);
                $user = new User($domain->CustomerId);
                $body .= $user->getFullName() . " => " . $domain->getReference(true) . "\n";
            }
        }

        if ($sendSummary && $this->settings->get('plugin_autoterminate_Email Notifications') != "") {
            $mailGateway = new NE_MailGateway();
            $destinataries = explode("\r\n", $this->settings->get('plugin_autoterminate_Email Notifications'));

            foreach ($destinataries as $destinatary) {
                if ($destinatary != '') {
                    $mailGateway->mailMessageEmail(
                        $body,
                        $this->settings->get('Support E-mail'),
                        $this->settings->get('Company Name'),
                        $destinatary,
                        false,
                        $this->user->lang("Auto Terminate Service Summary")
                    );
                }
            }
        }
    }

    private function suspendPackage($packageId)
    {
        $userPackage = new UserPackage($packageId, [], $this->user);
        $user = new User($userPackage->getCustomerId(), $this->user);

        if ($this->gateway->hasServerPlugin($userPackage->getCustomField("Server Id"), $pluginName)) {
            $errors = false;

            try {
                $userPackage->cancel(true, true);

                if ($user->getTotalNonCancelledPackages() == 0) {
                    $user->cancel();
                    $user->save();
                }
            } catch (Exception $ex) {
                $errors = true;
            }

            if ($errors) {
                $this->newPreEmailed[] = $userPackage->getID();

                if (!is_array($this->preEmailed) || !in_array($userPackage->getID(), $this->preEmailed)) {
                    $this->manualTerminate[] = $userPackage->getID();
                }
            } else {
                $this->autoTerminated[] = $userPackage->getID();

                $packageLog = Package_EventLog::newInstance(
                    false,
                    $userPackage->getCustomerId(),
                    $packageId,
                    PACKAGE_EVENTLOG_AUTOTERMINATED,
                    0,
                    $userPackage->getReference(true)
                );
                $packageLog->save();
            }
        } elseif (!is_array($this->preEmailed) || !in_array($userPackage->getID(), $this->preEmailed)) {
            $this->manualTerminate[] = $userPackage->getID();
            $this->newPreEmailed[] = $userPackage->getID();
        } else {
            $this->newPreEmailed[] = $userPackage->getID();
        }
    }

    public function pendingItems()
    {
        $gateway = new UserPackageGateway($this->user);
        $overdueArray = $this->getOverduePackagesReadyToTerminate();
        $returnArray = [];
        $returnArray['data'] = [];

        foreach ($overdueArray as $packageId => $dueDate) {
            $domain = new UserPackage($packageId, [], $this->user);
            $user = new User($domain->CustomerId);

            if ($gateway->hasServerPlugin($domain->getCustomField("Server Id"), $pluginName)) {
                $auto = "No";
            } else {
                $auto = "<span style=\"color:red\"><b>Yes</b></span>";
            }

            $tmpInfo = [];
            $tmpInfo['customer'] = '<a href="index.php?fuse=clients&controller=userprofile&view=profilecontact&frmClientID=' . $user->getId() . '">' . $user->getFullName() . '</a>';
            $tmpInfo['package_type'] = $domain->getProductGroupName();

            if ($domain->getProductType() == 3) {
                $tmpInfo['package'] = $domain->getProductGroupName();
            } else {
                $tmpInfo['package'] = $domain->getProductName();
            }

            $tmpInfo['domain'] = '<a href="index.php?fuse=clients&controller=userprofile&view=profileproduct&selectedtab=groupinfo&frmClientID=' . $user->getId() . '&id=' . $domain->getId() . '">' . $domain->getReference(true) . '</a>';
            $tmpInfo['date'] = date($this->settings->get('Date Format'), $dueDate);
            $tmpInfo['manual'] = $auto;
            $returnArray['data'][] = $tmpInfo;
        }

        if ($this->settings->get('plugin_autoterminate_Auto Terminate Pending Cancelations?')) {
            $pendingCancellations = $this->getPendingCancelationReadyToTerminate();
            foreach ($pendingCancellations as $packageId) {
                $domain = new UserPackage($packageId, [], $this->user);
                $user = new User($domain->CustomerId);

                if ($gateway->hasServerPlugin($domain->getCustomField("Server Id"), $pluginName)) {
                    $auto = "No";
                } else {
                    $auto = "<span style=\"color:red\"><b>Yes</b></span>";
                }

                $tmpInfo = [];
                $tmpInfo['customer'] = '<a href="index.php?fuse=clients&controller=userprofile&view=profilecontact&frmClientID=' . $user->getId() . '">' . $user->getFullName() . '</a>';
                $tmpInfo['package_type'] = $domain->getProductGroupName();

                if ($domain->getProductType() == 3) {
                    $tmpInfo['package'] = $domain->getProductGroupName();
                } else {
                    $tmpInfo['package'] = $domain->getProductName();
                }

                $tmpInfo['domain'] = '<a href="index.php?fuse=clients&controller=userprofile&view=profileproduct&selectedtab=groupinfo&frmClientID=' . $user->getId() . '&id=' . $domain->getId() . '">' . $domain->getReference(true) . '</a>';
                $dueDate = strtotime($userPackage->getEndOfBillingPeriod());
                $tmpInfo['date'] = date($this->settings->get('Date Format'), $dueDate);
                $tmpInfo['manual'] = $auto;
                $returnArray['data'][] = $tmpInfo;
            }
        }

        $returnArray["totalcount"] = count($returnArray['data']);
        $returnArray['headers'] = array(
            $this->user->lang('Client'),
            $this->user->lang('Package Type'),
            $this->user->lang('Package Name'),
            $this->user->lang('Package'),
            $this->user->lang('Due Date'),
            $this->user->lang('Requires Manual Termination?'),
        );

        return $returnArray;
    }

    public function output()
    {
    }

    public function dashboard()
    {
        $overdueArray = $this->getOverduePackagesReadyToTerminate();
        $autoTerminate = 0;
        $manualTerminate = 0;
        $gateway = new UserPackageGateway($this->user);

        foreach ($overdueArray as $packageId => $dueDate) {
            $userPackage = new UserPackage($packageId, [], $this->user);

            if ($gateway->hasServerPlugin($userPackage->getCustomField("Server Id"), $pluginName)) {
                $autoTerminate++;
            } else {
                $manualTerminate++;
            }
        }

        if ($this->settings->get('plugin_autoterminate_Auto Terminate Pending Cancelations?')) {
                $pendingCancellations = $this->getPendingCancelationReadyToTerminate();
            foreach ($pendingCancellations as $packageId) {
                $userPackage = new UserPackage($packageId, [], $this->user);

                if ($gateway->hasServerPlugin($userPackage->getCustomField("Server Id"), $pluginName)) {
                    $autoTerminate++;
                } else {
                    $manualTerminate++;
                }
            }
        }

        $message = $this->user->lang('Number of packages pending auto termination: %d', $autoTerminate);
        $message .= "<br>";
        $message .= $this->user->lang('Number of packages requiring manual termination: %d', $manualTerminate);

        return $message;
    }

    private function getOverduePackagesReadyToTerminate()
    {
        $query = "SELECT id FROM invoice WHERE status IN (0, 5) AND billdate < DATE_SUB( NOW() , INTERVAL ? DAY ) ORDER BY billdate ASC";
        $result = $this->db->query($query, @$this->settings->get('plugin_autoterminate_Days Overdue Before Terminating'));
        $overduePackages = [];
        $overdueCustomers = [];
        $statusGateway = StatusAliasGateway::getInstance($this->user);

        while ($row = $result->fetch()) {
            $invoice = new Invoice($row['id']);
            $user = new User($invoice->getUserID());

            foreach ($invoice->getInvoiceEntries() as $invoiceEntry) {
                if ($invoiceEntry->AppliesTo() != 0) {
                    // Found an overdue package, add it to the list
                    if (!in_array($invoiceEntry->AppliesTo(), array_keys($overduePackages))) {
                        $package = new UserPackage($invoiceEntry->AppliesTo(), [], $this->user);

                        if (!$statusGateway->isSuspendedPackageStatus($package->status)) {
                            continue;
                        }

                        // ignore this user package, as we are set to override the autosuspend.
                        if ($package->getCustomField('Override AutoSuspend') == 1) {
                            continue;
                        }

                        $overduePackages[$invoiceEntry->AppliesTo()] = $invoice->getDate('timestamp');
                    }
                }
            }
        }
        asort($overduePackages);
        return $overduePackages;
    }

    private function getPendingCancelationReadyToTerminate()
    {
        $packages = [];

        // get any immediately first
        $status = StatusAliasGateway::getInstance($this->user)->getPackageStatusIdsFor(
            PACKAGE_STATUS_PENDINGCANCELLATION
        );
        $sqlGetCustomFieldId = "SELECT id FROM customField WHERE name='Cancellation Type' AND groupId = 2 AND subGroupId = 0";
        $resultGetCustomFieldId = $this->db->query($sqlGetCustomFieldId);
        list($customFieldId) = $resultGetCustomFieldId->fetch();

        $query = "SELECT DISTINCT(d.id) AS id FROM domains d, object_customField cf_type WHERE cf_type.customFieldId = {$customFieldId} AND cf_type.objectid = d.id AND cf_type.value = 1 AND d.status IN(" . implode(', ', $status) . ")";

        $result = $this->db->query($query);
        while ($row = $result->fetch()) {
            $packages[$row['id']] = $row['id'];
        }

        // Cancel on end of billing
        $query = "SELECT DISTINCT(d.id) AS id FROM domains d, object_customField cf_type WHERE cf_type.customFieldId = {$customFieldId} AND cf_type.objectid = d.id AND cf_type.value = 2 AND d.status IN(" . implode(', ', $status) . ")";
        $result = $this->db->query($query);
        while ($row = $result->fetch()) {
            $userPackage = new UserPackage($row['id']);
            $cancelOn = strtotime($userPackage->getEndOfBillingPeriod());
            $today = strtotime('now');
            if ($today >= $cancelOn) {
                $packages[$row['id']] = $row['id'];
            }
        }

        asort($packages);
        return $packages;
    }
}
