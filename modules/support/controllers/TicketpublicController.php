<?php
require_once 'modules/support/models/TicketGateway.php';
require_once 'modules/knowledgebase/models/KB_CategoryGateway.php';
require_once 'modules/admin/models/StatusAliasGateway.php';
require_once 'modules/clients/models/ObjectCustomFields.php';

/**
 * Support Module's Ticket Controller
 *
 * @category   Action
 * @package    Support
 * @author     Alberto Vasquez <alberto@clientexec.com>
 * @license    http://www.clientexec.com  ClientExec License Agreement
 * @link       http://www.clientexec.com
 */
class Support_TicketpublicController extends CE_Controller_Action
{

    var $moduleName = "support";


    protected function toparticlesAction()
    {
        //get most popular articles
        $catGateway = new KB_CategoryGateway($this->user);
        $typeid = $this->getParam('typeid', FILTER_SANITIZE_NUMBER_INT);

        $articleGateway = new KB_ArticleGateway();
        $articleIt = $articleGateway->getArticlesRelatedToTicketType($typeid, $this->settings->get('Number of Knowledgebase Articles to Show as Top Questions'));
        $articles = array();
        while ($article = $articleIt->fetch()) {
            if (!$article->isDraft() && $article->getAccess() == KB_ARTICLE_ACCESS_PUBLIC) {
                $aArticle = array();
                $aArticle['title'] = $article->getTitle();
                $aArticle['id'] = $article->getId();

                $articles[] = $aArticle;
            }
        }

        $this->send(array("articles"=>$articles));
    }

    /**
     * save ticket
     * @return [type] [description]
     */
    protected function saveticketAction()
    {
        require_once 'library/CE/CurlPostCE.php';

        $args = array();
        $args['mode'] = $this->getParam('mode', FILTER_SANITIZE_STRING, 'public', false);

        $captchaPlugin = $this->settings->get('Enabled Captcha Plugin');
        if ($args['mode'] != 'supportwidget' && (!$this->user->isRegistered() && $this->settings->get('Show Captcha on Submit Ticket Page') == 1 && $captchaPlugin != '' && $captchaPlugin != 'disabled')) {
            $pluginGateway = new PluginGateway($this->user);

            $plugin = $pluginGateway->getPluginByName('captcha', $captchaPlugin);
            if (!$plugin->verify($_REQUEST)) {
                CE_Lib::addErrorMessage($this->user->lang('Failed Captcha'));
                CE_Lib::redirectPage('index.php?fuse=support&controller=ticket&view=submitticket', '', $_POST);
            }
        }

        $args['message'] = $this->getParam('message');
        $args['messagetype'] = $this->getParam('ticket-type', FILTER_SANITIZE_NUMBER_INT);
        $args['subject'] = $this->getParam('subject');
        $args['userid'] = $this->getParam('userid', FILTER_SANITIZE_NUMBER_INT);
        $args['guestname'] = $this->getParam('guestName', FILTER_SANITIZE_STRING, "");
        $args['guestemail'] = $this->getParam('guestEmail', FILTER_SANITIZE_STRING, "");
        $args['product_id'] = $this->getParam('product_id', FILTER_SANITIZE_NUMBER_INT, 0);

        $args['inNameOfUser'] =  true;

        $tg = new TicketGateway($this->user);

        try {
            $ticket = $tg->save_new_ticket($args);
        } catch (CE_Exception $e) {
            if ($args['mode'] == 'supportwidget') {
                $this->error = true;
                $this->message = $e->getMessage();
                $this->send();
                return;
            }
        }

        CE_Lib::trigger(
            'Ticket-ReplyByCustomer',
            $ticket,
            [
                'isadmin' => false,
                'user'=> $this->customer,
                'ticketid' => $ticket->getId(),
                'message' => $args['message']
            ]
        );

        if ($this->user->isGuest()) {
            $url = 'index.php';
            CE_Lib::redirectPage(
                $url,
                $this->user->lang('Your ticket has been submitted.  A support staff member will reach you shortly.')
            );
        } else {
            $url = 'index.php?fuse=support&controller=ticket&view=ticket&id=' . $ticket->id;
            CE_Lib::redirectPage(
                $url,
                $this->user->lang('Your ticket has been submitted.  A support staff member will reach you shortly.')
            );
        }
    }

