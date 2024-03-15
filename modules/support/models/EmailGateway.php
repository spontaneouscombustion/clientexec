<?php

include_once 'modules/support/models/Ticket.php';
include_once 'modules/support/models/TicketGateway.php';

/**
 * Email Gateway for Support Tickets
 *
 * @category   Action
 * @package    Support
 * @author     Matt Grandy <matt@clientexec.com
 * @license    http://www.clientexec.com  ClientExec License Agreement
 * @link       http://www.clientexec.com
 */
class EmailGateway extends NE_Model
{


    /**
     * Function to parse a raw string e-mail into a suppor ticket, from either pop3, or a support forward (pipe)
     *
     * @param string $email Raw e-mail being parsed into a support ticket.
     * @param string $routingType Routing type being used, pop3 or support pipe
     *
     * @return void
     */
    public function parseEmail($email, $routingType)
    {
        require_once 'modules/support/models/EmailRoutingRule.php';
        require_once 'modules/support/models/TicketNotifications.php';
        require_once 'modules/support/models/EmailRoutingRuleGateway.php';

        $ep = new CE_EmailParser($email, $routingType);
        $ep->parse();

        if (trim($ep->getFrom()) == '') {
            CE_Lib::log(3, 'Discard incoming email: it doesn\'t have an appropriate From header');
            return false;
        }

        if (!$this->checkAutoresponder($ep->getFrom())) {
            CE_Lib::log(3, "Discard incoming email: we've entered an autoresponder loop");
            return false;
        }

        if (stripos(trim($ep->getFrom()), 'mailer-daemon') === 0) {
            CE_Lib::log(3, "Discarded incoming email from " . $ep->getFrom() . " - subject: \"" . $ep->getSubject() . "\"");
            return false;
        }

        $userGateway = new UserGateway();
        $mailGateway = new NE_MailGateway();
        $isStaffResponse = false;

        CE_Lib::log(4, 'Processing incoming email from '.$ep->getFrom().' with subject "'.$ep->getSubject().'"');

        $ticketNotifications = new TicketNotifications($this->user);

        $notUsingSupportEmail = false;
        $userId = $userGateway->getUserIdForEmail($ep->getFrom());
        if ($userId) {
            $user = new User($userId);
        } else {
            // see if it's a registered user using a non-support email
            $userId = $userGateway->getUserIdForEmail($ep->getFrom(), false);
            if ($userId) {
                $user = new User($userId);
                $notUsingSupportEmail = true;
            } else {
                $user = $this->getGuestUser($ep);
            }
        }

        $emailRoutingGateway = new EmailRoutingRuleGateway();
        $emailRoutingIterator = $emailRoutingGateway->getApplicableRules($user, $ep->getTo(), $routingType, false, $notUsingSupportEmail);

        if ($notUsingSupportEmail && $emailRoutingIterator->getNumItems() == 0) {
            // couldn't find matching rule with user type "Registered, but not using support E-mail"
            // so gonna try now as simple guest user
            $user = $this->getGuestUser($ep);
            $emailRoutingIterator = $emailRoutingGateway->getApplicableRules($user, $ep->getTo(), $routingType);
        }

        /*
        if (isset($args[2])) {
            $emailRoutingRule = $args[2];
            $ruleIsApplicable = false;
            while ($emailRoutingRule2 = $emailRoutingIterator->fetch()) {
                if ($emailRoutingRule->getId() == $emailRoutingRule2->getId()) {
                    $ruleIsApplicable = true;
                    break;
                }
            }
            if (!$ruleIsApplicable) {
                CE_Lib::log(4, 'No matching routing rule found. Dropping E-mail');
                return;
            }
        } else {*/
        $emailRoutingRule = $emailRoutingIterator->fetch();
        if (!$emailRoutingRule) {
            CE_Lib::log(4, 'No matching routing rule found. Dropping email');
            return;
        }

        $autoresponderTemplate = $emailRoutingRule->getAutoresponder();

        /**
        * Handle case where this is a response to an existing ticket
        */
        $regex = "/.*\[".$this->settings->get('Ticket Number Prefix').'-';
        $regex .= '(\d+)';
        $regex .= '(-tech)?\]/';
        if (preg_match($regex, $ep->getSubject(), $matchid)) {
            $cTicket = new Ticket($matchid[1]);
            CE_Lib::log(4, "It's a response to ticket " . $cTicket->getId());
            if (!$cTicket->existsInDB()) {
                CE_Lib::log(4, '... but ticket doesn\'t exist in DB so gotta create a new one');
                $ep->setSubject(substr($ep->getSubject(), strlen($matchid[0])));
            } else {
                $staffReplyUsingUnknownEmail = false;
                $customerReplyUsingUnknownEmail = false;
                if ($user->isAnonymous()) {
                    if ($this->_isStaffReply($ep)) {
                        $isStaffResponse = true;
                        if (!$this->settings->get('Allow Admins To Reply From Any E-mail')) {
                            CE_Lib::log(4, 'Reply sent from an unknown email, to a message sent to an admin: discarded');
                            return;
                        } else {
                            $staffReplyUsingUnknownEmail = true;
                        }
                    } else {
                        if (!$this->settings->get('Allow Customers To Reply From Any E-mail')) {
                            CE_Lib::log(4, 'Reply sent from an unknown email, to a reply sent to a customer: send hardcoded autoreply to sender');
                            $mailGateway->mailMessageEmail(
                                array('HTML' => null, 'plainText' => $this->user->lang('Your email is not registered in our system, so your message has been discarded. This is an automated reply.')),
                                $this->settings->get('Support E-mail'),
                                $this->settings->get('Company Name'),
                                $ep->getFrom(),
                                '',
                                $ep->getSubject(),
                                3,
                                false,
                                '',
                                '',
                                MAILGATEWAY_CONTENTTYPE_HTML
                            );
                            return;
                        } else {
                            $customerReplyUsingUnknownEmail = $this->user->lang('via %s', $ep->getFrom());
                        }
                    }
                }

                // avoid autoresponders keeping the ticket on an status by ignoring duplicated messages.
                if ($cTicket->isDuplicatedReply($user->getId(), utf8_encode($ep->getMessage()))) {
                    CE_Lib::log(4, 'Duplicate reply: discarded');
                    return;
                }

                // autoresponders are not sent when replying to a ticket, unless the rule disallows ticket opening
                // and there's a reply coming in anyways (rare case).
                // But we don't consider the rule in the special cases when settings allow staff and customer to use unknown emails
                if ($emailRoutingRule->isOpenTicket() || $staffReplyUsingUnknownEmail || $customerReplyUsingUnknownEmail) {
                    CE_Lib::log(4, 'Creating ticket log');
                    $this->_createNewTicketLog($user, $cTicket, $ep->getMessage(), $staffReplyUsingUnknownEmail, $customerReplyUsingUnknownEmail, $ep);

                    $args = array();
                    $args['message'] = $ep->getMessage();
                    $args['subject'] = $ep->getSubject();
                    $cTickets = new TicketGateway();
                    $cTickets->_forwardMessageToAlternateAddresses($args, $cTicket, $user, $emailRoutingRule);
                } elseif ($autoresponderTemplate->getId()) {
                    CE_Lib::log(4, 'Sending autoresponder');
                    $this->_sendAutoresponder($autoresponderTemplate, $ep);
                }

                $event = "Ticket-ReplyByCustomer";
                if ($isStaffResponse) {
                    $event = 'Ticket-ReplyByAdmin';
                }
                CE_Lib::trigger($event, $cTicket, [
                    "isadmin" => $isStaffResponse,
                    "user" => $user,
                    "ticketid" => $cTicket->getId(),
                    "message" => $ep->getMessage()
                ]);

                return;
            }
        }

        /**
        * Handle case where this is a new ticket
        */
        if ($user->isAdmin()) {
            CE_Lib::log(4, 'Incoming email corresponds to an admin and it doesn\'t look like a reply to ticket: DISCARDED');
            return;
        }

        CE_Lib::log(4, "Applying email routing rule \"".$emailRoutingRule->getName()."\" to incoming email");
        if ($filteringRule = $emailRoutingRule->filterOut($ep)) {
            CE_Lib::log(4, "Discarding email. It matches the filtering-out rule \"$filteringRule\"");
            return;
        }

        foreach ($emailRoutingRule->getCopyDestinataries() as $destinatary) {
            CE_Lib::log(4, "Forwarding incoming email to $destinatary");
            $ticketNotifications->forwardMessageToAlternateAddress(
                $destinatary,
                $ep->getFrom(),
                $this->toUtf8($ep->getMessage(), $ep->getCharset()),
                $ep->getSubject(),
                $ep->getAttachments()
            );
        }

        if ($emailRoutingRule->isOpenTicket()) {
            // If a ticket is to be opened for a guest customer, we need to add the customer to the db before
            if ($user->isGuest()) {
                $user->add();
            }

            CE_Lib::log(4, "Opening new ticket");
            $cTicket = $this->_createNewTicket($user, $emailRoutingRule, $ep->getMessage(), $ep);

            CE_Lib::trigger('Ticket-CreateByClient', $this, ['ticketId' => $cTicket->getId()]);

            $templateID = $autoresponderTemplate->getId();
            if ($templateID) {
                include_once 'modules/admin/models/Translations.php';
                $languages = CE_Lib::getEnabledLanguages();
                $translations = new Translations();
                $languageKey = ucfirst(strtolower($user->getRealLanguage()));
                CE_Lib::setI18n($languageKey);

                $strMessage = $autoresponderTemplate->getContents();
                $strMessageSubject = $autoresponderTemplate->getSubject();

                if (count($languages) > 1) {
                    $strMessageSubject = $translations->getValue(EMAIL_SUBJECT, $templateID, $languageKey, $strMessageSubject);
                    $strMessage = $translations->getValue(EMAIL_CONTENT, $templateID, $languageKey, $strMessage);
                }

                $ticketNotifications->sendAutoReply(
                    $user,
                    $cTicket,
                    $ep->getFrom(),
                    $ep->getMessage(),
                    array(
                        'subject' => $strMessageSubject,
                        'HTML'    => $strMessage
                    )
                );
            }

            return;
        }

        if ($autoresponderTemplate->getId()) {
            $this->_sendAutoresponder($autoresponderTemplate, $ep);
        }
        return;
    }

