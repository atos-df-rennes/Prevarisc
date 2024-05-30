<?php

class EtablissementController extends Zend_Controller_Action
{
    public $cache;

    /**
     * @var mixed|Service_Etablissement
     */
    public $serviceEtablissement;

    /**
     * @var array<string, mixed>|mixed|mixed[]
     */
    public $etablissement;

    /**
     * @var array<string, mixed>|mixed
     */
    public $etablissement_parent;

    public function init(): void
    {
        $this->view->headLink()->appendStylesheet('/css/etiquetteAvisDerogations/greenCircle.css', 'all');

        $this->cache = Zend_Controller_Front::getInstance()->getParam('bootstrap')->getResource('cache');
        $this->view->assign('isAllowedEffectifsDegagements', unserialize($this->cache->load('acl'))->isAllowed(Zend_Auth::getInstance()->getIdentity()['group']['LIBELLE_GROUPE'], 'effectifs_degagements', 'effectifs_degagements_ets'));
        $this->view->assign('isAllowedAvisDerogations', unserialize($this->cache->load('acl'))->isAllowed(Zend_Auth::getInstance()->getIdentity()['group']['LIBELLE_GROUPE'], 'avisderogations', 'avis_derogations'));

        $this->serviceEtablissement = new Service_Etablissement();

        if ($this->getParam('id')) {
            $this->etablissement = $this->serviceEtablissement->get($this->getParam('id'));
            $this->view->assign('etablissement', $this->etablissement);
            $this->view->assign('avis', $this->serviceEtablissement->getAvisEtablissement($this->etablissement['general']['ID_ETABLISSEMENT'], $this->etablissement['general']['ID_DOSSIER_DONNANT_AVIS']));
            $this->view->assign('hasAvisDerogations', array_key_exists('AVIS_DEROGATIONS', $this->serviceEtablissement->getHistorique($this->_request->id)));
        }
    }

    public function indexAction()
    {
        $this->_helper->layout->setLayout('etablissement');

        $viewHeadScript = $this->view;
        $viewHeadScript->headScript()->appendFile('/js/tinymce.min.js');
        $viewHeadScript->headScript()->appendFile('/js/geoportail/sdk-ol/GpSDK2D.js', 'text/javascript');
        $viewHeadScript->headScript()->appendFile('/js/geoportail/manageMap.js', 'text/javascript');

        $viewHeadLink = $this->view;
        $viewHeadLink->headLink()->appendStylesheet('/js/geoportail/sdk-ol/GpSDK2D.css', 'all');

        $service_groupement_communes = new Service_GroupementCommunes();
        $service_carto = new Service_Carto();
        $DB_periodicite = new Model_DbTable_Periodicite();

        $this->view->assign('couches_cartographiques', $service_carto->getAll());
        $this->view->assign('key_ign', getenv('PREVARISC_PLUGIN_IGNKEY'));
        $this->view->assign('key_googlemap', getenv('PREVARISC_PLUGIN_GOOGLEMAPKEY'));
        $this->view->assign('geoconcept_url', getenv('PREVARISC_PLUGIN_GEOCONCEPT_URL'));

        $this->view->assign('default_periodicite', $DB_periodicite->gn4ForEtablissement($this->etablissement));
        $this->view->assign('periodicity', $this->serviceEtablissement->getDisplayedPeriodicity($this->etablissement));
        $this->view->assign('groupements_de_communes', 0 == count($this->etablissement['adresses']) ? [] : $service_groupement_communes->findAll($this->etablissement['adresses'][0]['NUMINSEE_COMMUNE']));

        $this->view->assign('store', Zend_Controller_Front::getInstance()->getParam('bootstrap')->getResource('dataStore'));

        $cache = Zend_Controller_Front::getInstance()->getParam('bootstrap')->getResource('cache');
        // Autorisation de suppression de l'établissement
        $this->view->assign('is_allowed_delete_etablissement', unserialize($cache->load('acl'))->isAllowed(Zend_Auth::getInstance()->getIdentity()['group']['LIBELLE_GROUPE'], 'suppression', 'delete_etablissement'));
    }

