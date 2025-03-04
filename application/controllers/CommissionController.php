<?php

class CommissionController extends Zend_Controller_Action
{
    public const ID_COMMISSION_COMMUNALE = 2;

    public const ID_GENRE_ETABLISSEMENT = 2;

    public const ID_GENRE_IGH = 5;

    public function deleteAction(): void
    {
        try {
            $this->_helper->viewRenderer->setNoRender();

            $requestId = $this->getRequest()->getParam('id');

            // Modèles de données
            $model_commission = new Model_DbTable_Commission();
            $model_ContactCommission = new Model_DbTable_CommissionContact();
            $model_MembreCommission = new Model_DbTable_CommissionMembre();
            $model_RegleCommission = new Model_DbTable_CommissionRegle();

            // Suppression des contacts
            foreach ($model_ContactCommission->fetchAll('ID_COMMISSION = '.$requestId) as $row) {
                $this->_helper->actionStack('delete', 'contact', 'default', ['item' => 'commission', 'id' => $row['ID_UTILISATEURINFORMATIONS'], 'id_item' => $row['ID_COMMISSION']]);
            }

            // Suppression des membres
            foreach ($model_MembreCommission->fetchAll('ID_COMMISSION = '.$requestId) as $row) {
                $this->_helper->actionStack('delete-membre', 'commission', 'default', ['id_membre' => $row['ID_COMMISSIONMEMBRE']]);
            }

            // Suppression des règles
            foreach ($model_RegleCommission->fetchAll('ID_COMMISSION = '.$requestId) as $row) {
                $this->_helper->actionStack('delete-regle', 'commission', 'default', ['id_regle' => $row['ID_REGLE']]);
            }

            // Suppression de la commission
            $model_commission->delete('ID_COMMISSION = '.$requestId);
            $this->_helper->flashMessenger([
                'context' => 'success',
                'title' => 'La commission a bien été supprimée',
                'message' => '',
            ]);
        } catch (Exception $exception) {
            $this->_helper->flashMessenger([
                'context' => 'error',
                'title' => 'Erreur lors de la suppression de la commission',
                'message' => $exception->getMessage(),
            ]);
        }
    }

    // Champ de compétence de la commission

    public function competencesAction(): void
    {
        // Les modèles
        $model_regles = new Model_DbTable_CommissionRegle();
        // On récupère les règles de la commission
        $this->view->assign('array_regles', $model_regles->get($this->getRequest()->getParam('id_commission')));
    }

    public function addRegleAction(): void
    {
        try {
            $this->_helper->viewRenderer->setNoRender();

            // Les modèles
            $model_regles = new Model_DbTable_CommissionRegle();

            // On ajoute une règle
            $row_regle = $model_regles->createRow();
            $row_regle->ID_COMMISSION = $this->getRequest()->getParam('id_commission');
            $row_regle->save();
            $this->_helper->flashMessenger([
                'context' => 'success',
                'title' => 'La régle a bien été enregistrées',
                'message' => '',
            ]);
        } catch (Exception $exception) {
            $this->_helper->flashMessenger([
                'context' => 'error',
                'title' => 'Erreur lors de l\'ajout de la régle',
                'message' => $exception->getMessage(),
            ]);
        }
    }

    public function deleteRegleAction(): void
    {
        try {
            $this->_helper->viewRenderer->setNoRender();

            $idRegle = $this->getRequest()->getParam('id_regle');

            // Les modèles
            $model_regles = new Model_DbTable_CommissionRegle();
            $model_reglesTypes = new Model_DbTable_CommissionRegleType();
            $model_reglesClasses = new Model_DbTable_CommissionRegleClasse();
            $model_reglesCategories = new Model_DbTable_CommissionRegleCategorie();
            $model_reglesEtudeVisite = new Model_DbTable_CommissionRegleEtudeVisite();
            $model_reglesLocalSommeil = new Model_DbTable_CommissionRegleLocalSommeil();

            // On supprime la règle
            $model_reglesTypes->delete('ID_REGLE = '.$idRegle);
            $model_reglesCategories->delete('ID_REGLE = '.$idRegle);
            $model_reglesClasses->delete('ID_REGLE = '.$idRegle);
            $model_reglesLocalSommeil->delete('ID_REGLE = '.$idRegle);
            $model_reglesEtudeVisite->delete('ID_REGLE = '.$idRegle);
            $model_regles->delete('ID_REGLE = '.$idRegle);

            $this->_helper->flashMessenger([
                'context' => 'success',
                'title' => 'Les régles ont bien été supprimées',
                'message' => '',
            ]);
        } catch (Exception $exception) {
            $this->_helper->flashMessenger([
                'context' => 'error',
                'title' => 'Erreur lors de la suppression des régles',
                'message' => $exception->getMessage(),
            ]);
        }
    }

