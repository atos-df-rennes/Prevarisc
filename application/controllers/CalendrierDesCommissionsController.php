<?php

class CalendrierDesCommissionsController extends Zend_Controller_Action
{
    /**
     * @var Service_DossierVerificationsTechniques
     */
    public $serviceDescriptifDossier;

    /**
     * @var Service_EtablissementDescriptif
     */
    public $serviceDescriptifEtablissement;

    /**
     * @var Service_DossierEffectifsDegagements
     */
    public $serviceDossierEffectifsDegagements;

    /**
     * @var Service_EtablissementEffectifsDegagements
     */
    public $serviceEtablissementEffectifsDegagements;

    /**
     * @var Service_Formulaire
     */
    public $serviceFormulaire;

    public function init(): void
    {
        $this->_helper->layout->setLayout('dashboard');

        /** @var Zend_Controller_Action_Helper_ContextSwitch $ajaxContext */
        $ajaxContext = $this->_helper->getHelper('AjaxContext');
        $ajaxContext->addActionContext('commissionselection', 'json')
            ->addActionContext('recupevenement', 'json')
            ->addActionContext('recupevenementodj', 'json')
            ->addActionContext('affectedossodj', 'json')
            ->addActionContext('recupdateliee', 'json')
            ->initContext()
        ;

        $this->serviceDescriptifDossier = new Service_DossierVerificationsTechniques();
        $this->serviceDescriptifEtablissement = new Service_EtablissementDescriptif();
        $this->serviceDossierEffectifsDegagements = new Service_DossierEffectifsDegagements();
        $this->serviceEtablissementEffectifsDegagements = new Service_EtablissementEffectifsDegagements();
        $this->serviceFormulaire = new Service_Formulaire();
    }

    public function indexAction(): void
    {
        // Titre de la page
        $this->view->assign('title', 'Calendrier des commissions');

        if ($this->_getParam('idComm')) {
            $this->view->assign('idComm', $this->_getParam('idComm'));
        }

        // Modèle de données
        $model_typesDesCommissions = new Model_DbTable_CommissionType();
        $model_commission = new Model_DbTable_Commission();

        // On cherche tous les types de commissions
        $rowset_typesDesCommissions = $model_typesDesCommissions->fetchAll();

        // Tableau de résultats
        $array_commissions = [];

        // Pour tous les types, on cherche leur commission
        foreach ($rowset_typesDesCommissions as $row_typeDeCommission) {
            $array_results = $model_commission->fetchAll('ID_COMMISSIONTYPE = '.$row_typeDeCommission->ID_COMMISSIONTYPE)->toArray();
            $array_results2 = [];
            foreach ($array_results as $item) {
                $array_results2[] = [
                    'ID_COMMISSION' => $item['ID_COMMISSION'],
                    'LIBELLE_COMMISSION' => $item['LIBELLE_COMMISSION'],
                    'DOCUMENT_CR' => $item['DOCUMENT_CR'],
                    'ID_COMMISSIONTYPE' => $item['ID_COMMISSIONTYPE'],
                    'LIBELLE_COMMISSIONTYPE' => $row_typeDeCommission->LIBELLE_COMMISSIONTYPE,
                ];
            }

            $array_commissions[$row_typeDeCommission->ID_COMMISSIONTYPE] = [
                'LIBELLE' => $row_typeDeCommission->LIBELLE_COMMISSIONTYPE,
                'ARRAY' => $array_results2,
            ];
        }

        $userId = Zend_Auth::getInstance()->getIdentity()['ID_UTILISATEUR'];

        $url = sprintf(
            '/api/1.0/calendar?userid=%s&key=%s',
            $userId,
            getenv('PREVARISC_SECURITY_KEY')
        );

        if ($this->_getParam('idComm')) {
            $url .= sprintf('&commission=%s', $this->_getParam('idComm'));
        }

        $protocol = (!empty($_SERVER['HTTPS']) && 'off' !== $_SERVER['HTTPS']) ? 'webcals' : 'webcal';

        $this->view->assign('url_webcal', $protocol.'://'.$_SERVER['HTTP_HOST'].$url);
        $this->view->assign('array_commissions', $array_commissions);

        $cache = Zend_Controller_Front::getInstance()->getParam('bootstrap')->getResource('cache');

        $this->view->assign('is_admin', unserialize($cache->load('acl'))->isAllowed(Zend_Auth::getInstance()->getIdentity()['group']['LIBELLE_GROUPE'], 'gestion_parametrages', 'gestion_commissions'));
        $this->view->assign('is_view_all', unserialize($cache->load('acl'))->isAllowed(
            Zend_Auth::getInstance()->getIdentity()['group']['LIBELLE_GROUPE'],
            'commission',
            'calendar_view_all'
        ));
    }

    public function recupdatelieeAction(): void
    {
        $this->_helper->viewRenderer->setNoRender();
        $dbDateComm = new Model_DbTable_DateCommission();
        $infosDateComm = $dbDateComm->find($this->_getParam('idDate'))->current();

        // Une fois les infos de la date récupérées on peux aller chercher les date liées à cette commission pour les afficher
        if ('' === $infosDateComm['DATECOMMISSION_LIEES'] || null === $infosDateComm['DATECOMMISSION_LIEES']) {
            $commPrincipale = $this->_getParam('idDate');
        } else {
            $commPrincipale = $infosDateComm['DATECOMMISSION_LIEES'];
        }

        $recupCommLiees = $dbDateComm->getCommissionsDateLieesMaster($commPrincipale);
        echo Zend_Json::encode($recupCommLiees);
    }

    // Gestion de l'affectation des dossier et de l'ordre du jour ODJ
    public function gestionodjAction(): void
    {
        // récuperation des informations concernant la date de commission concernant l'ordre du jour
        $dbDateComm = new Model_DbTable_DateCommission();
        $infosDateComm = $dbDateComm->find($this->_getParam('dateCommId'))->current();

        // Une fois les infos de la date récupérées on peux aller chercher les date liées à cette commission pour les afficher
        if ('' === $infosDateComm['DATECOMMISSION_LIEES'] || null === $infosDateComm['DATECOMMISSION_LIEES']) {
            $commPrincipale = $this->_getParam('dateCommId');
        } else {
            $commPrincipale = $infosDateComm['DATECOMMISSION_LIEES'];
        }

        $this->view->assign('recupCommLiees', $dbDateComm->getCommissionsDateLieesMaster($commPrincipale));

        // récupération des informations sur la commission
        $dbCommission = new Model_DbTable_Commission();
        $infosCommission = $dbCommission->find($infosDateComm['COMMISSION_CONCERNE'])->current();

        $dbCommissionType = new Model_DbTable_CommissionType();
        $infosCommissionType = $dbCommissionType->find($infosCommission['ID_COMMISSIONTYPE'])->current();

        // récuperation de tout les dossiers affectés à cette date de commission
        $dbDossierAffectation = new Model_DbTable_DossierAffectation();

        // Si on prend en compte les heures on récupère uniquement les dossiers n'ayant pas d'heure de passage
        $listeDossiersNonAffect = $dbDossierAffectation->getDossierNonAffect($this->_getParam('dateCommId'));
        $listeDossiersAffect = $dbDossierAffectation->getDossierAffect($this->_getParam('dateCommId'));
        $listeDesDossiers = array_merge($listeDossiersAffect, $listeDossiersNonAffect);

        $dbDossier = new Model_DbTable_Dossier();
        $dbDocUrba = new Model_DbTable_DossierDocUrba();
        $service_etablissement = new Service_Etablissement();
        $DB_prev = new Model_DbTable_DossierPreventionniste();

        foreach ($listeDossiersNonAffect as $val => $ue) {
            // On recupere la liste des établissements qui concernent le dossier
            $listeEtab = $dbDossier->getEtablissementDossierGenConvoc($ue['ID_DOSSIER']);
            // on recupere la liste des infos des établissement

            if ([] !== $listeEtab) {
                $etablissementInfos = $service_etablissement->get($listeEtab[0]['ID_ETABLISSEMENT']);
                $listeDossiersNonAffect[$val]['infosEtab'] = $etablissementInfos;
                $listeDocUrba = $dbDocUrba->getDossierDocUrba($ue['ID_DOSSIER']);
                $listeDossiersNonAffect[$val]['listeDocUrba'] = $listeDocUrba;

                $listeDossiersNonAffect[$val]['preventionnistes'] = $DB_prev->getPrevDossier($ue['ID_DOSSIER']);
            }
        }

        // Gestion de l'affichage de la date de la commission
        $date = new Zend_Date($infosDateComm['DATE_COMMISSION'], 'yyyy-MM-dd');
        $this->view->assign('dateFr', $date->get(Zend_Date::WEEKDAY.' '.Zend_Date::DAY_SHORT.' '.Zend_Date::MONTH_NAME_SHORT.' '.Zend_Date::YEAR, 'fr'));

        $this->view->assign('infosDateComm', $infosDateComm);
        $this->view->assign('infosCommission', $infosCommission);
        $this->view->assign('infosCommissionType', $infosCommissionType);

        $this->view->assign('listeDossierNonAffect', $listeDossiersNonAffect);
        $this->view->assign('nbDossiersAffect', count($listeDossiersAffect));
        $this->view->assign('nbDossiers', count($listeDesDossiers));
    }

    public function resizeodjAction(): void
    {
        try {
            $this->_helper->viewRenderer->setNoRender();
            $heureFin = new Zend_Date($this->_getParam('dateFin'), Zend_Date::ISO_8601, 'en');

            $dbDossierAffectation = new Model_DbTable_DossierAffectation();
            $dossierAffectationUpdate = $dbDossierAffectation->find($this->_getParam('dateCommId'), $this->_getParam('idDossier'))->current();

            $dossierAffectationUpdate->HEURE_FIN_AFFECT = $heureFin->get('HH:mm');
            $dossierAffectationUpdate->save();
            $this->_helper->flashMessenger([
                'context' => 'success',
                'title' => 'L\'événement a bien été modifié',
                'message' => '',
            ]);
        } catch (Exception $exception) {
            $this->_helper->flashMessenger([
                'context' => 'error',
                'title' => 'Erreur lors de la modification de l\'événement',
                'message' => $exception->getMessage(),
            ]);
        }
    }

