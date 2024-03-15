<?php
/**
 * Announcement File
 *
 * @category Model
 * @package  Admin
 * @author   Alberto Vasquez <alberto@clientexec.com>
 * @license  ClientExec License
 * @version  [someversion]
 * @link     http://www.clientexec.com
 */

require_once 'library/CE/NE_ActiveRecord.php';
require_once 'modules/admin/models/StatusAliasGateway.php';


define('RECIPIENTS_PUBLIC', 0);
define('RECIPIENTS_CLIENT_STATUS', 1);
define('RECIPIENTS_USERS', 2);
define('RECIPIENTS_SERVERS', 3);
define('RECIPIENTS_PRODUCT_GROUPS', 4);
define('RECIPIENTS_PRODUCTS', 5);
define('RECIPIENTS_CUSTOMER_GROUPS', 6);

/**
 * Announcement Model Class
 *
 * @category Model
 * @package  Admin
 * @author   Alberto Vasquez <alberto@clientexec.com>
 * @license  ClientExec License
 * @version  [someversion]
 * @link     http://www.clientexec.com
 */
class Announcement extends NE_ActiveRecord
{
    var $tableName = 'announcement';

    var $fields = array(
        'id'        => null,
        'title'     => '',
        'excerpt'   => '',
        'post'      => '',
        'postdate'  => null,
        'publish'   => 0,
        'authorid'  => 0,
        'recipient' => 0,
        'pinned'    => 0,
    );

    var $recipientsType;
    var $recipientsIds;

    /**
     * Announcement constructor
     *
     * @param int $tID id of announcement
     */
    function __construct($tID = false)
    {
        parent::__construct($tID);

        if ($tID) {
            $query = "SELECT recipient_id "
                ."FROM announcement_recipient "
                ."WHERE ann_id = ? ";
            $result = $this->db->query($query, $tID);

            $recipientsIds = array();

            while ($row = $result->fetch()) {
                $recipientsIds[] = $row['recipient_id'];
            }

            $this->setRecipientsIds($recipientsIds);
        }
    }

    /**
     * Property get for title
     *
     * @return string title of announcement
     */
    function getTitle()
    {
        return $this->fields['title'];
    }

    /**
     * Property set for title
     *
     * @param int $value title to save
     *
     * @return void
     */
    function setTitle($value)
    {
        $this->fields['title'] = $value;
        $this->dirty = true;
    }

    /**
     * Property get for Excerpt
     *
     * @return string
     */
    function getExcerpt()
    {
        return $this->fields['excerpt'];
    }

    /**
     * Property Set for Excerpt
     *
     * @param string $value excerpt
     *
     * @return void
     */
    function setExcerpt($value)
    {
        $this->fields['excerpt'] = $value;
        $this->dirty = true;
    }

    /**
     * Property get for Post
     *
     * @return string
     */
    function getPost()
    {
        return $this->fields['post'];
    }

    /**
     * Property set for Post
     *
     * @param string $value post
     *
     * @return void
     */
    function setPost($value)
    {
        $this->fields['post'] = $value;
        $this->dirty = true;
    }

    /**
     * Property get for Post Date
     *
     * @return <type>
     */
    function getPostDate()
    {
        return $this->fields['postdate'];
    }

    /**
     * Property set for Post Date
     *
     * @param <type> $value post date
     *
     * @return void
     */
    function setPostDate($value)
    {
        $this->fields['postdate'] = $value;
        $this->dirty = true;
    }

    /**
     * Property get for Publish
     *
     * @return string
     */
    function getPublish()
    {
        return $this->fields['publish'];
    }

    /**
     * Property set for Pinned
     *
     * @param string $value publish
     *
     * @return void
     */
    function setPublish($value)
    {
        $this->fields['publish'] = $value;
        $this->dirty = true;
    }

      /**
     * Property get for Pinned
     *
     * @return string
     */
    function getPinned()
    {
        return $this->fields['pinned'];
    }

    /**
     * Property set for Pinned
     *
     * @param boolean $value pinned
     *
     * @return void
     */
    function setPinned($value)
    {
        $this->fields['pinned'] = $value;
        $this->dirty = true;
    }

    /**
     * Property get for AuthorId
     *
     * @return string
     */
    function getAuthorID()
    {
        return $this->fields['authorid'];
    }

    /**
     * Property set AuthorId
     *
     * @param string $value author id
     *
     * @return void
     */
    function setAuthorID($value)
    {
        $this->fields['authorid'] = $value;
        $this->dirty = true;
    }

