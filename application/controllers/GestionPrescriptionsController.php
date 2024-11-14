<?php

class GestionPrescriptionsController extends Zend_Controller_Action
{
    public function init(): void
    {
        // Actions à effectuées en AJAX
        $ajaxContext = $this->_helper->getHelper('AjaxContext');
        $ajaxContext->addActionContext('selectiontexte', 'json')
            ->addActionContext('selectionarticle', 'json')
            ->initContext()
        ;

        $this->_helper->layout->setLayout('menu_admin');
    }

    public function indexAction(): void
    {
        $this->_helper->layout->setLayout('menu_admin');

        $service_prescription = new Service_Prescriptions();

        $this->view->assign('listePrescriptionCat', $service_prescription->getCategories());

        // on recupere les prescriptions qui n'ont ni catégories, ni texte, ni article
        $this->view->assign('prescriptionType', $service_prescription->showPrescriptionType(0, 0, 0));
    }

    public function showprescriptiontexteAction(): void
    {
        $service_prescription = new Service_Prescriptions();
        if ($this->_getParam('id')) {
            // ici on affiche les textes appartenant à la catégorie dont on passe l'id en param
            $idCategorie = $this->_getParam('id');
            $dbPrescripionTexte = new Model_DbTable_PrescriptionTexte();
            $this->view->assign('idCategorie', $idCategorie);
            $this->view->assign('listePrescriptionTexte', $dbPrescripionTexte->recupPrescriptionTexte($idCategorie));

            $this->view->assign('prescriptionType', $service_prescription->showPrescriptionType($this->_getParam('id'), 0, 0));
        }
    }

    public function showprescriptionarticleAction(): void
    {
        $service_prescription = new Service_Prescriptions();
        if ($this->_getParam('idTexte')) {
            $this->view->assign('idTexte', $this->_getParam('idTexte'));
            // ici on affiche les textes appartenant à la catégorie dont on passe l'id en param
            $idTexte = $this->_getParam('idTexte');
            $dbPrescripionArticle = new Model_DbTable_PrescriptionArticle();
            $this->view->assign('listePrescriptionArticle', $dbPrescripionArticle->recupPrescriptionArticle($idTexte));

            $dbPrescripionTexte = new Model_DbTable_PrescriptionTexte();
            $idCategorie = $dbPrescripionTexte->find($idTexte)->current()->toArray();
            $idCategorie = $idCategorie['ID_PRESCRIPTIONCAT'];
            $this->view->assign('prescriptionType', $service_prescription->showPrescriptionType($idCategorie, $this->_getParam('idTexte'), 0));
        }
    }

    public function showarticlecontenuAction(): void
    {
        // On affiche les prescriptions contenues dans la catégorie d'article selectionnée
        $service_prescription = new Service_Prescriptions();
        if ($this->_getParam('idArticle')) {
            $dbArticle = new Model_DbTable_PrescriptionArticle();
            $article = $dbArticle->find($this->_getParam('idArticle'))->current();
            $dbTexte = new Model_DbTable_PrescriptionTexte();
            $texte = $dbTexte->find($article['ID_PRESCRIPTIONTEXTE'])->current();
            $this->view->assign('prescriptionType', $service_prescription->showPrescriptionType($texte['ID_PRESCRIPTIONCAT'], $article['ID_PRESCRIPTIONTEXTE'], $this->_getParam('idArticle')));
            $this->view->assign('idArticle', $this->_getParam('idArticle'));
        }
    }

    // GESTION CATEGORIES
    public function formcategorieAction(): void
    {
        if ($this->_getParam('id')) {
            $this->view->assign('idCategorie', $this->_getParam('id'));
            $dbCat = new Model_DbTable_PrescriptionCat();
            $this->view->assign('catInfo', $dbCat->find($this->_getParam('id'))->current());
        }
    }

