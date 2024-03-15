<?php

require_once 'modules/admin/models/AnnouncementGateway.php';
require_once 'modules/admin/models/Announcements.php';

/**
 * Home Module's Calendar Action Controller
 *
 * @category   Action
 * @package    Home
 * @author     Alberto Vasquez <alberto@clientexec.com>
 * @author     Rick Goodrow <rick@clientexec.com>
 * @version    2.0 (2013-01-07)
 * @license    http://www.clientexec.com  ClientExec License Agreement
 * @link       http://www.clientexec.com
 */
class Home_AnnouncementspublicController extends CE_Controller_Action
{
    public $moduleName = 'home';

    protected function announcementAction()
    {
        $this->checkPermissions('clients_view_announcements');
        $languages = CE_Lib::getEnabledLanguages();
        include_once 'modules/admin/models/Translations.php';
        $translations = new Translations();
        $languageKey = ucfirst(strtolower($this->user->getLanguage()));

        $this->title = $this->user->lang('Announcement');

        // Check the URL input
        if (isset($_GET['ann_id']) && ((int)$_GET['ann_id'] <= 0)) {
            CE_Lib::log(1, '****XSS Attack Detected at index.php?fuse=home&view=announcements&controller=announcements. Currently logged in user:' . $this->user->getId() . ' (' . $this->user->getFullName() . ')');
            CE_Lib::redirectPage('index.php?fuse=home&view=announcements&controller=announcements', 'The requested announcement does not exist.');
        }

        $this->cssPages = array("templates/default/views/home/announcementspublic/announcements.css");

        $ann_id = $this->getParam('ann_id', FILTER_SANITIZE_NUMBER_INT);
        $announcement = new Announcement($ann_id);

        //
        $announcements = new Announcements($this->user);
        $annList = $announcements->getHomeAnnouncements($this->user->getId(), $this->user->isAdmin(), false);
        $show = false;

        //Is the announcement viewable by this user?
        if (count($annList) > 0) {
            foreach ($annList as $row) {
                if ($row['id'] == $ann_id) {
                    $show = true;
                    break;
                }
            }
        }

        // do not show if the announcement is not viewable by this user
        if (!$show) {
            CE_Lib::redirectPage('index.php?fuse=home&view=announcements&controller=announcements', 'The requested announcement does not exist.');
        }

        include_once 'library/CE/NE_MailGateway.php';
        $mailgateway = new NE_MailGateway($this->user);

        $author = new User($announcement->getAuthorID());

        if (count($languages) > 1) {
            $this->view->title = $translations->getValue(ANNOUNCEMENT_TITLE, $ann_id, $languageKey, $announcement->getTitle());
            $post              = $translations->getValue(ANNOUNCEMENT_CONTENT, $ann_id, $languageKey, $announcement->getPost());
        } else {
            $this->view->title = $announcement->getTitle();
            $post = $announcement->getPost();
        }
        if (!$this->user->isAdmin() && !$this->user->isAnonymous()) {
            $post = $mailgateway->replaceMailTags($post, $this->user);
        }
        $this->view->post = $post;

        $this->view->date = $this->user->lang(date('F', strtotime($announcement->getPostDate())))
            . ' ' . date('j, Y', strtotime($announcement->getPostDate()));
        $this->view->currentId = $announcement->getId();
        $this->view->postedBy = $author->getFirstName() . ' ' . $author->getLastName();

        $annGateway = new AnnouncementGateway($this->user);
        $this->view->previousId = $annGateway->get_previous_announcement_id($ann_id);
        $this->view->nextId = $annGateway->get_next_announcement_id($ann_id);
        if ($this->view->previousId == null) {
            $this->view->prevlink = 'javascript: void(0);';
        } else {
            $this->view->prevlink = 'index.php?fuse=home&controller=announcements&view=announcement&ann_id=' . $this->view->previousId;
        }
        if ($this->view->nextId == null) {
            $this->view->nextlink = 'javascript: void(0);';
        } else {
            $this->view->nextlink = 'index.php?fuse=home&controller=announcements&view=announcement&ann_id=' . $this->view->nextId;
        }
    }

    protected function announcementsAction()
    {
        $this->checkPermissions('clients_view_announcements');
        $this->title = $this->user->lang('Annoucements');
        $this->cssPages = array("templates/default/views/home/announcementspublic/announcements.css");

        $error = false;
        $this->view->companyName = $this->settings->get('Company Name');
        $announcements = new Announcements($this->user);
        $annList = $announcements->getHomeAnnouncements($this->user->getId(), $this->user->isAdmin(), false);
        $this->view->announcements = array();

        include_once 'library/CE/NE_MailGateway.php';
        $mailgateway = new NE_MailGateway($this->user);

        if (count($annList) > 0) {
            foreach ($annList as $row) {
                $announcement = array();
                $author = new User($row['authorid']);
                $announcement['date'] = $this->user->lang(date('F', strtotime($row['postdate'])))
                    . ' ' . date('j, Y', strtotime($row['postdate']));
                $announcement['title'] = $row['title'];

                if (!$this->user->isAdmin() && !$this->user->isAnonymous()) {
                    $row['excerpt'] = $mailgateway->replaceMailTags($row['excerpt'], $this->user);
                    $row['post'] = $mailgateway->replaceMailTags($row['post'], $this->user);
                }
                $announcement['post'] = $row['post'];
                $announcement['excerpt'] = $row['excerpt'];
                $announcement['postedBy'] = $author->getFirstName() . ' ' . $author->getLastName();
                $announcement['id'] = $row['id'];
                $this->view->announcements[] = $announcement;
            }
        } else {
            $url = 'index.php';
            if ($this->user->isRegistered()) {
                $url = 'index.php?fuse=home&view=dashboard';
            }
             CE_Lib::redirectPage(
                 $url,
                 $this->user->lang('The are no announcements to show.')
             );
        }
    }
}
