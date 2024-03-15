<?php

include_once 'modules/support/models/EmailRoutingRule.php';

/**
 * Support Module's Email Action Controller
 *
 * @category   Action
 * @package    Support
 * @author     Matt Grandy <matt@clientexec.com
 * @license    http://www.clientexec.com  ClientExec License Agreement
 * @link       http://www.clientexec.com
 */
class Support_EmailController extends CE_Controller_Action {

    public $moduleName = "support";

    protected function pipeemailAction()
    {
        include_once 'modules/support/models/EmailGateway.php';
        $this->disableLayout(true);
        $emailGateway = new EmailGateway($this->user);

        $email = "";
        $fd = fopen("php://stdin", "rb");
        while (!feof($fd)) {
            $email .= fread($fd, 812);
        }
        fclose($fd);

        if ( !$this->settings->get('Email Piping In Use') ) {
            $sql = "UPDATE `setting` SET value=1 WHERE name='Email Piping In Use'";
            $this->db->query($sql);
        }
        $emailGateway->parseEmail($email, EMAILROUTINGRULE_PIPEFORWARDING);
    }
}