    public function dropodjAction(): void
    {
        try {
            $this->_helper->viewRenderer->setNoRender();

            $heureDeb = new Zend_Date($this->_getParam('heureDebut'), Zend_Date::ISO_8601, 'en');
            $heureFin = new Zend_Date($this->_getParam('heureFin'), Zend_Date::ISO_8601, 'en');

            $dbDossierAffectation = new Model_DbTable_DossierAffectation();
            $dossierAffectationUpdate = $dbDossierAffectation->find($this->_getParam('dateCommId'), $this->_getParam('idDossier'))->current();

            $dossierAffectationUpdate->HEURE_DEB_AFFECT = $heureDeb->get('HH:mm');
            $dossierAffectationUpdate->HEURE_FIN_AFFECT = $heureFin->get('HH:mm');

            $dossierAffectationUpdate->save();
        } catch (Exception $exception) {
            $this->_helper->flashMessenger([
                'context' => 'error',
                'title' => 'Erreur inattendue',
                'message' => $exception->getMessage(),
            ]);
        }
    }

    public function commissionselectionAction(): void
    {
        // Utilisée pour l'auto complétion
        if (isset($_GET['q'])) {
            $commissions = new Model_DbTable_Commission();
            $this->view->assign('commissionsListe', $commissions->fetchAll("LIBELLE_COMMISSION LIKE '%".$_GET['q']."%'")->toArray());
        }
    }

    public function recupevenementAction(): void
    {
        $this->_helper->viewRenderer->setNoRender();
        // Permet la récupération des différents éléments du calendrier pour la commission concernée
        $dateDebut = new Zend_Date(substr($this->getRequest()->getParam('start'), 0, -3), Zend_Date::TIMESTAMP);
        $dateDebut = $dateDebut->get(Zend_Date::YEAR.'-'.Zend_Date::MONTH.'-'.Zend_Date::DAY);

        $dateFin = new Zend_Date(substr($this->getRequest()->getParam('end'), 0, -3), Zend_Date::TIMESTAMP);
        $dateFin = $dateFin->get(Zend_Date::YEAR.'-'.Zend_Date::MONTH.'-'.Zend_Date::DAY);

        $dbDateCommission = new Model_DbTable_DateCommission();

        $items = [];
        $requete = ($this->_getParam('idComm'))
            ? "COMMISSION_CONCERNE = '".$this->_getParam('idComm')."' AND DATE_COMMISSION BETWEEN '".$dateDebut."' AND '".$dateFin."'"
            : "DATE_COMMISSION BETWEEN '".$dateDebut."' AND '".$dateFin."'";

        if ($this->_getParam('type')) {
            $requete .= " AND ID_COMMISSIONTYPEEVENEMENT = '".$this->_getParam('type')."'";
        }

        foreach ($dbDateCommission->fetchAll($requete)->toArray() as $commissionEvent) {
            $items[] = [
                'id' => $commissionEvent['ID_DATECOMMISSION'],
                'title' => '   '.$commissionEvent['LIBELLE_DATECOMMISSION'],
                'start' => date($commissionEvent['DATE_COMMISSION'].' '.$commissionEvent['HEUREDEB_COMMISSION']),
                'end' => date($commissionEvent['DATE_COMMISSION'].' '.$commissionEvent['HEUREFIN_COMMISSION']),
                'url' => 'commission/id/'.$commissionEvent['ID_DATECOMMISSION'],
                'className' => 'display-'.$commissionEvent['ID_COMMISSIONTYPEEVENEMENT'],
                'allDay' => false,
            ];
        }

        $this->view->assign('items', $items);
    }

    public function recupevenementodjAction(): void
    {
        $this->_helper->viewRenderer->setNoRender();

        // Permet la récupération des différents éléments du calendrier pour la commission concernée
        $dateDebut = new Zend_Date(substr($this->getRequest()->getParam('start'), 0, -3), Zend_Date::TIMESTAMP);
        $dateDebut = $dateDebut->get(Zend_Date::YEAR.'-'.Zend_Date::MONTH.'-'.Zend_Date::DAY);

        $dateFin = new Zend_Date(substr($this->getRequest()->getParam('end'), 0, -3), Zend_Date::TIMESTAMP);
        $dateFin = $dateFin->get(Zend_Date::YEAR.'-'.Zend_Date::MONTH.'-'.Zend_Date::DAY);

        $dbDossierAffect = new Model_DbTable_DossierAffectation();
        $listeDossiersAffect = $dbDossierAffect->getDossierAffect($this->_getParam('dateCommId'));

        $items = [];

        $dbDossier = new Model_DbTable_Dossier();
        $dbDocUrba = new Model_DbTable_DossierDocUrba();
        $service_etablissement = new Service_Etablissement();

        foreach ($listeDossiersAffect as $val => $ue) {
            // On recupere la liste des établissements qui concernent le dossier
            $listeEtab = $dbDossier->getEtablissementDossierGenConvoc($ue['ID_DOSSIER']);
            // on recupere la liste des infos des établissement
            if ([] !== $listeEtab) {
                $etablissementInfos = $service_etablissement->get($listeEtab[0]['ID_ETABLISSEMENT']);
                $listeDossiersAffect[$val]['infosEtab'] = $etablissementInfos;
                $listeDocUrba = $dbDocUrba->getDossierDocUrba($ue['ID_DOSSIER']);
                $listeDossiersAffect[$val]['listeDocUrba'] = $listeDocUrba;
            } else {
                unset($listeDossiersAffect[$val]);
            }
        }

        $color = null;
        foreach ($listeDossiersAffect as $dossierAffect) {
            $affichage = '';
            if (isset($dossierAffect['infosEtab']['parents'][0]['LIBELLE_ETABLISSEMENTINFORMATIONS'])) {
                $affichage .= $dossierAffect['infosEtab']['parents'][0]['LIBELLE_ETABLISSEMENTINFORMATIONS'].' - ';
            }

            $affichage = $dossierAffect['infosEtab']['informations']['LIBELLE_ETABLISSEMENTINFORMATIONS'];

            $nbAdresse = count($dossierAffect['infosEtab']['adresses']);
            if (0 != $nbAdresse) {
                $affichage .= ' (';
                foreach ($dossierAffect['infosEtab']['adresses'] as $commune) {
                    $affichage .= $commune['LIBELLE_COMMUNE'];
                    if (1 != $nbAdresse) {
                        $affichage .= ', ';
                    }

                    --$nbAdresse;
                }

                $affichage .= ') ';
            } else {
                $affichage .= ' ( adresse non renseignée )';
            }

            if ('' != $dossierAffect['LIBELLE_DOSSIERNATURE']) {
                $affichage .= ' - '.$dossierAffect['LIBELLE_DOSSIERNATURE'];
            }

            if ('' != $dossierAffect['OBJET_DOSSIER']) {
                $affichage .= ' - Objet : '.$dossierAffect['OBJET_DOSSIER'];
            }

            if (isset($dossierAffect['listeDocUrba']) && count($dossierAffect['listeDocUrba']) > 0) {
                $affichage .= ' - Doc urbanisme : ';
                foreach ($dossierAffect['listeDocUrba'] as $ue) {
                    $affichage .= $ue['NUM_DOCURBA'].' . ';
                }
            }

            $DB_prev = new Model_DbTable_DossierPreventionniste();
            $preventionnistes = $DB_prev->getPrevDossier($dossierAffect['ID_DOSSIER']);
            if ([] !== $preventionnistes) {
                $affichage .= ' ('.$preventionnistes[0]['NOM_UTILISATEURINFORMATIONS'].' '.$preventionnistes[0]['PRENOM_UTILISATEURINFORMATIONS'].')';
            }

            if (0 == $dossierAffect['VERROU_DOSSIER']) {
                $color = '#e2a420';
            } elseif (1 == $dossierAffect['VERROU_DOSSIER']) {
                $color = '#83bff6';
            }

            $items[] = [
                'id' => $dossierAffect['ID_DOSSIER'],
                'url' => '/dossier/index/id/'.$dossierAffect['ID_DOSSIER'],
                'title' => '   '.$affichage,
                'start' => date($dateDebut.' '.$dossierAffect['HEURE_DEB_AFFECT']),
                'end' => date($dateFin.' '.$dossierAffect['HEURE_FIN_AFFECT']),
                'backgroundColor' => $color,
                'allDay' => false,
            ];
        }

        $this->view->assign('items', $items);
    }

    public function affectedossodjAction(): void
    {
        try {
            $this->_helper->viewRenderer->setNoRender();

            $dbDossierAffect = new Model_DbTable_DossierAffectation();
            $dossAffect = $dbDossierAffect->find($this->_getParam('dateCommId'), $this->_getParam('idDossier'))->current();

            $dateAttribDoss = new Zend_Date($this->_getParam('datadebut'), Zend_Date::ISO_8601, 'en');
            $dossAffect->HEURE_DEB_AFFECT = $dateAttribDoss->get('HH:mm');

            $dateAttribDoss->add('5', Zend_Date::MINUTE);
            $dossAffect->HEURE_FIN_AFFECT = $dateAttribDoss->get('HH:mm');
            $dossAffect->save();

            $DBdossier = new Model_DbTable_Dossier();
            $dossier = $DBdossier->find($this->_getParam('idDossier'))->current();

            $listeDossiersAffect = $dbDossierAffect->getDossierAffect($this->_getParam('dateCommId'));

            echo Zend_Json::encode([
                'verrou' => $dossier['VERROU_DOSSIER'],
                'countAffect' => count($listeDossiersAffect),
            ]);
        } catch (Exception $exception) {
            $this->_helper->flashMessenger([
                'context' => 'error',
                'title' => "Erreur lors de l'affectation du dossier",
                'message' => $exception->getMessage(),
            ]);
        }
    }

