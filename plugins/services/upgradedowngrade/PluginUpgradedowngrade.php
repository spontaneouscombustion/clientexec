<?php

require_once 'library/CE/NE_MailGateway.php';
require_once 'modules/admin/models/ServicePlugin.php';
require_once 'modules/admin/models/Package.php';
require_once 'modules/admin/models/PackageAddonGateway.php';
require_once 'modules/admin/models/PluginGateway.php';
require_once 'modules/support/models/TicketGateway.php';
require_once 'modules/support/models/TicketLog.php';
require_once 'modules/support/models/TicketTypeGateway.php';
require_once 'modules/support/models/Ticket_EventLog.php';
require_once 'modules/support/models/DepartmentGateway.php';
require_once 'modules/clients/models/Package_EventLog.php';

/**
* @package Plugins
*/
class PluginUpgradedowngrade extends ServicePlugin
{
    protected $featureSet = 'products';
    public $hasPendingItems = false;

    /**
     * All plugin variables/settings to be used for this particular service.
     *
     * @return array The plugin variables.
     */
    function getVariables()
    {
        $variables = array(
        lang('Plugin Name') => array(
            'type'        => 'hidden',
            'description' => '',
            'value'       => lang('Upgrade/Downgrade Packages'),
        ),
        lang('Enabled') => array(
            'type'        => 'yesno',
            'description' => lang('When enabled, packages will be upgraded/downgraded when this service is run if the respective invoice was paid.'),
            'value'       => '0',
        ),
        lang('Hours Overdue Before Voiding Invoice') => array(
            'type'        => 'text',
            'description' => lang('Only void invoices that are this many hours overdue.'),
            'value'       => '24',
        ),
        lang('E-mail Notifications') => array(
            'type'        => 'textarea',
            'description' => lang('When an upgrade/downgrade requires manual setup you will be notified at this E-mail address.'),
            'value'       => '',
        ),
        lang('Create Ticket') => array(
            'type'        => 'yesno',
            'description' => lang("When an upgrade/downgrade requires manual setup, automatically create a ticket under the user's account."),
            'value'       => '1',
        ),
        lang('Ticket Assign To') => array(
            'type'        => 'options',
            'description' => lang("If <b>Create Ticket</b> is set to YES, select here to whom you want the ticket to be assigned."),
            'options'     => $this->ticketAssignTo(),
            'value'       => 'dep_0_staff_0'
        ),
        lang('Run schedule - Minute') => array(
            'type'        => 'text',
            'description' => lang('Enter number, range, list or steps'),
            'value'       => '*',
            'helpid'      => '8',
        ),
        lang('Run schedule - Hour') => array(
            'type'        => 'text',
            'description' => lang('Enter number, range, list or steps'),
            'value'       => '*',
        ),
        lang('Run schedule - Day') => array(
            'type'        => 'text',
            'description' => lang('Enter number, range, list or steps'),
            'value'       => '*',
        ),
        lang('Run schedule - Month') => array(
            'type'        => 'text',
            'description' => lang('Enter number, range, list or steps'),
            'value'       => '*',
        ),
        lang('Run schedule - Day of the week') => array(
            'type'        => 'text',
            'description' => lang('Enter number in range 0-6 (0 is Sunday) or a 3 letter shortcut (e.g. sun)'),
            'value'       => '*',
        ),
        );

        return $variables;
    }