    /**
     * Determine whether to send the autoreply or not.  We do this to avoid autoresponder loops.
     * Works by checking to see if the last five E-mails that opened a new ticket came from the same
     * E-mail address and if they were all within a few minutes.
     *
     * @param string $from The E-mail address that the new ticket is coming from.
     *
     * @return bool if we should send an autoreply, false otherwise.
     */
    public function checkAutoresponder($from)
    {
        $sendAutoReply = false;
        $lastFive = $this->settings->get('E-mail Piping Last Five');
        if ($lastFive != "") {
            $lastFiveArr = @unserialize($lastFive);
            $count = 0;
            if (is_array($lastFiveArr)) {
                $count = count($lastFiveArr);
            }
            if ($count < 5) {
                $sendAutoReply = true;
                $lastFiveArr[$count]['address'] = $from;
                $lastFiveArr[$count]['time'] = time();
            } else {
                $newLastFiveArr = array();
                for ($i = 0; $i < $count; $i++) {
                    if ($lastFiveArr[$i]['address'] != $from || (time() - $lastFiveArr[$i]['time']) > 30) {
                        $sendAutoReply = true;
                    }
                    if ($i > 0) {
                        $newLastFiveArr[$i - 1]['address'] = $lastFiveArr[$i]['address'];
                        $newLastFiveArr[$i - 1]['time'] = $lastFiveArr[$i]['time'];
                    }
                }
                $newLastFiveArr[$count - 1]['address'] = $from;
                $newLastFiveArr[$count - 1]['time'] = time();
                $lastFiveArr = $newLastFiveArr;
            }
        } else {
            $sendAutoReply = true;
            $lastFiveArr = array();
            $lastFiveArr[0]['address'] = $from;
            $lastFiveArr[0]['time'] = time();
        }
        $this->settings->updateValue('E-mail Piping Last Five', serialize($lastFiveArr));

        return $sendAutoReply;
    }