    public function dialogcommAction(): void
    {
        $this->view->assign('do', $this->_getParam('do'));
        if ('edit' == $this->view->do) {
            // récupération de l'id de la date sur laquelle on clique
            $this->view->assign('dateClick', $this->_getParam('idDateComm'));

            $dbDateCommission = new Model_DbTable_DateCommission();
            $commQtipInfo = $dbDateCommission->find($this->_getParam('idDateComm'))->current();

            // Permet d'afficher les types d'evenements présents dans la BD (visite etc...)
            $dbCommTypeEvenement = new Model_DbTable_CommissionTypeEvenement();
            $libelleCom = $dbCommTypeEvenement->find($commQtipInfo['ID_COMMISSIONTYPEEVENEMENT'])->current();
            $this->view->assign('typeComLibelle', $libelleCom->LIBELLE_COMMISSIONTYPEEVENEMENT);

            $this->view->assign('idParam', $this->_getParam('idComm'));
            $this->view->assign('idTypeSelect', $commQtipInfo->ID_COMMISSIONTYPEEVENEMENT);

            if (null == $commQtipInfo->DATECOMMISSION_LIEES) {
                // On est sur la date principale
                $this->view->assign('listeDates', $dbDateCommission->getCommissionsQtypListing($this->_getParam('idDateComm')));
                $this->view->assign('dateCommission', $commQtipInfo->ID_DATECOMMISSION);
            } else {
                // On est sur une date liée
                $this->view->assign('listeDates', $dbDateCommission->getCommissionsQtypListing($commQtipInfo->DATECOMMISSION_LIEES));
                $this->view->assign('dateCommission', $dbDateCommission->find($commQtipInfo->DATECOMMISSION_LIEES)->current()->ID_DATECOMMISSION);
            }

            // Récupération du libelle de la Commission selectionnee
            $this->view->assign('libelleDateComm', $commQtipInfo->LIBELLE_DATECOMMISSION);
        } elseif ('newComm' == $this->view->do) {
            $dbCommissions = new Model_DbTable_Commission();
            $commFind = $dbCommissions->find($this->_getParam('idComm'));
            $commSelect = $commFind->current();
            $this->view->assign('libelleCom', $commSelect->LIBELLE_COMMISSION);

            // Permet d'afficher les types d'evenements présents dans la BD (visite etc...)
            $dbTypeEvenement = new Model_DbTable_CommissionTypeEvenement();
            $this->view->assign('listeCommType', $dbTypeEvenement->getCommListe());

            // Récupération de la date de début puis création des variables envoyées à la vue
            $dateD = new Zend_Date($this->_getParam('dateD'), Zend_Date::DATES, 'en');
            $this->view->assign('dateCommD', $dateD->get(Zend_Date::WEEKDAY.' '.Zend_Date::DAY_SHORT.' '.Zend_Date::MONTH_NAME_SHORT.' '.Zend_Date::YEAR, 'fr'));
            $this->view->assign('dateSelectD', $dateD->get(Zend_Date::YEAR.'/'.Zend_Date::MONTH.'/'.Zend_Date::DAY));

            // Récupération de la date de fin puis création des variables envoyées à la vue
            $dateF = new Zend_Date($this->_getParam('dateF'), Zend_Date::DATES, 'en');
            $this->view->assign('dateCommF', $dateF->get(Zend_Date::WEEKDAY.' '.Zend_Date::DAY_SHORT.' '.Zend_Date::MONTH_NAME_SHORT.' '.Zend_Date::YEAR, 'fr'));
            $this->view->assign('dateSelectF', $dateF->get(Zend_Date::YEAR.'/'.Zend_Date::MONTH.'/'.Zend_Date::DAY));

            // Récupération des heures de début et de fin. si 00:00 toutes les 2 il s'agit de journées entières
            $HeureD = new Zend_Date($this->_getParam('dateD'), Zend_Date::ISO_8601, 'en');
            $HeureF = new Zend_Date($this->_getParam('dateF'), Zend_Date::ISO_8601, 'en');

            // Liste des dates selectionnées dans un tableau puis envoyées à la vue
            $listeDates = [];
            while ($dateD->compare($dateF) <= 0) {
                $listeDates[] = [
                    'date' => $dateD->get(Zend_Date::WEEKDAY.' '.Zend_Date::DAY_SHORT.' '.Zend_Date::MONTH_NAME_SHORT.' '.Zend_Date::YEAR, 'fr'),
                    'inputH' => $dateD->get(Zend_Date::YEAR.'-'.Zend_Date::MONTH.'-'.Zend_Date::DAY),
                    'heureD' => $HeureD->get('HH:mm'),
                    'heureF' => $HeureF->get('HH:mm'),
                ];
                $dateD->addDay(1);
            }

            // Envoi à la vue la liste des dates selectionnées
            $this->view->assign('listeDates', $listeDates);

            $this->view->assign('libelleCom', '');
            if ($this->_getParam('libelleCom')) {
                $this->view->assign('libelleCom', $this->_getParam('libelleCom'));
            }

            $this->view->assign('typeDoss', '');
            if ($this->_getParam('type')) {
                $this->view->assign('typeDoss', $this->_getParam('type'));
            }
        } else {
            switch ($this->view->do) {
                case 'addDateN':
                    // affiche une ligne suplémentaire dans le tableau de résumé des dates
                    $dateAjoutee = new Zend_Date($this->_getParam('date'), Zend_Date::DATES, 'fr');
                    $this->view->assign('dateAjoutee', $dateAjoutee->get(Zend_Date::WEEKDAY.' '.Zend_Date::DAY_SHORT.' '.Zend_Date::MONTH_NAME_SHORT.' '.Zend_Date::YEAR, 'fr'));
                    $this->view->assign('dateAjouteeInput', $dateAjoutee->get(Zend_Date::YEAR.'-'.Zend_Date::MONTH.'-'.Zend_Date::DAY));

                    break;

                    // LIBELLE
                case 'libelleCom':
                case 'annule_libelleCom':
                    // EDITION Permet de charger le formulaire de modification pour le libellé
                    $dbDateCommission = new Model_DbTable_DateCommission();
                    $commQtipInfo = $dbDateCommission->find($this->_getParam('idDateComm'))->current();
                    $this->view->assign('libelleDateComm', $commQtipInfo->LIBELLE_DATECOMMISSION);

                    break;

                case 'valid_libelleCom':
                    // VALIDATION Lorsque l'on modifie le libellé de la commission programmée
                    $dbDateCommission = new Model_DbTable_DateCommission();
                    $commEdit = $dbDateCommission->find($this->_getParam('idDateComm'))->current();
                    if (null == $commEdit->DATECOMMISSION_LIEES) {
                        // On est sur la date maitre
                        $dbDateCommission->dateCommUpdateLibelle($this->_getParam('idDateComm'), addslashes($this->_getParam('data')));
                    } else {
                        // Cas d'une comm liée
                        $dbDateCommission->dateCommUpdateLibelle($commEdit->DATECOMMISSION_LIEES, addslashes($this->_getParam('data')));
                    }

                    $this->view->assign('libelleDateComm', $this->_getParam('data'));

                    break;

                    // TYPE
                case 'typeCom':
                    // EDITION Permet de charger le type de commission
                    $dbTypeEvenement = new Model_DbTable_CommissionTypeEvenement();
                    $this->view->assign('listeCommType', $dbTypeEvenement->getCommListe());

                    $dbDateCommission = new Model_DbTable_DateCommission();
                    $commEdit = $dbDateCommission->find($this->_getParam('idDateComm'))->current();
                    $this->view->assign('typeComSelect', $commEdit['ID_COMMISSIONTYPEEVENEMENT']);

                    break;

                case 'valid_typeCom':
                    // VALIDATION Permet de valider le changement de type des commissions concernées
                    $idTypeSelect = $this->_getParam('typeSelect');
                    $dbDateCommission = new Model_DbTable_DateCommission();
                    $LigneComm = $dbDateCommission->find($this->_getParam('idDateComm'))->current();
                    $idUtile = null != $LigneComm->DATECOMMISSION_LIEES ? $LigneComm->DATECOMMISSION_LIEES : $LigneComm->ID_DATECOMMISSION;
                    $dbDateCommission->dateCommUpdateType($idUtile, $idTypeSelect);
                    $dbTypeEvenement = new Model_DbTable_CommissionTypeEvenement();
                    $infoType = $dbTypeEvenement->find($idTypeSelect)->current();
                    $this->view->assign('libelleType', $infoType['LIBELLE_COMMISSIONTYPEEVENEMENT']);

                    break;

                case 'annule_typeCom':
                    // ANNULATION Permet de ne rien modifier concernant le type ré-affiche le type non modifié
                    $idDateComm = $this->_getParam('idDateComm');
                    $dbDateCommission = new Model_DbTable_DateCommission();
                    $commEdit = $dbDateCommission->find($idDateComm)->current();
                    $typeComSelect = $commEdit['ID_COMMISSIONTYPEEVENEMENT'];

                    $dbTypeEvenement = new Model_DbTable_CommissionTypeEvenement();
                    $infoType = $dbTypeEvenement->find($typeComSelect)->current();
                    $this->view->assign('libelleType', $infoType['LIBELLE_COMMISSIONTYPEEVENEMENT']);

                    break;

                    // DATE
                case 'dateComm':
                case 'annule_dateCom':
                    // EDITION Permet de charger le formulaire de modification pour une date
                    $dbDateCommission = new Model_DbTable_DateCommission();
                    $tabInfos = $dbDateCommission->find($this->_getParam('idDateComm'))->current();
                    $this->view->assign('dateCommDetail', $tabInfos);
                    $this->view->assign('first', $this->_getParam('first'));

                    break;

                case 'valid_dateCom':
                    // VALIDATION Lorsque l'on modifie la date
                    $HeureD = new Zend_Date($this->_getParam('hd'), 'HH:mm', 'en');
                    $HeureF = new Zend_Date($this->_getParam('hf'), 'HH:mm', 'en');
                    $date = new Zend_Date($this->_getParam('date'), Zend_Date::DATES, 'fr');

                    $dbDateCommission = new Model_DbTable_DateCommission();
                    $updateDateComm = $dbDateCommission->find($this->_getParam('idDateComm'))->current();
                    $updateDateComm->HEUREDEB_COMMISSION = $HeureD->get('HH:mm');
                    $updateDateComm->HEUREFIN_COMMISSION = $HeureF->get('HH:mm');
                    $updateDateComm->DATE_COMMISSION = $date->get(Zend_Date::YEAR.'-'.Zend_Date::MONTH.'-'.Zend_Date::DAY);
                    $updateDateComm->save();

                    $dbDateCommission->updateDependingDossierDates($updateDateComm);

                    $this->view->assign('updateDateComm', $updateDateComm);
                    $this->view->assign('first', $this->_getParam('first'));

                    break;

                case 'supp_dateCom':
                    // ANNULATION Lorsque l'on annule la modification une date
                    $dbDateCommission = new Model_DbTable_DateCommission();
                    $dateCommSupp = $dbDateCommission->find($this->_getParam('idDateComm'))->current();
                    if (null != $dateCommSupp->DATECOMMISSION_LIEES) {
                        $dateCommSupp->delete();
                    } else {
                        $this->_helper->viewRenderer->setNoRender();
                    }

                    break;

                case 'addDateS':
                    $date = new Zend_Date($this->_getParam('date'), Zend_Date::DATES, 'fr');
                    // verifier si la date liéee contien une date liee. Si oui on récup cette id et on insere si non on prend l'id et on insere
                    $dbDateCommission = new Model_DbTable_DateCommission();
                    $LigneComm = $dbDateCommission->find($this->_getParam('idDateCommLiee'))->current();
                    $idUtile = null != $LigneComm->DATECOMMISSION_LIEES ? $LigneComm->DATECOMMISSION_LIEES : $LigneComm->ID_DATECOMMISSION;
                    $LigneComm = $dbDateCommission->find($idUtile)->current();
                    $newDate = $dbDateCommission->createRow();
                    $newDate->DATE_COMMISSION = $date->get(Zend_Date::YEAR.'-'.Zend_Date::MONTH.'-'.Zend_Date::DAY);
                    $newDate->DATECOMMISSION_LIEES = $idUtile;
                    $newDate->ID_COMMISSIONTYPEEVENEMENT = $LigneComm->ID_COMMISSIONTYPEEVENEMENT;
                    $newDate->COMMISSION_CONCERNE = $LigneComm->COMMISSION_CONCERNE;
                    $newDate->LIBELLE_DATECOMMISSION = $LigneComm->LIBELLE_DATECOMMISSION;
                    $newDate->save();
                    $this->view->assign('TabNouvelleDate', $newDate);
                    $this->view->assign('nouvelleDate', $date->get(Zend_Date::WEEKDAY.' '.Zend_Date::DAY_SHORT.' '.Zend_Date::MONTH_NAME_SHORT.' '.Zend_Date::YEAR, 'fr'));

                    break;

                case 'makeDefaut':
                    // Cas utilisé pour rendre une date de commission comme celle par défaut
                    $idDateCom = $this->_getParam('idDateComm');
                    // on récupere l'enregistrement de la date que l'on va faire devenir par défaut
                    $dbDateCommission = new Model_DbTable_DateCommission();
                    $newMasterComm = $dbDateCommission->find($idDateCom)->current();

                    // fonction fait le changement des autres dates
                    $dbDateCommission->changeMasterDateComm($newMasterComm['DATECOMMISSION_LIEES'], $newMasterComm['ID_DATECOMMISSION']);

                    $newMasterComm->DATECOMMISSION_LIEES = null;
                    $newMasterComm->save();

                    break;

                default:
                    // cas théoriquement jamais utilisé
                    echo 'Erreur mauvais choix';

                    break;
            }
        }
    }