    /**
     * Property get Recipient
     *
     * @return string
     */
    function getRecipient()
    {
        return $this->fields['recipient'];
    }

    /**
     * Property set Recipient
     *
     * @param string $value recipient
     *
     * @return void
     */
    function setRecipient($value)
    {
        $this->fields['recipient'] = $value;
        $this->dirty = true;
    }

    /**
     * Property set RecipientsType
     *
     * @param string $value recipients type
     *
     * @return void
     */
    function setRecipientsType($value)
    {
        $this->recipientsType = $value;
    }

    /**
     * Property set for RecipientsIds
     *
     * @param string $value recipients ids
     *
     * @return void
     */
    function setRecipientsIds($value)
    {
        $this->recipientsIds = $value;
    }

    /**
     * Property get for ReceipientsIds
     *
     * @return string
     */
    function getRecipientsIds()
    {
        $return = array();
        switch ($this->fields['recipient']) {
            case RECIPIENTS_CLIENT_STATUS:
                include_once 'modules/clients/models/UserGateway.php';
                $userGateway = new UserGateway($this->user);
                foreach ($this->recipientsIds as $rID) {
                    $return[] = array( 'id' => $rID, 'text' => $userGateway->getStatusName($rID));
                }
                break;
            case RECIPIENTS_SERVERS:
                $recipients = $this->db->query('SELECT `id`, `name` FROM `server` WHERE `id` IN ('.implode(',', $this->recipientsIds).')');
                $recipientArray = array();
                while ($recipient = $recipients->fetch()) {
                    $recipientArray[$recipient['id']] = $recipient['name'];
                }
                foreach ($this->recipientsIds as $rID) {
                    $return[] = array('id' => $rID, 'text' => $recipientArray[$rID]);
                }
                break;
            case RECIPIENTS_PRODUCT_GROUPS:
                $recipients = $this->db->query('SELECT `id`, `name` FROM `promotion` WHERE `id` IN ('.implode(',', $this->recipientsIds).')');
                $recipientArray = array();
                while ($recipient = $recipients->fetch()) {
                    $recipientArray[$recipient['id']] = $recipient['name'];
                }
                foreach ($this->recipientsIds as $rID) {
                    $return[] = array('id' => $rID, 'text' => $recipientArray[$rID]);
                }
                break;
            case RECIPIENTS_PRODUCTS:
                $recipients = $this->db->query('SELECT `id`, `planname` FROM `package` WHERE `id` IN ('.implode(',', $this->recipientsIds).')');
                $recipientArray = array();
                while ($recipient = $recipients->fetch()) {
                    $recipientArray[$recipient['id']] = $recipient['planname'];
                }
                foreach ($this->recipientsIds as $rID) {
                    $return[] = array('id' => $rID, 'text' => $recipientArray[$rID]);
                }
                break;
            case RECIPIENTS_CUSTOMER_GROUPS:
                $recipients = $this->db->query('SELECT `id`, `name` FROM `groups` WHERE `id` IN ('.implode(',', $this->recipientsIds).')');
                $recipientArray = array();
                while ($recipient = $recipients->fetch()) {
                    $recipientArray[$recipient['id']] = $recipient['name'];
                }
                foreach ($this->recipientsIds as $rID) {
                    $return[] = array('id' => $rID, 'text' => $recipientArray[$rID]);
                }
                break;
        }

        return $return;
        //return $this->recipientsIds;
    }

    /**
     * Save Model
     *
     * @return void
     */
    function save()
    {

        parent::save();

        $query = "DELETE "
            ."FROM announcement_recipient "
            ."WHERE ann_id = ? ";
        $result = $this->db->query($query, $this->getId());

        if (count($this->recipientsIds) > 0 && $this->recipientsType > 0) {
            $values = array();
            $vars   = array();

            foreach ($this->recipientsIds as $recipientId) {
                $vars[]   = '(?, ?)';
                $values[] = $this->getId();
                $values[] = $recipientId;
            }

            $vars = implode(', ', $vars);

            $query = 'INSERT INTO announcement_recipient '
                .'(ann_id, recipient_id) '
                .'VALUES '.$vars;
            $result = $this->db->query($query, $values);
        }
    }

    /**
     * Delete Model
     *
     * @return void
     */
    function delete()
    {
        $query = "DELETE "
            ."FROM announcement_recipient "
            ."WHERE ann_id = ? ";
        $this->db->query($query, $this->getId());

        include_once 'modules/admin/models/Translations.php';
        $translations = new Translations();
        $translations->deleteAllValues(ANNOUNCEMENT_TITLE, $this->getId());
        $translations->deleteAllValues(ANNOUNCEMENT_EXCERPT, $this->getId());
        $translations->deleteAllValues(ANNOUNCEMENT_CONTENT, $this->getId());

        parent::delete();
    }