    public function editAction()
    {
        $this->_helper->layout->setLayout('etablissement');

        $viewHeadScript = $this->view;
        $viewHeadScript->headScript()->appendFile('/js/geoportail/sdk-ol/GpSDK2D.js', 'text/javascript');
        $viewHeadScript->headScript()->appendFile('/js/geoportail/manageMap.js', 'text/javascript');
        $viewHeadScript->headScript()->appendFile('/js/etablissement/edit/geolocaliseIGN.js', 'text/javascript');

        $viewHeadLink = $this->view;
        $viewHeadLink->headLink()->appendStylesheet('/js/geoportail/sdk-ol/GpSDK2D.css', 'all');

        $service_carto = new Service_Carto();

        $this->view->assign('key_ign', getenv('PREVARISC_PLUGIN_IGNKEY'));
        $this->view->assign('geoconcept_url', getenv('PREVARISC_PLUGIN_GEOCONCEPT_URL'));
        $this->view->assign('default_lon', getenv('PREVARISC_CARTO_DEFAULT_LON') ?: '2.71490430425517');
        $this->view->assign('default_lat', getenv('PREVARISC_CARTO_DEFAULT_LAT') ?: '50.4727273438818');

        $service_genre = new Service_Genre();
        $service_statut = new Service_Statut();
        $service_avis = new Service_Avis();
        $service_categorie = new Service_Categorie();
        $service_type = new Service_Type();
        $service_typeactivite = new Service_TypeActivite();
        $service_commission = new Service_Commission();
        $service_typesplan = new Service_TypePlan();
        $service_famille = new Service_Famille();
        $service_classe = new Service_Classe();
        $service_classement = new Service_Classement();

        $this->view->assign('DB_genre', $service_genre->getAll());
        $this->view->assign('DB_statut', $service_statut->getAll());
        $this->view->assign('DB_avis', $service_avis->getAll());
        $this->view->assign('DB_categorie', $service_categorie->getAll());
        $this->view->assign('DB_type', $service_type->getAll());
        $this->view->assign('DB_activite', $service_typeactivite->getAll());
        $this->view->assign('DB_commission', $service_commission->getCommissionsAndTypes());
        $this->view->assign('DB_typesplan', $service_typesplan->getAll());
        $this->view->assign('DB_famille', $service_famille->getAll());
        $this->view->assign('DB_classe', $service_classe->getAll());
        $this->view->assign('DB_classement', $service_classement->getAll());

        $this->view->assign('couches_cartographiques', $service_carto->getAll());

        $this->view->assign('add', false);

        $cache = Zend_Controller_Front::getInstance()->getParam('bootstrap')->getResource('cache');
        $mygroupe = Zend_Auth::getInstance()->getIdentity()['group']['LIBELLE_GROUPE'];
        $this->view->assign('is_allowed_change_statut', unserialize($cache->load('acl'))->isAllowed($mygroupe, 'statut_etablissement', 'edit_statut'));

        $request = $this->getRequest();
        if ($request->isPost()) {
            try {
                $post = $request->getPost();
                $options = '';
                if (
                    getenv('PREVARISC_MAIL_ENABLED')
                    && 1 == getenv('PREVARISC_MAIL_ENABLED')
                ) {
                    $typeAlerte = $this->serviceEtablissement->checkAlerte($this->etablissement, $post);

                    if (
                        unserialize($cache->load('acl'))->isAllowed($mygroupe, 'alerte_email', 'alerte_statut', 'alerte_classement')
                        && false !== $typeAlerte
                    ) {
                        $service_alerte = new Service_Alerte();
                        $options = $service_alerte->getLink($typeAlerte);
                    }
                }

                $date = date('Y-m-d');
                $this->serviceEtablissement->save($post['ID_GENRE'], $post, $request->id, $date);
                $this->_helper->flashMessenger(['context' => 'success', 'title' => 'Mise à jour réussie !', 'message' => 'L\'établissement a bien été mis à jour.'.$options]);
                $this->_helper->redirector('index', null, null, ['id' => $request->id]);
            } catch (Exception $e) {
                $this->_helper->flashMessenger(['context' => 'error', 'title' => '', 'message' => 'L\'établissement n\'a pas été mis à jour. Veuillez rééssayez. ('.$e->getMessage().')']);
            }
        }
    }

