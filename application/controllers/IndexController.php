<?php

class IndexController extends Zend_Controller_Action
{
    /**
     * @var Service_Platau
     */
    public $servicePlatau;

    public function init(): void
    {
        /** @var Zend_Controller_Action_Helper_ContextSwitch $ajaxContext */
        $ajaxContext = $this->_helper->getHelper('AjaxContext');
        $ajaxContext
            ->addActionContext('platauHealthcheck', 'json')
            ->initContext()
        ;

        $this->servicePlatau = new Service_Platau();
    }

    public function indexAction(): void
    {
        $service_user = new Service_User();
        $service_dashboard = new Service_Dashboard();
        $blocsConfig = $service_dashboard->getBlocConfig();

        $identity = Zend_Auth::getInstance()->getIdentity();
        $user = $service_user->find($identity['ID_UTILISATEUR']);

        $cache = Zend_Controller_Front::getInstance()->getParam('bootstrap')->getResource('cache');
        $acl = unserialize($cache->load('acl'));
        $profil = $user['group']['LIBELLE_GROUPE'];
        $blocs = [];

        foreach ($blocsConfig as $blocId => $blocConfig) {
            if (
                !$blocConfig['acl']
                || $acl->isAllowed($profil, $blocConfig['acl'][0], $blocConfig['acl'][1])
            ) {
                $method = $blocConfig['method'];
                $methodCount = $method.'Count';
                $serviceCount = new Service_Count();
                $blocs[$blocId] = [
                    'type' => $blocConfig['type'],
                    'title' => $blocConfig['title'],
                    'count' => $serviceCount->{$methodCount}($user),
                    'height' => $blocConfig['height'],
                    'width' => $blocConfig['width'],
                ];
            }
        }

        // determine the bloc order
        // user preferences
        if (
            isset($user['preferences']['DASHBOARD_BLOCS'])
            && $user['preferences']['DASHBOARD_BLOCS']
            && $blocsOrder = json_decode($user['preferences']['DASHBOARD_BLOCS'])
        ) {
            // treat the case where there will be new bloc added
            foreach (array_keys($blocsConfig) as $defaultBloc) {
                if (!in_array($defaultBloc, $blocsOrder)) {
                    $blocsOrder[] = $defaultBloc;
                }
            }
        } else {
            $blocsOrder = array_keys($blocsConfig);
        }

        $this->view->assign('user', $user);
        $this->view->assign('blocs', $blocs);
        $this->view->assign('blocsOrder', $blocsOrder);
        $this->view->inlineScript()->appendFile('/js/jquery.packery.pkgd.min.js');
        $this->_helper->layout->setLayout('index');
        $this->render('index');
    }

    public function blocAction(): void
    {
        $this->_helper->layout->disableLayout();

        $id = $this->getParam('id');

        $bloc = [];
        $service_user = new Service_User();
        $service_dashboard = new Service_Dashboard();
        $blocsConfig = $service_dashboard->getBlocConfig();

        if (isset($blocsConfig[$id])) {
            $blocConfig = $blocsConfig[$id];
            $identity = Zend_Auth::getInstance()->getIdentity();
            $user = $service_user->find($identity['ID_UTILISATEUR']);
            $service = new $blocConfig['service']();
            $method = $blocConfig['method'];
            $bloc = [
                'id' => $id,
                'data' => $service->{$method}($user),
                'type' => $blocConfig['type'],
                'title' => $blocConfig['title'],
                'height' => $blocConfig['height'],
                'width' => $blocConfig['width'],
            ];
        }

        if ('dossierPlatau' === $id) {
            $serviceNotification = new Service_Notification();
            $serviceNotification->setLastPageVisitDate(Service_Notification::DASHBOARD_DOSSIER_SESSION_NAMESPACE);
        }

        $this->view->assign('bloc', $bloc);
    }

    public function addMessageAction(): void
    {
        $service_feed = new Service_Feed();
        $service_user = new Service_User();

        $this->view->assign('groupes', $service_user->getAllGroupes());
        if ($this->_request->isPost()) {
            try {
                $service_feed->addMessage($this->_request->getParam('type'), $this->_request->getParam('text'), Zend_Auth::getInstance()->getIdentity()['ID_UTILISATEUR'], $this->_request->getParam('conf'));
                $this->_helper->flashMessenger(['context' => 'success', 'title' => 'Message ajouté !', 'message' => 'Le message a bien été ajouté.']);
            } catch (Exception $e) {
                $this->_helper->flashMessenger(['context' => 'danger', 'title' => 'Erreur !', 'message' => "Erreur lors de l'ajout du message : ".$e->getMessage()]);
            }

            $this->_helper->redirector('index', 'index');
        }
    }

    public function deleteMessageAction(): void
    {
        $service_feed = new Service_Feed();

        try {
            $service_feed->deleteMessage($this->_request->getParam('id'));
            $this->_helper->flashMessenger(['context' => 'success', 'title' => 'Message supprimé !', 'message' => 'Le message a bien été supprimé.']);
        } catch (Exception $exception) {
            $this->_helper->flashMessenger(['context' => 'danger', 'title' => 'Erreur !', 'message' => 'Erreur lors de la suppression du message : '.$exception->getMessage()]);
        }

        $this->_helper->redirector('index', 'index');
    }

    public function platauHealthcheckAction(): void
    {
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender();

        echo json_encode($this->servicePlatau->executeHealthcheck());
    }
}
