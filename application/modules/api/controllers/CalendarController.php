<?php

class Api_CalendarController extends Zend_Controller_Action
{
    public function indexAction(): void
    {
        $idCommission = 0;
        if ($this->getRequest()->getParam('commission')) {
            $idCommission = $this->getRequest()->getParam('commission');
        }

        $headers = ['Content-Type: text/Calendar; charset=utf-8'];

        $this->view->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
        $server = new SDIS62_Rest_Server();
        $server->setClass('Api_Service_Calendar');
        $server->setHeaders($headers);
        $server->setJsonResponse(false);
        $server->handle($this->getRequest()->getParams());
    }
}