    /**
     * Send Announcement
     *
     * @param bool $overrideOptOut override opt out?
     *
     * @return int
     */
    function send($overrideOptOut = false)
    {
        include_once 'library/CE/NE_MailGateway.php';
        include_once 'modules/admin/models/StatusAliasGateway.php' ;

        $userIds = array();
        $userStatuses = StatusAliasGateway::userActiveAliases($this->user);
        $statuses = StatusAliasGateway::packageActiveAliases($this->user);

        switch ($this->recipientsType) {
            case RECIPIENTS_PUBLIC:
                $sql = "SELECT u.id "
                ."FROM users u LEFT JOIN `groups` g ON u.groupid=g.id "
                ."WHERE u.status IN(".implode(', ', $userStatuses).") "
                ."AND g.iscustomersmaingroup=1";
                $result = $this->db->query($sql);

                while ($row = $result->fetch()) {
                    $userIds[] = $row['id'];
                }

                break;
            case RECIPIENTS_CLIENT_STATUS:
                if (count($this->recipientsIds) == 0) {
                    break;
                }

                $marks = implode(', ', array_fill(0, count($this->recipientsIds), "?"));

                $sql = "SELECT u.id "
                ."FROM users u LEFT JOIN `groups` g ON u.groupid=g.id "
                ."WHERE u.status IN ($marks) "
                ."AND g.iscustomersmaingroup=1 ";
                $result = $this->db->query($sql, (array)$this->recipientsIds);

                while ($row = $result->fetch()) {
                    $userIds[] = $row['id'];
                }

                break;

            case RECIPIENTS_USERS:
                $userIds = $this->recipientsIds;

                break;

            case RECIPIENTS_SERVERS:
                if (count($this->recipientsIds) == 0) {
                    break;
                }

                $marks = implode(', ', array_fill(0, count($this->recipientsIds), "?"));

                $sql = "SELECT DISTINCT u.id "
                ."FROM users u, domains d, object_customField ocf "
                ."WHERE d.CustomerID = u.id "

                ."AND d.id = ocf.objectid "
                ."AND ocf.customFieldId = (SELECT cf.id "
                ."FROM customField cf "
                ."WHERE cf.name = 'Server Id' "
                ."AND cf.groupid = 2 "
                ."and cf.subGroupId = 1 "
                ."Limit 1) "
                ."AND ocf.value IN (".$marks.") "

                ."AND u.status IN(".implode(', ', $userStatuses).") "
                ."AND d.status IN(".implode(', ', $statuses).") ";
                $result = $this->db->query($sql, $this->recipientsIds);

                while ($row = $result->fetch()) {
                    $userIds[] = $row['id'];
                }

                break;

            case RECIPIENTS_PRODUCT_GROUPS:
                if (count($this->recipientsIds) == 0) {
                    break;
                }

                $marks = implode(', ', array_fill(0, count($this->recipientsIds), "?"));

                $sql = "SELECT DISTINCT u.id "
                ."FROM users u, domains d, package p "
                ."WHERE d.CustomerID = u.id "

                ."AND d.Plan = p.id "
                ."AND p.planid IN (".$marks.") "

                ."AND u.status IN(".implode(', ', $userStatuses).") "
                ."AND d.status IN(".implode(', ', $statuses).") ";
                $result = $this->db->query($sql, $this->recipientsIds);

                while ($row = $result->fetch()) {
                    $userIds[] = $row['id'];
                }

                break;

            case RECIPIENTS_PRODUCTS:
                if (count($this->recipientsIds) == 0) {
                    break;
                }

                $marks = implode(', ', array_fill(0, count($this->recipientsIds), "?"));

                $sql = "SELECT DISTINCT u.id "
                ."FROM users u, domains d "
                ."WHERE d.CustomerID = u.id "

                ."AND d.Plan IN (".$marks.") "

                ."AND u.status IN(".implode(', ', $userStatuses).") "
                ."AND d.status IN(".implode(', ', $statuses).") ";
                $result = $this->db->query($sql, $this->recipientsIds);

                while ($row = $result->fetch()) {
                    $userIds[] = $row['id'];
                }

                break;

            case RECIPIENTS_CUSTOMER_GROUPS:
                if (count($this->recipientsIds) == 0) {
                    break;
                }

                $marks = implode(', ', array_fill(0, count($this->recipientsIds), "?"));

                $sql = "SELECT DISTINCT u.id "
                ."FROM users u, user_groups ug "
                ."WHERE ug.user_id = u.id "

                ."AND ug.group_id IN (".$marks.") "

                ."AND u.status IN(".implode(', ', $userStatuses).") ";
                $result = $this->db->query($sql, $this->recipientsIds);

                while ($row = $result->fetch()) {
                    $userIds[] = $row['id'];
                }

                break;
        }

        if (count($userIds) > 0) {
            if (!$overrideOptOut) {
                $sql = "SELECT id "
                    ."FROM customuserfields "
                    ."WHERE type = ? ";
                $result = $this->db->query($sql, TYPE_ALLOW_EMAIL);

                $row = $result->fetch();

                $strUserIds = implode(",", $userIds);

                $sql = "SELECT userid "
                    ."FROM user_customuserfields "
                    ."WHERE customid = ? "
                    ."AND value = 1 "
                    ."AND userid IN ($strUserIds) ";
                $result = $this->db->query($sql, $row['id']);

                unset($userIds);

                $userIds = array();

                while ($row = $result->fetch()) {
                    $userIds[] = $row['userid'];
                }
            }

            if (count($userIds) == 0) {
                return 0;
            }

            $languages = CE_Lib::getEnabledLanguages();
            if (count($languages) > 1) {
                $sql = "SELECT id "
                    ."FROM customuserfields "
                    ."WHERE type = ? ";
                $result = $this->db->query($sql, typeLANGUAGE);

                $row = $result->fetch();

                $strUserIds = implode(",", $userIds);

                $sql = "SELECT userid, value "
                    ."FROM user_customuserfields "
                    ."WHERE customid = ? "
                    ."AND userid IN ($strUserIds) ";
                $result = $this->db->query($sql, $row['id']);

                unset($userIds);

                $userIds = array();

                while ($row = $result->fetch()) {
                    $userIds[$row['value']][] = $row['userid'];
                }
            }

            if (count($userIds) == 0) {
                return 0;
            }

            $mailGateway = new NE_MailGateway();
            $datestr = date($this->settings->get('Date Format')." h:i A");

            //This is used to fix the images URLs when emailing an Announcement
            $clientExecURL = CE_Lib::getSoftwareURL();
            $urlFix = mb_substr($clientExecURL, -1, 1) == "//" ? '' : '/';
            $knowledgebaseImageUrl = $clientExecURL.$urlFix.'uploads/knowledgebase/';

            if (count($languages) > 1) {
                include_once 'modules/admin/models/Translations.php';
                $translations = new Translations();

                foreach ($userIds as $language => $languageUserIds) {
                    $user = new user($languageUserIds[0]);
                    $message = $this->settings->get('Company Name')." ".$user->lang("System Message").":<br />\n<br />\n"
                        ."$datestr<br />\n"
                        .$translations->getValue(ANNOUNCEMENT_CONTENT, $this->getId(), $language, $this->getPost());

                    $mailGateway->mailMessage(
                        array(
                        'HTML'      => str_replace('img src="../uploads/knowledgebase/', 'img src="'.$knowledgebaseImageUrl, $message),
                        'plainText' => null
                        ),
                        $this->settings->get('Support E-mail'),
                        $this->settings->get('Company Name'),
                        $languageUserIds,
                        0,
                        $translations->getValue(ANNOUNCEMENT_TITLE, $this->getID(), $language, $this->getTitle()),
                        3,
                        0,
                        'notifications',
                        '',
                        '',
                        MAILGATEWAY_CONTENTTYPE_HTML
                    );
                }
            } else {
                $user = new user($userIds[0]);
                $message = $this->settings->get('Company Name')." ".$user->lang("System Message").":<br />\n<br />\n"
                    ."$datestr<br />\n"
                    .$this->getPost();

                $mailGateway->mailMessage(
                    array(
                    'HTML'      => str_replace('img src="../uploads/knowledgebase/', 'img src="'.$knowledgebaseImageUrl, $message),
                    'plainText' => null
                    ),
                    $this->settings->get('Support E-mail'),
                    $this->settings->get('Company Name'),
                    $userIds,
                    0,
                    $this->getTitle(),
                    3,
                    0,
                    'notifications',
                    '',
                    '',
                    MAILGATEWAY_CONTENTTYPE_HTML
                );
            }
        }

        return count($userIds);
    }
}