    public function saveReglesAction(): void
    {
        try {
            $this->_helper->viewRenderer->setNoRender();

            // Les modèles
            $model_commission = new Model_DbTable_Commission();
            $model_regles = new Model_DbTable_CommissionRegle();
            $model_reglesTypes = new Model_DbTable_CommissionRegleType();
            $model_reglesClasses = new Model_DbTable_CommissionRegleClasse();
            $model_reglesCategories = new Model_DbTable_CommissionRegleCategorie();
            $model_reglesEtudeVisite = new Model_DbTable_CommissionRegleEtudeVisite();
            $model_reglesLocalSommeil = new Model_DbTable_CommissionRegleLocalSommeil();

            // On spécifi l'ID de la règle à null
            $id_regle = null;
            $rowset_regle = null;

            // On analyse toutes les données envoyé en POST
            foreach ($_POST['ID_REGLE'] as $id_regle) {
                // Mise à jour de la règle à sauvegarder
                // On récupère la ligne
                $rowset_regle = $model_regles->find($id_regle)->current();

                // On regarde dans quelle commission nous sommes
                $row_commission = $model_commission->find($rowset_regle->ID_COMMISSION)->current();

                // On supprime les porteuses de la règle
                $model_reglesTypes->delete('ID_REGLE = '.$id_regle);
                $model_reglesClasses->delete('ID_REGLE = '.$id_regle);
                $model_reglesCategories->delete('ID_REGLE = '.$id_regle);
                $model_reglesLocalSommeil->delete('ID_REGLE = '.$id_regle);
                $model_reglesEtudeVisite->delete('ID_REGLE = '.$id_regle);

                // On met à jour la commune et le groupement
                $rowset_regle->NUMINSEE_COMMUNE = (self::ID_COMMISSION_COMMUNALE == $row_commission->ID_COMMISSIONTYPE) ? $_POST[$id_regle.'_NUMINSEE_COMMUNE'] : null;
                $rowset_regle->ID_GROUPEMENT = (self::ID_COMMISSION_COMMUNALE != $row_commission->ID_COMMISSIONTYPE) ? $_POST[$id_regle.'_ID_GROUPEMENT'] : null;

                // On sauvegarde la règle
                $rowset_regle->save();

                // On sauvegarde la catégorie
                foreach ($_POST[$id_regle.'_ID_CATEGORIE'] as $categorie) {
                    $model_reglesCategories->insert([
                        'ID_REGLE' => $id_regle,
                        'ID_CATEGORIE' => $categorie,
                    ]);
                }

                // On sauvegarde les types d'activités
                foreach ($_POST[$id_regle.'_ID_TYPE'] as $type) {
                    $model_reglesTypes->insert([
                        'ID_REGLE' => $id_regle,
                        'ID_TYPE' => $type,
                    ]);
                }

                // On sauvegarde les classes IGH
                foreach ($_POST[$id_regle.'_ID_CLASSE'] as $classe) {
                    $model_reglesClasses->insert([
                        'ID_REGLE' => $id_regle,
                        'ID_CLASSE' => $classe,
                    ]);
                }

                // Local sommeil
                foreach ($_POST[$id_regle.'_LOCALSOMMEIL'] as $localsommeil) {
                    $model_reglesLocalSommeil->insert([
                        'ID_REGLE' => $id_regle,
                        'LOCALSOMMEIL' => $localsommeil,
                    ]);
                }

                // Etude visite
                foreach ($_POST[$id_regle.'_ETUDEVISITE'] as $etudevisite) {
                    $model_reglesEtudeVisite->insert([
                        'ID_REGLE' => $id_regle,
                        'ETUDEVISITE' => $etudevisite,
                    ]);
                }
            }

            $this->_helper->flashMessenger([
                'context' => 'success',
                'title' => 'Les régles ont bien été enregistrées',
                'message' => '',
            ]);
        } catch (Exception $exception) {
            $this->_helper->flashMessenger([
                'context' => 'error',
                'title' => 'Erreur lors de l\'enregistrement des régles',
                'message' => $exception->getMessage(),
            ]);
        }
    }