    public function savecategorieAction(): void
    {
        try {
            $dbCat = new Model_DbTable_PrescriptionCat();

            if ($this->_getParam('idCat')) {
                // Edition
                $this->view->assign('do', 'edit');
                $categorie = $dbCat->find($this->_getParam('idCat'))->current();
            } else {
                // Création
                $this->view->assign('do', 'new');
                $categorie = $dbCat->createRow();
                // On recupere le max num pour inserer la nouvelle catégorie
                $numMax = $dbCat->recupMaxNumCat();
                $numCategorie = $numMax['maxnum'];
                ++$numCategorie;
                $categorie->NUM_PRESCRIPTION_CAT = $numCategorie++;
            }

            $categorie->LIBELLE_PRESCRIPTION_CAT = $this->_getParam('LIBELLE_PRESCRIPTION_CAT');
            $categorie->save();
            $this->view->assign('categorie', $categorie);

            $this->_helper->flashMessenger([
                'context' => 'success',
                'title' => 'La catégorie a bien été sauvegardée',
                'message' => '',
            ]);
        } catch (Exception $exception) {
            $this->_helper->flashMessenger([
                'context' => 'error',
                'title' => 'Erreur lors de la sauvegarde de la catégorie',
                'message' => $exception->getMessage(),
            ]);
        }
    }

    // GESTION TEXTES
    public function formtexteAction(): void
    {
        if ($this->_getParam('idCat')) {
            // Création d'un nouveau texte
            $this->view->assign('idCategorie', $this->_getParam('idCat'));
        } elseif ($this->_getParam('idTexte')) {
            // edition d'un texte existant
            $this->view->assign('idTexte', $this->_getParam('idTexte'));
            $dbTexte = new Model_DbTable_PrescriptionTexte();
            $this->view->assign('texteInfo', $dbTexte->find($this->_getParam('idTexte'))->current());
        }
    }