    public function adddatecommAction(): void
    {
        // affiche une ligne suplémentaire dans le tableau de résumé des dates
        $dateAjoutee = new Zend_Date($this->_getParam('date'), Zend_Date::DATES, 'fr');
        $this->view->assign('dateAjoutee', $dateAjoutee->get(Zend_Date::WEEKDAY.' '.Zend_Date::DAY_SHORT.' '.Zend_Date::MONTH_NAME_SHORT.' '.Zend_Date::YEAR, 'fr'));
        $this->view->assign('dateAjouteeInput', $dateAjoutee->get(Zend_Date::YEAR.'-'.Zend_Date::MONTH.'-'.Zend_Date::DAY));
    }

    public function adddatedialogcommAction(): void
    {
        echo 'date ajoutée';
    }

    public function adddatesAction(): void
    {
        try {
            $this->_helper->viewRenderer->setNoRender();

            $libelle = $this->_getParam('libelle_comm');
            $typeComm = $this->_getParam('typeCom');

            $dbDateCommission = new Model_DbTable_DateCommission();

            $premiereDate = true;
            $listeDates = [];

            if ('on' == $this->_getParam('repeat')) {
                // Cas d'une seule date avec une périodicité selectionnée
                $periodicite = $this->_getParam('periodicite');

                $dateFin = new Zend_Date($this->_getParam('dateFin'), 'dd.MM.yyyy');

                foreach (array_keys($_POST) as $var) {
                    $varExplode1 = explode('_', $var);
                    $expectedNumberOfParameters = 2;

                    if ($expectedNumberOfParameters == count($varExplode1)) {
                        // il n'y à que la premiere date sélectionnée (de début) qui est composée d'un "_"
                        $varExplode2 = explode('-', $varExplode1[1]);
                        $expectedNumberOfDateParameters = 3;

                        if (
                            $expectedNumberOfDateParameters == count($varExplode2)
                            && 'D' === $varExplode1[0]
                        ) {
                            // on s'assure que c'est bien une date jj/mm/aaaa
                            // Ici insertion la premiere dates dans la base de données
                            $idOrigine = $dbDateCommission->addDateComm($varExplode1[1], $this->_getParam('D_'.$varExplode1[1]), $this->_getParam('F_'.$varExplode1[1]), $this->_getParam('idComm'), $this->_getParam('typeCom'), $libelle);
                            $dateDebut = new Zend_Date($varExplode1[1], 'yyyy-MM-dd');
                            $idCalendrierTab = $idOrigine;
                            $heureDebRef = $this->_getParam('D_'.$varExplode1[1]);
                            $heureFinRef = $this->_getParam('F_'.$varExplode1[1]);

                            // on prend garde de ne pas inserer le père ajouté ci dessus
                            $first = 1;
                            while ($dateDebut->compare($dateFin) <= 0) {
                                // on liste toutes les dates jusqu'a la date de fin
                                $dateDb = $dateDebut->get(Zend_Date::YEAR.'-'.Zend_Date::MONTH.'-'.Zend_Date::DAY);
                                if (1 != $first) {
                                    $idOrigine = $dbDateCommission->addDateComm($dateDb, $heureDebRef, $heureFinRef, $this->_getParam('idComm'), $this->_getParam('typeCom'), $this->_getParam('libelle_comm'));
                                    $idCalendrierTab = $idOrigine;
                                } else {
                                    $first = 0;
                                }

                                $listeDates[] = [
                                    'id' => $idCalendrierTab,
                                    'title' => $libelle,
                                    'start' => $dateDb.' '.$heureDebRef,
                                    'end' => $dateDb.' '.$heureFinRef,
                                    'url' => 'calendrier-des-commissions/id/'.$idCalendrierTab,
                                    'className' => 'display-'.$typeComm,
                                ];
                                $dateDebut->addDay(7 * $periodicite);
                            }
                        }
                    }
                }
            } else {
                // Cas de plusieurs dates selectionnées
                foreach (array_keys($_POST) as $var) {
                    $varExplode1 = explode('_', $var);
                    $expectedNumberOfParameters = 2;

                    if ($expectedNumberOfParameters == count($varExplode1)) {
                        // on est dans le cas d'une date
                        $varExplode2 = explode('-', $varExplode1[1]);
                        $expectedNumberOfDateParameters = 3;

                        if ($expectedNumberOfDateParameters == count($varExplode2)) {
                            // Ici insertion des dates dans la base de données
                            if ('D' === $varExplode1[0]) {
                                if ($premiereDate) {
                                    $idOrigine = $dbDateCommission->addDateComm($varExplode1[1], $this->_getParam('D_'.$varExplode1[1]), $this->_getParam('F_'.$varExplode1[1]), $this->_getParam('idComm'), $this->_getParam('typeCom'), $this->_getParam('libelle_comm'));
                                    $idCalendrierTab = $idOrigine;
                                    $premiereDate = false;
                                } else {
                                    if (!isset($idOrigine)) {
                                        throw new Exception("L'identifiant de la date de commission d'origine n'existe pas.");
                                    }

                                    $idCalendrierTab = $dbDateCommission->addDateCommLiee($varExplode1[1], $this->_getParam('D_'.$varExplode1[1]), $this->_getParam('F_'.$varExplode1[1]), $idOrigine, $this->_getParam('typeCom'), $this->_getParam('idComm'), $this->_getParam('libelle_comm'));
                                }

                                $listeDates[] = [
                                    'id' => $idCalendrierTab,
                                    'title' => $libelle,
                                    'start' => $varExplode1[1].' '.$this->_getParam('D_'.$varExplode1[1]),
                                    'end' => $varExplode1[1].' '.$this->_getParam('F_'.$varExplode1[1]),
                                    'url' => 'calendrier-des-commissions/id/'.$idCalendrierTab,
                                    'className' => 'display-'.$typeComm,
                                ];
                            } // fin $premiereDate && $varExplode1[0]=='D'
                        } // fin count = 3
                    } // fin count = 2
                } // fin foreach
            }

            $this->_helper->flashMessenger([
                'context' => 'success',
                'title' => 'Les dates ont bien été sauvegardées',
                'message' => '',
            ]);
            echo json_encode($listeDates);
        } catch (Exception $exception) {
            $this->_helper->flashMessenger([
                'context' => 'error',
                'title' => 'Erreur inattendue lors de la sauvegarde des dates',
                'message' => $exception->getMessage(),
            ]);
        }
    }

