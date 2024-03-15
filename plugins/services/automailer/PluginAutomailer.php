<?php

require_once 'library/CE/NE_MailGateway.php';
include_once 'modules/clients/models/Client_EventLog.php';
require_once 'modules/admin/models/ServicePlugin.php';
include_once 'modules/admin/models/StatusAliasGateway.php' ;
require_once 'modules/support/models/AutoresponderTemplateGateway.php';
require_once 'modules/clients/models/UserPackageGateway.php';
require_once 'modules/admin/models/Package.php';
include_once 'modules/admin/models/NotificationGateway.php';
include_once 'modules/admin/models/UserNotificationGateway.php';
require_once 'modules/billing/models/Invoice.php';
require_once 'modules/billing/models/Currency.php';
require_once 'modules/clients/models/UserPackage.php';

/**
* @package Plugins
*/
class PluginAutomailer extends ServicePlugin
{
    public $hasPendingItems = false;

    function getVariables()
    {
        $variables = array(
            lang('Plugin Name') => array(
                'type'        => 'hidden',
                'description' => '',
                'value'       => lang('Auto Mailer')
            ),
            lang('Enabled') => array(
                'type'        => 'yesno',
                'description' => lang('When enabled, email customers and/or create support tickets a set number of days before/after a given event, defined on <a href="index.php?fuse=admin&controller=notifications&view=adminviewnotifications"><b><u>Accounts&nbsp;>&nbsp;Notifications</u></b></a>.<br><b>NOTE:</b> Only run once per day to avoid duplicate E-mails or Tickets.'),
                'value'       => '0'
            ),
            lang('Summary E-mail') => array(
                'type'        => 'textarea',
                'description' => lang('E-mail addresses to which a summary of each service run will be sent.  (Leave blank if you do not wish to receive a summary)'),
                'value'       => ''
            ),
            lang('Summary E-mail Subject') => array(
                'type'        => 'text',
                'description' => lang('E-mail subject for the summary notification.'),
                'value'       => 'Auto Mailer / Ticket Creator Summary'
            ),
            lang('Run schedule - Minute') => array(
                'type'        => 'text',
                'description' => lang('Enter number, range, list or steps'),
                'value'       => '0',
                'helpid'      => '8'
            ),
            lang('Run schedule - Hour') => array(
                'type'        => 'text',
                'description' => lang('Enter number, range, list or steps'),
                'value'       => '0'
            ),
            lang('Run schedule - Day') => array(
                'type'        => 'text',
                'description' => lang('Enter number, range, list or steps'),
                'value'       => '*'
            ),
            lang('Run schedule - Month') => array(
                'type'        => 'text',
                'description' => lang('Enter number, range, list or steps'),
                'value'       => '*'
            ),
            lang('Run schedule - Day of the week') => array(
                'type'        => 'text',
                'description' => lang('Enter number in range 0-6 (0 is Sunday) or a 3 letter shortcut (e.g. sun)'),
                'value'       => '*'
            )
        );

        return $variables;
    }