    public function addAction()
    {
        $viewHeadScript = $this->view;
        $viewHeadScript->headScript()->appendFile('/js/geoportail/sdk-ol/GpSDK2D.js', 'text/javascript');
        $viewHeadScript->headScript()->appendFile('/js/geoportail/manageMap.js', 'text/javascript');
        $viewHeadScript->headScript()->appendFile('/js/etablissement/edit/geolocaliseIGN.js', 'text/javascript');

        $viewHeadLink = $this->view;
        $viewHeadLink->headLink()->appendStylesheet('/js/geoportail/sdk-ol/GpSDK2D.css', 'all');

        $service_genre = new Service_Genre();
        $service_statut = new Service_Statut();
        $service_avis = new Service_Avis();
        $service_categorie = new Service_Categorie();
        $service_type = new Service_Type();
        $service_typeactivite = new Service_TypeActivite();
        $service_commission = new Service_Commission();
        $service_typesplan = new Service_TypePlan();
        $service_famille = new Service_Famille();
        $service_classe = new Service_Classe();
        $service_classement = new Service_Classement();
        $service_carto = new Service_Carto();

        $this->view->assign('DB_genre', $service_genre->getAll());
        $this->view->assign('DB_statut', $service_statut->getAll());
        $this->view->assign('DB_avis', $service_avis->getAll());
        $this->view->assign('DB_categorie', $service_categorie->getAll());
        $this->view->assign('DB_type', $service_type->getAll());
        $this->view->assign('DB_activite', $service_typeactivite->getAll());
        $this->view->assign('DB_commission', $service_commission->getCommissionsAndTypes());
        $this->view->assign('DB_typesplan', $service_typesplan->getAll());
        $this->view->assign('DB_famille', $service_famille->getAll());
        $this->view->assign('DB_classe', $service_classe->getAll());
        $this->view->assign('DB_classement', $service_classement->getAll());

        $this->view->assign('add', true);

        $this->view->assign('key_ign', getenv('PREVARISC_PLUGIN_IGNKEY'));
        $this->view->assign('geoconcept_url', getenv('PREVARISC_PLUGIN_GEOCONCEPT_URL'));
        $this->view->assign('default_lon', getenv('PREVARISC_CARTO_DEFAULT_LON') ?: '2.71490430425517');
        $this->view->assign('default_lat', getenv('PREVARISC_CARTO_DEFAULT_LAT') ?: '50.4727273438818');
        $this->view->assign('couches_cartographiques', $service_carto->getAll());

        $cache = Zend_Controller_Front::getInstance()->getParam('bootstrap')->getResource('cache');
        $mygroupe = Zend_Auth::getInstance()->getIdentity()['group']['LIBELLE_GROUPE'];
        $this->view->assign('is_allowed_change_statut', unserialize($cache->load('acl'))->isAllowed($mygroupe, 'statut_etablissement', 'edit_statut'));

        $request = $this->getRequest();
        if ($request->isPost()) {
            try {
                $post = $request->getPost();
                $id_etablissement = $this->serviceEtablissement->save($post['ID_GENRE'], $post);
                $this->_helper->flashMessenger(['context' => 'success', 'title' => 'Ajout réussi !', 'message' => 'L\'établissement a bien été ajouté.']);

                if (
                    1 == $post['ID_GENRE']
                    && 1 == count($post['ID_FILS_ETABLISSEMENT'])
                ) {
                    $this->_helper->flashMessenger(['context' => 'warning', 'title' => 'Ajout des établissements enfants', 'message' => "Les droits d'accès au site sont déterminés par les droits d'accès aux établissements qui le compose. Veillez à ajouter des établissements afin de garantir l'accès au site dans Prevarisc."]);
                    $this->_helper->redirector('edit', null, null, ['id' => $id_etablissement]);
                } else {
                    $this->_helper->redirector('index', null, null, ['id' => $id_etablissement]);
                }
            } catch (Exception $e) {
                $this->_helper->flashMessenger(['context' => 'error', 'title' => '', 'message' => 'L\'établissement n\'a pas été ajouté. Veuillez rééssayez. ('.$e->getMessage().')']);
            }
        }

        $this->render('edit');
    }