    /**
     * display all tickets opened by customer
     * @return [type] [description]
     */
    protected function allticketsAction()
    {
        if ($this->user->isGuest()) {
            CE_Lib::redirectPermissionDenied($this->user->lang('You do not have access to view that page'));
        }

        $this->checkPermissions('support_view');

        $this->title = $this->user->lang('My Tickets');
        $start = $this->getParam('start', FILTER_SANITIZE_NUMBER_INT, 0);
        $statusfilter = $this->getParam('filter', FILTER_SANITIZE_STRING, "all");
        $items = false;

        switch ($statusfilter) {
            case -1:
                $statusfilter = 'closed';
                break;
            case 'all':
                break;
            default:
                $statusfilter = 'open';
        }

        $customCols = array();
        $this->view->customCols = array();
        foreach (ObjectCustomFields::getCustomFieldsByType('tickettypes') as $obj) {
            if (!$obj['isAdminOnly'] && $obj['isChangeable'] && $obj['showingridportal']) {
                $customCols[$obj['id']] = $obj['text'];
            }
        }

        $ticketGateway = new TicketGateway($this->user);
        list($tickets_iterator) = $ticketGateway->GetTickets($this->user, $items, $start, 'datesubmitted', 'DESC', $statusfilter, $this->user->getId());
        $this->view->tickets = array();
        while ($ticket = $tickets_iterator->fetch()) {
            // check if all repsonses are private, and do not show this if they are.
            if ($ticketGateway->getCountOfNonPrivateMsgsForTicketId($ticket->id) == 0) {
                 continue;
            }

            $a_ticket = $ticket->toArray();
            $a_ticket['ticketStatus'] = $this->user->lang($ticket->getStatus());
            $a_ticket['ticketStatusClass'] = $ticket->getStatusClass();
            //$a_ticket['datesubmitted'] = date('F j, Y', $a_ticket['datesubmitted']);
            $phpdate = strtotime($a_ticket['datesubmitted']);
            $mysqldate = date('Y-m-d H:i:s', $phpdate);
            $a_ticket['datesubmitted'] = date($this->settings->get('Date Format') . ' h:i a', strtotime($mysqldate));

            $a_ticket['customfields'] = array();

            $customFieldsIds = $ticket->getCustomFieldsIds();
            foreach ($customCols as $id => $label) {
                if ($customFieldsIds) {
                    $ids = explode(chr(29), $customFieldsIds);
                    $values = explode(chr(29), $ticket->getCustomFieldsValues());
                    if (($key = array_search($id, $ids)) !== false) {
                        if (!isset($this->view->customCols[$id])) {
                            $this->view->customCols[$id] = $label;
                        }
                        $a_ticket['customfields'][$id] = $values[$key];
                    } elseif (isset($this->view->customCols[$id])) {
                        $a_ticket['customfields'][$id] = '';
                    }
                } elseif (isset($this->view->customCols[$id])) {
                    $a_ticket['customfields'][$id] = '';
                }
            }

            $this->view->tickets[] = $a_ticket;
        }

        $this->view->filter = $statusfilter;
    }

