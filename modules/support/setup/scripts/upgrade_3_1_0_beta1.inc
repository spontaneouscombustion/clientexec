<?php

/*
* Refer to trouble ticket types by their ID,
* Create entries for ticket types present in the troubleticket table and not present in the troubleticket_type table
*/

$ticketTypes = array();
$query = "SELECT id, name FROM troubleticket_type";
$result = mysql_query($query) or die("ERROR in query: $query");
while ($row = mysql_fetch_assoc($result)) {
    $id = $row['id'];
    $name = $row['name'];

    if (!isset($firstTicketTypeId)) {
        $firstTicketTypeId = $id;
    }

    if ($name == 'Other') {
        $defaultTicketType = $id;   // used below
    }
    if ($name == 'Billing Issues') {
        $billingTicketType = $id;
    }
    $ticketTypes[$name] = $id;
}

if (!isset($defaultTicketType)) {
    $defaultTicketType = $id; // used below
}

if (!isset($billingTicketType)) {
    $billingTicketType = $firstTicketTypeId;
}

$query = "SELECT id, messagetype FROM troubleticket";
$result = mysql_query($query) or die("ERROR in query: $query");
while (list($id, $messageType) = mysql_fetch_array($result)) {
    if (!isset($ticketTypes[$messageType])) {
            $query2 = "INSERT INTO troubleticket_type (name, description, enabled, target_dept, target_staff) VALUES('".mysql_real_escape_string($messageType)."', '', 0, 0, 0)";
            $result2 = mysql_query($query2) or die("ERROR in query: $query2");
            $ticketTypes[$messageType] = mysql_insert_id();
    }
    $query3 = "UPDATE troubleticket SET messagetype=".$ticketTypes[$messageType]." WHERE id=".$id;
    mysql_query($query3) or die("ERROR in query: $query3");

    if ($id == $billingTicketType) {
        $query4 = "UPDATE troubleticket_type SET is_billing_type=1 WHERE id=$id";
        mysql_query($query4) or die("ERROR in query: $query4");
    }
}

$query = "ALTER TABLE `troubleticket` CHANGE `messagetype` `messagetype` INT( 11 ) NOT NULL";
mysql_query($query) or die("ERROR in query: $query");

/*
* Assign by default all ticket types to the general department
*/
$query = "UPDATE troubleticket_type SET target_dept=1";
$result = mysql_query($query) or die("ERROR in query: $query");

/*
* Set default department "General"
*/
$query = "SELECT id FROM users WHERE groupid=2";
$result = mysql_query($query) or die("ERROR in query: $query");
list($superadminId) = mysql_fetch_array($result);

$query = "SELECT value FROM setting WHERE name='E-mail For New Support Tickets' OR name='E-mail For New Trouble Tickets'";
$result = mysql_query($query) or die("ERROR in query: $query");
list($emails) = mysql_fetch_array($result);
$emails = mysql_real_escape_string($emails);

$query = "INSERT INTO `departments` (`id`, `name`, `is_generaldep`, `lead_id`, `assign_to_lead`, `notify_lead`, `sendclosed`, `sendresolution`, `notification_list`) VALUES (1, 'General', 1, $superadminId, 0, 0, 0, 0, '$emails')";
$result = mysql_query($query) or die("ERROR in query: $query");

$query = "INSERT INTO `departments_members` (`department_id`, `member_id`, `is_group`) VALUES (1, $superadminId, 0)";
$result = mysql_query($query) or die("ERROR in query: $query");

/*
* Set default department "Billing"
*/
$query = "SELECT id FROM users WHERE groupid=4";
$result = mysql_query($query) or die("ERROR in query: $query");
list($billingStaffId) = mysql_fetch_array($result);

$query = "INSERT INTO `departments` (`id`, `name`, `is_generaldep`, `lead_id`, `assign_to_lead`, `notify_lead`, `sendclosed`, `sendresolution`, `notification_list`) VALUES (2, 'Billing', 0, $superadminId, 0, 0, 0, 0, '')";
$result = mysql_query($query) or die("ERROR in query: $query");