    /**
     * Wrapper function to send out an auto response
     *
     * @param object $autoresponderTemplate AutoResponder template being sent out
     * @param object $ep NE_EmailParser object of the parsed e-mail
     *
     * @return void
     */
    function _sendAutoresponder($autoresponderTemplate, $ep)
    {
        $mailGateway = new NE_MailGateway();
        $ticketNotifications = new TicketNotifications($this->user);

        $templateID = $autoresponderTemplate->getId();
        $strMessage = $autoresponderTemplate->getContents();
        $strMessageSubject = $autoresponderTemplate->getSubject();

        //let's create the user creating this ticket
        $userGateway = new UserGateway($this->user);
        $userId = $userGateway->searchUserByEmail($ep->getFrom(), true, false);
        if ($userId) {
            $ticketUser = new User($userId);

            include_once 'modules/admin/models/Translations.php';
            $languages = CE_Lib::getEnabledLanguages();
            $translations = new Translations();
            $languageKey = ucfirst(strtolower($ticketUser->getRealLanguage()));
            CE_Lib::setI18n($languageKey);

            if (count($languages) > 1) {
                $strMessageSubject = $translations->getValue(EMAIL_SUBJECT, $templateID, $languageKey, $strMessageSubject);
                $strMessage = $translations->getValue(EMAIL_CONTENT, $templateID, $languageKey, $strMessage);
            }
        }

        $mailGateway->mailMessageEmail(
            $ticketNotifications->replaceBasicTags($strMessage),
            $this->settings->get('Support E-mail'),
            $this->settings->get('Company Name'),
            $ep->getFrom(),
            '',
            $strMessageSubject,
            3,
            false,
            '',
            '',
            MAILGATEWAY_CONTENTTYPE_HTML
        );
    }

