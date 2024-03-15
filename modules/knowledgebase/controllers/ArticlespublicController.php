<?php

include_once 'modules/knowledgebase/models/KB_CategoryGateway.php';

// ToDo: Move this to a DB Setting
define('KB_AUTOSUGGESTION_NUMARTICLES', 10);

/**
 * Knowledgebase Module's Action Controller
 *
 * @category   Action
 * @package    Knowledgebase
 * @author     Alberto Vasquez <alberto@clientexec.com>
 * @license    http://www.clientexec.com  ClientExec License Agreement
 * @link       http://www.clientexec.com
 */
class Knowledgebase_ArticlespublicController extends CE_Controller_Action
{
    public $moduleName = "knowledgebase";

    protected function mainAction()
    {
        $this->checkPermissions('knowledgebase_view');

        $articleGateway = new KB_ArticleGateway($this->user);
        if ($articleGateway->getCountOfViewableArticles() == 0) {
            $url = 'index.php';
            if ($this->user->isRegistered()) {
                $url = 'index.php?fuse=home&view=dashboard';
            }
            CE_Lib::redirectPage(
                $url,
                $this->user->lang('The are no knowledgebase articles available.')
            );
        }

        $languages = CE_Lib::getEnabledLanguages();
        include_once 'modules/admin/models/Translations.php';
        $translations = new Translations();
        $languageKey = ucfirst(strtolower($this->user->getLanguage()));

        $this->title = $this->user->lang('Knowledgebase');

        $this->cssPages = array("templates/default/views/knowledgebase/articlespublic/main.css");
        $this->jsLibs = array("templates/default/views/knowledgebase/articlespublic/main.js");

        $catGateway = new KB_CategoryGateway($this->user);
        $this->view->categories = array();
        $this->view->categoryId = $this->getParam('categoryId', FILTER_SANITIZE_NUMBER_INT, 0);

        $category = new KB_Category($this->view->categoryId);
        $categoryName = html_entity_decode($category->getName(), ENT_QUOTES);
        if (count($languages) > 1) {
            if ($this->view->categoryId !== false) {
                $categoryName = html_entity_decode($translations->getValue(KNOWLEDGE_BASE_CATEGORY_NAME, $this->view->categoryId, $languageKey, $categoryName), ENT_QUOTES);
            }
        }
        $this->view->categoryName = $categoryName;

        $this->categoryData[$this->view->categoryId] = $category;
        if ($this->view->categoryId <= 0 || !$category->existsInDB()) {
            $categoryId = -1;
            $this->view->category = array();
        } else {
            $categoryId = $this->view->categoryId;
            $this->view->category = $catGateway->processCategory($categoryId, $languageKey);
        }
        $this->view->categories = $catGateway->processRowSubcategories($categoryId, $languageKey);
    }

    protected function searchAction()
    {
        $languages = CE_Lib::getEnabledLanguages();
        include_once 'modules/admin/models/Translations.php';
        $translations = new Translations();
        $languageKey = ucfirst(strtolower($this->user->getLanguage()));

        $this->checkPermissions('knowledgebase_view');
        $this->title = $this->user->lang('Knowledgebase Search');
        $this->cssPages = array("templates/default/views/knowledgebase/articlespublic/main.css");
        $searchText = $this->getParam('query', FILTER_SANITIZE_STRING, "");

        //search widget passes subject and not query
        if ($searchText == "") {
            $searchText = $this->getParam('subject', FILTER_SANITIZE_STRING);
        }

        $this->view->searchText = $searchText;

        $this->view->articles = array();

        if (!empty($searchText)) {
            $new_string = '';
            foreach (array_unique(explode(" ", $searchText)) as $array => $value) {
                if (strlen($value) >= 3) {
                    $new_string .= '' . $value . ' ';
                }
            }
            $new_string = mb_substr($new_string, 0, ( strLen($new_string) - 1 ));

            if (strlen($new_string) > 3) {
                $articleGateway = new KB_ArticleGateway();
                $articles = $articleGateway->simpleArticlesSearch($new_string, $this->user, KB_AUTOSUGGESTION_NUMARTICLES);
                if ($articles) {
                    foreach ($articles as $article) {
                        $tempArticle = array();
                        $tempArticle['id'] = $article['id'];

                        $artName = $article['title'];
                        if (count($languages) > 1) {
                            if ($tempArticle['id'] !== false) {
                                $artName = $translations->getValue(KNOWLEDGE_BASE_ARTICLE_TITLE, $tempArticle['id'], $languageKey, $artName);
                            }
                        }
                        $tempArticle['title'] = $artName;

                        $tempArticle['excerpt'] = $article['excerpt'];
                        $tempArticle['created'] = $article['created'];
                        $tempArticle['author'] = $article['author'];
                        $tempArticle['href'] = $article['href'];

                        $this->view->articles[] = $tempArticle;
                    }
                }
            }
        }
    }


