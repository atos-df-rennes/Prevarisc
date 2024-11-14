<?php

class StatistiquesController extends Zend_Controller_Action
{
    // Liste des differentes extractions et stats avec le lien vers la page correspondant
    private $liste = [
        'ccdsa-liste-erp-en-exploitation-connus-soumis-a-controle' => 'Extraction 1 : CCDSA Liste ERP en exploitation connus soumis à contrôle',
        'liste-des-erp-sous-avis-defavorable' => 'Extraction 2 : Liste des ERP sous avis défavorable',
        'prochaines-visites-de-controle-periodique-a-faire-sur-une-commune' => 'Extraction 3 : Prochaines visites de contrôle périodique à faire sur une commune',
        'liste-erp-avec-visite-periodique-sur-un-an' => 'Extraction 4 : Liste ERP avec des visites périodiques sur 1 an',
    ];

    public function init(): void
    {
        ob_end_clean();
        $this->_helper->layout->setLayout('dashboard');
        // On prépare me XML pour l'extraction et la génération
        foreach (array_keys($this->liste) as $key) {
            $this->_helper->contextSwitch()->addActionContext($key, ['json', 'xml']);
        }

        $this->_helper->contextSwitch()->initContext();
    }

    // Accueil
    public function indexAction(): void
    {
        $this->view->assign('title', 'Statistiques');
        $this->view->assign('liste', $this->liste);
    }

    public function extractionProcess($champs_supplementaires, $noms_des_colonnes_a_afficher, Model_DbTable_Statistiques $requete): void
    {
        // Si on interroge l'action en json, on demande les champs supplémentaires
        if ('json' == $this->_getParam('format')) {
            $this->view->assign('result', $champs_supplementaires);
        } else {
            $this->view->assign('columns', $noms_des_colonnes_a_afficher);
            $this->view->assign('results', $requete->go());
            $this->view->assign('titre', [
                'normalize' => $this->_request->getActionName(),
                'full' => $this->liste[$this->_request->getActionName()],
            ]);
        }
    }

    // Extraction 1 : CCDSA Liste ERP en exploitation connus soumis à contrôle
    public function ccdsaListeErpEnExploitationConnusSoumisAControleAction(): void
    {
        $model_stat = new Model_DbTable_Statistiques();

        if ('json' != $this->_getParam('format')) {
            $date = new Zend_Date($this->_getParam('date'), Zend_Date::DATES);
            $this->view->assign('resume', 'Liste ERP en exploitation connus soumis à contrôle à la date du '.$date->get(Zend_Date::WEEKDAY.' '.Zend_Date::DAY_SHORT.' '.Zend_Date::MONTH_NAME_SHORT.' '.Zend_Date::YEAR));
        }

        $this->extractionProcess(
            [
                'date' => [
                    'label' => 'Soumis à un contrôle périodique obligatoire à la date du', 'type' => 'date', 'data' => date('d/m/Y', time()),
                ],
            ],
            [
                'Libellé' => 'LIBELLE_ETABLISSEMENTINFORMATIONS',
                'Commune' => 'LIBELLE_COMMUNE',
                'Arrondissement' => 'ARRONDISSEMENT',
                'Type' => 'LIBELLE_TYPE',
                'Catégorie' => 'LIBELLE_CATEGORIE',
                'Date dernière visite de contrôle' => 'DATEVISITE_DOSSIER',
                // "Date prochaine visite de contrôle" => "DATEVISITE_DOSSIER",
                'Commission' => 'LIBELLE_COMMISSION',
            ],
            $model_stat->listeDesERP($this->_getParam('date'))->enExploitation()->sousmisAControle()
        );
        if ('json' != $this->_getParam('format')) {
            $this->render('extraction');
        }
    }

