<?php

class FormulaireController extends Zend_Controller_Action
{
    /**
     * @var mixed|Model_DbTable_Champ
     */
    public $modelChamp;

    /**
     * @var mixed|Model_DbTable_ChampValeurListe
     */
    public $modelChampValeurListe;

    /**
     * @var mixed|Model_DbTable_ListeTypeChampRubrique
     */
    public $modelListeTypeChampRubrique;

    /**
     * @var mixed|Model_DbTable_Rubrique
     */
    public $modelRubrique;

    /**
     * @var mixed|Model_DbTable_CapsuleRubrique
     */
    public $modelCapsuleRubrique;

    /**
     * @var mixed|Service_Formulaire
     */
    public $serviceFormulaire;

    /**
     * @var Service_Utils
     */
    public $serviceUtils;

    /**
     * @var mixed|Service_Champ
     */
    public $serviceChamp;

    public function init(): void
    {
        $this->modelChamp = new Model_DbTable_Champ();
        $this->modelChampValeurListe = new Model_DbTable_ChampValeurListe();
        $this->modelListeTypeChampRubrique = new Model_DbTable_ListeTypeChampRubrique();
        $this->modelRubrique = new Model_DbTable_Rubrique();
        $this->modelCapsuleRubrique = new Model_DbTable_CapsuleRubrique();

        $this->serviceFormulaire = new Service_Formulaire();
        $this->serviceUtils = new Service_Utils();
        $this->serviceChamp = new Service_Champ();
    }

    public function indexAction(): void
    {
        // Définition des layouts et scripts
        $this->_helper->layout->setLayout('menu_admin');

        $viewInlineScript = $this->view;
        if (!getenv('PREVARISC_BOOTSTRAP_3')) {
            $viewInlineScript->inlineScript()->appendFile('/js/formulaire/capsule-rubrique.js', 'text/javascript');
        } else {
            $viewInlineScript->inlineScript()->appendFile('/js/formulaire/capsule-rubrique-bs3.js', 'text/javascript');
        }

        $viewInlineScript->inlineScript()->appendFile('/js/formulaire/ordonnancement/ordonnancement.js', 'text/javascript');
        $viewInlineScript->inlineScript()->appendFile('/js/formulaire/ordonnancement/Sortable.min.js', 'text/javascript');

        $viewHeadLink = $this->view;
        if (!getenv('PREVARISC_BOOTSTRAP_3')) {
            $viewHeadLink->headLink()->appendStylesheet('/css/formulaire/edit-table.css', 'all');
            $viewHeadLink->headLink()->appendStylesheet('/css/formulaire/formulaire.css', 'all');
        } else {
            $viewHeadLink->headLink()->appendStylesheet('/css/formulaire-bs3/edit-table.css', 'all');
            $viewHeadLink->headLink()->appendStylesheet('/css/formulaire-bs3/formulaire.css', 'all');
        }

        $form = new Form_CustomForm();

        $capsulesRubriques = $this->serviceFormulaire->getAllCapsuleRubrique();

        // Récupération des rubriques pour chaque objet global
        foreach ($capsulesRubriques as $key => $capsuleRubrique) {
            $capsulesRubriques[$key]['RUBRIQUES'] = $this->modelRubrique->getRubriquesByCapsuleRubrique($capsuleRubrique['NOM_INTERNE']);
        }

        // Assignation des variables à la vue
        $this->view->assign('form', $form);
        $this->view->assign('formulaires', $capsulesRubriques);
    }

    public function addRubriqueAction(): void
    {
        $this->_helper->viewRenderer->setNoRender(true);

        $request = $this->getRequest();
        if ($request->isPost()) {
            $post = $request->getPost();

            $post['idx'] = (int) $this->modelRubrique->getNbRubriqueOfDesc($post['capsule_rubrique']);
            $idRubrique = $this->serviceFormulaire->insertRubrique($post);
            $insertedRowAsArray = $this->modelRubrique->find($idRubrique)->current()->toArray();

            echo json_encode($insertedRowAsArray);
        }
    }