    function execute()
    {
        $messages = array();
        $numCustomers = 0;
        $mailGateway = new NE_MailGateway();
        $UserNotificationGateway = new UserNotificationGateway();
        $currency = new Currency($this->user);

        // Summary Variables
        $summaryNames = array();
        $summaryErrors = array();

        // Required Services
        $requiredServices = array();

        /*
        Get the params:
          - Name of the rule
          - Email template (from custom emails. will display the template name, but will use the template id)
          - Rules: a serialized array withd fields and params. Array structure is:
                array(
                    'match'          => 'all',                                      // values: 'all', 'any'

                    'overrideOptOut' => '1',                                        // values: '1' = YES
                                                                                               '0' = NO

                    'rules'          => array(

                        array(                                                      // One array per field rule

                          'fieldtype' => 'Field Classification',                    // values: 'System',
                                                                                    //         'User',
                                                                                    //         'User Custom Field',
                                                                                    //         'Package',
                                                                                    //         'Package Custom Field'
                                                                                    //         'Invoice'

                          'fieldname' => 'System Field Name, or Custom Field ID',   // values by fieldtype:
                                                                                    //     System
                                                                                    //         'After Account Pending',
                                                                                    //         'After Account Activated',
                                                                                    //         'After Account Canceled',
                                                                                    //         'After Package Activated',
                                                                                    //         'After Package Canceled',
                                                                                    //         'Before Domain Expires',
                                                                                    //         'Before Hosting Package Due Date',
                                                                                    //         'Before Domain Package Due Date',
                                                                                    //         'Before SSL Package Due Date',
                                                                                    //         'Before General Package Due Date'
                                                                                    //
                                                                                    //     User
                                                                                    //         * User Field name          (`users`.FIELD_NAME)
                                                                                    //
                                                                                    //     User Custom Field
                                                                                    //         * User Custom Field id     (`customuserfields`.`id`)
                                                                                    //
                                                                                    //     Package
                                                                                    //         * Package Field name       (`domains`.FIELD_NAME)
                                                                                    //         * Package Group Type Id    (`package`.`planid`)
                                                                                    //
                                                                                    //     Package Custom Field
                                                                                    //         * Package Custom Field id  (`customField`.`id`)
                                                                                    //
                                                                                    //     Invoice
                                                                                    //         * Invoice Field name       (`invoice`.FIELD_NAME)

                          'operator'  => '<=',                                      // values: '<', '<=', '>', '>=', '=', '!='

                          'value'     => '5',
                          'comment'   => 'days'
                        ),
                        array(                                                      // another field rule array for the example.
                          'fieldtype' => 'Field Classification 2',
                          'fieldname' => 'Field Name 2',
                          'operator'  => '=',
                          'value'     => '3',
                          'comment'   => 'days'
                        )
                    )
                )
          - Enabled: 1 = YES, 0 = NO

          For example:
            Salutation                                  $AutomailerRule->getName()
            37 (Are you enjoying our application?)      $AutomailerRule->getTemplateID()
            serialized array with fields and params     $AutomailerRule->getRules()
            1                                           $AutomailerRule->getEnabled()
        */
        $gateway = new NotificationGateway();
        $AutomailerRules = $gateway->getNotifications();

        include_once 'modules/admin/models/Translations.php';
        $languages = CE_Lib::getEnabledLanguages();
        $translations = new Translations();

        require_once 'modules/billing/models/InvoiceEntriesGateway.php';
        $InvoiceEntriesGateway = new InvoiceEntriesGateway($this->user);

        include_once 'modules/support/models/TicketTypeGateway.php';
        $ticketTypeGateway = new TicketTypeGateway();
        $externallyCreatedTicketType = $ticketTypeGateway->getExternallyCreatedTicketType();

        //Get the customers for each case:
        while ($AutomailerRule = $AutomailerRules->fetch()) {
            if ($AutomailerRule->getEnabled() == 1) {
                $Rules = $AutomailerRule->getRules();

                if ($Rules == '') {
                    array_unshift($messages, $this->user->lang('%s customer(s) were notified.', $numCustomers));
                    return $messages;
                }

                $Rules = unserialize($Rules);

                if (!is_array($Rules)) {
                    array_unshift($messages, $this->user->lang('%s customer(s) were notified.', $numCustomers));
                    return $messages;
                }

                $result = $this->getResults($AutomailerRule);

                if ($result === false) {
                    continue;
                }

                if (count($Rules['rules']) == 1 && $Rules['rules'][0]['fieldtype'] == 'System' && $Rules['rules'][0]['fieldname'] == 'Before Domain Expires') {
                    // Requires "Domain Updater" Service
                    $requiredServices[] = 'domainupdater';
                }

                $keep = array();

                // If find customers:
                if ($result->getNumRows()) {
                    // - Setup the customer email template
                    $templategateway = new AutoresponderTemplateGateway();
                    $template = $templategateway->getAutoresponder($AutomailerRule->getTemplateID());

                    if ($template->getId() != $AutomailerRule->getTemplateID()) {
                        $summaryErrors[] = $AutomailerRule->getTemplateID();
                    } else {
                        $strEmailArrT = $template->getContents();
                        $strSubjectEmailT = $template->getSubject();
                        $templateID = $template->getId();
                        $strNameEmailT = $template->getName();

                        // - For each customer:
                        while ($row = $result->fetch()) {
                            //ignore if the notification was already sent
                            if (isset($row['package_id'])) {
                                $object_type = 'package';
                                $object_id = $row['package_id'];
                            } elseif (isset($row['invoice_id'])) {
                                $object_type = 'invoice';
                                $object_id = $row['invoice_id'];
                            } else {
                                $object_type = 'user';
                                $object_id = $row['customer_id'];
                            }

                            if (!$UserNotificationGateway->existUserNotification($object_type, $object_id, $AutomailerRule->getId(), $AutomailerRule->isSystem())) {
                                // * Instantiate the user
                                $user = new User($row['customer_id']);
                                $languageKey = ucfirst(strtolower($user->getRealLanguage()));
                                CE_Lib::setI18n($languageKey);

                                // * Create a copy of the email template
                                $strEmailArr = $strEmailArrT;
                                $strSubjectEmail = $strSubjectEmailT;

                                if ($templateID !== false) {
                                    if (count($languages) > 1) {
                                        $strSubjectEmail = $translations->getValue(EMAIL_SUBJECT, $templateID, $languageKey, $strSubjectEmail);
                                        $strEmailArr = $translations->getValue(EMAIL_CONTENT, $templateID, $languageKey, $strEmailArr);
                                    }
                                }

                                // * Get tags values
                                $userPackage = false;
                                $package = false;
                                $additionalEmailTags = array();

                                if (isset($row['package_id'])) {
                                    $userPackage = new UserPackage((int)$row['package_id']);
                                    $recurringFee = $userPackage->getRecurringFeeEntry();
                                    $package = new Package($userPackage->Plan);

                                    if (count($languages) > 1) {
                                        $additionalEmailTags["[PACKAGEGROUPNAME]"] = $translations->getValue(PRODUCT_GROUP_NAME, $package->productGroup->getId(), $languageKey, $package->productGroup->fields['name']);
                                    } else {
                                        $additionalEmailTags["[PACKAGEGROUPNAME]"] = $package->productGroup->fields['name'];
                                    }

                                    $additionalEmailTags["[USERPACKAGEID]"] = $row['package_id'];
                                    $additionalEmailTags["[NEXTDUEDATE]"] = $recurringFee->getNextBillDate();
                                    $additionalEmailTags["[BILLINGEMAIL]"] = $this->settings->get("Billing E-mail");
                                }

                                if (isset($row['invoice_id'])) {
                                    $tInvoiceID = (int)$row['invoice_id'];
                                    $tempInvoice = new Invoice($tInvoiceID);
                                    $tempDescription = "";

                                    foreach ($tempInvoice->getInvoiceEntries() as $tempInvoiceEntry) {
                                        $invoice_label = $InvoiceEntriesGateway->getFullEntryDescription($tempInvoiceEntry->getId(), $languageKey);
                                        $tempDescription .="\n".$invoice_label;
                                        $daterangearray = unserialize($this->settings->get('Invoice Entry Date Range Format'));

                                        if ($tempInvoiceEntry->getPeriodStart() && $daterangearray[0] != '') {
                                            $tempDescription .= ' ('.CE_Lib::formatDateWithPHPFormat($tempInvoiceEntry->getPeriodStart(), $daterangearray[0]);

                                            if ($tempInvoiceEntry->getPeriodEnd() && $daterangearray[1] != '') {
                                                $tempDescription .= ' - ';
                                                $tempDescription .= CE_Lib::formatDateWithPHPFormat($tempInvoiceEntry->getPeriodEnd(), $daterangearray[1]);
                                            }
                                            
                                            $tempDescription .= ')';
                                        }

                                        $tempDescription .= " ".$currency->format($tempInvoice->getCurrency(), $tempInvoiceEntry->getPrice(), true, 'NONE', $user->isHTMLMails() ? true : false, true, true);
                                    }

                                    if ($tempInvoice->getSentDate() == "") {
                                        $sentdate = date($this->settings->get('Date Format'), mktime(0, 0, 0, date("m"), date("d"), date("Y")));
                                    } else {
                                        $sentdate = date($this->settings->get('Date Format'), $tempInvoice->getSentDate("timestamp"));
                                    }

                                    $tempTax = $tempInvoice->getTaxCharged();
                                    $amountExTax = $tempInvoice->getPrice() - $tempTax;

                                    include_once 'modules/billing/models/BillingGateway.php';
                                    $billingGateway = new BillingGateway($this->user);
                                    $additionalEmailTags["[SENTDATE]"] = $sentdate;
                                    $additionalEmailTags["[DATE]"] = date($this->settings->get('Date Format'), $tempInvoice->getDate("timestamp"));
                                    $additionalEmailTags["[AMOUNT]"] = $currency->format($tempInvoice->getCurrency(), $tempInvoice->getPrice(), true, 'NONE', $user->isHTMLMails() ? true : false, true, true);
                                    $additionalEmailTags["[PAID]"] = $currency->format($tempInvoice->getCurrency(), $tempInvoice->getPrice() - $tempInvoice->getBalanceDue(), true, 'NONE', $user->isHTMLMails() ? true : false, true, true);
                                    $additionalEmailTags["[BALANCEDUE]"] = $currency->format($tempInvoice->getCurrency(), $tempInvoice->getBalanceDue(), true, 'NONE', $user->isHTMLMails() ? true : false, true, true);
                                    $additionalEmailTags["[RAW_AMOUNT]"] = sprintf("%01.".$currency->getPrecision($tempInvoice->getCurrency())."f", round($tempInvoice->getPrice(), $currency->getPrecision($tempInvoice->getCurrency())));
                                    $additionalEmailTags["[TAX]"] = $currency->format($tempInvoice->getCurrency(), $tempTax, true, 'NONE', $user->isHTMLMails() ? true : false, true, true);
                                    $additionalEmailTags["[AMOUNT_EX_TAX]"] = $currency->format($tempInvoice->getCurrency(), $amountExTax, true, 'NONE', $user->isHTMLMails() ? true : false, true, true);
                                    $additionalEmailTags["[INVOICENUMBER]"] = $tInvoiceID;
                                    $additionalEmailTags["[SUBSCRIPTION_ID]"] = $tempInvoice->getSubscriptionID();
                                    $additionalEmailTags["[INVOICEDESCRIPTION]"] = $user->isHTMLMails() ? nl2br($tempDescription) : $tempDescription;
                                    $additionalEmailTags["[DIRECTPAYMENTLINK]"] = $billingGateway->createDirectPaymentLink($user, $tInvoiceID);
                                }

                                // * Parse a copy of the email template and the email subject template
                                $gateway = new UserPackageGateway($this->user);
                                $strSubjectEmail = $gateway->_replaceTags1($strSubjectEmail, $user, $package);
                                $strEmailArr = $gateway->_replaceTags1($strEmailArr, $user, $package);

                                if ($userPackage) {
                                    $gateway->_replaceTagsByType($userPackage, $user, $strEmailArr, $strSubjectEmail);
                                }

                                if (count($additionalEmailTags)) {
                                    $strSubjectEmail = str_replace(array_keys($additionalEmailTags), $additionalEmailTags, $strSubjectEmail);
                                    $strEmailArr = str_replace(array_keys($additionalEmailTags), $additionalEmailTags, $strEmailArr);
                                }

                                //Need to replace this tags here for the Email Event to save the content with the tags replaced.
                                $strSubjectEmail = $mailGateway->replaceMailTags($strSubjectEmail, $user);
                                $strEmailArr = $mailGateway->replaceMailTags($strEmailArr, $user);

                                //Determine if send email and/or create a ticket
                                $Action = isset($Rules['actionid'])? $Rules['actionid'] : 'email';
                                $createdTicket = false;
                                $sentEmail = false;

                                switch ($Action) {
                                    case 'ticket':
                                        try {
                                            // Create ticket
                                            $args = array();
                                            $args['message'] = strip_tags(str_replace(array("<br>", "</br>", "</ br>", "<br/>", "<br />"), "\n", $strEmailArr));
                                            $args['messagetype'] = $externallyCreatedTicketType->getId();
                                            $args['subject'] = $strSubjectEmail;
                                            $args['userid'] = $row['customer_id'];
                                            $args['product_id'] = (isset($row['package_id']))? $row['package_id'] : 0;
                                            $args['mode'] = "admin";
                                            $args['inNameOfUser'] = false;
                                            $args['notifyUser'] = true;
                                            $createdTicket = true;
                                            $tg = new TicketGateway($this->user);
                                            $ticket = $tg->save_new_ticket($args);
                                        } catch (Exception $e) {
                                            // Problem already logged inside NE_MailGetway, so nothing to do here.
                                        }
                                        break;
                                    case 'email':
                                        try {
                                            $fromName = $this->settings->get('Company Name');
                                            $fromEmail = $this->settings->get('Support E-mail');
                                            if ($AutomailerRule->getFromName() != '') {
                                                $fromName = $AutomailerRule->getFromName();
                                            }
                                            if ($AutomailerRule->getFromEmail() != '') {
                                                $fromEmail = $AutomailerRule->getFromEmail();
                                            } elseif ($template->getOverrideFrom() != '') {
                                                $fromEmail = $template->getOverrideFrom();
                                            }

                                            // Send Email
                                            // * Send a parsed copy of the email template to the customer
                                            $mailerResult = $mailGateway->mailMessage(
                                                $strEmailArr,
                                                $fromEmail,
                                                $fromName,
                                                $row['customer_id'],
                                                "",
                                                $strSubjectEmail,
                                                3,
                                                0,
                                                'notifications',
                                                '',
                                                '',
                                                MAILGATEWAY_CONTENTTYPE_HTML
                                            );

                                            if (!($mailerResult instanceof CE_Error)) {
                                                // log the email sent
                                                $clientsEventLog = Client_EventLog::newInstance(false, $row['customer_id'], $row['customer_id'], CLIENT_EVENTLOG_SENTNOTIFICATIONEMAIL, $this->user->getId());
                                                $clientsEventLog->setEmailSent($strSubjectEmail, $strEmailArr);
                                                $clientsEventLog->save();
                                                $sentEmail = true;
                                            }
                                        } catch (Exception $e) {
                                            try {
                                                // Create ticket
                                                $args = array();
                                                $args['message'] = strip_tags(str_replace(array("<br>", "</br>", "</ br>", "<br/>", "<br />"), "\n", $strEmailArr));
                                                $args['messagetype'] = $externallyCreatedTicketType->getId();
                                                $args['subject'] = $strSubjectEmail;
                                                $args['userid'] = $row['customer_id'];
                                                $args['product_id'] = (isset($row['package_id']))? $row['package_id'] : 0;
                                                $args['mode'] = "admin";
                                                $args['inNameOfUser'] = false;
                                                $args['notifyUser'] = true;
                                                $createdTicket = true;
                                                $tg = new TicketGateway($this->user);
                                                $ticket = $tg->save_new_ticket($args);
                                            } catch (Exception $e) {
                                                // Problem already logged inside NE_MailGetway, so nothing to do here.
                                            }
                                        }
                                        break;
                                }

                                if ($createdTicket || $sentEmail) {
                                    //track the notification by adding it to the user_notifications table
                                    $userNotification = new UserNotification();
                                    $userNotification->setObjectType($object_type);
                                    $userNotification->setObjectID($object_id);
                                    $userNotification->setRuleID($AutomailerRule->getId());
                                    $userNotification->setDate(date("Y-m-d H:i:s"));
                                    $userNotification->save();

                                    // * Add Customer to summary
                                    $summaryNames[$AutomailerRule->getName()][] = $user->getFullName(true).((isset($row['package_id']))? ', package: '.$row['package_id'] : '').((isset($row['invoice_id']))? ', invoice: '.$row['invoice_id'] : '');
                                    $numCustomers++;
                                }
                            }
                        }

                        //List of all objects matching the notification, even if already notified
                        $keep[] = $object_type.'_'.$object_id;
                    }
                }

                //delete user notifications that no longer applies
                $UserNotificationGateway->user = $this->user;
                $UserNotificationGateway->deleteExpiredUserNotifications($AutomailerRule->getId(), $keep);
            }
        }

        if ($this->settings->get('plugin_automailer_Summary E-mail') != "") {
            $summaryEmail = '';

            if (count($requiredServices) > 0) {
                $summaryEmailRequirementsIssues = array();
                $summaryEmailRequirementsTime = array();

                foreach ($requiredServices as $requiredService) {
                    // Get the Service name
                    $requiredServiceName = $this->settings->get('plugin_'.$requiredService.'_Plugin Name');

                    if (!$requiredServiceName) {
                        $requiredServiceName = $requiredService;
                    }

                    // Verify if the Service is enabled
                    if (!$this->settings->get('plugin_'.$requiredService.'_Enabled')) {
                        $summaryEmailRequirementsIssues[] = $this->user->lang("The service %s is not enabled", $requiredServiceName);
                    } else {
                        // Verify the last time the Service ran
                        $requiredServiceInfo = $this->settings->get('service_'.$requiredService.'_info');

                        if (!$requiredServiceInfo) {
                            $summaryEmailRequirementsIssues[] = $this->user->lang("The service %s does not have information about its last run.", $requiredServiceName);
                        } else {
                            $requiredServiceInfo = unserialize($requiredServiceInfo);

                            if (!is_array($requiredServiceInfo) || !isset($requiredServiceInfo['time'])) {
                                $summaryEmailRequirementsIssues[] = $this->user->lang("The information of the service %s about its last run, seems to be corrupted.", $requiredServiceName);
                            } else {
                                $summaryEmailRequirementsTime[$requiredServiceName] = $requiredServiceInfo['time'];
                            }
                        }
                    }
                }

                if (count($summaryEmailRequirementsIssues) > 0) {
                    $summaryEmail .= $this->user->lang("Auto Mailer / Ticket Creator has detected issues with some of the required Services for the Events selected. Please take a look").":\n";

                    foreach ($summaryEmailRequirementsIssues as $summaryEmailRequirementsIssue) {
                        $summaryEmail .= " - ".$summaryEmailRequirementsIssue."\n";
                    }

                    $summaryEmail .= "\n";
                }

                if (count($summaryEmailRequirementsTime) > 0) {
                    $summaryEmail .= $this->user->lang("Last execution of the required Services for the Events selected, were").":\n";

                    foreach ($summaryEmailRequirementsTime as $summaryEmailRequirementName => $summaryEmailRequirementTime) {
                        $summaryEmail .= " - ".$summaryEmailRequirementName.". Executed on: ".$summaryEmailRequirementTime."\n";
                    }

                    $summaryEmail .= "\n";
                }
            }

            if (count($summaryErrors) > 0) {
                $summaryEmail .= $this->user->lang("Auto Mailer / Ticket Creator has not been able to find the Email Templates with the following ids. Please take a look").":\n";

                foreach ($summaryErrors as $summaryError) {
                    $summaryEmail .= " - ".$summaryError."\n";
                }

                $summaryEmail .= "\n";
            }

            if (count($summaryNames) > 0) {
                $summaryEmail .= $this->user->lang("Auto Mailer / Ticket Creator has emailed and/or created tickets for the following events to the following customers").":\n";

                foreach ($summaryNames as $NotificationName => $summaryCustomers) {
                    $summaryEmail .= "\n".$NotificationName.":\n";

                    foreach ($summaryCustomers as $summaryCustomer) {
                        $summaryEmail .= " - ".$summaryCustomer."\n";
                    }
                }
            }

            if ($summaryEmail != '') {
                $destinataries = explode("\r\n", $this->settings->get('plugin_automailer_Summary E-mail'));

                foreach ($destinataries as $destinatary) {
                    $mailGateway->mailMessageEmail(
                        $summaryEmail,
                        $this->settings->get('Support E-mail'),
                        $this->settings->get('Company Name'),
                        $destinatary,
                        "",
                        $this->settings->get('plugin_automailer_Summary E-mail Subject')
                    );
                }
            }
        }

        array_unshift($messages, $this->user->lang('%s customer(s) were notified.', $numCustomers));
        return $messages;
    }

