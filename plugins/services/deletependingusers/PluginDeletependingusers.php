<?php
require_once 'modules/admin/models/ServicePlugin.php';
require_once 'modules/admin/models/StatusAliasGateway.php';

/**
* @package Plugins
*/
class PluginDeletependingusers extends ServicePlugin
{
    protected $featureSet = 'accounts';
    public $hasPendingItems = true;

    function getVariables()
    {
        $variables = array(
            lang('Plugin Name')   => array(
                'type'          => 'hidden',
                'description'   => '',
                'value'         => lang('Delete Pending Users'),
            ),
            lang('Enabled')       => array(
                'type'          => 'yesno',
                'description'   => lang('Erases pending users after the amount of days selected without being approved.'),
                'value'         => '0',
            ),
            lang('Also delete users with these statuses')       => array(
                'type'          => 'multipleoptions',
                'options'       => array(
                    USER_STATUS_INACTIVE => lang('Inactive'),
                    USER_STATUS_CANCELLED => lang('Cancelled'),
                    USER_STATUS_FRAUD => lang('Fraud')
                ),
                'description'   => lang('Erases inactive, cancelled and/or fraud users after the amount of days selected with that status.'),
                'value'         => serialize(array()),
            ),
            lang('Amount of days')    => array(
                'type'          => 'text',
                'description'   => lang('Set the amount of days before deleting a pending user from the system'),
                'value'         => '30',
            ),
            lang('Run schedule - Minute')  => array(
                'type'          => 'text',
                'description'   => lang('Enter number, range, list or steps'),
                'value'         => '30',
                'helpid'        => '8',
            ),
            lang('Run schedule - Hour')  => array(
                'type'          => 'text',
                'description'   => lang('Enter number, range, list or steps'),
                'value'         => '01',
            ),
            lang('Run schedule - Day')  => array(
                'type'          => 'text',
                'description'   => lang('Enter number, range, list or steps'),
                'value'         => '*',
            ),
            lang('Run schedule - Month')  => array(
                'type'          => 'text',
                'description'   => lang('Enter number, range, list or steps'),
                'value'         => '*',
            ),
            lang('Run schedule - Day of the week')  => array(
                'type'          => 'text',
                'description'   => lang('Enter number in range 0-6 (0 is Sunday) or a 3 letter shortcut (e.g. sun)'),
                'value'         => '*',
            ),
        );

        return $variables;
    }

    function getUsersWithStatus($status = '')
    {
        $query = "SELECT DISTINCT u.`id`, "
            ."UNIX_TIMESTAMP(u.`dateactivated`), "
            ."UNIX_TIMESTAMP(IFNULL(ucuf.`value`, '0000-00-00')) AS laststatusdate, "
            ."u.`status` "
            ."FROM `users` u "
            ."LEFT JOIN `user_customuserfields` ucuf "
            ."ON u.`id` = ucuf.`userid` "
            ."LEFT JOIN `customuserfields` cuf "
            ."ON ucuf.`customid` = cuf.`id` AND cuf.`name` = 'Last Status Date' AND cuf.`type` = 52 "
            ."WHERE u.`status` IN (".$status.") ";
        $result = $this->db->query($query);
        return $result;
    }

    function getUsersToDelete()
    {
        $arrayUsersToDelete = array();
        $daysToDeleteUser = $this->settings->get('plugin_deletependingusers_Amount of days');

        $statusPending = StatusAliasGateway::getInstance($this->user)->getUserStatusIdsFor(USER_STATUS_PENDING);
        $allStatusesToSearch = $statusPending;

        $addicionalStatuses = unserialize($this->settings->get('plugin_deletependingusers_Also delete users with these statuses'));
        foreach ( $addicionalStatuses as $addicionalStatus ) {
            $otherStatus = StatusAliasGateway::getInstance($this->user)->getUserStatusIdsFor($addicionalStatus);
            $allStatusesToSearch = array_merge($allStatusesToSearch, $otherStatus);
        }

        $statuses = implode(', ', $allStatusesToSearch);
        $result = $this->getUsersWithStatus($statuses);
        $num_rows = $result->getNumRows();

        if($num_rows > 0){
            $tempActualDate = strtotime(date('Y-m-d'));
            while(list($id, $dateactivated, $laststatusdate, $status) = $result->fetch()){
                if (in_array($status, $statusPending)) {
                    $diffdate = $tempActualDate - $dateactivated;
                    $diffdate = $diffdate/(60*60*24);
                    if($diffdate < $daysToDeleteUser){
                        continue;
                    }
                } else {
                    $diffdate = $tempActualDate - $laststatusdate;
                    $diffdate = $diffdate/(60*60*24);
                    if($diffdate < $daysToDeleteUser){
                        continue;
                    }
                }

                $objUser = new User($id);

                // Get number of tickets that are not closed
                $ticketCount = $objUser->getCountOfNotClosedTickets();

                // only delete the user if they have 0 not closed tickets
                if ($ticketCount > 0) {
                    continue;
                }

                // Get number of packages that are active
                $packagesCount = $objUser->getTotalActiveProducts();

                // only delete the user if they have 0 active packages
                if ($packagesCount > 0) {
                    continue;
                }

                $arrayUsersToDelete[] = $id;
            }
        }

        return $arrayUsersToDelete;
    }

    function execute()
    {
        include_once 'modules/clients/models/Client_EventLog.php';

        $arrayUsersToDelete = $this->getUsersToDelete();
        $deletedUsers = 0;
        foreach($arrayUsersToDelete as $userid) {
            $objUser = new User($userid);

            // do not delete with server plugin
            $objUser->delete(false, $this->user);
            $clientLog = Client_EventLog::newInstance(false, $userid, $userid, CLIENT_EVENTLOG_DELETED, NE_EVENTLOG_USER_SYSTEM);
            $clientLog->save();
            $deletedUsers++;
        }
        return array($deletedUsers." user(s) deleted");
    }

    function pendingItems()
    {
        $usersToDelete = $this->getUsersToDelete();
        $returnArray = array();
        $returnArray['data'] = array();
        if ( count($usersToDelete) > 0 ) {
            foreach ( $usersToDelete as $userID ) {
                $user = new User($userID);
                $tmpInfo = array();
                $tmpInfo['customer'] = '<a href="index.php?fuse=clients&controller=userprofile&view=profilecontact&frmClientID=' . $user->getId() . '">' . $user->getFullName() . '</a>';
                $tmpInfo['email'] = $user->getEmail();
                $returnArray['data'][] = $tmpInfo;
            }
        }
        $returnArray['totalcount'] = count($returnArray['data']);
        $returnArray['headers'] = array (
            $this->user->lang('Customer'),
            $this->user->lang('E-mail'),

        );
        return $returnArray;
    }

    function output() { }

    function dashboard()
    {
        $usersToDelete = $this->getUsersToDelete();
    	$numberOfUsersToDelete = count($usersToDelete);
        return $this->user->lang('Pending users to be deleted on next run: %d', $numberOfUsersToDelete);
    }
}
?>