    /**
     * Wrapper function to process any attachments for a support ticket
     *
     * @param int $userId User id submitting the ticket
     * @param object $ccTicket Ticket object of ticket being submitted
     * @param array $filename Array of attachment file names, returned by reference
     * @param array $filename Array of file attachments, returned by reference
     * @param string $message Full support ticket message
     * @param object $ep NE_EmailParser object of the parsed e-mail
     *
     * @return string Full support ticket message
     */
    function _processAttachments($userId, &$ccTicket, &$filename, &$fileattach, $message, $ep, $logId)
    {
        $supportDir = dirname(__FILE__) . '/../../../uploads/support/';
        $validExtensions = explode(',', str_replace(' ', '', strtolower($this->settings->get('Allowed File Extensions'))));

        if ($ep->getNumAttachments() > 0 && is_writable($supportDir) && $this->settings->get('Allow Customer File Uploads')) {
            for ($i = 0; $i < $ep->getNumAttachments(); $i++) {
                $attachment = $ep->getAttachment($i);
                $filekey = $ccTicket->generateFileKey();
                if (($pos = strrpos($attachment['name'], '.')) !== false) {
                    $len = strlen($attachment['name']) - ($pos+1);
                    $ext = mb_substr($attachment['name'], -$len);
                    if (in_array(strtolower($ext), $validExtensions) || in_array('*', $validExtensions)) {
                        $fp = @fopen($supportDir.$filekey, "w");
                        fwrite($fp, $attachment['string']);
                        fclose($fp);
                        chmod($supportDir.$filekey, 0666);
                        $ccTicket->addFile($attachment['name'], $filekey, $userId, $logId);
                        $fileattach[] = $attachment['string'];
                        $filename[] = $attachment['name'];
                    }
                }
            }
        }
        return $message;
    }

