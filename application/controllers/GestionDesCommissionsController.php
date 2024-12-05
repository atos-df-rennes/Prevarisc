<?php

class GestionDesCommissionsController extends Zend_Controller_Action
{
    public function indexAction(): void
    {
        // Titre
        $this->view->assign('title', 'Gestion des commissions');

        $this->_helper->layout->setLayout('menu_admin');

        // Modèles de données
        $model_typesDesCommissions = new Model_DbTable_CommissionType();

        $this->view->assign('rowset_typesDesCommissions', $model_typesDesCommissions->fetchAll());
    }

    public function formAction(): void
    {
        // Modèles de données
        $model_typesDesCommissions = new Model_DbTable_CommissionType();
        $model_commissions = new Model_DbTable_Commission();

        $this->view->assign('rowset_typesDesCommissions', $model_typesDesCommissions->fetchAll());
        $this->view->assign('rowset_commissions', $model_commissions->fetchAll());
    }

    public function saveAction(): void
    {
        try {
            $this->_helper->viewRenderer->setNoRender();

            // Modèles de données
            $model_commissions = new Model_DbTable_Commission();

            // Sauvegarde
            foreach ($this->_request->id_commission as $i => $singleId_commission) {
                if (0 != $_POST['id_commission'][$i]) {
                    $item = $model_commissions->find($_POST['id_commission'][$i])->current();
                    $item->ID_COMMISSIONTYPE = $_POST['idtype_commission'][$i];
                    $item->LIBELLE_COMMISSION = $_POST['nom_commission'][$i];
                    $item->save();
                } else {
                    $item = $model_commissions->createRow();
                    $item->ID_COMMISSIONTYPE = $_POST['idtype_commission'][$i];
                    $item->LIBELLE_COMMISSION = $_POST['nom_commission'][$i];
                    $item->save();
                }

                $dossier = REAL_DATA_PATH.DS.'uploads'.DS.'documents'.DS.$item->ID_COMMISSION;
                if (!is_dir($dossier)) {
                    mkdir($dossier);
                }
            }

            $this->_helper->flashMessenger([
                'context' => 'success',
                'title' => 'Les informations ont été sauvegardées',
                'message' => '',
            ]);
        } catch (Exception $exception) {
            $this->_helper->flashMessenger([
                'context' => 'error',
                'title' => 'Erreur lors de la sauvegarde',
                'message' => $exception->getMessage(),
            ]);
        }
    }

    public function getCommissionsAction(): void
    {
        // Modèles de données
        $model_commission = new Model_DbTable_Commission();
        $model_typesDesCommissions = new Model_DbTable_CommissionType();

        // On récupère les commissions du type demandé
        $this->view->assign('rowset_commissions', $model_commission->fetchAll('ID_COMMISSIONTYPE = '.$this->_request->id_type_des_commissions));

        // On récupère le type
        $this->view->assign('row_typeDesCommissions', $model_typesDesCommissions->fetchRow('ID_COMMISSIONTYPE = '.$this->_request->id_type_des_commissions));
    }

    public function addCommissionAction(): void
    {
        try {
            // Modèle
            $DB_commission = new Model_DbTable_Commission();

            // Si on sauvegarde, on désactive le rendu
            if (
                isset($_GET['action'])
                && 'save' == $_GET['action']
            ) {
                $this->_helper->viewRenderer->setNoRender(true);
            }

            if (!empty($this->_request->cid)) {
                $this->view->assign('commission', $DB_commission->find($this->_request->cid)->current());

                if (
                    isset($_GET['action'])
                    && 'save' == $_GET['action']
                ) {
                    $this->view->commission->setFromArray(array_intersect_key($_POST, $DB_commission->info('metadata')))->save();
                }
            } elseif (
                isset($_GET['action'])
                && 'save' == $_GET['action']
            ) {
                $DB_commission->insert(array_intersect_key($_POST, $DB_commission->info('metadata')));
            }

            $this->view->assign('tid', $_GET['tid']);

            $this->_helper->flashMessenger([
                'context' => 'success',
                'title' => 'La commission a bien été sauvegardée',
                'message' => '',
            ]);
        } catch (Exception $exception) {
            $this->_helper->flashMessenger([
                'context' => 'error',
                'title' => 'Erreur lors de la sauvegarde',
                'message' => $exception->getMessage(),
            ]);
        }
    }
}
