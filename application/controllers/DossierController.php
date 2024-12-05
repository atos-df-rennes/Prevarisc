<?php

class DossierController extends Zend_Controller_Action
{
    public const ID_DOSSIERTYPE_VISITE = 2;

    public const ID_DOSSIERTYPE_GRPVISITE = 3;

    public const ID_NATURE_LEVEE_PRESCRIPTIONS = 7;

    public const ID_NATURE_LEVEE_AVIS_DEF = 19;

    public const ID_NATURE_PERIODIQUE_VISITE = 21;

    public const ID_NATURE_PERIODIQUE_GRPVISITE = 26;

    public const ID_AVIS_DEFAVORABLE = 2;

    public const ID_GENRE_ETABLISSEMENT = 2;

    public const ID_GENRE_CELLULE = 3;

    public const ID_ACTIVITE_CENTRE_COMMERCIAL = 29;

    public $cache;

    /**
     * @var int|mixed
     */
    public $idDossier;

    /**
     * @var array<string, mixed>|mixed
     */
    public $infosDossier;

    public $listeDossierLies;

    private $id_dossier;

    // liste des champs à afficher en fonction de la nature
    private $listeChamps = [
        // ETUDES
        // PC - OK
        '1' => ['type', 'DATEINSERT', 'OBJET', 'NUMDOCURBA', 'NUMCHRONO', 'DATEMAIRIE', 'DATESECRETARIAT', 'SERVICEINSTRUC', 'COMMISSION', 'DESCGEN', 'DESCEFF', 'DATECOMM', 'AVIS', 'COORDSSI', 'DATESDIS', 'DATEREP', 'PREVENTIONNISTE', 'ABSQUORUM', 'DEMANDEUR', 'INCOMPLET', 'HORSDELAI', 'AVIS_COMMISSION', 'OBSERVATION'],
        // AT - OK
        '2' => ['type', 'DATEINSERT', 'OBJET', 'NUMDOCURBA', 'NUMCHRONO', 'DATEMAIRIE', 'DATESECRETARIAT', 'SERVICEINSTRUC', 'COMMISSION', 'DESCGEN', 'DESCEFF', 'DATECOMM', 'AVIS', 'COORDSSI', 'DATESDIS', 'DATEREP', 'PREVENTIONNISTE', 'ABSQUORUM', 'DEMANDEUR', 'INCOMPLET', 'HORSDELAI', 'AVIS_COMMISSION', 'OBSERVATION'],
        // Dérogation - OK
        '3' => ['type', 'DATEINSERT', 'OBJET', 'NUMDOCURBA', 'NUMCHRONO', 'DATEMAIRIE', 'DATESECRETARIAT', 'SERVICEINSTRUC', 'COMMISSION', 'DESCGEN', 'JUSTIFDEROG', 'MESURESCOMPENS', 'MESURESCOMPLE', 'DESCEFF', 'DATECOMM', 'AVIS', 'COORDSSI', 'DATESDIS', 'DATEREP', 'PREVENTIONNISTE', 'ABSQUORUM', 'DEMANDEUR', 'REGLEDEROG', 'INCOMPLET', 'HORSDELAI', 'AVIS_COMMISSION', 'OBSERVATION'],
        // Cahier des charges fonctionnel du SSI - OK
        '4' => ['type', 'DATEINSERT', 'OBJET', 'NUMCHRONO', 'DATEMAIRIE', 'DATESECRETARIAT', 'COMMISSION', 'DESCGEN', 'DATECOMM', 'AVIS', 'COORDSSI', 'DATESDIS', 'DATEREP', 'PREVENTIONNISTE', 'ABSQUORUM', 'DEMANDEUR', 'INCOMPLET', 'HORSDELAI', 'AVIS_COMMISSION', 'OBSERVATION'],
        // Cahier des charges de type T - OK
        '5' => ['DATEINSERT', 'OBJET', 'NUMCHRONO', 'DATEMAIRIE', 'DATESECRETARIAT', 'COMMISSION', 'DESCGEN', 'DESCEFF', 'DATECOMM', 'AVIS', 'DATESDIS', 'DATEREP', 'PREVENTIONNISTE', 'ABSQUORUM', 'DEMANDEUR', 'INCOMPLET', 'HORSDELAI', 'AVIS_COMMISSION', 'OBSERVATION'],
        // Salon type T - OK
        '6' => ['DATEINSERT', 'OBJET', 'NUMCHRONO', 'DATEMAIRIE', 'DATESECRETARIAT', 'COMMISSION', 'DESCGEN', 'DESCEFF', 'DATECOMM', 'AVIS', 'DATESDIS', 'DATEREP', 'CHARGESEC', 'PREVENTIONNISTE', 'ABSQUORUM', 'DEMANDEUR', 'INCOMPLET', 'HORSDELAI', 'AVIS_COMMISSION', 'OBSERVATION'],
        // RVRMD (diag sécu) => Levée de prescriptions - OK
        '7' => ['DATEINSERT', 'OBJET', 'NUMCHRONO', 'DATEMAIRIE', 'DATESECRETARIAT', 'COMMISSION', 'DESCGEN', 'DESCEFF', 'DATECOMM', 'AVIS', 'DATESDIS', 'PREVENTIONNISTE', 'ABSQUORUM', 'DEMANDEUR', 'INCOMPLET', 'HORSDELAI', 'AVIS_COMMISSION', 'OBSERVATION'],
        // Documents divers - OK
        '8' => ['DATEINSERT', 'OBJET', 'NUMCHRONO', 'DATEMAIRIE', 'DATESECRETARIAT', 'COMMISSION', 'DESCGEN', 'DESCEFF', 'DATECOMM', 'AVIS', 'DATESDIS', 'DATEPREF', 'DATEREP', 'PREVENTIONNISTE', 'ABSQUORUM', 'DEMANDEUR', 'INCOMPLET', 'HORSDELAI', 'AVIS_COMMISSION', 'OBSERVATION'],
        // Changement de DUS - OK
        '9' => ['DATEINSERT', 'OBJET', 'NUMCHRONO', 'DATEMAIRIE', 'DATESECRETARIAT', 'COMMISSION', 'DATECOMM', 'AVIS', 'DATESDIS', 'DATEREP', 'PREVENTIONNISTE', 'ABSQUORUM', 'DEMANDEUR', 'INCOMPLET', 'HORSDELAI', 'AVIS_COMMISSION', 'OBSERVATION'],
        // Suivi organisme formation SSIAP - OK
        '10' => ['DATEINSERT', 'OBJET', 'NUMCHRONO', 'AVIS', 'DATESDIS', 'DATEPREF', 'DATEREP', 'PREVENTIONNISTE', 'ABSQUORUM', 'DEMANDEUR', 'INCOMPLET', 'HORSDELAI', 'AVIS_COMMISSION', 'OBSERVATION'],
        // Demande de registre de sécurité CTS - OK
        '11' => ['DATEINSERT', 'OBJET', 'NUMCHRONO', 'DATESECRETARIAT', 'COMMISSION', 'DESCGEN', 'DESCEFF', 'DATECOMM', 'AVIS', 'DATESDIS', 'DATEPREF', 'DATEREP', 'PREVENTIONNISTE', 'ABSQUORUM', 'DEMANDEUR', 'INCOMPLET', 'HORSDELAI', 'AVIS_COMMISSION', 'OBSERVATION'],
        // Demande d'implantation CTS < 6mois - OK
        '12' => ['DATEINSERT', 'OBJET', 'NUMCHRONO', 'DATEMAIRIE', 'DATESECRETARIAT', 'COMMISSION', 'DESCGEN', 'DESCEFF', 'DATECOMM', 'AVIS', 'DATESDIS', 'DATEREP', 'PREVENTIONNISTE', 'ABSQUORUM', 'DEMANDEUR', 'INCOMPLET', 'HORSDELAI', 'AVIS_COMMISSION', 'OBSERVATION'],
        // Demande d'implantation CTS > 6mois - OK
        '13' => ['DATEINSERT', 'OBJET', 'NUMCHRONO', 'DATEMAIRIE', 'DATESECRETARIAT', 'COMMISSION', 'DESCGEN', 'DESCEFF', 'DATECOMM', 'AVIS', 'DATESDIS', 'DATEREP', 'PREVENTIONNISTE', 'ABSQUORUM', 'DEMANDEUR', 'INCOMPLET', 'HORSDELAI', 'AVIS_COMMISSION', 'OBSERVATION'],
        // Permis d'aménager - OK
        '14' => ['DATEINSERT', 'OBJET', 'NUMDOCURBA', 'NUMCHRONO', 'COMMISSION', 'DATEMAIRIE', 'DATESECRETARIAT', 'SERVICEINSTRUC', 'DESCGEN', 'DESCEFF', 'DATECOMM', 'AVIS', 'DATESDIS', 'DATEREP', 'PREVENTIONNISTE', 'ABSQUORUM', 'DEMANDEUR', 'INCOMPLET', 'HORSDELAI', 'AVIS_COMMISSION', 'OBSERVATION'],
        // Permis de démolir - OK
        '15' => ['DATEINSERT', 'OBJET', 'NUMDOCURBA', 'NUMCHRONO', 'DATEMAIRIE', 'DATESECRETARIAT', 'COMMISSION', 'DESCGEN', 'DESCEFF', 'DATECOMM', 'AVIS', 'DATESDIS', 'DATEREP', 'PREVENTIONNISTE', 'ABSQUORUM', 'DEMANDEUR', 'INCOMPLET', 'HORSDELAI', 'AVIS_COMMISSION', 'OBSERVATION'],
        // CR de visite des organismes d'ins.... - OK
        '16' => ['DATEINSERT', 'OBJET', 'NUMCHRONO', 'DATESECRETARIAT', 'COMMISSION', 'DESCGEN', 'DESCEFF', 'DATECOMM', 'AVIS', 'DATESDIS', 'DATEPREF', 'DATEREP', 'PREVENTIONNISTE', 'ABSQUORUM', 'DEMANDEUR', 'INCOMPLET', 'HORSDELAI', 'AVIS_COMMISSION', 'OBSERVATION'],
        // Etude suite a un avis ne se prononce pas - OK MAIS VOIR POUR PARTICULARITé TABLEAU
        '17' => ['DATEINSERT', 'OBJET', 'NUMCHRONO', 'DATEMAIRIE', 'DATESECRETARIAT', 'COMMISSION', 'DESCGEN', 'DESCEFF', 'DATECOMM', 'AVIS', 'DATESDIS', 'DATEREP', 'PREVENTIONNISTE', 'ABSQUORUM', 'DEMANDEUR', 'INCOMPLET', 'HORSDELAI', 'AVIS_COMMISSION', 'OBSERVATION'],
        // Utilisation exceptionnelle de locaux - OK
        '18' => ['DATEINSERT', 'OBJET', 'NUMCHRONO', 'DATEMAIRIE', 'DATESECRETARIAT', 'COMMISSION', 'DESCGEN', 'DESCEFF', 'DATECOMM', 'AVIS', 'DATESDIS', 'DATEREP', 'PREVENTIONNISTE', 'ABSQUORUM', 'DEMANDEUR', 'INCOMPLET', 'HORSDELAI', 'AVIS_COMMISSION', 'OBSERVATION'],
        // Levée de réserves - OK
        '19' => ['DATEINSERT', 'OBJET', 'NUMCHRONO', 'DATEMAIRIE', 'DATESECRETARIAT', 'COMMISSION', 'DESCGEN', 'DESCEFF', 'DATECOMM', 'AVIS', 'DATESDIS', 'DATEREP', 'PREVENTIONNISTE', 'ABSQUORUM', 'DEMANDEUR', 'AVIS_COMMISSION', 'OBSERVATION'],
        // Echéncier de travaux - OK
        '46' => ['DATEINSERT', 'OBJET', 'NUMCHRONO', 'DATEMAIRIE', 'DATESECRETARIAT', 'COMMISSION', 'DESCGEN', 'DESCEFF', 'DATECOMM', 'AVIS', 'DATESDIS', 'DATEREP', 'PREVENTIONNISTE', 'DEMANDEUR', 'INCOMPLET', 'HORSDELAI', 'AVIS_COMMISSION', 'OBSERVATION'],
        // Déclaration préalable
        '30' => ['DATEINSERT', 'OBJET', 'NUMCHRONO', 'DATEMAIRIE', 'DATESECRETARIAT', 'SERVICEINSTRUC', 'COMMISSION', 'DESCGEN', 'DESCEFF', 'DATECOMM', 'AVIS', 'DATESDIS', 'DATEREP', 'PREVENTIONNISTE', 'DEMANDEUR', 'INCOMPLET', 'HORSDELAI', 'AVIS_COMMISSION', 'OBSERVATION', 'NUMDOCURBA'],
        // RVRMD diag sécu
        '33' => ['DATEINSERT', 'OBJET', 'NUMCHRONO', 'DATEMAIRIE', 'DATESECRETARIAT', 'COMMISSION', 'DESCGEN', 'DESCEFF', 'DATECOMM', 'AVIS', 'DATESDIS', 'DATEREP', 'PREVENTIONNISTE', 'ABSQUORUM', 'DEMANDEUR', 'INCOMPLET', 'HORSDELAI', 'AVIS_COMMISSION', 'OBSERVATION'],
        // Autorisation d'une ICPE - OK
        '61' => ['type', 'DATEINSERT', 'OBJET', 'NUMCHRONO', 'DATEMAIRIE', 'DATESECRETARIAT', 'SERVICEINSTRUC', 'COMMISSION', 'DESCGEN', 'DESCEFF', 'DATECOMM', 'AVIS', 'COORDSSI', 'DATESDIS', 'DATEREP', 'PREVENTIONNISTE', 'DEMANDEUR', 'INCOMPLET', 'HORSDELAI', 'AVIS_COMMISSION', 'OBSERVATION'],
        // Certificats d'urbanisme (CU) - OK
        '62' => ['type', 'DATEINSERT', 'OBJET', 'NUMDOCURBA', 'NUMCHRONO', 'DATEMAIRIE', 'DATESECRETARIAT', 'SERVICEINSTRUC', 'COMMISSION', 'DESCGEN', 'DESCEFF', 'DATECOMM', 'AVIS', 'COORDSSI', 'DATESDIS', 'DATEREP', 'PREVENTIONNISTE', 'DEMANDEUR', 'INCOMPLET', 'HORSDELAI', 'AVIS_COMMISSION', 'OBSERVATION'],
        // Demande d'organisation de manifestation temporaire - OK
        '63' => ['DATEINSERT', 'OBJET', 'NUMCHRONO', 'DATEMAIRIE', 'DATESECRETARIAT', 'SERVICEINSTRUC', 'COMMISSION', 'DESCGEN', 'DESCEFF', 'DATECOMM', 'AVIS', 'DATESDIS', 'DATEREP', 'PREVENTIONNISTE', 'ABSQUORUM', 'DEMANDEUR', 'INCOMPLET', 'HORSDELAI', 'AVIS_COMMISSION', 'OBSERVATION'],
        // Déclassement / Reclassement - OK
        '66' => ['DATEINSERT', 'OBJET', 'NUMCHRONO', 'DATEMAIRIE', 'DATESECRETARIAT', 'COMMISSION', 'DESCGEN', 'DESCEFF', 'DATECOMM', 'AVIS', 'DATESDIS', 'DATEREP', 'PREVENTIONNISTE', 'ABSQUORUM', 'DEMANDEUR', 'INCOMPLET', 'HORSDELAI', 'AVIS_COMMISSION', 'OBSERVATION'],
        // VISITE DE COMMISSION
        // Réception de travaux - OK
        '20' => ['DATEINSERT', 'OBJET', 'COMMISSION', 'DESCGEN', 'DESCEFF', 'DATEVISITE', 'COORDSSI', 'DATEREP', 'PREVENTIONNISTE', 'ABSQUORUM', 'NPSP', 'AVIS_COMMISSION', 'OBSERVATION', 'DATERVRAT', 'DELAIPRESC'],
        // Avant ouverture - OK
        '47' => ['DATEINSERT', 'OBJET', 'COMMISSION', 'DESCGEN', 'DESCEFF', 'DATEVISITE', 'COORDSSI', 'DATEREP', 'PREVENTIONNISTE', 'ABSQUORUM', 'NPSP', 'AVIS_COMMISSION', 'OBSERVATION', 'DATERVRAT', 'DELAIPRESC'],
        // Périodique - OK
        '21' => ['DATEINSERT', 'COMMISSION', 'DESCGEN', 'DESCEFF', 'DATEVISITE', 'DATEREP', 'PREVENTIONNISTE', 'DIFFEREAVIS', 'ABSQUORUM', 'AVIS', 'AVIS_COMMISSION', 'OBSERVATION', 'DELAIPRESC'],
        // Chantier - OK
        '22' => ['DATEINSERT', 'OBJET', 'COMMISSION', 'DESCGEN', 'DESCEFF', 'DATEVISITE', 'COORDSSI', 'DATEREP', 'PREVENTIONNISTE', 'OBSERVATION', 'DELAIPRESC'],
        // Controle - OK
        '23' => ['DATEINSERT', 'OBJET', 'COMMISSION', 'DESCGEN', 'DESCEFF', 'DATEVISITE', 'COORDSSI', 'DATEREP', 'PREVENTIONNISTE', 'DIFFEREAVIS', 'ABSQUORUM', 'AVIS_COMMISSION', 'OBSERVATION', 'DELAIPRESC'],
        // Inopinéee - OK
        '24' => ['DATEINSERT', 'OBJET', 'COMMISSION', 'DESCGEN', 'DESCEFF', 'DATEVISITE', 'DATEREP', 'PREVENTIONNISTE', 'DIFFEREAVIS', 'ABSQUORUM', 'AVIS_COMMISSION', 'OBSERVATION', 'DELAIPRESC'],
        // Visite conseil - OK
        '64' => ['DATEINSERT', 'OBJET', 'COMMISSION', 'DESCGEN', 'DESCEFF', 'DATEVISITE', 'COORDSSI', 'DATEREP', 'PREVENTIONNISTE', 'OBSERVATION', 'DELAIPRESC'],
        // GROUPE DE VISITE
        // Réception de travaux - OK
        '25' => ['type', 'DATEINSERT', 'OBJET', 'COMMISSION', 'DESCGEN', 'DESCEFF', 'DATECOMM', 'DATEVISITE', 'AVIS', 'COORDSSI', 'DATEREP', 'PREVENTIONNISTE', 'NPSP', 'ABSQUORUM', 'AVIS_COMMISSION', 'OBSERVATION', 'DATERVRAT', 'DELAIPRESC'],
        // Avant ouverture - OK
        '48' => ['DATEINSERT', 'OBJET', 'COMMISSION', 'DESCGEN', 'DESCEFF', 'DATECOMM', 'DATEVISITE', 'AVIS', 'COORDSSI', 'DATEREP', 'PREVENTIONNISTE', 'NPSP', 'ABSQUORUM', 'AVIS_COMMISSION', 'OBSERVATION', 'DATERVRAT', 'DELAIPRESC'],
        // Périodique - OK
        '26' => ['DATEINSERT', 'COMMISSION', 'DESCGEN', 'DESCEFF', 'DATECOMM', 'DATEVISITE', 'AVIS', 'DATEREP', 'PREVENTIONNISTE', 'DIFFEREAVIS', 'ABSQUORUM', 'AVIS_COMMISSION', 'OBSERVATION', 'DELAIPRESC'],
        // Chantier - OK
        '27' => ['DATEINSERT', 'OBJET', 'COMMISSION', 'DESCGEN', 'DESCEFF', 'DATEVISITE', 'COORDSSI', 'DATEREP', 'PREVENTIONNISTE', 'OBSERVATION', 'DELAIPRESC'],
        // Controle - OK
        '28' => ['DATEINSERT', 'OBJET', 'COMMISSION', 'DESCGEN', 'DESCEFF', 'DATECOMM', 'DATEVISITE', 'AVIS', 'COORDSSI', 'DATEREP', 'PREVENTIONNISTE', 'DIFFEREAVIS', 'ABSQUORUM', 'AVIS_COMMISSION', 'OBSERVATION', 'DELAIPRESC'],
        // Inopinéee - OK
        '29' => ['DATEINSERT', 'OBJET', 'COMMISSION', 'DESCGEN', 'DESCEFF', 'DATECOMM', 'DATEVISITE', 'AVIS', 'DATEREP', 'PREVENTIONNISTE', 'DIFFEREAVIS', 'ABSQUORUM', 'AVIS_COMMISSION', 'OBSERVATION', 'DELAIPRESC'],
        // REUNION
        // Locaux SDIS - OK
        '31' => ['DATEINSERT', 'OBJET', 'DATEREUN', 'DATEREP', 'PREVENTIONNISTE', 'DEMANDEUR', 'OBSERVATION'],
        // Exterieur SDIS - OK
        '32' => ['DATEINSERT', 'OBJET', 'LIEUREUNION', 'DATEREUN', 'DATEREP', 'PREVENTIONNISTE', 'DEMANDEUR', 'OBSERVATION'],
        // Téléphonique - OK
        '43' => ['DATEINSERT', 'OBJET', 'DATEREUN', 'DATEREP', 'PREVENTIONNISTE', 'DEMANDEUR', 'OBSERVATION'],
        // COURRIER/COURRIEL
        // Lettre - OK
        '52' => ['DATEINSERT', 'OBJET', 'NUMCHRONO', 'DATEMAIRIE', 'PREVENTIONNISTE', 'DATEREP', 'DATEENVTRANSIT', 'PREVENTIONNISTE', 'DATESDIS', 'DEMANDEUR', 'DATETRANSFERTCOMM', 'DATERECEPTIONCOMM', 'OBSERVATION'],
        // Mise en demeure - OK
        '55' => ['DATEINSERT', 'OBJET', 'NUMCHRONO', 'DATEMAIRIE', 'PREVENTIONNISTE', 'DATEREP', 'DATEENVTRANSIT', 'PREVENTIONNISTE', 'DATESDIS', 'DEMANDEUR', 'DATETRANSFERTCOMM', 'DATERECEPTIONCOMM', 'OBSERVATION'],
        // Avis écrit motivé - OK
        '51' => ['DATEINSERT', 'OBJET', 'NUMCHRONO', 'DATEMAIRIE', 'PREVENTIONNISTE', 'DATEREP', 'DATEENVTRANSIT', 'PREVENTIONNISTE', 'DATESDIS', 'DEMANDEUR', 'DATETRANSFERTCOMM', 'DATERECEPTIONCOMM', 'OBSERVATION'],
        // Consultation PLU - OK
        '53' => ['DATEINSERT', 'OBJET', 'NUMCHRONO', 'DATEMAIRIE', 'PREVENTIONNISTE', 'DATEREP', 'DATEENVTRANSIT', 'PREVENTIONNISTE', 'DATESDIS', 'DEMANDEUR', 'DATETRANSFERTCOMM', 'DATERECEPTIONCOMM', 'OBSERVATION'],
        // Rapport d'organisme agréé - OK
        '49' => ['DATEINSERT', 'OBJET', 'NUMCHRONO', 'DATEMAIRIE', 'PREVENTIONNISTE', 'DATEREP', 'DATEENVTRANSIT', 'PREVENTIONNISTE', 'DATESDIS', 'DEMANDEUR', 'DATETRANSFERTCOMM', 'DATERECEPTIONCOMM', 'OBSERVATION'],
        // Demande de renseignements
        '54' => ['DATEINSERT', 'OBJET', 'NUMCHRONO', 'DATEMAIRIE', 'PREVENTIONNISTE', 'DATEREP', 'DATEENVTRANSIT', 'PREVENTIONNISTE', 'DATESDIS', 'DEMANDEUR', 'DATETRANSFERTCOMM', 'DATERECEPTIONCOMM', 'OBSERVATION'],
        // Demande de visite périodique
        '59' => ['DATEINSERT', 'OBJET', 'NUMCHRONO', 'DATEMAIRIE', 'PREVENTIONNISTE', 'DATEREP', 'DATEENVTRANSIT', 'PREVENTIONNISTE', 'DATESDIS', 'DEMANDEUR', 'DATETRANSFERTCOMM', 'DATERECEPTIONCOMM', 'OBSERVATION'],
        // Demande de visite technique
        '57' => ['DATEINSERT', 'OBJET', 'NUMCHRONO', 'DATEMAIRIE', 'PREVENTIONNISTE', 'DATEREP', 'DATEENVTRANSIT', 'PREVENTIONNISTE', 'DATESDIS', 'DEMANDEUR', 'DATETRANSFERTCOMM', 'DATERECEPTIONCOMM', 'OBSERVATION'],
        // Demande de visite inopinée
        '58' => ['DATEINSERT', 'OBJET', 'NUMCHRONO', 'DATEMAIRIE', 'PREVENTIONNISTE', 'DATEREP', 'DATEENVTRANSIT', 'PREVENTIONNISTE', 'DATESDIS', 'DEMANDEUR', 'DATETRANSFERTCOMM', 'DATERECEPTIONCOMM', 'OBSERVATION'],
        // Demande de visite hors programme
        '50' => ['DATEINSERT', 'OBJET', 'NUMCHRONO', 'DATEMAIRIE', 'PREVENTIONNISTE', 'DATEREP', 'DATEENVTRANSIT', 'PREVENTIONNISTE', 'DATESDIS', 'DEMANDEUR', 'DATETRANSFERTCOMM', 'DATERECEPTIONCOMM', 'OBSERVATION'],
        // Demande de visite de réception
        '60' => ['DATEINSERT', 'OBJET', 'NUMCHRONO', 'DATEMAIRIE', 'PREVENTIONNISTE', 'DATEREP', 'DATEENVTRANSIT', 'PREVENTIONNISTE', 'DATESDIS', 'DEMANDEUR', 'DATETRANSFERTCOMM', 'DATERECEPTIONCOMM', 'OBSERVATION'],
        // Autorisation de travaux
        '65' => ['DATEINSERT', 'OBJET', 'NUMCHRONO', 'DATEMAIRIE', 'PREVENTIONNISTE', 'DATEREP', 'DATEENVTRANSIT', 'PREVENTIONNISTE', 'DATESDIS', 'DEMANDEUR', 'DATETRANSFERTCOMM', 'DATERECEPTIONCOMM', 'OBSERVATION'],
        // INTERVENTION
        // Incendie - OK
        '37' => ['DATEINSERT', 'OBJET', 'OPERSDIS', 'RCCI', 'REX', 'NUMINTERV', 'DATEINTERV', 'DUREEINTERV', 'DATEREP', 'PREVENTIONNISTE', 'OBSERVATION'],
        // SAP - OK
        '38' => ['DATEINSERT', 'OBJET', 'OPERSDIS', 'REX', 'NUMINTERV', 'DATEINTERV', 'DUREEINTERV', 'DATEREP', 'PREVENTIONNISTE', 'OBSERVATION'],
        // Intervention div - OK
        '39' => ['DATEINSERT', 'OBJET', 'OPERSDIS', 'REX', 'NUMINTERV', 'DATEINTERV', 'DUREEINTERV', 'DATEREP', 'PREVENTIONNISTE', 'OBSERVATION'],
        // ARRETE
        // Ouverture - OK
        '40' => ['DATEINSERT', 'DATESIGN', 'DATEREP', 'PREVENTIONNISTE', 'OBSERVATION'],
        // Fermeture - OK
        '41' => ['DATEINSERT', 'OBJET', 'DATESIGN', 'DATEREP', 'PREVENTIONNISTE', 'OBSERVATION'],
        // Mise en demeure - OK
        '42' => ['DATEINSERT', 'OBJET', 'DATESIGN', 'DATEREP', 'PREVENTIONNISTE', 'OBSERVATION'],
        // Utilisation exceptionnelle de locaux - OK
        '44' => ['DATEINSERT', 'OBJET', 'DATESIGN', 'DATEREP', 'PREVENTIONNISTE', 'OBSERVATION'],
        // Courrier - OK
        '45' => ['DATEINSERT', 'OBJET', 'DATESIGN', 'DATEREP', 'PREVENTIONNISTE', 'OBSERVATION'],
    ];