    /**
     * Update an article's content and last update date
     *
     * @return json
     */
    protected function updatearticlecontentAction()
    {
        $this->message = $this->user->lang("Article was updated successfully");

        $articleId = $this->getRequest()->getParam('articleId');
        $html = $this->getRequest()->getParam('articleContent');

        $article = new KB_Article($articleId);
        $article->setModifiedUserId($this->user->getID());
        $article->setContent($html);
        $article->setModified(strftime('%Y-%m-%d %H:%M:%S'));
        $article->save();


        $this->send(array("html" => $html));
    }

    /**
     * Rating article from public view
     * @return [type] [description]
     */
    protected function rateAction()
    {

        $articleId = $this->getParam('articleId', FILTER_SANITIZE_NUMBER_INT);
        $rating = $this->getParam('rating', FILTER_SANITIZE_NUMBER_INT);

        $artGateway = new KB_ArticleGateway();
        $article = new KB_Article($articleId);
        $retArray = array();

        if ($rating > 5) {
            $this->error = true;
            $this->message = $this->user->lang("Invalid Rating");
            $this->send();
            return;
        }
        if (!$article->existsInDB()) {
            $this->error = true;
            $this->message = $this->user->lang("That article does not exist");
            $this->send();
            return;
        }


        $ip = $_SERVER['REMOTE_ADDR'];
        if ($artGateway->checkUsedIP($articleId, $ip)) {
            $this->error = true;
            $this->message = $this->user->lang("This ip address was already used to rate the article");
        } else {
            if ($article->getRating() != 0) {
                $article->setRating((($article->getRating() * $article->getRatingVisitors()) + $rating) / ($article->getRatingVisitors() + 1));
                $article->setRatingVisitors($article->getRatingVisitors() + 1);
                $article->save();
                $type = 2;
            } else {
                $article->setRating($rating);
                $article->setRatingVisitors($article->getRatingVisitors() + 1);
                $article->save();
                $type = 1;
            }
            $tense = ($article->getRatingVisitors() == 1) ? "vote" : "votes";
            $artGateway->updateUsedIPs($articleId, $ip);
            $retArray = [
                "newRating" => round($article->getRating(), 1),
                "reviewcount" => $article->getRatingVisitors(),
                "votes" => $tense
            ];
            $this->message = $this->user->lang("Thank you for rating this article");
        }

        $this->send($retArray);
    }

    /**
     * Add comment to kb article
     */
    protected function addcommentAction()
    {
        $article = new KB_Article($_REQUEST['articleId']);
        $url = $article->generateLink();

        $_REQUEST['userEmail'] = urldecode($_REQUEST['userEmail']);

        $captchaPlugin = $this->settings->get('Enabled Captcha Plugin');
        if ($this->user->isAnonymous() && $this->user->hasPermission('knowledgebase_postComments') && $this->settings->get('Show Captcha on KB Article Comments') == 1 && $captchaPlugin != '' && $captchaPlugin != 'disabled') {
            $pluginGateway = new PluginGateway($this->user);
            $plugin = $pluginGateway->getPluginByName('captcha', $captchaPlugin);
            if (!$plugin->verify($_REQUEST)) {
                CE_Lib::addErrorMessage($this->user->lang('Failed Captcha'));
                CE_Lib::redirectPage("index.php?fuse=knowledgebase&view=article&controller=articles&articleId={$_REQUEST['articleId']}");
            }
        }

        //validate fields
        $requiredFields = array('articleId', 'userName', 'userEmail', 'userComment');
        foreach ($requiredFields as $requiredField) {
            if (!isset($_REQUEST[$requiredField]) or empty($_REQUEST[$requiredField])) {
                CE_Lib::redirectPage($url, $this->user->lang('Please fill in all required fields.'));
            }
        }

        if (!CE_Lib::valid_email($_REQUEST['userEmail'])) {
               CE_Lib::redirectPage($url, $this->user->lang('Please enter a valid email address.'));
        }

        if (!$this->user->isRegistered()) {
            include_once 'modules/clients/models/UserGateway.php';
            $userGateway = new UserGateway($this->user);
            $userIdForEmail = $userGateway->searchUserByEmail($_REQUEST['userEmail'], true, false);
            if ($userIdForEmail > 0) {
                CE_Lib::redirectPage($url, $this->user->lang('Please login to comment as this user.'));
            }
        }


        //insert comment
        $query = 'INSERT INTO kb_articles_comments (articleid, username, added, email, comment, is_approved, is_internal) VALUES (?, ?, NOW(), ?, ?, ?, ?)';
        $isApproved = $this->settings->get('Publish Comments Automatically');
        if ($this->user->isAdmin()) {
            $isApproved = 1;
        }
        $isInternal = ($this->user->isAdmin() && isset($_POST['commentIsInternal']) && $_POST['commentIsInternal']) ? 1 : 0;
        $this->db->query($query, $_REQUEST['articleId'], $_REQUEST['userName'], $_REQUEST['userEmail'], $_REQUEST['userComment'], $isApproved, $isInternal);

        // check if we were automatically approved or not.
        if (!$isApproved) {
            $message = $this->user->lang('Thank you for the reply.&nbsp;&nbsp;This system requires moderation of posts.');
        } else {
            $message = $this->user->lang('Thank you for the reply.');
        }

        CE_Lib::redirectPage($url, $message);
    }


