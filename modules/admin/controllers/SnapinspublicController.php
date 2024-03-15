<?php

class Admin_SnapinspublicController extends CE_Controller_Action
{
    var $moduleName = "admin";


    protected function doactionAction()
    {
        $pluginName = $this->getParam('plugin', FILTER_SANITIZE_STRING);
        $pluginType = $this->getParam('type', FILTER_SANITIZE_STRING);
        $functionName = $this->getParam('do', FILTER_SANITIZE_STRING);

        if (CE_Lib::hasDots($pluginName)) {
            CE_Lib::log(1, '*** Possible path injection attack detected at index.php?fuse=admin&controller=snapinspublic&action=snapin. Currently logged in user:'.$this->user->getId().'('.$this->user->getFullName().')');
            exit;
        }

        $path = "plugins/{$pluginType}/{$pluginName}";
        $pluginClass = 'Plugin' . ucfirst(strtolower($pluginName));

        if (file_exists($path . "/$pluginClass.php")) {
            include_once($path . "/$pluginClass.php");
            $plugin = new $pluginClass($this->user, 1);
            $plugin->setInternalName($pluginName);
            try {
                $response = $plugin->$functionName($_REQUEST);
                $this->message = $response['message'];
                $this->send($response['data']);
            } catch (CE_Exception $e) {
                $this->error = 1;
                $this->message = $e->getMessage();
                $this->send();
            }
        }
    }

    protected function snapinAction()
    {

        $plugin_name = $this->getParam('plugin', FILTER_SANITIZE_STRING);
        if (CE_Lib::hasDots($plugin_name)) {
            CE_Lib::log(1, '*** Possible path injection attack detected at index.php?fuse=admin&controller=snapinspublic&action=snapin. Currently logged in user:'.$this->user->getId().'('.$this->user->getFullName().')');
            exit;
        }

        $plugin = null;

        if (!$this->user->canUseSnapin($plugin_name)) {
            CE_Lib::addErrorMessage("<b>".$this->user->lang("Access denied")."</b><br/>".$this->user->lang("You must be logged in before using that feature"));
            CE_Lib::redirectPage("index.php?fuse=home&view=dashboard");
        }

        //create plugin
        $path = 'plugins/snapin/'.$plugin_name;

        $pluginClass = 'Plugin'.ucfirst(strtolower($plugin_name));
        if (file_exists($path."/$pluginClass.php")) {
            @include_once($path."/$pluginClass.php");
            $plugin = new $pluginClass($this->user, 1);
            $plugin->setInternalName($plugin_name);
            $plugin->setTemplate($this->view);
        } else {
            CE_Lib::addErrorMessage("Plugin ".$plugin_name." at ".$path."/$pluginClass.php does not exist");
            CE_Lib::redirectPage("index.php");
        }

        $vars = $plugin->getVariables();
        $hash = $this->getParam('h', FILTER_SANITIZE_STRING, "");
        if ($hash == "") {
            $hash = $this->getParam('v');
            $hash = base64_encode("view:".$hash);
        }
        $view = $plugin->matchViewByHash($hash);

        $this->title = $view['title'];
        $plugin->setMatching($view);
        $loadassets = false;
        $output = $plugin->mapped_view($loadassets);
        if ($loadassets) {
            //let's see if we have js file and css file for this tab
            if (file_exists("plugins/snapin/".$plugin_name."/".$view['tpl'].".js")) {
                $this->jsLibs[] = "plugins/snapin/".$plugin_name."/".$view['tpl'].".js";
            }
            if (file_exists("plugins/snapin/".$plugin_name."/".$view['tpl'].".css")) {
                $this->cssPages[] = "plugins/snapin/".$plugin_name."/".$view['tpl'].".css";
            }
        }

        echo $output;
    }
}
