<?php

class Plugin_Security extends Zend_Controller_Plugin_Abstract
{
    public function preDispatch(Zend_Controller_Request_Abstract $request): void
    {
        if ('error' != $request->getControllerName()) {
            $params = array_merge($request->getParams(), $_GET, $_POST);

            $filters = [new Zend_Filter_HtmlEntities(), new Zend_Filter_StripTags()];

            $input = new Zend_Filter_Input($filters, [], $params);

            if (@!$input->isValid()) {
                $redirector = Zend_Controller_Action_HelperBroker::getStaticHelper('redirector');
                $redirector->gotoRouteAndExit([], 'error', true);
            }
        }
    }
}
