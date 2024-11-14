<?php

class ErrorController extends Zend_Controller_Action
{
    public function errorAction(): void
    {
        $this->_helper->layout->setLayout('error');

        $errors = $this->_getParam('error_handler');

        if (
            !$errors
            || !$errors instanceof ArrayObject
        ) {
            $this->view->assign('message', "Vous avez atteint la page d'erreur");

            return;
        }

        // On envoie le bon code erreur en fonction du type
        switch ($errors->type) {
            case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_ROUTE:
            case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_CONTROLLER:
            case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_ACTION:
                // Type de l'erreur :  404 error
                $this->getResponse()->setHttpResponseCode(404);
                $priority = Zend_Log::NOTICE;
                $this->view->assign('message', 'Page introuvable');

                break;

            case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_OTHER:
                $priority = Zend_Log::ERR;
                $unautorizedStatusCode = 401;

                if ($unautorizedStatusCode == $errors->exception->getCode()) {
                    $this->getResponse()->setHttpResponseCode($unautorizedStatusCode);
                    $priority = Zend_Log::NOTICE;
                    $this->render('not-allowed');
                } else {
                    $this->view->assign('message', $errors->exception->getMessage());
                }

                break;

            default:
                // Type de l'erreur : application error
                $this->getResponse()->setHttpResponseCode(500);
                $priority = Zend_Log::CRIT;
                $this->view->assign('message', 'L\'application a levée une erreur');

                break;
        }

        // Log exception, if logger available
        if ($log = $this->getLog()) {
            $log->log($this->view->message."\n".$errors->exception, $priority);
            $log->log("Request Parameters\n".print_r($errors->request->getParams(), true), $priority);
        }

        // Si l'affichage des exceptions est activé, on envoie un message
        $this->view->assign('showException', $this->getInvokeArg('displayExceptions'));
        $this->view->assign('exception', $errors->exception);

        // On envoie la requête de l'erreur sur la vue
        $this->view->assign('request', $errors->request);
    }

    /**
     * Récupération des logs.
     *
     * @return false|Zend_Log
     */
    public function getLog()
    {
        $bootstrap = $this->getInvokeArg('bootstrap');

        if (!$bootstrap->hasResource('Log')) {
            return false;
        }

        return $bootstrap->getResource('Log');
    }
}
