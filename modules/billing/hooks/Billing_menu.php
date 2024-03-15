<?php

/**
 * @category Hooks
 * @package  Billing
 * @author   Alberto Vasquez <alberto@clientexec.com>
 * @license  ClientExec License
 * @version  [someversion]
 * @link     http://www.clientexec.com
 */
class Billing_menu extends NE_MenuHook
{

    var $width = "295px;";
    var $offset = "-165px;";
    var $direction = "left";

    function __construct($user)
    {

        //Process Invoices
        $menuItem = new NE_MenuItem($user->lang("Process Invoices"), "index.php?fuse=billing&controller=invoice&view=processinvoices&phase=RecurringSettings");
        $menuItem->addViews(array('processinvoices'));
        $menuItem->addPermissions("billing_generate_invoices");
        $menuItem->setKey("first");
        $this->addItem($menuItem);


        //Invoice List
        $menuItem = new NE_MenuItem($user->lang("Invoice List"), "#");
        $menuItem->addViews(array("invoices","invoice"));
        $menuItem->addPermissions("billing_view");
        $menuItem->setKey("first");

        $subMenu = new NE_MenuHook($user);
        $submenuItem = new NE_MenuItem($user->lang('All Invoices'), "index.php?fuse=billing&amp;controller=invoice&amp;view=invoices&filter=2");
        $subMenu->addItem($submenuItem);

        $submenuItem = new NE_MenuItem($user->lang('Overdue Invoices'), "index.php?fuse=billing&amp;controller=invoice&amp;view=invoices&filter=0");
        $subMenu->addItem($submenuItem);

        $submenuItem = new NE_MenuItem($user->lang('Paid Invoices'), "index.php?fuse=billing&amp;controller=invoice&amp;view=invoices&filter=6");
        $subMenu->addItem($submenuItem);

        $submenuItem = new NE_MenuItem($user->lang('Unpaid Invoices'), "index.php?fuse=billing&amp;controller=invoice&amp;view=invoices&filter=1");
        $subMenu->addItem($submenuItem);

        $submenuItem = new NE_MenuItem($user->lang('Pending Invoices'), "index.php?fuse=billing&amp;controller=invoice&amp;view=invoices&filter=4");
        $subMenu->addItem($submenuItem);

        $submenuItem = new NE_MenuItem($user->lang('Draft Invoices'), "index.php?fuse=billing&amp;controller=invoice&amp;view=invoices&filter=5");
        $subMenu->addItem($submenuItem);

        $submenuItem = new NE_MenuItem($user->lang('Unsent Invoices'), "index.php?fuse=billing&amp;controller=invoice&amp;view=invoices&filter=-3");
        $subMenu->addItem($submenuItem);

        $submenuItem = new NE_MenuItem($user->lang('Failed Invoices'), "index.php?fuse=billing&amp;controller=invoice&amp;view=invoices&filter=-2");
        $subMenu->addItem($submenuItem);

        $menuItem->addSubmenu($subMenu);
        $this->addItem($menuItem);
    }
}