    public function descriptifAction()
    {
        $viewHeadLink = $this->view;
        $viewHeadLink->headLink()->appendStylesheet('/css/formulaire/tableauInputParent.css', 'all');

        if (1 === (int) getenv('PREVARISC_DESCRIPTIF_PERSONNALISE')) {
            $this->descriptifPersonnaliseAction();
        } else {
            $this->_helper->layout->setLayout('etablissement');

            $descriptifs = $this->serviceEtablissement->getDescriptifs($this->_request->id);

            $this->view->assign('descriptif', $descriptifs['descriptif']);
            $this->view->assign('historique', $descriptifs['historique']);
            $this->view->assign('derogations', $descriptifs['derogations']);
            $this->view->assign('champs_descriptif_technique', $descriptifs['descriptifs_techniques']);
        }
    }

    public function descriptifPersonnaliseAction(): void
    {
        $this->_helper->layout->setLayout('etablissement');

        $viewHeadLink = $this->view;
        $viewHeadLink->headLink()->appendStylesheet('/css/formulaire/descriptif.css', 'all');
        $viewHeadLink->headLink()->appendStylesheet('/css/formulaire/tableauInputParent.css', 'all');

        $service_etablissement = new Service_Etablissement();
        $serviceEtablissementDescriptif = new Service_EtablissementDescriptif();

        $idEtablissement = $this->getParam('id');

        $this->view->assign('etablissement', $service_etablissement->get($idEtablissement));
        $this->view->assign('avis', $service_etablissement->getAvisEtablissement($this->view->etablissement['general']['ID_ETABLISSEMENT'], $this->view->etablissement['general']['ID_DOSSIER_DONNANT_AVIS']));

        $rubriques = $serviceEtablissementDescriptif->getRubriques($idEtablissement, 'Etablissement');

        $this->view->assign('rubriques', $rubriques);
        $this->view->assign('champsvaleurliste', $serviceEtablissementDescriptif->getValeursListe());
    }

    public function editDescriptifAction()
    {
        if (1 === (int) getenv('PREVARISC_DESCRIPTIF_PERSONNALISE')) {
            $this->editDescriptifPersonnaliseAction();
        } else {
            $this->descriptifAction();

            $request = $this->getRequest();
            if ($request->isPost()) {
                try {
                    $post = $request->getPost();
                    $this->serviceEtablissement->saveDescriptifs($request->id, $post['historique'], $post['descriptif'], $post['derogations'], $post['descriptifs_techniques']);
                    $this->_helper->flashMessenger(['context' => 'success', 'title' => 'Mise à jour réussie !', 'message' => 'Les descriptifs ont bien été mis à jour.']);
                } catch (Exception $e) {
                    $this->_helper->flashMessenger(['context' => 'error', 'title' => 'Mise à jour annulée', 'message' => 'Les descriptifs n\'ont pas été mis à jour. Veuillez rééssayez. ('.$e->getMessage().')']);
                }

                $this->_helper->redirector('descriptif', null, null, ['id' => $request->id]);
            }
        }
    }

    public function editDescriptifPersonnaliseAction(): void
    {
        $this->view->headLink()->appendStylesheet('/css/formulaire/edit-table.css', 'all');
        $this->view->headLink()->appendStylesheet('/css/formulaire/formulaire.css', 'all');

        $this->view->inlineScript()->appendFile('/js/formulaire/ordonnancement/Sortable.min.js', 'text/javascript');
        $this->view->inlineScript()->appendFile('/js/formulaire/ordonnancement/ordonnancement.js', 'text/javascript');
        $this->view->inlineScript()->appendFile('/js/formulaire/tableau/gestionTableau.js', 'text/javascript');
        $this->view->inlineScript()->appendFile('/js/formulaire/descriptif/edit.js', 'text/javascript');
        $this->view->inlineScript()->appendFile('/js/Calendrier/Calendar.js', 'text/javascript');

        $serviceEtablissementDescriptif = new Service_EtablissementDescriptif();
        $idEtablissement = $this->getParam('id');

        $this->descriptifPersonnaliseAction();

        $request = $this->getRequest();
        if ($request->isPost()) {
            try {
                $post = $request->getPost();

                foreach ($post as $key => $value) {
                    // Informations concernant l'affichage des rubriques
                    if (0 === strpos($key, 'afficher_rubrique-')) {
                        $serviceEtablissementDescriptif->saveRubriqueDisplay($key, $idEtablissement, (int) $value);
                    }

                    // Informations concernant les valeurs des champs
                    if (0 === strpos($key, 'champ-')) {
                        $serviceEtablissementDescriptif->saveValeurChamp($key, $idEtablissement, 'Etablissement', $value);
                    }
                }

                $groupInputsPost = $serviceEtablissementDescriptif->groupInputByOrder($post, $idEtablissement, 'Etablissement');
                // Sauvegarde les changements dans les tableaux
                $serviceEtablissementDescriptif->saveChangeTable($this->view->rubriques, $groupInputsPost, 'Etablissement', $idEtablissement);

                $this->_helper->flashMessenger(['context' => 'success', 'title' => 'Mise à jour réussie !', 'message' => 'Les descriptifs ont bien été mis à jour.']);
            } catch (Exception $e) {
                $this->_helper->flashMessenger(['context' => 'error', 'title' => 'Mise à jour annulée', 'message' => 'Les descriptifs n\'ont pas été mis à jour. Veuillez rééssayez. ('.$e->getMessage().')']);
            }

            $this->_helper->redirector('descriptif', null, null, ['id' => $this->_request->id]);
        }
    }

