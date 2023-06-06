<?php

class TableauDesPeriodicitesController extends Zend_Controller_Action
{
    public function indexAction()
    {
        // Définition du layout
        $this->_helper->layout->setLayout('menu_admin');

        // Liste des types d'activité
        $activite_model = new Model_DbTable_Type();
        $this->view->array_types = $activite_model->fetchAll()->toArray();

        // Liste des catégorie
        $cat_model = new Model_DbTable_Categorie();
        $this->view->array_categories = $cat_model->fetchAll()->toArray();

        // Liste des classes
        $classe_model = new Model_DbTable_Classe();
        $this->view->array_classes = $classe_model->fetchAll()->toArray();

        // Les périodicités
        $perio_model = new Model_DbTable_Periodicite();
        $tableau = $perio_model->fetchAll()->toArray();

        $result = [];

        foreach ($tableau as $i => $singleTableau) {
            // Sans local sommeil
            $result[$tableau[$i]['ID_CATEGORIE']][$tableau[$i]['ID_TYPE']][$tableau[$i]['LOCALSOMMEIL_PERIODICITE']] = $singleTableau['PERIODICITE_PERIODICITE'];
            // Avec local (on exclu igh == categ à 0)
            if (0 != $singleTableau['ID_CATEGORIE']) {
                $result[$tableau[$i++]['ID_CATEGORIE']][$tableau[$i]['ID_TYPE']][$tableau[$i]['LOCALSOMMEIL_PERIODICITE']] = $singleTableau['PERIODICITE_PERIODICITE'];
            }
        }
        if (getenv('PREVARISC_UNITE_PERIODICITE_ANNEES')) {
            foreach ($cat_model->fetchAll()->toArray() as $categorie) {
                foreach ($activite_model->fetchAll()->toArray() as $type) {
                    $result[$categorie["ID_CATEGORIE"]][$type["ID_TYPE"]][0] /= 12;
                    $result[$categorie["ID_CATEGORIE"]][$type["ID_TYPE"]][1] /= 12;
                }
            }
        }
        $this->view->tableau = $result;
    }

    public function saveAction()
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
        } catch (Exception $e) {
            $this->_helper->flashMessenger([
                'context' => 'error',
                'title' => 'Erreur lors de la sauvegarde du tableau des périodicités',
                'message' => $e->getMessage(),
            ]);
        }

        // Redirection
        $this->_helper->redirector('index');
    }

    public function applyAction()
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
        } catch (Exception $e) {
            $this->_helper->flashMessenger([
                'context' => 'error',
                'title' => 'Erreur lors de la sauvegarde du tableau des périodicités',
                'message' => $e->getMessage(),
            ]);
        }

        // Récupération de la ressource cache à partir du bootstrap
        $cache = Zend_Controller_Front::getInstance()->getParam('bootstrap')->getResource('cacheSearch');
        $cache->clean(Zend_Cache::CLEANING_MODE_ALL);

        // Redirection
        $this->_helper->redirector('index');
    }
}