    /**
     * Execute the upgrade/downgrade processor. We'll update the information of the package, addons and  recurring fees, if they are paid.
     *
     */
    function execute()
    {
        $createTicket = $this->settings->get('plugin_upgradedowngrade_Create Ticket');
        $emailNotifications = $this->settings->get('plugin_upgradedowngrade_E-mail Notifications');
        $hoursOverdueBeforeVoidingInvoice = $this->settings->get('plugin_upgradedowngrade_Hours Overdue Before Voiding Invoice');

        $aogateway = new ActiveOrderGateway($this->user);
        $addonGateway = new AddonGateway();
        $packageAddonGateway = new PackageAddonGateway();
        $numUpgradesDowngradesProcessed = 0;

        // Get ids of all paid invoices related to upgrade/downgrade packages, and their clients ids and packages ids in question
        $query = "SELECT DISTINCT i.`id`, i.`customerid`, ie.`appliestoid` FROM `invoice` i INNER JOIN `invoiceentry` ie ON ie.`invoiceid` = i.`id` AND ie.`billingtypeid` = ? WHERE i.`status` = ? ";
        $result = $this->db->query($query, BILLINGTYPE_PACKAGE_UPGRADE, INVOICE_STATUS_PAID);

        while ($row = $result->fetch()) {
            //- Verify that appliestoid has a value
            if (in_array($row['appliestoid'], array(0, '0', '', null))) {
                continue;
            }

            $userPackage = new UserPackage($row['appliestoid']);
            $oldProductReference = $userPackage->getReference(true, false);

            $upgradingToProductId = $userPackage->getCustomField("Upgrading to product id");

            //- Verify that "Upgrading to product id" has a value
            if (!isset($upgradingToProductId) || in_array($upgradingToProductId, array(0, '0', '', null))) {
                continue;
            }

            $upgradingToProductIdArray = unserialize($upgradingToProductId);

            //- Verify that "Upgrading to product id" has a value
            if (!is_array($upgradingToProductIdArray)) {
                continue;
            }

            //- Verify that "Upgrading to product id" has a value
            if (!isset($upgradingToProductIdArray['Package']['New Product Id']) || in_array($upgradingToProductIdArray['Package']['New Product Id'], array(0, '0', '', null)) || $upgradingToProductIdArray['Package']['New Product Id'] <= 0) {
                continue;
            }

            //- Update the package to use the new product id
            $query2 = "UPDATE `domains` SET `Plan` = ? WHERE `id` = ? ";
            $result2 = $this->db->query($query2, $upgradingToProductIdArray['Package']['New Product Id'], $row['appliestoid']);

            // Check if we should open a ticket for this product being ordered.
            $aogateway->checkProductOpenTicket($row['appliestoid']);

            //- Delete the recurring fees of type package and addon
            $query3 = "DELETE FROM `recurringfee` WHERE `appliestoid` = ? AND `billingtypeid` IN (?, ?) ";
            $result3 = $this->db->query($query3, $row['appliestoid'], BILLINGTYPE_PACKAGE, BILLINGTYPE_PACKAGE_ADDON);

            $query4 = "SELECT `customerid`, `description`, `detail`, `price`, `paymentterm`, `taxable`, `recurring` FROM `invoiceentry` WHERE `id` = ? ";
            $result4 = $this->db->query($query4, $upgradingToProductIdArray['Package']['Invoice Entry Id']);

            //- Insert the new recurring fees of type package, based on what we have on the invoice
            while ($row4 = $result4->fetch()) {
                if ($row4['paymentterm'] == 0) {
                    // If we have 0 then it's one time
                    // Create some recurring work, even if not recurring
                    $tParams = array(
                    'customerid'      => $row4['customerid'],
                    'paymentterm'     => 0,
                    'description'     => $row4['description'],
                    'detail'          => $row4['detail'],
                    'billingtypeid'   => BILLINGTYPE_PACKAGE,
                    'appliestoid'     => $row['appliestoid'],
                    'quantity'        => 1,
                    'disablegenerate' => 0,
                    'recurring'       => 0,
                    'subscriptionId'  => ''
                    );

                    $domainRegRecurringEntry = new RecurringWork($tParams);
                    $domainRegRecurringEntry->update();
                } else {
                    // Create some recurring work
                    $tParams = array(
                    'customerid'      => $row4['customerid'],
                    'paymentterm'     => $row4['paymentterm'],
                    'description'     => $row4['description'],
                    'detail'          => $row4['detail'],
                    'billingtypeid'   => BILLINGTYPE_PACKAGE,
                    'nextbilldate'    => $upgradingToProductIdArray['Package']['Next Due Date'],
                    'appliestoid'     => $row['appliestoid'],
                    'amount'          => $row4['price'],
                    'quantity'        => 1,
                    'disablegenerate' => 0,
                    'taxable'         => $row4['taxable'],
                    'recurring'       => 1,
                    'subscriptionId'  => ''
                    );
                    $domainRegRecurringEntry = new RecurringWork($tParams);
                    $domainRegRecurringEntry->update();

                    //- Update the invoice entry to add the respective recurring fee reference
                    $query5 = "UPDATE `invoiceentry` SET `recurringappliesto` = ? WHERE `id` = ? ";
                    $result5 = $this->db->query($query5, $domainRegRecurringEntry->vars['id']['data'], $upgradingToProductIdArray['Package']['Invoice Entry Id']);
                }
            }

            $oldAddons = array();
            $newAddons = array();
            $removedAddons = array();

            //- Get old addons from package
            $queryOldAddons = "SELECT `packageaddon_prices_id`, `quantity` FROM `domain_packageaddon_prices` WHERE `domain_id` = ? ";
            $resultOldAddons = $this->db->query($queryOldAddons, $row['appliestoid']);

            while ($rowOldAddons = $resultOldAddons->fetch()) {
                $oldAddons[$rowOldAddons['packageaddon_prices_id'] . '_' . (float)$rowOldAddons['quantity']] = array(
                    'Price Id' => $rowOldAddons['packageaddon_prices_id'],
                    'Quantity' => (float)$rowOldAddons['quantity']
                );
            }

            //- Delete old addons from package
            $query6 = "DELETE FROM `domain_packageaddon_prices` WHERE `domain_id` = ? ";
            $result6 = $this->db->query($query6, $row['appliestoid']);

            foreach ($upgradingToProductIdArray['Addons'] as $upgradeAddonElementArray) {
                $query7 = "SELECT `customerid`, `description`, `detail`, `price`, `quantity`, `paymentterm`, `taxable`, `recurring` FROM `invoiceentry` WHERE `id` = ? ";
                $result7 = $this->db->query($query7, $upgradeAddonElementArray['Invoice Entry Id']);

                while ($row7 = $result7->fetch()) {
                    $openTicket = $addonGateway->getOpenTicketFromPriceId($upgradeAddonElementArray['Price Id']);

                    //- Add new addon to package
                    if (isset($upgradeAddonElementArray['Next Due Date'])) {
                        $query8 = "INSERT INTO `domain_packageaddon_prices` (`domain_id`, `packageaddon_prices_id`, `billing_cycle`, `quantity`, `openticket`, `nextbilldate`) VALUES (?, ?, ?, ?, ?, ?) ";
                        $result8 = $this->db->query($query8, $row['appliestoid'], $upgradeAddonElementArray['Price Id'], $row7['paymentterm'], (float)$row7['quantity'], $openTicket, $upgradeAddonElementArray['Next Due Date']);

                        //- Insert the new recurring fee of type addon, based on what we have on the invoice
                        $recurringEntry = new RecurringWork(
                            array(
                            'description'          => $row7['description'],
                            'detail'               => $row7['detail'],
                            'billingtypeid'        => BILLINGTYPE_PACKAGE_ADDON,
                            'packageAddonPricesId' => $upgradeAddonElementArray['Price Id'],
                            'amount'               => $row7['price'],
                            'quantity'             => (float)$row7['quantity'],
                            'disablegenerate'      => 0,
                            'customerid'           => $row7['customerid'],
                            'paymentterm'          => $row7['paymentterm'],
                            'nextbilldate'         => $upgradeAddonElementArray['Next Due Date'],
                            'appliestoid'          => $row['appliestoid'],
                            'taxable'              => $row7['taxable'],
                            'subscriptionId'       => ''
                            )
                        );
                        $recurringEntry->update();

                        //- Update the invoice entry to add the respective recurring fee reference
                        $query9 = "UPDATE `invoiceentry` SET `recurringappliesto` = ? WHERE `id` = ? ";
                        $result9 = $this->db->query($query9, $recurringEntry->vars['id']['data'], $upgradeAddonElementArray['Invoice Entry Id']);
                    } else {
                        $query10 = "INSERT INTO `domain_packageaddon_prices` (`domain_id`, `packageaddon_prices_id`, `billing_cycle`, `quantity`, `openticket`) VALUES (?, ?, ?, ?, ?) ";
                        $result10 = $this->db->query($query10, $row['appliestoid'], $upgradeAddonElementArray['Price Id'], $row7['paymentterm'], (float)$row7['quantity'], $openTicket);
                    }

                    // Check if we should be opening a ticket for this addon.
                    $aogateway->checkAddonOpenTicket($upgradeAddonElementArray['Price Id'], $row['appliestoid'], $row7['description']);

                    //- Get new addons from package. Only addons that were not used before by the package
                    if (!isset($oldAddons[$upgradeAddonElementArray['Price Id'] . '_' . (float)$row7['quantity']])) {
                        $newAddons[] = $packageAddonGateway->getAddonNameAndOption($upgradeAddonElementArray['Price Id']) . ' (' . 'Quantity' . ': ' . (float)$row7['quantity'] . ')';
                    } else {
                        unset($oldAddons[$upgradeAddonElementArray['Price Id'] . '_' . (float)$row7['quantity']]);
                    }
                }
            }

            // - This addons were removed
            if (count($oldAddons) > 0) {
                foreach ($oldAddons as $oldAddonValue) {
                    $removedAddons[] = $packageAddonGateway->getAddonNameAndOption($oldAddonValue['Price Id']) . ' (' . 'Quantity' . ': ' . (float)$oldAddonValue['Quantity'] . ')';
                }
            }

            //- Update invoice entries with the billing types that are no upgrade: BILLINGTYPE_PACKAGE_UPGRADE -> BILLINGTYPE_PACKAGE
            $query11 = "UPDATE `invoiceentry` SET `billingtypeid` = ? WHERE `billingtypeid` = ? AND `invoiceid` = ? ";
            $result11 = $this->db->query($query11, BILLINGTYPE_PACKAGE, BILLINGTYPE_PACKAGE_UPGRADE, $row['id']);

            //- Update invoice entries with the billing types that are no upgrade: BILLINGTYPE_COUPON_DISCOUNT_UPGRADE -> BILLINGTYPE_COUPON_DISCOUNT
            //We need to keep the BILLINGTYPE_COUPON_DISCOUNT_UPGRADE type as it works a bit different than the BILLINGTYPE_COUPON_DISCOUNT

            //- Update invoice entries with the billing types that are no upgrade: BILLINGTYPE_PACKAGE_ADDON_UPGRADE -> BILLINGTYPE_PACKAGE_ADDON
            $query12 = "UPDATE `invoiceentry` SET `billingtypeid` = ? WHERE `billingtypeid` = ? AND `invoiceid` = ? ";
            $result12 = $this->db->query($query12, BILLINGTYPE_PACKAGE_ADDON, BILLINGTYPE_PACKAGE_ADDON_UPGRADE, $row['id']);

            //- Update the next due date of the other recurring fees that applies to the package, to use today's date
            $query13 = "UPDATE `recurringfee` SET `nextbilldate` = ? WHERE `appliestoid` = ? AND `billingtypeid` NOT IN (?, ?) ";
            $result13 = $this->db->query($query13, date("Y-m-d"), $row['appliestoid'], BILLINGTYPE_PACKAGE, BILLINGTYPE_PACKAGE_ADDON);

            // Get the new user package now
            unset($userPackage);
            $userPackage = new UserPackage($row['appliestoid']);
            $newProductReference = $userPackage->getReference(true, false);

            //- Delete the value of "Upgrading to product id"
            $userPackage->setCustomField("Upgrading to product id", '');

            //- Create Event
            $packageLog = Package_EventLog::newInstance(false, $userPackage->getCustomerId(), $userPackage->getId(), PACKAGE_EVENTLOG_UPGRADE_PROCESSED, 0);
            $packageLog->save();

            $numUpgradesDowngradesProcessed++;

            $user = new User($row['customerid']);
            $mailGateway = new NE_MailGateway();
            $userPackageGateway = new UserPackageGateway($this->user);

            $requiresManuaActions = false;
            $appendToMessage = ".";

            if ($userPackageGateway->hasPlugin($userPackage, $pluginName)) {
                $pluginGateway = new PluginGateway($this->user);

                $plugin = $pluginGateway->getPluginByUserPackage($userPackage, $pluginName);
                if ($plugin->supports('upgrades')) {
                    try {
                        $args = $plugin->buildParams($userPackage);
                        $changes = [
                            'package' => $args['package']['name_on_server']
                        ];
                        $userPackageGateway->callPluginAction($userPackage, 'Update', $changes);
                    } catch (Exception $e) {
                        // - Send an e-mail to notifiy admin that the attempt to automatically upgrade / donwgrade the package failed.
                        $appendToMessage = ", as the automatic attempt failed. Error message: " . $e->getMessage();
                        $requiresManuaActions = true;
                    }
                } else {
                    $requiresManuaActions = true;
                }
            } else {
                $requiresManuaActions = true;
            }

            if ((count($newAddons) > 0 || count($removedAddons) > 0 || $requiresManuaActions) && ($createTicket || $emailNotifications != '')) {
                $subject = $this->user->lang("Upgrade/Downgrade Manual Intervention Required") . ": " . $user->getFullName();
                $message = "Dear Support Member,"
                ."\r\n\r\nCustomer " . $user->getFullName() . " has upgraded/downgraded the package with id " . $row['appliestoid'] . " from " . $oldProductReference . " to " . $newProductReference . " and it requires manual actions" . $appendToMessage;

                if (count($removedAddons) > 0) {
                    $message .= "\r\n\r\nThe following addons were removed:\r\n" . implode("\r\n", $removedAddons);
                }

                if (count($newAddons) > 0) {
                    $message .= "\r\n\r\nThe following addons were added or modified:\r\n" . implode("\r\n", $newAddons);
                }

                $message .= "\r\n\r\nPlease upgrade/downgrade the package on the server as soon as possible."
                ."\r\n\r\nIt is related to the paid invoice with id " . $row['id']
                ."\r\n\r\nThank You";

                if ($createTicket) {
                    $this->createTicket($subject, nl2br($message), $userPackage);
                }

                if ($emailNotifications != '') {
                    $destinataries = explode("\r\n", $emailNotifications);
                    foreach ($destinataries as $destinatary) {
                        $mailGateway->mailMessageEmail(
                            $message,
                            $this->settings->get('Support E-mail'),
                            $this->settings->get('Company Name'),
                            $destinatary,
                            '',
                            $subject
                        );
                    }
                }
            }
        }

        //Get all packages that have a value for "Upgrading to product id"
        $query14 = "SELECT ocf.`objectid` FROM `object_customField` ocf WHERE ocf.`value` != '' AND ocf.`customFieldId` IN (SELECT cf.`id` FROM `customField` cf WHERE cf.`name` = 'Upgrading to product id' ) ";
        $result14 = $this->db->query($query14, BILLINGTYPE_PACKAGE_UPGRADE, INVOICE_STATUS_UNPAID);

        while ($row14 = $result14->fetch()) {
            $userPackage = new UserPackage($row14['objectid']);

            $upgradingToProductId = $userPackage->getCustomField("Upgrading to product id");
            $upgradingToProductIdArray = unserialize($upgradingToProductId);

            if (!is_array($upgradingToProductIdArray) || !isset($upgradingToProductIdArray['Time']) || time() - $upgradingToProductIdArray['Time'] > $hoursOverdueBeforeVoidingInvoice*60*60) {
                $userPackage->cancelUpgradeDowngrade(true);
            }
        }

        return array(
            $this->user->lang('%s upgrade(s) / downgrade(s) processed', $numUpgradesDowngradesProcessed)
        );
    }