    public function textesApplicablesAction()
    {
        $this->_helper->layout->setLayout('etablissement');

        $this->view->assign('textes_applicables_de_etablissement', $this->serviceEtablissement->getAllTextesApplicables($this->_request->id));
    }

    public function editTextesApplicablesAction()
    {
        $this->_helper->layout->setLayout('etablissement');

        $service_textes_applicables = new Service_TextesApplicables();

        $this->view->assign('textes_applicables_de_etablissement', $this->serviceEtablissement->getAllTextesApplicables($this->_request->id));
        $this->view->assign('textes_applicables', $service_textes_applicables->getAll());

        if ($this->_request->isPost()) {
            try {
                $post = $this->_request->getPost();
                $this->serviceEtablissement->saveTextesApplicables($this->_request->id, $post['textes_applicables']);
                $this->_helper->flashMessenger(['context' => 'success', 'title' => 'Mise à jour réussie !', 'message' => 'Les textes applicables ont bien été mis à jour.']);
            } catch (Exception $e) {
                $this->_helper->flashMessenger(['context' => 'error', 'title' => 'Mise à jour annulée', 'message' => 'Les textes applicables n\'ont pas été mis à jour. Veuillez rééssayez. ('.$e->getMessage().')']);
            }

            $this->_helper->redirector('textes-applicables', null, null, ['id' => $this->_request->id]);
        }
    }

    public function piecesJointesAction()
    {
        $this->_helper->layout->setLayout('etablissement');

        $store = Zend_Controller_Front::getInstance()->getParam('bootstrap')->getResource('dataStore');
        $allPiecesJointes = $this->serviceEtablissement->getAllPJ($this->_request->id);

        $piecesJointes = array_filter(
            $allPiecesJointes,
            function ($pieceJointe) use ($store) {
                $pieceJointePath = $store->getFilePath($pieceJointe, 'etablissement', $pieceJointe['ID_ETABLISSEMENT']);

                return is_readable($pieceJointePath);
            }
        );

        $this->view->assign('pieces_jointes', $piecesJointes);
        $this->view->assign('store', $store);
    }

    public function getPieceJointeAction()
    {
        $this->forward('get', 'piece-jointe');
    }

    public function editPiecesJointesAction()
    {
        $this->_helper->layout->setLayout('etablissement');

        $store = Zend_Controller_Front::getInstance()->getParam('bootstrap')->getResource('dataStore');
        $allPiecesJointes = $this->serviceEtablissement->getAllPJ($this->_request->id);

        $piecesJointes = array_filter(
            $allPiecesJointes,
            function ($pieceJointe) use ($store) {
                $pieceJointePath = $store->getFilePath($pieceJointe, 'etablissement', $pieceJointe['ID_ETABLISSEMENT']);

                return is_readable($pieceJointePath);
            }
        );

        $this->view->assign('pieces_jointes', $piecesJointes);
        $this->view->assign('store', $store);
    }

