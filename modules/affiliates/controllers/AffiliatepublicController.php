<?php

require_once "modules/affiliates/models/AffiliateGateway.php";

class Affiliates_AffiliatepublicController extends CE_Controller_Action
{
    public $moduleName = "affiliates";

    public function overviewAction()
    {
        $this->checkPermissions();
        if (!CE_Lib::affiliateSystem()) {
            CE_Lib::redirectPermissionDenied();
            return;
        }

        if ($this->customer->isDeclinedAffiliate()) {
            CE_Lib::redirectPermissionDenied();
            return;
        }

        if ($this->customer->isPendingAffiliate()) {
            CE_Lib::redirectPermissionDenied('Your affiliate account is currently pending approval.');
            return;
        }

        if (!$this->customer->isApprovedAffiliate()) {
            CE_Lib::redirectPage('index.php?fuse=affiliates&controller=affiliate&view=register');
            return;
        }

        $currency = new Currency($this->user);
        $affiliateGateway = new AffiliateGateway($this->customer);
        $affiliateId = $this->customer->getAffiliateId();
        $affiliate = new Affiliate($affiliateId);

        $this->view->url = CE_Lib::getSoftwareURL() . '/index.php?aff=' . $affiliateId;
        $this->view->days = $this->settings->get('Payout Days');

        $rate = $this->settings->get('Default Commission');
        if ($this->settings->get('Default Commission Structure') == '0') {
            $rate .= '%';
        } else {
            $rate = $currency->convertValueToCurrency($rate, $this->customer->getCurrency(), $this->settings->get('Default Currency'));
            $rate = $affiliateGateway->formatAffiliateCurrency($this->customer->getCurrency(), $rate);
        }
        $this->view->commRate = $rate;

        //Convert minimum withdrawal to the affiliate currency
        $minimumPay = $this->settings->get('Minimum Withdrawal');
        $minimumPay = $currency->convertValueToCurrency($minimumPay, $this->customer->getCurrency(), $this->settings->get('Default Currency'));
        $this->view->minimumPay = $affiliateGateway->formatAffiliateCurrency($this->customer->getCurrency(), $minimumPay);

        $this->view->balance = $affiliateGateway->formatAffiliateCurrency($this->customer->getCurrency(), $affiliate->balance);
        $this->view->hits = $affiliateGateway->getNumberOfAffiliateHits($affiliateId);
        $this->view->sales = $affiliateGateway->getAffiliateSales($affiliateId);
        if ($this->view->hits > 0) {
            $this->view->rate = number_format($this->view->sales / $this->view->hits * 100, 2);
        } else {
            $this->view->rate = 0;
        }
        $this->view->totalCommissions = $affiliateGateway->formatAffiliateCurrency($this->customer->getCurrency(), $affiliateGateway->getPaidAffiliateCommission($affiliateId));
        $this->view->pendingCommission = $affiliateGateway->formatAffiliateCurrency($this->customer->getCurrency(), $affiliateGateway->getPendingAffiliateCommission($affiliateId));

        $this->view->showWithdrawlButton = ($affiliate->balance >= $minimumPay) ? true : false;
    }

    public function commissionsAction()
    {
        $this->checkPermissions();
        if (!$this->customer->isApprovedAffiliate() || !CE_Lib::affiliateSystem()) {
            CE_Lib::redirectPermissionDenied(
                $this->user->lang('Your affiliate account is not approved')
            );
            return;
        }
        if ($this->customer->isDeclinedAffiliate()) {
            CE_Lib::redirectPermissionDenied();
            return;
        }
        $this->view->filter = $this->getParam('filter', FILTER_SANITIZE_STRING, 'all');
    }