$query = "INSERT INTO `departments_members` (`department_id`, `member_id`, `is_group`) VALUES (2, $superadminId, 0)";
$result = mysql_query($query) or die("ERROR in query: $query");

/*
* migrate all permission 'View all assigned tickets' to the new ones
*/
$query = "SELECT subject_id, is_group FROM permissions WHERE permission='support_view_assigned_others_tickets'";
$result = mysql_query($query) or die("ERROR in query: $query");
while (list($subjectId, $isGroup) = mysql_fetch_array($result)) {
    $query2 = "INSERT INTO permissions (subject_id, is_group, permission, target_id) VALUES($subjectId, $isGroup, 'support_view_assigned_department_tickets', 0)";
    $result2 = mysql_query($query2) or die("ERROR in query: $query2");
    $query2 = "INSERT INTO permissions (subject_id, is_group, permission, target_id) VALUES($subjectId, $isGroup, 'support_view_assigned_otherdepartment_tickets', 0)";
    $result2 = mysql_query($query2) or die("ERROR in query: $query2");
}
$query2 = "DELETE FROM permissions WHERE permission='support_view_assigned_others_tickets'";
$result2 = mysql_query($query2) or die("ERROR in query: $query2");

/*
* Move New Trouble Ticket Autoresponder template to the default autoresponder template
*/
$query = "SELECT value, value_alternate FROM setting WHERE name='New Trouble Ticket Autoresponder Subject Template' OR name='New Support Ticket Autoresponder Subject Template'";
$result = mysql_query($query) or die("ERROR in query: $query");
list($templateSubject) = mysql_fetch_array($result);
$templateSubject = mysql_real_escape_string($templateSubject);

$query = "SELECT value, value_alternate FROM setting WHERE name='New Trouble Ticket Autoresponder Template' OR name='New Support Ticket Autoresponder Template'";
$result = mysql_query($query) or die("ERROR in query: $query");
list($template, $templateHTML) = mysql_fetch_array($result);
$template = mysql_real_escape_string($template);
$templateHTML = mysql_real_escape_string($templateHTML);
$query = "INSERT INTO `autoresponders` (`id`, `name`, `subject`, `contents`, `contents_html`) VALUES (1, 'Default', '$templateSubject', '$template', '$templateHTML')";
$result = mysql_query($query) or die("ERROR in query: $query");

$query = "DELETE FROM setting WHERE name='New Trouble Ticket Autoresponder Subject Template' OR name='New Support Ticket Autoresponder Subject Template'";
$result = mysql_query($query) or die("ERROR in query: $query");
$query = "DELETE FROM setting WHERE name='New Trouble Ticket Autoresponder Template' OR name='New Support Ticket Autoresponder Template'";
$result = mysql_query($query) or die("ERROR in query: $query");

/*
* Move Bounce Back Template to the default autoresponder template
*/
$query = "SELECT value FROM setting WHERE name='Bounce Back Template'";
$result = mysql_query($query) or die("ERROR in query: $query");
list($template) = mysql_fetch_array($result);
$templateHTML = nl2br($template);
$template = mysql_real_escape_string($template);
$templateHTML = mysql_real_escape_string($templateHTML);
$query = "INSERT INTO `autoresponders` (`id`, `name`, `subject`, `contents`, `contents_html`) VALUES (2, 'Bounce back', 'Support request failure', '$template', '$templateHTML')";
$result = mysql_query($query) or die("ERROR in query: $query");

$query = "DELETE FROM setting WHERE name='Bounce Back Template'";
$result = mysql_query($query) or die("ERROR in query: $query");