    /**
     * show ticket
     * @return [type] [description]
     */
    protected function ticketAction()
    {
        $this->checkPermissions();
        $this->title = $this->user->lang('View Ticket');

        $ticket_id = $this->getParam('id', FILTER_SANITIZE_NUMBER_INT);
        $ticket = new Ticket($ticket_id);
        $ticket_gateway = new TicketGateway($this->user);

        if (!isset($_REQUEST['id']) || !$ticket->existsInDB()) {
            CE_Lib::addErrorMessage($this->user->lang('You are trying to access a ticket that does not exist.'));
            CE_Lib::redirectPage("index.php?fuse=support&controller=ticket&view=alltickets");
        }

        $ticket_customer = $ticket->getUser();
        if ($this->user->getId() != $ticket_customer->getId()) {
            CE_Lib::addErrorMessage($this->user->lang('You are trying to access a ticket that does not exist.'));
            CE_Lib::redirectPage("index.php?fuse=support&controller=ticket&view=alltickets");
        }

        // check if all repsonses are private
        if ($ticket_gateway->getCountOfNonPrivateMsgsForTicketId($ticket_id) == 0) {
            CE_Lib::addErrorMessage($this->user->lang('You are trying to access a ticket that does not exist.'));
            CE_Lib::redirectPage("index.php?fuse=support&controller=ticket&view=alltickets");
        }

        $this->view->maxfilesize = 0;
        if (($this->user->isAdmin() || $this->settings->get('Allow Customer File Uploads')) && is_writable('uploads/support')) {
            $this->view->maxfilesize = ini_get('upload_max_filesize');
            $allowedExt = str_replace(' ', '', strtolower($this->settings->get('Allowed File Extensions')));
            $this->view->extns = $allowedExt;
            $this->view->extnsmessage = '';
            if ($allowedExt != '' && !in_array('*', explode(',', $allowedExt))) {
                $this->view->extnsmessage = str_replace(",", ", ", $allowedExt);
            }
        }

        $ticket_data = $ticket_gateway->getTicket($ticket_id, true);

        if ($this->settings->get('Days To Allow Tickets To Be Reopened') == "" || $this->settings->get('Days To Allow Tickets To Be Reopened') > floor((time()-$ticket->GetLastLogDateTimeTimestamp())/(24 * 60 * 60))) {
            $this->view->ticket_can_reopen = true;
        } else {
            $this->view->ticket_can_reopen = false;
        }

        $ticketType = new TicketType($ticket_data['metadata']['ticket_type']);
        $this->view->canCloseTicket = $this->user->hasPermission('support_close_tickets') && $ticketType->getAllowClose() == 1;


        $this->view->closeTicketURL = $this->view->urlCsrf([
            'fuse' => 'support',
            'controller' => 'ticket',
            'action' => 'setstatus',
            'status' => -1,
            'id' => $ticket_id
        ]);

        $this->view->assign($ticket_data);

        $customFields = [];
        $ticketType = new TicketType($ticket->getMessageType());

        $objectCustomFields = new ObjectCustomFields(
            CUSTOM_FIELDS_FOR_TICKETS,
            $ticket,
            false,
            array('fieldOrder', 'ASC')
        );

        while ($row = $objectCustomFields->fetch()) {
            if ($row['isadminonly'] == 1 && !$this->user->isAdmin()) {
                continue;
            }
            if ($row['dropdownoptions'] != '') {
                $selectOptions = [];
                $options = explode(",", trim($row['dropdownoptions']));
                foreach ($options as $option) {
                    if (preg_match('/(.*)(?<!\\\)\((.*)(?<!\\\)\)/', $option, $matches)) {
                        $value = $matches[2];
                        $label = $matches[1];
                    } else {
                        $value = $label = $option;
                    }

                    $label = str_replace(array('\\(', '\\)'), array('(', ')'), $label);
                    $selectOptions[] = array($value,$label);
                }
                $row['dropdownoptions'] = $selectOptions;
            }


            if ($row['isEncrypted']) {
                $row['value'] = Clientexec::decryptString($row['value']);
            }
            $row['value'] = htmlspecialchars_decode($row['value'], ENT_QUOTES);
            $customFields[] = $row;
        }
        $this->view->customFields = $customFields;
    }

    /**
     * gets an attachment for a ticket
     * @return binary
     */
    protected function getattachmentAction()
    {
        $file_id = $this->getParam('file_id', FILTER_SANITIZE_NUMBER_INT);
        $ticketGateway = new TicketGateway($this->user);
        $ticketGateway->render_attachment($file_id, $this->user->getId());
    }

    public function deleteattachmentAction()
    {
        $this->disableLayout();

        $fileId = $this->getParam('file_id', FILTER_SANITIZE_NUMBER_INT, 0);
        $ticketId = $this->getParam('ticketid', FILTER_SANITIZE_NUMBER_INT, 0);

        $ticket = new Ticket($ticketId);
        // ensure ticket belongs to active user
        if ($this->user->getId() != $ticket->getUserId()) {
            CE_Lib::redirectPermissionDenied($this->user->lang('You do not have permission to delete this file attachment'));
        }

        $message = $this->user->lang("You do not have permission to delete this file attachment");
        if ($fileId != 0 && $ticketId != 0) {
            $query = "SELECT `filename`, `filekey` FROM `troubleticket_files` WHERE `id`=? AND `ticketid`=?";
            $result = $this->db->query($query, $fileId, $ticketId);
            $row = $result->fetch();
            @unlink('uploads/support/'.$row['filekey']);
            $query = "DELETE FROM `troubleticket_files` WHERE `id`=?";
            $this->db->query($query, $fileId);

            $ticketGateway = new TicketGateway($this->user);
            $ticketGateway->addNonMsgLog(
                $ticket,
                TicketLog::TYPE_DELETED_ATTACHMENT,
                $row['filename']
            );

            $message = $this->user->lang("File removed successfully.");
        }
        CE_Lib::redirectPage('index.php?fuse=support&controller=ticket&view=ticket&id=' . $ticketId, $message);
    }

