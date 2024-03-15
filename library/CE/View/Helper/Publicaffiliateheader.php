<?php

class CE_View_Helper_Publicaffiliateheader extends CE_View_Helper_Abstract
{

    public function publicaffiliateheader($user)
    {
        $view = $this->getParam('view', FILTER_SANITIZE_STRING);

        switch ($view) {
            case 'overview':
                $this->view->pageTitle = $user->lang('Affiliate Overview');
                break;
            case 'commissions':
                $this->view->pageTitle = $user->lang('Commissions');
                break;
            case 'settings':
                $this->view->pageTitle = $user->lang('Affiliate Setting');
                break;
            // case 'links':
            //     $this->view->pageTitle = $user->lang('Custom Links');
            //     break;
        }

        $tabs = [];
        $tabs[] = [
            'name' => $user->lang('Overview'),
            'link' => 'index.php?fuse=affiliates&controller=affiliate&view=overview',
            'view' => 'overview',
            'icon' => 'fas fa-home'
        ];
        $tabs[] = [
            'name' => $user->lang('Commissions'),
            'link' => 'index.php?fuse=affiliates&controller=affiliate&view=commissions',
            'view' => 'commissions',
            'icon' => 'fas fa-chart-line'
        ];
        // $tabs[] = [
        //     'name' => $user->lang('Payout Settings'),
        //     'link' => 'index.php?fuse=affiliates&controller=affiliate&view=settings',
        //     'view' => 'settings',
        //     'icon' => 'fas fa-receipt'
        // ];
        // $tabs[] = [
        //     'name' => $user->lang('Custom Links'),
        //     'link' => 'index.php?fuse=affiliates&controller=affiliate&view=links',
        //     'view' => 'links',
        //     'icon' => 'fas fa-link'
        // ];


        $activeTabs = [];
        foreach ($tabs as $tab) {
            $class = '';
            if ($view === $tab['view']) {
                $class = 'active';
            }
            $tab['class'] = $class;
            $activeTabs[] = $tab;
        }

        $this->view->tabs = $activeTabs;
        return $this->view->render('affiliatepublic/affiliateheader.phtml');
    }
}
