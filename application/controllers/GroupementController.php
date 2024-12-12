<?php

class GroupementController extends Zend_Controller_Action
{
    public function init(): void
    {
        $ajaxContext = $this->_helper->getHelper('AjaxContext');
        $ajaxContext->addActionContext('add', 'json')
            ->addActionContext('display', 'html')
            ->addActionContext('add-type', 'json')
            ->initContext()
        ;
    }

    public function indexAction(): void
    {
        // Titre
        $this->view->assign('title', 'Groupements de communes');

        $this->_helper->layout->setLayout('menu_admin');

        // Liste des models
        $model_groupementstypes = new Model_DbTable_GroupementType();

        // Liste des types de groupement
        $array_groupementstypes = $model_groupementstypes->fetchAll()->toArray();

        // Envoi dans la vue les groupements et leur types
        $this->view->assign('array_groupementstypes', $array_groupementstypes);

        $commune = new Model_DbTable_AdresseCommune();
        $this->view->assign('villes_tests', $commune->fetchAll()->toArray());
    }

    public function displayAction(): void
    {
        // Liste des villes pour le select
        $commune = new Model_DbTable_AdresseCommune();
        $this->view->assign('villes', $commune->fetchAll()->toArray());

        // On check les prev du groupement
        $groupements = new Model_DbTable_Groupement();

        // Liste des types de groupement
        $groupement_type = new Model_DbTable_GroupementType();
        $types = $groupement_type->fetchAll();

        $this->view->assign('types', $types);

        // Coordonnées du groupement
        $DB_informations = new Model_DbTable_UtilisateurInformations();

        $this->view->assign('preventionnistes', []);
        if (
            isset($_GET['id'])
            && 0 != $_GET['id']
        ) {
            $groupement = $groupements->find($_GET['id'])->current();
            $this->view->assign('groupement', $groupement->toArray());
            $this->view->assign('libelle', $groupement['LIBELLE_GROUPEMENT']);
            $this->view->assign('type', $groupement['ID_GROUPEMENTTYPE']);
            $this->view->assign('preventionnistes', $groupements->getPreventionnistes($_GET['id']));
            $this->view->assign('ville_du_groupement', $groupement->findModel_DbTable_AdresseCommuneViaModel_DbTable_GroupementCommune()->toArray());
            $this->view->assign('user_info', $DB_informations->find($groupement->ID_UTILISATEURINFORMATIONS)->current());
        }
    }

    public function viewAction(): void
    {
        $model_groupement = new Model_DbTable_Groupement();
        $this->view->assign('row', $model_groupement->get($this->_request->id));
        $this->view->assign('prev', $model_groupement->getPreventionnistes($this->_request->id));
    }

