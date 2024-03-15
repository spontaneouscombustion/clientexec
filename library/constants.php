<?php

// Required defines for app to work when config.php missing entries or file is missing altogether
if (!defined('INSTALLED')) {
    define('INSTALLED', false);
}
if (!defined('DEBUG')) {
    define('DEBUG', false);
}
if (!defined('REMOTELOG')) {
    define('REMOTELOG', false);
}
if (!defined('APIKEY')) {
    define('APIKEY', false);
}
if (!defined('SESSION_PATH')) {
    define('SESSION_PATH', false);
}
if (!defined('SALT')) {
    define('SALT', 'canary');
}
if (!defined('DISABLE_CACHING')) {
    define('DISABLE_CACHING', false);
}

if (!defined('SESSION_NAME')) {
    define('SESSION_NAME', 'CLIENTEXEC');
}

if (!defined('RUNNING_SERVICE_SCRIPT')) {
    define('RUNNING_SERVICE_SCRIPT', false);
}

// Fix for PHP < 5.3.6
if (!defined('DEBUG_BACKTRACE_IGNORE_ARGS')) {
    define('DEBUG_BACKTRACE_IGNORE_ARGS', false);
}

if (!defined('HOSTED')) {
    define('HOSTED', false);
}

if (!defined('SAMEORIGIN')) {
    define('SAMEORIGIN', false);
}

// Connection Issue (time out, etc).
define('EXCEPTION_CODE_CONNECTION_ISSUE', 10001);

// Any exception that shouldn't be e-mailed out.
define('EXCEPTION_CODE_NO_EMAIL', 20001);

// Package Status
define('PACKAGE_STATUS_PENDING', 0);
define('PACKAGE_STATUS_ACTIVE', 1);
define('PACKAGE_STATUS_SUSPENDED', 2);
define('PACKAGE_STATUS_CANCELLED', 3);
define('PACKAGE_STATUS_PENDINGCANCELLATION', 4);
define('PACKAGE_STATUS_EXPIRED', 5);

// Package Upgrade Status
define('PACKAGE_UPGRADE_ALL_OK', 1);
define('PACKAGE_UPGRADE_USER_NO_PERMISSION', 2);
define('PACKAGE_UPGRADE_PACKAGE_NOT_OWNED_BY_USER', 3);
define('PACKAGE_UPGRADE_PACKAGE_NO_UPGRADE_OPTIONS', 4);
define('PACKAGE_UPGRADE_PACKAGE_ALREADY_BEING_UPGRADED', 5);
define('PACKAGE_UPGRADE_PACKAGE_NOT_ACTIVE', 6);
define('PACKAGE_UPGRADE_PACKAGE_HAS_INVOICES_AWAITING_PAYMENT', 7);
define('PACKAGE_UPGRADE_PACKAGE_HAS_INVOICES_AWAITING_GENERATION', 8);
define('PACKAGE_UPGRADE_PACKAGE_HAS_NO_INVOICES', 9);
define('PACKAGE_UPGRADE_PACKAGE_HAS_ALL_INVOICES_VOID', 10);
define('PACKAGE_UPGRADE_PACKAGE_HAS_ACTIVE_SUBSCRIPTIONS', 11);

/**
 * User roles
 */
define('ROLE_GUEST', -1);
define('ROLE_ANONYMOUS', 0);
define('ROLE_CUSTOMER', 1);
define('ROLE_SUPERADMIN', 2);
/* * #@- */

define('APIUSERID', -1);

// Package Types
define('PACKAGE_TYPE_GENERAL', 0);
define('PACKAGE_TYPE_HOSTING', 1);
define('PACKAGE_TYPE_SSL', 2);
define('PACKAGE_TYPE_DOMAIN', 3);

/**
 * Custom field type, corresponding to the "type" field in the customuserfields table
 */
define('typeTEXTFIELD', 0);
define('typeADDRESS', 2);
define('typeCITY', 3);
define('typeSTATE', 4);
define('typeZIPCODE', 5);
define('typePHONENUMBER', 7);
define('typeYESNO', 1);
define('typeCOUNTRY', 6);
define('typeLANGUAGE', 8);
define('typeDROPDOWN', 9);
define('typeTEXTAREA', 10);
define('typeFIRSTNAME', 11);
define('typeLASTNAME', 12);
define('typeEMAIL', 13);
define('typeORGANIZATION', 14);
define('typeDATE', 15);
define('TYPE_ALLOW_EMAIL', 16);
define('typeRECORDSPERPAGE', 20);
define('typePAYPALSUBSCRIPTIONS', 24);
define('typePRODUCTSTATUS', 30);
define('typeDASHBOARDLASTUSEDGRAPH', 41);
define('typeVIEWMENU', 42);
define('typeLASTSENTFEEDBACKEMAIL', 43);
define('typeVATNUMBER', 47);
define('typeDASHBOARDSTATE', 48);
define('typePASSWORD', 70);

//100 > are preference customfields
define('typePREFERENCE_TICKETREPLYTOP', 100);
define('typePREFERENCE_SITEDEFAULTACTIVEUSERPANEL', 101);

//200 > are notification customfields
define('typeNOTIFICATION', 200);


/* constants for ui */
define('TYPE_UI_BUTTON', 49);
define('TYPE_UI_DNSENTRY', 50);
define('typeVATVALIDATION', 51);
define('typeHIDDEN', 52);
define('typeCHECK', 53);
define('typeNUMBER', 54);
define('typeNAMESERVER', 55);
define('typeNICKNAME', 61);
define('typeSTATUS', 62);
define('TYPEPASSWORD', 65);

/* * */
/**
 * Define Credit Card Types
 *
 * @access private
 */