    public function applyReglesAction(): void
    {
        try {
            $this->_helper->viewRenderer->setNoRender();

            // Modèles et services
            $model_etablissementInformation = new Model_DbTable_EtablissementInformations();
            $model_commission = new Model_DbTable_Commission();

            if (!isset($_POST['ID_COMMISSION']) && !$_POST['ID_COMMISSION']) {
                throw new Exception('Aucun ID de commission fournit dans la requête');
            }

            $id_commission = $_POST['ID_COMMISSION'];
            $regles = $model_commission->getRegles($id_commission);

            $search = new Model_DbTable_Search();
            $search->setItem('etablissement');
            $search->setCriteria('etablissementinformations.ID_GENRE', [2, 5]);
            $search->setCriteria('etablissementadresse.NUMINSEE_COMMUNE IS NOT NULL');
            $rowset_ets = $search->run(false, null, false)->toArray();

            $ets_to_update = [];

            // Pour tout les ets, on récup leur commission par défaut
            foreach ($rowset_ets as $key => $row) {
                // On récupère la commission
                foreach ($regles as $regle) {
                    if (
                        (
                            self::ID_GENRE_ETABLISSEMENT == $row['ID_GENRE']
                            && in_array($row['NUMINSEE_COMMUNE'], $regle['NUMINSEE_COMMUNE'])
                            && $row['LOCALSOMMEIL_ETABLISSEMENTINFORMATIONS'] == $regle['LOCALSOMMEIL']
                            && $row['ID_TYPE'] == $regle['ID_TYPE']
                            && $row['ID_CATEGORIE'] == $regle['ID_CATEGORIE']
                        )
                        || (
                            self::ID_GENRE_IGH == $row['ID_GENRE']
                            && in_array($row['NUMINSEE_COMMUNE'], $regle['NUMINSEE_COMMUNE'])
                            && $row['ID_CLASSE'] == $regle['ID_CLASSE']
                        )
                    ) {
                        $ets_to_update[] = $row['ID_ETABLISSEMENTINFORMATIONS'];

                        break;
                    }
                }

                // save memory
                unset($rowset_ets[$key]);
            }

            if ([] !== $ets_to_update) {
                $model_etablissementInformation->update(['ID_COMMISSION' => $id_commission], 'ID_ETABLISSEMENTINFORMATIONS IN ('.implode(',', $ets_to_update).')');

                // removes cache if any changes
                Zend_Controller_Front::getInstance()->getParam('bootstrap')->getResource('cache')->clean(Zend_Cache::CLEANING_MODE_ALL);
            }
        } catch (Exception $exception) {
            $this->_helper->flashMessenger([
                'context' => 'error',
                'title' => 'Erreur inattendue',
                'message' => $exception->getMessage(),
            ]);
        }
    }

    // Membres de la commission
    public function membresAction(): void
    {
        // Les modèles
        $model_types = new Model_DbTable_Type();
        $model_membres = new Model_DbTable_CommissionMembre();

        // On récupère les règles de la commission
        $this->view->assign('array_membres', $model_membres->get($this->getRequest()->getParam('id_commission')));

        // On met le libellé du type dans le tableau des activités
        $types = $model_types->fetchAll()->toArray();
        $types_sort = [];

        foreach ($types as $_type) {
            $types_sort[$_type['ID_TYPE']] = $_type;
        }

        foreach ($this->view->array_membres as &$membre) {
            $type_sort = [];

            foreach ($membre['types'] as $type) {
                if (!array_key_exists($types_sort[$type['ID_TYPE']]['LIBELLE_TYPE'], $type_sort)) {
                    $type_sort[$types_sort[$type['ID_TYPE']]['LIBELLE_TYPE']] = [];
                }

                $type_sort[$types_sort[$type['ID_TYPE']]['LIBELLE_TYPE']][] = $type;
            }

            $membre['types'] = $type_sort;
        }
    }

    public function addMembreAction(): void
    {
        try {
            $this->_helper->viewRenderer->setNoRender();

            // Les modèles
            $model_membres = new Model_DbTable_CommissionMembre();

            // On ajoute une règle
            $row_membre = $model_membres->createRow();
            $row_membre->ID_COMMISSION = $this->getRequest()->getParam('id_commission');
            $row_membre->LIBELLE_COMMISSIONMEMBRE = '';
            $row_membre->PRESENCE_COMMISSIONMEMBRE = 0;
            $row_membre->save();

            $this->_helper->flashMessenger([
                'context' => 'success',
                'title' => 'Le membre a bien été ajouté',
                'message' => '',
            ]);
        } catch (Exception $exception) {
            $this->_helper->flashMessenger([
                'context' => 'error',
                'title' => "Erreur lors de l'ajout du membre",
                'message' => $exception->getMessage(),
            ]);
        }
    }

