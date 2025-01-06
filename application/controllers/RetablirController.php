<?php

class RetablirController extends Zend_Controller_Action
{
    public function indexAction(): void
    {
        $this->_helper->layout->setLayout('menu_admin');

        /** @var Zend_View $view */
        $view = $this->view;
        $view->headLink()->appendStylesheet('/css/elements-supprimes.css', 'all');

        $DBDossier = new Model_DbTable_Dossier();
        $DBEtablissement = new Model_DbTable_Etablissement();
        $servicePrivilege = new Service_Privilege();

        $dossiersSupprimes = $DBDossier->getDeleteDossier();
        $etablissementsSupprimes = $DBEtablissement->getDeleteEtablissement();

        $this->view->assign('dossiersSupprimes', $dossiersSupprimes);
        $this->view->assign('etablissementsSupprimes', $etablissementsSupprimes);
        $this->view->assign('is_allowed_delete_dossier', $servicePrivilege->isAllowed('suppression', 'delete_dossier'));
        $this->view->assign('is_allowed_delete_etablissement', $servicePrivilege->isAllowed('suppression', 'delete_etablissement'));
    }
}