    public function deplacecommissiondateAction(): void
    {
        try {
            $date = new Zend_Date($_POST['debut'], Zend_Date::DATES, 'en');

            $dbDateCommission = new Model_DbTable_DateCommission();
            $commUpdate = $dbDateCommission->find($_POST['idComm'])->current();
            $commUpdate->DATE_COMMISSION = $date->get(Zend_Date::YEAR.'-'.Zend_Date::MONTH.'-'.Zend_Date::DAY);
            if ($this->_getParam('debut')) {
                $HeureD = new Zend_Date($this->_getParam('debut'), Zend_Date::ISO_8601, 'en');
                $commUpdate->HEUREDEB_COMMISSION = $HeureD->get('HH:mm');
            }

            if ($this->_getParam('fin')) {
                $HeureF = new Zend_Date($this->_getParam('fin'), Zend_Date::ISO_8601, 'en');
                $commUpdate->HEUREFIN_COMMISSION = $HeureF->get('HH:mm');
            }

            $commUpdate->save();

            $dbDateCommission->updateDependingDossierDates($commUpdate);

            $this->_helper->flashMessenger([
                'context' => 'success',
                'title' => 'L\'événement a bien été déplacé',
                'message' => '',
            ]);
        } catch (Exception $exception) {
            $this->_helper->flashMessenger([
                'context' => 'error',
                'title' => 'Erreur lors du déplacement de l\'événement',
                'message' => $exception->getMessage(),
            ]);
        }
    }

    public function resizecommissiondateAction(): void
    {
        try {
            $this->_helper->viewRenderer->setNoRender();
            $heureFin = new Zend_Date($this->_getParam('fin'), Zend_Date::ISO_8601, 'en');

            $dbDateCommission = new Model_DbTable_DateCommission();
            $commResize = $dbDateCommission->find($this->_getParam('idComm'))->current();
            $commResize->HEUREFIN_COMMISSION = $heureFin->get('HH:mm');
            $commResize->save();
            $this->_helper->flashMessenger([
                'context' => 'success',
                'title' => 'L\'événement a bien été modifié',
                'message' => '',
            ]);
        } catch (Exception $exception) {
            $this->_helper->flashMessenger([
                'context' => 'error',
                'title' => 'Erreur lors de la modification de l\'événement',
                'message' => $exception->getMessage(),
            ]);
        }
    }

    public function gestionheuresAction(): void
    {
        try {
            // Permet de prendre en compte ou non les horraires de passage en commission
            $this->_helper->viewRenderer->setNoRender();

            $gestionHeure = null;
            if ('non' == $this->_getParam('gestionHeure')) {
                $gestionHeure = 0;
            } elseif ('oui' == $this->_getParam('gestionHeure')) {
                $gestionHeure = 1;
            }

            // On selectionne la commission concernée et en fonction des paramettres on prend en comptes les heures ou pas
            $dbDateCommission = new Model_DbTable_DateCommission();
            $dateCommConcerne = $dbDateCommission->find($this->_getParam('dateCommId'))->current();
            $dateCommConcerne->GESTION_HEURES = $gestionHeure;
            $dateCommConcerne->save();

            $dbDossierAffectation = new Model_DbTable_DossierAffectation();
            if (0 == $gestionHeure) {
                // Si on ne prend pas en compte les heures, on passe en revue chacuns des dossiers concernés par la commission
                // Récupération de l'ensemble des dossiers
                $listeDossiersConcernes = $dbDossierAffectation->getAllDossierAffect($this->_getParam('dateCommId'));
                // Pour chacun d'entre eux on passe les champs HEURE_DEB_AFFECT et HEURE_FIN_AFFECT à NULL
                // On créee un compteur afin de les classer dans l'ordre souhaité
                $nbDossier = 0;
                foreach ($listeDossiersConcernes as $val) {
                    // si l'heure de début ou de fin sont différent de NULL on les passe à NULL
                    if (null != $val['ID_DOSSIER_AFFECT']) {
                        $dossierEdit = $dbDossierAffectation->find($this->_getParam('dateCommId'), $val['ID_DOSSIER_AFFECT'])->current();
                        $dossierEdit->HEURE_DEB_AFFECT = null;
                        $dossierEdit->HEURE_FIN_AFFECT = null;
                        $dossierEdit->NUM_DOSSIER = $nbDossier;
                        $dossierEdit->save();
                    }

                    ++$nbDossier;
                }
            } else {
                // Récupération de l'ensemble des dossiers
                $listeDossiersConcernes = $dbDossierAffectation->getAllDossierAffect($this->_getParam('dateCommId'));
                // Pour chacun d'entre eux on passe les champs HEURE_DEB_AFFECT et HEURE_FIN_AFFECT à NULL
                // On créee un compteur afin de les classer dans l'ordre souhaité
                foreach ($listeDossiersConcernes as $val) {
                    $dossierEdit = $dbDossierAffectation->find($this->_getParam('dateCommId'), $val['ID_DOSSIER_AFFECT'])->current();
                    $dossierEdit->HEURE_DEB_AFFECT = null;
                    $dossierEdit->HEURE_FIN_AFFECT = null;
                    $dossierEdit->NUM_DOSSIER = '0';
                    $dossierEdit->save();
                }
            }

            $this->_helper->flashMessenger([
                'context' => 'success',
                'title' => 'La modification de l\'événement a bien été enregistrée',
                'message' => '',
            ]);
        } catch (Exception $exception) {
            $this->_helper->flashMessenger([
                'context' => 'error',
                'title' => 'Erreur lors de la modification de l\'événement',
                'message' => $exception->getMessage(),
            ]);
        }
    }

    public function changementordreAction(): void
    {
        try {
            $this->_helper->viewRenderer->setNoRender();
            $stringUpdate = $this->_getParam('ordreDossier');

            $dossierId = explode(',', $stringUpdate);

            $dbDossierAffectation = new Model_DbTable_DossierAffectation();

            $numDossier = 0;
            foreach ($dossierId as $idDossier) {
                $updateOrdreDossier = $dbDossierAffectation->find($this->_getParam('dateCommId'), $idDossier)->current();
                $updateOrdreDossier->HEURE_DEB_AFFECT = null;
                $updateOrdreDossier->HEURE_FIN_AFFECT = null;
                $updateOrdreDossier->NUM_DOSSIER = $numDossier;
                $updateOrdreDossier->save();
                ++$numDossier;
            }
        } catch (Exception $exception) {
            $this->_helper->flashMessenger([
                'context' => 'error',
                'title' => 'Erreur inattendue',
                'message' => $exception->getMessage(),
            ]);
        }
    }