    public function deleteMembreAction(): void
    {
        try {
            $this->_helper->viewRenderer->setNoRender();

            $idMembre = $this->getRequest()->getParam('id_membre');

            // Les modèles
            $model_membres = new Model_DbTable_CommissionMembre();
            $model_membresTypes = new Model_DbTable_CommissionMembreTypeActivite();
            $model_membresClasses = new Model_DbTable_CommissionMembreClasse();
            $model_membresCategories = new Model_DbTable_CommissionMembreCategorie();
            $model_membresDossierNatures = new Model_DbTable_CommissionMembreDossierNature();
            $model_membresDossierTypes = new Model_DbTable_CommissionMembreDossierType();

            // On supprime les courriers
            $courrier_path = REAL_DATA_PATH.DS.'uploads'.DS.'courriers';
            $row_membre = $model_membres->find($idMembre)->current();
            unlink($courrier_path.DS.$idMembre.'ODJ'.$row_membre->COURRIER_ODJ);
            unlink($courrier_path.DS.$idMembre.'CONVOCATIONVISITE'.$row_membre->COURRIER_CONVOCATIONVISITE);
            unlink($courrier_path.DS.$idMembre.'CONVOCATIONSALLE'.$row_membre->COURRIER_CONVOCATIONSALLE);

            // On supprime la règle
            $model_membresTypes->delete('ID_COMMISSIONMEMBRE = '.$idMembre);
            $model_membresCategories->delete('ID_COMMISSIONMEMBRE = '.$idMembre);
            $model_membresClasses->delete('ID_COMMISSIONMEMBRE = '.$idMembre);
            $model_membresDossierNatures->delete('ID_COMMISSIONMEMBRE = '.$idMembre);
            $model_membresDossierTypes->delete('ID_COMMISSIONMEMBRE = '.$idMembre);
            $row_membre->delete();

            $this->_helper->flashMessenger([
                'context' => 'success',
                'title' => 'Le membre a bien été supprimé',
                'message' => '',
            ]);
        } catch (Exception $exception) {
            $this->_helper->flashMessenger([
                'context' => 'error',
                'title' => 'Erreur lors de la suppression du membre',
                'message' => $exception->getMessage(),
            ]);
        }
    }