    // Extraction 2 : Liste des ERP sous avis défavorable
    public function listeDesErpSousAvisDefavorableAction(): void
    {
        $model_stat = new Model_DbTable_Statistiques();

        if ('json' != $this->_getParam('format')) {
            $date = new Zend_Date($this->_getParam('date'), Zend_Date::DATES);
            $this->view->assign('resume', 'Liste ERP en exploitation sous avis défavorable à la date du '.$date->get(Zend_Date::WEEKDAY.' '.Zend_Date::DAY_SHORT.' '.Zend_Date::MONTH_NAME_SHORT.' '.Zend_Date::YEAR));
        }

        $this->extractionProcess(
            [
                'tri' => [
                    'label' => 'Tri sur une colonne',
                    'type' => 'select',
                    'data' => [
                        'Arrondissement' => 'ARRONDISSEMENT',
                        'Commission' => 'ID_COMMISSION',
                        'Commune' => 'NUMINSEE_COMMUNE',
                        'Type' => 'ID_TYPE',
                        'Catégorie' => 'ID_CATEGORIE',
                        'Date de la dernière visite de contrôle' => 'DATEVISITE_DOSSIER',
                        'Nombre de jours écoulés sous avis défavorable par rapport à la date renseignée' => 'NBJOURS_DEFAVORABLE',
                    ],
                ],
                'date' => [
                    'label' => 'Liste des ERP sous avis défavorable à la date',
                    'type' => 'date',
                    'data' => date('d/m/Y', time()),
                ],
            ],
            [
                'Libellé' => 'LIBELLE_ETABLISSEMENTINFORMATIONS',
                'Commune' => 'LIBELLE_COMMUNE',
                'Arrondissement' => 'ARRONDISSEMENT',
                'Type' => 'ID_TYPE',
                'Catégorie' => 'LIBELLE_CATEGORIE',
                'Date dernière visite de contrôle' => 'DATEVISITE_DOSSIER',
                'Commission' => 'LIBELLE_COMMISSION',
                'Nombre de jours écoulés sous avis défavorable par rapport à la date renseignée' => 'NBJOURS_DEFAVORABLE',
            ],
            $model_stat->listeDesERP($this->_getParam('date'))->enExploitation()->sousAvisDefavorable()->trierPar($this->_getParam('tri'))
        );
        if ('json' != $this->_getParam('format')) {
            $this->render('extraction');
        }
    }

    // Extraction 3 : Prochaines visites de contrôle périodique à faire sur une commune
    public function prochainesVisitesDeControlePeriodiqueAFaireSurUneCommuneAction(): void
    {
        $model_stat = new Model_DbTable_Statistiques();

        // Récupération des communes
        $model_commune = new Model_DbTable_AdresseCommune();
        $rowset_communes = $model_commune->fetchAll(null, 'LIBELLE_COMMUNE');
        $communes = [];
        foreach ($rowset_communes as $commune) {
            $communes[$commune['LIBELLE_COMMUNE']] = $commune['NUMINSEE_COMMUNE'];
        }

        if ('json' != $this->_getParam('format')) {
            $date = new Zend_Date($this->_getParam('date'), Zend_Date::DATES);
            $this->view->assign('resume', 'Prochaines visites de contrôle périodique à faire sur '.array_search($this->_getParam('commune'), $communes, true).' à la date du '.$date->get(Zend_Date::WEEKDAY.' '.Zend_Date::DAY_SHORT.' '.Zend_Date::MONTH_NAME_SHORT.' '.Zend_Date::YEAR));
        }

        $this->extractionProcess(
            [
                'date' => [
                    'label' => 'Date',
                    'type' => 'date',
                    'data' => date('d/m/Y', time()),
                ],
                'commune' => [
                    'label' => 'Commune',
                    'type' => 'select',
                    'data' => $communes,
                ],
                'tri' => [
                    'label' => 'Tri sur une colonne',
                    'type' => 'select',
                    'data' => [
                        'Type' => 'ID_TYPE',
                        'Catégorie' => 'ID_CATEGORIE',
                        'Date de la dernière visite de contrôle' => 'DATEVISITE_DOSSIER',
                        'Avis de la dernière visite' => 'LIBELLE_AVIS',
                    ],
                ],
            ],
            [
                'Libellé' => 'LIBELLE_ETABLISSEMENTINFORMATIONS',
                'Commune' => 'LIBELLE_COMMUNE',
                'Type' => 'ID_TYPE',
                'Catégorie' => 'LIBELLE_CATEGORIE',
                'Date dernière visite de contrôle' => 'DATEVISITE_DOSSIER',
                'Avis de la dernière visite' => 'LIBELLE_AVIS',
                'Commission' => 'LIBELLE_COMMISSION',
            ],
            $model_stat->listeDesERP($this->_getParam('date'))->enExploitation()->sousmisAControle()->surLaCommune($this->_getParam('commune'))->trierPar($this->_getParam('tri'))
        );
        if ('json' != $this->_getParam('format')) {
            $this->render('extraction');
        }
    }