define('cCREDITVISA', 0);
define('cCREDITMC', 1);
define('cCREDITAMEX', 2);
define('cCREDITDISC', 3);

define('cCREDITLASER', 4);
define('cCREDITDINERS', 5);
define('cCREDITSWITCH', 6);
/* * */

/**
 *
 * @access private
 */
define('errDuplicateEmail', 0);
define('errDuplicateUserName', 1);
define('errDuplicateDomainName', 2);
define('ERRINCORRECTPASSPHRASE', 3);
define('errCCExpiresInPast', 4);
/* * */

/**
 * User Status
 */
define('USER_STATUS_PENDING', 0);
define('USER_STATUS_ACTIVE', 1);
define('USER_STATUS_INACTIVE', -1);
define('USER_STATUS_CANCELLED', -2);
define('USER_STATUS_FRAUD', -3);
/* * */

/**
 * Affiliate Commission Status
 */
define('COMMISSION_STATUS_PENDING', 0);
define('COMMISSION_STATUS_APPROVED', 1);
define('COMMISSION_STATUS_PAID', 2);
define('COMMISSION_STATUS_DECLINED', 3);
define('COMMISSION_STATUS_PENDING_PAID', 4);
/* * */

/**
 * Affiliate Status
 */
define('AFFILIATE_STATUS_PENDING', 0);
define('AFFILIATE_STATUS_APPROVED', 1);
define('AFFILIATE_STATUS_CANCELLED', 2);
define('AFFILIATE_STATUS_DECLINED', 3);
/* * */

/**
 * Affiliate Pay Type
 */
define('AFFILIATE_DEFAULT_PAY_TYPE', -1);
define('AFFILIATE_RECURRING_PAY_TYPE', 0);
define('AFFILIATE_ONE_TIME_PAY_TYPE', 1);
/* * */

/**
 * User Chat Online Status
 */
define('CHAT_STATUS_AVAILABLE', 0);
define('CHAT_STATUS_BUSY', 1);
define('CHAT_STATUS_AWAY', 2);


/**
  * Cancellation Types
  */
define('PACKAGE_CANCELLATION_TYPE_IMMEDIATE', 1);
define('PACKAGE_CANCELLATION_TYPE_END_BILLING', 2);

/**
 * Event Types
 */
define('EVENT_TYPE_ALL', 1);
define('EVENT_TYPE_INVOICES', 2);
define('EVENT_TYPE_TICKETS', 3);
define('EVENT_TYPE_PROFILE', 4);
define('EVENT_TYPE_PACKAGE', 5);
define('EVENT_TYPE_ORDER', 6);

/**
 * Ticket Status
 */
define('TICKET_STATUS_UNASSIGNED', 0);
define('TICKET_STATUS_OPEN', 1);
define('TICKET_STATUS_WAITINGONTECH', 2);
define('TICKET_STATUS_WAITINGONCUSTOMER', 3);
define('TICKET_STATUS_CLOSED', -1);

/**
 * Ticket Rating
 */
define('TICKET_RATE_NO', 0);
define('TICKET_RATE_OUTSTANDING', 1);
define('TICKET_RATE_GOOD', 2);
define('TICKET_RATE_MEDIOCRE', 3);
define('TICKET_RATE_POOR', 4);

/**
 * Custom Status Types (Aliases)
 */
define('ALIAS_STATUS_PACKAGE', 1);
define('ALIAS_STATUS_TICKET', 2);
define('ALIAS_STATUS_USER', 3);

define('REGEXDOMAIN_PARSLEY', '^([a-zA-Z0-9]([a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?\.)+[a-zA-Z]{2,24}$');
define('REGEXSUBDOMAIN_PARSLEY', '^([a-zA-Z0-9]([a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?)+$');

/**
 * Translations types
 */
define('ANNOUNCEMENT_TITLE', 1);
define('ANNOUNCEMENT_EXCERPT', 2);
define('ANNOUNCEMENT_CONTENT', 3);
define('PRODUCT_GROUP_NAME', 4);
define('PRODUCT_GROUP_DESCRIPTION', 5);
define('PRODUCT_NAME', 6);
define('PRODUCT_DESCRIPTION', 7);
define('PRODUCT_ASSET', 8);
define('ADDON_NAME', 9);
define('ADDON_DESCRIPTION', 10);
define('ADDON_OPTION_LABEL', 11);
define('EMAIL_NAME', 12);
define('EMAIL_SUBJECT', 13);
define('EMAIL_CONTENT', 14);
define('SETTING_VALUE', 15);
define('KNOWLEDGE_BASE_CATEGORY_NAME', 16);
define('KNOWLEDGE_BASE_CATEGORY_DESCRIPTION', 17);
define('KNOWLEDGE_BASE_ARTICLE_TITLE', 18);
define('KNOWLEDGE_BASE_ARTICLE_CONTENT', 19);

/**
 * Prices types
 */
define('PRODUCT_PRICE', 1);
define('ADDON_PRICE', 2);
define('BILLING_TYPE_PRICE', 3);
define('COUPON_PRICE', 4);

define('STRIPE_API_VERSION', '2022-11-15');
define('STRIPE_PARTNER_ID', 'pp_partner_KkKlx865iQQYoF');

/**
 * isvat Exception class. Make sure you try catch this
 */
class isvatException extends Exception
{
    /**
     * Exception constructor. Use is to set the error message format.
     */
    public function __construct($code, $info, $url = null)
    {

        $message = "isvat error: code=$code, info=$info";

        //Include URL in message if supplied
        if (!empty($url)) {
            $message .= " url=$url";
        }

        parent::__construct($message, (int)$code);
    }
}

class CE_Exception extends Exception
{

}
class CE_ExceptionPermissionDenied extends Exception
{
}
class CE_PackageException extends Exception
{
}
