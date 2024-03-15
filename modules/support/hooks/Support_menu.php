<?php

/**
* @package Support
*/
class Support_menu extends NE_MenuHook
{

    var $width = "500px;";
    var $offset = "-378px;";
    var $direction = "left";

    function __construct($user)
    {

       //View Tickets
       $menuItem = new NE_MenuItem($user->lang('Ticket List'),"index.php?fuse=support&amp;view=viewtickets&amp;controller=ticket&searchfilter=open");
       $menuItem->setHighlight('left');
       $menuItem->addClass("main-ticket-filters");
       $menuItem->addViews(array('viewtickets','viewticket','viewticketevents','viewticketarticles','viewticketnotes'));
       $menuItem->addPermissions("support_view");

        include_once "modules/support/models/TicketSummaryGateway.php";
        $ticketSummaryGateway = new TicketSummaryGateway($user);
        $filters = $ticketSummaryGateway->GetTicketFilters();
        $subMenu = new NE_MenuHook($user);
        foreach($filters['filters'] as $filter) {
            $submenuItem = new NE_MenuItem($filter['ticketfilter_name'],"index.php?fuse=support&amp;view=viewtickets&amp;controller=ticket&searchfilter=".$filter['ticketfilter_id']);
            $submenuItem->addAttribut("data-filter-id",$filter['ticketfilter_id']);
            $submenuItem->addAttribut("data-filter-name",$filter['ticketfilter_name']);
            $submenuItem->addClass("ticket-filter-link");
            $subMenu->addItem($submenuItem);
        }
        $menuItem->addSubmenu($subMenu);
        $this->addItem($menuItem);

        //system filters

        $menuItem = new NE_MenuItem($user->lang("System Filters"),"#");
        $menuItem->setKey("second");
        $menuItem->addViews("lastseen");
        $menuItem->addPermissions("support_view");

        $subMenu = new NE_MenuHook($user);

        $submenuItem = new NE_MenuItem($user->lang("Last Tickets Viewed"),"index.php?fuse=support&amp;view=lastseen&amp;controller=ticket&searchfilter=lastseen");
        $submenuItem->addAttribut("data-filter-id","lastseen");
        $submenuItem->addAttribut("data-filter-name",$user->lang("Last Tickets Viewed"));
        $submenuItem->addClass("ticket-filter-link");
        $subMenu->addItem($submenuItem);


        $submenuItem = new NE_MenuItem($user->lang("Tickets Following"),"index.php?fuse=support&amp;view=viewtickets&amp;controller=ticket&searchfilter=subscribedto");
        $submenuItem->addAttribut("data-filter-id","subscribedto");
        $submenuItem->addAttribut("data-filter-name",$user->lang("Tickets Following"));
        $submenuItem->addClass("ticket-filter-link");
        $subMenu->addItem($submenuItem);

        $menuItem->addSubmenu($subMenu);
        $this->addItem($menuItem);

        // KB Main Menu
        $menuItem = new NE_MenuItem($user->lang('Knowledge Base'),"index.php?fuse=knowledgebase&controller=articles&view=viewarticles");
        $menuItem->setKey("second");
        $menuItem->addViews(array('viewarticles', 'viewcomments'));
        $menuItem->addPermissions("knowledgebase_view");

        $submenu = new NE_MenuHook($user);
        $submenuItem = new NE_MenuItem($user->lang("Articles"),"index.php?fuse=knowledgebase&controller=articles&view=viewarticles");
        $submenu->addItem($submenuItem);

        $submenuItem = new NE_MenuItem($user->lang("Comments"),"index.php?fuse=knowledgebase&amp;controller=comments&amp;view=viewcomments");
        $submenuItem->addPermissions("knowledgebase_manageComments");
        $submenu->addItem($submenuItem);

        $menuItem->addSubmenu($submenu);
        $this->addItem($menuItem);

        $menuItem = new NE_MenuItem($user->lang('Feedback'),"index.php?fuse=support&controller=tickets&view=feedback");
        $menuItem->setKey("second");
        $menuItem->addViews(array('viewfeedback'));
        $menuItem->addPermissions("support_view_feedback");
        $this->addItem($menuItem);
    }
}