    public function saveMembresAction(): void
    {
        try {
            $this->_helper->viewRenderer->setNoRender();

            // Les modèles
            $model_membres = new Model_DbTable_CommissionMembre();
            $model_membresTypes = new Model_DbTable_CommissionMembreTypeActivite();
            $model_membresClasses = new Model_DbTable_CommissionMembreClasse();
            $model_membresCategories = new Model_DbTable_CommissionMembreCategorie();
            $model_membresDossierNatures = new Model_DbTable_CommissionMembreDossierNature();
            $model_membresDossierTypes = new Model_DbTable_CommissionMembreDossierType();

            // On spécifi l'ID de la règle à null
            $id_membre = null;
            $rowset_membre = null;

            // On analyse toutes les données envoyé en POST
            foreach ($_POST['ID_COMMISSIONMEMBRE'] as $id_membre) {
                // Mise à jour de la règle à sauvegarder
                // On récupère la ligne
                $rowset_membre = $model_membres->find($id_membre)->current();

                // On supprime les porteuses de la règle
                $model_membresTypes->delete('ID_COMMISSIONMEMBRE = '.$id_membre);
                $model_membresClasses->delete('ID_COMMISSIONMEMBRE = '.$id_membre);
                $model_membresCategories->delete('ID_COMMISSIONMEMBRE = '.$id_membre);
                $model_membresDossierNatures->delete('ID_COMMISSIONMEMBRE = '.$id_membre);
                $model_membresDossierTypes->delete('ID_COMMISSIONMEMBRE = '.$id_membre);

                // On met à jour la commune et le groupement
                $rowset_membre->LIBELLE_COMMISSIONMEMBRE = $_POST[$id_membre.'_LIBELLE_COMMISSIONMEMBRE'];
                $rowset_membre->PRESENCE_COMMISSIONMEMBRE = $_POST[$id_membre.'_PRESENCE_COMMISSIONMEMBRE'];
                $rowset_membre->ID_GROUPEMENT = null;

                if ('1' === $_POST[$id_membre.'_typemembre']) {
                    $rowset_membre->ID_GROUPEMENT = $_POST[$id_membre.'_ID_GROUPEMENT'];
                }

                // On sauvegarde la règle
                $rowset_membre->save();

                // On sauvegarde la catégorie
                foreach ($_POST[$id_membre.'_ID_CATEGORIE'] as $categorie) {
                    $model_membresCategories->insert([
                        'ID_COMMISSIONMEMBRE' => $id_membre,
                        'ID_CATEGORIE' => $categorie,
                    ]);
                }

                // On sauvegarde les types d'activités
                foreach ($_POST[$id_membre.'_ID_TYPEACTIVITE'] as $type) {
                    $model_membresTypes->insert([
                        'ID_COMMISSIONMEMBRE' => $id_membre,
                        'ID_TYPEACTIVITE' => $type,
                    ]);
                }

                // On sauvegarde les classes IGH
                foreach ($_POST[$id_membre.'_ID_CLASSE'] as $classe) {
                    $model_membresClasses->insert([
                        'ID_COMMISSIONMEMBRE' => $id_membre,
                        'ID_CLASSE' => $classe,
                    ]);
                }

                // On sauvegarde les types de dossier
                foreach ($_POST[$id_membre.'_ID_DOSSIERTYPE'] as $type) {
                    $model_membresDossierTypes->insert([
                        'ID_COMMISSIONMEMBRE' => $id_membre,
                        'ID_DOSSIERTYPE' => $type,
                    ]);
                }

                // On sauvegarde les natures du dossier
                foreach ($_POST[$id_membre.'_ID_DOSSIERNATURE'] as $type) {
                    $model_membresDossierNatures->insert([
                        'ID_COMMISSIONMEMBRE' => $id_membre,
                        'ID_DOSSIERNATURE' => $type,
                    ]);
                }
            }

            $this->_helper->flashMessenger([
                'context' => 'success',
                'title' => 'Les modifications ont bien été sauvegardées',
                'message' => '',
            ]);
        } catch (Exception $exception) {
            $this->_helper->flashMessenger([
                'context' => 'error',
                'title' => 'Erreur lors de la sauvegarde des modifications',
                'message' => $exception->getMessage(),
            ]);
        }
    }

    // Contacts de la commission
    public function contactsAction() {}

    // Courriers types des membres de la commission
    public function courriersAction(): void
    {
        // Les modèles
        $model_membres = new Model_DbTable_CommissionMembre();

        // On récupère la liste des membres de la commission
        $this->view->assign('rowset_membres', $model_membres->fetchAll('ID_COMMISSION = '.$this->getRequest()->getParam('id_commission')));
    }

    // Courriers types des membres de la commission
    public function documentsAction(): void
    {
        // Les modèles
        $model_commission = new Model_DbTable_Commission();

        $commission = $model_commission->find($this->getRequest()->getParam('id_commission'))->current();

        // On récupère la liste des membres de la commission
        $this->view->assign('name_document_cr', $commission->DOCUMENT_CR);
    }

    // Courriers types des membres de la commission
    public function addDocumentAction(): void
    {
        try {
            $this->_helper->viewRenderer->setNoRender();

            $error = 'null';

            // Extension du fichier uploadé
            $string_extension = strrchr($_FILES['COURRIER']['name'], '.');

            // On check si on veut uploader un document odt
            if ('.odt' == $string_extension) {
                if (move_uploaded_file($_FILES['COURRIER']['tmp_name'], REAL_DATA_PATH.DS.'uploads'.DS.'documents_commission'.DS.$_FILES['COURRIER']['name'])) {
                    // Les modèles
                    $model_commission = new Model_DbTable_Commission();

                    $commission = $model_commission->find($this->getRequest()->getParam('id_commission'))->current();

                    // Si il y a déjà un courrier, on le supprime
                    if (null != $commission->DOCUMENT_CR) {
                        unlink(REAL_DATA_PATH.DS.'uploads'.DS.'documents_commission'.DS.$commission->DOCUMENT_CR);
                    }

                    // On met à jour le libellé du courrier modifié
                    $commission->DOCUMENT_CR = $_FILES['COURRIER']['name'];

                    // et on sauvegarde
                    $commission->save();
                } else {
                    $error = 'Le téléchargement a échoué.';
                }
            } else {
                $error = 'Extension non supportée.';
            }

            // CALLBACK
            echo sprintf("<script type='text/javascript'>window.top.window.callback('%s');</script>", $error);
            $this->_helper->flashMessenger([
                'context' => 'success',
                'title' => 'Le document a bien été sauvegardé',
                'message' => '',
            ]);
        } catch (Exception $exception) {
            $this->_helper->flashMessenger([
                'context' => 'error',
                'title' => 'Erreur lors de la sauvegarde du document',
                'message' => $exception->getMessage(),
            ]);
        }
    }

