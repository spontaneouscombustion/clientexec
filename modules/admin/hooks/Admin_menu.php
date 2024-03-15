<?php
/**
 * Admin Menu Hook
 *
 * @category Hook
 * @package  Admin
 * @author   Alberto Vasquez <alberto@clientexec.com>
 * @license  ClientExec License
 * @version  [someversion]
 * @link     http://www.clientexec.com
 */
class Admin_menu extends NE_MenuHook
{

    var $width = "685px;";
    var $direction = "left";
    var $offset = "-347px;";

    function __construct($user)
    {

        include_once "modules/admin/models/SettingsGateway.php";
        $sg = new SettingsGateway($user);

        if (!$user->hasPermission("admin_edit_settings")) {
            return;
        }


        /* company settings */
        $menuItem = new NE_MenuItem($user->lang('Company'), "#");
        $menuItem->setKey("1st");
        $menuItem->addViews(array(
            'templateoptions',
            'templatehtml',
            'emailtemplates',
            "all",
            'viewsettings',
            'snapinsettings',
        ));

        $submenu = new NE_MenuHook($user);

        $submenuItem = new NE_MenuItem($user->lang("General"), "index.php?fuse=admin&controller=settings&view=viewsettings&settings=admin_general");
        $submenu->addItem($submenuItem);

        $submenuItem = new NE_MenuItem($user->lang("Localization"), "index.php?fuse=admin&controller=settings&view=viewsettings&settings=admin_localization");
        $submenu->addItem($submenuItem);

        $submenuItem = new NE_MenuItem($user->lang("Mail Configuration"), "index.php?fuse=admin&controller=settings&view=viewsettings&settings=admin_email");
        $submenu->addItem($submenuItem);

        $submenuItem = new NE_MenuItem($user->lang("Email Templates"), "index.php?fuse=admin&controller=settings&view=emailtemplates&settings=mail");
        $submenu->addItem($submenuItem);

        $submenuItem = new NE_MenuItem($user->lang("Public Style"), "index.php?fuse=admin&controller=settings&view=templateoptions&settings=style_options");
        $submenu->addItem($submenuItem);

        $submenuItem = new NE_MenuItem($user->lang("Customize HTML"), "index.php?fuse=admin&controller=settings&view=templatehtml&settings=style_options");
        $submenu->addItem($submenuItem);

        $submenuItem = new NE_MenuItem($user->lang("Social Sharing"), "index.php?fuse=admin&controller=settings&view=viewsettings&settings=admin_social");
        $submenu->addItem($submenuItem);

        //let's get snapins that have settings here
        $snapin_settings = $sg->getSnapinSettingsForType("company");
        foreach ($snapin_settings as $snapin_setting) {
            $submenuItem = new NE_MenuItem($user->lang($snapin_setting['name']), $snapin_setting['url']);
            $submenu->addItem($submenuItem);
        }

        $menuItem->addSubmenu($submenu);
        $this->addItem($menuItem);

        /* users settings */
        $menuItem = new NE_MenuItem($user->lang('Users'), "#");
        $menuItem->setKey("1st");
        $menuItem->addViews(array(
            'staffsettings',
            'permissions',
            'usercustomfields',
            'editaddadminaccount',
            'adminlist',
            'ViewAddEditGeneralGroup',
            'viewgroups',
            'ViewCustomerGroupNotes',
            'AddEditCustomerGroup'
        ));

        $submenu = new NE_MenuHook($user);

        $submenuItem = new NE_MenuItem($user->lang("General"), "index.php?fuse=admin&controller=settings&view=viewsettings&settings=account_general");
        $submenu->addItem($submenuItem);


        //Staff / Clients
        if ($user->isSuperAdmin() || $user->hasPermission('admin_manage_customer_group')) {
            $submenu = new NE_MenuHook($user);
            if ($user->isSuperAdmin()) {
                $submenuItem = new NE_MenuItem($user->lang("Staff Management"), "index.php?fuse=admin&controller=staff&view=adminlist");
                $submenu->addItem($submenuItem);
            }

            if ($user->hasPermission('admin_manage_customer_group')) {
                $submenuItem = new NE_MenuItem($user->lang("Client Groups"), "index.php?fuse=admin&controller=groups&view=viewgroups");
                $submenu->addItem($submenuItem);
            }

            $submenuItem = new NE_MenuItem($user->lang("Custom Fields"), "index.php?fuse=admin&controller=settings&view=usercustomfields");
            $submenu->addItem($submenuItem);

            $submenuItem = new NE_MenuItem($user->lang("Status Alias"), "index.php?fuse=admin&view=statusalias&controller=settings&settings=users_statusalias&type=3");
            $submenu->addItem($submenuItem);
        }

        //let's get snapins that have settings here
        $snapin_settings = $sg->getSnapinSettingsForType("users");
        foreach ($snapin_settings as $snapin_setting) {
            $submenuItem = new NE_MenuItem($user->lang($snapin_setting['name']), $snapin_setting['url']);
            $submenu->addItem($submenuItem);
        }

        $menuItem->addSubmenu($submenu);
        $this->addItem($menuItem);

        // Affiliate Settings

        $menuItem = new NE_MenuItem($user->lang('Affiliates'), "#");
        $menuItem->setKey("1st");
        $submenu = new NE_MenuHook($user);
        $submenuItem = new NE_MenuItem($user->lang("General"), "index.php?fuse=admin&controller=settings&view=viewsettings&settings=affiliates_affiliate");
        $submenu->addItem($submenuItem);
        $menuItem->addSubmenu($submenu);

        $submenuItem = new NE_MenuItem(
            $user->lang("Custom Links"),
            "index.php?fuse=admin&view=customlinks&controller=settings&settings=clients"
        );
        $submenu->addItem($submenuItem);
        $menuItem->addSubmenu($submenu);
        $this->addItem($menuItem);

        /*billing settings*/
        if ($user->hasPermission("admin_billing_setup")) {
            $menuItem = new NE_MenuItem($user->lang('Billing'), "#");
            $menuItem->setKey("2nd");
            $menuItem->addViews(array(
            "coupons",
            "currencies",
            "billingtypes",
            "taxes",
            ));

            $submenu = new NE_MenuHook($user);

            $submenuItem = new NE_MenuItem($user->lang("General"), "index.php?fuse=admin&controller=settings&view=viewsettings&settings=billing_billing");
            $submenu->addItem($submenuItem);

            $submenuItem = new NE_MenuItem($user->lang("Invoices"), "index.php?fuse=admin&controller=settings&view=viewsettings&settings=billing_invoicing");
            $submenu->addItem($submenuItem);

            $submenuItem = new NE_MenuItem($user->lang("Billing Types"), "index.php?fuse=admin&view=billingtypes&controller=settings&settings=billing");
            $submenu->addItem($submenuItem);

            $submenuItem = new NE_MenuItem($user->lang("Billing Cycles"), "index.php?fuse=admin&view=billingcycles&controller=settings&settings=billing");
            $submenu->addItem($submenuItem);

            $submenuItem = new NE_MenuItem($user->lang("Coupons"), "index.php?fuse=admin&controller=settings&view=coupons&settings=billing");
            $submenu->addItem($submenuItem);

            $submenuItem = new NE_MenuItem($user->lang("Currencies"), "index.php?fuse=admin&controller=settings&view=currencies&settings=billing");
            $submenu->addItem($submenuItem);

            $submenuItem = new NE_MenuItem($user->lang("Taxes"), "index.php?fuse=admin&controller=settings&view=taxes&settings=billing");
            $submenu->addItem($submenuItem);

          //let's get snapins that have settings here
            $snapin_settings = $sg->getSnapinSettingsForType("billing");
            foreach ($snapin_settings as $snapin_setting) {
                $submenuItem = new NE_MenuItem($user->lang($snapin_setting['name']), $snapin_setting['url']);
                $submenu->addItem($submenuItem);
            }

            $menuItem->addSubmenu($submenu);
            $this->addItem($menuItem);
        }



        /* support settings */
        if ($user->hasPermission("admin_support_setup")) {
            $menuItem = new NE_MenuItem($user->lang('Support'), "#");
            $menuItem->setKey("2nd");
            $menuItem->addViews(array(
                "supportwidget",
                "departments",
                "tickettypes",
                'ticketcustomfields',
                'emailrouting',
                'cannedresponses'
            ));

            $submenu = new NE_MenuHook($user);

            $submenuItem = new NE_MenuItem($user->lang("General"), "index.php?fuse=admin&controller=settings&view=viewsettings&settings=support_support");
            $submenu->addItem($submenuItem);

            $submenuItem = new NE_MenuItem($user->lang("Departments"), "index.php?fuse=admin&controller=settings&view=departments&settings=support");
            $submenu->addItem($submenuItem);

            $submenuItem = new NE_MenuItem($user->lang("Routing"), "index.php?fuse=admin&view=emailrouting&controller=settings&settings=support");
            $submenu->addItem($submenuItem);

            if ($user->hasPermission('support_manage_spam_filters')) {
                $submenuItem = new NE_MenuItem(
                    $user->lang("Spam Filters"),
                    "index.php?fuse=admin&controller=settings&view=spamfilters&settings=support"
                );
                $submenu->addItem($submenuItem);
            }

            $submenuItem = new NE_MenuItem($user->lang("Canned Replies"), "index.php?fuse=support&amp;controller=cannedresponse&amp;view=cannedresponses");
            $submenu->addItem($submenuItem);

            $submenuItem = new NE_MenuItem($user->lang("Ticket Types"), "index.php?fuse=admin&view=tickettypes&controller=settings&settings=support");
            $submenu->addItem($submenuItem);

            $submenuItem = new NE_MenuItem($user->lang("Custom Fields"), "index.php?fuse=admin&view=ticketcustomfields&controller=settings&settings=support");
            $submenu->addItem($submenuItem);

            $submenuItem = new NE_MenuItem($user->lang("Site Widget"), "index.php?fuse=admin&view=supportwidget&controller=settings&settings=support");
            $submenu->addItem($submenuItem);

            $submenuItem = new NE_MenuItem($user->lang("Status Alias"), "index.php?fuse=admin&view=statusalias&controller=settings&settings=support_statusalias&type=2");
            $submenu->addItem($submenuItem);

            $submenuItem = new NE_MenuItem($user->lang("Knowledge Base"), "index.php?fuse=admin&controller=settings&view=viewsettings&settings=support_knowledgebase");
            $submenu->addItem($submenuItem);

            //let's get snapins that have settings here
            $snapin_settings = $sg->getSnapinSettingsForType("support");
            foreach ($snapin_settings as $snapin_setting) {
                $submenuItem = new NE_MenuItem($user->lang($snapin_setting['name']), $snapin_setting['url']);
                $submenu->addItem($submenuItem);
            }

            $menuItem->addSubmenu($submenu);
            $this->addItem($menuItem);
        }

        /* product settings */
        $menuItem = new NE_MenuItem($user->lang('Products'), "#");
        $menuItem->setKey("3rd");
        $menuItem->addViews(array('products','product','productaddons','productaddon','productcustomfields','servers','addeditserver'));

        $submenu = new NE_MenuHook($user);

        if ($user->hasPermission("admin_view_packagetypes")) {
            //let's add a sub-sub-menu
            $submenuItem = new NE_MenuItem($user->lang("Products"), "index.php?fuse=admin&amp;view=products&amp;controller=products");
            $submenu->addItem($submenuItem);

            $submenuItem = new NE_MenuItem($user->lang("Addons"), "index.php?fuse=admin&controller=addons&view=productaddons");
            $submenu->addItem($submenuItem);
        }

        //Hosting Menu Item
        $submenuItem = new NE_MenuItem($user->lang('Servers'), "index.php?fuse=admin&amp;view=servers&amp;controller=servers");
        $submenuItem->addPermissions("admin_edit_servers");
        $submenu->addItem($submenuItem);

        $submenuItem = new NE_MenuItem($user->lang("Order Page"), "index.php?fuse=admin&controller=settings&view=viewsettings&settings=admin_signup");
        $submenu->addItem($submenuItem);

        if ($user->hasPermission('admin_show_custom_fields')) {
            $submenuItem = new NE_MenuItem($user->lang("Custom Fields"), "index.php?fuse=admin&controller=settings&view=productcustomfields");
            $submenu->addItem($submenuItem);
        }

        $submenuItem = new NE_MenuItem($user->lang("Status Alias"), "index.php?fuse=admin&view=statusalias&controller=settings&settings=packages_statusalias&type=1");
        $submenu->addItem($submenuItem);

        $submenuItem = new NE_MenuItem($user->lang("Domains"), "index.php?fuse=admin&controller=settings&view=viewsettings&settings=admin_domain");
        $submenu->addItem($submenuItem);

        $submenuItem = new NE_MenuItem($user->lang("Stock Control"), "index.php?fuse=admin&controller=settings&view=viewsettings&settings=admin_stock");
        $submenu->addItem($submenuItem);

        if ($user->hasPermission('clients_upgrade_customer_packages')) {
            $submenuItem = new NE_MenuItem($user->lang("Upgrade/Downgrade"), "index.php?fuse=admin&controller=settings&view=viewsettings&settings=admin_upgrade");
            $submenu->addItem($submenuItem);
        }

        //let's get snapins that have settings here
        $snapin_settings = $sg->getSnapinSettingsForType("products");
        foreach ($snapin_settings as $snapin_setting) {
            $submenuItem = new NE_MenuItem($user->lang($snapin_setting['name']), $snapin_setting['url']);
            $submenu->addItem($submenuItem);
        }

        $menuItem->addSubmenu($submenu);
        $this->addItem($menuItem);

        /*//webhooks
        if ($user->hasPermission('admin_manage_webhooks')) {
            $menuItem = new NE_MenuItem($user->lang('Webhooks'),"#");
            $menuItem->addViews(array('webhooks'));
            $menuItem->setKey("first");
            $this->addItem($menuItem);
        }*/

        //SQL Tool



        //Product menu items


        if ($user->hasPermission('admin_manage_plugins')) {
            //plugins
            $menuItem = new NE_MenuItem($user->lang('Plugins'), "#");
            $menuItem->setKey("3rd");
            $menuItem->addViews(array('plugins'));

            $submenu = new NE_MenuHook($user);

            $submenuItem = new NE_MenuItem($user->lang("Snapins"), "index.php?fuse=admin&controller=settings&view=snapinsettings&plugin=licensedefender&settings=plugins_snapins&type=Snapins");
            $submenu->addItem($submenuItem);

            $submenuItem = new NE_MenuItem($user->lang("Payment Processors"), "index.php?fuse=admin&controller=settings&view=plugins&settings=plugins_gateways&type=gateways");
            $submenu->addItem($submenuItem);

            $submenuItem = new NE_MenuItem($user->lang("Automation Services"), "index.php?fuse=admin&controller=settings&view=plugins&settings=plugins_services&type=Services");
            $submenu->addItem($submenuItem);

            $submenuItem = new NE_MenuItem($user->lang("Registrars"), "index.php?fuse=admin&controller=settings&view=plugins&settings=plugins_registrars&type=Registrars");
            $submenu->addItem($submenuItem);

            $submenuItem = new NE_MenuItem($user->lang("SSL"), "index.php?fuse=admin&controller=settings&view=plugins&settings=plugins_ssl&type=SSLRegistrars");
            $submenu->addItem($submenuItem);

            $submenuItem = new NE_MenuItem($user->lang("Fraud"), "index.php?fuse=admin&controller=settings&view=plugins&settings=plugins_fraud&type=Fraud");
            $submenu->addItem($submenuItem);

            $submenuItem = new NE_MenuItem($user->lang("Captcha"), "index.php?fuse=admin&controller=settings&view=plugins&settings=plugins_captcha&type=Captcha");
            $submenu->addItem($submenuItem);

            $showPhoneVerification = false;
            $plugins = new NE_PluginCollection('phoneverification', $this->user);
            // loop over this, and set to true if we have any
            while ($plugins->getNext()) {
                $showPhoneVerification = true;
                break;
            }
            if ($showPhoneVerification === true) {
                $submenuItem = new NE_MenuItem($user->lang("Phone Verification"), "index.php?fuse=admin&controller=settings&view=plugins&settings=plugins_phoneverification&type=PhoneVerification");
                $submenu->addItem($submenuItem);
            }

            /*$submenuItem = new NE_MenuItem($user->lang("SMS Gateways"),"index.php?fuse=admin&controller=settings&view=plugins&settings=plugins_sms&type=Sms");
            $submenu->addItem($submenuItem);*/

            $menuItem->addSubmenu($submenu);
            $this->addItem($menuItem);
        }

        //Import / Export
        $menuItem = new NE_MenuItem($user->lang('Security'), "#");
        $menuItem->setKey("4th");
        $menuItem->addViews(array(
            "apikey",
            "passphraseinfo",
            "domainpasswords",
            "domainpasswords",
            ));

        $submenu = new NE_MenuHook($user);

        $submenuItem = new NE_MenuItem($user->lang("Passwords"), "index.php?fuse=admin&controller=settings&view=viewsettings&settings=admin_passwords");
        $submenu->addItem($submenuItem);

        if (!DEMO) {
            $submenuItem = new NE_MenuItem($user->lang("Application Key"), "index.php?fuse=admin&view=apikey&controller=settings&settings=security");
            $submenu->addItem($submenuItem);
        }

        $submenuItem = new NE_MenuItem($user->lang("CC Passphrase"), "index.php?fuse=admin&view=passphraseinfo&controller=settings&settings=security");
        $submenu->addItem($submenuItem);

        $submenuItem = new NE_MenuItem($user->lang("Banned IPs"), "index.php?fuse=admin&view=viewsettings&controller=settings&settings=admin_bannedips");
        $submenu->addItem($submenuItem);

        $submenuItem = new NE_MenuItem($user->lang("Captcha"), "index.php?fuse=admin&controller=settings&view=viewsettings&settings=admin_captcha");
        $submenu->addItem($submenuItem);

        $submenuItem = new NE_MenuItem($user->lang("Domain Encryption"), "index.php?fuse=admin&view=domainpasswords&controller=settings&settings=security");
        $submenu->addItem($submenuItem);

        //let's get snapins that have settings here
        $snapin_settings = $sg->getSnapinSettingsForType("security");
        foreach ($snapin_settings as $snapin_setting) {
            $submenuItem = new NE_MenuItem($user->lang($snapin_setting['name']), $snapin_setting['url']);
            $submenu->addItem($submenuItem);
        }

        $menuItem->addSubmenu($submenu);
        $this->addItem($menuItem);


        //Import / Export
        if ($user->hasPermission('admin_view_import_export')) {
            $menuItem = new NE_MenuItem($user->lang('Utilities'), "#");
            $menuItem->setKey("4th");
            $menuItem->addViews(array('viewimportplugins', 'viewexportplugins','showdatabaseoptions'));

            $submenu = new NE_MenuHook($user);

            if ($user->isSuperAdmin()) {
                $submenuItem = new NE_MenuItem($user->lang("SQL Tool"), "index.php?fuse=admin&amp;view=showdatabaseoptions&controller=index");
                $submenu->addItem($submenuItem);
            }

            $submenuItem = new NE_MenuItem($user->lang("Import Data"), "index.php?fuse=admin&view=viewimportplugins&controller=importexport");
            $submenu->addItem($submenuItem);

            $submenuItem = new NE_MenuItem($user->lang("Export Data"), "index.php?fuse=admin&view=viewexportplugins&controller=importexport");
            $submenu->addItem($submenuItem);

            //let's get snapins that have settings here
            $snapin_settings = $sg->getSnapinSettingsForType("utilities");
            foreach ($snapin_settings as $snapin_setting) {
                $submenuItem = new NE_MenuItem($user->lang($snapin_setting['name']), $snapin_setting['url']);
                $submenu->addItem($submenuItem);
            }

            $menuItem->addSubmenu($submenu);
            $this->addItem($menuItem);
        }

        //Files
        /*
        if($user->hasPermission("files_view")){
            $menuItem = new NE_MenuItem($user->lang('Files'),"index.php?fuse=files&amp;view=overview");
            $menuItem->addViews(array('FileEdit','overview'));
            $this->addItem($menuItem);
        }*/
    }
}