    public function addAction(): void
    {
        try {
            // Modele groupement et groupement communes et groupement prev.
            $groupements = new Model_DbTable_Groupement();
            $groupementscommune = new Model_DbTable_GroupementCommune();
            $groupementsprev = new Model_DbTable_GroupementPreventionniste();
            $DB_informations = new Model_DbTable_UtilisateurInformations();

            // Si c'est pour un nouveau groupement
            if (0 == $_POST['id_gpt']) {
                $new_groupement = $groupements->createRow();
                $id_coord = $DB_informations->insert(array_intersect_key($_POST, $DB_informations->info('metadata')));
                $new_groupement->ID_UTILISATEURINFORMATIONS = $id_coord;
            } else {
                $new_groupement_item = $groupements->find($_POST['id_gpt']);
                $new_groupement = $new_groupement_item->current();

                $groupementscommune->delete($groupementscommune->getAdapter()->quoteInto('ID_GROUPEMENT = ?', $new_groupement->ID_GROUPEMENT));
                $groupementsprev->delete($groupementsprev->getAdapter()->quoteInto('ID_GROUPEMENT = ?', $new_groupement->ID_GROUPEMENT));

                $info = $DB_informations->find($new_groupement->ID_UTILISATEURINFORMATIONS)->current();

                if (null == $info) {
                    if ('null' == $_POST['ID_UTILISATEURCIVILITE']) {
                        unset($_POST['ID_UTILISATEURCIVILITE']);
                    }

                    unset($_POST['ID_FONCTION']);
                    $id = $DB_informations->insert(array_intersect_key($_POST, $DB_informations->info('metadata')));
                    $new_groupement->ID_UTILISATEURINFORMATIONS = $id;
                } else {
                    if ('null' == $_POST['ID_UTILISATEURCIVILITE']) {
                        unset($_POST['ID_UTILISATEURCIVILITE']);
                    }

                    unset($_POST['ID_FONCTION']);
                    $info->setFromArray(array_intersect_key($_POST, $DB_informations->info('metadata')))->save();
                }
            }

            $new_groupement->LIBELLE_GROUPEMENT = $_POST['nom_groupement'];
            $new_groupement->ID_GROUPEMENTTYPE = $_POST['type_groupement'];

            $new_groupement->save();

            // On associe les communes
            if (isset($_POST['villes'])) {
                foreach ($_POST['villes'] as $value) {
                    $new = $groupementscommune->createRow();

                    $new->ID_GROUPEMENT = $new_groupement->ID_GROUPEMENT;
                    $new->NUMINSEE_COMMUNE = $value;

                    $new->save();
                }
            }

            // On associe les preventionnistes
            if (isset($_POST['prev'])) {
                foreach ($_POST['prev'] as $value) {
                    $new = $groupementsprev->createRow();

                    $new->ID_GROUPEMENT = $new_groupement->ID_GROUPEMENT;
                    $new->ID_UTILISATEUR = $value;
                    $new->DATEDEBUT_GROUPEMENTPREVENTIONNISTE = date('Y-m-d H:i:s');

                    $new->save();
                }
            }

            $this->view->assign('id', $new_groupement->ID_GROUPEMENT);
            $this->view->assign('libelle', $new_groupement->LIBELLE_GROUPEMENT);
            $this->view->assign('type', $new_groupement->ID_GROUPEMENTTYPE);

            $this->_helper->flashMessenger([
                'context' => 'success',
                'title' => 'Ajout réussi !',
                'message' => 'Le groupement '.$new_groupement->LIBELLE_GROUPEMENT.' a été ajouté.',
            ]);
        } catch (Exception $exception) {
            $this->_helper->flashMessenger([
                'context' => 'error',
                'title' => 'Aie',
                'message' => $exception->getMessage(),
            ]);
        }
    }

    public function deleteAction(): void
    {
        try {
            $this->_helper->viewRenderer->setNoRender(); // On desactive la vue

            // On supprime le groupement
            $groupements = new Model_DbTable_Groupement();
            $communes = new Model_DbTable_GroupementCommune();
            $prev = new Model_DbTable_GroupementPreventionniste();
            $contacts = new Model_DbTable_GroupementContact();

            $contacts->delete('ID_GROUPEMENT = '.$_GET['id']);
            $communes->delete('ID_GROUPEMENT = '.$_GET['id']);
            $prev->delete('ID_GROUPEMENT = '.$_GET['id']);
            $groupements->delete('ID_GROUPEMENT = '.$_GET['id']);

            $this->_helper->flashMessenger([
                'context' => 'success',
                'title' => 'Suppression réussie !',
                'message' => 'Le groupement a été supprimé.',
            ]);
        } catch (Exception $exception) {
            $this->_helper->flashMessenger([
                'context' => 'error',
                'title' => 'Aie',
                'message' => $exception->getMessage(),
            ]);
        }

        // Redirection
        $this->_helper->redirector('index');
    }

    public function addTypeAction(): void
    {
        try {
            // Modèle
            $model_groupementtype = new Model_DbTable_GroupementType();

            // Ajout du type
            $new = $model_groupementtype->createRow();
            $new->LIBELLE_GROUPEMENTTYPE = $this->_request->LIBELLE_GROUPEMENTTYPE;
            $new->save();

            $this->view->assign('id', $new->ID_GROUPEMENTTYPE);

            $this->_helper->flashMessenger([
                'context' => 'success',
                'title' => 'Ajout réussi !',
                'message' => 'Le traitement est ok.',
            ]);
        } catch (Exception $exception) {
            $this->_helper->flashMessenger([
                'context' => 'error',
                'title' => 'Aie',
                'message' => $exception->getMessage(),
            ]);
        }

        // Redirection
        $this->_helper->redirector('index');
    }

    public function deleteTypeAction(): void
    {
        // On desactive la vue
        $this->_helper->viewRenderer->setNoRender();
    }

    public function preventionnisteAction(): void
    {
        $service_groupement = new Service_GroupementCommunes();
        $request = $this->getRequest();

        $service_groupement->reaffectationPreventioniste($request->getParam('groupement'));
        Zend_Controller_Front::getInstance()->getParam('bootstrap')->getResource('cacheSearch')->clean(Zend_Cache::CLEANING_MODE_ALL);
    }
}