    public function addPieceJointeAction()
    {
        $this->_helper->layout->disableLayout();

        if ($this->_request->isPost()) {
            try {
                $post = $this->_request->getPost();
                $name = $post['name'] ?? '';
                $description = $post['description'] ?? '';
                $mise_en_avant = $post['mise_en_avant'] ?? 0;
                $this->serviceEtablissement->addPJ($this->_request->id, $_FILES['file'], $name, $description, $mise_en_avant);
                $this->_helper->flashMessenger(['context' => 'success', 'title' => 'Mise à jour réussie !', 'message' => 'La pièce jointe a bien été ajoutée.']);
            } catch (Exception $e) {
                $this->_helper->flashMessenger(['context' => 'error', 'title' => 'Mise à jour annulée', 'message' => 'La pièce jointe n\'a été ajoutée. Veuillez rééssayez. ('.$e->getMessage().')']);
            }

            $this->_helper->redirector('edit-pieces-jointes', null, null, ['id' => $this->_request->id]);
        }
    }

    public function deletePieceJointeAction()
    {
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        if ($this->_request->isGet()) {
            try {
                $this->serviceEtablissement->deletePJ($this->_request->id, $this->_request->id_pj);
                $this->_helper->flashMessenger(['context' => 'success', 'title' => 'Suppression réussie !', 'message' => 'La pièce jointe a bien été supprimée.']);
            } catch (Exception $e) {
                $this->_helper->flashMessenger(['context' => 'error', 'title' => 'Suppression annulée', 'message' => 'La pièce jointe n\'a été supprimée. Veuillez rééssayez. ('.$e->getMessage().')']);
            }

            $this->_helper->redirector('edit-pieces-jointes', null, null, ['id' => $this->_request->id]);
        }
    }

    public function contactsAction()
    {
        $this->_helper->layout->setLayout('etablissement');

        $contacts_etablissements_parents = [];

        // Récupération des contacts des établissements parents
        foreach ($this->etablissement['parents'] as $this->etablissement_parent) {
            $contacts_etablissements_parents = array_merge($contacts_etablissements_parents, $this->serviceEtablissement->getAllContacts($this->etablissement_parent['ID_ETABLISSEMENT']));
        }

        $this->view->assign('contacts', $this->serviceEtablissement->getAllContacts($this->_request->id));
        $this->view->assign('contacts_etablissements_parents', $contacts_etablissements_parents);
    }

    public function editContactsAction()
    {
        $this->_helper->layout->setLayout('etablissement');

        $this->view->assign('contacts', $this->serviceEtablissement->getAllContacts($this->_request->id));
    }

    public function addContactAction()
    {
        $this->_helper->layout->disableLayout();

        $service_contact = new Service_Contact();

        $this->view->assign('fonctions', $service_contact->getFonctions());

        if ($this->_request->isPost()) {
            try {
                $post = $this->_request->getPost();
                $this->serviceEtablissement->addContact($this->_request->id, $post['firstname'], $post['lastname'], $post['id_fonction'], $post['societe'], $post['fixe'], $post['mobile'], $post['fax'], $post['mail'], $post['adresse'], $post['web']);
                $this->_helper->flashMessenger(['context' => 'success', 'title' => 'Mise à jour réussie !', 'message' => 'Le contact a bien été ajouté.']);
            } catch (Exception $e) {
                $this->_helper->flashMessenger(['context' => 'error', 'title' => 'Mise à jour annulée', 'message' => 'Le contact n\'a été ajouté. Veuillez rééssayez. ('.$e->getMessage().')']);
            }

            $this->_helper->redirector('edit-contacts', null, null, ['id' => $this->_request->id]);
        }
    }

    public function addContactExistantAction()
    {
        $this->_helper->layout->disableLayout();

        if ($this->_request->isPost()) {
            try {
                $this->serviceEtablissement->addContactExistant($this->_request->id, $this->_request->id_contact);
                $this->_helper->flashMessenger(['context' => 'success', 'title' => 'Mise à jour réussie !', 'message' => 'Le contact a bien été ajouté.']);
            } catch (Exception $e) {
                $this->_helper->flashMessenger(['context' => 'error', 'title' => 'Mise à jour annulée', 'message' => 'Le contact n\'a été ajouté. Veuillez rééssayez. ('.$e->getMessage().')']);
            }

            $this->_helper->redirector('edit-contacts', null, null, ['id' => $this->_request->id]);
        }
    }