    function getResults($AutomailerRule)
    {
        $Rules = $AutomailerRule->getRules();
        $Rules = unserialize($Rules);
        $Match = $Rules['match'];

        //IGNORE IF CUSTOMER DO NOT WANT EMAILS, UNLESS THE NOTIFICATION SAYS TO SEND TO ALL.
        $overrideOptOut = isset($Rules['overrideOptOut'])? $Rules['overrideOptOut'] : '1';
        $excludeJoin = '';
        $excludeWhere = '';

        if (!$overrideOptOut) {
            $query = "SELECT `id` FROM `customuserfields` WHERE `type` = ?";
            $result = $this->db->query($query, TYPE_ALLOW_EMAIL);
            $row = $result->fetch();
            $excludeJoin = "JOIN `user_customuserfields` ucufex ON u.`id` = ucufex.`userid` ";
            $excludeWhere = "AND ucufex.`customid` = ".$row['id']." AND ucufex.`value` = 1 ";
        }

        $Rules = $Rules['rules'];

        if ($AutomailerRule->isSystem()) {
            //IT IS A PREDEFINED RULE

            switch ($Rules[0]['fieldname']) {
                // Before Dates
                case 'Before Domain Expires':
                case 'Before Hosting Package Due Date':
                case 'Before Domain Package Due Date':
                case 'Before SSL Package Due Date':
                case 'Before General Package Due Date':
                    $dateTimeStamp = mktime(0, 0, 0, date("m"), date("d") + $Rules[0]['value'], date("Y"));
                    break;

                // After Dates
                case 'After Account Pending':
                case 'After Account Activated':
                case 'After Account Canceled':
                case 'After Package Activated':
                case 'After Package Canceled':
                default:
                    $dateTimeStamp = mktime(0, 0, 0, date("m"), date("d") - $Rules[0]['value'], date("Y"));
                    break;
            }

            $periodStart = date('Y-m-d 0:0:0', $dateTimeStamp);
            $periodEnd = date('Y-m-d 23:59:59', $dateTimeStamp);

            switch ($Rules[0]['fieldname']) {
                case 'After Account Pending':
                    $status = StatusAliasGateway::getInstance($this->user)->getUserStatusIdsFor(USER_STATUS_PENDING);
                    $query = "SELECT u.`id` AS customer_id "
                        ."FROM `users` u "
                        .$excludeJoin
                        ."WHERE u.`groupid` = 1 "
                        ."AND u.`status` IN (".implode(', ', $status).") "
                        ."AND u.`dateActivated` BETWEEN ? AND ? "
                        .$excludeWhere;
                    $result = $this->db->query($query, $periodStart, $periodEnd);
                    break;
                case 'After Account Activated':
                    $status = StatusAliasGateway::getInstance($this->user)->getUserStatusIdsFor(USER_STATUS_ACTIVE);
                    $query = "SELECT u.`id` AS customer_id "
                        ."FROM `users` u "
                        ."JOIN `user_customuserfields` ucuf ON u.`id` = ucuf.`userid` "
                        .$excludeJoin
                        ."WHERE u.`groupid` = 1 "
                        ."AND u.`status` IN (".implode(', ', $status).") "
                        ."AND ucuf.`customid` IN ( "
                        ."SELECT cuf.`id` "
                        ."FROM `customuserfields` cuf "
                        ."WHERE cuf.`name` = 'Last Status Date' "
                        ."AND cuf.`type` = 52) "
                        ."AND ucuf.`value` BETWEEN ? AND ? "
                        .$excludeWhere;
                    $result = $this->db->query($query, $periodStart, $periodEnd);
                    break;
                case 'After Account Canceled':  // Includes Canceled and Inactive Users
                    $status = StatusAliasGateway::getInstance($this->user)->getUserStatusIdsFor(array(USER_STATUS_INACTIVE, USER_STATUS_CANCELLED));
                    $query = "SELECT u.`id` AS customer_id "
                        ."FROM `users` u "
                        ."JOIN `user_customuserfields` ucuf ON u.`id` = ucuf.`userid` "
                        .$excludeJoin
                        ."WHERE u.`groupid` = 1 "
                        ."AND u.`status` IN (".implode(', ', $status).") "
                        ."AND ucuf.`customid` IN ( "
                        ."SELECT cuf.`id` "
                        ."FROM `customuserfields` cuf "
                        ."WHERE cuf.`name` = 'Last Status Date' "
                        ."AND cuf.`type` = 52) "
                        ."AND ucuf.`value` BETWEEN ? AND ? "
                        .$excludeWhere;
                    $result = $this->db->query($query, $periodStart, $periodEnd);
                    break;
                case 'After Package Activated':
                    $status = StatusAliasGateway::getInstance($this->user)->getPackageStatusIdsFor(PACKAGE_STATUS_ACTIVE);
                    $query = "SELECT DISTINCT u.`id` AS customer_id, d.`id` AS package_id "
                        ."FROM `users` u "
                        ."JOIN `domains` d ON u.`id` = d.`CustomerID` "
                        ."JOIN `object_customField` ocf ON d.`id` = ocf.`objectid` "
                        .$excludeJoin
                        ."WHERE d.`status` IN (".implode(', ', $status).") "
                        ."AND ocf.`customFieldId` IN ( "
                        ."SELECT cf.`id` "
                        ."FROM `customField` cf "
                        ."WHERE cf.`name` = 'Last Status Date' "
                        ."AND cf.`groupId` = 2) "
                        ."AND ocf.`value` BETWEEN ? AND ? "
                        .$excludeWhere;
                    $result = $this->db->query($query, $periodStart, $periodEnd);
                    break;
                case 'After Package Canceled':  // Includes Canceled, Suspended, and Expired Packages
                    $status = StatusAliasGateway::getInstance($this->user)->getPackageStatusIdsFor(array(PACKAGE_STATUS_SUSPENDED, PACKAGE_STATUS_CANCELLED, PACKAGE_STATUS_EXPIRED));
                    $query = "SELECT DISTINCT u.`id` AS customer_id, d.`id` AS package_id "
                        ."FROM `users` u "
                        ."JOIN `domains` d ON u.`id` = d.`CustomerID` "
                        ."JOIN `object_customField` ocf ON d.`id` = ocf.`objectid` "
                        .$excludeJoin
                        ."WHERE d.`status` IN (".implode(', ', $status).") "
                        ."AND ocf.`customFieldId` IN ( "
                        ."SELECT cf.`id` "
                        ."FROM `customField` cf "
                        ."WHERE cf.`name` = 'Last Status Date' "
                        ."AND cf.`groupId` = 2) "
                        ."AND ocf.`value` BETWEEN ? AND ? "
                        .$excludeWhere;
                    $result = $this->db->query($query, $periodStart, $periodEnd);
                    break;
                case 'Before Domain Expires':  // Includes Active and Pending Cancellation Packages
                    // Query based on the custom field "Expiration Date".  The field value is timestamp type.
                    $status = StatusAliasGateway::getInstance($this->user)->getPackageStatusIdsFor(array(PACKAGE_STATUS_ACTIVE, PACKAGE_STATUS_PENDINGCANCELLATION));
                    $query = "SELECT DISTINCT u.`id` AS customer_id, d.`id` AS package_id "
                        ."FROM `users` u "
                        ."JOIN `domains` d ON u.`id` = d.`CustomerID` "
                        ."JOIN `object_customField` ocf ON d.`id` = ocf.`objectid` "
                        .$excludeJoin
                        ."WHERE d.`status` IN (".implode(', ', $status).") "
                        ."AND d.`Plan` IN ( "
                        ."SELECT pa.`id` "
                        ."FROM `package` pa "
                        ."WHERE pa.`planid` IN ( "
                        ."SELECT pr.`id` "
                        ."FROM `promotion` pr "
                        ."WHERE pr.`type` = 3)) "
                        ."AND ocf.`customFieldId` IN ( "
                        ."SELECT cf.`id` "
                        ."FROM `customField` cf "
                        ."WHERE cf.`name` = 'Expiration Date' "
                        ."AND cf.`groupId` = 2 "
                        ."AND cf.`subGroupId` = 3) "
                        ."AND ocf.`value` BETWEEN ? AND ? "
                        .$excludeWhere;
                    $result = $this->db->query($query, $periodStart, $periodEnd);
                    break;
                case 'Before Hosting Package Due Date':
                case 'Before Domain Package Due Date':
                case 'Before SSL Package Due Date':
                case 'Before General Package Due Date':
                    $packageTypeId = PACKAGE_TYPE_GENERAL;
                    switch ($Rules[0]['fieldname']) {
                        case 'Before Hosting Package Due Date':
                            $packageTypeId = PACKAGE_TYPE_HOSTING;
                            break;
                        case 'Before Domain Package Due Date':
                            $packageTypeId = PACKAGE_TYPE_DOMAIN;
                            break;
                        case 'Before SSL Package Due Date':
                            $packageTypeId = PACKAGE_TYPE_SSL;
                            break;
                        case 'Before General Package Due Date':
                            $packageTypeId = PACKAGE_TYPE_GENERAL;
                            break;
                    }

                    // Query based on the "nextbilldate" field of the "recurringfee" table.
                    $status = StatusAliasGateway::getInstance($this->user)->getPackageStatusIdsFor(array(PACKAGE_STATUS_ACTIVE, PACKAGE_STATUS_PENDINGCANCELLATION));
                    $query = "SELECT DISTINCT u.`id` AS customer_id, d.`id` AS package_id "
                        ."FROM `users` u "
                        ."JOIN `domains` d ON u.`id` = d.`CustomerID` "
                        ."JOIN `recurringfee` rf ON d.`id` = rf.`appliestoid` AND rf.`billingtypeid` = -1 AND rf.`recurring` = 1 AND rf.paymentterm != 0 "
                        .$excludeJoin
                        ."WHERE d.`status` IN (".implode(', ', $status).") "
                        ."AND d.`Plan` IN ( "
                        ."SELECT pa.`id` "
                        ."FROM `package` pa "
                        ."WHERE pa.`planid` IN ( "
                        ."SELECT pr.`id` "
                        ."FROM `promotion` pr "
                        ."WHERE pr.`type` = $packageTypeId)) "
                        ."AND rf.`nextbilldate` BETWEEN ? AND ? "
                        .$excludeWhere;
                    $result = $this->db->query($query, $periodStart, $periodEnd);
                    break;
                default:
                    $result = false;
                    break;
            }
        } else {
            $gateway = new NotificationGateway();
            $joinFilters = "";
            $whereFiltersArray = array();
            $hasPackage = 0;
            $hasInvoice = 0;
            $joinIndex = 0; // To tag the join tables with different names and avoid issues
            $parameters = array();
            $jointypes = array();
            $jointypes['package_custom_field'] = array();

            //We should not be doing joins if same field, we should be doing where value IN ()
            foreach ($Rules as $Rule) {
                switch ($Rule['fieldtype']) {
                    case 'User':
                        $jointypes['user_field'][$Rule['fieldname']][] = $Rule;
                        break;
                    case 'User Custom Field':
                        $jointypes['user_custom_field'][$Rule['fieldname']][] = $Rule;
                        break;
                    case 'Package':
                        $jointypes['package_field'][$Rule['fieldname']][] = $Rule;
                        $hasPackage = 1;
                        break;
                    case 'Package Custom Field':
                        $jointypes['package_custom_field'][$Rule['fieldname']][] = $Rule;
                        $hasPackage = 1;
                        break;
                    case 'Invoice':
                        $jointypes['invoice_field'][$Rule['fieldname']][] = $Rule;
                        $hasInvoice = 1;
                        break;
                }
            }

            //let's look for each type so we can build a better join
            foreach ($jointypes as $typename => $jointype) {
                switch ($typename) {
                    case 'user_field':
                        foreach ($jointype as $key => $values) {
                            //let's see if we can merge some joins if we are working with same custom field
                            foreach ($values as $rule) {
                                if ($this->isDateRule($gateway->getRuleFields('user'), $rule)) {
                                    $whereFiltersArray[] = $this->completeConditions(
                                        $rule,
                                        "u.`".$rule['fieldname']."`"
                                    );
                                } else {
                                    $val = $this->db->escape_string($rule['value']);
                                    $whereFiltersArray[] = "(u.`".$rule['fieldname']."` ".$rule['operator']." '".$val."')";
                                }
                            }
                        }

                        break;
                    case 'user_custom_field':
                        foreach ($jointype as $key => $values) {
                            //let's see if we can merge some joins if we are working with same custom field
                            $joinFilters .= " JOIN `user_customuserfields` ucuf".$joinIndex." ON u.`id` = ucuf".$joinIndex.".`userid` ";

                            foreach ($values as $rule) {
                                if ($this->isDateRule($gateway->getRuleFields('user'), $rule)) {
                                    $whereFiltersArray[] = $this->completeConditions(
                                        $rule,
                                        "ucuf".$joinIndex.".`value`",
                                        array("ucuf$joinIndex.`customid` = ".$rule['fieldname'])
                                    );
                                } else {
                                    $val = $this->db->escape_string($rule['value']);
                                    $whereFiltersArray[] = "(ucuf".$joinIndex.".`customid` = ".$rule['fieldname']." AND ucuf".$joinIndex.".`value` ".$rule['operator']." '".$val."')";
                                }
                            }

                            $joinIndex++; // since we are adding a join filter let's raise
                        }

                        break;
                    case 'package_field':
                        foreach ($jointype as $key => $values) {
                            //let's see if we can merge some joins if we are working with same custom field
                            foreach ($values as $rule) {
                                if ($this->isDateRule($gateway->getRuleFields('package'), $rule)) {
                                    $whereFiltersArray[] = $this->completeConditions(
                                        $rule,
                                        "d.`".$rule['fieldname']."`"
                                    );
                                } else {
                                    $val = $this->db->escape_string($rule['value']);

                                    if ($rule['fieldname'] == 'Group') {
                                        $whereFiltersArray[] = "(d.`Plan` IN (SELECT pa.`id` FROM `package` pa WHERE pa.`planid` ".$rule['operator']." '".$val."'))";
                                    } else {
                                        $whereFiltersArray[] = "(d.`".$rule['fieldname']."` ".$rule['operator']." '".$val."')";
                                    }
                                }
                            }
                        }

                        break;
                    case 'package_custom_field':
                        foreach ($jointype as $key => $values) {
                            //let's see if we can merge some joins if we are working with same custom field
                            $joinFilters .= " JOIN `object_customField` ocf".$joinIndex." ON d.`id` = ocf".$joinIndex.".`objectid` ";

                            foreach ($values as $rule) {
                                if ($this->isDateRule($gateway->getRuleFields('package'), $rule)) {
                                    $whereFiltersArray[] = $this->completeConditions(
                                        $rule,
                                        "ocf$joinIndex.value",
                                        array("ocf$joinIndex.`customFieldId` = ".$rule['fieldname'])
                                    );
                                } else {
                                    $val = $this->db->escape_string($rule['value']);
                                    $whereFiltersArray[] = "(ocf".$joinIndex.".`customFieldId` = ".$rule['fieldname']." AND ocf".$joinIndex.".`value` ".$rule['operator']." '".$val."')";
                                }
                            }

                            $joinIndex++; // since we are adding a join filter let's raise
                        }

                        break;
                    case 'invoice_field':
                        foreach ($jointype as $key => $values) {
                            //let's see if we can merge some joins if we are working with same custom field
                            foreach ($values as $rule) {
                                if ($this->isDateRule($gateway->getRuleFields('invoice'), $rule)) {
                                    $whereFiltersArray[] = $this->completeConditions(
                                        $rule,
                                        "i.`{$rule['fieldname']}`"
                                    );
                                } elseif ($rule['fieldname'] == 'status') {
                                    switch ($rule['value']) {
                                        case Notification::INVOICE_STATUS_NOT_PAID_PROCESSED:
                                            $joinFilters .= " LEFT JOIN invoicetransaction it".$joinIndex." ON i.id = it".$joinIndex.".invoiceid ";
                                            $whereFiltersArray[] = "(i.status IN (".INVOICE_STATUS_UNPAID.", ".INVOICE_STATUS_PARTIALLY_PAID.") AND it$joinIndex.id IS NOT NULL)";
                                            $joinIndex++;
                                            break;
                                        case Notification::INVOICE_STATUS_NOT_PAID:
                                            $whereFiltersArray[] = "(i.status IN (".INVOICE_STATUS_UNPAID.", ".INVOICE_STATUS_PARTIALLY_PAID."))";
                                            break;
                                        case INVOICE_STATUS_PAID:
                                        case INVOICE_STATUS_VOID:
                                        case INVOICE_STATUS_REFUNDED:
                                        case INVOICE_STATUS_PENDING:
                                        case INVOICE_STATUS_CREDITED:
                                            $whereFiltersArray[] = "(i.status = ".$rule['value'].')';
                                            break;
                                        default:
                                            throw new Exception('Invalid invoice status');
                                    }
                                } else {
                                    $value = $this->db->escape_string($rule['value']);
                                    $whereFiltersArray[] = "(i.`".$rule['fieldname']."` ".$rule['operator']." '".$value."')";
                                }
                            }
                        }

                        break;
                }
            }

            $whereFilters = "";

            if (count($whereFiltersArray) > 0) {
                if ($Match === 'all') {
                    $whereFilters = " AND (".implode(" AND ", $whereFiltersArray).") ";
                } elseif ($Match === 'any') {
                    $whereFilters = " AND (".implode(" OR ", $whereFiltersArray).") ";
                }
            }

            $selectPackageID = "";
            $joinDomains = "";

            if ($hasPackage) {
                $selectPackageID = " , d.`id` AS package_id ";
                $joinDomains = " JOIN `domains` d ON u.`id` = d.`CustomerID` ";
            }

            $selectInvoiceID = "";
            $joinInvoice = "";

            if ($hasInvoice) {
                $selectInvoiceID = " , i.`id` AS invoice_id ";
                $joinInvoice = " JOIN `invoice` i ON u.`id` = i.`customerid` ";
            }

            $query = "SELECT DISTINCT u.`id` AS customer_id "
                .$selectPackageID
                .$selectInvoiceID
                ." FROM `users` u "
                .$joinDomains
                .$joinInvoice
                .$joinFilters
                .$excludeJoin
                ." WHERE u.`groupid` = 1 "
                .$whereFilters
                .$excludeWhere;
            CE_Lib::log(7, "Automailer SQL: $query");

            try {
                $result = $this->db->query($query, $parameters);
            } catch (Exception $ex) {
                CE_Lib::debug($ex->getMessage());
                return false;
            }
        }

        return $result;
    }