    public function getcommissionsAction()
    {
        $this->checkPermissions();
        if (!$this->customer->isApprovedAffiliate() || !CE_Lib::affiliateSystem()) {
            CE_Lib::redirectPermissionDenied(
                $this->user->lang('Your affiliate account is not approved')
            );
            return;
        }
        if ($this->customer->isDeclinedAffiliate()) {
            CE_Lib::redirectPermissionDenied();
            return;
        }
        $affiliateGateway = new AffiliateGateway($this->customer);
        $commissionGateway = new CommissionGateway($this->customer);

        $start = $this->getParam('start', FILTER_SANITIZE_NUMBER_INT, 0);
        $statusfilter = $this->getParam('filter', FILTER_SANITIZE_STRING, 'all');
        $items = $this->getParam('limit', FILTER_SANITIZE_NUMBER_INT, 10);
        $dir = $this->getParam('dir', FILTER_SANITIZE_STRING, 'desc');
        $sort = $this->getParam('sort', FILTER_SANITIZE_STRING, 'id');

        $affiliateId = $this->customer->getAffiliateId();

        $filter = [];
        $filter['affiliate_id'] = $affiliateId;
        if ($statusfilter == 'all') {
            $filter['status'] = [
                COMMISSION_STATUS_PENDING,
                COMMISSION_STATUS_APPROVED,
                COMMISSION_STATUS_PAID,
                COMMISSION_STATUS_DECLINED,
                COMMISSION_STATUS_PENDING_PAID
            ];
        } elseif ($statusfilter == 'pending') {
            $filter['status'] = [
                COMMISSION_STATUS_PENDING
            ];
        } elseif ($statusfilter == 'paid') {
            $filter['status'] = [
                COMMISSION_STATUS_PAID
            ];
        } elseif ($statusfilter == 'declined') {
            $filter['status'] = [
                COMMISSION_STATUS_DECLINED
            ];
        } elseif ($statusfilter == 'pending_payout') {
            $filter['status'] = [
                COMMISSION_STATUS_PENDING_PAID
            ];
        }


        $iterator = $commissionGateway->getCommissions(
            $affiliateId,
            $sort . ' ' . $dir,
            $filter,
            0,
            0
        );

        $commissions = [];
        while ($commission = $iterator->fetch()) {
            $a_commission = $commission->toArray();
            $a_commission['amount'] = $affiliateGateway->formatAffiliateCurrency($this->customer->getCurrency(), $a_commission['amount']);
            $a_commission['status_name'] = $commission->getStatusName();
            $a_commission['status_class'] = $commission->getStatusClass();


            $commissions[] = $a_commission;
        }

        $this->send([
            'commissions' => $commissions,
            'recordsTotal' => 0,
            'recordsFiltered' => 0
        ]);
    }

    public function requestaccountAction()
    {
        $this->checkPermissions();
        if (!CE_Lib::affiliateSystem()) {
            return;
        }
        if ($this->customer->isDeclinedAffiliate()) {
            CE_Lib::redirectPermissionDenied();
            return;
        }

        $affiliateGateway = new AffiliateGateway($this->customer);
        $affiliateGateway->createAffiliateAccount($this->customer->getId());

        if ($this->settings->get('Automatically Activate Affiliate Account?') == 1) {
            CE_Lib::redirectPage(
                'index.php?fuse=affiliates&controller=affiliate&view=overview',
                'Your affiliate account has been approved.'
            );
        } else {
            CE_Lib::redirectPage(
                'index.php?fuse=home&view=dashboard',
                'Your affiliate account has been submitted for approval.'
            );
        }
    }


