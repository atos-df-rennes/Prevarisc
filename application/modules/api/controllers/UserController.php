<?php

class Api_UserController extends Zend_Controller_Action
{
    public function indexAction(): void
    {
        header('Content-type: application/json');

        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $server = new SDIS62_Rest_Server();
        $server->setClass('Api_Service_User');
        $server->handle($this->getRequest()->getParams());
    }
}
