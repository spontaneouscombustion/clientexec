<?php
require_once 'modules/support/models/Ticket.php';
require_once 'modules/support/models/Ticket_EventLog.php';

/**
 * Support Module's Public Index Controller
 *
 * @category   Action
 * @package    Support
 * @author     Matt Grandy <matt@clientexec.com>
 * @license    http://www.clientexec.com  ClientExec License Agreement
 */
class Support_IndexpublicController extends CE_Controller_Action {

    protected $moduleName = "support";

    protected function supportwidgetformAction()
    {
        $this->disableLayout(false);

        $this->view->forceSuggestions = $this->getParam('forceSuggestions', FILTER_VALIDATE_BOOLEAN, false, false);

        $this->view->hasPermission = true;
    }

    /**
     * Rating service for ticket emailed to customer
     * @return [type] [description]
     */
    public function rateserviceAction()
    {

        $ticket_id = $this->getParam('ticketId', FILTER_SANITIZE_NUMBER_INT);

        $ticket = new Ticket($ticket_id);
        if (!$ticket->existsInDB()) {
            CE_Lib::redirectPage('index.php?fuse=support&view=thanksforrating&error=1');
        }

        if (!$ticket->getRateHash()) {
            CE_Lib::redirectPage('index.php?fuse=support&view=thanksforrating&error=2');
        }

        if ($ticket->getRateHash() != $_GET['hash']) {
            CE_Lib::redirectPage('index.php?fuse=support&view=thanksforrating&error=3');
        }

        if (!$ticket->setRate($_GET['rate'])) {
            CE_Lib::redirectPage('index.php?fuse=support&view=thanksforrating&error=4');
        }

        $ticket->generateRateHash();
        $ticket->save();

        CE_Lib::redirectPage('index.php?fuse=support&view=thanksforrating&ticketId='.$ticket_id.'&hash='.$ticket->getRateHash());
    }

    /**
     * After adding rating customer can add free form text for additional input
     * @return [type] [description]
     */
    public function savefeedbackAction()
    {
        $ticket_id = $this->getParam('ticketId', FILTER_SANITIZE_NUMBER_INT);
        $ticket_message = $this->getParam('message', FILTER_SANITIZE_STRING, "");

        $ticket = new Ticket($ticket_id, $this->user);
        $customer = new User($ticket->getUserId());
        $assignedTo= new User($ticket->getAssignedToId());
        $ticket->setFeedback($ticket_message);
        if (!$ticket->getRateHash()) {
            CE_Lib::redirectPage('index.php?fuse=support&view=thanksforrating&error=2');
        }

        if ($ticket->getRateHash() != $_POST['hash']) {
            CE_Lib::redirectPage('index.php?fuse=support&view=thanksforrating&error=3');
        }
        $ticket->setRateHash(null);
        $ticket->save();
        $supportLog = Ticket_EventLog::newInstance(false, $customer->getId(), $ticket_id, TICKET_EVENTLOG_FEEDBACK, $customer->getId(), $ticket_message);
        $supportLog->save();

        //Send E-mails to Department Lead
        $dept = $ticket->getAssignedToDept();
        $feedbackNotifyMembers = array();
        $feedbackNotifyMembers = $dept->getNotifyMembers('feedback');
        if (!empty($feedbackNotifyMembers)) {
            include_once 'modules/support/models/TicketNotifications.php';
            $ticketNotifications= new TicketNotifications();

            $message = $this->user->lang('Rate: %s', $ticket->getRateName()) . "\r\n".$this->user->lang('Comment').': '.$ticket_message;
            $ticketNotifications->notifyForNewFeedBack($feedbackNotifyMembers, $ticket, $customer, $message);
        }

        CE_Lib::redirectPage('index.php?fuse=support&view=thanksforrating&thanks=1&ticketId=' . $ticket_id);
    }

    /**
     * show customer a thank you page after reviewing ticket
     * @return [type] [description]
     */
    public function thanksforratingAction()
    {
        $this->title = $this->user->lang('Thank you');
        $this->view->showFeedback = false;

        if (isset($_REQUEST['ticketId'])) {
            $this->view->ticketId = $_GET['ticketId'];
            $ticket = new Ticket($this->view->ticketId);
            $this->view->shareTwitter = $this->settings->get('Show Twitter Button on Excellent Feedback') && ($ticket->getRate() == TICKET_RATE_OUTSTANDING);
        }

        if (isset($_GET['error'])) {
            $this->view->headTitle = $this->user->lang('There was an error with this request');
            switch($_GET['error']) {
                case 1:
                    $this->view->badTicket = 'badTicket';
                    break;
                case 2:
                    $this->view->alreadyRated = 'alreadyRated';
                    break;
                case 3:
                    $this->view->badHash = 'badHash';
                    break;
                case 4:
                    $this->view->badRate = 'badRate';
                    break;

            }
        } elseif (@$_GET['thanks'] == 1) {
            $this->view->showFeedback = false;
            $this->view->showThanks = true;
            $this->view->headTitle = $this->user->lang('Thank you for your feedback');
        } else {

            $this->view->hashCode = $_GET['hash'];
            $this->view->showFeedback = true;
            $this->view->headTitle = $this->user->lang('Share Feedback');
        }

        $this->view->tweet = $this->settings->get('Default Feedback Tweet');
        $this->view->companyUrl = $this->settings->get('Company URL');
    }
}
