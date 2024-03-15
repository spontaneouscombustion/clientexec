<?php

/*
    menu array
    [0] = link including text to show for the given menu item
    [1] = type of item (depracted) - this was used to determine if it was a menu dropdownlist item or a subitem from old views (keep at zero)
    [2] = Determine if this is the active item
*/

/**
* @package Home
*/
class Home_menu extends NE_MenuHook
{

    var $width = "475px;";
    var $direction = "left";
    var $offset = "-370px;";

    function __construct($user)
    {

            //Dashboard
            $menuItem = new NE_MenuItem($user->lang("Dashboard"), "index.php?fuse=home&amp;view=dashboard");
            $menuItem->addViews(array("dashboard","viewevents"));
            $menuItem->setKey('first_col');

            $submenu = new NE_MenuHook($user);

            $submenuItem = new NE_MenuItem($user->lang("Company Vitals"), "index.php?fuse=home&view=dashboard");
            $submenu->addItem($submenuItem);
            $menuItem->addSubmenu($submenu);

        if ($user->hasPermission('admin_view_events')) {
            $submenuItem = new NE_MenuItem($user->lang("Event List"), "index.php?fuse=home&controller=events&view=viewevents");
            $submenu->addItem($submenuItem);
            $menuItem->addSubmenu($submenu);
        }

            $this->addItem($menuItem);


            //let's get pending items
            $menuItem = new NE_MenuItem($user->lang("Need Your Attention"), "index.php?fuse=home&amp;view=dashboard");
            $menuItem->setKey('pending');
            $menuItem->setHighlight('right');

            //let's add a sub-sub-menu
            $submenu = new NE_MenuHook($user);


        if ($user->hasPermission('clients_view_pending_order')) {
            $submenuItem = new NE_MenuItem($user->lang("Pending Orders"), "#self");
            $submenuItem->addClass("menu-pending-orders");
            $submenu->addItem($submenuItem);
        }

        if ($user->hasPermission('clients_cancel_packages')) {
            $submenuItem = new NE_MenuItem($user->lang("Pending Cancellations"), "#self");
            $submenuItem->addClass("menu-pending-cancel");
            $submenu->addItem($submenuItem);
        }

        if ($user->hasPermission('billing_generate_invoices')) {
            $submenuItem = new NE_MenuItem($user->lang("Clients With Invoices Ready"), "#self");
            $submenuItem->addClass("menu-invoices-ready");
            $submenu->addItem($submenuItem);

            $submenuItem = new NE_MenuItem($user->lang("Credit Card Invoices Ready"), "#self");
            $submenuItem->addClass("menu-cc-invoices-ready");
            $submenu->addItem($submenuItem);

            $submenuItem = new NE_MenuItem($user->lang("Failed Invoices"), "#self");
            $submenuItem->addClass("menu-cc-invoices-failed");
            $submenu->addItem($submenuItem);
        }

        if ($user->hasPermission('clients_view_customers')) {
            if ($user->hasPermission('clients_passphrase_cc')) {
                $submenuItem = new NE_MenuItem($user->lang("Credit Cards Needing Validation"), "#self");
                $submenuItem->addClass("menu-credit-validation");
                $submenu->addItem($submenuItem);
            }

            $submenuItem = new NE_MenuItem($user->lang("Expired Credit Cards"), "#self");
            $submenuItem->addClass("menu-expired-ccs");
            $submenu->addItem($submenuItem);
        }

        if ($user->hasPermission('support_view')) {
            $submenuItem = new NE_MenuItem($user->lang("Tickets Awaiting Reply"), "#self");
            $submenuItem->addClass("menu-tickets-awaiting-reply");
            $submenu->addItem($submenuItem);
        }


        if (CE_Lib::affiliateSystem()) {
            $submenuItem = new NE_MenuItem($user->lang("Pending Affiliates"), "#self");
            $submenuItem->addClass("menu-pending-affiliates");
            $submenu->addItem($submenuItem);

            $submenuItem = new NE_MenuItem($user->lang("Pending Pay Out Commissions"), "#self");
            $submenuItem->addClass("menu-pending-pay-commissions");
            $submenu->addItem($submenuItem);
        }

        if ($user->hasPermission('knowledgebase_manageComments')) {
            $submenuItem = new NE_MenuItem($user->lang("KB Comments Requiring Approval"), "#self");
            $submenuItem->addClass("menu-kb-comments-approval");
            $submenu->addItem($submenuItem);
        }

            $menuItem->addSubmenu($submenu);

            $this->addItem($menuItem);
    }
}