    /**
     * [reopenAction description]
     * @return [type] [description]
     */
    public function setstatusAction()
    {

        $id = $this->getParam('id', FILTER_SANITIZE_NUMBER_INT);
        $newstatus = $this->getParam('status', FILTER_SANITIZE_NUMBER_INT);

        $ticket = new Ticket($id);
        $ticket_customer = $ticket->getUser();
        //check if I own ticket
        if ($this->user->getId() != $ticket_customer->getId()) {
            CE_Lib::addErrorMessage($this->user->lang('Ticket was not found'));
            CE_Lib::redirectPage("index.php?fuse=support&controller=ticket&view=alltickets");
        }

        $ticketGateway = new TicketGateway($this->user);
        $newname = $ticketGateway->change_ticket_status($id, $newstatus);
        CE_Lib::addSuccessMessage($this->user->lang("Your ticket was successfully updated"));
        CE_Lib::redirectPage("index.php?fuse=support&controller=ticket&view=ticket&id=".$id);
    }

    /**
     * Save the new log and then forward back to ticket
     * @return redirect
     */
    public function savenewlogAction()
    {
        $id = $this->getParam('id', FILTER_SANITIZE_NUMBER_INT);
        $log_message = $this->getParam('message');
        $ticketstatus = $this->getParam('ticketstatus', FILTER_SANITIZE_STRING, "");

        $ticketGateway = new TicketGateway($this->user);
        $ticket = new Ticket($id);
        $ticket_customer = $ticket->getUser();
        //check if I own ticket
        if ($this->user->getId() != $ticket_customer->getId()) {
            CE_Lib::addErrorMessage($this->user->lang('Trying to perform action on invalid ticket'));
            CE_Lib::redirectPage("index.php?fuse=support&controller=ticket&view=alltickets");
        }

        include_once 'modules/support/models/TicketLog.php';
        include_once 'modules/support/models/TicketNotifications.php';

        // don't change ticket status when a staff-only message is posted
        // or if we don't have ticket status set
        if ($ticketstatus == "") {
            $ticketstatus = $ticket->getStatusId();
        }
        $changedStatus = $ticket->getStatusId() != $ticketstatus;
        $ticket->SetStatus($ticketstatus);
        $ticket->SetLastLogDateTime(date('Y-m-d H-i-s'));
        $ticket->save();

        /* save log */
        $cTicketLog = new TicketLog();
        $cTicketLog->setTroubleTicketId($id);
        $cTicketLog->setUserId($this->user->getId());
        $cTicketLog->SetMyDateTime(date('Y-m-d H-i-s'));
        $cTicketLog->setPrivate(0);
        //save so we can get the id
        $cTicketLog->save();

        /* save the attachment files */
        $attachment_array = $ticketGateway->add_file_attachments_to_entry($cTicketLog->getId(), $ticket);

        //update message
        $cTicketLog->setMessage($attachment_array['log_message'].$log_message);
        $cTicketLog->save();

        if ($attachment_array['arrFilename']) {
            $ticketGateway->addNonMsgLog(
                $ticket,
                TicketLog::TYPE_ADDED_ATTACHMENT,
                $attachment_array['arrFilename'][0]
            );
        }

        $ticketNotifications = new TicketNotifications($this->user);
        $ticketDept = $ticket->getAssignedToDept();

        // I think customers are not allowed to close tickets, but let's leave this for FC (future compatibility ;-)
        $sendclosedNotifyMember = $ticketDept->getNotifyMembers('closed');
        $isClosed = StatusAliasGateway::getInstance($this->user)->isTicketClosed($ticket->getStatusId());
        if ($changedStatus && $isClosed && $ticketDept->getId() && !empty($sendclosedNotifyMember)) {
            $ticketNotifications->sendTranscription($ticket, $this->user, $ticketDept, $this->user->lang('Ticket #%s has been closed', $ticket->getId()), '');
        }

        $file = '';
        $mailStatus = true;
        $arrFiles = array();
        foreach ($attachment_array['arrFilekey'] as $key => $filekey) {
            $file = '';
            if ($filekey != "") {
                if ($fp = @fopen('uploads/support/' . $filekey, 'rb')) {
                    while (!feof($fp)) {
                        $file .= fread($fp, 4096);
                    }
                    fclose($fp);
                }
                $arrFiles[] = $file;
            }
        }

        $mailStatus = $ticketNotifications->notifyAssigneeForTicketReply($this->user, $ticket, $log_message, $arrFiles, $attachment_array['arrFilename']);

        include_once 'modules/support/models/EmailRoutingRuleGateway.php';
        $emailRoutingGateway = new EmailRoutingRuleGateway();
        $emailRoutingIterator = $emailRoutingGateway->getApplicableRules($this->user, '', EMAILROUTINGRULE_PUBLICSECTION);
        $emailRoutingRule = $emailRoutingIterator->fetch();
        $args = array();
        $args['message'] = $log_message;
        $args['subject'] = $ticket->getSubject();
        $cTickets = new TicketGateway($this->user);
        if ($emailRoutingRule) {
            $cTickets->_forwardMessageToAlternateAddresses($args, null, $this->user, $emailRoutingRule);
        }

        if ($changedStatus) {
            $ticketGateway->addNonMsgLog(
                $ticket,
                TicketLog::TYPE_STATUS,
                $ticketstatus
            );

            $supportLog = Ticket_EventLog::newInstance(false, $this->user->getId(), $cTicketLog->getTroubleTicketId(), TICKET_EVENTLOG_ADDEDMESSAGE, $this->user->getId(), $ticketstatus);
            $supportLog->save();
        } else {
            $supportLog = Ticket_EventLog::newInstance(false, $this->user->getId(), $cTicketLog->getTroubleTicketId(), TICKET_EVENTLOG_ADDEDMESSAGE, $this->user->getId());
            $supportLog->save();
        }

        if (!is_a($mailStatus, 'CE_Error')) {
        } elseif ($mailStatus->getErrCode() == 4096) {
            CE_Lib::addErrorMessage($attachment_array['filemessage'] . " " . $mailStatus->getMessage());
        } else {
            CE_Lib::addErrorMessage($attachment_array['filemessage'] . " " . $mailStatus->getMessage());
        }

        CE_Lib::redirectPage("index.php?fuse=support&controller=ticket&view=ticket&id=".$id);
    }

