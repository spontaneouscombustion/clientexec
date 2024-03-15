<?php

/**
 * Admin Module's Action Controller
 *
 * @category   Action
 * @package    Home
 * @author     Alberto Vasquez <alberto@clientexec.com>
 * @license    http://www.clientexec.com  ClientExec License Agreement
 * @link       http://www.clientexec.com
 */
class Admin_PluginpublicController extends CE_Controller_Action
{
    var $moduleName = "admin";

     /**
     * Perform an action inside the plugin code
     * This is used so that we can keep plugins self contained
     * as much as possible
     *
     * @allowapi true
     * Param of dashboard and snapins are supported defaults to dashboard (sidebar extension)
     */
    public function dopluginAction($callback=false)
    {

        $this->_helper->viewRenderer->setNoRender(true);            
        if (!isset($_GET['view'])) {
            $this->_helper->layout()->disableLayout();
        }

        //should have snapins for snapin actions
        $plugintype = $this->getRequest()->getParam('type','dashboard');
        $pluginName = $this->getRequest()->getParam('plugin');
        if (CE_Lib::hasDots($plugintype) || CE_Lib::hasDots($pluginName)) {
            CE_Lib::log(1, '*** Possible path injection attack detected at index.php?fuse=admin&controller=pluginpublic&action=doplugin. Currently logged in user:'.$this->user->getId().'('.$this->user->getFullName().')');
            exit;
        }

        if ($pluginName == "") {
            throw new CE_Exception("Plugin value required");
        }
        
        try {
            $path = 'plugins/'.$plugintype.'/'.$pluginName;
            $pluginClass = 'Plugin'.ucfirst(strtolower($pluginName));
            @include_once ($path."/$pluginClass.php");
            $plugin = new $pluginClass($this->user,1);
            $plugin->setTemplate($this->view);
            $plugin->setInternalName($pluginName);
            if ($callback) {
                return $plugin->callAction(true);
            } else if (NE_API){

                $returnData = $plugin->callAction(true);
                if (gettype($returnData) == "NULL") {
                    $this->send(array());
                } else {
                    $this->send($returnData);
                }

            } else {
                echo $plugin->callAction(false);
            }
        } catch (Exception $ex) {
            if (NE_API){
                NE_Rest::sendResponse(401);
                return;
            } else {
                CE_Lib::log(1,$ex->getMessage());
                return null;
            }
        }

        //let's see if we have any csspages or jslibs to add
        if (isset($_GET['view'])) {
        	// register any required javascript libraries
            // we should only be loading javascript files for selected plugins
            $jsLibs = $plugin->getJsLibs();
            if (is_array($jsLibs) && sizeof($jsLibs) > 0) {
                foreach ($jsLibs as $jsLib) {
                    if (!in_array($jsLib, $this->jsLibs))
                        $this->jsLibs[] = $jsLib;
                }
            }

            // register any required css files
            $cssPages = $plugin->getCssPages();            
            if (is_array($cssPages) && sizeof($cssPages) > 0) {
                foreach ($cssPages as $cssPage) {
                    if (!in_array($cssPage, $this->cssPages))
                        $this->cssPages[] = $cssPage;
                }
            }

        }

    }


}