    public function requestwithdrawlAction()
    {
        $this->checkPermissions();
        if (!$this->customer->isApprovedAffiliate() || !CE_Lib::affiliateSystem()) {
            CE_Lib::redirectPermissionDenied(
                $this->user->lang('Your affiliate account is not approved')
            );
            return;
        }
        if ($this->customer->isDeclinedAffiliate()) {
            CE_Lib::redirectPermissionDenied();
            return;
        }
        $affiliateGateway = new AffiliateGateway($this->customer);
        $affiliateId = $this->customer->getAffiliateId();
        $affiliate = new Affiliate($affiliateId);

        //Convert minimum withdrawal to the affiliate currency
        $currency = new Currency($this->user);
        $minimumPay = $this->settings->get('Minimum Withdrawal');
        $minimumPay = $currency->convertValueToCurrency($minimumPay, $this->customer->getCurrency(), $this->settings->get('Default Currency'));

        if ($affiliate->balance < $minimumPay) {
            $this->error = 1;
            $this->message = $this->user->lang(
                'Sorry, %s is the minimum Withdrawal amount.',
                $affiliateGateway->formatAffiliateCurrency($this->customer->getCurrency(), $minimumPay)
            );
            $this->send();
            return;
        }

        if ($affiliateGateway->hasPendingPayout($affiliateId)) {
            $this->error = 1;
            $this->message = $this->user->lang(
                'Sorry, you have already requested your payout.'
            );
            $this->send();
            return;
        }

        $affiliateGateway->markCommissionsAsPendingPayout($affiliateId);

        $subject = 'Affiliate Withdrawal Request';
        $message = 'Affiliate Account Withdrawal Request: ' . $affiliateGateway->formatAffiliateCurrency($this->customer->getCurrency(), $affiliate->balance);

        $date = date('Y-m-d H-i-s');
        $ticket = new Ticket();
        $tickets = new TicketGateway();
        $ticketTypeGateway = new TicketTypeGateway();

        if ($tickets->GetTicketCount() == 0) {
            $id = $this->settings->get('Support Ticket Start Number');
            $ticket->setForcedId($id);
        }

        $ticket->setUser($this->customer);
        $ticket->save();
        $ticket->setSubject($subject);
        $ticket->SetDateSubmitted($date);
        $ticket->SetLastLogDateTime($date);
        $ticket->setMethod(1);
        $ticket->SetStatus(TICKET_STATUS_OPEN);
        $ticket->SetMessageType($ticketTypeGateway->getBillingTicketType());
        $ticket->setAssignedToDeptId($this->settings->get('Payout Request Department'));

        $ticket->save();
        $supportLog = Ticket_EventLog::newInstance(
            false,
            $this->customer->getId(),
            $ticket->getId(),
            TICKET_EVENTLOG_CREATED,
            $this->customer->getId()
        );
        $supportLog->save();
        $ticket->addInitialLog($message, $date, $this->customer);

        $this->message = $this->user->lang('Successfully requested withdrawl');
        $this->send();
    }

    public function registerAction()
    {
        $this->title = $this->user->lang('Affiliate Registration');
        $this->checkPermissions();

        if (!CE_Lib::affiliateSystem()) {
            CE_Lib::redirectPermissionDenied();
            return;
        }

        if ($this->customer->isDeclinedAffiliate()) {
            CE_Lib::redirectPermissionDenied();
            return;
        }

        if ($this->customer->isApprovedAffiliate()) {
            CE_Lib::redirectPage('index.php?fuse=affiliates&controller=affiliate&view=overview');
            return;
        }
        $affiliateGateway = new AffiliateGateway($this->customer);

        $this->view->bonus = false;
        if ($this->settings->get('Bonus Deposit') > 0) {
            $this->view->bonus = $affiliateGateway->formatAffiliateCurrency($this->customer->getCurrency(), $this->settings->get('Bonus Deposit'));
        }
        $this->view->cookieLength = $this->settings->get('Length of Cookie');

        $commission = $this->settings->get('Default Commission');
        if ($this->settings->get('Default Commission Structure') == '0') {
            $commission .= '%';
        } else {
            $commission = $affiliateGateway->formatAffiliateCurrency($this->customer->getCurrency(), $commission);
        }
        $this->view->commission = $commission;

        $this->view->buttonText = $this->user->lang('Request Affiliate Account');
        if ($this->settings->get('Automatically Activate Affiliate Account?') == 1) {
            $this->view->buttonText = $this->user->lang('Activate Affiliate Account');
        }
    }
}