/*
* If the setting 'Alternate Support Address' is set, then don't use the 'bounce back' autoresponder.
*/
$query = "SELECT value FROM setting WHERE name='Alternate Support Address'";
$result = mysql_query($query) or die("ERROR in query: $query");
list($alternateAddress) = mysql_fetch_array($result);
if (trim($alternateAddress) == '') {
    $sendCopyTo = '';
    $guestAutoresponder = 2;
} else {
    $sendCopyTo = mysql_real_escape_string($alternateAddress);
    $guestAutoresponder = 0;
}
$query = "INSERT INTO `routingrules` (`id`, `name`, `order`, `emails`, `user_type`, `autoresponder_template`, `openticket`, `target_type`, `target_priority`, `target_dept`, `target_staff`, `copy_destinataries`, `routing_type`, `pop3_hostname`, `pop3_port`, `pop3_username`, `pop3_password`, `pop3_delete_emails`) VALUES (2, 'Default autoresponder for unregistered users', 1, '*', 2, $guestAutoresponder, 0, 0, 0, 0, 0, '$sendCopyTo', 1, '', 110, '', '', 0)";
$result = mysql_query($query) or die("ERROR in query: $query");

/*
* Default ticket type for registered user's routing rules is "Other".
* But if that type is missing, use the last in the troubleticket_type table.
*/
$query = "INSERT INTO `routingrules` (`id`, `name`, `order`, `emails`, `user_type`, `autoresponder_template`, `openticket`, `target_type`, `target_priority`, `target_dept`, `target_staff`, `copy_destinataries`, `routing_type`, `pop3_hostname`, `pop3_port`, `pop3_username`, `pop3_password`, `pop3_delete_emails`) VALUES(1, 'Default autoresponder for registered users', 0, '*', 1, 1, 1, $defaultTicketType, 2, -1, -1, '', 1, '', '', '', '', 0)";
$result = mysql_query($query) or die("ERROR in query: $query");

/*
* Move Tickets Generator plugin settings about mail server to a default routing rule for registered customers
*/
$query = "SELECT value FROM setting WHERE name='plugin_fetchticket_Enabled'";
$result = mysql_query($query) or die("ERROR in query: $query");
list($enabled) = mysql_fetch_array($result);
if ($enabled == 1) {
    $query = "SELECT value FROM setting WHERE name='plugin_fetchticket_E-mail server hostname'";
    $result = mysql_query($query) or die("ERROR in query: $query");
    list($hostname) = mysql_fetch_array($result);
    $hostname = mysql_real_escape_string($hostname);

    $query = "SELECT value FROM setting WHERE name='plugin_fetchticket_E-mail server port'";
    $result = mysql_query($query) or die("ERROR in query: $query");
    list($port) = mysql_fetch_array($result);
    $port = mysql_real_escape_string($port);

    $query = "SELECT value FROM setting WHERE name='plugin_fetchticket_E-mail account username'";
    $result = mysql_query($query) or die("ERROR in query: $query");
    list($username) = mysql_fetch_array($result);
    $username = mysql_real_escape_string($username);

    $query = "SELECT value FROM setting WHERE name='plugin_fetchticket_E-mail account password'";
    $result = mysql_query($query) or die("ERROR in query: $query");
    list($password) = mysql_fetch_array($result);
    $password = mysql_real_escape_string($password);

    $query = "INSERT INTO `routingrules` (`id`, `name`, `order`, `emails`, `user_type`, `autoresponder_template`, `openticket`, `target_type`, `target_priority`, `target_dept`, `target_staff`, `copy_destinataries`, `routing_type`, `pop3_hostname`, `pop3_port`, `pop3_username`, `pop3_password`, `pop3_delete_emails`) VALUES(3, 'Default autoresponder for registered users, using POP3 retrieval', 2, '*', 1, 1, 1, $defaultTicketType, 2, -1, -1, '', 2, '$hostname', '$port', '$username', '$password', 0)";
    $result = mysql_query($query) or die("ERROR in query: $query");

    $query = "INSERT INTO `routingrules` (`id`, `name`, `order`, `emails`, `user_type`, `autoresponder_template`, `openticket`, `target_type`, `target_priority`, `target_dept`, `target_staff`, `copy_destinataries`, `routing_type`, `pop3_hostname`, `pop3_port`, `pop3_username`, `pop3_password`, `pop3_delete_emails`) VALUES (4, 'Default autoresponder for unregistered users, using POP3 retrieval', 3, '*', 2, $guestAutoresponder, 0, 0, 0, 0, 0, '$sendCopyTo', 2, '$hostname', '$port', '$username', '$password', 0)";
    $result = mysql_query($query) or die("ERROR in query: $query");
}


?>