    function getAppliesTo($AutomailerRule)
    {
        $appliesToArray = array(
            'apply' => array(
                'users' => array(),
                'packages' => array(),
                'invoices' => array()
            )
        );

        $UserNotificationGateway = new UserNotificationGateway();
        $Rules = $AutomailerRule->getRules();

        if ($Rules == '') {
            return $appliesToArray;
        }

        $Rules = unserialize($Rules);

        if (!is_array($Rules)) {
            return $appliesToArray;
        }

        $result = $this->getResults($AutomailerRule);

        if ($result === false) {
            return $appliesToArray;
        }

        $keep = array();

        // If find customers:
        if ($result->getNumRows()) {
            // - For each customer:
            while ($row = $result->fetch()) {
                //ignore if the notification was already sent
                if (isset($row['package_id'])) {
                    $object_type = 'package';
                    $object_id = $row['package_id'];
                } elseif (isset($row['invoice_id'])) {
                    $object_type = 'invoice';
                    $object_id = $row['invoice_id'];
                } else {
                    $object_type = 'user';
                    $object_id = $row['customer_id'];
                }

                if (!$UserNotificationGateway->existUserNotification($object_type, $object_id, $AutomailerRule->getId(), $AutomailerRule->isSystem())) {
                    if (isset($row['package_id'])) {
                        $appliesToArray['apply']['packages'][] = $row['package_id'];
                    } elseif (isset($row['invoice_id'])) {
                        $appliesToArray['apply']['invoices'][] = $row['invoice_id'];
                    } else {
                        $appliesToArray['apply']['users'][] = $row['customer_id'];
                    }
                }

                //List of all objects matching the notification, even if already notified
                $keep[] = $object_type.'_'.$object_id;
            }
        }

        //delete user notifications that no longer applies
        $UserNotificationGateway->user = $this->user;
        $UserNotificationGateway->deleteExpiredUserNotifications($AutomailerRule->getId(), $keep);

        return $appliesToArray;
    }

