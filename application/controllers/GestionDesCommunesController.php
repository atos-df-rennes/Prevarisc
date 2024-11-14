<?php

class GestionDesCommunesController extends Zend_Controller_Action
{
    public function indexAction(): void
    {
        // Définition du layout
        $this->_helper->layout->setLayout('menu_admin');

        // Modèles
        $commune = new Model_DbTable_AdresseCommune();

        // Liste des villes pour le select
        $this->view->assign('rowset_communes', $commune->fetchAll(null, 'LIBELLE_COMMUNE'));
    }

    public function displayAction(): void
    {
        // Modèles de données
        $DB_informations = new Model_DbTable_UtilisateurInformations();
        $DB_communes = new Model_DbTable_AdresseCommune();

        // On récupère la commune
        $commune = $DB_communes->find($this->_request->numinsee)->current();
        $this->view->assign('commune', $commune);

        // On envoie le tout sur la vue
        $this->view->assign('user_info', $DB_informations->find($commune->ID_UTILISATEURINFORMATIONS)->current());

        $this->view->assign('ext', $this->_request->ext);
    }

    public function saveAction(): void
    {
        try {
            if (
                isset($_POST['ID_UTILISATEURCIVILITE'])
                && 'null' == $_POST['ID_UTILISATEURCIVILITE']
            ) {
                unset($_POST['ID_UTILISATEURCIVILITE']);
            }

            $this->_helper->viewRenderer->setNoRender();

            // Modèles de données
            $DB_informations = new Model_DbTable_UtilisateurInformations();
            $DB_communes = new Model_DbTable_AdresseCommune();

            // On récupère la commune
            $commune = $DB_communes->find($_GET['numinsee'])->current();

            if (0 == $commune->ID_UTILISATEURINFORMATIONS) {
                $commune->ID_UTILISATEURINFORMATIONS = $DB_informations->insert(array_intersect_key($_POST, $DB_informations->info('metadata')));
            } else {
                $info = $DB_informations->find($commune->ID_UTILISATEURINFORMATIONS)->current();

                if (null == $info) {
                    $id = $DB_informations->insert(array_intersect_key($_POST, $DB_informations->info('metadata')));
                    $commune->ID_UTILISATEURINFORMATIONS = $id;
                } else {
                    $info->setFromArray(array_intersect_key($_POST, $DB_informations->info('metadata')))->save();
                }
            }

            $commune->save();

            $this->_helper->flashMessenger(
                [
                    'context' => 'success',
                    'title' => 'Sauvegarde réussie !',
                    'message' => 'La commune '.$commune->LIBELLE_COMMUNE.' a été enregistrée.',
                ]
            );
        } catch (Exception $exception) {
            $this->_helper->flashMessenger(
                [
                    'context' => 'error',
                    'title' => 'Aie',
                    'message' => $exception->getMessage(),
                ]
            );
        }

        // Redirection
        $this->_helper->redirector('index');
    }
}
