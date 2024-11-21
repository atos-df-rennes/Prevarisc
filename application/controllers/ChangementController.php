<?php

class ChangementController extends Zend_Controller_Action
{
    public function indexAction(): void
    {
        $this->_helper->layout->setLayout('menu_admin');
        $this->view->headScript()->appendFile('js/tinymce.min.js');

        $request = $this->getRequest();
        $serviceChangement = new Service_Changement();

        if ($request->isPost()) {
            try {
                $post = $request->getPost();
                $serviceChangement->save($post);
                $this->_helper->flashMessenger([
                    'context' => 'success',
                    'title' => 'Mise à jour réussie !',
                    'message' => 'Les messages d\'alerte ont bien été mis à jour.',
                ]);
                $this->_helper->redirector('index', null, null, ['id' => $request->getParam('id')]);
            } catch (Exception $e) {
                $this->_helper->flashMessenger([
                    'context' => 'error',
                    'title' => '',
                    'message' => 'Les messages d\'alerte n\'ont pas été mis à jour. Veuillez rééssayez. ('.$e->getMessage().')',
                ]);
            }
        }

        $this->view->assign('changements', $serviceChangement->getAll());
    }

    public function alerteformAction(): void
    {
        $serviceEtablissement = new Service_Etablissement();
        $serviceChangement = new Service_Changement();
        $serviceUser = new Service_User();

        $etablissement = $serviceEtablissement->get(
            $this->_getParam('id_etablissement')
        );

        $changement = $serviceChangement->get($this->_getParam('changement'));

        $users = $serviceUser->getUtilisateursForAlterte(
            (int) $changement['ID_CHANGEMENT'],
            $etablissement
        );

        $mails = [];
        $tos = [];
        foreach ($users as $user) {
            $mails[] = $user['MAIL_UTILISATEURINFORMATIONS'];
            $tos[] = sprintf(
                '<span id="dst_%s">%s, %s <a class="remove-dst" href="%s/%s">Retirer</a></span>',
                $user['ID_UTILISATEUR'],
                $user['NOM_UTILISATEURINFORMATIONS'],
                $user['PRENOM_UTILISATEURINFORMATIONS'],
                $user['ID_UTILISATEUR'],
                $user['MAIL_UTILISATEURINFORMATIONS']
            );
        }

        $this->view->assign('tos', implode(', ', $tos));
        $this->view->assign('mails', implode(';', $mails));

        $this->view->assign('objet', $serviceChangement->getObjet(
            (int) $changement['ID_CHANGEMENT'],
            $etablissement
        ));

        $this->view->assign('message', $serviceChangement->convertMessage(
            $changement['MESSAGE_CHANGEMENT'],
            $etablissement
        ));
    }

    public function balisesAction(): void
    {
        $serviceChangement = new Service_Changement();
        $this->view->assign('balises', $serviceChangement->getBalises());
    }

    public function sendmailalerteAction(): void
    {
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $tos = $this->_getParam('mail-emails-dst');

        $result = false;

        if ('' !== $tos) {
            $arrayMails = explode(';', $tos);
            $object = $this->_getParam('alerte-objet');
            $message = $this->_getParam('alerte-message');

            $serviceMail = new Service_Mail();
            $result = $serviceMail->sendAlerteMail($object, $message, $arrayMails);
        }

        echo Zend_Json::encode($result);
    }
}