    // Courriers types des membres de la commission
    public function deleteDocumentAction(): void
    {
        try {
            $this->_helper->viewRenderer->setNoRender();

            // Les modèles
            $model_commission = new Model_DbTable_Commission();

            $commission = $model_commission->find($this->getRequest()->getParam('id_commission'))->current();

            // On supprime le fichier
            unlink(REAL_DATA_PATH.DS.'uploads'.DS.'documents_commission'.DS.$commission->DOCUMENT_CR);

            // On met à null dans la DB
            $commission->DOCUMENT_CR = null;
            $commission->save();

            $this->_helper->flashMessenger([
                'context' => 'success',
                'title' => 'Le document a bien été supprimé',
                'message' => '',
            ]);
        } catch (Exception $exception) {
            $this->_helper->flashMessenger([
                'context' => 'error',
                'title' => 'Erreur lors de la suppression du document',
                'message' => $exception->getMessage(),
            ]);
        }
    }

    // Courriers types des membres de la commission
    public function addCourrierAction(): void
    {
        try {
            $this->_helper->viewRenderer->setNoRender();

            $error = 'null';
            $idMembre = $this->getRequest()->getParam('id_membre');
            $type = $this->getRequest()->getParam('type');

            // Extension du fichier uploadé
            $string_extension = strrchr($_FILES['COURRIER']['name'], '.');

            // On check si on veut uploader un document odt
            if ('.odt' == $string_extension) {
                if (move_uploaded_file($_FILES['COURRIER']['tmp_name'], REAL_DATA_PATH.DS.'uploads'.DS.'courriers'.DS.$idMembre.$type.'_'.$_FILES['COURRIER']['name'])) {
                    // Les modèles
                    $model_membres = new Model_DbTable_CommissionMembre();

                    // On récupère l'instance du membres
                    $row_membre = $model_membres->find($idMembre)->current();
                    $row = 'COURRIER_'.$type;

                    // Si il y a déjà un courrier, on le supprime
                    if (null != $row_membre->{$row} && $row_membre->{$row} !== $_FILES['COURRIER']['name']) {
                        unlink(REAL_DATA_PATH.DS.'uploads'.DS.'courriers'.DS.$idMembre.$type.'_'.$row_membre->{$row});
                    }

                    // On met à jour le libellé du courrier modifié
                    $row_membre->{$row} = $_FILES['COURRIER']['name'];

                    // et on sauvegarde
                    $row_membre->save();
                } else {
                    $error = 'Le téléchargement a échoué.';
                }
            } else {
                $error = 'Extension non supportée.';
            }

            // CALLBACK
            echo sprintf("<script type='text/javascript'>window.top.window.callback('%s');</script>", $error);
            $this->_helper->flashMessenger([
                'context' => 'success',
                'title' => 'Le document a bien été sauvegardé',
                'message' => '',
            ]);
        } catch (Exception $exception) {
            $this->_helper->flashMessenger([
                'context' => 'error',
                'title' => 'Erreur lors de la sauvegarde du document',
                'message' => $exception->getMessage(),
            ]);
        }
    }

    public function deleteCourrierAction(): void
    {
        try {
            $this->_helper->viewRenderer->setNoRender();

            $idMembre = $this->getRequest()->getParam('id_membre');
            $type = $this->getRequest()->getParam('type');

            // Les modèles
            $model_membres = new Model_DbTable_CommissionMembre();

            // On récupère l'instance du membres
            $row_membre = $model_membres->find($idMembre)->current();
            $row = 'COURRIER_'.$type;

            // On supprime le fichier
            unlink(REAL_DATA_PATH.DS.'uploads'.DS.'courriers'.DS.$idMembre.$type.'_'.$row_membre->{$row});

            // On met à null dans la DB
            $row_membre->{$row} = null;
            $row_membre->save();
            $this->_helper->flashMessenger([
                'context' => 'success',
                'title' => 'Le document a bien été supprimé',
                'message' => '',
            ]);
        } catch (Exception $exception) {
            $this->_helper->flashMessenger([
                'context' => 'error',
                'title' => 'Erreur lors de la suppression du document',
                'message' => $exception->getMessage(),
            ]);
        }
    }
}
