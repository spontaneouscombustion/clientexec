<?php
include_once 'modules/knowledgebase/models/KB_ArticleGateway.php';

/**
 * Knowledgebase Module's Action Controller
 *
 * @category   Action
 * @package    Knowledgebase
 * @author     Alberto Vasquez <alberto@clientexec.com>
 * @license    http://www.clientexec.com  ClientExec License Agreement
 * @link       http://www.clientexec.com
 */
class Knowledgebase_IndexpublicController extends CE_Controller_Action {

    var $moduleName = "knowledgebase";


    protected function livearticlesearchAction()
    {
        if (!isset($_REQUEST['limit'])) {
            $_REQUEST['limit'] = 15;
        }
        $numarticles = filter_var($_REQUEST['limit'], FILTER_SANITIZE_NUMBER_INT);

        $path = $this->getParam('path',FILTER_SANITIZE_STRING,"");
        $articles = array();
        $searchText = $this->getParam('subject',FILTER_SANITIZE_STRING,"");

        if( $searchText != "") {
            $new_string = '';
            foreach ( array_unique ( explode ( " ",$searchText) ) as $array => $value ){
                if(strlen($value) >= 3){
                    $new_string .= '+'.$value.' ';
                }
            }
            $new_string = mb_substr ( $new_string,0, ( strLen ( $new_string ) -1 ) );
            if ( strlen ( $new_string ) > 3 ){
                $articleGateway = new KB_ArticleGateway();
                $articles = $articleGateway->simpleArticlesSearch($new_string, $this->user, $numarticles);
            }
        }

        $this->send(array("articles"=>$articles));

    }

    /**
     * Retrieve list of articles based on autosuggestion
     *
     * @return json
     */
    protected function getautosuggetarticesAction() {

        $articleGateway = new KB_ArticleGateway();
        if (!isset($_REQUEST['subject']))
            $_REQUEST['subject'] = "";
        if (!isset($_REQUEST['limit']))
            $_REQUEST['limit'] = 5;
        if (!isset($_REQUEST['start']))
            $_REQUEST['start'] = 0;
        $subject = filter_var($_REQUEST['subject'], FILTER_SANITIZE_STRING);
        $limit = filter_var($_REQUEST['limit'], FILTER_SANITIZE_NUMBER_INT);
        $start = filter_var($_REQUEST['start'], FILTER_SANITIZE_NUMBER_INT);
        $totalEntries = 0;
        $articles = $articleGateway->simpleArticlesSearch($subject, $this->user, $limit, $totalEntries, $start);

        $this->send(array("entries" => $articles, "total_entries" => $totalEntries, "moreResultsURL" => "testing.html"));
    }

}