    public function init(): void
    {
        $this->_helper->layout->setLayout('dossier');
        $this->view->inlineScript()->appendFile('/js/dossier/dossierGeneral.js', 'text/javascript');
        $this->view->inlineScript()->appendFile('/js/dossier/platau.js', 'text/javascript');
        $this->view->headLink()->appendStylesheet('/css/etiquetteAvisDerogations/greenCircle.css', 'all');

        // Actions à effectuées en AJAX
        $ajaxContext = $this->_helper->getHelper('AjaxContext');
        $ajaxContext->addActionContext('selectionabreviation', 'json')
            ->addActionContext('selectionetab', 'json')
            ->initContext()
        ;

        $this->cache = Zend_Controller_Front::getInstance()->getParam('bootstrap')->getResource('cache');

        if (!(property_exists($this->view, 'action') && null !== $this->view->action)) {
            $this->view->assign('action', $this->getRequest()->getActionName());
        }

        $this->view->assign('idUser', Zend_Auth::getInstance()->getIdentity()['ID_UTILISATEUR']);

        $this->idDossier = (int) $this->getRequest()->getParam('id');
        // FIXME A déplacer dans le 2ème if ?
        $this->view->assign('idDossier', $this->idDossier);

        if (null == $this->idDossier) {
            $this->idDossier = (int) $this->getRequest()->getParam('idDossier');
        }

        if (null != $this->idDossier) {
            // Si on à l'id d'un dossier, on récupére tous les établissements liés à ce dossier
            $DBdossier = new Model_DbTable_Dossier();
            $dossier = $DBdossier->find($this->idDossier)->current();

            $this->view->assign('id_platau', $dossier['ID_PLATAU'] ?? null);

            if (null !== $dossier['ID_PLATAU']) {
                if (filter_var(getenv('PREVARISC_DEACTIVATE_PLATAU'), FILTER_VALIDATE_BOOLEAN)) {
                    throw new Exception("Plat'AU est désactivé", 500);
                }

                $platauConsultationMapper = new Model_PlatauConsultationMapper();
                $platauConsultationModel = new Model_PlatauConsultation();
                $this->view->assign('enumStatutsPec', new Model_Enum_PlatauStatutPec());
                $this->view->assign('enumStatutsAvis', new Model_Enum_PlatauStatutAvis());

                $platauConsultation = $platauConsultationMapper->find($dossier['ID_PLATAU'], $platauConsultationModel);

                if ($platauConsultation instanceof Model_PlatauConsultation) {
                    $this->view->assign('statutPec', $platauConsultation->getStatutPec());
                    $this->view->assign('datePec', $platauConsultation->getDatePec());
                    $this->view->assign('statutAvis', $platauConsultation->getStatutAvis());
                    $this->view->assign('dateAvis', $platauConsultation->getDateAvis());

                    $tempsRestant = Service_Utils_TempsRestant::calculate($platauConsultation->getDateReponseAttendue());
                    $this->view->assign('tempsRestant', $tempsRestant);

                    if (null !== $tempsRestant) {
                        $this->view->assign('couleurTempsRestant', Service_Utils_TempsRestant::getCouleurTempsRestant($platauConsultation->getDateReponseAttendue()));
                    }
                }
            }

            $DBdossierType = new Model_DbTable_DossierType();
            $libelleType = $DBdossierType->find($dossier->TYPE_DOSSIER)->current();

            $this->view->assign('objetDossier', $dossier->OBJET_DOSSIER);
            $this->view->assign('idTypeDossier', $dossier->TYPE_DOSSIER);
            $this->view->assign('libelleType', $libelleType['LIBELLE_DOSSIERTYPE']);

            $natureDossier = $DBdossier->getDossierTypeNature($this->idDossier);
            $this->view->assign('natureDossier', $natureDossier[0]['ID_NATURE']);
            // FIXME Il faut en virer un des 2, ils font la même chose : Attention aux impacts dans les services et les vues
            $this->view->assign('verrouDossier', $dossier['VERROU_DOSSIER']);
            $this->view->assign('verrou', $dossier->VERROU_DOSSIER);

            $serviceDossier = new Service_Dossier();
            $this->view->assign('hasAvisDerogation', $serviceDossier->hasAvisDerogation($this->idDossier));
            $this->view->assign('dossierSupprime', null !== $dossier['DATESUPPRESSION_DOSSIER']);
            $this->view->assign('nombreNouvellesPiecesJointes', $serviceDossier->getNombreNouvellesPiecesJointes($this->idDossier));

            // Noms des onglets paramétrables
            $modelCapsuleRubrique = new Model_DbTable_CapsuleRubrique();
            $this->view->assign('nomOngletVerificationsTechniques', $modelCapsuleRubrique->getCapsuleRubriqueByInternalName('descriptifVerificationsTechniques')['NOM']);
            $this->view->assign('nomOngletEffectifsDegagements', $modelCapsuleRubrique->getCapsuleRubriqueByInternalName('effectifsDegagementsDossier')['NOM']);

            // Définition des autorisations
            $this->view->assign('isAllowedAvisDerogation', unserialize($this->cache->load('acl'))->isAllowed(Zend_Auth::getInstance()->getIdentity()['group']['LIBELLE_GROUPE'], 'avisderogations', 'avis_derogations'));
            $this->view->assign('isAllowedEffectifsDegagements', unserialize($this->cache->load('acl'))->isAllowed(Zend_Auth::getInstance()->getIdentity()['group']['LIBELLE_GROUPE'], 'effectifs_degagements', 'effectifs_degagements_doss'));
            $this->view->assign('isAllowedVerificationsTechniques', unserialize($this->cache->load('acl'))->isAllowed(Zend_Auth::getInstance()->getIdentity()['group']['LIBELLE_GROUPE'], 'verificationstechniques', 'verifications_techniques'));
        }
    }

    public function pieceJointeAction(): void
    {
        $DBdossier = new Model_DbTable_Dossier();
        $service_dossier = new Service_Dossier();
        $id = $this->getRequest()->getParam('id');

        if ($this->idDossier) {
            $this->view->assign('enteteEtab', $service_dossier->getEtabInfos($this->idDossier));
        }

        $this->infosDossier = $DBdossier->find((int) $id)->current();

        $this->forward('index', 'piece-jointe', null, [
            'type' => 'dossier',
            'id' => $id,
            'verrou' => $this->infosDossier['VERROU_DOSSIER'],
        ]);
    }

    public function addAction(): void
    {
        $this->view->assign('action', 'add');
        $this->forward('index');
    }

    public function indexAction(): void
    {
        $historiqueEtab = [];
        $this->view->headScript()->appendFile('/js/tinymce.min.js');

        $this->view->assign('do', 'new');
        if ($this->getRequest()->getParam('id')) {
            $this->view->assign('do', 'edit');
            $this->view->assign('idDossier', $this->getRequest()->getParam('id'));
        }

        $service_dossier = new Service_Dossier();
        if ($this->idDossier) {
            $this->view->assign('enteteEtab', $service_dossier->getEtabInfos($this->idDossier));
        } elseif ($this->getRequest()->getParam('id_etablissement')) {
            $this->view->assign('enteteEtab', $service_dossier->getEtabInfos(null, $this->getRequest()->getParam('id_etablissement')));
        }

        $this->view->assign('idEtablissement', $this->getRequest()->getParam('id_etablissement'));
        if (property_exists($this->view, 'idEtablissement') && null !== $this->view->idEtablissement) {
            $DBetablissement = new Model_DbTable_Etablissement();
            $this->view->assign('etablissementLibelle', $DBetablissement->getLibelle($this->getRequest()->getParam('id_etablissement')));
        }

        $this->view->assign('idUser', Zend_Auth::getInstance()->getIdentity()['ID_UTILISATEUR']);
        $this->view->assign('userInfos', Zend_Auth::getInstance()->getIdentity());

        // On récupère tous les types de dossier
        $DBdossierType = new Model_DbTable_DossierType();
        $DBdossier = new Model_DbTable_Dossier();
        $this->view->assign('dossierType', $DBdossierType->fetchAll());

        // Récupération de la liste des avis pour la génération du select
        $DBlisteAvis = new Model_DbTable_Avis();
        $this->view->assign('listeAvis', $DBlisteAvis->getAvis());
        $this->view->assign('afficherChamps', []);

        $listeMois = ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];
        $this->view->assign('mois', $listeMois);

        // AUTORISATIONS CHANGEMENT AVIS DE LA COMMISSION
        $cache = Zend_Controller_Front::getInstance()->getParam('bootstrap')->getResource('cache');

        $this->view->assign('is_allowed_change_avis', unserialize($cache->load('acl'))->isAllowed(Zend_Auth::getInstance()->getIdentity()['group']['LIBELLE_GROUPE'], 'avis_commission', 'edit_avis_com'));

        // Autorisation de suppression du dossier
        $this->view->assign('is_allowed_delete_dossier', unserialize($cache->load('acl'))->isAllowed(Zend_Auth::getInstance()->getIdentity()['group']['LIBELLE_GROUPE'], 'suppression', 'delete_dossier'));

        $service_etablissement = new Service_Etablissement();

        if ($this->getRequest()->getParam('idEtablissement')) {
            $this->view->assign('idEtablissement', $this->getRequest()->getParam('idEtablissement'));
        }

        // RECUPERATIONS INFOS ETABLISSEMENT (cellule ou etab pour generation des avis)
        if ($this->getRequest()->getParam('id_etablissement')) {
            $DBetab = new Model_DbTable_Etablissement();
            $etabTab = $DBetab->getInformations($this->getRequest()->getParam('id_etablissement'));
            $etablissement = $etabTab->toArray();
            $this->view->assign('genre', $etablissement['ID_GENRE']);
            $commissionEtab = $etablissement['ID_COMMISSION'];
            $idEtablissement = $this->getRequest()->getParam('id_etablissement');

            $etablissementInfos = $service_etablissement->get($this->view->idEtablissement);
            $ID_DOSSIER_DONNANT_AVIS = $etablissementInfos['general']['ID_DOSSIER_DONNANT_AVIS'];

            $this->view->assign('avisExploitationEtab', 3);
            if (null != $ID_DOSSIER_DONNANT_AVIS) {
                $avisExploitationEtab = $DBdossier->getAvisDossier($ID_DOSSIER_DONNANT_AVIS);
                $this->view->assign('avisExploitationEtab', $avisExploitationEtab['AVIS_DOSSIER']);
            }

            $historiqueEtab = $service_etablissement->getHistorique($this->getRequest()->getParam('id_etablissement'));
        } elseif (0 !== (int) $this->getRequest()->getParam('id')) {
            $tabEtablissement = $DBdossier->getEtablissementDossier((int) $this->getRequest()->getParam('id'));
            $this->view->assign('listeEtablissement', $tabEtablissement);
            if ([] !== $tabEtablissement) {
                $DBetab = new Model_DbTable_Etablissement();
                $etablissement = $DBetab->getInformations($tabEtablissement[0]['ID_ETABLISSEMENT'])->toArray();
                $this->view->assign('genre', $etablissement['ID_GENRE']);
                $commissionEtab = $etablissement['ID_COMMISSION'];
                $idEtablissement = $tabEtablissement[0]['ID_ETABLISSEMENT'];

                $etablissementInfos = $service_etablissement->get($idEtablissement);
                $ID_DOSSIER_DONNANT_AVIS = $etablissementInfos['general']['ID_DOSSIER_DONNANT_AVIS'];

                $this->view->assign('avisExploitationEtab', 3);
                if (null != $ID_DOSSIER_DONNANT_AVIS) {
                    $avisExploitationEtab = $DBdossier->getAvisDossier($ID_DOSSIER_DONNANT_AVIS);
                    $this->view->assign('avisExploitationEtab', $avisExploitationEtab['AVIS_DOSSIER']);
                }

                $historiqueEtab = $service_etablissement->getHistorique($idEtablissement);
            }
        }

        if (isset($historiqueEtab['avis'])) {
            $nbAvisEtab = count($historiqueEtab['avis']);
            $this->view->assign('lastAvisEtab', $historiqueEtab['avis'][$nbAvisEtab - 1]['valeur']);
        }

        if (isset($commissionEtab)) {
            $this->view->assign('commissionEtab', $commissionEtab);
        }

        if (isset($idEtablissement)) {
            $this->view->assign('idEtablissement', $idEtablissement);
        }

        $today = new Zend_Date();
        $this->view->assign('dateToday', $today->get(Zend_Date::DAY.'/'.Zend_Date::MONTH.'/'.Zend_Date::YEAR));

        $DBdossierCommission = new Model_DbTable_Commission();

        // Modèle de données
        $model_typesDesCommissions = new Model_DbTable_CommissionType();
        $model_commission = new Model_DbTable_Commission();

        // On cherche tous les types de commissions
        $rowset_typesDesCommissions = $model_typesDesCommissions->fetchAll();

        // Tableau de résultats
        $array_commissions = [];

        // Pour tous les types, on cherche leur commission
        foreach ($rowset_typesDesCommissions as $row_typeDeCommission) {
            $array_commissions[$row_typeDeCommission->ID_COMMISSIONTYPE] = [
                'LIBELLE' => $row_typeDeCommission->LIBELLE_COMMISSIONTYPE,
                'ARRAY' => $model_commission->fetchAll('ID_COMMISSIONTYPE = '.$row_typeDeCommission->ID_COMMISSIONTYPE)->toArray(),
            ];
        }

        $this->view->assign('array_commissions', $array_commissions);