    public function deleteContactAction()
    {
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        if ($this->_request->isGet()) {
            try {
                $this->serviceEtablissement->deleteContact($this->_request->id, $this->_request->id_contact);
                $this->_helper->flashMessenger(['context' => 'success', 'title' => 'Suppression réussie !', 'message' => 'Le contact a bien été supprimé de la fiche établissement.']);
            } catch (Exception $e) {
                $this->_helper->flashMessenger(['context' => 'error', 'title' => 'Suppression annulée', 'message' => 'Le contact n\'a été supprimé. Veuillez rééssayez. ('.$e->getMessage().')']);
            }

            $this->_helper->redirector('edit-contacts', null, null, ['id' => $this->_request->id]);
        }
    }

    public function dossiersAction()
    {
        $this->_helper->layout->setLayout('etablissement');

        $dossiers = $this->serviceEtablissement->getNLastDossiers($this->_request->id);

        $this->view->assign('etudes', $dossiers['etudes']);
        $this->view->assign('visites', $dossiers['visites']);
        $this->view->assign('autres', $dossiers['autres']);

        $this->view->assign('nbElemMax', Service_Utils_DossiersMaxNumber::value());
        $this->view->assign('nbEtudes', $this->serviceEtablissement->getNbDossierTypeEtablissement($this->_request->id, 'etudes'));
        $this->view->assign('nbVisites', $this->serviceEtablissement->getNbDossierTypeEtablissement($this->_request->id, 'visites'));
        $this->view->assign('nbAutres', $this->serviceEtablissement->getNbDossierTypeEtablissement($this->_request->id, 'autres'));
    }

    public function getDossiersAfterNAction()
    {
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $dossiers = $this->serviceEtablissement->getDossiersAfterN($this->_request->id, $this->_request->typeDossier);

        $html = "<ul class='recherche_liste'>";
        $html .= Zend_Layout::getMvcInstance()->getView()->partialLoop('search/results/dossier.phtml', (array) $dossiers);
        $html .= '</ul>';

        echo $html;
    }

    public function historiqueAction()
    {
        $this->_helper->layout->setLayout('etablissement');

        $this->view->assign('historique', $this->serviceEtablissement->getHistorique($this->_request->id));
    }

    public function deleteAction()
    {
        try {
            $idEtablissement = $this->_getParam('id');

            // On supprime les dossiers de l'établissement
            $service_dossier = new Service_Dossier();
            $service_dossier->deleteByEtab($idEtablissement);

            // On supprime l'établissement
            $this->serviceEtablissement->delete($idEtablissement);

            // Récupération de la ressource cache à partir du bootstrap
            $cache = Zend_Controller_Front::getInstance()->getParam('bootstrap')->getResource('cacheSearch');
            $cache->clean(Zend_Cache::CLEANING_MODE_ALL);

            $this->_helper->flashMessenger(['context' => 'success', 'title' => 'Mise à jour réussie !', 'message' => 'L\'établissement a bien été supprimé.']);
            $this->redirect('/search/etablissement?label=&page=1');
        } catch (Exception $e) {
            $this->_helper->flashMessenger(['context' => 'error', 'title' => '', 'message' => 'L\'établissement n\'a pas été supprimé. Veuillez rééssayez. ('.$e->getMessage().')']);
        }
    }

    public function updateAdresseAction()
    {
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $postData = $this->getRequest()->getPost();
        echo json_encode($postData);
    }

    public function effectifsDegagementsEtablissementAction()
    {
        $this->_helper->layout->setLayout('etablissement');

        $viewHeadLink = $this->view;
        $viewHeadLink->headLink()->appendStylesheet('/css/formulaire/descriptif.css', 'all');
        $viewHeadLink->headLink()->appendStylesheet('/css/formulaire/tableauInputParent.css', 'all');

        $service_etablissement = new Service_Etablissement();
        $serviceEtablissementEffectifsDegagements = new Service_EtablissementEffectifsDegagements();

        $idEtablissement = $this->getParam('id');

        $this->view->assign('etablissement', $service_etablissement->get($idEtablissement));
        $this->view->assign('avis', $service_etablissement->getAvisEtablissement($this->view->etablissement['general']['ID_ETABLISSEMENT'], $this->view->etablissement['general']['ID_DOSSIER_DONNANT_AVIS']));

        $rubriques = $serviceEtablissementEffectifsDegagements->getRubriques($idEtablissement, 'Etablissement');

        $this->view->assign('rubriques', $rubriques);
        $this->view->assign('champsvaleurliste', $serviceEtablissementEffectifsDegagements->getValeursListe());
    }