    public function savetexteAction(): void
    {
        try {
            $dbTexte = new Model_DbTable_PrescriptionTexte();
            $texte = null;

            if ($this->_getParam('idCategorie')) {
                // Sauvegarde d'un nouveau texte
                $this->view->assign('do', 'new');
                $texte = $dbTexte->createRow();
                // On recupere le num max de la catégorie
                $numMax = $dbTexte->recupMaxNumTexte($this->_getParam('idCategorie'));

                if (!$numMax['maxnum']) {
                    $numTexte = 1;
                } else {
                    $numTexte = $numMax['maxnum'];
                    ++$numTexte;
                }

                $texte->NUM_PRESCRIPTIONTEXTE = $numTexte;
                $texte->ID_PRESCRIPTIONCAT = $this->_getParam('idCategorie');
            } elseif ($this->_getParam('idTexte')) {
                // edition d'un texte
                $this->view->assign('do', 'edit');
                $texte = $dbTexte->find($this->_getParam('idTexte'))->current();
            }

            $texte->LIBELLE_PRESCRIPTIONTEXTE = $this->_getParam('LIBELLE_PRESCRIPTIONTEXTE');
            $texte->save();
            $this->view->assign('texte', $texte);

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
    }

    // GESTION ARTICLES
    public function formarticleAction(): void
    {
        if ($this->_getParam('idTexte')) {
            // Création d'un nouveau texte
            $this->view->assign('idTexte', $this->_getParam('idTexte'));
        } elseif ($this->_getParam('idArticle')) {
            // edition d'un texte existant
            $this->view->assign('idArticle', $this->_getParam('idArticle'));
            $dbArticle = new Model_DbTable_PrescriptionArticle();
            $this->view->assign('articleInfo', $dbArticle->find($this->_getParam('idArticle'))->current());
        }
    }

    public function savearticleAction(): void
    {
        try {
            $dbArticle = new Model_DbTable_PrescriptionArticle();
            $article = null;

            if ($this->_getParam('idTexte')) {
                // Sauvegarde d'un nouveau article
                $this->view->assign('do', 'new');
                $article = $dbArticle->createRow();
                // On recupere le num max de la catégorie
                $numMax = $dbArticle->recupMaxNumArticle($this->_getParam('idTexte'));

                if (!$numMax['maxnum']) {
                    $numArticle = 1;
                } else {
                    $numArticle = $numMax['maxnum'];
                    ++$numArticle;
                }

                $article->NUM_PRESCRIPTIONARTICLE = $numArticle;
                $article->ID_PRESCRIPTIONTEXTE = $this->_getParam('idTexte');
            } elseif ($this->_getParam('idArticle')) {
                // edition d'un article
                $this->view->assign('do', 'edit');
                $article = $dbArticle->find($this->_getParam('idArticle'))->current();
            }

            $article->LIBELLE_PRESCRIPTIONARTICLE = $this->_getParam('LIBELLE_PRESCRIPTIONARTICLE');
            $article->save();
            $this->view->assign('article', $article);

            $this->_helper->flashMessenger([
                'context' => 'success',
                'title' => 'L\'article a bien été sauvegardé',
                'message' => '',
            ]);
        } catch (Exception $exception) {
            $this->_helper->flashMessenger([
                'context' => 'error',
                'title' => "Erreur lors de la sauvegarde de l'article",
                'message' => $exception->getMessage(),
            ]);
        }
    }

    public function formprescriptionAction(): void
    {
        $dbTexte = new Model_DbTable_PrescriptionTexteListe();
        $dbArticle = new Model_DbTable_PrescriptionArticleListe();

        $this->view->assign('listeTextes', $dbTexte->getAllTextes(1));
        $this->view->assign('listeArticles', $dbArticle->getAllArticles(1));

        $service_prescription = new Service_Prescriptions();
        if ($this->_getParam('idPrescType')) {
            $this->view->assign('idPrescType', $this->_getParam('idPrescType'));
            $this->view->assign('do', 'edit');

            $this->view->assign('assoc', $service_prescription->getPrescriptionTypeDetail($this->_getParam('idPrescType')));
        } else {
            $this->view->assign('do', 'new');
            $dbCategorie = new Model_DbTable_PrescriptionCat();
            $this->view->assign('listeCategorie', $dbCategorie->recupPrescriptionCat());

            switch ($this->_getParam('typePresc')) {
                case 'addPrescriptionCat':
                    // cas d'une prescription dans une catégorie
                    $this->view->assign('categorie', $this->_getParam('empl'));

                    break;

                case 'addPrescriptionTexte':
                    // cas d'une prescription dans un texte
                    $dbPrescTexte = new Model_DbTable_PrescriptionTexte();
                    $texteInfo = $dbPrescTexte->find($this->_getParam('empl'))->current();
                    $this->view->assign('categorie', $texteInfo->ID_PRESCRIPTIONCAT);
                    $this->view->assign('texte', $this->_getParam('empl'));

                    break;

                case 'addPrescriptionArticle':
                    // cas d'une prescription dans un article
                    $dbPrescArticle = new Model_DbTable_PrescriptionArticle();
                    $articleInfo = $dbPrescArticle->find($this->_getParam('empl'))->current();
                    $this->view->assign('texte', $articleInfo->ID_PRESCRIPTIONTEXTE);

                    $dbPrescTexte = new Model_DbTable_PrescriptionTexte();
                    $texteInfo = $dbPrescTexte->find($this->view->texte)->current();
                    $this->view->assign('categorie', $texteInfo->ID_PRESCRIPTIONCAT);

                    $this->view->assign('article', $this->_getParam('empl'));

                    break;

                default:
                    break;
            }
        }
    }

    public function emplacementAction(): void
    {
        $this->view->assign('categorie', $this->_getParam('PRESCRIPTIONTYPE_CATEGORIE'));
        $this->view->assign('texte', $this->_getParam('PRESCRIPTIONTYPE_TEXTE'));
        $this->view->assign('article', $this->_getParam('PRESCRIPTIONTYPE_ARTICLE'));

        if (
            !$this->view->categorie
            && !$this->view->texte
            && !$this->view->article
        ) {
            // on affiche les catégories
            $dbPrescriptionCat = new Model_DbTable_PrescriptionCat();
            $listePrescriptionCat = $dbPrescriptionCat->recupPrescriptionCat();
            $this->view->assign('categorieListe', $listePrescriptionCat);
        } elseif (
            !$this->view->texte
            && !$this->view->article
        ) {
            $dbPrescriptionCat = new Model_DbTable_PrescriptionCat();
            $categorieLibelle = $dbPrescriptionCat->find($this->view->categorie)->current()->toArray();
            $this->view->assign('categorieLibelle', $categorieLibelle['LIBELLE_PRESCRIPTION_CAT']);
            // on viens de choisir une catégorie il faut afficher les texte de la catégorie
            $dbTexte = new Model_DbTable_PrescriptionTexte();
            $this->view->assign('texteListe', $dbTexte->recupPrescriptionTexte($this->_getParam('PRESCRIPTIONTYPE_CATEGORIE')));
        } elseif (!$this->view->article) {
            $dbPrescriptionCat = new Model_DbTable_PrescriptionCat();
            $categorieLibelle = $dbPrescriptionCat->find($this->view->categorie)->current()->toArray();
            $this->view->assign('categorieLibelle', $categorieLibelle['LIBELLE_PRESCRIPTION_CAT']);
            $dbTexte = new Model_DbTable_PrescriptionTexte();
            $texteLibelle = $dbTexte->find($this->view->texte)->current()->toArray();
            $this->view->assign('texteLibelle', $texteLibelle['LIBELLE_PRESCRIPTIONTEXTE']);
            // on viens de choisir un texte il faut afficher les articles
            $dbArticle = new Model_DbTable_PrescriptionArticle();
            $this->view->assign('texteArticle', $dbArticle->recupPrescriptionArticle($this->_getParam('PRESCRIPTIONTYPE_TEXTE')));
        } else {
            $dbPrescriptionCat = new Model_DbTable_PrescriptionCat();
            $categorieLibelle = $dbPrescriptionCat->find($this->view->categorie)->current()->toArray();
            $this->view->assign('categorieLibelle', $categorieLibelle['LIBELLE_PRESCRIPTION_CAT']);

            $dbTexte = new Model_DbTable_PrescriptionTexte();
            $texteLibelle = $dbTexte->find($this->view->texte)->current()->toArray();
            $this->view->assign('texteLibelle', $texteLibelle['LIBELLE_PRESCRIPTIONTEXTE']);

            $dbArticle = new Model_DbTable_PrescriptionArticle();
            $articleLibelle = $dbArticle->find($this->view->article)->current()->toArray();
            $this->view->assign('articleLibelle', $articleLibelle['LIBELLE_PRESCRIPTIONARTICLE']);
        }
    }

    public function saveprescriptiontypeAction(): void
    {
        if ($this->_request->isPost()) {
            try {
                $post = $this->_request->getPost();
                $service_prescription = new Service_Prescriptions();

                if ('' != $post['ID_PRESCRIPTIONTYPE']) {
                    $idPrescriptionType = $service_prescription->savePrescriptionType($post, $post['ID_PRESCRIPTIONTYPE']);
                } else {
                    $idPrescriptionType = $service_prescription->savePrescriptionType($post);
                }

                $this->view->assign('prescriptionType', $service_prescription->getPrescriptionTypeDetail($idPrescriptionType));
                $this->view->assign('idPrescriptionType', $this->view->prescriptionType[0]['ID_PRESCRIPTIONTYPE']);
            } catch (Exception $e) {
                $this->_helper->flashMessenger([
                    'context' => 'error',
                    'title' => 'Erreur lors de la sauvegarde de la prescription',
                    'message' => $e->getMessage(),
                ]);
            }
        }
    }

    // GESTION DES TEXTES
    public function gestionTextesAction(): void
    {
        $this->_helper->layout->setLayout('menu_admin');
        $service_prescTextes = new Service_Prescriptions();

        if ($this->_request->isPost()) {
            try {
                $post = $this->_request->getPost();
                if ('add' == $post['action']) {
                    $service_prescTextes->saveTexte($post);
                    $this->_helper->flashMessenger(['context' => 'success', 'title' => 'Enregistrement effectué.', 'message' => 'La texte a bien été enregistré']);
                } elseif ('edit' == $post['action']) {
                    $service_prescTextes->saveTexte($post, $post['id_texte']);
                    $this->_helper->flashMessenger(['context' => 'success', 'title' => 'Modification effectuée.', 'message' => 'La texte a bien été enregistré']);
                } elseif ('replace' == $post['action']) {
                    $service_prescTextes->replaceTexte($post['id_texte'], $post['idTexteReplace']);
                    $this->_helper->flashMessenger(['context' => 'success', 'title' => 'Suppression effectuée.', 'message' => 'Le texte a bien été supprimé']);
                }
            } catch (Exception $e) {
                $this->_helper->flashMessenger(['context' => 'error', 'title' => "Erreur lors de l'enregistrement.", 'message' => 'Une erreur s\'est produite lors de l\enregistrement de la prescription ('.$e->getMessage().')']);
            }
        }

        $liste_textes = $service_prescTextes->getTextesListe();

        $this->view->assign('liste_textes', $liste_textes);
    }

    public function gestionTextesAddAction(): void
    {
        $this->_helper->layout->setLayout('menu_admin');
        $this->_helper->viewRenderer->setNoRender();

        $this->view->assign('action', 'add');

        $this->render('gestion-textes-edit');
    }

    public function gestionTextesEditAction(): void
    {
        $this->_helper->layout->setLayout('menu_admin');

        $this->view->assign('action', 'edit');

        $service_prescTextes = new Service_Prescriptions();
        $texteInfo = $service_prescTextes->getTexte($this->_getParam('id'));
        $this->view->assign('texteInfo', $texteInfo);
    }

    public function gestionTextesReplaceAction(): void
    {
        $this->_helper->layout->setLayout('menu_admin');

        $this->view->assign('action', 'replace');

        $service_prescTextes = new Service_Prescriptions();
        $texteInfo = $service_prescTextes->getTexte($this->_getParam('id'));
        $this->view->assign('texteInfo', $texteInfo);

        $liste_textes = $service_prescTextes->getTextesListe();
        $this->view->assign('liste_textes', $liste_textes);
    }

    // GESTION DES ARTICLES
    public function gestionArticlesAction(): void
    {
        $this->_helper->layout->setLayout('menu_admin');
        // On affiche tous les textes accessible dans les prescriptions
        $service_prescription = new Service_Prescriptions();

        if ($this->_request->isPost()) {
            try {
                $post = $this->_request->getPost();
                if ('add' == $post['action']) {
                    $service_prescription->saveArticle($post);
                    $this->_helper->flashMessenger(['context' => 'success', 'title' => 'Enregistrement effectué.', 'message' => 'L\'article a bien été enregistré']);
                } elseif ('edit' == $post['action']) {
                    $service_prescription->saveArticle($post, $post['id_article']);
                    $this->_helper->flashMessenger(['context' => 'success', 'title' => 'Modification effectuée.', 'message' => 'L\'article a bien été enregistré']);
                } elseif ('replace' == $post['action']) {
                    $service_prescription->replaceArticle($post['id_article'], $post['idArticleReplace']);
                    $this->_helper->flashMessenger(['context' => 'success', 'title' => 'Suppression effectuée.', 'message' => 'L\'article a bien été supprimé']);
                }
            } catch (Exception $e) {
            }
        }

        $liste_articles = $service_prescription->getArticlesListe();

        $this->view->assign('liste_articles', $liste_articles);
    }

    public function gestionArticlesAddAction(): void
    {
        $this->_helper->layout->setLayout('menu_admin');
        $this->_helper->viewRenderer->setNoRender();

        $this->view->assign('action', 'add');

        $this->render('gestion-articles-edit');
    }

    public function gestionArticlesEditAction(): void
    {
        $this->_helper->layout->setLayout('menu_admin');

        $this->view->assign('action', 'edit');

        $service_prescription = new Service_Prescriptions();
        $articleInfo = $service_prescription->getArticle($this->_getParam('id'));
        $this->view->assign('articleInfo', $articleInfo);
    }

    public function gestionArticlesReplaceAction(): void
    {
        $this->_helper->layout->setLayout('menu_admin');

        $this->view->assign('action', 'replace');

        $service_prescription = new Service_Prescriptions();
        $articleInfo = $service_prescription->getArticle($this->_getParam('id'));
        $this->view->assign('articleInfo', $articleInfo);

        $liste_articles = $service_prescription->getArticlesListe();
        $this->view->assign('liste_articles', $liste_articles);
    }

    // GESTION DES RAPPELS REGLEMENTAIRES
    public function gestionRappelRegAction(): void
    {
        $service_prescription = new Service_Prescriptions();

        if ($this->_request->isPost()) {
            $post = $this->_request->getPost();

            if ('add' == $post['action']) {
                $service_prescription->savePrescription($post);
                $this->_helper->flashMessenger(['context' => 'success', 'title' => 'Enregistrement effectué.', 'message' => 'Le rappel réglementaire a bien été enregistré']);
            } elseif ('edit' == $post['action']) {
                $service_prescription->savePrescription($post, $post['idPrescription']);
                $this->_helper->flashMessenger(['context' => 'success', 'title' => 'Rappel réglementaire modifié.', 'message' => 'Le rappel réglementaire a bien été modifié']);
            }
        }

        $this->view->assign('listePrescEtude', $service_prescription->getPrescriptions('etude'));
        $this->view->assign('listePrescVisite', $service_prescription->getPrescriptions('visite'));
    }

    public function gestionRappelRegAddAction(): void
    {
        $this->_forward('prescription-form');

        // On envoi à la vue l'ensemble des textes et articles
        $dbTexte = new Model_DbTable_PrescriptionTexteListe();
        $this->view->assign('listeTextes', $dbTexte->getAllTextes(1));
        $dbArticle = new Model_DbTable_PrescriptionArticleListe();
        $this->view->assign('listeArticles', $dbArticle->getAllArticles(1));

        $this->view->assign('action', 'add');
        $this->view->assign('typeAction', 'rappel-reg');
    }

    public function gestionRappelRegEditAction(): void
    {
        $this->_forward('prescription-form');

        $dbTexte = new Model_DbTable_PrescriptionTexteListe();
        $dbArticle = new Model_DbTable_PrescriptionArticleListe();

        $this->view->assign('listeTextes', $dbTexte->getAllTextes());
        $this->view->assign('listeArticles', $dbArticle->getAllArticles());

        $idPrescription = $this->_getParam('id');
        $typeAction = 'rappel-reg';

        $service_prescription = new Service_Prescriptions();
        $prescriptionInfo = $service_prescription->getPrescriptionInfo($this->_getParam('id'), $typeAction);

        $this->view->assign('infosPrescription', $prescriptionInfo);
        $this->view->assign('idPrescription', $idPrescription);
        $this->view->assign('action', 'edit');
        $this->view->assign('typeAction', $typeAction);
        $this->view->assign('libelle', $prescriptionInfo[0]['PRESCRIPTIONREGL_LIBELLE']);
    }

    // FORMULAIRE DE PRESCRIPTIONS
    public function prescriptionFormAction(): void
    {
        $this->_helper->layout->setLayout('menu_admin');
    }

    public function moveAction(): void
    {
        // action permettant la sauvegarde de l'ordre des prescriptions type
        $this->_helper->viewRenderer->setNoRender();
        if ($this->_request->isPost()) {
            $service_prescription = new Service_Prescriptions();

            try {
                $post = $this->_request->getPost();
                if (isset($post['prescType'])) {
                    $service_prescription->setOrder($post['prescType'], $post['type']);
                } elseif (isset($post['categorie'])) {
                    $service_prescription->setOrder($post['categorie'], $post['type']);
                } elseif (isset($post['texte'])) {
                    $service_prescription->setOrder($post['texte'], $post['type']);
                } elseif (isset($post['article'])) {
                    $service_prescription->setOrder($post['article'], $post['type']);
                }
            } catch (Exception $e) {
            }
        }
    }
}