        if (0 !== (int) $this->getRequest()->getParam('id')) {
            // Cas d'affichage des infos d'un dossier existant
            $this->view->assign('do', 'edit');
            // On récupère l'id du dossier
            $idDossier = (int) $this->getRequest()->getParam('id');
            $this->view->assign('idDossier', $idDossier);
            // Récupération de tous les champs de la table dossier
            $this->view->assign('infosDossier', $DBdossier->find($idDossier)->current());

            // On verifie les éléments masquant l'avis et la date de commission/visite pour les afficher ou non
            // document manquant - absence de quorum - hors delai - ne peut se prononcer - differe l'avis
            $absQuorum = filter_var($this->view->infosDossier['ABSQUORUM_DOSSIER'], FILTER_VALIDATE_BOOLEAN);
            $horsDelai = filter_var($this->view->infosDossier['HORSDELAI_DOSSIER'], FILTER_VALIDATE_BOOLEAN);
            $npsp = filter_var($this->view->infosDossier['NPSP_DOSSIER'], FILTER_VALIDATE_BOOLEAN);
            $differeAvis = filter_var($this->view->infosDossier['DIFFEREAVIS_DOSSIER'], FILTER_VALIDATE_BOOLEAN);
            $incompletDossier = filter_var($this->view->infosDossier['INCOMPLET_DOSSIER'], FILTER_VALIDATE_BOOLEAN);

            // Debut mise en place avec service (voir pour récup le type)
            $afficheAvis = 1;
            if (
                $absQuorum
                || $horsDelai
                || $npsp
                || $differeAvis
                || $incompletDossier
            ) {
                $afficheAvis = 0;
            }

            $this->view->assign('afficheAvis', $afficheAvis);

            // récuperation des informations sur le créateur du dossier
            $DB_user = new Model_DbTable_Utilisateur();
            $DB_informations = new Model_DbTable_UtilisateurInformations();
            $this->view->assign('user_info', '');
            if ('' !== $this->view->infosDossier['CREATEUR_DOSSIER'] && null !== $this->view->infosDossier['CREATEUR_DOSSIER']) {
                $user = $DB_user->find($this->view->infosDossier['CREATEUR_DOSSIER'])->current();
                $this->view->assign('user_info', $DB_informations->find($user->ID_UTILISATEURINFORMATIONS)->current());
            }

            if ('' !== $this->view->infosDossier['VERROU_USER_DOSSIER'] && null !== $this->view->infosDossier['VERROU_USER_DOSSIER']) {
                $user = $DB_user->find($this->view->infosDossier['VERROU_USER_DOSSIER'])->current();
                $this->view->assign('user_infoVerrou', $DB_informations->find($user->ID_UTILISATEURINFORMATIONS)->current());
            }

            // Conversion de la date d'insertion du dossier
            $this->view->infosDossier['DATEINSERT_DOSSIER'] = Service_Utils_Date::formatDateWithDayName($this->view->infosDossier['DATEINSERT_DOSSIER']);
            $this->view->assign('DATEINSERT_INPUT', Service_Utils_Date::convertFromMySQL($this->view->infosDossier['DATEINSERT_DOSSIER']));

            // Conversion de la date de dépot en mairie pour l'afficher
            $this->view->infosDossier['DATEMAIRIE_DOSSIER'] = Service_Utils_Date::formatDateWithDayName($this->view->infosDossier['DATEMAIRIE_DOSSIER']);
            $this->view->assign('DATEMAIRIE_INPUT', Service_Utils_Date::convertFromMySQL($this->view->infosDossier['DATEMAIRIE_DOSSIER']));

            // Conversion de la date de dépot en secrétariat pour l'afficher
            $this->view->infosDossier['DATESECRETARIAT_DOSSIER'] = Service_Utils_Date::formatDateWithDayName($this->view->infosDossier['DATESECRETARIAT_DOSSIER']);
            $this->view->assign('DATESECRETARIAT_INPUT', Service_Utils_Date::convertFromMySQL($this->view->infosDossier['DATESECRETARIAT_DOSSIER']));

            // Conversion de la date de dépot en secrétariat pour l'afficher
            $this->view->infosDossier['DATEENVTRANSIT_DOSSIER'] = Service_Utils_Date::formatDateWithDayName($this->view->infosDossier['DATEENVTRANSIT_DOSSIER']);
            $this->view->assign('DATEENVTRANSIT_INPUT', Service_Utils_Date::convertFromMySQL($this->view->infosDossier['DATEENVTRANSIT_DOSSIER']));

            // Conversion de la date de réception SDIS
            $this->view->infosDossier['DATESDIS_DOSSIER'] = Service_Utils_Date::formatDateWithDayName($this->view->infosDossier['DATESDIS_DOSSIER']);
            $this->view->assign('DATESDIS_INPUT', Service_Utils_Date::convertFromMySQL($this->view->infosDossier['DATESDIS_DOSSIER']));

            // Conversion de la date prefecture
            $this->view->infosDossier['DATEPREF_DOSSIER'] = Service_Utils_Date::formatDateWithDayName($this->view->infosDossier['DATEPREF_DOSSIER']);
            $this->view->assign('DATEPREF_INPUT', Service_Utils_Date::convertFromMySQL($this->view->infosDossier['DATEPREF_DOSSIER']));

            // Conversion de la date de réponse
            $this->view->infosDossier['DATEREP_DOSSIER'] = Service_Utils_Date::formatDateWithDayName($this->view->infosDossier['DATEREP_DOSSIER']);
            $this->view->assign('DATEREP_INPUT', Service_Utils_Date::convertFromMySQL($this->view->infosDossier['DATEREP_DOSSIER']));

            // Conversion de la date de réunion
            $this->view->infosDossier['DATEREUN_DOSSIER'] = Service_Utils_Date::formatDateWithDayName($this->view->infosDossier['DATEREUN_DOSSIER']);
            $this->view->assign('DATEREUN_INPUT', Service_Utils_Date::convertFromMySQL($this->view->infosDossier['DATEREUN_DOSSIER']));

            // Conversion de la date et l'heure d'intervention
            if ('' != $this->view->infosDossier['DATEINTERV_DOSSIER']) {
                $dateHeure = explode(' ', $this->view->infosDossier['DATEINTERV_DOSSIER']);
                $this->view->infosDossier['DATEINTERV_DOSSIER'] = Service_Utils_Date::formatDateWithDayName($this->view->infosDossier['DATEINTERV_DOSSIER']);
                $this->view->assign('DATEINTERV_INPUT', Service_Utils_Date::convertFromMySQL($this->view->infosDossier['DATEINTERV_DOSSIER']));
                $heure = explode(':', $dateHeure[1]);
                $this->view->assign('HEUREINTERV_INPUT', $heure[0].':'.$heure[1]);
            }

            // Conversion de la date signature
            $this->view->infosDossier['DATESIGN_DOSSIER'] = Service_Utils_Date::formatDateWithDayName($this->view->infosDossier['DATESIGN_DOSSIER']);
            $this->view->assign('DATESIGN_INPUT', Service_Utils_Date::convertFromMySQL($this->view->infosDossier['DATESIGN_DOSSIER']));

            // Conversion date echeancier de travaux
            $this->view->infosDossier['ECHEANCIERTRAV_DOSSIER'] = Service_Utils_Date::formatDateWithDayName($this->view->infosDossier['ECHEANCIERTRAV_DOSSIER']);
            $this->view->assign('ECHEANCIERTRAV', Service_Utils_Date::convertFromMySQL($this->view->infosDossier['ECHEANCIERTRAV_DOSSIER']));

            // Conversion date incomplet
            $this->view->infosDossier['DATEINCOMPLET_DOSSIER'] = Service_Utils_Date::formatDateWithDayName($this->view->infosDossier['DATEINCOMPLET_DOSSIER']);
            $this->view->assign('DATEINCOMPLET', Service_Utils_Date::convertFromMySQL($this->view->infosDossier['DATEINCOMPLET_DOSSIER']));

            // Conversion de transfert à la commission compétente
            $this->view->infosDossier['DATETRANSFERTCOMM_DOSSIER'] = Service_Utils_Date::formatDateWithDayName($this->view->infosDossier['DATETRANSFERTCOMM_DOSSIER']);
            $this->view->assign('DATETRANSFERTCOMM', Service_Utils_Date::convertFromMySQL($this->view->infosDossier['DATETRANSFERTCOMM_DOSSIER']));

            // Conversion de reception à la commission compétente
            $this->view->infosDossier['DATERECEPTIONCOMM_DOSSIER'] = Service_Utils_Date::formatDateWithDayName($this->view->infosDossier['DATERECEPTIONCOMM_DOSSIER']);
            $this->view->assign('DATERECEPTIONCOMM', Service_Utils_Date::convertFromMySQL($this->view->infosDossier['DATERECEPTIONCOMM_DOSSIER']));

            // Conversion de la date de reception du rvrat
            $this->view->infosDossier['DATERVRAT_DOSSIER'] = Service_Utils_Date::formatDateWithDayName($this->view->infosDossier['DATERVRAT_DOSSIER']);
            $this->view->assign('DATERVRAT_INPUT', Service_Utils_Date::convertFromMySQL($this->view->infosDossier['DATERVRAT_DOSSIER']));

            // Conversion de la date de levée de prescriptions
            $this->view->infosDossier['DELAIPRESC_DOSSIER'] = Service_Utils_Date::formatDateWithDayName($this->view->infosDossier['DELAIPRESC_DOSSIER']);
            $this->view->assign('DELAIPRESC_INPUT', Service_Utils_Date::convertFromMySQL($this->view->infosDossier['DELAIPRESC_DOSSIER']));

            // Conversion de la durée de l'intervention
            if ('' != $this->view->infosDossier['DUREEINTERV_DOSSIER']) {
                $heure = explode(':', $this->view->infosDossier['DUREEINTERV_DOSSIER']);
                $this->view->infosDossier['DUREEINTERV_DOSSIER'] = $heure[0].':'.$heure[1];
            }

            if ('' != $this->view->infosDossier['DESCGEN_DOSSIER']) {
                $this->view->infosDossier['DESCGEN_DOSSIER'] = nl2br($this->view->infosDossier['DESCGEN_DOSSIER']);
                $this->view->assign('DESCGEN_INPUT', str_replace('<br />', '', $this->view->infosDossier['DESCGEN_DOSSIER']));
            }

            if ('' != $this->view->infosDossier['DESCRIPTIF_DOSSIER']) {
                $this->view->infosDossier['DESCRIPTIF_DOSSIER'] = nl2br($this->view->infosDossier['DESCRIPTIF_DOSSIER']);
                $this->view->assign('DESCRIPTIF_INPUT', str_replace('<br />', '', $this->view->infosDossier['DESCRIPTIF_DOSSIER']));
            }

            if ('' != $this->view->infosDossier['AVIS_DOSSIER']) {
                $this->view->assign('AVIS_VALUE', $DBlisteAvis->getAvisLibelle($this->view->infosDossier['AVIS_DOSSIER']));
            }

            if ('' != $this->view->infosDossier['AVIS_DOSSIER_COMMISSION']) {
                $this->view->assign('AVIS_COMMISSION_VALUE', $DBlisteAvis->getAvisLibelle($this->view->infosDossier['AVIS_DOSSIER_COMMISSION']));
            }

            // Récupération du libellé du type de dossier
            $libelleType = $DBdossierType->find($this->view->infosDossier['TYPE_DOSSIER'])->current();
            $this->view->assign('libelleType', $libelleType['LIBELLE_DOSSIERTYPE']);

            // Récupération tous les libellé des natures du dossier concerné
            $DBdossierNature = new Model_DbTable_DossierNature();
            $this->view->assign('natureConcerne', $DBdossierNature->getDossierNaturesLibelle($idDossier));

            // Récupération de la liste des natures pour la génération du select
            $DBdossierNatureListe = new Model_DbTable_DossierNatureliste();
            $this->view->assign('dossierNatureListe', $DBdossierNatureListe->getDossierNature($this->view->infosDossier['TYPE_DOSSIER']));

            // Récupération de la liste des documents d'urbanismes
            $DBdossierDocUrba = new Model_DbTable_DossierDocUrba();
            $this->view->assign('dossierDocUrba', $DBdossierDocUrba->getDossierDocUrba($idDossier));

            // On récupére l'ensemble des commissions pour l'affichage du select
            // ICI RéCUPERATION DU LIBELLE DE LA COMMISSION !!!!!!!!!!! PUIS AFFICHAGE DANS LE INPUT !!!
            $this->view->assign('commissionInfos', $DBdossierCommission->find($this->view->infosDossier['COMMISSION_DOSSIER'])->current());
            $this->view->assign('commissionInfosCommissionType', $model_typesDesCommissions->find($this->view->commissionInfos['ID_COMMISSIONTYPE'])->current());

            // On récupère la liste de tous les champs que l'on doit afficher en fonction des natures
            // Si il y à plusieurs natures on les fait une par une pour savoir tous les champs à afficher
            $premiereNature = 1;
            $afficherChamps = [];
            foreach ($this->view->natureConcerne as $value) {
                if (1 == $premiereNature) {
                    $afficherChamps = $this->listeChamps[$value['ID_NATURE']];
                    $premiereNature = 0;
                } else {
                    $tabTemp = $this->listeChamps[$value['ID_NATURE']];
                    foreach ($tabTemp as $value) {
                        // si la nature contient un champ n'étant pas dans le tableau principal on l'ajoute
                        if (!in_array($value, $afficherChamps)) {
                            $afficherChamps[] = $value;
                        }
                    }
                }
            }

            $this->view->assign('afficherChamps', $afficherChamps);

            // On verifie les éléments masquant l'avis et la date de commission/visite pour les afficher ou non
            // GESTION DES DATES DE COMMISSIONS ET DE VISITE / GROUPE DE VISITE
            // On récupere les infos concernant l'affectation à une commission si il y en a eu une
            $dbAffectDossier = new Model_DbTable_DossierAffectation();
            $affectDossier = $dbAffectDossier->find(null, $this->getRequest()->getParam('id'))->current();
            $this->view->assign('affectDossier', $affectDossier);

            $listeDateAffectDossier = $dbAffectDossier->recupDateDossierAffect($this->getRequest()->getParam('id'));

            $dbDateComm = new Model_DbTable_DateCommission();
            $dateComm = $dbDateComm->find($affectDossier['ID_DATECOMMISSION_AFFECT'])->current();

            // En fonction du type de dossier on traite les dates d'affectation existantes differement
            if (1 == $this->view->infosDossier['TYPE_DOSSIER']) {
                // CAS D'UNE éTUDE
                // Concernant cette affectation on récupere les infos sur la commission (date aux différents format)
                if ('' != $dateComm['DATE_COMMISSION']) {
                    $date = new Zend_Date($dateComm['DATE_COMMISSION'], Zend_Date::DATES);
                    $this->view->assign('dateCommValue', $date->get(Zend_Date::WEEKDAY.' '.Zend_Date::DAY_SHORT.' '.Zend_Date::MONTH_NAME_SHORT.' '.Zend_Date::YEAR));
                    $this->view->assign('dateCommInput', $date->get(Zend_Date::DAY.'/'.Zend_Date::MONTH.'/'.Zend_Date::YEAR));
                    $this->view->assign('idDateCommissionAffect', $dateComm['ID_DATECOMMISSION']);
                }
            } elseif (self::ID_DOSSIERTYPE_VISITE == $this->view->infosDossier['TYPE_DOSSIER'] || self::ID_DOSSIERTYPE_GRPVISITE == $this->view->infosDossier['TYPE_DOSSIER']) {
                // CAS D'UNE VISITE
                foreach ($listeDateAffectDossier as $ue) {
                    if (1 == $ue['ID_COMMISSIONTYPEEVENEMENT']) {
                        // COMMISSION EN SALLE
                        $date = new Zend_Date($ue['DATE_COMMISSION'], Zend_Date::DATES);
                        $this->view->assign('dateCommValue', $date->get(Zend_Date::WEEKDAY.' '.Zend_Date::DAY_SHORT.' '.Zend_Date::MONTH_NAME_SHORT.' '.Zend_Date::YEAR));
                        $this->view->assign('dateCommInput', $date->get(Zend_Date::DAY.'/'.Zend_Date::MONTH.'/'.Zend_Date::YEAR));
                        $this->view->assign('idDateCommissionAffect', $ue['ID_DATECOMMISSION']);
                    } else {
                        // VISITE OU GROUPE DE VISITE
                        $dateVisite = $dbDateComm->getInfosVisite($this->getRequest()->getParam('id'));

                        $dateLiees = $dbDateComm->getDateLieesv2($dateVisite['ID_DATECOMMISSION_AFFECT']);
                        $this->view->assign('dateVisite', $this->view->infosDossier['DATEVISITE_DOSSIER']);

                        $nbDates = count($dateLiees);

                        $listeDateValue = '';
                        $listeDateInput = '';
                        foreach ($dateLiees as $ue) {
                            $this->view->assign('idDateVisiteAffect', $ue['ID_DATECOMMISSION']);
                            $listeDateValue .= Service_Utils_Date::formatDateWithDayName($ue['DATE_COMMISSION']);
                            $listeDateInput .= Service_Utils_Date::convertFromMySQL($ue['DATE_COMMISSION']);
                            if ($nbDates > 1) {
                                $listeDateValue .= ', ';
                                $listeDateInput .= ', ';
                            }

                            --$nbDates;
                        }

                        $this->view->assign('idDateVisiteAffect', $dateVisite['ID_DATECOMMISSION_AFFECT']);
                        $this->view->assign('dateVisiteValue', $listeDateValue);
                        $this->view->assign('dateVisiteInput', $listeDateInput);
                    }
                }
            }

            // Recuperation des documents manquants dans le cas d'un dossier incomplet
            $dbDossDocManquant = new Model_DbTable_DossierDocManquant();
            $this->view->assign('listeDocManquant', $dbDossDocManquant->getDocManquantDoss($this->getRequest()->getParam('id')));

            $DBdossierPrev = new Model_DbTable_DossierPreventionniste();
            $this->view->assign('preventionnistes', $DBdossierPrev->getPrevDossier($this->getRequest()->getParam('id')));
        } else {
            $this->view->assign('do', 'new');
            $search = new Model_DbTable_Search();
            $preventionnistes = ($this->getRequest()->getParam('id_etablissement')) ? $search->setItem('utilisateur')->setCriteria('etablissementinformations.ID_ETABLISSEMENT', $this->getRequest()->getParam('id_etablissement'))->run()->getAdapter()->getItems(0, 99999999999)->toArray() : null;
            $preventionnistes[-1] = array_fill_keys(['LIBELLE_GRADE', 'NOM_UTILISATEURINFORMATIONS', 'PRENOM_UTILISATEURINFORMATIONS'], null);
            unset($preventionnistes[-1]);
            $this->view->assign('preventionnistes', $preventionnistes);
            $this->view->assign('listeDocManquant', []);
            $this->view->assign('dossierNatureListe', []);
        }