    /**
     * display form for submitting new ticket
     * @return [type] [description]
     */
    protected function submitticketAction()
    {
        $this->title = $this->user->lang('Submit New Ticket');

        $this->view->loggedin = true;
        if ($this->user->getId() == 0) {
            $this->view->loggedin = false;
        }

        //let's see if we have permission to show form
        $this->view->hasPermission = true;
        if (!$this->user->isAnonymous() && is_object($this->customer)) {
            include_once 'modules/clients/models/UserPackageGateway.php';
            $DomainGateway = new UserPackageGateway($this->user);
            $this->view->domainDropDown = $DomainGateway->returnDomainDropDown($this->customer->getId(), $this->view);
        } elseif (!$this->user->hasPermission('support_submit_ticket')) {
            $this->view->domainDropDown = array();
            $this->view->hasPermission = false;
        }

        //Get all Message Types
        $ticketTypeGateway = new TicketTypeGateway();
        $ticketTypeIterator = $ticketTypeGateway->getTicketTypes();

        $this->view->tickettypes = [];
        $tempType['value'] = 0;
        $tempType['name'] = $this->user->lang("Select below ...");
        $tempType['selected'] = '';
        $this->view->tickettypes[] = $tempType;

        while ($ticketType = $ticketTypeIterator->fetch()) {
            $tempType = [];
            if ($this->user->isAnonymous() && !$ticketType->isEnabledPublic()
              || ($ticketType->getSystemId() > 0)) {
                continue;
            }

            $tempType['value'] = $ticketType->getId();
            $tempType['name'] = $this->user->lang($ticketType->getName());
            if (isset($_REQUEST['messagetype']) && $_REQUEST['messagetype'] == $ticketType->getId()) {
                $tempType['selected'] = "selected='selected'";
            } elseif (isset($_REQUEST['tickettype']) && strtolower($_REQUEST['tickettype']) == strtolower($ticketType->getName())) {
                $tempType['selected'] = "selected='selected'";
            } else {
                $tempType['selected'] = "";
            }
            $this->view->tickettypes[] = $tempType;
        }

        //should we include the articles that match subject
        $this->view->subjectOnkeyup = "";
        $artGateway = new KB_ArticleGateway();
        if (($artGateway->thereAreMemberArticles())||$artGateway->thereArePublicArticles()) {
            $this->view->subjectOnkeyup = "clientexec.loadKBArticles(event);";
        }

        $this->view->message = $this->getParam('message', FILTER_SANITIZE_STRING, "");
        $this->view->subject = $this->getParam('subject', FILTER_SANITIZE_STRING, "");
        $this->view->guestname = $this->getParam('guestName', FILTER_SANITIZE_STRING, "");
        $this->view->guestemail = $this->getParam('guestEmail', FILTER_SANITIZE_STRING, "");

        $this->view->maxfilesize = 0;
        if (($this->user->isAdmin() || $this->settings->get('Allow Customer File Uploads')) && is_writable('uploads/support')) {
            $this->view->maxfilesize = ini_get('upload_max_filesize');
            $allowedExt = str_replace(' ', '', strtolower($this->settings->get('Allowed File Extensions')));
            $this->view->extns = $allowedExt;
            $this->view->extnsmessage = '';
            if ($allowedExt != '' && !in_array('*', explode(',', $allowedExt))) {
                $this->view->extnsmessage = str_replace(",", ", ", $allowedExt);
            }
        }

        $pluginGateway = new PluginGateway($this->user);
        $this->view->showCaptcha = false;
        $captchaPlugin = $this->settings->get('Enabled Captcha Plugin');
        if ($this->settings->get('Show Captcha on Submit Ticket Page') == 1 && $captchaPlugin != '') {
            $this->view->showCaptcha = true;
            $plugin = $pluginGateway->getPluginByName('captcha', $captchaPlugin);
            $plugin->setTemplate($this->view);
            $this->view->captchaHtml = $plugin->view();
        }

        $topSnapins = $pluginGateway->getMappingsForView('client_top_submitticket');
        $this->view->top_snapin_html = "";
        if (count($topSnapins) > 0) {
            foreach ($topSnapins as $key => $value) {
                $snapinName = $value['plugin'];
                $plugin = $pluginGateway->getSnapinContent($snapinName, $this->view);
                $view = $pluginGateway->getHookByKey($snapinName, 'client_top_submitticket', $value['key']);

                $matched_mapping = array();
                $matched_mapping['type'] = "hooks";
                $matched_mapping['loc'] = "client_top_submitticket";
                $matched_mapping['tpl'] = $view['tpl'];

                $plugin->setMatching($matched_mapping);
                $loadassests = false;
                $output = $plugin->mapped_view($loadassests);
                $this->view->top_snapin_html .= $output;
            }
        }
    }