    /**
     * Wrapper function to actually create the support ticket
     *
     * @param object $user User object of the user submitting the ticket
     * @param object $emailRoutingRule Routing rule object
     * @param string $message Full support ticket message
     * @param object $ep NE_EmailParser object of the parsed e-mail
     *
     * @return void
     */
    function _createNewTicket(&$user, &$emailRoutingRule, $message, $ep)
    {
        $tTimeStamp = date('Y-m-d H-i-s');
        $cTicket = new Ticket();
        $cTickets = new TicketGateway();
        if ($cTickets->GetTicketCount() == 0) {
             $id = $this->settings->get('Support Ticket Start Number');
             $cTicket->setForcedId($id);
        }

        $cTicket->setUser($user);
        $cTicket->setSubject($ep->getSubject());
        $cTicket->SetDateSubmitted($tTimeStamp);
        $cTicket->SetLastLogDateTime($tTimeStamp);
        $cTicket->setMethod(2);
        $cTicket->SetPriority($emailRoutingRule->getTargetPriority());

        // if the email was sent to more than one recipient, we can tell which one to pick
        // so we leave this empty. CE will then fall back to the general support email when needed.
        if ($this->getNumEmails($ep->getTo()) == 1) {
            $cTicket->SetSupportEmail($ep->getTo());
        }

        $cTicket->SetStatus(TICKET_STATUS_WAITINGONTECH);

        $cTicket->SetMessageType($emailRoutingRule->getTargetTypeId());

        if ($emailRoutingRule->getTargetDept() == EMAILROUTINGRULE_DEFAULTASSIGNEE) {
            $ticketType = new TicketType($emailRoutingRule->getTargetTypeId());
            if ($targetDeptId = $ticketType->getTargetDept()) {
                require_once 'modules/support/models/Department.php';
                $dep = new Department($targetDeptId);
                $staff = null;
                if ($targetStaffId = $ticketType->getTargetStaff()) {
                    $staff = new User($targetStaffId);
                }
                $cTicket->assign($dep, $staff);
            }
        } elseif ($emailRoutingRule->getTargetDept() != 0) {
            require_once 'modules/support/models/Department.php';
            $dep = new Department($emailRoutingRule->getTargetDept());
            $staff = null;
            if ($targetStaffId = $emailRoutingRule->getTargetStaff()) {
                $staff = new User($targetStaffId);
            }
            $cTicket->assign($dep, $staff);
        }

        $cTicket->save();

        $supportLog = Ticket_EventLog::newInstance(
            false,
            $user->getId(),
            $cTicket->getId(),
            TICKET_EVENTLOG_CREATED,
            $user->getId()  // $this->user is an empty user object in this context
        );
        $supportLog->save();

        $filename = array();
        $fileattach = array();

        // I need a log id
        $ticketLog = new TicketLog();
        $ticketLog->dirty = true;
        $ticketLog->save();

        $message = $this->_processAttachments($user->getId(), $cTicket, $filename, $fileattach, $message, $ep, $ticketLog->getId());

        $message = $this->toUtf8($message, $ep->getCharset());
        $cTicket->addInitialLog($message, $tTimeStamp, $user, true, false, $ticketLog, $ep->getFrom());
        $attachments = false;
        if ($fileattach) {
            $attachments = array(
                'number'    => count($fileattach),
                'name'      => $filename,
                'string'    => $fileattach,
            );
        }
        $cTicket->notifyAssignation($user, $attachments);

        return $cTicket;
    }