    public function generationconvocAction(): void
    {
        try {
            $dbDateCommPj = new Model_DbTable_DateCommissionPj();
            $model_membres = new Model_DbTable_CommissionMembre();
            $model_commission = new Model_DbTable_Commission();
            $model_adresseCommune = new Model_DbTable_AdresseCommune();
            $model_utilisateurInfo = new Model_DbTable_UtilisateurInformations();
            $dbDossier = new Model_DbTable_Dossier();
            $dbDocUrba = new Model_DbTable_DossierDocUrba();
            $model_etablissement = new Model_DbTable_Etablissement();

            $service_etablissement = new Service_Etablissement();

            $dateCommId = $this->_getParam('dateCommId');
            $this->view->assign('idComm', $dateCommId);

            // on recupere le type de commission (salle / visite / groupe de visite)
            $dbDateComm = new Model_DbTable_DateCommission();
            $commissionInfo = $dbDateComm->find($dateCommId)->current()->toArray();

            // 1 = salle . 2 = visite . 3 = groupe de visite
            $this->view->assign('typeCommission', $commissionInfo['ID_COMMISSIONTYPEEVENEMENT']);

            // On récupère la liste des dossiers
            // Suivant si l'on prend en compte les heures ou non on choisi la requete à effectuer
            if (1 == $commissionInfo['GESTION_HEURES']) {
                // prise en compte heures
                $listeDossiers = $dbDateCommPj->getDossiersInfosByHour($dateCommId);
            } else {
                // prise en compte ordre
                $listeDossiers = $dbDateCommPj->getDossiersInfosByOrder($dateCommId);
            }

            // Récupération des membres de la commission
            $listeMembres = $model_membres->get($commissionInfo['COMMISSION_CONCERNE']);
            foreach ($listeMembres as $var => $membre) {
                $listeMembres[$var]['infosFiles'] = $model_membres->fetchAll('ID_COMMISSIONMEMBRE = '.$membre['id_membre']);
            }

            $this->view->assign('informationsMembre', $listeMembres);

            $this->view->assign('membresFiles', $model_membres->fetchAll('ID_COMMISSION = '.$commissionInfo['COMMISSION_CONCERNE']));

            // On récupère le nom de la commission
            $this->view->assign('commissionInfos', $model_commission->find($commissionInfo['COMMISSION_CONCERNE'])->toArray());

            // FIXME Grouper les foreach en un seul, là c'est débile de faire 3 fois le même
            // afin de récuperer les informations des communes (adresse des mairies etc)
            foreach ($listeDossiers as $val => $ue) {
                $listePrev = $dbDossier->getPreventionnistesDossier($ue['ID_DOSSIER']);

                if ([] !== $listePrev) {
                    $listeDossiers[$val]['preventionnistes'] = $listePrev;
                }

                // On recupere la liste des établissements qui concernent le dossier
                $listeEtab = $dbDossier->getEtablissementDossierGenConvoc($ue['ID_DOSSIER']);
                // on recupere la liste des infos des établissement
                if ([] !== $listeEtab) {
                    $etablissementInfos = $service_etablissement->get($listeEtab[0]['ID_ETABLISSEMENT']);
                    // Ajoute les avis derogations provenant du dossier
                    $listeDossiers[$val]['AVIS_DEROGATIONS'] = $dbDossier->getListAvisDerogationsFromDossier($ue['ID_DOSSIER']);
                    $listeDossiers[$val]['AVIS_DEROGATIONS_ETABLISSEMENT'] = empty($ue['infosEtab']) ? [] : $model_etablissement->getListAvisDerogationsEtablissement($listeEtab[0]['ID_ETABLISSEMENT']);
                    $listeDossiers[$val]['infosEtab'] = $etablissementInfos;
                    $listeDocUrba = $dbDocUrba->getDossierDocUrba($ue['ID_DOSSIER']);
                    $listeDossiers[$val]['listeDocUrba'] = $listeDocUrba;
                } else {
                    unset($listeDossiers[$val]);
                }
            }

            $libelleCommune = '';
            $tabCommune = [];
            $numCommune = 0;
            foreach ($listeDossiers as $ue) {
                if (0 == $numCommune) {
                    if (count($ue['infosEtab']['adresses']) > 0) {
                        $libelleCommune = $ue['infosEtab']['adresses'][0]['LIBELLE_COMMUNE'];
                        $adresseCommune = $model_adresseCommune->find($ue['infosEtab']['adresses'][0]['NUMINSEE_COMMUNE'])->toArray();
                        $communeInfo = $model_utilisateurInfo->find($adresseCommune[0]['ID_UTILISATEURINFORMATIONS'])->toArray();
                        $tabCommune[$numCommune] = [$libelleCommune, $communeInfo];
                    }

                    ++$numCommune;
                }

                $existe = 0;
                foreach ($tabCommune as $value) {
                    if (
                        count($ue['infosEtab']['adresses']) > 0
                        && isset($value[0])
                        && $value[0] == $ue['infosEtab']['adresses'][0]['LIBELLE_COMMUNE']
                    ) {
                        $existe = 1;
                    }
                }

                if (0 == $existe) {
                    if (count($ue['infosEtab']['adresses']) > 0) {
                        $libelleCommune = $ue['infosEtab']['adresses'][0]['LIBELLE_COMMUNE'];
                        $adresseCommune = $model_adresseCommune->find($ue['infosEtab']['adresses'][0]['NUMINSEE_COMMUNE'])->toArray();
                        $communeInfo = $model_utilisateurInfo->find($adresseCommune[0]['ID_UTILISATEURINFORMATIONS'])->toArray();
                        $tabCommune[$numCommune] = [$libelleCommune, $communeInfo];
                    }

                    ++$numCommune;
                }
            }

            foreach ($listeDossiers as $key => $dossier) {
                // Gestion des formulaires personnalisés
                $rubriquesDossier = $this->serviceDescriptifDossier->getRubriques($dossier['ID_DOSSIER'], 'Dossier');
                $rubriquesEtablissement = empty($dossier['infosEtab']) ? '' : $this->serviceDescriptifEtablissement->getRubriques($dossier['infosEtab']['general']['ID_ETABLISSEMENT'], 'Etablissement');
                $rubriquesDossierEffectifsDegagements = $this->serviceDossierEffectifsDegagements->getRubriques($dossier['ID_DOSSIER'], 'Dossier');
                $rubriquesEtablissementEffectifsDegagements = empty($dossier['infosEtab']) ? '' : $this->serviceEtablissementEffectifsDegagements->getRubriques($dossier['infosEtab']['general']['ID_ETABLISSEMENT'], 'Etablissement');

                $rubriquesByCapsuleRubrique = [
                    'descriptifVerificationsTechniques' => $rubriquesDossier,
                    'descriptifEtablissement' => $rubriquesEtablissement,
                    'effectifsDegagementsDossier' => $rubriquesDossierEffectifsDegagements,
                    'effectifsDegagementsEtablissement' => $rubriquesEtablissementEffectifsDegagements,
                ];

                // Gestion des rubriques/champs personnalisés
                $capsulesRubriques = $this->serviceFormulaire->getAllCapsuleRubrique();

                // Récupération des rubriques pour chaque objet global
                foreach ($capsulesRubriques as $index => $capsuleRubrique) {
                    $capsulesRubriques[$index]['RUBRIQUES'] = $rubriquesByCapsuleRubrique[$capsuleRubrique['NOM_INTERNE']];
                }

                $listeDossiers[$key]['FORMULAIRES'] = $capsulesRubriques;
            }

            $this->view->assign('listeCommunes', $tabCommune);
            $this->view->assign('dossierComm', $listeDossiers);

            // récuperation du nom de la commission
            $this->view->assign('nomComm', $listeDossiers[0]['LIBELLE_DATECOMMISSION']);
            $this->view->assign('dateComm', $listeDossiers[0]['DATE_COMMISSION']);
            $this->view->assign('heureDeb', $listeDossiers[0]['HEUREDEB_COMMISSION']);

            $this->_helper->flashMessenger([
                'context' => 'success',
                'title' => 'Le document a bien été généré',
                'message' => '',
            ]);
        } catch (Exception $exception) {
            $this->_helper->flashMessenger([
                'context' => 'error',
                'title' => 'Erreur lors de la génération du document',
                'message' => $exception->getMessage(),
            ]);
        }
    }

    public function generationodjAction(): void
    {
        $tabCommune = [];
        $dateCommId = $this->_getParam('dateCommId');
        $this->view->assign('idComm', $dateCommId);

        // On récupère la liste des dossiers
        // Suivant si l'on prend en compte les heures ou non on choisi la requete à effectuer
        $dbDateComm = new Model_DbTable_DateCommission();
        $commSelect = $dbDateComm->find($dateCommId)->current();
        $dbDateCommPj = new Model_DbTable_DateCommissionPj();

        $listeDossiers = null;
        if (1 == $commSelect['GESTION_HEURES']) {
            // prise en compte heures
            $listeDossiers = $dbDateCommPj->getDossiersInfosByHour($dateCommId);
        } elseif (0 == $commSelect['GESTION_HEURES']) {
            // prise en compte ordre
            $listeDossiers = $dbDateCommPj->getDossiersInfosByOrder($dateCommId);
        }

        // On récupère le nom de la commission
        $model_commission = new Model_DbTable_Commission();
        $this->view->assign('commissionInfos', $model_commission->find($commSelect['COMMISSION_CONCERNE'])->toArray());

        // Récupération des membres de la commission
        $model_membres = new Model_DbTable_CommissionMembre();
        $this->view->assign('membresFiles', $model_membres->fetchAll('ID_COMMISSION = '.$commSelect['COMMISSION_CONCERNE']));

        // afin de récuperer les informations des communes (adresse des mairies etc)
        $model_adresseCommune = new Model_DbTable_AdresseCommune();
        $model_utilisateurInfo = new Model_DbTable_UtilisateurInformations();

        $dbDossier = new Model_DbTable_Dossier();
        $dbDocUrba = new Model_DbTable_DossierDocUrba();
        $service_etablissement = new Service_Etablissement();

        foreach ($listeDossiers as $val => $ue) {
            $listeDossiers[$val]['preventionnistes'] = [];
            $listeDossiers[$val]['listeDocUrba'] = [];
            $listeDossiers[$val]['infosEtab'] = [];

            $listePrev = $dbDossier->getPreventionnistesDossier($ue['ID_DOSSIER']);
            if ([] !== $listePrev) {
                $listeDossiers[$val]['preventionnistes'] = $listePrev;
            }

            // On recupere la liste des établissements qui concernent le dossier
            $listeEtab = $dbDossier->getEtablissementDossierGenConvoc($ue['ID_DOSSIER']);
            // on recupere la liste des infos des établissement
            if ([] !== $listeEtab) {
                $etablissementInfos = $service_etablissement->get($listeEtab[0]['ID_ETABLISSEMENT']);
                $listeDossiers[$val]['infosEtab'] = $etablissementInfos;

                $listeDocUrba = $dbDocUrba->getDossierDocUrba($ue['ID_DOSSIER']);
                $listeDossiers[$val]['listeDocUrba'] = $listeDocUrba;
            }
        }

        $libelleCommune = '';
        $tabCommune[] = [];
        $numCommune = 0;
        foreach ($listeDossiers as $ue) {
            if (0 == $numCommune) {
                if (isset($ue['infosEtab']['adresses'][0])) {
                    $libelleCommune = $ue['infosEtab']['adresses'][0]['LIBELLE_COMMUNE'];
                    $adresseCommune = $model_adresseCommune->find($ue['infosEtab']['adresses'][0]['NUMINSEE_COMMUNE'])->toArray();
                }

                if (isset($adresseCommune[0]['ID_UTILISATEURINFORMATIONS'])) {
                    $communeInfo = $model_utilisateurInfo->find($adresseCommune[0]['ID_UTILISATEURINFORMATIONS'])->toArray();
                }

                if (isset($libelleCommune, $communeInfo)) {
                    $tabCommune[$numCommune] = [$libelleCommune, $communeInfo];
                }

                ++$numCommune;
            }

            $existe = 0;
            foreach ($tabCommune as $value) {
                if (isset($ue['infosEtab']['adresses'][0]['LIBELLE_COMMUNE'])) {
                    if (isset($value[0]) && $value[0] == $ue['infosEtab']['adresses'][0]['LIBELLE_COMMUNE']) {
                        $existe = 1;
                    }
                } else {
                    $existe = 1;
                }
            }

            if (0 == $existe) {
                $libelleCommune = $ue['infosEtab']['adresses'][0]['LIBELLE_COMMUNE'];
                $adresseCommune = $model_adresseCommune->find($ue['infosEtab']['adresses'][0]['NUMINSEE_COMMUNE'])->toArray();
                $communeInfo = $model_utilisateurInfo->find($adresseCommune[0]['ID_UTILISATEURINFORMATIONS'])->toArray();
                $tabCommune[$numCommune] = [$libelleCommune, $communeInfo];
                ++$numCommune;
            }
        }

        $listeMembres = $model_membres->get($commSelect['COMMISSION_CONCERNE']);
        foreach ($listeMembres as $var => $membre) {
            $listeMembres[$var]['infosFiles'] = $model_membres->fetchAll('ID_COMMISSIONMEMBRE = '.$membre['id_membre']);
        }

        $model_etablissement = new Model_DbTable_Etablissement();

        foreach ($listeDossiers as $key => $dossier) {
            $listeDossiers[$key]['AVIS_DEROGATIONS'] = $dbDossier->getListAvisDerogationsFromDossier($dossier['ID_DOSSIER']);
            $listeDossiers[$key]['AVIS_DEROGATIONS_ETABLISSEMENT'] = empty($dossier['infosEtab']) ? [] : $model_etablissement->getListAvisDerogationsEtablissement($dossier['infosEtab']['general']['ID_ETABLISSEMENT']);

            // Gestion des formulaires personnalisés
            $rubriquesDossier = $this->serviceDescriptifDossier->getRubriques($dossier['ID_DOSSIER'], 'Dossier');
            $rubriquesEtablissement = empty($dossier['infosEtab']) ? '' : $this->serviceDescriptifEtablissement->getRubriques($dossier['infosEtab']['general']['ID_ETABLISSEMENT'], 'Etablissement');
            $rubriquesDossierEffectifsDegagements = $this->serviceDossierEffectifsDegagements->getRubriques($dossier['ID_DOSSIER'], 'Dossier');
            $rubriquesEtablissementEffectifsDegagements = empty($dossier['infosEtab']) ? '' : $this->serviceEtablissementEffectifsDegagements->getRubriques($dossier['infosEtab']['general']['ID_ETABLISSEMENT'], 'Etablissement');

            $rubriquesByCapsuleRubrique = [
                'descriptifVerificationsTechniques' => $rubriquesDossier,
                'descriptifEtablissement' => $rubriquesEtablissement,
                'effectifsDegagementsDossier' => $rubriquesDossierEffectifsDegagements,
                'effectifsDegagementsEtablissement' => $rubriquesEtablissementEffectifsDegagements,
            ];

            // Gestion des rubriques/champs personnalisés
            $capsulesRubriques = $this->serviceFormulaire->getAllCapsuleRubrique();

            // Récupération des rubriques pour chaque objet global
            foreach ($capsulesRubriques as $index => $capsuleRubrique) {
                $capsulesRubriques[$index]['RUBRIQUES'] = $rubriquesByCapsuleRubrique[$capsuleRubrique['NOM_INTERNE']];
            }

            $listeDossiers[$key]['FORMULAIRES'] = $capsulesRubriques;
        }

        $this->view->assign('informationsMembre', $listeMembres);
        $this->view->assign('listeCommunes', $tabCommune);

        $this->view->assign('dossierComm', $listeDossiers);

        $this->view->assign('dateComm', $listeDossiers[0]['DATE_COMMISSION']);
        $this->view->assign('heureDeb', $listeDossiers[0]['HEUREDEB_COMMISSION']);
    }