    private function createTicket($subject, $message, $userPackage)
    {
        $ticketTypeGateway = new TicketTypeGateway();
        $billingTicketType = $ticketTypeGateway->getBillingTicketType();

        $date = date('Y-m-d H-i-s');
        $user = new User($userPackage->getCustomerId());
        $ticket = new Ticket();
        $tickets = new TicketGateway();

        if ($tickets->GetTicketCount() == 0) {
             $id = $this->settings->get('Support Ticket Start Number');
             $ticket->setForcedId($id);
        }

        $ticket->setUser($user);
        $ticket->save();
        $ticket->setSubject($subject);
        $ticket->setDomainId($userPackage->getId());
        $ticket->SetDateSubmitted($date);
        $ticket->SetLastLogDateTime($date);
        $ticket->setMethod(1);
        $ticket->SetStatus(TICKET_STATUS_OPEN);
        $ticket->SetMessageType($billingTicketType);

        $ticketAssignTo = $this->settings->get('plugin_upgradedowngrade_Ticket Assign To');
        $target = explode('_', $ticketAssignTo);

        if ($target[1] != 0) {
            $ticket->setAssignedToDeptId($target[1]);
        }

        if (isset($target[2]) && $target[2] == 'staff') {
            if ($target[3] != 0) {
                $ticket->setAssignedToId($target[3]);
            }
        }

        $ticket->save();
        $supportLog = Ticket_EventLog::newInstance(false, $user->getId(), $ticket->getId(), TICKET_EVENTLOG_CREATED, $user->getId());
        $supportLog->save();

        // tickets can't be html-formatted
        $ticket->addInitialLog(strip_tags(NE_MailGateway::br2nl($message)), $date, $user, false, true);
        $ticketNotifications = new TicketNotifications($this->user);
        $ticketNotifications->notifyCustomerForNewTicket($user, $ticket, $message, '', '', true);
    }

    function ticketAssignTo()
    {
        $depGateway = new DepartmentGateway($this->user);
        $departments = $depGateway->getDepartmentListWithMembers(1);
        $ticketAssignTo = array();

        foreach ($departments["groups"] as $group) {
            $padding_left = '';

            if ($group['indentClass'] == 'indentTenPix') {
                $padding_left = '&nbsp;&nbsp;&nbsp;';
            }
            $ticketAssignTo[$group['assigneeId']] = $padding_left.$group['assigneeLabel'];
        }

        return $ticketAssignTo;
    }

    function output()
    {
    }

    function dashboard()
    {
    }
}