    /**
     * Wrapper function to actually create the support ticket log
     *
     * @param object $user User object of the user submitting the ticket
     * @param object cTicket Support ticket object
     * @param string $message Full support ticket message
     * @param bool $staffReplyUsingUnknownEmail True if staff is replying from an unknown email address.
     * @param bool $customerReplyUsingUnknownEmail True if customer is replying from an unknown email address.
     * @param object $ep NE_EmailParser object of the parsed e-mail
     *
     * @return void
     */
    function _createNewTicketLog(&$user, &$cTicket, $message, $staffReplyUsingUnknownEmail, $customerReplyUsingUnknownEmail, $ep)
    {
        $ticketNotifications = new TicketNotifications($this->user);

        $cTicket->SetStatus($user->isAdmin()? TICKET_STATUS_WAITINGONCUSTOMER : TICKET_STATUS_WAITINGONTECH);
        $timeStamp = date('Y-m-d H-i-s');
        $cTicket->SetLastLogDateTime($timeStamp);
        $cTicket->save();
        $filename = array();
        $fileattach = array();

        // need an id for the ticketLog now
        $cTicketLog = new TicketLog();
        $cTicketLog->dirty = true;
        $cTicketLog->save();

        $message = $this->_processAttachments($user->getId(), $cTicket, $filename, $fileattach, $message, $ep, $cTicketLog->getId());
        $cTicketLog->setUserId($user->getId());
        $cTicketLog->setTroubleTicketID($cTicket->getId());

        $message = $this->toUtf8($message, $ep->getCharset());
        $cTicketLog->setMessage($message);
        $cTicketLog->setMyDateTime($timeStamp);
        $cTicketLog->setLogAction(0);
        $cTicketLog->setIsExternalEmail(1);
        $cTicketLog->setEmail($ep->getFrom());
        if ($customerReplyUsingUnknownEmail) {
            $cTicketLog->setDeletedName($customerReplyUsingUnknownEmail);
        }
        $cTicketLog->save();

        if (!$staffReplyUsingUnknownEmail && ($user->getGroup()->isCustomersMainGroup() || $user->isGuest())) {
            $ticketNotifications->notifyAssigneeForTicketReply($user, $cTicket, $message, $fileattach, $filename);
        } else {
            $tClient = new User($cTicket->GetUserID());
            $ticketNotifications->notifyCustomerForTicketReply($tClient, $cTicket, $message, $fileattach, $filename);
        }
    }

    /**
     * Check if the e-mail is a staff response
     *
     * @param object $ep NE_EmailParser object of the parsed e-mail
     *
     * @return bool
     */
    function _isStaffReply($ep)
    {
        $regex = "/.*\[".$this->settings->get('Ticket Number Prefix').'-';
        $regex .= '(\d+)';
        $regex .= '-tech\]/';
        return preg_match($regex, $ep->getSubject());
    }

    /**
     * Function to get the amount of addresses in the TO field
     *
     * @param string $to Comma seperated list of emails.
     *
     * @return int Number of email addresses the ticket was addressed to
     */
    private function getNumEmails($to)
    {
        return count(explode(',', $to));
    }

    private function toUtf8($message, $origCharset)
    {
        if ($origCharset && stripos($origCharset, 'utf-8') === false) {
            // suppress error if charset is not recognized
            $message = @mb_convert_encoding($message, 'UTF-8', $origCharset);
        } elseif (!$origCharset) {
            // assume latin1
            $message = utf8_encode($message);
        }

        return $message;
    }

    /**
     * Wrapper function to create a guest user
     *
     * @param object $ep NE_EmailParser object of the parsed e-mail
     *
     * @return object Guest User Object
     */
    public function getGuestUser($ep)
    {
        $user = new User();
        $user->setEmail($ep->getFrom());
        $user->setGroupId(ROLE_GUEST);
        $user->setDateCreated(date('Y-m-d'));
        $user->setLastName($ep->getFromLabel());
        return $user;
    }
}
