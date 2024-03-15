
<?php
/**
 * Description of Settingsgroup
 *
 * @author alberto
 */
class CE_View_Helper_Publichelper extends CE_View_Helper_Abstract
{
    public function publichelper()
    {
        $breadcrumb = "";
        $this->view->viewingPlugin = false;
        if ($this->view->gFuse === "files") {
        } elseif ($this->view->gView === 'announcements') {
            $breadcrumb = $this->createAnnouncementsBreadCrumb();
        } elseif ($this->view->gView === 'announcement') {
            $breadcrumb = $this->createAnnouncementBreadCrumb();
        } elseif ($this->view->gFuse === 'knowledgebase') {
            $breadcrumb = $this->createKBBreadCrumb();
        } elseif ($this->view->gView === 'submitticket') {
            $breadcrumb = $this->createSupportBreadCrumb();
            $breadcrumb .= '<li class="active"><span>' . $this->view->user->lang('Submit ticket') . '</span></li>';
        } elseif ($this->view->gView === 'ShowSnapinsCustomer') {
            $this->view->viewingPlugin = true;
        } elseif ($this->view->gView === "products") {
            $breadcrumb = $this->createProductsBreadCrumb(true);
        } elseif (in_array($this->view->gView, array('product', 'productsslinfo', 'productsnapinview'))) {
            $breadcrumb = $this->createProductsBreadCrumb();
            $breadcrumb .= '<li class="active"><span>' . $this->view->user->lang('Viewing Product') . '</span></li>';
        } elseif ($this->view->gView === "productaddons") {
            $breadcrumb = $this->createProductsBreadCrumb();
            $breadcrumb .= '<li class="active"><span>' . $this->view->user->lang('Viewing Product Addons') . '</span></li>';
        } elseif ($this->view->gView === "ticket") {
            $breadcrumb = $this->createSupportBreadCrumb();
            $breadcrumb .= '<li><a href="index.php?fuse=support&controller=ticket&view=alltickets">' . $this->view->user->lang('My Tickets') . '</a></li>';
            $breadcrumb .= '<li class="active"><span>' . $this->view->user->lang('Viewing Ticket') . '</span></li>';
        } elseif ($this->view->gView === "alltickets") {
            $breadcrumb = $this->createSupportBreadCrumb();
            $breadcrumb .= '<li class="active"><span>' . $this->view->user->lang('Viewing Tickets') . '</span></li>';
        } elseif ($this->view->gView === "allinvoices") {
            $breadcrumb = $this->createBillingBreadCrumb();
            $breadcrumb .= '<li class="active"><span>' . $this->view->user->lang('Viewing Invoices') . '</span></li>';
        } elseif ($this->view->gView === "invoice") {
            $breadcrumb = $this->createBillingBreadCrumb();
            $breadcrumb .= '<li><a href="index.php?fuse=billing&controller=invoice&view=allinvoices">' . $this->view->user->lang('My Invoices') . '</a></li>';
            $breadcrumb .= '<li class="active"><span>' . $this->view->user->lang('Viewing Invoice') . '</span></li>';
        } elseif ($this->view->gView === 'paymentmethod') {
            $breadcrumb = $this->createBillingBreadCrumb();
            $breadcrumb .= '<li class="active"><span>' . $this->view->user->lang('Edit payment method') . '</span></li>';
        }

        if ($breadcrumb !== "") {
            $this->view->tplBreadCrumb = '<ol class="breadcrumb">' . $breadcrumb . '</ol>';
        }

        return;
    }

    private function createProductsBreadCrumb($isactive = false)
    {

        $html = '<li><a href="index.php?fuse=home&view=dashboard">' . $this->view->user->lang('Dashboard') . '</a></li>';

        if ($isactive) {
            $html .= '<li class="active"><span>' . $this->view->user->lang('Viewing Products') . '</span></li>';
        } else {
            $html .= '<li><a href="index.php?fuse=clients&controller=products&view=products">' . $this->view->user->lang('My Products') . '</a></li>';
        }

        return $html;
    }

    private function createBillingBreadCrumb($isactive = false)
    {
        if ($isactive) {
            $html = '<li class="active"><span>' . $this->view->user->lang('Dashboard') . '</span></li>';
        } else {
            $html = '<li><a href="index.php?fuse=home&view=dashboard">' . $this->view->user->lang('Dashboard') . '</a></li>';
        }
        return $html;
    }

    private function createSupportBreadCrumb($isactive = false)
    {
        if ($this->view->loggedIn) {
            $strLabel = $this->view->user->lang('Dashboard');
            if ($isactive) {
                $html = '<li class="active"><span>' . $strLabel . '</span></li>';
            } else {
                $html = '<li><a href="index.php?fuse=home&view=dashboard">' . $strLabel . '</a></li>';
            }
        } else {
            if ($isactive) {
                $html = '<li class="active"><span>' . $this->view->user->lang("Support") . '</span></li>';
            } else {
                $html = '<li><a href="' . CE_Lib::generateMainKBLink() . '">' . $this->view->user->lang('Support') . '</a></li>';
            }
        }

        return $html;
    }

    private function createHomeBreadCrumb($isactive = false)
    {
        if ($this->view->loggedIn) {
            $strLabel = $this->view->user->lang('Dashboard');
            if ($isactive) {
                $breadcrumb = '<li class="active"><span>' . $strLabel . '</span></li>';
            } else {
                $breadcrumb = '<li><a href="index.php?fuse=home&view=dashboard">' . $strLabel . '</a></li>';
            }
        } else {
            $strLabel = $this->view->user->lang("Home");
            if ($isactive) {
                $breadcrumb = '<li class="active"><span>' . $strLabel . '</span></li>';
            } else {
                $breadcrumb = '<li><a href="index.php">' . $strLabel . '</a></li>';
            }
        }

        return $breadcrumb;
    }

    private function createAnnouncementBreadCrumb()
    {
        return $this->createHomeBreadCrumb() . '<li><a href="index.php?fuse=home&controller=announcements&view=announcements">' . $this->view->user->lang('Announcements') . '</a></li><li>' . $this->view->user->lang('Announcement') . '</li>';
    }

    private function createAnnouncementsBreadCrumb()
    {
        return $this->createHomeBreadCrumb() . '<li class="active"><span>' . $this->view->user->lang('Announcements') . '</span></li>';
    }

    private function createKBBreadCrumb()
    {

        $html = $this->createSupportBreadCrumb();

        if ($this->view->categoryName != "") {
            $html .= '<li><a href="' . CE_Lib::generateMainKBLink() . '">' . $this->view->user->lang('Kb') . '</a></li>';

            if ($this->view->articleName != '') {
                $category = new KB_Category($this->view->categoryId);
                $html .= '<li><a href="' . $category->generateLink() . '">' . $this->view->categoryName . '</a></li>';
                $html .= '<li class="active"><span>' . $this->view->articleName . '</span></li>';
            } else {
                $html .= '<li class="active"><span>' . $this->view->categoryName . '</span></li>';
            }
        } else {
            $html .= '<li class="active"><span>' . $this->view->user->lang("Kb") . '</span></li>';
        }
        return $html;
    }
}