    public function generationpvAction(): void
    {
        $dateCommId = $this->_getParam('dateCommId');
        $this->view->assign('idComm', $dateCommId);
        // Suivant si l'on prend en compte les heures ou non on choisi la requete à effectuer
        $dbDateComm = new Model_DbTable_DateCommission();

        // 1 = salle . 2 = visite . 3 = groupe de visite
        // on recupere le type de commission (salle / visite / groupe de visite)
        $commissionInfo = $dbDateComm->find($dateCommId)->current()->toArray();
        $this->view->assign('dateComm', $commissionInfo['DATE_COMMISSION']);
        // On récupère le nom de la commission
        $model_commission = new Model_DbTable_Commission();
        $this->view->assign('commissionInfos', $model_commission->find($commissionInfo['COMMISSION_CONCERNE'])->toArray());
        $model_membres = new Model_DbTable_CommissionMembre();
        $this->view->assign('membresFiles', $model_membres->fetchAll('ID_COMMISSION = '.$commissionInfo['COMMISSION_CONCERNE'].' AND ID_GROUPEMENT IS NULL'));
        $dbDateCommPj = new Model_DbTable_DateCommissionPj();

        if (1 == $commissionInfo['GESTION_HEURES']) {
            $listeDossiers = $dbDateCommPj->TESTRECUPDOSSHEURE($dateCommId);
        } else {
            $listeDossiers = $dbDateCommPj->TESTRECUPDOSS($dateCommId);
        }

        $dbDossier = new Model_DbTable_Dossier();
        $dbDocUrba = new Model_DbTable_DossierDocUrba();
        $service_etablissement = new Service_Etablissement();
        $model_etablissement = new Model_DbTable_Etablissement();

        foreach ($listeDossiers as $val => $ue) {
            // On recupere la liste des établissements qui concernent le dossier
            $listeEtab = $dbDossier->getEtablissementDossierGenConvoc($ue['ID_DOSSIER']);

            // on recupere la liste des infos des établissement
            if (isset($listeEtab[0]['ID_ETABLISSEMENT'])) {
                $etablissementInfos = $service_etablissement->get($listeEtab[0]['ID_ETABLISSEMENT']);
                $listeDossiers[$val]['infosEtab'] = $etablissementInfos;
            }

            $listeDocUrba = $dbDocUrba->getDossierDocUrba($ue['ID_DOSSIER']);
            $listeDossiers[$val]['listeDocUrba'] = $listeDocUrba;
            $service_dossier = new Service_Dossier();
            $listeDossiers[$val]['prescriptionReglDossier'] = $service_dossier->getPrescriptions((int) $ue['ID_DOSSIER'], 0);
            $listeDossiers[$val]['prescriptionExploitation'] = $service_dossier->getPrescriptions((int) $ue['ID_DOSSIER'], 1);
            $listeDossiers[$val]['prescriptionAmelioration'] = $service_dossier->getPrescriptions((int) $ue['ID_DOSSIER'], 2);

            $listeDossiers[$val]['AVIS_DEROGATIONS'] = $dbDossier->getListAvisDerogationsFromDossier($ue['ID_DOSSIER']);
            $listeDossiers[$val]['AVIS_DEROGATIONS_ETABLISSEMENT'] = empty($listeDossiers[$val]['infosEtab']) ? [] : $model_etablissement->getListAvisDerogationsEtablissement($listeDossiers[$val]['infosEtab']['general']['ID_ETABLISSEMENT']);

            // FIXME Remplacer les $listeDossiers[$val] par $ue
            // Gestion des formulaires personnalisés
            $rubriquesDossier = $this->serviceDescriptifDossier->getRubriques($listeDossiers[$val]['ID_DOSSIER'], 'Dossier');
            $rubriquesEtablissement = empty($listeDossiers[$val]['infosEtab']) ? '' : $this->serviceDescriptifEtablissement->getRubriques($listeDossiers[$val]['infosEtab']['general']['ID_ETABLISSEMENT'], 'Etablissement');
            $rubriquesDossierEffectifsDegagements = $this->serviceDossierEffectifsDegagements->getRubriques($listeDossiers[$val]['ID_DOSSIER'], 'Dossier');
            $rubriquesEtablissementEffectifsDegagements = empty($listeDossiers[$val]['infosEtab']) ? '' : $this->serviceEtablissementEffectifsDegagements->getRubriques($listeDossiers[$val]['infosEtab']['general']['ID_ETABLISSEMENT'], 'Etablissement');

            $rubriquesByCapsuleRubrique = [
                'descriptifVerificationsTechniques' => $rubriquesDossier,
                'descriptifEtablissement' => $rubriquesEtablissement,
                'effectifsDegagementsDossier' => $rubriquesDossierEffectifsDegagements,
                'effectifsDegagementsEtablissement' => $rubriquesEtablissementEffectifsDegagements,
            ];

            // Gestion des rubriques/champs personnalisés
            $capsulesRubriques = $this->serviceFormulaire->getAllCapsuleRubrique();

            // Récupération des rubriques pour chaque objet global
            foreach ($capsulesRubriques as $index => $capsuleRubrique) {
                $capsulesRubriques[$index]['RUBRIQUES'] = $rubriquesByCapsuleRubrique[$capsuleRubrique['NOM_INTERNE']];
            }

            $listeDossiers[$val]['FORMULAIRES'] = $capsulesRubriques;
        }

        $this->view->assign('dossierComm', $listeDossiers);
    }

