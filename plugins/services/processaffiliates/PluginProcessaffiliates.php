<?php

use Illuminate\Database\Capsule\Manager as Db;
use Carbon\Carbon;

class PluginProcessaffiliates extends ServicePlugin
{
    public $hasPendingItems = false;
    protected $featureSet = 'products';

    public function getVariables()
    {
        $variables = array(
            lang('Plugin Name') => array(
                'type'        => 'hidden',
                'description' => '',
                'value'       => lang('Process Affiliates'),
            ),
            lang('Enabled') => array(
                'type'        => 'yesno',
                'description' => lang('When enabled, this will process pending commissions and pending affiliates'),
                'value'       => '0',
            ),
            lang('Run schedule - Minute') => array(
                'type'        => 'text',
                'description' => lang('Enter number, range, list or steps'),
                'value'       => '0',
                'helpid'      => '8',
            ),
            lang('Run schedule - Hour') => array(
                'type'        => 'text',
                'description' => lang('Enter number, range, list or steps'),
                'value'       => '0',
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

    public function execute()
    {
        if (!CE_Lib::affiliateSystem()) {
            return;
        }

        $this->processPendingCommissions();
    }

    private function processPendingCommissions()
    {
        $mailGateway = new NE_MailGateway($this->user);
        $affiliateGateway = new AffiliateGateway($this->user);
        $statusGateway = StatusAliasGateway::getInstance($this->user);
        $statusActive = $statusGateway->getPackageStatusIdsFor(PACKAGE_STATUS_ACTIVE);
        $statusCancelled = $statusGateway->getPackageStatusIdsFor(PACKAGE_STATUS_CANCELLED);

        $result = Db::table('affiliate_commission')
            ->where('status', '=', COMMISSION_STATUS_PENDING)
            ->where('clearing_date', '<=', date("Y-m-d"))
            ->get();

        foreach ($result as $commission) {
            $affiliateAccount = new AffiliateAccount($commission->affiliate_accounts_id);
            $userPackage = new UserPackage($affiliateAccount->userpackage_id);
            if (in_array($userPackage->status, $statusActive)) {
                $commission = new AffiliateCommission($commission->id);
                $commission->status = COMMISSION_STATUS_APPROVED;
                $commission->save();

                $affId = $commission->affiliate_id;
                $affiliate = new Affiliate($affId);
                $oldBalance = $affiliate->balance;
                $affiliate->balance = $oldBalance + $commission->amount;
                $affiliate->save();

                $affiliateAccount->lastpaid = Carbon::now()->toDateTimeString();
                $affiliateAccount->save();

                $templategateway = new AutoresponderTemplateGateway();
                $template = $templategateway->getEmailTemplateByName('New Approved Commission');
                $strMessage = $template->getContents();
                $strSubjectEmailString = $template->getSubject();

                $affiliateUser = new User($affiliate->user_id);
                $amount = $affiliateGateway->formatAffiliateCurrency($affiliateUser->getCurrency(), $commission->amount, true);

                $strMessage = $affiliateGateway->replaceAffiliateTags(
                    $strMessage,
                    $affiliate,
                    $amount
                );
                $strSubjectEmailString = $affiliateGateway->replaceAffiliateTags(
                    $strSubjectEmailString,
                    $affiliate,
                    $amount
                );
                $from = $this->settings->get('Support E-mail');
                if ($template->getOverrideFrom() != '') {
                    $from = $template->getOverrideFrom();
                }

                $mailGateway->MailMessage(
                    $strMessage,
                    $from,
                    $this->settings->get('Company Name'),
                    $affiliate->user_id,
                    '',
                    $strSubjectEmailString,
                    '3',
                    '0',
                    'notifications',
                    '',
                    '',
                    MAILGATEWAY_CONTENTTYPE_HTML
                );
            } elseif (in_array($userPackage->status, $statusCancelled)) {
                $commission = new AffiliateCommission($commission->id);
                $commission->status = COMMISSION_STATUS_DECLINED;
                $commission->save();
            }
        }
    }
}
