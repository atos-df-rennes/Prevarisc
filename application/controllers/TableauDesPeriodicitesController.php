<?php

class TableauDesPeriodicitesController extends Zend_Controller_Action
{
    public function indexAction(): void
    {
        // Définition du layout
        $this->_helper->layout->setLayout('menu_admin');

        // Liste des types d'activité
        $activite_model = new Model_DbTable_Type();
        $this->view->assign('array_types', $activite_model->fetchAll()->toArray());

        // Liste des catégorie
        $cat_model = new Model_DbTable_Categorie();
        $this->view->assign('array_categories', $cat_model->fetchAll()->toArray());

        // Liste des classes
        $classe_model = new Model_DbTable_Classe();
        $this->view->assign('array_classes', $classe_model->fetchAll()->toArray());

        // Les périodicités
        $perio_model = new Model_DbTable_Periodicite();
        $tableau = $perio_model->fetchAll()->toArray();

        $result = [];

        foreach ($tableau as $singleTableau) {
            $result[$singleTableau['ID_CATEGORIE']][$singleTableau['ID_TYPE']][$singleTableau['LOCALSOMMEIL_PERIODICITE']] = $singleTableau['PERIODICITE_PERIODICITE'];
        }

        $this->view->assign('tableau', $result);
    }

    public function saveAction(): void
    {
        try {
            // Model des périodicités
            $perio_model = new Model_DbTable_Periodicite();

            // Requests
            $request = $this->getRequest();

            foreach ($request->getPost() as $key => $value) {
                $result = explode('_', $key);

                if ($item = null == $perio_model->find($result[0], $result[1], $result[2])->current()) {
                    $item = $perio_model->createRow();
                } else {
                    $item = $perio_model->find($result[0], $result[1], $result[2])->current();
                }

                $item->ID_CATEGORIE = $result[0];
                $item->ID_TYPE = $result[1];
                $item->LOCALSOMMEIL_PERIODICITE = $result[2];
                $item->PERIODICITE_PERIODICITE = $value;
                $item->save();

                $this->_helper->flashMessenger([
                    'context' => 'success',
                    'title' => 'Le tableau des périodicités a bien été sauvegardé',
                    'message' => '',
                ]);
            }
        } catch (Exception $exception) {
            $this->_helper->flashMessenger([
                'context' => 'error',
                'title' => 'Erreur lors de la sauvegarde du tableau des périodicités',
                'message' => $exception->getMessage(),
            ]);
        }

        // Redirection
        $this->_helper->redirector('index');
    }

    public function applyAction(): void
    {
        try {
            // Model des périodicités
            $perio_model = new Model_DbTable_Periodicite();
            $perio_model->apply();

            $this->_helper->flashMessenger([
                'context' => 'success',
                'title' => 'OKAY!',
                'message' => 'Le tableau des périodicités a bien été appliqué',
            ]);
        } catch (Exception $exception) {
            $this->_helper->flashMessenger([
                'context' => 'error',
                'title' => 'Erreur lors de la sauvegarde du tableau des périodicités',
                'message' => $exception->getMessage(),
            ]);
        }

        // Récupération de la ressource cache à partir du bootstrap
        $cache = Zend_Controller_Front::getInstance()->getParam('bootstrap')->getResource('cacheSearch');
        $cache->clean(Zend_Cache::CLEANING_MODE_ALL);

        // Redirection
        $this->_helper->redirector('index');
    }
}