        // 23/10/12 Ajout du service instructeur remplacé par le select des groupements de communes
        // Liste des types de groupement
        if ($this->view->infosDossier['SERVICEINSTRUC_DOSSIER']) {
            $groupements = new Model_DbTable_Groupement();
            $servInstructeur = $this->view->infosDossier['SERVICEINSTRUC_DOSSIER'];
            $groupement = $groupements->find($servInstructeur)->current();
            $this->view->assign('serviceInstructeurLibelle', $groupement['LIBELLE_GROUPEMENT']);
            $this->view->assign('serviceInstructeurId', $groupement['ID_GROUPEMENT']);
        }
    }

    public function shownatureAction(): void
    {
        $idType = (int) $this->getRequest()->getParam('idType');

        // Récupération de la liste des natures
        $DBdossiernatureliste = new Model_DbTable_DossierNatureliste();
        $this->view->assign('dossierNatureListe', $DBdossiernatureliste->getDossierNature($idType));
    }

    public function showchampsAction(): void
    {
        $this->_helper->viewRenderer->setNoRender();
        $listeNature = $this->getRequest()->getParam('listeNature');

        // Si une liste de nature est envoyée on peux traiter les différents champs à afficher
        if ('' != $listeNature) {
            $tabListeIdNature = explode('_', $listeNature);
            $premiereNature = 1;
            $afficherChamps = [];

            foreach ($tabListeIdNature as $idNature) {
                if (1 == $premiereNature) {
                    $afficherChamps = $this->listeChamps[$idNature];
                    $premiereNature = 0;
                } else {
                    $tabTemp = $this->listeChamps[$idNature];
                    foreach ($tabTemp as $value) {
                        // si la nature contient un champ n'étant pas dans le tableau principal on l'ajoute
                        if (!in_array($value, $afficherChamps)) {
                            $afficherChamps[] = $value;
                        }
                    }
                }
            }

            echo json_encode($afficherChamps);
        }
    }

    public function ajoutdocvalidAction(): void
    {
        $this->ajoutdocAction($this->id_dossier);
    }

    public function formdocmanquantAction(): void
    {
        $dbDocManquant = new Model_DbTable_DocManquant();
        // Si on passe un id dossier en param alors on cherche le dernier champ doc manquant si il existe
        // On recupere la liste des documents manquant type
        $this->view->assign('listeDoc', $dbDocManquant->getDocManquant());
        $this->view->assign('numDocManquant', $this->getRequest()->getParam('numDoc'));

        $date = Zend_Date::now();
        $this->view->assign('dateDay', $date->get(Zend_Date::DAY_SHORT.'/'.Zend_Date::MONTH.'/'.Zend_Date::YEAR));
    }

    public function savenewAction(): void
    {
        $this->forward('save');
    }

    // Permet de faire les insertions de dossier en base de données et de rediriger vers le dossier/index/id/X => X = id du dossier qui vient d'être crée
    public function saveAction(): void
    {
        header('Content-type: application/json');

        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $cache = Zend_Controller_Front::getInstance()->getParam('bootstrap')->getResource('cache');
        $service_dossier = new Service_Dossier();

        try {
            $DBdossier = new Model_DbTable_Dossier();
            $DBdossierNature = new Model_DbTable_DossierNature();
            $nouveauDossier = null;
            $oldNature = null;
            if ('new' == $this->getRequest()->getParam('do')) {
                $nouveauDossier = $DBdossier->createRow();
                $nouveauDossier->CREATEUR_DOSSIER = $this->getRequest()->getParam('ID_CREATEUR');
            } elseif ('edit' == $this->getRequest()->getParam('do')) {
                $nouveauDossier = $DBdossier->find($this->getRequest()->getParam('idDossier'))->current();

                $oldNature = $DBdossier->getNatureDossier($this->getRequest()->getParam('idDossier'));
                $oldNature = $oldNature['ID_NATURE'];

                $newNature = $this->getRequest()->getParam('selectNature');

                $arrayT2 = [20, 47, 25, 48];

                $dbDocConsulte = new Model_DbTable_DossierDocConsulte();
                $dbDocAjout = new Model_DbTable_ListeDocAjout();

                if (
                    in_array($oldNature, $arrayT2)
                    && in_array($newNature, $arrayT2)
                ) {
                    // On conserve les documents consultés en faisant une copie dans la table docajout
                    $docRestant = $dbDocConsulte->getDocOtheNature($this->getRequest()->getParam('idDossier'), $oldNature);
                    foreach ($docRestant as $doc) {
                        $newDocAjout = $dbDocAjout->createRow();
                        $newDocAjout->LIBELLE_DOCAJOUT = $doc['LIBELLE_DOC'];
                        $newDocAjout->REF_DOCAJOUT = $doc['REF_CONSULTE'];
                        $newDocAjout->DATE_DOCAJOUT = $doc['DATE_CONSULTE'];
                        $newDocAjout->ID_DOSSIER = $doc['ID_DOSSIER'];
                        $newDocAjout->ID_NATURE = $newNature;
                        $newDocAjout->save();

                        $where = $dbDocConsulte->getAdapter()->quoteInto('ID_DOSSIERDOCCONSULTE = ?', $doc['ID_DOSSIERDOCCONSULTE']);
                        $dbDocConsulte->delete($where);
                    }
                } elseif ($oldNature != $newNature) {
                    // On supprime les documents consultés
                    $where = $dbDocAjout->getAdapter()->quoteInto('ID_DOSSIER = ?', $this->getRequest()->getParam('idDossier'));
                    $dbDocAjout->delete($where);

                    $where = $dbDocConsulte->getAdapter()->quoteInto('ID_DOSSIER = ?', $this->getRequest()->getParam('idDossier'));
                    $dbDocConsulte->delete($where);
                }
            }

            $excludes = [
                'DATEVISITE_PERIODIQUE',
                'selectNature',
                'NUM_DOCURBA',
                'natureId',
                'docUrba',
                'do',
                'idDossier',
                'HEUREINTERV_DOSSIER',
                'idEtablissement',
                'ID_AFFECTATION_DOSSIER_VISITE',
                'ID_AFFECTATION_DOSSIER_COMMISSION',
                'preventionniste',
                'commissionSelect',
                'ID_CREATEUR',
                'HORSDELAI_DOSSIER',
                'genreInfo',
                'docManquant',
                'dateReceptionDocManquant',
                'dateDocManquant',
                'ABSQUORUM_DOSSIER',
                'servInst',
                'servInstVille',
                'servInstGrp',
                'repercuterAvis',
                'INCOMPLET_DOSSIER',
                'export-pj-platau',
            ];

            $includes = [
                'DATEMAIRIE_DOSSIER',
                'DATESECRETARIAT_DOSSIER',
                'DATEVISITE_DOSSIER',
                'DATECOMM_DOSSIER',
                'DATESDIS_DOSSIER',
                'DATEPREF_DOSSIER',
                'DATEREP_DOSSIER',
                'DATEREP_DOSSIER',
                'DATEREUN_DOSSIER',
                'DATEINTERV_DOSSIER',
                'DATESIGN_DOSSIER',
                'DATEINSERT_DOSSIER',
                'DATEENVTRANSIT_DOSSIER',
                'ECHEANCIERTRAV_DOSSIER',
                'DATETRANSFERTCOMM_DOSSIER',
                'DATERECEPTIONCOMM_DOSSIER',
                'DATERVRAT_DOSSIER',
                'DELAIPRESC_DOSSIER',
            ];

            foreach ($_POST as $libelle => $value) {
                // On exclu la lecture de selectNature => select avec les natures;
                // NUM_DOCURB => input text pour la saisie des doc urba; docUrba & natureId => interpreté après;
                if (!in_array($libelle, $excludes)) {
                    // Test pour voir s'il sagit d'une date pour la convertir au format ENG et l'inserer dans la base de données
                    if (in_array($libelle, $includes)) {
                        if ($value) {
                            if ('DATEVISITE_DOSSIER' == $libelle) {
                                $dateTab = explode(', ', $value);
                                $value = $dateTab[0];
                            }

                            $dateTab = explode('/', $value);
                            $value = $dateTab[2].'-'.$dateTab[1].'-'.$dateTab[0];
                            if ('DATEINTERV_DOSSIER' == $libelle) {
                                $value .= ' '.$this->getRequest()->getParam('HEUREINTERV_DOSSIER');
                            }
                        } else {
                            $value = null;
                        }
                    }

                    if (
                        ('AVIS_DOSSIER' == $libelle && 0 == $value)
                        || '' == $value
                    ) {
                        $value = null;
                    }

                    $nouveauDossier->{$libelle} = $value;
                }
            }

            if ($pjs = $this->getRequest()->getParam('export-pj-platau')) {
                $servicePj = new Service_PieceJointe();

                $servicePj->exportPlatau($pjs);
            }

            $nouveauDossier->HORSDELAI_DOSSIER = 0;
            if ($this->getRequest()->getParam('HORSDELAI_DOSSIER')) {
                $nouveauDossier->HORSDELAI_DOSSIER = 1;
            }

            $nouveauDossier->ABSQUORUM_DOSSIER = 0;
            if ($this->getRequest()->getParam('ABSQUORUM_DOSSIER')) {
                $nouveauDossier->ABSQUORUM_DOSSIER = 1;
            }

            $nouveauDossier->NPSP_DOSSIER = 0;
            if ($this->getRequest()->getParam('NPSP_DOSSIER')) {
                $nouveauDossier->NPSP_DOSSIER = 1;
            }

            $nouveauDossier->DIFFEREAVIS_DOSSIER = 0;
            if ($this->getRequest()->getParam('DIFFEREAVIS_DOSSIER')) {
                $nouveauDossier->DIFFEREAVIS_DOSSIER = 1;
            }

            $nouveauDossier->CNE_DOSSIER = 0;
            if ($this->getRequest()->getParam('CNE_DOSSIER')) {
                $nouveauDossier->CNE_DOSSIER = 1;
            }

            if (!in_array('OBJET', $this->listeChamps[$this->getRequest()->getParam('selectNature')])) {
                $nouveauDossier->OBJET_DOSSIER = null;
            }

            if (null != $this->getRequest()->getParam('servInst')) {
                if ('servInstGrp' == $this->getRequest()->getParam('servInst')) {
                    // service instructeur groupement
                    $nouveauDossier->TYPESERVINSTRUC_DOSSIER = $this->getRequest()->getParam('servInst');
                    $nouveauDossier->SERVICEINSTRUC_DOSSIER = $this->getRequest()->getParam('servInstGrp');
                } elseif ('servInstCommune' == $this->getRequest()->getParam('servInst')) {
                    // service instructeur commune
                    $nouveauDossier->TYPESERVINSTRUC_DOSSIER = $this->getRequest()->getParam('servInst');
                    $nouveauDossier->SERVICEINSTRUC_DOSSIER = $this->getRequest()->getParam('servInstVille');
                }
            }

            $nouveauDossier->save();

            if (
                (
                    (self::ID_NATURE_PERIODIQUE_VISITE == $this->getRequest()->getParam('selectNature') && self::ID_DOSSIERTYPE_VISITE == $this->getRequest()->getParam('TYPE_DOSSIER'))
                    || self::ID_NATURE_PERIODIQUE_GRPVISITE == $this->getRequest()->getParam('selectNature')
                )
                && $this->getRequest()->getParam('DATEVISITE_PERIODIQUE')
            ) {
                // VISITE PERIODIQUE
                // Dans le cas d'une visite périodique on renseigne le champ DATEVISITE_DOSSIER pour pouvoir calculer la périodicité suviante
                $datePeriodique = explode('/', $_POST['DATEVISITE_PERIODIQUE']);
                $dateToSql = $datePeriodique[2].'-'.$datePeriodique[1].'-'.$datePeriodique[0];
                $nouveauDossier->DATEVISITE_DOSSIER = $dateToSql;
                $nouveauDossier->save();
            }

            $idDossier = $nouveauDossier->ID_DOSSIER;
            $idNature = $this->getRequest()->getParam('selectNature');

            // Si le dossier est une levée de prescription ou de reserve on ajoute 5 "documents consultés" de type : Attestation de
            if (
                'new' == $this->getRequest()->getParam('do')
                && (self::ID_NATURE_LEVEE_PRESCRIPTIONS == $idNature || self::ID_NATURE_LEVEE_AVIS_DEF == $idNature)
            ) {
                $dbListeDocAjout = new Model_DbTable_ListeDocAjout();
                $nbDocsAAjouter = 5;

                for ($i = 0; $i < $nbDocsAAjouter; ++$i) {
                    $docAttestation = $dbListeDocAjout->createRow();
                    $docAttestation->LIBELLE_DOCAJOUT = 'Attestation de';
                    $docAttestation->ID_NATURE = $idNature;
                    $docAttestation->ID_DOSSIER = $idDossier;
                    $docAttestation->DATE_DOCAJOUT = '0000-00-00';
                    $docAttestation->save();
                }
            }

            $DBetablissementDossier = new Model_DbTable_EtablissementDossier();
            if ('new' == $this->getRequest()->getParam('do')) {
                if (isset($_POST['idEtablissement']) && '' != $_POST['idEtablissement']) {
                    $saveEtabDossier = $DBetablissementDossier->createRow();
                    $saveEtabDossier->ID_ETABLISSEMENT = $this->getRequest()->getParam('idEtablissement');
                    $saveEtabDossier->ID_DOSSIER = $idDossier;
                    $saveEtabDossier->save();
                }

                // Sauvegarde des natures du dossier
                $saveNature = $DBdossierNature->createRow();
                $saveNature->ID_DOSSIER = $idDossier;
                $saveNature->ID_NATURE = $_POST['selectNature'];
                $saveNature->save();

                // Récupération des contacts de l'établissement (Resp. unique de sécu, Proprio, Exploitant, DUS)
                $dbDossierContact = new Model_DbTable_DossierContact();
                $contactsEtab = $dbDossierContact->recupContactEtablissement($this->getRequest()->getParam('idEtablissement'));

                $idsFonction = [
                    7,
                    8,
                    9,
                    17,
                ];

                foreach ($contactsEtab as $contact) {
                    if (in_array($contact['ID_FONCTION'], $idsFonction)) {
                        $newContact = $dbDossierContact->createRow();
                        $newContact->ID_DOSSIER = $idDossier;
                        $newContact->ID_UTILISATEURINFORMATIONS = $contact['ID_UTILISATEURINFORMATIONS'];
                        $newContact->save();
                    }
                }
            } else {
                // gestion des natures en mode édition
                $DBdossierNature = new Model_DbTable_DossierNature();
                $natureCheck = $DBdossierNature->getDossierNaturesId($idDossier);
                $nature = $DBdossierNature->find($natureCheck['ID_DOSSIERNATURE'])->current();
                $nature->ID_NATURE = $idNature;
                $nature->save();
            }

            // GESTION DE LA RECUPERATION DES TEXTES APPLICABLES DANS CERTAINS CAS
            // lorsque je crée un dossier visite ou groupe de visite VP (21-26), VC (22-27), VI (24-29),
            // il faut que les textes applicables à l'ERP se retrouvent de fait dans le dossier créé
            $idsNature = [
                21,
                22,
                24,
                26,
                27,
                29,
            ];

            if (
                in_array($idNature, $idsNature)
                && '' != $_POST['idEtablissement']
                && 'new' == $_POST['do']
            ) {
                $dbEtablissementTextAppl = new Model_DbTable_EtsTextesAppl();
                $listeTexteApplEtab = $dbEtablissementTextAppl->recupTextes($_POST['idEtablissement']);
                $dbDossierTexteAppl = new Model_DbTable_DossierTextesAppl();
                foreach ($listeTexteApplEtab as $ue) {
                    $saveTexteAppl = $dbDossierTexteAppl->createRow();
                    $saveTexteAppl->ID_DOSSIER = $idDossier;
                    $saveTexteAppl->ID_TEXTESAPPL = $ue['ID_TEXTESAPPL'];
                    $saveTexteAppl->save();
                }
            }

            // GESTION DE LA RECUPERATION DES PRESCRIPTIONS EN RAPPEL REGLEMETAIRE DANS LE CAS DES ETUDES ET DES VISITES
            $service_prescription = new Service_Prescriptions();
            $service_dossier = new Service_Dossier();
            if ('new' == $this->getRequest()->getParam('do')) {
                if (1 == $this->getRequest()->getParam('TYPE_DOSSIER')) {
                    $listePrescRegl = $service_prescription->getPrescriptions('etude', true);
                    $service_dossier->savePrescriptionRegl($idDossier, $listePrescRegl);
                } elseif (
                    self::ID_DOSSIERTYPE_VISITE == $this->getRequest()->getParam('TYPE_DOSSIER')
                    || self::ID_DOSSIERTYPE_GRPVISITE == $this->getRequest()->getParam('TYPE_DOSSIER')
                ) {
                    $listePrescRegl = $service_prescription->getPrescriptions('visite', true);
                    $service_dossier->savePrescriptionRegl($idDossier, $listePrescRegl);
                }
            }

            // GESTION DE LA RECUPERATION DES DOCUMENTS CONSULTES DE LA PRECEDENTE VP SI IL EN EXISTE UNE (UNIQUEMENT EN CREATION DE DOSSIER)
            if (
                (self::ID_NATURE_PERIODIQUE_VISITE == $idNature || self::ID_NATURE_PERIODIQUE_GRPVISITE == $idNature)
                && '' != $_POST['idEtablissement']
                && 'new' == $this->getRequest()->getParam('do')
            ) {
                $lastVP = $DBdossier->findLastVp($this->getRequest()->getParam('idEtablissement'));
                $idDossierLastVP = $lastVP['ID_DOSSIER'];
                if ('' != $lastVP['ID_DOSSIER']) {
                    $dblistedoc = new Model_DbTable_DossierListeDoc();
                    $dblistedocAjout = new Model_DbTable_ListeDocAjout();

                    // ici on récupère tous les documents qui ont été renseigné dans la base par un utilisateur (avec id du dossier et de la nature)
                    $listeDocRenseigne = $dblistedoc->recupDocDossier($idDossierLastVP);

                    // ici on récupère tous les documents qui ont été ajoutés par l'utilisateur (document non proposé par défaut)
                    $listeDocAjout = $dblistedocAjout->getDocAjout($idDossierLastVP);

                    // on copie les docrenseigne pour la nouvelle visite
                    $dbDocConsulte = new Model_DbTable_DossierDocConsulte();
                    foreach ($listeDocRenseigne as $ue) {
                        $cpDocConsulte = $dbDocConsulte->createRow();
                        $cpDocConsulte->ID_NATURE = $idNature;
                        $cpDocConsulte->REF_CONSULTE = $ue['REF_CONSULTE'];
                        $cpDocConsulte->DATE_CONSULTE = $ue['DATE_CONSULTE'];
                        $cpDocConsulte->DOC_CONSULTE = $ue['DOC_CONSULTE'];
                        $cpDocConsulte->ID_DOSSIER = $idDossier;
                        $cpDocConsulte->ID_DOC = $ue['ID_DOC'];
                        $cpDocConsulte->save();
                    }

                    $dbListeDocAjout = new Model_DbTable_ListeDocAjout();
                    foreach ($listeDocAjout as $ue) {
                        $cpDocAjout = $dbListeDocAjout->createRow();
                        $cpDocAjout->LIBELLE_DOCAJOUT = $ue['LIBELLE_DOCAJOUT'];
                        $cpDocAjout->REF_DOCAJOUT = $ue['REF_DOCAJOUT'];
                        $cpDocAjout->DATE_DOCAJOUT = $ue['DATE_DOCAJOUT'];
                        $cpDocAjout->ID_NATURE = $idNature;
                        $cpDocAjout->ID_DOSSIER = $idDossier;
                        $cpDocAjout->save();
                    }
                }
            }

            if (isset($_POST['docManquant'])) {
                $docManquantArray = [];
                $dateDocManquantArray = [];
                $dateDocManquantRecepArray = [];

                if (isset($_POST['docManquant'])) {
                    foreach ($_POST['docManquant'] as $value) {
                        if ('' != $value) {
                            $docManquantArray[] = $value;
                        }
                    }
                }

                if (isset($_POST['dateReceptionDocManquant'])) {
                    foreach ($_POST['dateReceptionDocManquant'] as $value) {
                        if ('' != $value) {
                            $dateDocManquantRecepArray[] = $value;
                        }
                    }
                }

                if (isset($_POST['dateDocManquant'])) {
                    foreach ($_POST['dateDocManquant'] as $value) {
                        if ('' != $value) {
                            $dateDocManquantArray[] = $value;
                        }
                    }
                }

                $nbDateParam = count($dateDocManquantArray);

                $dbDossDocManquant = new Model_DbTable_DossierDocManquant();
                $cpt = 0;
                foreach ($docManquantArray as $value) {
                    $docEnC = $dbDossDocManquant->getDocManquantDossNum($idDossier, $cpt);

                    if ($docEnC) {
                        $dossDocManquant = $dbDossDocManquant->find($docEnC['ID_DOCMANQUANT'])->current();
                        $dossDocManquant->DOCMANQUANT = $value;

                        if (
                            $nbDateParam > 0
                            && $cpt < $nbDateParam
                        ) {
                            $dateTab = explode('/', $dateDocManquantArray[$cpt]);
                            $value = $dateTab[2].'-'.$dateTab[1].'-'.$dateTab[0];

                            $dossDocManquant->DATE_DOCSMANQUANT = $value;
                            $dossDocManquant->DATE_RECEPTION_DOC = null;
                            if (
                                isset($dateDocManquantRecepArray[$cpt])
                                && null != $dateDocManquantRecepArray[$cpt]
                                && '' != $dateDocManquantRecepArray[$cpt]
                            ) {
                                $dateTabRecep = explode('/', $dateDocManquantRecepArray[$cpt]);
                                $valueRecep = $dateTabRecep[2].'-'.$dateTabRecep[1].'-'.$dateTabRecep[0];
                                $dossDocManquant->DATE_RECEPTION_DOC = $valueRecep;
                            }
                        }

                        $dossDocManquant->save();
                    } elseif (!$docEnC) {
                        $dossDocManquant = $dbDossDocManquant->createRow();
                        $dossDocManquant->ID_DOSSIER = $idDossier;
                        $dossDocManquant->NUM_DOCSMANQUANT = $cpt;
                        $dossDocManquant->DOCMANQUANT = $value;

                        if (
                            $nbDateParam > 0
                            && $cpt < $nbDateParam
                        ) {
                            $dateTab = explode('/', $dateDocManquantArray[$cpt]);
                            $value = $dateTab[2].'-'.$dateTab[1].'-'.$dateTab[0];

                            $dossDocManquant->DATE_DOCSMANQUANT = $value;
                            if (isset($dateDocManquantRecepArray[$cpt])) {
                                $dateTabRecep = explode('/', $dateDocManquantRecepArray[$cpt]);
                                $valueRecep = $dateTabRecep[2].'-'.$dateTabRecep[1].'-'.$dateTabRecep[0];
                                $dossDocManquant->DATE_RECEPTION_DOC = $valueRecep;
                            }
                        }

                        $dossDocManquant->save();
                    }

                    ++$cpt;
                }
            }

            $nouveauDossier->INCOMPLET_DOSSIER = $_POST['INCOMPLET_DOSSIER'];
            $nouveauDossier->save();

            // lorsque je crée un nouveau dossier de VP pour un ERP qui a déjà été visité, il faudrait que les « éléments consultés » de base soient les mêmes
            // Sauvegarde des numéro de document d'urbanisme du dossier
            $DBdossierDocUrba = new Model_DbTable_DossierDocUrba();
            $where = $DBdossierDocUrba->getAdapter()->quoteInto('ID_DOSSIER = ?', $idDossier);
            $DBdossierDocUrba->delete($where);

            if (isset($_POST['docUrba'])) {
                foreach ($_POST['docUrba'] as $value) {
                    $saveDocUrba = $DBdossierDocUrba->createRow();
                    $saveDocUrba->ID_DOSSIER = $idDossier;
                    $saveDocUrba->NUM_DOCURBA = $value;
                    $saveDocUrba->save();
                }
            }

            // Sauvegarde des préventionnistes
            $DBdossierPrev = new Model_DbTable_DossierPreventionniste();
            $DBdossierPrev->delete('ID_DOSSIER = '.$idDossier);
            if (isset($_POST['preventionniste'])) {
                foreach ($_POST['preventionniste'] as $infos) {
                    $savePrev = $DBdossierPrev->createRow();
                    $savePrev->ID_DOSSIER = $idDossier;
                    $savePrev->ID_PREVENTIONNISTE = $infos;
                    $savePrev->save();
                }
            }

            // Sauvegarde des informations concernant l'affectation d'un dossier à une commission
            $dbDossierAffectation = new Model_DbTable_DossierAffectation();
            $dbDateComm = new Model_DbTable_DateCommission();
            if (
                '' == $this->getRequest()->getParam('COMMISSION_DOSSIER')
                || !in_array('COMMISSION', $this->listeChamps[$this->getRequest()->getParam('selectNature')])
            ) {
                $dbDossierAffectation->deleteDateDossierAffect($idDossier);
            } else {
                $listeDateDossAffect = $dbDossierAffectation->getDossierAffectAndType($idDossier);
                foreach ($listeDateDossAffect as $dateAffect) {
                    if (1 == $dateAffect['ID_COMMISSIONTYPEEVENEMENT']) {
                        // Comm en salle
                        $infosDateSalle = $dateAffect;
                    } elseif (
                        in_array($dateAffect['ID_COMMISSIONTYPEEVENEMENT'], [2, 3])
                    ) {
                        // Visite / Groupe de visites
                        $infosDateVisite = $dateAffect;
                    }
                }

                // Partie concernant la date de visite
                if (
                    $this->getRequest()->getParam('ID_AFFECTATION_DOSSIER_VISITE')
                    && '' != $this->getRequest()->getParam('ID_AFFECTATION_DOSSIER_VISITE')
                ) {
                    if (isset($infosDateVisite)) {
                        // la date de visite existe déjà on vérifie si elle a changé
                        if ($infosDateVisite['ID_DATECOMMISSION_AFFECT'] != $this->getRequest()->getParam('ID_AFFECTATION_DOSSIER_VISITE')) {
                            // Dans le cas ou la date commission est différente de celle passée en paramètre alors on la met à jour
                            $dateEdit = $dbDossierAffectation->find($infosDateVisite['ID_DATECOMMISSION_AFFECT'], $idDossier)->current();
                            $dateEdit->ID_DATECOMMISSION_AFFECT = $this->getRequest()->getParam('ID_AFFECTATION_DOSSIER_VISITE');
                            $dateEdit->HEURE_DEB_AFFECT = null;
                            $dateEdit->HEURE_FIN_AFFECT = null;
                            $dateEdit->NUM_DOSSIER = 0;
                            $dateEdit->save();
                        }
                    } else {
                        // la date de visite n'existe pas il faut donc la crééer.
                        $affectation = $dbDossierAffectation->createRow();
                        $affectation->ID_DATECOMMISSION_AFFECT = $this->getRequest()->getParam('ID_AFFECTATION_DOSSIER_VISITE');
                        $affectation->ID_DOSSIER_AFFECT = $idDossier;
                        $affectation->save();
                    }

                    $dateCommDoss = $dbDateComm->find($this->getRequest()->getParam('ID_AFFECTATION_DOSSIER_VISITE'))->current();
                    $nouveauDossier->DATEVISITE_DOSSIER = $dateCommDoss->DATE_COMMISSION;
                    $nouveauDossier->save();
                } else {
                    $nouveauDossier->DATEVISITE_DOSSIER = null;
                    $nouveauDossier->save();
                    // Supprimer l'affectation si elle existe
                    if (isset($infosDateVisite)) {
                        $dateDelete = $dbDossierAffectation->find($infosDateVisite['ID_DATECOMMISSION_AFFECT'], $idDossier)->current();
                        $dateDelete->delete();
                    }
                }

                // Partie concernant la date de commission
                if (
                    $this->getRequest()->getParam('ID_AFFECTATION_DOSSIER_COMMISSION')
                    && '' != $this->getRequest()->getParam('ID_AFFECTATION_DOSSIER_COMMISSION')
                ) {
                    if (isset($infosDateSalle)) {
                        // la date de commission existe déjà on vérifie si elle a changé
                        if ($infosDateSalle['ID_DATECOMMISSION_AFFECT'] != $this->getRequest()->getParam('ID_AFFECTATION_DOSSIER_COMMISSION')) {
                            // Dans le cas ou la date commission est différente de celle passée en paramètre alors on la met à jour
                            $dateEdit = $dbDossierAffectation->find($infosDateSalle['ID_DATECOMMISSION_AFFECT'], $idDossier)->current();
                            $dateEdit->ID_DATECOMMISSION_AFFECT = $this->getRequest()->getParam('ID_AFFECTATION_DOSSIER_COMMISSION');
                            $dateEdit->HEURE_DEB_AFFECT = null;
                            $dateEdit->HEURE_FIN_AFFECT = null;
                            $dateEdit->NUM_DOSSIER = 0;
                            $dateEdit->save();
                        }
                    } else {
                        // la date de commission n'existe pas il faut donc la crééer.
                        $affectation = $dbDossierAffectation->createRow();
                        $affectation->ID_DATECOMMISSION_AFFECT = $this->getRequest()->getParam('ID_AFFECTATION_DOSSIER_COMMISSION');
                        $affectation->ID_DOSSIER_AFFECT = $idDossier;
                        $affectation->save();
                    }

                    $dateCommDoss = $dbDateComm->find($this->getRequest()->getParam('ID_AFFECTATION_DOSSIER_COMMISSION'))->current();
                    $nouveauDossier->DATECOMM_DOSSIER = $dateCommDoss->DATE_COMMISSION;
                    $nouveauDossier->save();
                } else {
                    $nouveauDossier->DATECOMM_DOSSIER = null;
                    $nouveauDossier->save();
                    // Supprimer l'affectation si elle existe
                    if (isset($infosDateSalle)) {
                        $dateDelete = $dbDossierAffectation->find($infosDateSalle['ID_DATECOMMISSION_AFFECT'], $idDossier)->current();
                        $dateDelete->delete();
                    }
                }
            }

            $naturesDonnantAvis = [7, 16, 17, 19, 21, 23, 24, 26, 28, 29, 47, 48];

            // On met le champ ID_DOSSIER_DONNANT_AVIS de établissement avec l'ID du dossier que l'on vient d'enregistrer dans les cas suivant
            if (
                $this->getRequest()->getParam('AVIS_DOSSIER_COMMISSION')
                && (1 == $this->getRequest()->getParam('AVIS_DOSSIER_COMMISSION') || self::ID_AVIS_DEFAVORABLE == $this->getRequest()->getParam('AVIS_DOSSIER_COMMISSION'))
                && $service_dossier->isDossierDonnantAvis($nouveauDossier, $idNature)
            ) {
                if (
                    'new' == $this->getRequest()->getParam('do')
                    && $this->getRequest()->getParam('idEtablissement')
                ) {
                    $listeEtab = [[
                        'ID_ETABLISSEMENT' => $this->getRequest()->getParam('idEtablissement'),
                    ]];
                } else {
                    $listeEtab = $DBetablissementDossier->getEtablissementListe($idDossier);
                }

                $cache = Zend_Controller_Front::getInstance()->getParam('bootstrap')->getResource('cache');
                $mygroupe = Zend_Auth::getInstance()->getIdentity()['group']['LIBELLE_GROUPE'];
                $service_ets = new Service_Etablissement();
                $arrayEtsAvis = [];

                if (
                    unserialize($cache->load('acl'))->isAllowed($mygroupe, 'alerte_email', 'alerte_avis')
                    && getenv('PREVARISC_MAIL_ENABLED')
                    && 1 == getenv('PREVARISC_MAIL_ENABLED')
                ) {
                    foreach ($listeEtab as $etab) {
                        $currentEts = $service_ets->get($etab['ID_ETABLISSEMENT']);
                        if ([] !== $currentEts) {
                            $arrayEtsAvis[$etab['ID_ETABLISSEMENT']]['avis'] = $currentEts['avis'];
                            $arrayEtsAvis[$etab['ID_ETABLISSEMENT']]['libelle'] = $currentEts['informations']['LIBELLE_ETABLISSEMENTINFORMATIONS'];
                        }
                    }
                }

                $dbEtab = new Model_DbTable_Etablissement();

                $updatedEtab = $service_dossier->saveDossierDonnantAvis($nouveauDossier, $listeEtab, $cache, $this->getRequest()->getParam('repercuterAvis'));
                if (
                    unserialize($cache->load('acl'))->isAllowed($mygroupe, 'alerte_email', 'alerte_avis')
                    && getenv('PREVARISC_MAIL_ENABLED')
                    && 1 == getenv('PREVARISC_MAIL_ENABLED')
                ) {
                    $service_alerte = new Service_Alerte();
                    foreach ($updatedEtab as $upEts) {
                        if (
                            $upEts['ID_DOSSIER_DONNANT_AVIS'] === $nouveauDossier['ID_DOSSIER']
                            && $nouveauDossier['AVIS_DOSSIER_COMMISSION'] !== $arrayEtsAvis[$upEts['ID_ETABLISSEMENT']]['avis']
                        ) {
                            $options = $service_alerte->getLink(2, $upEts['ID_ETABLISSEMENT']);
                            $this->_helper->flashMessenger(
                                [
                                    'context' => 'success',
                                    'title' => 'Mise à jour réussie !', 'message' => 'L\'établissement '.$arrayEtsAvis[$upEts['ID_ETABLISSEMENT']]['libelle'].' a bien été mis à jour.'.$options,
                                ]
                            );
                        }
                    }
                }

                // AVERTISSEMENT SUR L'OUVERTURE D'UN ETABLISSEMENT A EFFECTUER
                // Dans le cas d'une visite avant ouverture avec avis de commission positif
                if (
                    1 == $this->getRequest()->getParam('AVIS_DOSSIER_COMMISSION')
                    && in_array($idNature, [47, 48])
                ) {
                    foreach ($updatedEtab as $ue) {
                        $etabInformation = $dbEtab->getInformations($ue['ID_ETABLISSEMENT']);
                        // Si l'établissement est en statut projet, et uniquement ce cas
                        if (
                            $etabInformation
                            && 1 == $etabInformation->ID_STATUT
                        ) {
                            $this->_helper->flashMessenger([
                                'context' => 'warning',
                                'title' => 'Avertissement',
                                'message' => "La visite d'avant ouverture étant favorable, vous devriez passer le statut de l'établissement <a title='Ouvrir' href='/etablissement/edit/id/".$ue['ID_ETABLISSEMENT']."'>".$etabInformation['LIBELLE_ETABLISSEMENTINFORMATIONS']."</a> à 'ouvert' (statut actuellement à 'projet').",
                            ]);
                        }
                    }
                }
            }
            // On passe d'un dossier donnant avis à un dossier ne donnant pas avis (edit)
            elseif (
                $this->getRequest()->getParam('AVIS_DOSSIER_COMMISSION')
                && in_array($this->getRequest()->getParam('AVIS_DOSSIER_COMMISSION'), [1, 2])
                && !$service_dossier->isDossierDonnantAvis($nouveauDossier, $idNature)
                && 'edit' == $this->getRequest()->getParam('do')
                && in_array($oldNature, $naturesDonnantAvis)
            ) {
                $listeEtab = $DBetablissementDossier->getEtablissementListe($idDossier);

                $cache = Zend_Controller_Front::getInstance()->getParam('bootstrap')->getResource('cache');

                $dbDossier = new Model_DbTable_Dossier();
                // on récupère les infos du dernier dossier donnant avis de l'établissement courant
                foreach ($listeEtab as $etab) {
                    $dernierDossierDonnantAvis = $dbDossier->getGeneral($dbDossier->getDernierIdDossierDonnantAvis($etab['ID_ETABLISSEMENT'])['ID_DOSSIER']);
                    $service_dossier->saveDossierDonnantAvisCurrentEtab($dernierDossierDonnantAvis, $etab, $cache);
                }
            }

            // Clean du cache de la recherche pour rester à jour
            $cache = Zend_Controller_Front::getInstance()->getParam('bootstrap')->getResource('cacheSearch');
            $cache->clean(Zend_Cache::CLEANING_MODE_ALL);

            // on envoi l'id à la vue pour qu'elle puisse rediriger vers la bonne page
            $idArray = ['id' => $nouveauDossier->ID_DOSSIER];
            echo json_encode($idArray);
        } catch (Exception $exception) {
            $this->_helper->flashMessenger([
                'context' => 'error',
                'title' => 'Erreur lors de la sauvegarde du dossier',
                'message' => $exception->getMessage(),
            ]);
        }
    }

    // Autocomplétion pour selection ABREVIATION
    public function selectionabreviationAction(): void
    {
        if (isset($_GET['q'])) {
            $DBprescPrescType = new Model_DbTable_PrescriptionType();
            $this->view->assign('selectAbreviation', $DBprescPrescType->fetchAll("ABREVIATION_PRESCRIPTIONTYPE LIKE '%".$_GET['q']."%'")->toArray());
        }
    }

    // Autocomplétion pour selection ETABLISSEMENT
    public function selectionetabAction(): void
    {
        // Création de l'objet recherche
        $search = new Model_DbTable_Search();

        // On set le type de recherche
        $search->setItem('etablissement');
        $search->limit(5);

        if (array_key_exists('ID_GENRE', $_GET)) {
            $search->setCriteria('genre.ID_GENRE', $this->getRequest()->getParam('ID_GENRE') + 1);
        }

        // On recherche avec le libellé
        $search->setCriteria('LIBELLE_ETABLISSEMENTINFORMATIONS', $this->getRequest()->getParam('q'), false);

        // On balance le résultat sur la vue
        $this->view->assign('selectEtab', $search->run()->getAdapter()->getItems(0, 99999999999)->toArray());

        $service_etablissement = new Service_Etablissement();
        foreach ($this->view->selectEtab as $etab => $val) {
            $etablissementInfos = $service_etablissement->get($val['ID_ETABLISSEMENT']);

            $this->view->selectEtab[$etab]['libelleParent'] = '';
            if (isset($etablissementInfos['parents'][0]['LIBELLE_ETABLISSEMENTINFORMATIONS'])) {
                $this->view->selectEtab[$etab]['libelleParent'] = $etablissementInfos['parents'][0]['LIBELLE_ETABLISSEMENTINFORMATIONS'];
            }
        }
    }

    // Action permettant de lister les établissements et les dossiers liés
    public function lieesAction(): void
    {
        $idDossier = (int) $this->getRequest()->getParam('id');
        $this->view->assign('id_dossier', $idDossier);

        $DBdossier = new Model_DbTable_Dossier();
        $dbDossierLie = new Model_DbTable_DossierLie();

        // Enregistrement des dossiers si necessaire
        if ($this->getRequest()->isPost()) {
            try {
                $post = $this->getRequest()->getPost();
                if ('saveDossLink' == $post['do']) {
                    foreach ($post['idDossierLie'] as $idDossLink) {
                        $newLink = $dbDossierLie->createRow();
                        $newLink->ID_DOSSIER1 = $idDossier;
                        $newLink->ID_DOSSIER2 = $idDossLink;
                        $newLink->save();
                    }
                }
            } catch (Exception $e) {
                $this->_helper->flashMessenger(['context' => 'error', 'title' => "Erreur lors de l'enregistrement.", 'message' => 'Une erreur s\'est produite lors de l\enregistrement de la prescription ('.$e->getMessage().')']);
            }
        }

        $this->view->assign('infosDossier', $DBdossier->find($idDossier)->current());
        $this->view->assign('listeEtablissement', $DBdossier->getEtablissementDossier((int) $this->getRequest()->getParam('id')));

        $service_dossier = new Service_Dossier();
        if ($this->idDossier) {
            $this->view->assign('enteteEtab', $service_dossier->getEtabInfos($this->idDossier));
        }

        $service_etablissement = new Service_Etablissement();
        foreach ($this->view->listeEtablissement as $etab => $val) {
            $this->view->listeEtablissement[$etab]['pereInfos'] = $service_etablissement->get($val['ID_ETABLISSEMENT']);
        }

        $listeDossierLies = $dbDossierLie->getDossierLie($idDossier);

        $dossierToShow = null;
        foreach ($listeDossierLies as $numrez => $attr) {
            // on parcour chacun dossiers liers pour en récupérer les informations à afficher
            if ($idDossier == $attr['ID_DOSSIER1']) {
                $dossierToShow = $attr['ID_DOSSIER2'];
            } elseif ($idDossier == $attr['ID_DOSSIER2']) {
                $dossierToShow = $attr['ID_DOSSIER1'];
            }

            $listeDossierLies[$numrez]['etabInfo'] = $service_dossier->getEtabInfos($dossierToShow);
            $listeDossierLies[$numrez]['dossierInfo'] = $DBdossier->getDossierTypeNature($dossierToShow);
        }

        $this->view->assign('listeDossierLies', $listeDossierLies);
    }

    public function lieesDossAction(): void
    {
        $service_dossier = new Service_Dossier();
        $service_etablissement = new Service_Etablissement();

        $dbEtablissement = new Model_DbTable_Etablissement();
        $dbEtablissementDossier = new Model_DbTable_EtablissementDossier();

        $idDossier = (int) $this->getRequest()->getParam('id');
        if ($this->idDossier) {
            $this->view->assign('enteteEtab', $service_dossier->getEtabInfos($this->idDossier));
        }

        $listeEtablissementTest = $dbEtablissementDossier->getEtablissementListe($idDossier);

        // On place dans un tableau chacun des idEtablissement liés au dossier
        $listeEtab = [];
        foreach ($listeEtablissementTest as $etab) {
            $listeEtab[] = $etab['ID_ETABLISSEMENT'];
        }

        // Pour chacun des établissement on va récuperer les dossiers concernés
        $listeDossierEtab = [];

        foreach ($listeEtab as $val) {
            $etabInfo = $dbEtablissement->getInformations($val);

            $listeDossierEtab[$val]['LIBELLE_ETABLISSEMENT'] = $etabInfo['LIBELLE_ETABLISSEMENTINFORMATIONS'];
            $listeDossierEtab[$val]['dossiers'] = $service_etablissement->getDossiers($val);
        }

        $this->view->assign('idDossier', $idDossier);
        $this->view->assign('listeDossierEtab', $listeDossierEtab);
        $this->view->assign('listeEtab', $listeEtab);

        $dbDossierLie = new Model_DbTable_DossierLie();
        $this->listeDossierLies = $dbDossierLie->getDossierLie($idDossier);

        $dejaLies = [];
        foreach ($this->listeDossierLies as $attr) {
            // on parcour chacun dossiers liers pour en récupérer les informations à afficher
            if ($idDossier == $attr['ID_DOSSIER1']) {
                $dejaLies[] = $attr['ID_DOSSIER2'];
            } elseif ($this->getRequest()->getParam('idDossier') == $attr['ID_DOSSIER2']) {
                $dejaLies[] = $attr['ID_DOSSIER1'];
            }
        }

        $this->view->assign('dejaLies', $dejaLies);
    }

    public function contactAction(): void
    {
        $this->view->assign('idDossier', (int) $this->getRequest()->getParam('id'));
        $service_dossier = new Service_Dossier();
        if ($this->idDossier) {
            $this->view->assign('enteteEtab', $service_dossier->getEtabInfos($this->idDossier));
        }

        $DBdossier = new Model_DbTable_Dossier();
        $this->view->assign('infosDossier', $DBdossier->find((int) $this->getRequest()->getParam('id'))->current());
    }

    // GESTION DOCUMENTS CONSULTES
    public function docconsulteAction(): void
    {
        $this->view->inlineScript()->appendFile('/js/dossier/dossierDocConsulte.js', 'text/javascript');

        // récupération du type de dossier (etude / visite)
        $service_dossier = new Service_Dossier();
        if ($this->idDossier) {
            $this->view->assign('enteteEtab', $service_dossier->getEtabInfos($this->idDossier));
        }

        $dbdossier = new Model_DbTable_Dossier();
        $this->view->assign('infosDossier', $dbdossier->find((int) $this->getRequest()->getParam('id'))->current());

        $dossierType = $dbdossier->getTypeDossier((int) $this->getRequest()->getParam('id'));

        $this->view->assign('idDossier', (int) $this->getRequest()->getParam('id'));

        // récupération de toutes les natures
        $DBdossierNature = new Model_DbTable_DossierNature();
        $this->view->assign('listeNatures', $DBdossierNature->getDossierNaturesLibelle((int) $this->getRequest()->getParam('id')));

        // suivant le type on récup la liste des docs que l'on met dans un tableau a multi dimension.
        // l'index de chaque liste sera l'id de la nature
        $dblistedoc = new Model_DbTable_DossierListeDoc();
        $dblistedocAjout = new Model_DbTable_ListeDocAjout();

        $listeDocAjout = null;
        $listeDocConsulte = null;
        $listeDocRenseigne = null;
        foreach ($this->view->listeNatures as $nature) {
            if (in_array($dossierType['TYPE_DOSSIER'], [2, 3])) {
                if (in_array($nature['ID_NATURE'], [20, 25])) {
                    // cas d'un groupe de visite d'une récption de travaux
                    $listeDocConsulte[$nature['ID_NATURE']] = $dblistedoc->getDocVisiteRT();
                } elseif (in_array($nature['ID_NATURE'], [47, 48])) {
                    // cas d'une VAO
                    $listeDocConsulte[$nature['ID_NATURE']] = $dblistedoc->getDocVisiteVAO();
                } else {
                    // cas général d'une visite
                    $listeDocConsulte[$nature['ID_NATURE']] = $dblistedoc->getDocVisite();
                }
            } elseif (1 == $dossierType['TYPE_DOSSIER']) {
                // cas d'une etude
                if (in_array($nature['ID_NATURE'], [7, 19])) {
                    $listeDocConsulte[$nature['ID_NATURE']] = $dblistedoc->getDocVisite();
                } else {
                    $listeDocConsulte[$nature['ID_NATURE']] = $dblistedoc->getDocEtude();
                }
            } else {
                $listeDocConsulte = 0;
            }

            // ici on récupère tous les documents qui ont été renseigné dans la base par un utilisateur (avec id du dossier et de la nature)
            $listeDocRenseigne[$nature['ID_NATURE']] = $dblistedoc->recupDocDossier($this->getRequest()->getParam('id'));

            // ici on récupère tous les documents qui ont été ajoutés par l'utilisateur (document non proposé par défaut)
            $listeDocAjout[$nature['ID_NATURE']] = $dblistedocAjout->getDocAjout((int) $this->getRequest()->getParam('id'));
        }

        // On envoie à la vue la liste des documents consultés classés par nature (peux y avoir plusieurs fois la même liste)
        $this->view->assign('listeDocs', $listeDocConsulte);
        // on envoie à la vue tous les documents qui ont été renseignés parmi la liste de ceux récupéré dans la boucle ci-dessus
        $this->view->assign('dossierDocConsutle', $listeDocRenseigne);
        // on recup les docs ajouté pr le dossiers
        $this->view->assign('listeDocsAjout', $listeDocAjout);
    }

    public function ajoutdocAction($idDossier): void
    {
        try {
            $dblistedocajout = new Model_DbTable_ListeDocAjout();

            // insertion dans la base de données du nouveau type de document
            $newDoc = $dblistedocajout->createRow();
            $newDoc->LIBELLE_DOCAJOUT = $this->getRequest()->getParam('libelleNewDoc');
            $newDoc->ID_DOSSIER = $this->getRequest()->getParam('idDossier');
            $newDoc->ID_NATURE = $this->getRequest()->getParam('natureDocAjout');
            $newDoc->save();

            $this->view->assign('idNatureNewDoc', $this->getRequest()->getParam('natureDocAjout'));
            $this->view->assign('idNewDoc', $newDoc->ID_DOCAJOUT);
            $this->view->assign('libelleNewDoc', $newDoc->LIBELLE_DOCAJOUT);

            $this->render('ajoutdoc');

            $this->_helper->flashMessenger([
                'context' => 'success',
                'title' => 'Le document a bien été ajouté',
                'message' => '',
            ]);
        } catch (Exception $exception) {
            $this->_helper->flashMessenger([
                'context' => 'error',
                'title' => "Erreur lors de l'ajout du document",
                'message' => $exception->getMessage(),
            ]);
        }
    }

    public function validdocAction(): ?bool
    {
        try {
            $this->_helper->viewRenderer->setNoRender();
            $idDossier = (int) $this->getRequest()->getParam('id');
            $idValid = $this->getRequest()->getParam('idValid');
            $datePost = $this->_getParam('date_'.$idValid);
            if (0 == $idDossier) {
                return false;
            }

            if ('' == $idValid) {
                return false;
            }

            if ('' != $datePost) {
                $dateTab = explode('/', $datePost);
                $date = $dateTab[2].'-'.$dateTab[1].'-'.$dateTab[0];
            } else {
                $date = '0000-00-00';
            }

            $ref = str_replace('"', "''", $_POST['ref_'.$idValid]);
            $libelle = $_POST['libelle_'.$idValid] ?? '';

            // on définit s'il sagid d'un doc ajouté ou nom
            $tabNom = explode('_', $idValid);
            $expectedCountIfNotAdded = 2;

            if ($expectedCountIfNotAdded === count($tabNom)) {
                $dblistedoc = new Model_DbTable_DossierDocConsulte();
                $listevalid = $dblistedoc->getGeneral($idDossier, $tabNom[1]);
                if ($listevalid) {
                    // si UN enregistrement existe
                    $liste = $dblistedoc->find($listevalid['ID_DOSSIERDOCCONSULTE'])->current();
                    $liste->REF_CONSULTE = $ref;
                    $liste->DATE_CONSULTE = $date;
                } else {
                    // si AUCUN enregistrement existe
                    $liste = $dblistedoc->createRow();
                    $liste->ID_DOC = $tabNom[1];
                    $liste->ID_DOSSIER = $idDossier;
                    $liste->ID_NATURE = $tabNom[0];
                    $liste->REF_CONSULTE = $ref;
                    $liste->DATE_CONSULTE = $date;
                    $liste->DOC_CONSULTE = 1;
                }

                $liste->save();
            } else {
                // On commence par isoler l'id de "_aj"
                $idDocAjout = explode('_', $this->getRequest()->getParam('idValid'));
                $dblistedocajout = new Model_DbTable_ListeDocAjout();

                $docAjout = $dblistedocajout->find($idDocAjout[1])->current();

                $docAjout->LIBELLE_DOCAJOUT = $libelle;
                $docAjout->REF_DOCAJOUT = $ref;
                $docAjout->DATE_DOCAJOUT = $date;
                $docAjout->ID_DOSSIER = $idDossier;

                $docAjout->save();
            }
        } catch (Exception $exception) {
        }

        return null;
    }

    public function suppdocAction(): void
    {
        $this->_helper->viewRenderer->setNoRender();
        // cas de la suppression d'un document qui avait été renseigné
        $tabInfos = explode('_', $this->getRequest()->getParam('docInfos'));
        $numdoc = $tabInfos[1];

        $expectedCountIfNotAdded = 2;
        $expectedCountIfAdded = 3;
        if ($expectedCountIfNotAdded === count($tabInfos)) {
            // cas d'un document existant
            $dbToUse = new Model_DbTable_DossierDocConsulte();
            $searchResult = $dbToUse->getGeneral($this->getRequest()->getParam('idDossier'), $numdoc);
            $docDelete = $dbToUse->find($searchResult['ID_DOSSIERDOCCONSULTE'])->current();
            $docDelete->delete();
        } elseif ($expectedCountIfAdded === count($tabInfos)) {
            // cas d'un document ajouté
            $dbToUse = new Model_DbTable_ListeDocAjout();
            $searchResult = $dbToUse->find($numdoc)->current();
            $searchResult->delete();
        }
    }

    // GESTION LIAISON ETABLISSMENTS
    public function addetablissementAction(): void
    {
        try {
            $DBetablissementDossier = new Model_DbTable_EtablissementDossier();
            $newEtabDossier = $DBetablissementDossier->createRow();
            $newEtabDossier->ID_ETABLISSEMENT = $this->getRequest()->getParam('idSelect');
            $newEtabDossier->ID_DOSSIER = $this->getRequest()->getParam('idDossier');
            $newEtabDossier->save();

            // on répercute l'avis du dossier sur l'établissement
            // par exemple dans le cas des dossiers de levée d'avis défavorable
            // qui impactent plusieurs établissement
            $service_dossier = new Service_Dossier();
            $DB_dossier = new Model_DbTable_Dossier();
            $cache = Zend_Controller_Front::getInstance()->getParam('bootstrap')->getResource('cache');

            $dossier = $DB_dossier->find($this->getRequest()->getParam('idDossier'))->current();
            $idNature = $DB_dossier->getNatureDossier($this->getRequest()->getParam('idDossier'));
            $idNature = $idNature['ID_NATURE'] ?? 0;

            if ($service_dossier->isDossierDonnantAvis($dossier, $idNature)) {
                $service_dossier->saveDossierDonnantAvis(
                    $dossier,
                    [
                        [
                            'ID_ETABLISSEMENT' => $this->getRequest()->getParam('idSelect'),
                        ],
                    ],
                    $cache
                );
            }

            $this->view->assign('libelleEtab', $this->getRequest()->getParam('libelleSelect'));
            $this->view->assign('infosEtab', $newEtabDossier);
            $this->_helper->flashMessenger([
                'context' => 'success',
                'title' => 'L\'établissement a bien été ajouté',
                'message' => '',
            ]);
        } catch (Exception $exception) {
            $this->_helper->flashMessenger([
                'context' => 'error',
                'title' => 'Erreur lors de l\'ajout de l\'établissement',
                'message' => $exception->getMessage(),
            ]);
        }
    }

    public function deleteetablissementAction(): void
    {
        try {
            $this->_helper->viewRenderer->setNoRender();

            $DBetablissementDossier = new Model_DbTable_EtablissementDossier();
            $dbEtab = new Model_DbTable_Etablissement();
            $service_etablissement = new Service_Etablissement();
            $cache = Zend_Controller_Front::getInstance()->getParam('bootstrap')->getResource('cache');

            $deleteEtabDossier = $DBetablissementDossier->find($this->getRequest()->getParam('idEtabDossier'))->current();
            if (!$deleteEtabDossier) {
                $this->_helper->flashMessenger([
                    'context' => 'warning',
                    'title' => "L'établissement n'est pas lié à ce dossier.",
                    'message' => '',
                ]);

                return;
            }

            $idEtablissement = $deleteEtabDossier['ID_ETABLISSEMENT'];
            $idDossier = $deleteEtabDossier['ID_DOSSIER'];
            $etablissement = $dbEtab->find($idEtablissement)->current();
            $deleteEtabDossier->delete();

            $this->_helper->flashMessenger([
                'context' => 'success',
                'title' => "L'établissement n'est plus lié à ce dossier.",
                'message' => '',
            ]);

            if ($etablissement->ID_DOSSIER_DONNANT_AVIS == $idDossier) {
                $newDossier = $service_etablissement->getDossierDonnantAvis($idEtablissement);

                $etablissement->ID_DOSSIER_DONNANT_AVIS = null;
                if (
                    $newDossier
                    && isset($newDossier['ID_DOSSIER'])
                ) {
                    $etablissement->ID_DOSSIER_DONNANT_AVIS = $newDossier['ID_DOSSIER'];
                }

                $etablissement->save();
                $cache->remove(sprintf('etablissement_id_%d', $idEtablissement));
                Zend_Controller_Front::getInstance()->getParam('bootstrap')->getResource('cacheSearch')->clean(Zend_Cache::CLEANING_MODE_ALL);

                $this->_helper->flashMessenger([
                    'context' => 'warning',
                    'title' => "Attention, ce dossier donnait avis à l'établissement.",
                    'message' => $etablissement->ID_DOSSIER_DONNANT_AVIS ? 'Un nouveau dossier donne à présent avis.' : "L'établissement n'a plus de dossier donnant avis.",
                ]);
            }
        } catch (Exception $exception) {
            $this->_helper->flashMessenger([
                'context' => 'error',
                'title' => "Erreur lors de la suppression du lien à l'établissement.",
                'message' => $exception->getMessage(),
            ]);
        }
    }

    public function deleteliendossierAction(): void
    {
        try {
            // action appelée lorsque l'on supprime un lien avec un autre dossier
            $this->_helper->viewRenderer->setNoRender();

            $DBetablissementDossier = new Model_DbTable_DossierLie();
            $deleteEtabDossier = $DBetablissementDossier->find($this->getRequest()->getParam('idLienDossier'))->current();
            $deleteEtabDossier->delete();

            $this->_helper->flashMessenger([
                'context' => 'success',
                'title' => 'Le lien avec le dossier a bien été supprimé',
                'message' => '',
            ]);
        } catch (Exception $exception) {
            $this->_helper->flashMessenger([
                'context' => 'error',
                'title' => 'Erreur lors de la suppression du lien avec ledossier',
                'message' => $exception->getMessage(),
            ]);
        }
    }

    public function dialogcommshowAction(): void
    {
        $dbDateComm = new Model_DbTable_DateCommission();
        $infosDateComm = $dbDateComm->find($this->getRequest()->getParam('idDateComm'))->current();
        $this->view->assign('infosDateComm', $infosDateComm);

        $date = new Zend_Date($infosDateComm['DATE_COMMISSION'], Zend_Date::DATES);
        $this->view->assign('dateSelect', $date->get(Zend_Date::WEEKDAY.' '.Zend_Date::DAY_SHORT.' '.Zend_Date::MONTH_NAME.' '.Zend_Date::YEAR));
    }

    public function affectationodjAction(): void
    {
        $this->_helper->viewRenderer->setNoRender();
    }

    public function descriptifsAction(): void
    {
        $idDossier = (int) $this->getRequest()->getParam('id');
        $DBdossier = new Model_DbTable_Dossier();

        $this->view->assign('infosDossier', $DBdossier->find($idDossier)->current());
    }

    // GENERATION DOCUMENTS
    public function rapportAction(): void
    {
        $service_commission = new Service_Commission();
        $service_dossier = new Service_Dossier();

        // si on génére un document
        if ($this->getRequest()->isPost()) {
            $idDossier = $this->getRequest()->getParam('idDossier');
            $commission = $this->getRequest()->getParam('commission');
            foreach ($this->getRequest()->getParam('idEtab') as $etablissementId) {
                $this->creationdocAction($idDossier, $etablissementId, $commission);
            }
        }

        $idDossier = (int) $this->getRequest()->getParam('id');

        // informations sur le verrouillage
        $DBdossier = new Model_DbTable_Dossier();
        $dossierInfos = $DBdossier->find($idDossier)->current();
        $this->view->assign('locked', $dossierInfos['VERROU_DOSSIER']);

        $this->view->assign('id_dossier', $idDossier);

        if (0 !== $idDossier) {
            $this->view->assign('enteteEtab', $service_dossier->getEtabInfos($idDossier));

            $pathBase = REAL_DATA_PATH.DS.'uploads'.DS.'documents';

            // Récupération des documents présents dans le dossier 0. Documents visibles après vérrouillage
            $pathVer = $pathBase.DS.'0';
            ($dirVer = opendir($pathVer)) || exit('Erreur de listage : le répertoire n\'existe pas');
            $fichierVer = [];
            $dossierVer = [];
            while ($elementVer = readdir($dirVer)) {
                if (
                    '.' !== $elementVer
                    && '..' !== $elementVer
                    && '.gitignore' !== $elementVer
                ) {
                    if (!is_dir($pathVer.DS.$elementVer)) {
                        $fichierVer[] = $elementVer;
                    } else {
                        $dossierVer[] = $elementVer;
                    }
                }
            }

            closedir($dirVer);
            sort($fichierVer);

            $this->view->assign('fichierVer', $fichierVer);

            $this->view->assign('infosCommission', $service_dossier->getCommission($idDossier));
            // liste des commissions pour le select
            $liste_commission = $service_commission->getAll();

            foreach ($liste_commission as $var => $commission) {
                $path = $pathBase.DS.$commission['ID_COMMISSION'];
                ($dir = opendir($path)) || exit('Erreur de listage : le répertoire n\'existe pas'); // on ouvre le contenu du dossier courant
                $fichier = []; // on déclare le tableau contenant le nom des fichiers
                $dossier = []; // on déclare le tableau contenant le nom des dossiers

                while ($element = readdir($dir)) {
                    if (
                        '.' !== $element
                        && '..' !== $element
                        && '.gitignore' !== $element
                    ) {
                        if (!is_dir($path.DS.$element)) {
                            $fichier[] = $element;
                        } else {
                            $dossier[] = $element;
                        }
                    }
                }

                closedir($dir);
                sort($fichier);

                $liste_commission[$var]['listeFichier'] = $fichier;
            }

            $this->view->assign('pathBase', $pathBase);
            $this->view->assign('path', DATA_PATH.'/uploads/documents');

            $this->view->assign('liste_commission', $liste_commission);

            $DBdossier = new Model_DbTable_Dossier();
            $this->view->assign('listeEtablissement', $DBdossier->getEtablissementDossier($idDossier));

            $this->view->assign('idTypeActivitePrinc', $this->view->enteteEtab[0]['infosEtab']['informations']['ID_TYPEACTIVITE']);
        }
    }

    public function generationrapportAction(): void
    {
        $this->_helper->viewRenderer->setNoRender();

        $idDossier = $this->getRequest()->getParam('idDossier');
        $idCommission = $this->getRequest()->getParam('idCommission');

        foreach ($this->getRequest()->getParam('idEtab') as $etablissementId) {
            $this->creationdocAction($idDossier, $etablissementId, $idCommission);
        }
    }

    /**
     * @param int   $idEtab
     * @param mixed $idDossier
     * @param mixed $commission
     */
    public function creationdocAction($idDossier, $idEtab, $commission): void
    {
        $this->view->assign('idDossier', $idDossier);
        $this->view->assign('idCommission', $commission);

        $this->view->assign('fichierSelect', $this->getRequest()->getParam('file'));

        $dateDuJour = new Zend_Date();
        $this->view->assign('dateDuJour', $dateDuJour->get(Zend_Date::DAY.'/'.Zend_Date::MONTH.'/'.Zend_Date::YEAR));

        // RECUPERATIONS DES INFORMATIONS SUR L'ETABLISSEMENT
        $service_etablissement = new Service_Etablissement();
        $this->view->assign('etablissementInfos', $service_etablissement->get($idEtab));

        $model_etablissement = new Model_DbTable_Etablissement();
        $etablissement = $model_etablissement->find($idEtab)->current();
        $this->view->assign('etabDesc', $etablissement);

        $this->view->assign('numWinPrev', $etablissement['NUMEROID_ETABLISSEMENT']);
        $this->view->assign('numTelEtab', $etablissement['TELEPHONE_ETABLISSEMENT']);
        $this->view->assign('numFaxEtab', $etablissement['FAX_ETABLISSEMENT']);
        $this->view->assign('mailEtab', $etablissement['COURRIEL_ETABLISSEMENT']);

        // Informations de l'établissement (catégorie, effectifs, activité / type principal)
        $object_informations = $model_etablissement->getInformations($idEtab);
        $this->view->assign('entite', $object_informations);

        $this->view->assign('numPublic', $object_informations['EFFECTIFPUBLIC_ETABLISSEMENTINFORMATIONS']);
        $this->view->assign('numPersonnel', $object_informations['EFFECTIFPERSONNEL_ETABLISSEMENTINFORMATIONS']);
        $this->view->assign('numHeberge', $object_informations['EFFECTIFHEBERGE_ETABLISSEMENTINFORMATIONS']);
        $this->view->assign('numTotal', $object_informations['EFFECTIFPUBLIC_ETABLISSEMENTINFORMATIONS'] + $object_informations['EFFECTIFPERSONNEL_ETABLISSEMENTINFORMATIONS']);

        $this->view->assign('etablissementLibelle', $object_informations['LIBELLE_ETABLISSEMENTINFORMATIONS']);

        $model_typeactivite = new Model_DbTable_TypeActivite();
        $dbType = new Model_DbTable_Type();

        $lettreType = $dbType->find($object_informations['ID_TYPE'])->current();
        $this->view->assign('typeLettreP', $lettreType['LIBELLE_TYPE']);

        $activitePrincipale = $model_typeactivite->find($object_informations['ID_TYPEACTIVITE'])->current();
        $this->view->assign('libelleActiviteP', $activitePrincipale['LIBELLE_ACTIVITE']);

        // Types / activités secondaires
        $model_typesactivitessecondaire = new Model_DbTable_EtablissementInformationsTypesActivitesSecondaires();
        $array_types_activites_secondaires = $model_typesactivitessecondaire->fetchAll('ID_ETABLISSEMENTINFORMATIONS = '.$object_informations->ID_ETABLISSEMENTINFORMATIONS)->toArray();

        $idGenreEtab = $object_informations['ID_GENRE'];
        $dbGenre = new Model_DbTable_Genre();
        $infosGenre = $dbGenre->find($idGenreEtab)->current();
        $this->view->assign('genreEtab', $infosGenre['LIBELLE_GENRE']);

        $typeS = '';
        $actS = '';

        foreach ($array_types_activites_secondaires as $var) {
            $lettreTypeS = $dbType->find($var['ID_TYPE_SECONDAIRE'])->current();
            $typeS .= $lettreTypeS['LIBELLE_TYPE'].', ';
            $activiteSearchLibelle = $model_typeactivite->find($var['ID_TYPEACTIVITE_SECONDAIRE'])->current();
            $actS .= $activiteSearchLibelle['LIBELLE_ACTIVITE'].', ';
        }

        $this->view->assign('activiteSecondaire', substr($actS, 0, -2));
        $this->view->assign('typeSecondaire', substr($typeS, 0, -2));

        // En fonction du genre on récupère les informations de l'établissement ou du site
        if (self::ID_GENRE_ETABLISSEMENT == $object_informations['ID_GENRE']) {
            // cas d'un établissement
            $this->view->assign('GN', 2);
        } elseif (self::ID_GENRE_CELLULE == $object_informations['ID_GENRE']) {
            // cas d'une céllule
            $this->view->assign('GN', 3);
        }

        $dbEtabLie = new Model_DbTable_EtablissementLie();
        $etabLie = $dbEtabLie->recupEtabCellule($object_informations['ID_ETABLISSEMENT']);
        if (null != $etabLie) {
            $idPere = $etabLie[0]['ID_ETABLISSEMENT'];
            $this->view->assign('infoPere', $model_etablissement->getInformations($idPere));
            $lettreType = $dbType->find($this->view->infoPere['ID_TYPE'])->current();
            $this->view->assign('typeLettrePPere', $lettreType['LIBELLE_TYPE']);
            $activitePrincipale = $model_typeactivite->find($this->view->infoPere['ID_TYPEACTIVITE'])->current();
            $this->view->assign('libelleActivitePPere', $activitePrincipale['LIBELLE_ACTIVITE']);
            $this->view->assign('categorieEtabPere', $this->view->infoPere['ID_CATEGORIE']);
            // Récuperation du genre du pere
            $idGenrePere = $this->view->infoPere['ID_GENRE'];
            $infosGenrePere = $dbGenre->find($idGenrePere)->current();
            $this->view->assign('genrePere', $infosGenrePere['LIBELLE_GENRE']);
        }

        // Catégorie
        if (
            self::ID_GENRE_CELLULE == $object_informations['ID_GENRE']
            && $this->view->infoPere
        ) {
            $object_informations['ID_CATEGORIE'] = $this->view->infoPere['ID_CATEGORIE'];
        }

        $dbCategorie = new Model_DbTable_Categorie();
        if ($object_informations['ID_CATEGORIE']) {
            $categorie = $dbCategorie->getCategories($object_informations['ID_CATEGORIE']);
            $categorie = explode(' ', $categorie['LIBELLE_CATEGORIE']);
            $this->view->assign('categorieEtab', $categorie[0]);
        }

        // Adresses
        $model_adresse = new Model_DbTable_EtablissementAdresse();
        $array_adresses = $model_adresse->get($idEtab);
        $service_adresse = new Service_Adresse();

        if (!empty($array_adresses)) {
            $this->view->assign('communeEtab', $array_adresses[0]['LIBELLE_COMMUNE']);
            $adresse = '';
            if (0 != $array_adresses[0]['NUMERO_ADRESSE']) {
                $adresse = $array_adresses[0]['NUMERO_ADRESSE'].' ';
            }

            if ('' != $array_adresses[0]['LIBELLE_RUE']) {
                $adresse .= $array_adresses[0]['LIBELLE_RUE'].' ';
            }

            if ('' != $array_adresses[0]['CODEPOSTAL_COMMUNE']) {
                $adresse .= $array_adresses[0]['CODEPOSTAL_COMMUNE'].' ';
            }

            if ('' != $array_adresses[0]['LIBELLE_COMMUNE']) {
                $adresse .= strtoupper($array_adresses[0]['LIBELLE_COMMUNE']).' ';
            }

            $this->view->assign('maire', $service_adresse->getMaire($array_adresses[0]['NUMINSEE_COMMUNE']));
            $this->view->assign('etablissementAdresse', $adresse);
        }

        // RECUPERATIONS DES INFORMATIONS SUR LE DOSSIER
        // Récupération des documents d'urbanisme
        $DBdossierDocUrba = new Model_DbTable_DossierDocUrba();
        $dossierDocUrba = $DBdossierDocUrba->getDossierDocUrba($idDossier);
        $listeDocUrba = '';
        foreach ($dossierDocUrba as $var) {
            $listeDocUrba .= $var['NUM_DOCURBA'].', ';
        }

        $this->view->assign('listeDocUrba', substr($listeDocUrba, 0, -2));

        // Récupération de tous les champs de la table dossier
        $DBdossier = new Model_DbTable_Dossier();
        $this->view->assign('infosDossier', $DBdossier->find($idDossier)->current());

        // Avis & Dérogations
        $this->view->assign('avisDerogations', $DBdossier->getListAvisDerogationsFromDossier($idDossier));
        $this->view->assign('avisDerogationsEtablissement', $model_etablissement->getListAvisDerogationsEtablissement($idEtab));

        // Récupération du type et de la nature du dossier
        $dbType = new Model_DbTable_DossierType();
        $typeDossier = $dbType->find($this->view->infosDossier['TYPE_DOSSIER'])->current();
        $this->view->assign('typeDossier', $typeDossier['LIBELLE_DOSSIERTYPE']);

        $dbNature = new Model_DbTable_DossierNature();
        $natureDossier = $dbNature->getDossierNatureLibelle($idDossier);
        $this->view->assign('natureDossier', $natureDossier['LIBELLE_DOSSIERNATURE']);

        // On récupère les informations du préventionniste
        $DBdossierPrev = new Model_DbTable_DossierPreventionniste();
        $this->view->assign('preventionnistes', $DBdossierPrev->getPrevDossier($idDossier));

        $dbGroupement = new Model_DbTable_Groupement();
        $servInstructeur = '';
        $servInstructeurPrenomContact = '';
        $servInstructeurNomContact = '';
        $servInstructeurMail = '';

        if (
            $this->view->infosDossier['SERVICEINSTRUC_DOSSIER']
            && $this->view->infosDossier['TYPESERVINSTRUC_DOSSIER']
        ) {
            if ('servInstCommune' == $this->view->infosDossier['TYPESERVINSTRUC_DOSSIER']) {
                $dbCommune = new Model_DbTable_AdresseCommune();
                $commune = $dbCommune->get($this->view->infosDossier['SERVICEINSTRUC_DOSSIER']);
                if (isset($commune[0])) {
                    $idUtilisateur = $commune[0]['ID_UTILISATEURINFORMATIONS'];
                    $dbUtilisateur = new Model_DbTable_UtilisateurInformations();
                    $infos = $dbUtilisateur->find($idUtilisateur)->current();
                    $this->view->assign('servInstructeur', $infos);
                    $servInstructeur = $this->view->infosDossier['SERVICEINSTRUC_DOSSIER'];
                    $servInstructeurPrenomContact = $infos['PRENOM_UTILISATEURINFORMATIONS'];
                    $servInstructeurNomContact = $infos['NOM_UTILISATEURINFORMATIONS'];
                    $servInstructeurMail = $infos['MAIL_UTILISATEURINFORMATIONS'];
                }
            } else {
                $libelle = $this->view->infosDossier['SERVICEINSTRUC_DOSSIER'];
                $groupement = $dbGroupement->getByLibelle($libelle);
                if (isset($groupement[0])) {
                    $servInstructeur = $groupement[0]['LIBELLE_GROUPEMENT'];
                    $servInstructeurPrenomContact = $groupement[0]['PRENOM_UTILISATEURINFORMATIONS'];
                    $servInstructeurNomContact = $groupement[0]['NOM_UTILISATEURINFORMATIONS'];
                    $servInstructeurMail = $groupement[0]['MAIL_UTILISATEURINFORMATIONS'];
                }
            }
        }

        $this->view->assign('servInstructeur', $servInstructeur);
        $this->view->assign('servInstructeurPrenomContact', $servInstructeurPrenomContact);
        $this->view->assign('servInstructeurNomContact', $servInstructeurNomContact);
        $this->view->assign('servInstructeurMail', $servInstructeurMail);

        $serviceDossier = new Service_Dossier();
        $this->view->assign([
            'maitreOeuvre' => $serviceDossier->getContactInfo($idDossier, $idEtab, 4),
            'maitreOuvrage' => $serviceDossier->getContactInfo($idDossier, $idEtab, 3),
            'dusDossier' => $serviceDossier->getContactInfo($idDossier, $idEtab, 8),
            'exploitantDossier' => $serviceDossier->getContactInfo($idDossier, $idEtab, 7),
            'respsecuDossier' => $serviceDossier->getContactInfo($idDossier, $idEtab, 9),
            'proprioInfos' => $serviceDossier->getContactInfo($idDossier, $idEtab, 17),
            'petitionnaireDemandeur' => $serviceDossier->getContactInfo($idDossier, $idEtab, 5),
            'controllerTechnique' => $serviceDossier->getContactInfo($idDossier, $idEtab, 6),
            'participant' => $serviceDossier->getContactInfo($idDossier, $idEtab, 10),
            'demandeur' => $serviceDossier->getContactInfo($idDossier, $idEtab, 11),
            'prefetInfos' => $serviceDossier->getContactInfo($idDossier, $idEtab, 1),
        ]);

        // Affichage dossier incomplet pour generation dossier incomplet
        // Recuperation des documents manquants dans le cas d'un dossier incomplet
        $dbDossDocManquant = new Model_DbTable_DossierDocManquant();
        $this->view->assign('listeDocManquant', $dbDossDocManquant->getDocManquantDossLast($idDossier));

        $DBavisDossier = new Model_DbTable_Avis();
        $libelleAvis = $DBavisDossier->find($this->view->infosDossier['AVIS_DOSSIER'])->current();
        $this->view->assign('avisDossier', $libelleAvis['LIBELLE_AVIS']);

        // Avis commission
        $libelleAvisCommission = $DBavisDossier->find($this->view->infosDossier['AVIS_DOSSIER_COMMISSION'])->current();
        $this->view->assign('avisDossierCommission', $libelleAvisCommission['LIBELLE_AVIS']);

        $DBdossierCommission = new Model_DbTable_Commission();

        $this->view->assign('commissionInfos', 'Aucune commission');
        if ('' !== $this->view->infosDossier['COMMISSION_DOSSIER'] && null !== $this->view->infosDossier['COMMISSION_DOSSIER']) {
            $this->view->assign('commissionInfos', $DBdossierCommission->find($this->view->infosDossier['COMMISSION_DOSSIER'])->current());
        }

        $this->view->assign('etatDossier', 'Complet');
        if (1 == $this->view->infosDossier['INCOMPLET_DOSSIER']) {
            $this->view->assign('etatDossier', 'Incomplet');
        }

        // récup de l'id de la piece jointe qu'aura le rapport
        $DBpieceJointe = new Model_DbTable_PieceJointe();
        $this->view->assign('idRapportPj', $DBpieceJointe->maxPieceJointe());

        $this->view->assign('idPieceJointe', 1);
        if (isset($this->view->idRapportPj['MAX(ID_PIECEJOINTE)'])) {
            $this->view->assign('idPieceJointe', $this->view->idRapportPj['MAX(ID_PIECEJOINTE)'] + 1);
        }

        $this->view->assign('dateCommEntete', 'Indisponible');
        if ('' != $this->view->infosDossier['DATECOMM_DOSSIER'] && isset($this->view->infosDossier['DATECOMM_DOSSIER'])) {
            $dateComm = new Zend_Date($this->view->infosDossier['DATECOMM_DOSSIER'], Zend_Date::DATES);
            $this->view->assign('dateCommEntete', $dateComm->get(Zend_Date::DAY.'/'.Zend_Date::MONTH.'/'.Zend_Date::YEAR));
        }

        // récuperation de la date de passage en commission
        $dbAffectDossier = new Model_DbTable_DossierAffectation();
        $affectDossier = $dbAffectDossier->find(null, $idDossier)->current();
        $this->view->assign('affectDossier', $affectDossier);

        // Concernant cette affectation on récupere les infos sur la commission (date aux différents format)
        $dbDateComm = new Model_DbTable_DateCommission();

        // Récupération de la (ou des) date(s) de visite
        // VISITE OU GROUPE DE VISITE
        $this->view->assign('dateVisite', $this->view->infosDossier['DATEVISITE_DOSSIER']);
        // on récupère les date liées si il en existe
        // Une fois les infos de la date récupérées on peux aller chercher les date liées à cette commission pour les afficher
        $infosDateComm = $dbDateComm->find($affectDossier['ID_DATECOMMISSION_AFFECT'])->current();
        $this->view->assign('ID_AFFECTATION_DOSSIER_VISITE', $infosDateComm['ID_DATECOMMISSION']);
        if ('' === $infosDateComm['DATECOMMISSION_LIEES'] || null === $infosDateComm['DATECOMMISSION_LIEES']) {
            $commPrincipale = $affectDossier['ID_DATECOMMISSION_AFFECT'];
        } else {
            $commPrincipale = $infosDateComm['DATECOMMISSION_LIEES'];
        }

        // récupération de l'ensemble des dates liées
        $recupCommLiees = $dbDateComm->getCommissionsDateLieesMaster($commPrincipale);
        $nbDatesTotal = count($recupCommLiees);
        $nbDateDecompte = $nbDatesTotal;

        $listeDateInput = '';
        $listeHeureInput = [];

        foreach ($recupCommLiees as $ue) {
            $date = new Zend_Date($ue['DATE_COMMISSION'], Zend_Date::DATES);

            if ($nbDateDecompte == $nbDatesTotal) {
                // premiere date = date visite donc on renseigne l'input hidden correspondant avec l'id de cette date
                $this->view->assign('idDateVisiteAffect', $ue['ID_DATECOMMISSION']);
            }

            if ($nbDateDecompte > 1) {
                $listeDateInput .= $date->get(Zend_Date::DAY_SHORT.' '.Zend_Date::MONTH_NAME.' '.Zend_Date::YEAR).', ';
            } elseif (1 == $nbDateDecompte) {
                $listeDateInput .= $date->get(Zend_Date::DAY_SHORT.' '.Zend_Date::MONTH_NAME.' '.Zend_Date::YEAR);
            }

            $listeHeureInput[] = substr($ue['HEUREDEB_COMMISSION'], 0, 5).' à '.substr($ue['HEUREFIN_COMMISSION'], 0, 5);

            $this->view->assign('dateVisiteInput', $listeDateInput);
            --$nbDateDecompte;
        }

        $this->view->assign('dateVisite', $this->view->dateVisiteInput);
        $this->view->assign('heureVisite', implode(', ', $listeHeureInput));

        // PARTIE DOC CONSULTE

        // récupération du type de dossier (etude / visite)
        $dbdossier = new Model_DbTable_Dossier();
        $dossierType = $dbdossier->getTypeDossier((int) $idDossier);
        $dossierNature = $dbdossier->getNatureDossier((int) $idDossier);

        // suivant le type on récup la liste des docs
        $dblistedoc = new Model_DbTable_DossierListeDoc();

        if (in_array($dossierType['TYPE_DOSSIER'], [2, 3])) {
            if (in_array($dossierNature['ID_NATURE'], [20, 25])) {
                // cas d'un groupe de visite d'une récption de travaux
                $listeDocConsulte = $dblistedoc->getDocVisiteRT();
            } elseif (in_array($dossierNature['ID_NATURE'], [47, 48])) {
                // cas d'une VAO
                $listeDocConsulte = $dblistedoc->getDocVisiteVAO();
            } else {
                $listeDocConsulte = $dblistedoc->getDocVisite();
            }
        } elseif (1 == $dossierType['TYPE_DOSSIER']) {
            // cas d'une etude
            if (in_array($dossierNature['ID_NATURE'], [7, 19])) {
                $listeDocConsulte = $dblistedoc->getDocVisite();
            } else {
                $listeDocConsulte = $dblistedoc->getDocEtude();
            }
        } else {
            $listeDocConsulte = 0;
        }

        // on envoi la liste de base à la vue
        $this->view->assign('listeDocs', $listeDocConsulte);

        // on recup les docs ajouté pr le dossiers
        $dblistedocAjout = new Model_DbTable_ListeDocAjout();
        $listeDocAjout = $dblistedocAjout->getDocAjout((int) $idDossier);
        $this->view->assign('listeDocsAjout', $listeDocAjout);

        $this->view->assign('dossierDocConsutle', $dblistedoc->recupDocDossier((int) $idDossier));

        $service_dossier = new Service_Dossier();
        $this->view->assign('id_typeactivite', $object_informations['ID_TYPEACTIVITE']);

        // PARTIE PRESCRIPTION
        // Cas particulier pour les centres commerciaux (id_typeactivite = 29)
        // Les dossiers ayant pour nature VP,VI et VC  21,26,24,29,23,28
        $natureCC = [21, 26, 24, 29, 23, 28];
        // Les dossiers ayant pour nature LR et LP 7,19
        $natureCCL = [7, 19];

        if (
            self::ID_ACTIVITE_CENTRE_COMMERCIAL == $this->view->id_typeactivite
            && in_array($dossierNature['ID_NATURE'], $natureCC)
            && !$this->getRequest()->getParam('repriseCC')
        ) {
            if (
                isset($affectDossier['ID_DATECOMMISSION_AFFECT'])
                && '' !== $affectDossier['ID_DATECOMMISSION_AFFECT']
            ) {
                $cptIdArray = 0;
                $listeDossierConcerne = $dbAffectDossier->getDossierNonAffect($affectDossier['ID_DATECOMMISSION_AFFECT']);
                foreach ($listeDossierConcerne as $dossier) {
                    $listeDossierConcerne[$cptIdArray]['regl'] = $service_dossier->getPrescriptions((int) $dossier['ID_DOSSIER'], 0);
                    $listeDossierConcerne[$cptIdArray]['exploit'] = $service_dossier->getPrescriptions((int) $dossier['ID_DOSSIER'], 1);
                    $listeDossierConcerne[$cptIdArray]['amelio'] = $service_dossier->getPrescriptions((int) $dossier['ID_DOSSIER'], 2);
                    ++$cptIdArray;
                }

                $this->view->assign('celluleDossier', $listeDossierConcerne);
            }
        } elseif (
            self::ID_ACTIVITE_CENTRE_COMMERCIAL == $this->view->id_typeactivite
            && in_array($dossierNature['ID_NATURE'], $natureCCL)
            && !$this->getRequest()->getParam('repriseCC')
        ) {
            $dateCommGen = $this->view->infosDossier['DATECOMM_DOSSIER'];
            // On récupère toutes les cellules
            $cellulesListe = $this->view->etablissementInfos['etablissement_lies'];
            foreach ($cellulesListe as $celluleKey => $cellule) {
                // Si la cellule n'a pas un statut : fermé ou erreur
                if (!in_array($cellule['ID_STATUT'], [3, 99])) {
                    // on récupère les dossiers de la cellule
                    $dossiers = $service_etablissement->getDossiers($cellule['ID_ETABLISSEMENT']);
                    $cellulesListe[$celluleKey]['dossiers'] = $dossiers;

                    unset($cellulesListe[$celluleKey]['dossiers']['visites'], $cellulesListe[$celluleKey]['dossiers']['autres']);

                    $nbEtude = 0;

                    foreach ($dossiers['etudes'] as $dossierKey => $dossier) {
                        // Si les natures correspondent
                        if (
                            $dossier['ID_DOSSIERNATURE'] == $dossierNature['ID_NATURE']
                            && (isset($dossier['DATECOMM_DOSSIER']) && '' != $dossier['DATECOMM_DOSSIER'])
                        ) {
                            if (substr($dossier['DATECOMM_DOSSIER'], 0, 10) === $dateCommGen) {
                                ++$nbEtude;
                                // on pousse les prescriptions
                                $cellulesListe[$celluleKey]['dossiers']['etudes'][$dossierKey]['regl'] = $service_dossier->getPrescriptions((int) $dossier['ID_DOSSIER'], 0);
                                $cellulesListe[$celluleKey]['dossiers']['etudes'][$dossierKey]['exploit'] = $service_dossier->getPrescriptions((int) $dossier['ID_DOSSIER'], 1);
                                $cellulesListe[$celluleKey]['dossiers']['etudes'][$dossierKey]['amelio'] = $service_dossier->getPrescriptions((int) $dossier['ID_DOSSIER'], 2);
                            } else {
                                unset($cellulesListe[$celluleKey]['dossiers']['etudes'][$dossierKey]);
                            }
                        } else {
                            // on supprime du tableau les dossiers qui ne correspondent pas
                            unset($cellulesListe[$celluleKey]['dossiers']['etudes'][$dossierKey]);
                        }
                    }

                    if (0 == $nbEtude) {
                        unset($cellulesListe[$celluleKey]);
                    }
                } else {
                    // on supprime du tableau les cellules qui ne correspondent pas
                    unset($cellulesListe[$celluleKey]);
                }
            }

            $this->view->assign('celluleDossierLevee', $cellulesListe);
        }

        $this->view->assign('prescriptionReglDossier', $service_dossier->getPrescriptions((int) $idDossier, 0));
        $this->view->assign('prescriptionExploitation', $service_dossier->getPrescriptions((int) $idDossier, 1));
        $this->view->assign('prescriptionAmelioration', $service_dossier->getPrescriptions((int) $idDossier, 2));

        // GESTION DES DATES
        // Conversion de la date de dépot en mairie pour l'afficher
        if ('' != $this->view->infosDossier['DATEMAIRIE_DOSSIER']) {
            $date = new Zend_Date($this->view->infosDossier['DATEMAIRIE_DOSSIER'], Zend_Date::DATES);
            $this->view->assign('DATEMAIRIE', $date->get(Zend_Date::DAY_SHORT.' '.Zend_Date::MONTH_NAME.' '.Zend_Date::YEAR));
        }

        // Conversion de la date de dépot en secrétariat pour l'afficher
        if ('' != $this->view->infosDossier['DATESECRETARIAT_DOSSIER']) {
            $date = new Zend_Date($this->view->infosDossier['DATESECRETARIAT_DOSSIER'], Zend_Date::DATES);
            $this->view->assign('DATESECRETARIAT', $date->get(Zend_Date::DAY_SHORT.' '.Zend_Date::MONTH_NAME.' '.Zend_Date::YEAR));
        }

        // Conversion de la date de réception SDIS
        if ('' != $this->view->infosDossier['DATEINSERT_DOSSIER']) {
            $date = new Zend_Date($this->view->infosDossier['DATEINSERT_DOSSIER'], Zend_Date::DATES);
            $this->view->assign('DATEINSERTDOSSIER', $date->get(Zend_Date::DAY_SHORT.' '.Zend_Date::MONTH_NAME.' '.Zend_Date::YEAR));
        }

        // Conversion de la date de création du dossier
        if ('' != $this->view->infosDossier['DATESDIS_DOSSIER']) {
            $date = new Zend_Date($this->view->infosDossier['DATESDIS_DOSSIER'], Zend_Date::DATES);
            $this->view->assign('DATESDIS', $date->get(Zend_Date::DAY_SHORT.' '.Zend_Date::MONTH_NAME.' '.Zend_Date::YEAR));
        }

        // Conversion de la date de dossier incomplet (documents manquants)
        if (null !== $this->view->listeDocManquant['DATE_DOCSMANQUANT']) {
            $date = new Zend_Date($this->view->listeDocManquant['DATE_DOCSMANQUANT'], Zend_Date::DATES);
            $this->view->assign('DATE_DOCSMANQUANT', $date->get(Zend_Date::DAY_SHORT.' '.Zend_Date::MONTH_NAME.' '.Zend_Date::YEAR));
        }

        // Conversion de la date de dossier complet (réception des documents manquants)
        if (null !== $this->view->listeDocManquant['DATE_RECEPTION_DOC']) {
            $date = new Zend_Date($this->view->listeDocManquant['DATE_RECEPTION_DOC'], Zend_Date::DATES);
            $this->view->assign('DATE_RECEPTION_DOC', $date->get(Zend_Date::DAY_SHORT.' '.Zend_Date::MONTH_NAME.' '.Zend_Date::YEAR));
        }

        // date rvrat et attestation solidité et MO
        $this->view->assign('dateRvrat', 'Indisponible');
        if (
            '' != $this->view->infosDossier['DATERVRAT_DOSSIER']
            && isset($this->view->infosDossier['DATERVRAT_DOSSIER'])
        ) {
            $dateComm = new Zend_Date($this->view->infosDossier['DATERVRAT_DOSSIER'], Zend_Date::DATES);
            $this->view->assign('dateRvrat', $dateComm->get(Zend_Date::DAY_SHORT.' '.Zend_Date::MONTH_NAME.' '.Zend_Date::YEAR));
        }

        // date levée prescriptions
        $this->view->assign('dateDelaipresc', 'Pas de date');
        if (
            '' != $this->view->infosDossier['DELAIPRESC_DOSSIER']
            && isset($this->view->infosDossier['DELAIPRESC_DOSSIER'])
        ) {
            $dateComm = new Zend_Date($this->view->infosDossier['DELAIPRESC_DOSSIER'], Zend_Date::DATES);
            $this->view->assign('dateDelaipresc', $dateComm->get(Zend_Date::DAY_SHORT.' '.Zend_Date::MONTH_NAME.' '.Zend_Date::YEAR));
        }

        // PARTIE TEXTES APPLICABLES
        // on recupere tout les textes applicables qui ont été cochés dans le dossier
        $dbDossierTextesAppl = new Model_DbTable_DossierTextesAppl();
        $this->view->assign('listeTextesAppl', $dbDossierTextesAppl->recupTextesDossierGenDoc($this->getRequest()->getParam('idDossier')));

        // DATE DE LA DERNIERE VISITE PERIODIQUE
        $dateVisite = $this->view->infosDossier['DATEVISITE_DOSSIER'];

        if (null !== $dateVisite) {
            $dateLastVP = $DBdossier->findLastVpCreationDoc($idEtab, $idDossier, $dateVisite);

            $this->view->assign('dateLastVP', null);
            if ($dateLastVP) {
                $ZendDateLastVP = new Zend_Date($dateLastVP['DATEVISITE_DOSSIER'], Zend_Date::DATES);
                $this->view->assign('dateLastVP', $ZendDateLastVP->get(Zend_Date::DAY.' '.Zend_Date::MONTH_NAME.' '.Zend_Date::YEAR));
                $avisLastVP = $DBdossier->getAvisDossier($dateLastVP['ID_DOSSIER']);
                $this->view->assign('avisLastVP', $avisLastVP['LIBELLE_AVIS']);
            }
        }

        $serviceDescriptifDossier = new Service_DossierVerificationsTechniques();
        $rubriquesDossier = $serviceDescriptifDossier->getRubriques($idDossier, 'Dossier');

        $serviceDescriptifEtablissement = new Service_EtablissementDescriptif();
        $rubriquesEtablissement = 0 === $idEtab ? '' : $serviceDescriptifEtablissement->getRubriques($idEtab, 'Etablissement');

        $serviceDossierEffectifsDegagements = new Service_DossierEffectifsDegagements();
        $rubriquesDossierEffectifsDegagements = $serviceDossierEffectifsDegagements->getRubriques($idDossier, 'Dossier');

        $serviceEtablissementEffectifsDegagements = new Service_EtablissementEffectifsDegagements();
        $rubriquesEtablissementEffectifsDegagements = 0 === $idEtab ? '' : $serviceEtablissementEffectifsDegagements->getRubriques($idEtab, 'Etablissement');

        $rubriquesByCapsuleRubrique = [
            'descriptifVerificationsTechniques' => $rubriquesDossier,
            'descriptifEtablissement' => $rubriquesEtablissement,
            'effectifsDegagementsDossier' => $rubriquesDossierEffectifsDegagements,
            'effectifsDegagementsEtablissement' => $rubriquesEtablissementEffectifsDegagements,
        ];

        $serviceFormulaire = new Service_Formulaire();
        $capsulesRubriques = $serviceFormulaire->getAllCapsuleRubrique();

        // Récupération des rubriques pour chaque objet global
        foreach ($capsulesRubriques as $key => $capsuleRubrique) {
            $capsulesRubriques[$key]['RUBRIQUES'] = $rubriquesByCapsuleRubrique[$capsuleRubrique['NOM_INTERNE']];
        }

        $this->view->assign('formulaires', $capsulesRubriques);
        $this->view->assign('isDescriptifPersonnalise', 1 === (int) getenv('PREVARISC_DESCRIPTIF_PERSONNALISE'));

        // Sauvegarde de la pièce jointe
        $dateDuJour = new Zend_Date();
        $DBpieceJointe = new Model_DbTable_PieceJointe();
        $nouvellePJ = $DBpieceJointe->createRow();
        $nouvellePJ->ID_PIECEJOINTE = $this->view->idPieceJointe;
        $nouvellePJ->NOM_PIECEJOINTE = substr(basename($this->view->fichierSelect), 0, strlen(basename($this->view->fichierSelect)) - 4);
        $nouvellePJ->EXTENSION_PIECEJOINTE = '.odt';
        $nouvellePJ->DESCRIPTION_PIECEJOINTE = sprintf(
            "Rapport de l'établissement %s (%s) généré le %s à %s",
            $object_informations['LIBELLE_ETABLISSEMENTINFORMATIONS'],
            $etablissement['NUMEROID_ETABLISSEMENT'],
            $dateDuJour->get(Zend_Date::DAY.'/'.Zend_Date::MONTH.'/'.Zend_Date::YEAR),
            $dateDuJour->get(Zend_Date::HOUR.':'.Zend_Date::MINUTE)
        );
        $nouvellePJ->DATE_PIECEJOINTE = $dateDuJour->get(Zend_Date::YEAR.'-'.Zend_Date::MONTH.'-'.Zend_Date::DAY);
        $nouvellePJ->save();

        $this->view->assign('nouvellePJ', $nouvellePJ);

        $this->view->assign('store', Zend_Controller_Front::getInstance()->getParam('bootstrap')->getResource('dataStore'));

        $url = $this->getHelper('url')->url(['controller' => 'piece-jointe', 'id' => $idDossier, 'action' => 'get', 'idpj' => $nouvellePJ['ID_PIECEJOINTE'], 'type' => 'dossier']);

        echo "<a href='".$url."'>Ouvrir le rapport de l'établissement : ".$object_informations['LIBELLE_ETABLISSEMENTINFORMATIONS'].'<a/><br/><br/>';

        $DBsave = new Model_DbTable_DossierPj();
        $linkPj = $DBsave->createRow();
        $linkPj->ID_DOSSIER = $idDossier;
        $linkPj->ID_PIECEJOINTE = $nouvellePJ->ID_PIECEJOINTE;
        $linkPj->save();

        $this->render('creationdoc');
    }

    public function descriptifAction(): void
    {
        $idDossier = (int) $this->getRequest()->getParam('id');

        if (0 !== $idDossier) {
            // Cas d'affichage des infos d'un dossier existant
            $this->view->assign('do', 'edit');
            // On récupère l'id du dossier
            $this->view->assign('idDossier', $idDossier);
            // Récupération de tous les champs de la table dossier
            $DBdossier = new Model_DbTable_Dossier();
            $this->view->assign('infosDossier', $DBdossier->find($idDossier)->current());

            $DBdossierNature = new Model_DbTable_DossierNature();
            $this->view->assign('natureConcerne', $DBdossierNature->getDossierNaturesLibelle($idDossier));
        }

        $service_dossier = new Service_Dossier();

        if ($this->idDossier) {
            $this->view->assign('enteteEtab', $service_dossier->getEtabInfos($this->idDossier));
        }

        if ($this->getRequest()->isPost()) {
            $DBdossier = new Model_DbTable_Dossier();
            $dossier = $DBdossier->find($idDossier)->current();
            $dossier->DESCRIPTIF_DOSSIER = $this->getRequest()->getParam('DESCRIPTIF_DOSSIER');
            $dossier->save();

            $this->_helper->_redirector('descriptif', $this->getRequest()->getControllerName(), null, ['id' => $idDossier]);
        }
    }

    public function textesApplicablesAction(): void
    {
        $this->_helper->layout->setLayout('dossier');

        $service_dossier = new Service_Dossier();

        if ($this->idDossier) {
            $this->view->assign('enteteEtab', $service_dossier->getEtabInfos($this->idDossier));
        }

        $this->view->assign('textes_applicables_dossier', $service_dossier->getAllTextesApplicables($this->getRequest()->getParam('id')));
    }

    public function editTextesApplicablesAction(): void
    {
        $service_dossier = new Service_Dossier();
        $service_textes_applicables = new Service_TextesApplicables();
        $id = $this->getRequest()->getParam('id');

        $this->view->assign('textes_applicables_dossier', $service_dossier->getAllTextesApplicables($id));
        $this->view->assign('textes_applicables', $service_textes_applicables->getAll());

        if ($this->getRequest()->isPost()) {
            try {
                $post = $this->getRequest()->getPost();
                $service_dossier->saveTextesApplicables($id, $post['textes_applicables']);
                $this->_helper->flashMessenger(['context' => 'success', 'title' => 'Mise à jour réussie !', 'message' => 'Les textes applicables ont bien été mis à jour.']);
            } catch (Exception $e) {
                $this->_helper->flashMessenger(['context' => 'error', 'title' => 'Mise à jour annulée', 'message' => 'Les textes applicables n\'ont pas été mis à jour. Veuillez rééssayez. ('.$e->getMessage().')']);
            }

            $this->_helper->redirector('textes-applicables', null, null, ['id' => $id]);
        }
    }

    // GESTION DE LA PARTIE PRESCRIPTION
    public function emplacementAction(): void
    {
        $this->view->assign('categorie', $this->getRequest()->getParam('PRESCRIPTIONTYPE_CATEGORIE'));
        $this->view->assign('texte', $this->getRequest()->getParam('PRESCRIPTIONTYPE_TEXTE'));
        $this->view->assign('article', $this->getRequest()->getParam('PRESCRIPTIONTYPE_ARTICLE'));

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
            $this->view->assign('texteListe', $dbTexte->recupPrescriptionTexte($this->getRequest()->getParam('PRESCRIPTIONTYPE_CATEGORIE')));
        } elseif (!$this->view->article) {
            $dbPrescriptionCat = new Model_DbTable_PrescriptionCat();
            $categorieLibelle = $dbPrescriptionCat->find($this->view->categorie)->current()->toArray();
            $this->view->assign('categorieLibelle', $categorieLibelle['LIBELLE_PRESCRIPTION_CAT']);
            $dbTexte = new Model_DbTable_PrescriptionTexte();
            $texteLibelle = $dbTexte->find($this->view->texte)->current()->toArray();
            $this->view->assign('texteLibelle', $texteLibelle['LIBELLE_PRESCRIPTIONTEXTE']);
            // on viens de choisir un texte il faut afficher les articles
            $dbArticle = new Model_DbTable_PrescriptionArticle();
            $this->view->assign('texteArticle', $dbArticle->recupPrescriptionArticle($this->getRequest()->getParam('PRESCRIPTIONTYPE_TEXTE')));
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

    public function prescriptionAction(): void
    {
        $service_dossier = new Service_Dossier();
        if ($this->idDossier) {
            $this->view->assign('enteteEtab', $service_dossier->getEtabInfos($this->idDossier));
        }

        if ($this->getRequest()->isPost()) {
            try {
                $post = $this->getRequest()->getPost();
                if (
                    'edit' == $post['action']
                    || 'edit-type' == $post['action']
                ) {
                    $service_dossier->savePrescription($post);
                    $this->_helper->flashMessenger(['context' => 'success', 'title' => 'Enregistrement effectué.', 'message' => 'La prescription a bien été modifiée']);
                } elseif ('presc-add' == $post['action']) {
                    $service_dossier->savePrescription($post);
                    $this->_helper->flashMessenger(['context' => 'success', 'title' => 'Enregistrement effectué.', 'message' => 'La prescription a bien été ajoutée']);
                } elseif ('delete' == $post['action']) {
                    $service_dossier->deletePrescription($post);
                    $this->_helper->flashMessenger(['context' => 'success', 'title' => 'Suppression effectué.', 'message' => 'La prescription a bien été supprimée']);
                }
            } catch (Exception $e) {
                $this->_helper->flashMessenger(['context' => 'error', 'title' => "Erreur lors de l'enregistrement.", 'message' => 'Une erreur s\'est produite lors de l\enregistrement de la prescription ('.$e->getMessage().')']);
            }
        }

        $this->view->assign('id_dossier', $this->getRequest()->getParam('id'));
        $DbDossier = new Model_DbTable_Dossier();
        $this->view->assign('infosDossier', $DbDossier->find((int) $this->view->id_dossier)->current());

        $this->view->assign('prescriptionReglDossier', $service_dossier->getPrescriptions((int) $this->getRequest()->getParam('id'), 0));
        $this->view->assign('prescriptionExploitation', $service_dossier->getPrescriptions((int) $this->getRequest()->getParam('id'), 1));
        $this->view->assign('prescriptionAmelioration', $service_dossier->getPrescriptions((int) $this->getRequest()->getParam('id'), 2));
    }

    public function prescriptionwordsearchAction(): void
    {
        $this->view->assign('tabMotCles', []);
        if ($this->getRequest()->getParam('motsCles')) {
            $this->view->assign('tabMotCles', explode(' ', $this->getRequest()->getParam('motsCles')));
            $dbPrescType = new Model_DbTable_PrescriptionType();
            $listePrescType = $dbPrescType->getPrescriptionTypeByWords($this->view->tabMotCles);

            $dbPrescAssoc = new Model_DbTable_PrescriptionTypeAssoc();
            $prescriptionArray = [];
            foreach ($listePrescType as $ue) {
                $assoc = $dbPrescAssoc->getPrescriptionAssoc($ue['ID_PRESCRIPTIONTYPE']);
                if ([] !== $assoc) {
                    $prescriptionArray[] = $assoc;
                }
            }

            $this->view->assign('prescriptionType', $prescriptionArray);
        }
    }

    public function prescriptionAddAction(): void
    {
        $this->forward('prescription-edit');
    }

    public function prescriptiontypeformAction(): void
    {
        $this->showprescriptionTypeAction(0, 0, 0);
    }

    public function showprescriptionTypeAction($categorie, $texte, $article): void
    {
        $dbPrescType = new Model_DbTable_PrescriptionType();
        $listePrescType = $dbPrescType->getPrescriptionType($categorie, $texte, $article);

        $dbPrescAssoc = new Model_DbTable_PrescriptionTypeAssoc();
        $prescriptionArray = [];

        foreach ($listePrescType as $ue) {
            $assoc = $dbPrescAssoc->getPrescriptionAssoc($ue['ID_PRESCRIPTIONTYPE']);
            $prescriptionArray[] = $assoc;
        }

        $this->view->assign('prescriptionType', $prescriptionArray);
    }

    public function prescriptionEditAction(): void
    {
        $this->view->inlineScript()->appendFile('/js/calendrier/today.js', 'text/javascript');

        $idDossier = $this->getRequest()->getParam('id');
        $id_prescription = $this->getRequest()->getParam('id-prescription');

        $this->view->assign('id_dossier', $idDossier);
        $this->view->assign('id_prescription', $id_prescription);

        // On envoi à la vue l'ensemble des textes et articles
        $dbTexte = new Model_DbTable_PrescriptionTexteListe();
        $this->view->assign('listeTextes', $dbTexte->getAllTextes(1));
        $dbArticle = new Model_DbTable_PrescriptionArticleListe();
        $this->view->assign('listeArticles', $dbArticle->getAllArticles(1));

        if (isset($id_prescription)) {
            $service_dossier = new Service_Dossier();
            $this->view->assign('infosPrescription', $service_dossier->getDetailPrescription($id_prescription));

            $this->view->assign('action', 'edit-type');
            if (null == $this->view->infosPrescription['ID_PRESCRIPTION_TYPE']) {
                $this->view->assign('action', 'edit');
            }
        } else {
            $this->view->assign('action', 'presc-add');
        }
    }

    public function prescriptionshowemplacementAction(): void
    {
        $this->view->assign('categorie', $this->getRequest()->getParam('PRESCRIPTIONTYPE_CATEGORIE'));
        $this->view->assign('texte', $this->getRequest()->getParam('PRESCRIPTIONTYPE_TEXTE'));
        $this->view->assign('article', $this->getRequest()->getParam('PRESCRIPTIONTYPE_ARTICLE'));

        if (
            !$this->view->categorie
            && !$this->view->texte
            && !$this->view->article
        ) {
            $this->showprescriptionTypeAction(0, 0, 0);
        } elseif (
            !$this->view->texte
            && !$this->view->article
        ) {
            $this->showprescriptionTypeAction($this->view->categorie, 0, 0);
        } elseif (!$this->view->article) {
            $this->showprescriptionTypeAction($this->view->categorie, $this->view->texte, 0);
        } else {
            $this->showprescriptionTypeAction($this->view->categorie, $this->view->texte, $this->view->article);
        }
    }

    public function prescriptionaddtypeAction(): void
    {
        $idPrescType = $this->getRequest()->getParam('idPrescType');
        $idDossier = $this->getRequest()->getParam('idDossier');
        $this->view->assign('typePrescDossier', $this->getRequest()->getParam('typePrescriptionDossier'));
        $this->view->assign('idDossier', $idDossier);

        // on recup le num max de prescription du dossier
        $dbPrescDossier = new Model_DbTable_PrescriptionDossier();
        $numMax = $dbPrescDossier->recupMaxNumPrescDossier($idDossier, $this->getRequest()->getParam('typePrescriptionDossier'));
        $num = $numMax['maxnum'];

        if (null == $numMax['maxnum']) {
            // premiere prescription que l'on ajoute
            $num = 1;
        } else {
            ++$num;
        }

        $newPrescDossier = $dbPrescDossier->createRow();
        $newPrescDossier->ID_DOSSIER = $idDossier;
        $newPrescDossier->NUM_PRESCRIPTION_DOSSIER = $num;
        $newPrescDossier->ID_PRESCRIPTION_TYPE = $idPrescType;
        $newPrescDossier->TYPE_PRESCRIPTION_DOSSIER = $this->getRequest()->getParam('typePrescriptionDossier');
        $newPrescDossier->save();

        $this->view->assign('idPrescriptionDossier', $newPrescDossier->ID_PRESCRIPTION_DOSSIER);

        // On recupere les informations de la prescription type pour l'afficher dans la liste
        $dbPrescTypeAssoc = new Model_DbTable_PrescriptionTypeAssoc();
        $prescType = $dbPrescTypeAssoc->getPrescriptionAssoc($idPrescType);
        $texteArray = [];
        $articleArray = [];

        foreach ($prescType as $value) {
            $articleArray[] = $value['LIBELLE_ARTICLE'];
            $texteArray[] = $value['LIBELLE_TEXTE'];
            $this->view->assign('libelle', $value['PRESCRIPTIONTYPE_LIBELLE']);
        }

        $this->view->assign('numPresc', $num);
        $this->view->assign('textes', $texteArray);
        $this->view->assign('articles', $articleArray);

        $nbPresc = 1;
        $listeExploit = $dbPrescDossier->recupPrescDossier($idDossier, 1);
        foreach ($listeExploit as $prescDossier) {
            $prescCount = $dbPrescDossier->find($prescDossier['ID_PRESCRIPTION_DOSSIER'])->current();
            if (!$prescCount) {
                continue;
            }

            $prescCount->NUM_PRESCRIPTION_DOSSIER = $nbPresc;
            $prescCount->save();
            ++$nbPresc;
        }

        $listeAmelio = $dbPrescDossier->recupPrescDossier($idDossier, 2);
        foreach ($listeAmelio as $prescDossier) {
            $prescCount = $dbPrescDossier->find($prescDossier['ID_PRESCRIPTION_DOSSIER'])->current();
            if (!$prescCount) {
                continue;
            }

            $prescCount->NUM_PRESCRIPTION_DOSSIER = $nbPresc;
            $prescCount->save();
            ++$nbPresc;
        }
    }

    public function prescriptionchangeposAction(): void
    {
        $this->_helper->viewRenderer->setNoRender();

        $stringUpdateReg = $this->getRequest()->getParam('tableUpdateReg');
        $tabIdReg = explode(',', $stringUpdateReg);

        $stringUpdate = $this->getRequest()->getParam('tableUpdate');
        $tabId = explode(',', $stringUpdate);

        $service_dossier = new Service_Dossier();
        $service_dossier->changePosPrescription($tabIdReg);
        $service_dossier->changePosPrescription($tabId);
    }

    public function formrecupprescriptionAction(): void
    {
        $idDossier = (int) $this->getRequest()->getParam('idDossier');

        // récupération de l'établissement attaché au dossier
        $dbEtabDossier = new Model_DbTable_EtablissementDossier();
        $listeEtab = $dbEtabDossier->getEtablissementListe($idDossier);

        $this->view->assign('nbEtab', count($listeEtab));
        $this->view->assign('idDossier', $idDossier);

        if (1 == $this->view->nbEtab) {
            // si il n'y a qu'un établissement, on affiche la liste des dossiers qu'il contient
            $service_etablissement = new Service_Etablissement();
            $dossiers = $service_etablissement->getDossiers($listeEtab['0']['ID_ETABLISSEMENT']);
            $this->view->assign('etudes', $dossiers['etudes']);
            $this->view->assign('visites', $dossiers['visites']);
            $this->view->assign('autres', $dossiers['autres']);
        }
    }

    public function recupprescriptionAction(): void
    {
        $this->_helper->viewRenderer->setNoRender();
        $service_dossier = new Service_Dossier();

        $idDossier = (int) $this->getRequest()->getParam('idDossier');
        $dossiersSelect = [];
        $post = $this->getRequest()->getPost();

        foreach ($post as $key => $value) {
            if (0 === strpos($key, 'dossierSelect-')) {
                $dossiersSelect[] = $value;
            }
        }

        foreach ($dossiersSelect as $idDossierInitial) {
            $prescriptionRappelsReglementaire = $service_dossier->getPrescriptions($idDossierInitial, 0);
            $service_dossier->copyPrescriptionDossier($prescriptionRappelsReglementaire, $idDossier, $idDossierInitial);

            $prescriptionExploitation = $service_dossier->getPrescriptions($idDossierInitial, 1);
            $service_dossier->copyPrescriptionDossier($prescriptionExploitation, $idDossier, $idDossierInitial);

            $prescriptionAmelioration = $service_dossier->getPrescriptions($idDossierInitial, 2);
            $service_dossier->copyPrescriptionDossier($prescriptionAmelioration, $idDossier, $idDossierInitial);
        }
    }

    public function formrecupeffectifsdegagementsAction(): void
    {
        $idDossier = (int) $this->getRequest()->getParam('idDossier');

        // récupération de l'établissement attaché au dossier
        $dbEtabDossier = new Model_DbTable_EtablissementDossier();
        $listeEtab = $dbEtabDossier->getEtablissementListe($idDossier);

        $this->view->assign('nbEtab', count($listeEtab));
        $this->view->assign('idDossier', $idDossier);

        if (1 == $this->view->nbEtab) {
            // si il n'y a qu'un établissement, on affiche la liste des dossiers qu'il contient
            $service_etablissement = new Service_Etablissement();
            $dossiers = $service_etablissement->getDossiers($listeEtab['0']['ID_ETABLISSEMENT']);
            $this->view->assign('etudes', $dossiers['etudes']);
            $this->view->assign('visites', $dossiers['visites']);
            $this->view->assign('autres', $dossiers['autres']);
        }
    }

    public function recupeffectifsdegagementsAction(): void
    {
        $this->_helper->viewRenderer->setNoRender();

        $idDossierInitial = (int) $this->getRequest()->getParam('dossierSelect');
        $idDossier = (int) $this->getRequest()->getParam('idDossier');

        $serviceDossierEffectifsDegagements = new Service_DossierEffectifsDegagements();

        $rubriques = $serviceDossierEffectifsDegagements->getRubriques($idDossierInitial, 'Dossier');
        $serviceDossierEffectifsDegagements->copyValeurs($idDossier, $rubriques);
    }

    public function lienmultipleAction(): void
    {
        $this->_helper->viewRenderer->setNoRender();
        foreach ($this->getRequest()->getParam('etabId') as $val) {
            try {
                $DBetablissementDossier = new Model_DbTable_EtablissementDossier();
                $newEtabDossier = $DBetablissementDossier->createRow();
                $newEtabDossier->ID_ETABLISSEMENT = $val;
                $newEtabDossier->ID_DOSSIER = $this->getRequest()->getParam('idDossier');
                $newEtabDossier->save();

                $this->_helper->flashMessenger([
                    'context' => 'success',
                    'title' => 'L\'établissement a bien été ajouté',
                    'message' => '',
                ]);
            } catch (Exception $e) {
                $this->_helper->flashMessenger([
                    'context' => 'error',
                    'title' => 'Erreur lors de l\'ajout de l\'établissement',
                    'message' => $e->getMessage(),
                ]);
            }
        }
    }

    // GESTION DU VERROUILLAGE
    public function verrouAction(): void
    {
        $this->_helper->viewRenderer->setNoRender();
        $DBdossier = new Model_DbTable_Dossier();
        $lockDosier = $DBdossier->find($this->getRequest()->getParam('idDossier'))->current();
        $lockDosier->VERROU_DOSSIER = 1;
        $lockDosier->VERROU_USER_DOSSIER = $this->getRequest()->getParam('ID_CREATEUR');
        $lockDosier->save();
        echo $lockDosier->ID_DOSSIER;
    }

    public function deverrouAction(): void
    {
        $this->_helper->viewRenderer->setNoRender();
        $DBdossier = new Model_DbTable_Dossier();
        $lockDosier = $DBdossier->find($this->getRequest()->getParam('idDossier'))->current();
        $lockDosier->VERROU_DOSSIER = 0;
        $lockDosier->VERROU_USER_DOSSIER = null;
        $lockDosier->save();
        echo $lockDosier->ID_DOSSIER;
    }

    // GESTION DE LA SUPPRESSION
    public function deleteAction(): void
    {
        try {
            $DBetablissementDossier = new Model_DbTable_EtablissementDossier();
            $listeEtab = $DBetablissementDossier->getEtablissementListe($this->getRequest()->getParam('id'));
            $cache = Zend_Controller_Front::getInstance()->getParam('bootstrap')->getResource('cache');

            $service_dossier = new Service_Dossier();
            $service_dossier->delete($this->getRequest()->getParam('id'));

            $dbDossier = new Model_DbTable_Dossier();
            // on récupère les infos du dernier dossier donnant avis de l'établissement courant
            foreach ($listeEtab as $etab) {
                $dernierDossierDonnantAvis = $dbDossier->getGeneral($dbDossier->getDernierIdDossierDonnantAvis($etab['ID_ETABLISSEMENT'])['ID_DOSSIER']);
                $service_dossier->saveDossierDonnantAvisCurrentEtab($dernierDossierDonnantAvis, $etab, $cache);
            }

            // Récupération de la ressource cache à partir du bootstrap
            $cache = Zend_Controller_Front::getInstance()->getParam('bootstrap')->getResource('cacheSearch');
            $cache->clean(Zend_Cache::CLEANING_MODE_ALL);

            $this->_helper->flashMessenger([
                'context' => 'success',
                'title' => 'Mise à jour réussie !',
                'message' => 'Le dossier a bien été supprimé.',
            ]);
            $this->redirect('/search/dossier?objet=&page=1');
        } catch (Exception $exception) {
            $this->_helper->flashMessenger([
                'context' => 'error',
                'title' => '',
                'message' => 'L\'établissement n\'a pas été mis à jour. Veuillez rééssayez. ('.$exception->getMessage().')',
            ]);
        }
    }

    public function effectifsDegagementsDossierAction(): void
    {
        $viewHeadLink = $this->view;
        $viewHeadLink->headLink()->appendStylesheet('/css/formulaire/descriptif.css', 'all');
        $viewHeadLink->headLink()->appendStylesheet('/css/formulaire/tableauInputParent.css', 'all');

        $serviceDossierEffectifsDegagements = new Service_DossierEffectifsDegagements();
        $service_dossier = new Service_Dossier();
        $service_champ = new Service_Champ();

        if ($this->idDossier) {
            $this->view->assign('enteteEtab', $service_dossier->getEtabInfos($this->idDossier));
        }

        $rubriques = $serviceDossierEffectifsDegagements->getRubriques($this->idDossier, 'Dossier');
        $hasData = false;

        foreach ($rubriques as $rubrique) {
            $champs = $rubrique['CHAMPS'];

            foreach ($champs as $champ) {
                if ($service_champ->hasValue($champ)) {
                    $hasData = true;

                    break;
                }
            }
        }

        $this->view->assign('rubriques', $rubriques);
        $this->view->assign('champsvaleurliste', $serviceDossierEffectifsDegagements->getValeursListe());
        $this->view->assign('hasData', $hasData);
    }

    public function effectifsDegagementsDossierEditAction(): void
    {
        $this->view->headLink()->appendStylesheet('/css/formulaire/edit-table.css', 'all');
        $this->view->headLink()->appendStylesheet('/css/formulaire/formulaire.css', 'all');

        $this->view->inlineScript()->appendFile('/js/formulaire/ordonnancement/Sortable.min.js', 'text/javascript');
        $this->view->inlineScript()->appendFile('/js/formulaire/ordonnancement/ordonnancement.js', 'text/javascript');
        $this->view->inlineScript()->appendFile('/js/formulaire/tableau/gestionTableau.js', 'text/javascript');
        $this->view->inlineScript()->appendFile('/js/formulaire/descriptif/edit.js', 'text/javascript');
        $this->view->inlineScript()->appendFile('/js/calendrier/today.js', 'text/javascript');

        $serviceDossierEffectifsDegagements = new Service_DossierEffectifsDegagements();

        $this->effectifsDegagementsDossierAction();

        $request = $this->getRequest();
        if ($request->isPost()) {
            try {
                $post = $request->getParams();

                foreach ($post as $key => $value) {
                    // Informations concernant l'affichage des rubriques

                    if (0 === strpos($key, 'afficher_rubrique-')) {
                        $serviceDossierEffectifsDegagements->saveRubriqueDisplay($key, $this->idDossier, (int) $value);
                    }

                    // Informations concernant les valeurs des champs
                    if (0 === strpos($key, 'champ-')) {
                        $serviceDossierEffectifsDegagements->saveValeurChamp($key, $this->idDossier, 'Dossier', $value);
                    }
                }

                // Sauvegarde les changements dans les tableaux
                $serviceDossierEffectifsDegagements->saveChangeTable($this->view->rubriques, $serviceDossierEffectifsDegagements->groupInputByOrder($post, $this->idDossier, 'Dossier'), 'Dossier', $this->idDossier);

                $this->_helper->flashMessenger(['context' => 'success', 'title' => 'Mise à jour réussie !', 'message' => 'Les effectifs et dégagements ont bien été mis à jour.']);
            } catch (Exception $e) {
                $this->_helper->flashMessenger(['context' => 'error', 'title' => 'Mise à jour annulée', 'message' => 'Les effectifs et dégagements n\'ont pas été mis à jour. Veuillez rééssayez. ('.$e->getMessage().')']);
            }

            $this->_helper->redirector('effectifs-degagements-dossier', null, null, ['id' => $request->getParam('id')]);
        }
    }

    public function verificationsTechniquesAction(): void
    {
        $viewHeadLink = $this->view;
        $viewHeadLink->headLink()->appendStylesheet('/css/formulaire/descriptif.css', 'all');
        $viewHeadLink->headLink()->appendStylesheet('/css/formulaire/tableauInputParent.css', 'all');

        $serviceDossierVerificationsTechniques = new Service_DossierVerificationsTechniques();
        $service_dossier = new Service_Dossier();

        if ($this->idDossier) {
            $this->view->assign('enteteEtab', $service_dossier->getEtabInfos($this->idDossier));
        }

        $this->view->assign('rubriques', $serviceDossierVerificationsTechniques->getRubriques($this->idDossier, 'Dossier'));
        $this->view->assign('champsvaleurliste', $serviceDossierVerificationsTechniques->getValeursListe());
    }

    public function editVerificationsTechniquesAction(): void
    {
        $this->view->headLink()->appendStylesheet('/css/formulaire/edit-table.css', 'all');
        $this->view->headLink()->appendStylesheet('/css/formulaire/formulaire.css', 'all');

        $this->view->inlineScript()->appendFile('/js/formulaire/ordonnancement/Sortable.min.js', 'text/javascript');
        $this->view->inlineScript()->appendFile('/js/formulaire/ordonnancement/ordonnancement.js', 'text/javascript');
        $this->view->inlineScript()->appendFile('/js/formulaire/tableau/gestionTableau.js', 'text/javascript');
        $this->view->inlineScript()->appendFile('/js/formulaire/descriptif/edit.js', 'text/javascript');
        $this->view->inlineScript()->appendFile('/js/calendrier/today.js', 'text/javascript');

        $serviceDossierVerificationsTechniques = new Service_DossierVerificationsTechniques();

        $this->verificationsTechniquesAction();

        $request = $this->getRequest();
        if ($request->isPost()) {
            try {
                $post = $request->getParams();

                foreach ($post as $key => $value) {
                    // Informations concernant l'affichage des rubriques

                    if (0 === strpos($key, 'afficher_rubrique-')) {
                        $serviceDossierVerificationsTechniques->saveRubriqueDisplay($key, $this->idDossier, (int) $value);
                    }

                    // Informations concernant les valeurs des champs
                    if (0 === strpos($key, 'champ-')) {
                        $serviceDossierVerificationsTechniques->saveValeurChamp($key, $this->idDossier, 'Dossier', $value);
                    }
                }

                // Sauvegarde les changements dans les tableaux
                $serviceDossierVerificationsTechniques->saveChangeTable($this->view->rubriques, $serviceDossierVerificationsTechniques->groupInputByOrder($post, $this->idDossier, 'Dossier'), 'Dossier', $this->idDossier);

                $this->_helper->flashMessenger(['context' => 'success', 'title' => 'Mise à jour réussie !', 'message' => 'Les vérifications techniques ont bien été mises à jour.']);
            } catch (Exception $e) {
                $this->_helper->flashMessenger(['context' => 'error', 'title' => 'Mise à jour annulée', 'message' => 'Les vérifications techniques n\'ont pas été mises à jour. Veuillez rééssayez. ('.$e->getMessage().')']);
            }

            $this->_helper->redirector('verifications-techniques', null, null, ['id' => $request->getParam('id')]);
        }
    }

    // Avis et derogations action donne une vue du/des avis et derogations donne sur ce dossier
    public function avisEtDerogationsAction(): void
    {
        $this->view->headLink()->appendStylesheet('/css/etiquetteAvisDerogations/cardAvisDerogations.css', 'all');
        $this->view->inlineScript()->appendFile('/js/dossier/avisDerogation.js');
        $this->view->inlineScript()->appendFile('/js/dossier/drop-list-button.js');

        $dbAvisDerogation = new Model_DbTable_AvisDerogations();
        $dbDossier = new Model_DbTable_Dossier();

        $service_dossier = new Service_Dossier();
        if ($this->idDossier) {
            $this->view->assign('enteteEtab', $service_dossier->getEtabInfos($this->idDossier));
        } elseif ($this->getRequest()->getParam('id_etablissement')) {
            $this->view->assign('enteteEtab', $service_dossier->getEtabInfos(null, $this->getRequest()->getParam('id_etablissement')));
        }

        $idDossier = $this->getParam('id');

        $this->view->assign('arrayAvisDerogations', $dbDossier->getListAvisDerogationsFromDossier($idDossier));
        $this->view->assign('listDossierEtab', $dbDossier->getListeDossierFromDossier($idDossier));
        $this->view->assign('listDossierEtabN', $dbDossier->getListeDossierFromDossierN($idDossier));

        $DBlisteAvis = new Model_DbTable_Avis();
        $this->view->assign('listeAvis', $DBlisteAvis->getAvis());

        $request = $this->getRequest();
        if ($request->isPost()) {
            $data = $request->getPost();
            $dbAvisDerogation->insert($data);

            $this->_helper->redirector('avis-et-derogations', null, null, ['id' => $idDossier]);
        }
    }

    /**
     * Retourne les informations d'une liste d avis et derogations selon l id d une etude
     * +
     * retourne vers la page d edition de ces avis + derogations.
     */
    public function avisEtDerogationsEditAction(): void
    {
        $this->view->headLink()->appendStylesheet('/css/etiquetteAvisDerogations/cardAvisDerogations.css', 'all');
        $this->view->inlineScript()->appendFile('/js/dossier/avisDerogation.js');
        $this->view->inlineScript()->appendFile('/js/dossier/drop-list-button.js');

        $dbAvisDerogations = new Model_DbTable_AvisDerogations();
        $dbDossier = new Model_DbTable_Dossier();

        $service_dossier = new Service_Dossier();
        if ($this->idDossier) {
            $this->view->assign('enteteEtab', $service_dossier->getEtabInfos($this->idDossier));
        } elseif ($this->getRequest()->getParam('id_etablissement')) {
            $this->view->assign('enteteEtab', $service_dossier->getEtabInfos(null, $this->getRequest()->getParam('id_etablissement')));
        }

        $idDossier = $this->getParam('id');
        $idAvisDerogation = $this->getParam('avis-derogation');

        $avisDerogation = $dbAvisDerogations->getByIdAvisDerogation($idAvisDerogation);
        $this->view->assign('listDossierEtab', $dbDossier->getListeDossierFromDossier($idDossier));
        $this->view->assign('listDossierEtabN', $dbDossier->getListeDossierFromDossierN($idDossier));

        $DBlisteAvis = new Model_DbTable_Avis();
        $this->view->assign('listeAvis', $DBlisteAvis->getAvis());

        $request = $this->getRequest();
        if ($request->isPost()) {
            $data = $request->getPost();

            // Recuperation de l entite a mettre a jour
            $where = $dbAvisDerogations->getAdapter()->quoteInto('ID_AVIS_DEROGATION = ?', $idAvisDerogation);

            $dbAvisDerogations->update($data, $where);

            if (!array_key_exists('ID_DOSSIER_LIE', $data)) {
                $avisDerogation->ID_DOSSIER_LIE = null;
                $avisDerogation->save();
            }

            $this->_helper->redirector('avis-et-derogations', null, null, ['id' => $idDossier]);
        }

        $this->view->assign('avisDerogations', $avisDerogation);
    }

    public function avisEtDerogationsDeleteAction(): void
    {
        $dbAvisDerogations = new Model_DbTable_AvisDerogations();

        $dbAvisDerogations->delete('ID_AVIS_DEROGATION = '.$this->getParam('id'));
    }

    public function getZipAllPjAction(): void
    {
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender();

        $idDossier = $this->getRequest()->getParam('id');
        $serviceDossier = new Service_Dossier();
        $zip = new ZipArchive();

        $pjs = $serviceDossier->getAllPiecesJointes($idDossier);

        $zipname = $idDossier.'.zip';
        $zipPath = REAL_DATA_PATH.DS.'uploads'.DS.$zipname;
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        foreach ($pjs as $pj) {
            $pjPath = Service_Utils::getPjPath($pj);

            if (!$zip->addFile($pjPath, $pj['NOM_PIECEJOINTE'].$pj['EXTENSION_PIECEJOINTE'])) {
                error_log(sprintf('Erreur lors de l\'ajout de la pièce jointe "%s%s" au fichier ZIP', $pj['NOM_PIECEJOINTE'], $pj['EXTENSION_PIECEJOINTE']));
            }
        }

        if (!$zip->close()) {
            $this->_helper->flashMessenger([
                'context' => 'error',
                'title' => 'Erreur lors de la création du fichier ZIP',
                'message' => 'Le fichier est vide',
            ]);

            $this->redirect('/dossier/piece-jointe/id/'.$idDossier);
        }

        header('Content-Type: application/zip');
        header('Content-disposition: attachment; filename='.$zipname);
        header('Content-Length: '.filesize($zipPath));

        readfile($zipPath);
        unlink($zipPath);
    }

    public function retablirDossierAction(): void
    {
        $this->_helper->viewRenderer->setNoRender();

        $previousUrl = $_SERVER['HTTP_REFERER'];
        $serviceDossier = new Service_Dossier();

        $serviceDossier->retablirDossier($this->getRequest()->getParam('idDossier'));

        $cacheSearch = Zend_Controller_Front::getInstance()->getParam('bootstrap')->getResource('cacheSearch');
        $cacheSearch->clean(Zend_Cache::CLEANING_MODE_ALL);

        $this->_helper->flashMessenger([
            'context' => 'success',
            'title' => 'Le dossier a bien été rétabli',
            'message' => '',
        ]);

        $this->redirect($previousUrl);
    }
}