    public function generationcompterenduAction(): void
    {
        $dateCommId = $this->_getParam('dateCommId');
        $this->view->assign('idComm', $dateCommId);
        // Suivant si l'on prend en compte les heures ou non on choisi la requete à effectuer
        $dbDateComm = new Model_DbTable_DateCommission();
        $commissionInfo = $dbDateComm->find($dateCommId)->current()->toArray();
        $this->view->assign('dateComm', $commissionInfo['DATE_COMMISSION']);
        // 1 = salle . 2 = visite . 3 = groupe de visite
        // on recupere le type de commission (salle / visite / groupe de visite)
        $commissionInfo = $dbDateComm->find($dateCommId)->current()->toArray();

        // On récupère le nom de la commission
        $model_commission = new Model_DbTable_Commission();
        $this->view->assign('commissionInfos', $model_commission->find($commissionInfo['COMMISSION_CONCERNE'])->toArray());
        $model_membres = new Model_DbTable_CommissionMembre();

        $this->view->assign('membresFiles', $model_membres->fetchAll('ID_COMMISSION = '.$commissionInfo['COMMISSION_CONCERNE']));
        $dbDateCommPj = new Model_DbTable_DateCommissionPj();

        if (1 == $commissionInfo['GESTION_HEURES']) {
            $listeDossiers = $dbDateCommPj->TESTRECUPDOSSHEURE($dateCommId);
        } else {
            $listeDossiers = $dbDateCommPj->TESTRECUPDOSS($dateCommId);
        }

        $dbDossier = new Model_DbTable_Dossier();
        $dbDocUrba = new Model_DbTable_DossierDocUrba();
        $service_etablissement = new Service_Etablissement();
        $model_etablissement = new Model_DbTable_Etablissement();

        foreach ($listeDossiers as $val => $ue) {
            // On recupere la liste des établissements qui concernent le dossier
            $listeEtab = $dbDossier->getEtablissementDossierGenConvoc($ue['ID_DOSSIER']);
            // on recupere la liste des infos des établissement
            if (isset($listeEtab[0]['ID_ETABLISSEMENT'])) {
                $etablissementInfos = $service_etablissement->get($listeEtab[0]['ID_ETABLISSEMENT']);
                $listeDossiers[$val]['infosEtab'] = $etablissementInfos;
            }

            $listeDocUrba = $dbDocUrba->getDossierDocUrba($ue['ID_DOSSIER']);
            $listeDossiers[$val]['listeDocUrba'] = $listeDocUrba;

            $listeDossiers[$val]['AVIS_DEROGATIONS'] = $dbDossier->getListAvisDerogationsFromDossier($ue['ID_DOSSIER']);
            $listeDossiers[$val]['AVIS_DEROGATIONS_ETABLISSEMENT'] = empty($listeDossiers[$val]['infosEtab']) ? [] : $model_etablissement->getListAvisDerogationsEtablissement($listeDossiers[$val]['infosEtab']['general']['ID_ETABLISSEMENT']);

            // Gestion des formulaires personnalisés
            $rubriquesDossier = $this->serviceDescriptifDossier->getRubriques($listeDossiers[$val]['ID_DOSSIER'], 'Dossier');
            $rubriquesEtablissement = empty($listeDossiers[$val]['infosEtab']) ? '' : $this->serviceDescriptifEtablissement->getRubriques($listeDossiers[$val]['infosEtab']['general']['ID_ETABLISSEMENT'], 'Etablissement');
            $rubriquesDossierEffectifsDegagements = $this->serviceDossierEffectifsDegagements->getRubriques($listeDossiers[$val]['ID_DOSSIER'], 'Dossier');
            $rubriquesEtablissementEffectifsDegagements = empty($listeDossiers[$val]['infosEtab']) ? '' : $this->serviceEtablissementEffectifsDegagements->getRubriques($listeDossiers[$val]['infosEtab']['general']['ID_ETABLISSEMENT'], 'Etablissement');

            $rubriquesByCapsuleRubrique = [
                'descriptifVerificationsTechniques' => $rubriquesDossier,
                'descriptifEtablissement' => $rubriquesEtablissement,
                'effectifsDegagementsDossier' => $rubriquesDossierEffectifsDegagements,
                'effectifsDegagementsEtablissement' => $rubriquesEtablissementEffectifsDegagements,
            ];

            // Gestion des rubriques/champs personnalisés
            $capsulesRubriques = $this->serviceFormulaire->getAllCapsuleRubrique();

            // Récupération des rubriques pour chaque objet global
            foreach ($capsulesRubriques as $index => $capsuleRubrique) {
                $capsulesRubriques[$index]['RUBRIQUES'] = $rubriquesByCapsuleRubrique[$capsuleRubrique['NOM_INTERNE']];
            }

            $listeDossiers[$val]['FORMULAIRES'] = $capsulesRubriques;
        }

        $this->view->assign('dossierComm', $listeDossiers);
    }

    public function alertsuppressionAction(): void
    {
        $this->view->assign('commissionId', $this->_getParam('commissionId'));
        $this->view->assign('dateCommission', $this->_getParam('dateCommission'));
    }

    public function validsuppressionAction(): void
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->view->assign('commissionId', $this->_getParam('commissionId'));
        $this->view->assign('dateCommission', $this->_getParam('dateCommission'));

        $dbDossierAffect = new Model_DbTable_DossierAffectation();
        $listeDossiersAffect = $dbDossierAffect->getDossierAffect($this->_getParam('dateCommission'));
        $listeDossierNonAffect = $dbDossierAffect->getDossierNonAffect($this->_getParam('dateCommission'));
        $listeDossiers = array_merge($listeDossiersAffect, $listeDossierNonAffect);
        // On supprime les dates de commission et de visite dans les dossiers
        $dbDossier = new Model_DbTable_Dossier();
        foreach ($listeDossiers as $dossier) {
            $dossier = $dbDossier->find($dossier['ID_DOSSIER'])->current();
            $dossier['DATECOMM_DOSSIER'] = null;
            $dossier->save();
        }

        // On supprime ensuite les liens dans dossier affectation
        $dbDossierAffectation = new Model_DbTable_DossierAffectation();
        $whereDossAffect = $dbDossierAffectation->getAdapter()->quoteInto('ID_DATECOMMISSION_AFFECT = ?', $this->_getParam('dateCommission'));
        $dbDossierAffectation->delete($whereDossAffect);
        // On supprime toute les pièces jointes physiquement et dans la base de données
        $dbDateCommPj = new Model_DbTable_DateCommissionPj();
        $listePj = $dbDateCommPj->getPjInfos($this->_getParam('dateCommission'));
        $store = Zend_Controller_Front::getInstance()->getParam('bootstrap')->getResource('dataStore');
        foreach ($listePj as $pj) {
            $path = $store->getFilePath($pj, 'dateCommission', $this->_getParam('dateCommission'));
            if (file_exists($path)) {
                unlink($path);
            }
        }

        $whereDateCommPj = $dbDateCommPj->getAdapter()->quoteInto('ID_DATECOMMISSION = ?', $this->_getParam('dateCommission'));
        $dbDateCommPj->delete($whereDateCommPj);

        $dbDateComm = new Model_DbTable_DateCommission();
        $dateComm = $dbDateComm->find($this->_getParam('dateCommission'))->current();
        $dateComm->delete();
    }

    public function exportoutlookAction(): void
    {
        $idDateComm = $this->_getParam('dateCommId');

        $this->view->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        header('Content-Type: text/Calendar');
        header('Content-Disposition: inline; filename=calendar.ics');

        $ics = '';

        if (null != $idDateComm) {
            $dbDateCommission = new Model_DbTable_DateCommission();
            $dbCommission = new Model_DbTable_Commission();

            $commissionEvent = $dbDateCommission->find($idDateComm)->toArray();

            if ([] !== $commissionEvent) {
                $row = $commissionEvent[0];
                $dateStart = str_replace('-', '', $row['DATE_COMMISSION']);
                $dateStart .= 'T'.str_replace(':', '', $row['HEUREDEB_COMMISSION']);
                $dateEnd = str_replace('-', '', $row['DATE_COMMISSION']);
                $dateEnd .= 'T'.str_replace(':', '', $row['HEUREFIN_COMMISSION']);

                $descriptifAdd = '';
                $commissionArray = $dbCommission->find($row['COMMISSION_CONCERNE'])->toArray();
                if (null != $commissionArray) {
                    $commission = $commissionArray[0];
                    $descriptifAdd .= ' / Commission : '.$commission['LIBELLE_COMMISSION'];
                }

                $ics .= "BEGIN:VCALENDAR\n";

                $ics .= $idDateComm;
                $ics .= "VERSION:2.0\n";
                $ics .= "PRODID:SDIS62/Prevarisc\n";
                $ics .= "METHOD:REQUEST\n"; // required by Outlook
                $ics .= "BEGIN:VEVENT\n";
                $ics .= "ORGANIZER:prevarisc@atos.net\n";
                $ics .= 'DTSTART:'.$dateStart."\n";
                $ics .= 'DTEND:'.$dateEnd."\n";
                $ics .= 'SUMMARY:'.$row['LIBELLE_DATECOMMISSION']."\n";
                $ics .= 'DESCRIPTION:'.$row['LIBELLE_DATECOMMISSION'].$descriptifAdd."\n";
                $ics .= 'UID:'.date('Ymd').'T'.date('His').'-'.random_int(0, mt_getrandmax())."prevarisc\n";
                $ics .= "SEQUENCE:0\n";
                $ics .= 'DTSTAMP:'.date('Ymd').'T'.date('His')."\n";
                $ics .= "END:VEVENT\n";
                $ics .= "END:VCALENDAR\n";
            }
        }

        echo $ics;
    }

    public function exportoutlookmoisAction(): void
    {
        $idComm = $this->_getParam('CommId');
        $mois = $this->_getParam('Mois');
        $annee = $this->_getParam('Annee');

        $this->view->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $ics = '';

        if (null != $idComm && null != $mois && null != $annee) {
            $dbDateCommission = new Model_DbTable_DateCommission();
            $dbCommission = new Model_DbTable_Commission();
            $dossiersaff = new Model_DbTable_DossierAffectation();

            $commissions = $dbDateCommission->getMonthCommission($mois, $annee, $idComm);
            $commissionArray = $dbCommission->getLibelleCommissions($idComm);

            $libellecommission = $commissionArray[0];

            if ([] !== $commissions) {
                header('Content-Type: text/Calendar');
                header('Content-Disposition: inline; filename=calendar_'.$mois.'_'.$annee.'_'.$libellecommission['LIBELLE_COMMISSION'].'.ics');

                $ics .= "BEGIN:VCALENDAR\n";
                $ics .= "VERSION:2.0\n";
                $ics .= "PRODID:SDIS62/Prevarisc\n";

                foreach ($commissions as $commissiondujour) {
                    $dateStart = str_replace('-', '', $commissiondujour['DATE_COMMISSION']);
                    $dateStart .= 'T'.str_replace(':', '', $commissiondujour['HEUREDEB_COMMISSION']);
                    $dateEnd = str_replace('-', '', $commissiondujour['DATE_COMMISSION']);
                    $dateEnd .= 'T'.str_replace(':', '', $commissiondujour['HEUREFIN_COMMISSION']);
                    $descriptifAdd = '';

                    $descriptifAdd .= ' / Commission : '.$libellecommission['LIBELLE_COMMISSION'];

                    $dossieraffecte = $dossiersaff->getListDossierAffect($commissiondujour['ID_DATECOMMISSION']);

                    if ([] !== $dossieraffecte) {
                        $descriptifAdd .= ' / Ordre du jour : ';
                        foreach ($dossieraffecte as $dossier) {
                            $descriptifAdd .= $dossier['OBJET_DOSSIER'].';';
                        }
                    }

                    $ics .= "BEGIN:VEVENT\n";
                    $ics .= "ORGANIZER:prevarisc@atos.net\n";
                    $ics .= 'DTSTART:'.$dateStart."\n";
                    $ics .= 'DTEND:'.$dateEnd."\n";
                    $ics .= 'SUMMARY:'.$commissiondujour['LIBELLE_DATECOMMISSION']."\n";
                    $ics .= 'DESCRIPTION:'.$commissiondujour['LIBELLE_DATECOMMISSION'].$descriptifAdd."\n";
                    $ics .= "END:VEVENT\n";
                }

                $ics .= "END:VCALENDAR\n";
            }
        }

        echo $ics;
    }
}