    public function editRubriqueAction(): void
    {
        $this->_helper->layout->setLayout('menu_admin');

        $viewInlineScript = $this->view;
        if (!getenv('PREVARISC_BOOTSTRAP_3')) {
            $viewInlineScript->inlineScript()->appendFile('/js/formulaire/rubrique.js', 'text/javascript');
        } else {
            $viewInlineScript->inlineScript()->appendFile('/js/formulaire/rubrique-bs3.js', 'text/javascript');
        }

        $viewInlineScript->inlineScript()->appendFile('/js/formulaire/ordonnancement/ordonnancement.js', 'text/javascript');
        $viewInlineScript->inlineScript()->appendFile('/js/formulaire/ordonnancement/Sortable.min.js', 'text/javascript');

        $viewHeadLink = $this->view;
        if (!getenv('PREVARISC_BOOTSTRAP_3')) {
            $viewHeadLink->headLink()->appendStylesheet('/css/formulaire/edit-table.css', 'all');
            $viewHeadLink->headLink()->appendStylesheet('/css/formulaire/formulaire.css', 'all');
        } else {
            $viewHeadLink->headLink()->appendStylesheet('/css/formulaire-bs3/edit-table.css', 'all');
            $viewHeadLink->headLink()->appendStylesheet('/css/formulaire-bs3/formulaire.css', 'all');
        }

        $fieldForm = new Form_CustomFormField();

        $idRubrique = (int) $this->getParam('rubrique');
        $rubrique = $this->modelRubrique->find($idRubrique)->current();

        $champs = $this->modelChamp->getChampsByRubrique($rubrique['ID_RUBRIQUE']);
        foreach ($champs as $key => $champ) {
            if ('Liste' === $champ['TYPE']) {
                $champs[$key]['VALEURS'] = $this->modelChampValeurListe->getValeurListeByChamp($champ['ID_CHAMP']);
            }

            if ('Parent' === $champ['TYPE']) {
                $champs[$key]['LIST_CHAMP'] = $this->modelChamp->getChampsFromParent($champ['ID_CHAMP']);
            }
        }

        $this->view->assign('fieldForm', $fieldForm);
        $this->view->assign('rubrique', $rubrique);
        $this->view->assign('champs', $champs);

        $request = $this->getRequest();
        if ($request->isPost()) {
            try {
                $post = $request->getPost();

                $rubrique->NOM = $post['nom_rubrique'];
                $rubrique->DEFAULT_DISPLAY = $post['afficher_rubrique'];
                $rubrique->save();

                $this->_helper->redirector('index');
            } catch (Exception $e) {
                $this->_helper->flashMessenger(['context' => getenv('PREVARISC_BOOTSTRAP_3') ? 'danger' : 'error', 'title' => 'Erreur lors de la sauvegarde', 'message' => 'La rubrique n\'a pas été modifiée. Veuillez rééssayez. ('.$e->getMessage().')']);
            }
        }
    }

    public function editCapsuleRubriqueNomAction(): void
    {
        $request = $this->getRequest();

        if ($request->isPost()) {
            try {
                $post = $request->getPost();
                $idCapsuleRubrique = (int) $post['id'];
                $newName = $post['newName'];
                $this->modelCapsuleRubrique->updateCapsuleRubriqueName(
                    $idCapsuleRubrique,
                    $newName
                );

                $this->_helper->json(['status' => 'success']);
            } catch (Exception $e) {
                $this->_helper->json(['status' => 'error', 'message' => $e->getMessage()]);
            }
        }
    }

    public function deleteRubriqueAction(): void
    {
        $idRubrique = (int) $this->getParam('rubrique');

        $rubrique = $this->modelRubrique->find($idRubrique)->current();
        $rubrique->delete();

        $this->_helper->redirector('index');
    }

    public function addChampAction(): void
    {
        $viewRenderer = $this->_helper->viewRenderer;
        $viewRenderer->setNoRender(true);

        $idRubrique = (int) $this->getParam('rubrique');
        $rubrique = $this->modelRubrique->find($idRubrique)->current()->toArray();

        $request = $this->getRequest();

        if ($request->isPost()) {
            $post = $request->getPost();
            $post['idx'] = $request->getParam('ID_CHAMP_PARENT') ? $this->modelChamp->getNbChampOfParent($request->getParam('ID_CHAMP_PARENT')) : $this->modelChamp->getNbChampOfRubrique($idRubrique);

            $idListe = $this->modelListeTypeChampRubrique->getIdTypeChampByName('Liste')['ID_TYPECHAMP'];

            $isParent = filter_var($request->getParam('isParent', false), FILTER_VALIDATE_BOOLEAN);
            $champ = $this->serviceFormulaire->insertChamp($post, $rubrique, $isParent);

            $idChamp = (int) $champ['ID_CHAMP'];
            $idTypeChamp = (int) $champ['ID_TYPECHAMP'];

            $insertedRowAsArray = $this->modelChamp->getChampAndJoins($idChamp, $idTypeChamp === $idListe);

            echo json_encode($insertedRowAsArray);
        }
    }