    // Extraction 4 : Liste ERP liées au commission de visite periodique dans une année
    public function listeErpAvecVisitePeriodiqueSurUnAnAction(): void
    {
        $model_stat = new Model_DbTable_Statistiques();
        $dateDebut = date('01/01/'.date('Y'), time());
        $dateFin = date('31/12/'.date('Y'), time());

        if ('json' != $this->_getParam('format')) {
            $date = new Zend_Date($this->_getParam('date'), Zend_Date::DATES);
            $this->view->assign('resume', 'Liste ERP avec des visites periodiques à partir du '.$date->get(Zend_Date::WEEKDAY.' '.Zend_Date::DAY_SHORT.' '.Zend_Date::MONTH_NAME_SHORT.' '.Zend_Date::YEAR));
        }

        $this->extractionProcess(
            [
                'date' => [
                    'label' => 'visite périodique du', 'type' => 'date', 'data' => $dateDebut,
                ],
                'datefin' => [
                    'label' => 'au', 'type' => 'date', 'data' => $dateFin,
                ],
                'tri' => [
                    'label' => 'Tri sur une colonne',
                    'type' => 'select',
                    'data' => [
                        'Commune' => 'LIBELLE_COMMUNE',
                        'Commission' => 'ID_COMMISSION',
                        'Etablissement' => 'LIBELLE_ETABLISSEMENTINFORMATIONS',
                        'Type' => 'ID_TYPE',
                        'Catégorie' => 'ID_CATEGORIE',
                        'Date de prochaine visite' => 'DATEVISITE_DOSSIER',
                    ],
                ],
                'iderp' => [
                    'label' => '', 'type' => 'id', 'data' => 'ID_ETABLISSEMENT',
                ],
            ],
            [
                'Commune' => 'LIBELLE_COMMUNE',
                'Etablissement' => 'LIBELLE_ETABLISSEMENTINFORMATIONS',
                'Prevenstionniste' => 'NOM_UTILISATEURINFORMATIONS',
                'Type' => 'LIBELLE_TYPE',
                'Catégorie' => 'LIBELLE_CATEGORIE',
                'Date de prochaine visite' => 'DATEVISITE_DOSSIER',
                'Date limite de prochaine visite' => 'PERIODICITE_ETABLISSEMENTINFORMATIONS',
                'Commission' => 'LIBELLE_COMMISSION',
            ],
            $model_stat->listeDesERPVisitePeriodique($this->_getParam('date'), $this->_getParam('datefin'))->trierPar($this->_getParam('tri'))
        );

        if ('json' != $this->_getParam('format')) {
            $results = $this->view->results;
            foreach ($results as $key => $row) {
                if (null == $row['DATEVISITE_DOSSIER']) {
                    $results[$key]['DATEVISITE_DOSSIER'] = "<a href='/dossier/add/id_etablissement/".$row['ID_ETABLISSEMENT']."'>Programmer une visite</a>";
                }

                if (null == $row['NOM_UTILISATEURINFORMATIONS']) {
                    $results[$key]['NOM_UTILISATEURINFORMATIONS'] = "<a href='/etablissement/edit/id/".$row['ID_ETABLISSEMENT']."'>Ajouter un préventionniste</a>";
                }

                if (0 != $row['PERIODICITE_ETABLISSEMENTINFORMATIONS']) {
                    $results[$key]['PERIODICITE_ETABLISSEMENTINFORMATIONS'] = '';
                    if (null != $row['DATEVISITE_DOSSIER']) {
                        $date = $row['DATEVISITE_DOSSIER'];
                        $d = new DateTime($date);
                        $i = new DateInterval('P'.$row['PERIODICITE_ETABLISSEMENTINFORMATIONS'].'M');
                        $d->add($i);
                        $results[$key]['PERIODICITE_ETABLISSEMENTINFORMATIONS'] = $d->format('Y-m-d');
                    }
                } else {
                    $results[$key]['PERIODICITE_ETABLISSEMENTINFORMATIONS'] = "<a href='/etablissement/edit/id/".$row['ID_ETABLISSEMENT']."'>Modifier la periodicité</a>";
                }
            }

            $this->view->assign('results', $results);
            $this->render('extraction');
        }
    }
}