    /**
     * viewing Article
     * @return void
     */
    protected function articleAction()
    {
        $this->checkPermissions('knowledgebase_viewArticle');
        $this->title = $this->user->lang('Knowledgebase Article');

        include_once 'modules/admin/models/Translations.php';

        $languages = CE_Lib::getEnabledLanguages();
        $translations = new Translations();
        $languageKey = ucfirst(strtolower($this->user->getLanguage()));
        $this->view->articleId = $this->getParam('articleId', FILTER_SANITIZE_NUMBER_INT, 0);

        $article = new KB_Article($this->view->articleId);
        $artGateway = new KB_ArticleGateway($this->user);

        $category = $article->getCategories();
        $category = $category[0];

        //let's get series links
        $seriesName = "";
        if ($category['is_global_series']) {
            // get global series
            $this->view->series = $artGateway->getAllSeries(false, $this->view->articleId);
            $seriesName = $this->settings->get('Global Series Name');
            $GlobalSeriesNameSettingId = $this->settings->getSettingIdForName('Global Series Name');

            if (count($languages) > 1) {
                if ($GlobalSeriesNameSettingId !== false) {
                    $seriesName = $translations->getValue(SETTING_VALUE, $GlobalSeriesNameSettingId, $languageKey, $seriesName);
                }
            }
        } elseif ($category['is_series']) {
            $this->view->series = $artGateway->getAllSeries($category['categoryId']);
        } else {
            $this->view->series = [];
        }

        $this->view->links = [
            'previous' => [],
            'next' => []
        ];
        if (count($this->view->series) > 0) {
            foreach ($this->view->series as $key => $articles) {
                foreach ($articles['articles'] as $art) {
                    if ($this->view->articleId == $art['art_id']) {
                        $this->view->links['previous']['url'] = $art['art_previous_url'];
                        $this->view->links['previous']['name'] = $art['art_previous_name'];

                        $this->view->links['next']['url'] = $art['art_next_url'];
                        $this->view->links['next']['name'] = $art['art_next_name'];
                    }
                }
            }
        }

        if (!$article->existsInDB()) {
            //article does not exist .. we should send to 404 error page
            CE_Lib::redirectPage(CE_Lib::generateMainKBLink() . '?notfound=true');
        }

        //let's get access information of article
        if (($this->user->isAnonymous() && $article->getAccess() != KB_ARTICLE_ACCESS_PUBLIC) || (!$this->user->isAdmin() && !in_array($article->getAccess(), array(KB_ARTICLE_ACCESS_PUBLIC, KB_ARTICLE_ACCESS_MEMBERS)))) {
            CE_Lib::redirectPage(CE_Lib::generateMainKBLink() . '?permissiondenied=true');
        }


        // CE_Lib::debug($category);
        //let's check if we have permission for the category we are viewing
        if (!$this->user->isAdmin()) {
            if ($category['staffOnly']) {
                CE_Lib::redirectPage(CE_Lib::generateMainKBLink() . '?permissiondenied=true');
            }
        }

        if ($article->isDraft() && !$this->user->isAdmin()) {
            CE_Lib::redirectPage(CE_Lib::generateMainKBLink() . '?permissiondenied=true');
        }

        $this->title = $article->getTitle() .  ' - ' . $this->title;
        $this->title = html_entity_decode($this->title, ENT_QUOTES);

        if (!$article->getUsedIpToViewArticles($_SERVER['REMOTE_ADDR'], $this->user)) {
            $article->setTotalVisitors($article->getTotalVisitors() + 1);
            $article->setUsedIpToViewArticles($_SERVER['REMOTE_ADDR'], $this->user);
        }

        $ip_num = preg_replace("/[^0-9\.]/", "", $_SERVER['REMOTE_ADDR']);
        $ip = $_SERVER['REMOTE_ADDR'];

        $this->view->userCanRate = true;
        $this->view->category = $category;
        $this->view->categoryId = $category['categoryId'];
        $this->view->alreadyRated = false;

        if (!$artGateway->checkUsedIP($this->view->articleId, $ip)) {
            $barinfo = $artGateway->getRatingBarInfo($article, 5);
            if ($this->user->getId() == $article->getPublisher()) {
                $this->view->userCanRate = false;
            }
        } else {
            $barinfo = $artGateway->getRatingBarInfo($article, 5);
            $this->view->alreadyRated = true;
        }

        $this->view->assign($barinfo);

        $artEditLink = "";
        if (!$article->isTicketSummary() && $this->user->hasPermission('knowledgebase_manageArticles')) {
            //$artEditLink = "<a href='index.php?fuse=knowledgebase&amp;view=KB_AddArticle&amp;articleId=".$this->article->getId()."'>[".$this->user->lang("edit article")."]</a>";
        }

        $excerpt = $article->getExcerpt();

        if ($excerpt == "") {
            $excerpt = $this->user->lang("None Available");
        }

        $author = new User($article->getPublisher());
        $this->view->articleId = $article->getId();
        $this->view->articleCreated = $article->getCreated();

        $this->view->articleModified = date('F j, Y', strtotime($article->getModifiedRaw()));

        $this->view->articleRating = round($article->getRating());
        $this->view->articleTypeClass = $article->articleTypeClass();

        $artName = html_entity_decode($article->getTitle(), ENT_QUOTES);
        $aContent = $article->getContent();
        if (count($languages) > 1) {
            if ($this->view->articleId !== false) {
                $artName = html_entity_decode($translations->getValue(KNOWLEDGE_BASE_ARTICLE_TITLE, $this->view->articleId, $languageKey, $artName), ENT_QUOTES);
                $aContent = $translations->getValue(KNOWLEDGE_BASE_ARTICLE_CONTENT, $this->view->articleId, $languageKey, $aContent);
            }
        }
        if (isset($_REQUEST['searchstring'])) {
            $words = explode(" ", $_REQUEST['searchstring']);
            $aContent = $artGateway->highlightContent($aContent, $words);
        }

        // replace relative image with full path
        $aContent = str_replace('<img src="../uploads/knowledgebase/', '<img src="' . CE_Lib::getSoftwareURL() . '/uploads/knowledgebase/', $aContent);

        $this->view->articleName = $artName;
        $this->view->articleDescription = $aContent;

        $this->view->articleEditLink = $artEditLink;

        $this->view->articleAuthor = $article->getAuthor();

        // Fix to stop avatar showing when the publisher mismatches the author for old articles
        if ($this->view->articleAuthor == $author->getFirstName() . " " . $author->getLastName()) {
            $this->view->articleAuthorEmail = $author->getEmail();
        } else {
            $this->view->articleAuthorEmail = '';
        }

        $categoryName = html_entity_decode($category['categoryName'], ENT_QUOTES);
        if (count($languages) > 1) {
            if ($category['categoryId'] !== false) {
                $categoryName = html_entity_decode($translations->getValue(KNOWLEDGE_BASE_CATEGORY_NAME, $category['categoryId'], $languageKey, $categoryName), ENT_QUOTES);
            }
        }

        $this->view->categoryName = ($seriesName == "") ? $categoryName : $seriesName;
        $this->view->assign($artGateway->parseRelatedArticlesSection($article));
        $this->view->assign($artGateway->parseComments($article));
        // $this->view->assign($artGateway->parseAttachmentsSection($article));

        $this->view->showArticleForm = false;
        if ($this->user->hasPermission('knowledgebase_postComments')) {
            $this->view->showArticleForm = true;
        }

        $this->view->showCaptcha = false;
        $captchaPlugin = $this->settings->get('Enabled Captcha Plugin');
        if ($this->user->isAnonymous() && $this->view->showArticleForm && $this->settings->get('Show Captcha on KB Article Comments') == 1 && $captchaPlugin != '') {
            $pluginGateway = new PluginGateway($this->user);
            $this->view->showCaptcha = true;

            $plugin = $pluginGateway->getPluginByName('captcha', $captchaPlugin);
            $plugin->setTemplate($this->view);
            $this->view->captchaHtml = $plugin->view();
        }

        $article->save();


        $requestUri = $this->getRequest()->getRequestUri();
        $this->view->seoLinks = $this->settings->get('Enable SEO Links') && strpos($requestUri, 'knowledge-base') > 0;

        if ($this->view->hasArticleComments) {
            if (count($this->view->ArticleComments) == 1) {
                $commentsText = $this->user->lang('1 Comment');
            } else {
                $commentsText = $this->user->lang('%s Comments', count($this->view->ArticleComments));
            }
        } else {
            $commentsText = $this->user->lang('0 Comments');
        }
        $this->view->commentsText = $commentsText;
    }
}