    public function editChampAction(): void
    {
        $this->_helper->layout->setLayout('menu_admin');

        $viewInlineScript = $this->view;
        if (!getenv('PREVARISC_BOOTSTRAP_3')) {
            $viewInlineScript->inlineScript()->appendFile('/js/formulaire/rubrique.js', 'text/javascript');
        } else {
            $viewInlineScript->inlineScript()->appendFile('/js/formulaire/rubrique-bs3.js', 'text/javascript');
        }

        $viewInlineScript->inlineScript()->appendFile('/js/formulaire/champ.js', 'text/javascript');
        $viewInlineScript->inlineScript()->appendFile('/js/formulaire/ordonnancement/ordonnancement.js', 'text/javascript');
        $viewInlineScript->inlineScript()->appendFile('/js/formulaire/ordonnancement/Sortable.min.js', 'text/javascript');

        $viewHeadLink = $this->view;
        if (!getenv('PREVARISC_BOOTSTRAP_3')) {
            $viewHeadLink->headLink()->appendStylesheet('/css/formulaire/edit-table.css', 'all');
            $viewHeadLink->headLink()->appendStylesheet('/css/formulaire/formulaire.css', 'all');
        } else {
            $viewHeadLink->headLink()->appendStylesheet('/css/formulaire-bs3/edit-table.css', 'all');
            $viewHeadLink->headLink()->appendStylesheet('/css/formulaire-bs3/formulaire.css', 'all');
        }

        $idChamp = (int) $this->getParam('champ');
        $champ = $this->modelChamp->find($idChamp)->current();
        $champType = $this->modelChamp->getTypeChamp($idChamp);

        // Cas d'une liste
        $idListe = $this->modelListeTypeChampRubrique->getIdTypeChampByName('Liste')['ID_TYPECHAMP'];

        if ($champ['ID_TYPECHAMP'] === $idListe) {
            $valeursChamp = $this->modelChampValeurListe->getValeurListeByChamp($champ['ID_CHAMP']);
            $this->view->assign('valeursChamp', $valeursChamp);
        }

        $rubrique = $this->modelRubrique->find($champ['ID_RUBRIQUE'])->current();
        $capsuleRubrique = $this->modelCapsuleRubrique->find($rubrique['ID_CAPSULERUBRIQUE'])->current();
        $listeTypeChampRubrique = $this->serviceFormulaire->getAllListeTypeChampRubrique();

        $backUrlOptions = [
            'controller' => 'formulaire',
            'action' => 'edit-rubrique',
            'rubrique' => $champ['ID_RUBRIQUE'],
        ];
        $champFusionValue = null;

        if ('Parent' === $champType['TYPE']) {
            $listChamps = $this->modelChamp->getChampsFromParent($idChamp);

            foreach ($listChamps as $key => $listChamp) {
                if ('Liste' === $listChamp['TYPE']) {
                    $listChamps[$key]['VALEURS'] = $this->modelChampValeurListe->getValeurListeByChamp($listChamp['ID_CHAMP']);
                }
            }

            if ($this->serviceChamp->isTableau($champ->toArray())) {
                $fieldNames = [];
                $fieldValues = [];

                $loopName = $this->serviceUtils->getFusionNameMagicalCase(
                    implode(
                        ' ',
                        [
                            $capsuleRubrique['NOM_INTERNE'],
                            $rubrique['NOM'],
                            $champ['NOM'],
                            'valeurs',
                        ]
                    )
                );

                $idx = 0;
                foreach ($listChamps as $listChamp) {
                    $fieldNames[] = $this->serviceUtils->getFullFusionName(
                        $capsuleRubrique['NOM_INTERNE'],
                        [
                            $rubrique['NOM'],
                            $champ['NOM'],
                            $listChamp['idx'],
                        ]
                    );

                    $fieldValues[] = sprintf('valeur%d', $idx);
                    ++$idx;
                }

                $champFusionValue = [
                    'fieldNames' => $fieldNames,
                    'loopName' => $loopName,
                    'fieldValues' => $fieldValues,
                ];
            }

            $this->view->assign('listChamp', $listChamps);
            $this->view->assign('listType', $this->modelListeTypeChampRubrique->getTypeWithoutParent());
            $this->view->assign(
                'formChamp',
                new Form_FormChampFromParent(
                    [
                        'champParentID' => $this->getRequest()->getParam('champ'),
                        'rubriqueID' => $this->getRequest()->getParam('rubrique'),
                    ]
                )
            );
        } elseif (null === $champ['ID_PARENT']) {
            $champFusionValue = $this->serviceUtils->getFullFusionName(
                $capsuleRubrique['NOM_INTERNE'],
                [
                    $rubrique['NOM'],
                    $champ['NOM'],
                ]
            );
        } else {
            $infosParent = $this->modelChamp->getInfosParent($champ['ID_CHAMP']);
            $this->view->assign('infosParent', $infosParent);

            if (
                !$this->serviceChamp->isTableau(
                    $this->modelChamp->find($infosParent['ID_CHAMP'])->current()->toArray()
                )
            ) {
                $champFusionValue = $this->serviceUtils->getFullFusionName(
                    $capsuleRubrique['NOM_INTERNE'],
                    [
                        $rubrique['NOM'],
                        $infosParent['NOM'],
                        $champ['NOM'],
                    ]
                );
            }

            $backUrlOptions['action'] = 'edit-champ';
            $backUrlOptions['champ'] = $champ['ID_PARENT'];
        }

        $this->view->assign('champ', $champ);
        $this->view->assign('champFusionValue', $champFusionValue);
        $this->view->assign('rubrique', $rubrique);
        $this->view->assign('listeTypeChampRubrique', $listeTypeChampRubrique);
        $this->view->assign('type', $champType['TYPE']);
        $this->view->assign('backUrl', $this->view->url($backUrlOptions, null, true));

        $request = $this->getRequest();

        if ($request->isPost()) {
            $post = $request->getPost();

            // Modification de valeur
            // On récupère les valeurs de la liste séparément des autres champs
            $listFieldValueArray = array_filter($post, function ($key): bool {
                return 0 === strpos($key, 'valeur-champ-');
            }, ARRAY_FILTER_USE_KEY);

            foreach ($listFieldValueArray as $key => $value) {
                $explodedKey = explode('-', $key);
                $idValue = (int) end($explodedKey);

                $valueField = $this->modelChampValeurListe->find($idValue)->current();
                $valueField->VALEUR = $value;
                $valueField->save();
            }

            // Ajout de valeur
            // On récupère les valeurs de la liste séparément des autres champs
            $listValueArray = array_filter($post, function ($key): bool {
                return 0 === strpos($key, 'valeur-ajout-');
            }, ARRAY_FILTER_USE_KEY);

            foreach ($listValueArray as $listValue) {
                $this->modelChampValeurListe->insert([
                    'VALEUR' => $listValue,
                    'ID_CHAMP' => $champ['ID_CHAMP'],
                ]);
            }

            $champ->NOM = $post['nom_champ'];
            $champ->tableau = (int) filter_var($post['is-tableau'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            $champ->save();
            $this->_helper->redirector('edit-rubrique', null, null, ['rubrique' => $rubrique['ID_RUBRIQUE']]);
        }
    }

    public function deleteChampAction(): void
    {
        $idChamp = (int) $this->getParam('champ');
        $champ = $this->modelChamp->find($idChamp)->current();
        $idRubrique = $champ['ID_RUBRIQUE'];
        $champ->delete();
        if ($this->getParam('ID_PARENT')) {
            $this->_helper->redirector('edit-champ', null, null, ['rubrique' => $idRubrique, 'champ' => $this->getParam('ID_PARENT')]);
        } else {
            $this->_helper->redirector('edit-rubrique', null, null, ['rubrique' => $idRubrique]);
        }
    }

    public function deleteValeurListeAction(): void
    {
        $this->_helper->viewRenderer->setNoRender(true);

        $idValeurListe = $this->getParam('liste');

        $valeurListe = $this->modelChampValeurListe->find($idValeurListe)->current();
        $valeurListe->delete();
    }

    public function updateChampIdxAction(): void
    {
        $this->_helper->viewRenderer->setNoRender(true);

        $request = $this->getRequest();
        if ($request->isPost()) {
            $post = $request->getPost();
            $this->modelChamp->updateNewIdx($post);
        }
    }

    public function updateRubriqueIdxAction(): void
    {
        $this->_helper->viewRenderer->setNoRender(true);

        $request = $this->getRequest();
        if ($request->isPost()) {
            $post = $request->getPost();
            $this->modelRubrique->updateNewIdx($post);
        }
    }
}