    /**
     * get custom fields for a ticket
     * @return json
     */
    protected function getticketcustomfieldsAction()
    {
        $ticketId = $this->getParam('ticketId', FILTER_SANITIZE_NUMBER_INT, 0);
        $ticket = new Ticket($ticketId);
        if (!$ticket->existsInDB()) {
            return $this->send(array(
            'error' => true,
            'message' => $this->user->lang('Invalid ticket')
            ));
        }

        if ($this->user->getId() != $ticket->getUserId()) {
            $this->error = true;
            $this->message = $this->user->lang('You can not view the custom fields for this ticket.');
            $this->send();
            return;
        }

        $ticketTypeId = $ticket->getMessageType();

        if ($ticketId > 0) {
            include_once 'modules/support/models/TicketType.php';
            $ticketType = new TicketType($ticketTypeId);
            if (!$ticketType->existsInDB()) {
                $this->error = true;
                $this->message = $this->user->lang('Invalid ticket type');
                return $this->send();
            }
            $ticket->setMessageType($ticketType);
        } else {
            return $this->send(array(
            'count' => 0,
            'fields' => array()
            ));
        }

        $customFields = new ObjectCustomFields(CUSTOM_FIELDS_FOR_TICKETS, $ticket, false, array('fieldOrder', 'ASC'));
        $data = array();
        while ($row = $customFields->fetch()) {
            if ($row['isadminonly'] == 1 && !$this->user->isAdmin()) {
                continue;
            }

            if ($row['isEncrypted']) {
                $row['value'] = Clientexec::decryptString($row['value']);
            }
            ObjectCustomFields::parseFieldToArray($row, $this->user, $this->settings);
            $row['isrequired'] = ($row['isrequired'] === "0") ? false : true;
            $row['value'] = htmlspecialchars_decode($row['value'], ENT_QUOTES);
            $row['type'] = $row['fieldtype'];
            $row['isreadonly'] = $row['isadminonly'] == 2;
            $row["ischangeable"] = true;
            $data[] = $row;
        }

        $this->send(array(
        'count' => count($data),
        'fields' => $data,
        ));
    }