    public function effectifsDegagementsEtablissementEditAction()
    {
        $this->view->headLink()->appendStylesheet('/css/formulaire/edit-table.css', 'all');
        $this->view->headLink()->appendStylesheet('/css/formulaire/formulaire.css', 'all');

        $this->view->inlineScript()->appendFile('/js/formulaire/ordonnancement/Sortable.min.js', 'text/javascript');
        $this->view->inlineScript()->appendFile('/js/formulaire/ordonnancement/ordonnancement.js', 'text/javascript');
        $this->view->inlineScript()->appendFile('/js/formulaire/tableau/gestionTableau.js', 'text/javascript');
        $this->view->inlineScript()->appendFile('/js/formulaire/descriptif/edit.js', 'text/javascript');
        $this->view->inlineScript()->appendFile('/js/Calendrier/Calendar.js', 'text/javascript');


        $serviceEtablissementEffectifsDegagements = new Service_EtablissementEffectifsDegagements();
        $idEtablissement = $this->getParam('id');

        $this->effectifsDegagementsEtablissementAction();

        $request = $this->getRequest();
        if ($request->isPost()) {
            try {
                $post = $request->getPost();

                foreach ($post as $key => $value) {
                    // Informations concernant l'affichage des rubriques
                    if (0 === strpos($key, 'afficher_rubrique-')) {
                        $serviceEtablissementEffectifsDegagements->saveRubriqueDisplay($key, $idEtablissement, (int) $value);
                    }

                    // Informations concernant les valeurs des champs
                    if (0 === strpos($key, 'champ-')) {
                        $serviceEtablissementEffectifsDegagements->saveValeurChamp($key, $idEtablissement, 'Etablissement', $value);
                    }
                }

                $groupInputsPost = $serviceEtablissementEffectifsDegagements->groupInputByOrder($post, $idEtablissement, 'Etablissement');
                // Sauvegarde les changements dans les tableaux
                $serviceEtablissementEffectifsDegagements->saveChangeTable($this->view->rubriques, $groupInputsPost, 'Etablissement', $idEtablissement);

                $this->_helper->flashMessenger(['context' => 'success', 'title' => 'Mise à jour réussie !', 'message' => 'Les effectifs et dégagements ont bien été mis à jour.']);
            } catch (Exception $e) {
                $this->_helper->flashMessenger(['context' => 'error', 'title' => 'Mise à jour annulée', 'message' => 'Les effectifs et dégagements n\'ont pas été mis à jour. Veuillez rééssayez. ('.$e->getMessage().')']);
            }

            $this->_helper->redirector('effectifs-degagements-etablissement', null, null, ['id' => $this->_request->id]);
        }
    }

    public function avisDerogationsEtablissementAction()
    {
        $this->_helper->layout->setLayout('etablissement');
        $this->view->assign('historiqueAvisDerogations', $this->serviceEtablissement->getHistorique($this->_request->id)['AVIS_DEROGATIONS'] ?? []);
    }

    public function retablirEtablissementAction(): void
    {
        $this->_helper->viewRenderer->setNoRender();

        $previousUrl = $_SERVER['HTTP_REFERER'];
        $serviceEtablissement = new Service_Etablissement();

        $serviceEtablissement->retablirEtablissement($this->_getParam('idEtablissement'));

        $cacheSearch = Zend_Controller_Front::getInstance()->getParam('bootstrap')->getResource('cacheSearch');
        $cacheSearch->clean(Zend_Cache::CLEANING_MODE_ALL);

        $this->_helper->flashMessenger([
            'context' => 'success',
            'title' => "L'établissement a bien été rétabli",
            'message' => '',
        ]);

        $this->redirect($previousUrl);
    }
}
