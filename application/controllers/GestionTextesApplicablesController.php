<?php

class GestionTextesApplicablesController extends Zend_Controller_Action
{
    public function indexAction(): void
    {
        $this->_helper->layout->setLayout('menu_admin');

        // on commence par afficher tous les texte applicables regroupés par leurs type
        $dbTextesAppl = new Model_DbTable_TextesAppl();
        $this->view->assign('listeTextesAppl', $dbTextesAppl->recupTextesAppl());
    }

    public function formtexteapplAction(): void
    {
        // Cas d'une création d'un texte
        $dbTypeTextesAppl = new Model_DbTable_TypeTextesAppl();
        $this->view->assign('listeType', $dbTypeTextesAppl->getType());
        if ($this->_getParam('id')) {
            $this->view->assign('idTexteAppl', $this->_getParam('id'));
            $dbTextesAppl = new Model_DbTable_TextesAppl();
            $this->view->assign('texteEdit', $dbTextesAppl->find($this->_getParam('id')));
        }
    }

    public function saveAction(): void
    {
        try {
            // sauvegarde d'un nouveau texte ou mise à jour d'un texte existant
            $dbTextesAppl = new Model_DbTable_TextesAppl();
            if ($this->_getParam('idTexteAppl')) {
                // cas d'une édition
                $rowEdit = $dbTextesAppl->find($this->_getParam('idTexteAppl'))->current();
                $rowEdit['LIBELLE_TEXTESAPPL'] = $this->_getParam('libelle');
                $rowEdit['VISIBLE_TEXTESAPPL'] = $this->_getParam('visible');
                $rowEdit['ID_TYPETEXTEAPPL'] = $this->_getParam('type');
                $rowEdit['NUM_TEXTESAPPL'] = '99999';
                $rowEdit->save();
            } else {
                // cas d'une création
                $newRow = $dbTextesAppl->createRow();
                $newRow['LIBELLE_TEXTESAPPL'] = $this->_getParam('libelle');
                $newRow['VISIBLE_TEXTESAPPL'] = $this->_getParam('visible');
                $newRow['ID_TYPETEXTEAPPL'] = $this->_getParam('type');
                $newRow['NUM_TEXTESAPPL'] = '99999';
                $newRow->save();
            }

            if ('yes' == $this->_getParam('defPrescription')) {
                // on enregistre le texte dans la table prescriptiontexteliste
                $dbPrescTextes = new Model_DbTable_PrescriptionTexteListe();
                $newTexte = $dbPrescTextes->createRow();
                $newTexte->LIBELLE_TEXTE = $this->_getParam('libelle');
                $newTexte->save();
            }

            $this->_helper->flashMessenger([
                'context' => 'success',
                'title' => 'Le texte a bien été sauvegardé',
                'message' => '',
            ]);
        } catch (Exception $exception) {
            $this->_helper->flashMessenger([
                'context' => 'error',
                'title' => 'Erreur lors de la sauvegarde du texte',
                'message' => $exception->getMessage(),
            ]);
        }

        // Redirection
        $this->_helper->redirector('index');
    }

    public function updateorderAction(): void
    {
        $this->_helper->viewRenderer->setNoRender();
        $tabId = explode(',', $this->_getParam('tableUpdate'));
        $dbTexteAppl = new Model_DbTable_TextesAppl();
        $num = 0;
        foreach ($tabId as $id) {
            $updateTexteAppl = $dbTexteAppl->find($id)->current();
            $updateTexteAppl->NUM_TEXTESAPPL = $num;
            $updateTexteAppl->save();
            ++$num;
        }
    }
}