    private function isDateRule($fields, $rule)
    {
        $isDate = false;
        foreach ($fields as $haystack => $val) {
            $needle = '_'.$rule['fieldname'];
            $length = strlen($needle);

            if (substr($haystack, -$length) === $needle) {
                $isDate = $val[1] && isset($rule['operator_dates']) && isset($rule['operator_dates_units']);
                break;
            }
        }
        return $isDate;
    }

    private function completeConditions($rule, $field, $conditions = array())
    {
        $value = (float) $rule['value'];
        switch ($rule['operator_dates_units']) {
            case Notification::DATE_UNIT_HOURS:
                $value = $value * 60 * 60;
                $valueDec = $value - 60 * 60;
                $valueInc = $value + 60 * 60;
                break;
            case Notification::DATE_UNIT_DAYS:
                $value = $value * 60 * 60 * 24;
                $valueDec = $value - 60 * 60 * 24;
                $valueInc = $value + 60 * 60 * 24;
                break;
            case Notification::DATE_UNIT_WEEKS:
                $value = $value * 60 * 60 * 24 * 7;
                $valueDec = $value - 60 * 60 * 24 * 7;
                $valueInc = $value + 60 * 60 * 24 * 7;
                break;
            case Notification::DATE_UNIT_MONTHS:
                $value = $value * 60 * 60 * 24 *  30;
                $valueDec = $value - 60 * 60 * 24 * 30;
                $valueInc = $value + 60 * 60 * 24 * 30;
                break;
        }

        $now = date('Y-m-d H:i:s');

        // DATE_FORMAT() below is used to normalize date-only fields to date-time
        switch ($rule['operator_dates']) {
            case Notification::DATE_OPERATOR_WAS_EXACTLY:
                $conditions[] = "TIME_TO_SEC(TIMEDIFF('$now', DATE_FORMAT($field, '%Y-%m-%d %H:%i:%s'))) >= $value";
                $conditions[] = "TIME_TO_SEC(TIMEDIFF('$now', DATE_FORMAT($field, '%Y-%m-%d %H:%i:%s'))) < $valueInc";
                break;
            case Notification::DATE_OPERATOR_WAS_LESS_THAN:
                $conditions[] = "TIME_TO_SEC(TIMEDIFF('$now', DATE_FORMAT($field, '%Y-%m-%d %H:%i:%s'))) > 0";
                $conditions[] = "TIME_TO_SEC(TIMEDIFF('$now', DATE_FORMAT($field, '%Y-%m-%d %H:%i:%s'))) < $value";
                break;
            case Notification::DATE_OPERATOR_WAS_MORE_THAN:
                $conditions[] = "TIME_TO_SEC(TIMEDIFF('$now', DATE_FORMAT($field, '%Y-%m-%d %H:%i:%s'))) > $value";
                break;
            case Notification::DATE_OPERATOR_WILL_OCCUR_IN:
                $conditions[] = "TIME_TO_SEC(TIMEDIFF(DATE_FORMAT($field, '%Y-%m-%d %H:%i:%s'), '$now')) > $value";
                $conditions[] = "TIME_TO_SEC(TIMEDIFF(DATE_FORMAT($field, '%Y-%m-%d %H:%i:%s'), '$now')) <= $valueInc";
                break;
            case Notification::DATE_OPERATOR_WILL_OCCUR_WITHIN:
                $conditions[] = "TIME_TO_SEC(TIMEDIFF(DATE_FORMAT($field, '%Y-%m-%d %H:%i:%s'), '$now')) > 0";
                $conditions[] = "TIME_TO_SEC(TIMEDIFF(DATE_FORMAT($field, '%Y-%m-%d %H:%i:%s'), '$now')) < $value";
                break;
        }
        return '('.implode(' AND ', $conditions).')';
    }
}
