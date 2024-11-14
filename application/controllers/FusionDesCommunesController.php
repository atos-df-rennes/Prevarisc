<?php

class FusionDesCommunesController extends Zend_Controller_Action
{
    public function init(): void
    {
        $this->_helper->layout->setLayout('menu_admin');
    }

    public function indexAction(): void
    {
        $form = new Form_FusionCommunes();
        $this->view->assign('form', $form);

        /** @var Zend_Controller_Request_Http $request */
        $request = $this->getRequest();

        if ($request->isPost()) {
            if (!$form->isValid($request->getPost())) {
                $this->render('index');
            }

            if (!$form->fusioncommunes->receive()) {
                throw new Exception('Erreur lors de la réception du fichier');
            }

            $service = new Service_FusionCommand();
            $this->view->assign('hasErrors', $service->mergeArrayCommune(
                json_decode(
                    file_get_contents($form->fusioncommunes->getFileName())
                )
            ));

            unlink($form->fusioncommunes->getFileName());

            $this->view->assign('processComplete', true);
        }
    }
}