    /**
     * Saves ticket custom fields
     * @return void
     */
    protected function savecustomfieldsAction()
    {
        $ticketId = $this->getParam('ticketId');
        $ticket = new Ticket($ticketId);
        if (!$ticket->existsInDB()) {
            $this->error = true;
            $this->message = 'Invalid ticket';
            $this->send();
            return;
        }

        if ($this->user->getId() != $ticket->getUserId()) {
            $this->error = true;
            $this->message = $this->user->lang('You can not save the custom fields for this ticket.');
            $this->send();
            return;
        }

        $customFields = $this->getParam('customfields');
        foreach ($customFields as $obj) {
            if (strpos($obj['name'], 'CT_') === 0) {
                $fieldId = substr($obj['name'], 3);

                /*
                // If it's a date field convert it database format
                if (@$_POST["CTT_$fieldId"] == typeDATE && $val != '') {
                  $val = CE_Lib::form_to_db($val, $this->settings->get('Date Format'), "/");
                }*/

                $oldField = $ticket->getCustomField($fieldId);
                if ($oldField['isadminonly'] == 0 || $this->user->isAdmin()) {
                    $ticket->setCustomField($fieldId, $obj['value']);
                }
            }
        }

        $this->message = $this->user->lang('Custom fields have been saved');
        $this->send();
    }

    /**
     * Saves ticket sort replies
     * @return void
     */
    protected function sortrepliesAction()
    {
        $ticketId = $this->getParam('ticketId');
        $ticket = new Ticket($ticketId);
        if (!$ticket->existsInDB()) {
            $this->error = true;
            $this->message = 'Invalid ticket';
            $this->send();
            return;
        }

        if ($this->user->getId() != $ticket->getUserId()) {
            $this->error = true;
            $this->message = $this->user->lang('You can not save the custom fields for this ticket.');
            $this->send();
            return;
        }

        try {
            $value = $this->getParam('replyOnTop', FILTER_SANITIZE_NUMBER_INT);
            $tempUser = new User($ticket->getUserId());
            $tempUser->updateCustomTag("Support-TicketReplyOnTop", $value);
            $tempUser->save();

        } catch (Exception $ex) {
            $this->message = $ex->getMessage();
            $this->error = true;
            CE_Lib::log(1, $ex->getMessage());
        }

        $this->send();
    }

    /**
     * get custom fields for a ticket
     * @return html
     */
    protected function customfieldsfortypeAction()
    {
        $this->disableLayout(false);
        $customFields = [];
        $ticketTypeId = $this->getParam('ticketType', FILTER_SANITIZE_NUMBER_INT);

        $ticket = new Ticket(0);
        $ticketType = new TicketType($ticketTypeId);
        if (!$ticketType->existsInDB()) {
            echo "";
            die();
        }
        $ticket->setMessageType($ticketType);

        $objectCustomFields = new ObjectCustomFields(
            CUSTOM_FIELDS_FOR_TICKETS,
            $ticket,
            false,
            array('fieldOrder', 'ASC')
        );

        while ($row = $objectCustomFields->fetch()) {
            if ($row['isadminonly'] == 1 && !$this->user->isAdmin()) {
                continue;
            }
            if ($row['dropdownoptions'] != '') {
                $selectOptions = [];
                $options = explode(",", trim($row['dropdownoptions']));
                foreach ($options as $option) {
                    if (preg_match('/(.*)(?<!\\\)\((.*)(?<!\\\)\)/', $option, $matches)) {
                        $value = $matches[2];
                        $label = $matches[1];
                    } else {
                        $value = $label = $option;
                    }

                    $label = str_replace(array('\\(', '\\)'), array('(', ')'), $label);
                    $selectOptions[] = array($value,$label);
                }
                $row['dropdownoptions'] = $selectOptions;
            }


            if ($row['isEncrypted']) {
                $row['value'] = Clientexec::decryptString($row['value']);
            }
            $row['value'] = htmlspecialchars_decode($row['value'], ENT_QUOTES);
            $customFields[] = $row;
        }
        $this->view->customFields = $customFields;
    }
}
