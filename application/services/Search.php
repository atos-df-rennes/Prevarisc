<?php

class Service_Search
{
    public const MAX_LIMIT_PAGES_ETABLISSEMENTS = 1000;

    public const MAX_LIMIT_PAGES_DOSSIERS = 100;

    /**
     * Recherche des établissements.
     *
     * @param string       $label
     * @param string       $identifiant
     * @param array|string $types_activites
     * @param bool         $avis_favorable
     * @param array|string $statuts
     * @param bool         $local_sommeil
     * @param float        $lon
     * @param float        $lat
     * @param int          $parent
     * @param string       $city
     * @param int          $street_id
     * @param string       $number
     * @param int          $count                    Par défaut 10, max 1000
     * @param int          $page                     par défaut = 1
     * @param null|mixed   $genres
     * @param null|mixed   $categories
     * @param null|mixed   $classes
     * @param null|mixed   $familles
     * @param null|mixed   $commissions
     * @param null|mixed   $groupements_territoriaux
     * @param null|mixed   $preventionniste
     *
     * @return array
     */
    public function etablissements($label = null, $identifiant = null, $genres = null, $categories = null, $classes = null, $familles = null, $types_activites = null, $avis_favorable = null, $statuts = null, $local_sommeil = null, $lon = null, $lat = null, $parent = null, $city = null, $street_id = null, $number = null, $commissions = null, $groupements_territoriaux = null, $preventionniste = null, $count = 10, $page = 1)
    {
        // Récupération de la ressource cache à partir du bootstrap
        $cache = Zend_Controller_Front::getInstance()->getParam('bootstrap')->getResource('cacheSearch');

        // Identifiant de la recherche
        $search_id = 'search_etablissements_'.md5(serialize(func_get_args()));

        if (($results = unserialize($cache->load($search_id))) === false) {
            // Création de l'objet recherche
            $select = new Zend_Db_Select(Zend_Controller_Front::getInstance()->getParam('bootstrap')->getResource('db'));

            // Requête principale
            $select->from(['e' => 'etablissement'], ['NUMEROID_ETABLISSEMENT', 'DUREEVISITE_ETABLISSEMENT', 'NBPREV_ETABLISSEMENT'])
                ->columns([
                    'NB_ENFANTS' => new Zend_Db_Expr('( SELECT COUNT(etablissementlie.ID_FILS_ETABLISSEMENT)
                        FROM etablissement
                        INNER JOIN etablissementlie ON etablissement.ID_ETABLISSEMENT = etablissementlie.ID_ETABLISSEMENT
                        WHERE etablissement.ID_ETABLISSEMENT = e.ID_ETABLISSEMENT
                        AND etablissement.DATESUPPRESSION_ETABLISSEMENT IS NULL)'),
                    'PRESENCE_ECHEANCIER_TRAVAUX' => new Zend_Db_Expr('(SELECT COUNT(dossierlie.ID_DOSSIER1)
                        FROM dossier
                        INNER JOIN etablissementdossier ON dossier.ID_DOSSIER = etablissementdossier.ID_DOSSIER
                        INNER JOIN dossierlie ON dossier.ID_DOSSIER = dossierlie.ID_DOSSIER2
                        INNER JOIN dossiernature ON dossierlie.ID_DOSSIER1 = dossiernature.ID_DOSSIER
                        WHERE dossiernature.ID_NATURE = 46 AND etablissementdossier.ID_ETABLISSEMENT = e.ID_ETABLISSEMENT
                        AND dossier.DATESUPPRESSION_DOSSIER IS NULL)'), ])
                ->join('etablissementinformations', 'e.ID_ETABLISSEMENT = etablissementinformations.ID_ETABLISSEMENT AND etablissementinformations.DATE_ETABLISSEMENTINFORMATIONS = ( SELECT MAX(etablissementinformations.DATE_ETABLISSEMENTINFORMATIONS) FROM etablissementinformations WHERE etablissementinformations.ID_ETABLISSEMENT = e.ID_ETABLISSEMENT )')
                ->joinLeft('dossier', 'e.ID_DOSSIER_DONNANT_AVIS = dossier.ID_DOSSIER', ['DATEVISITE_DOSSIER', 'DATECOMM_DOSSIER', 'DATEINSERT_DOSSIER', 'DIFFEREAVIS_DOSSIER'])
                ->joinLeft('avis', 'dossier.AVIS_DOSSIER_COMMISSION = avis.ID_AVIS')
                ->joinLeft('type', 'etablissementinformations.ID_TYPE = type.ID_TYPE', 'LIBELLE_TYPE')
                ->joinLeft('typeactivite', 'etablissementinformations.ID_TYPEACTIVITE = typeactivite.ID_TYPEACTIVITE', 'LIBELLE_ACTIVITE')
                ->join('genre', 'etablissementinformations.ID_GENRE = genre.ID_GENRE', 'LIBELLE_GENRE')
                ->joinLeft('etablissementlie', 'e.ID_ETABLISSEMENT = etablissementlie.ID_FILS_ETABLISSEMENT', ['pere' => 'ID_ETABLISSEMENT', 'ID_FILS_ETABLISSEMENT'])
                ->joinLeft('etablissementadresse', 'e.ID_ETABLISSEMENT = etablissementadresse.ID_ETABLISSEMENT', ['NUMINSEE_COMMUNE', 'LON_ETABLISSEMENTADRESSE', 'LAT_ETABLISSEMENTADRESSE', 'ID_ADRESSE', 'ID_RUE', 'NUMERO_ADRESSE'])
                ->joinLeft('adressecommune', 'etablissementadresse.NUMINSEE_COMMUNE = adressecommune.NUMINSEE_COMMUNE', 'LIBELLE_COMMUNE AS LIBELLE_COMMUNE_ADRESSE_DEFAULT')
                ->joinLeft('groupementcommune', 'groupementcommune.NUMINSEE_COMMUNE = adressecommune.NUMINSEE_COMMUNE')
                ->joinLeft('groupement', 'groupement.ID_GROUPEMENT = groupementcommune.ID_GROUPEMENT AND groupement.ID_GROUPEMENTTYPE = 5', 'LIBELLE_GROUPEMENT')
                ->joinLeft('adresserue', 'adresserue.ID_RUE = etablissementadresse.ID_RUE', 'LIBELLE_RUE')
                ->joinLeft(['etablissementadressesite' => 'etablissementadresse'], 'etablissementadressesite.ID_ETABLISSEMENT = (SELECT ID_FILS_ETABLISSEMENT FROM etablissementlie WHERE ID_ETABLISSEMENT = e.ID_ETABLISSEMENT LIMIT 1)', 'ID_RUE AS ID_RUE_SITE')
                ->joinLeft(['adressecommunesite' => 'adressecommune'], 'etablissementadressesite.NUMINSEE_COMMUNE = adressecommunesite.NUMINSEE_COMMUNE', 'LIBELLE_COMMUNE AS LIBELLE_COMMUNE_ADRESSE_SITE')
                ->joinLeft(['etablissementadressecell' => 'etablissementadresse'], 'etablissementadressecell.ID_ETABLISSEMENT = (SELECT ID_ETABLISSEMENT FROM etablissementlie WHERE ID_FILS_ETABLISSEMENT = e.ID_ETABLISSEMENT LIMIT 1)', 'ID_RUE AS ID_RUE_CELL')
                ->joinLeft(['adressecommunecell' => 'adressecommune'], 'etablissementadressecell.NUMINSEE_COMMUNE = adressecommunecell.NUMINSEE_COMMUNE', 'LIBELLE_COMMUNE AS LIBELLE_COMMUNE_ADRESSE_CELLULE')
                ->joinLeft('etablissementinformationspreventionniste', 'etablissementinformationspreventionniste.ID_ETABLISSEMENTINFORMATIONS = etablissementinformations.ID_ETABLISSEMENTINFORMATIONS')
                ->where('e.DATESUPPRESSION_ETABLISSEMENT IS NULL')
                // Vincent MICHEL le 12/11/2014 : retrait de cette clause qui tue les performances
                // sur la recherche. Je n'ai pas vu d'impact sur le retrait du group by.
                // Cyprien DEMAEGDT le 03/08/2015 : rétablissement de la clause pour résoudre le
                // problème de duplicité d'établissements dans les résultats de recherche (#1300)
                ->group('e.ID_ETABLISSEMENT')
            ;

            // Critères : nom de l'établissement
            if (null !== $label) {
                $cleanLabel = trim($label);

                // recherche par id
                if ('#' === substr($cleanLabel, 0, 1)) {
                    $this->setCriteria($select, 'NUMEROID_ETABLISSEMENT', substr($cleanLabel, 1), false);

                // on test si la chaine contient uniquement des caractères de type identifiant sans espace
                } elseif (1 === preg_match('/^[E0-9\/\-\.]+([0-9A-Z]{1,2})?$/', $cleanLabel)) {
                    $this->setCriteria($select, 'NUMEROID_ETABLISSEMENT', $cleanLabel, false);

                // cas par défaut
                } else {
                    $this->setCriteria($select, 'LIBELLE_ETABLISSEMENTINFORMATIONS', $cleanLabel, false);
                }
            }

            // Critères : identifiant
            if (null !== $identifiant) {
                $this->setCriteria($select, 'NUMEROID_ETABLISSEMENT', $identifiant);
            }

            // Critères : genre
            if (null !== $genres) {
                $this->setCriteria($select, 'genre.ID_GENRE', $genres);
            }

            // Critères : catégorie
            if (null !== $categories) {
                $this->setCriteria($select, 'ID_CATEGORIE', $categories);
            }

            // Critères : classe
            if (null !== $classes) {
                $this->setCriteria($select, 'ID_CLASSE', $classes);
            }

            // Critères : famille
            if (null !== $familles) {
                $this->setCriteria($select, 'ID_FAMILLE', $familles);
            }

            // Critères : type
            if (null !== $types_activites) {
                $this->setCriteria($select, 'typeactivite.ID_TYPEACTIVITE', $types_activites);
            }

            // Critères : avis favorable
            if (null !== $avis_favorable) {
                $this->setCriteria($select, 'avis.ID_AVIS', $avis_favorable ? 1 : 2);
            }

            // Critères : statuts
            if (null !== $statuts) {
                $this->setCriteria($select, 'ID_STATUT', $statuts);
            }

            // Critères : local à sommeil
            if (null !== $local_sommeil) {
                $this->setCriteria($select, 'LOCALSOMMEIL_ETABLISSEMENTINFORMATIONS', $local_sommeil);
            }

            // Critères : numéro de rue
            if (null !== $number) {
                $clauses = [];
                $clauses[] = 'etablissementadresse.NUMERO_ADRESSE = '.$select->getAdapter()->quote($number);
                if (null == $genres || in_array('1', $genres)) {
                    $clauses[] = 'etablissementadressesite.NUMERO_ADRESSE = '.$select->getAdapter()->quote($number);
                }

                if (null == $genres || in_array('3', $genres)) {
                    $clauses[] = 'etablissementadressecell.NUMERO_ADRESSE = '.$select->getAdapter()->quote($number);
                }

                $select->where('('.implode(' OR ', $clauses).')');
            }

            // Critère : commune et rue
            if (null !== $street_id) {
                $clauses = [];
                $clauses[] = 'etablissementadresse.ID_RUE = '.$select->getAdapter()->quote($street_id);
                if (null == $genres || in_array('1', $genres)) {
                    $clauses[] = 'etablissementadressesite.ID_RUE = '.$select->getAdapter()->quote($street_id);
                }

                if (null == $genres || in_array('3', $genres)) {
                    $clauses[] = 'etablissementadressecell.ID_RUE = '.$select->getAdapter()->quote($street_id);
                }

                $select->where('('.implode(' OR ', $clauses).')');
            } elseif (null !== $city) {
                $clauses = [];
                $clauses[] = 'etablissementadresse.NUMINSEE_COMMUNE = '.$select->getAdapter()->quote($city);
                if (null == $genres || in_array('1', $genres)) {
                    $clauses[] = 'etablissementadressesite.NUMINSEE_COMMUNE = '.$select->getAdapter()->quote($city);
                }

                if (null == $genres || in_array('3', $genres)) {
                    $clauses[] = 'etablissementadressecell.NUMINSEE_COMMUNE = '.$select->getAdapter()->quote($city);
                }

                $select->where('('.implode(' OR ', $clauses).')');
            }

            // Critère : commission
            if (null !== $commissions) {
                $this->setCriteria($select, 'ID_COMMISSION', $commissions);
            }

            // Critère : groupement territorial
            if (null !== $groupements_territoriaux) {
                $this->setCriteria($select, 'groupement.ID_GROUPEMENT', $groupements_territoriaux);
            }

            // Critères : géolocalisation
            if (null !== $lon && null !== $lat) {
                $this->setCriteria($select, 'etablissementadresse.LON_ETABLISSEMENTADRESSE', $lon);
                $this->setCriteria($select, 'etablissementadresse.LAT_ETABLISSEMENTADRESSE', $lat);
            }

            // Critères : parent
            if (null !== $parent) {
                $select->where(0 == $parent ? 'etablissementlie.ID_ETABLISSEMENT IS NULL' : 'etablissementlie.ID_ETABLISSEMENT = ?', $parent);
            }

            // Critère : preventionniste
            if (null !== $preventionniste) {
                $select->where('etablissementinformationspreventionniste.ID_UTILISATEUR = '.$preventionniste);
            }

            // Performance optimisation : avoid sorting on big queries, and sort only if
            // there is at least one where part
            if (count($select->getPart(Zend_Db_Select::WHERE)) > 1) {
                $select->order('etablissementinformations.LIBELLE_ETABLISSEMENTINFORMATIONS ASC');
            }

            // Gestion des pages et du count
            $select->limitPage($page, $count > self::MAX_LIMIT_PAGES_ETABLISSEMENTS ? self::MAX_LIMIT_PAGES_ETABLISSEMENTS : $count);

            // Construction du résultat
            $rows_counter = new Zend_Paginator_Adapter_DbSelect($select);
            $results = [
                'results' => $select->query()->fetchAll(),
                'search_metadata' => [
                    'search_id' => $search_id,
                    'current_page' => $page,
                    'count' => count($rows_counter),
                ],
            ];

            $cache->save(serialize($results));
        }

        return $results;
    }

    /**
     * Recherche des établissements pour l'extraction Calc.
     *
     * @param string       $label
     * @param string       $identifiant
     * @param array|string $types_activites
     * @param bool         $avis_favorable
     * @param array|string $statuts
     * @param bool         $local_sommeil
     * @param float        $lon
     * @param float        $lat
     * @param int          $parent
     * @param string       $city
     * @param int          $street_id
     * @param null|mixed   $genres
     * @param null|mixed   $categories
     * @param null|mixed   $classes
     * @param null|mixed   $familles
     * @param null|mixed   $number
     * @param null|mixed   $commissions
     * @param null|mixed   $groupements_territoriaux
     * @param null|mixed   $preventionniste
     *
     * @return array
     */
    public function extractionEtablissements($label = null, $identifiant = null, $genres = null, $categories = null, $classes = null, $familles = null, $types_activites = null, $avis_favorable = null, $statuts = null, $local_sommeil = null, $lon = null, $lat = null, $parent = null, $city = null, $street_id = null, $number = null, $commissions = null, $groupements_territoriaux = null, $preventionniste = null)
    {
        // Récupération de la ressource cache à partir du bootstrap
        $cache = Zend_Controller_Front::getInstance()->getParam('bootstrap')->getResource('cacheSearch');

        // Identifiant de la recherche
        $search_id = 'extract_etablissements_'.md5(serialize(func_get_args()));

        if (($results = unserialize($cache->load($search_id))) === false) {
            // Création de l'objet recherche
            $select = new Zend_Db_Select(Zend_Controller_Front::getInstance()->getParam('bootstrap')->getResource('db'));

            // Requête principale
            $select->from(['e' => 'etablissement'], ['NUMEROID_ETABLISSEMENT'])
                ->columns([
                    'DATE_PREMIER_AVIS_FAVORABLE' => new Zend_Db_Expr('(SELECT MIN(dossier.DATEVISITE_DOSSIER)
                                FROM dossier
                                INNER JOIN dossiernature ON dossier.ID_DOSSIER = dossiernature.ID_DOSSIER
                                INNER JOIN etablissementdossier ON dossier.ID_DOSSIER = etablissementdossier.ID_DOSSIER
                                WHERE dossiernature.ID_NATURE IN (19, 21, 23, 24, 26, 28, 29, 47, 48) AND dossier.AVIS_DOSSIER_COMMISSION = 1 AND etablissementdossier.ID_ETABLISSEMENT = e.ID_ETABLISSEMENT)'),
                    'DATE_DERNIERE_VISITE' => new Zend_Db_Expr('(SELECT MAX(dossier.DATEVISITE_DOSSIER)
                                FROM dossier
                    INNER JOIN dossiernature ON dossier.ID_DOSSIER = dossiernature.ID_DOSSIER
                                INNER JOIN etablissementdossier ON dossier.ID_DOSSIER = etablissementdossier.ID_DOSSIER
                                WHERE dossiernature.ID_NATURE IN (21, 26, 47, 48) AND dossier.DATEVISITE_DOSSIER < CURDATE() AND dossier.DATESUPPRESSION_DOSSIER IS NULL AND etablissementdossier.ID_ETABLISSEMENT = e.ID_ETABLISSEMENT)'),
                    'DATE_VISITE_PREVUE' => new Zend_Db_Expr('(SELECT MAX(dossier.DATEVISITE_DOSSIER)
                        FROM dossier
                        INNER JOIN dossiernature ON dossier.ID_DOSSIER = dossiernature.ID_DOSSIER
                        INNER JOIN etablissementdossier ON dossier.ID_DOSSIER = etablissementdossier.ID_DOSSIER
                        WHERE dossiernature.ID_NATURE IN (21, 26, 47, 48) AND dossier.DATEVISITE_DOSSIER >= CURDATE() AND dossier.DATESUPPRESSION_DOSSIER IS NULL AND etablissementdossier.ID_ETABLISSEMENT = e.ID_ETABLISSEMENT)'),
                    'DATE_DERNIER_AVIS' => new Zend_Db_Expr('(SELECT CASE
                    WHEN d.DATEVISITE_DOSSIER IS NOT NULL THEN (SELECT dossier.DATEVISITE_DOSSIER FROM dossier where dossier.ID_DOSSIER = d.ID_DOSSIER)
                        WHEN d.DATECOMM_DOSSIER IS NOT NULL THEN (SELECT dossier.DATECOMM_DOSSIER FROM dossier where dossier.ID_DOSSIER = d.ID_DOSSIER)
                        WHEN d.DATEINSERT_DOSSIER IS NOT NULL THEN (SELECT dossier.DATEINSERT_DOSSIER FROM dossier where dossier.ID_DOSSIER = d.ID_DOSSIER)
                    END
                                FROM dossier d
                                WHERE d.ID_DOSSIER = e.ID_DOSSIER_DONNANT_AVIS)'),
                    'DATE_PREMIER_AVIS_DEFAVORABLE_CONSECUTIF' => new Zend_Db_Expr('(SELECT DISTINCT d1.DATEVISITE_DOSSIER FROM dossier d1 LEFT JOIN etablissementdossier ed ON d1.ID_DOSSIER = ed.ID_DOSSIER
                    WHERE d1.DATEVISITE_DOSSIER = (select min(DATEVISITE_DOSSIER) AS date_visite_defavorable_mini from dossier d2 INNER JOIN etablissementdossier on etablissementdossier.ID_DOSSIER = d2.ID_DOSSIER
                        WHERE d2.AVIS_DOSSIER_COMMISSION = 2 and d2.TYPE_DOSSIER = 2 and etablissementdossier.ID_ETABLISSEMENT = e.ID_ETABLISSEMENT
                        and not exists (SELECT * FROM dossier INNER JOIN etablissementdossier on etablissementdossier.ID_DOSSIER = dossier.ID_DOSSIER
                                WHERE dossier.AVIS_DOSSIER_COMMISSION = 1 and dossier.TYPE_DOSSIER = 2 and dossier.DATEVISITE_DOSSIER >= d2.DATEVISITE_DOSSIER and etablissementdossier.ID_ETABLISSEMENT = e.ID_ETABLISSEMENT))
                    AND ed.ID_ETABLISSEMENT = e.ID_ETABLISSEMENT)'), ])
                ->join('etablissementinformations', 'e.ID_ETABLISSEMENT = etablissementinformations.ID_ETABLISSEMENT AND etablissementinformations.DATE_ETABLISSEMENTINFORMATIONS = ( SELECT MAX(etablissementinformations.DATE_ETABLISSEMENTINFORMATIONS) FROM etablissementinformations WHERE etablissementinformations.ID_ETABLISSEMENT = e.ID_ETABLISSEMENT )')
                ->joinLeft('dossier', 'e.ID_DOSSIER_DONNANT_AVIS = dossier.ID_DOSSIER', ['DATEVISITE_DOSSIER', 'DATECOMM_DOSSIER', 'DATEINSERT_DOSSIER', 'DIFFEREAVIS_DOSSIER'])
                ->joinLeft('avis', 'dossier.AVIS_DOSSIER_COMMISSION = avis.ID_AVIS')
                ->joinLeft('categorie', 'etablissementinformations.ID_CATEGORIE = categorie.ID_CATEGORIE', 'LIBELLE_CATEGORIE')
                ->joinLeft('type', 'etablissementinformations.ID_TYPE = type.ID_TYPE', 'LIBELLE_TYPE')
                ->joinLeft('typeactivite', 'etablissementinformations.ID_TYPEACTIVITE = typeactivite.ID_TYPEACTIVITE', 'LIBELLE_ACTIVITE')
                ->joinLeft('commission', 'etablissementinformations.ID_COMMISSION = commission.ID_COMMISSION', 'LIBELLE_COMMISSION')
                ->joinLeft('statut', 'etablissementinformations.ID_STATUT = statut.ID_STATUT', 'LIBELLE_STATUT')
                ->join('genre', 'etablissementinformations.ID_GENRE = genre.ID_GENRE', 'LIBELLE_GENRE')
                ->joinLeft('etablissementadresse', 'e.ID_ETABLISSEMENT = etablissementadresse.ID_ETABLISSEMENT', ['NUMINSEE_COMMUNE', 'ID_ADRESSE', 'ID_RUE', 'NUMERO_ADRESSE', 'COMPLEMENT_ADRESSE'])
                ->joinLeft('adresserue', 'adresserue.ID_RUE = etablissementadresse.ID_RUE', 'LIBELLE_RUE')
                ->joinLeft('adressecommune', 'etablissementadresse.NUMINSEE_COMMUNE = adressecommune.NUMINSEE_COMMUNE', ['CODEPOSTAL_COMMUNE', 'LIBELLE_COMMUNE'])
                ->joinLeft('groupementcommune', 'groupementcommune.NUMINSEE_COMMUNE = adressecommune.NUMINSEE_COMMUNE')
                ->joinLeft('groupement', 'groupement.ID_GROUPEMENT = groupementcommune.ID_GROUPEMENT AND groupement.ID_GROUPEMENTTYPE = 5', 'LIBELLE_GROUPEMENT')
                ->joinLeft('etablissementlie', 'e.ID_ETABLISSEMENT = etablissementlie.ID_FILS_ETABLISSEMENT')
                ->joinLeft(['etablissementinformationspere' => 'etablissementinformations'], 'etablissementinformationspere.ID_ETABLISSEMENT = etablissementlie.ID_ETABLISSEMENT', ['LIBELLE_ETABLISSEMENT_PERE' => 'LIBELLE_ETABLISSEMENTINFORMATIONS'])
                ->joinLeft(['etablissementadressesite' => 'etablissementadresse'], 'etablissementadressesite.ID_ETABLISSEMENT = (SELECT ID_FILS_ETABLISSEMENT FROM etablissementlie WHERE ID_ETABLISSEMENT = e.ID_ETABLISSEMENT LIMIT 1)', 'ID_RUE AS ID_RUE_SITE')
                ->joinLeft(['adressecommunesite' => 'adressecommune'], 'etablissementadressesite.NUMINSEE_COMMUNE = adressecommunesite.NUMINSEE_COMMUNE', 'LIBELLE_COMMUNE AS LIBELLE_COMMUNE_ADRESSE_SITE')
                ->joinLeft(['etablissementadressecell' => 'etablissementadresse'], 'etablissementadressecell.ID_ETABLISSEMENT = (SELECT ID_ETABLISSEMENT FROM etablissementlie WHERE ID_FILS_ETABLISSEMENT = e.ID_ETABLISSEMENT LIMIT 1)', 'ID_RUE AS ID_RUE_CELL')
                ->joinLeft(['adressecommunecell' => 'adressecommune'], 'etablissementadressecell.NUMINSEE_COMMUNE = adressecommunecell.NUMINSEE_COMMUNE', 'LIBELLE_COMMUNE AS LIBELLE_COMMUNE_ADRESSE_CELLULE')
                ->joinLeft('etablissementinformationspreventionniste', 'etablissementinformations.ID_ETABLISSEMENTINFORMATIONS = etablissementinformationspreventionniste.ID_ETABLISSEMENTINFORMATIONS')
                ->joinLeft('utilisateur', 'etablissementinformationspreventionniste.ID_UTILISATEUR = utilisateur.ID_UTILISATEUR')
                ->joinLeft('utilisateurinformations', 'utilisateurinformations.ID_UTILISATEURINFORMATIONS = utilisateur.ID_UTILISATEURINFORMATIONS', ['NOM_UTILISATEURINFORMATIONS', 'PRENOM_UTILISATEURINFORMATIONS'])
                ->where('e.DATESUPPRESSION_ETABLISSEMENT IS NULL')
                ->order('adressecommune.LIBELLE_COMMUNE ASC')
                ->order('categorie.LIBELLE_CATEGORIE ASC')
                ->order('type.LIBELLE_TYPE ASC')
                ->order('statut.LIBELLE_STATUT ASC')
                ->order('etablissementinformations.LIBELLE_ETABLISSEMENTINFORMATIONS ASC')
                ->group('e.ID_ETABLISSEMENT')
            ;

            // Critères : nom de l'établissement
            if (null !== $label) {
                $cleanLabel = trim($label);

                // recherche par id
                if ('#' === substr($cleanLabel, 0, 1)) {
                    $this->setCriteria($select, 'e.NUMEROID_ETABLISSEMENT', substr($cleanLabel, 1), false);

                // on test si la chaine contient uniquement des caractères de type identifiant sans espace
                } elseif (1 === preg_match('/^[E0-9\/\-\.]+([0-9A-Z]{1,2})?$/', $cleanLabel)) {
                    $this->setCriteria($select, 'e.NUMEROID_ETABLISSEMENT', $cleanLabel, false);

                // cas par défaut
                } else {
                    $this->setCriteria($select, 'etablissementinformations.LIBELLE_ETABLISSEMENTINFORMATIONS', $cleanLabel, false);
                }
            }

            // Critères : identifiant
            if (null !== $identifiant) {
                $this->setCriteria($select, 'e.NUMEROID_ETABLISSEMENT', $identifiant);
            }

            // Critères : genre
            if (null !== $genres) {
                $this->setCriteria($select, 'genre.ID_GENRE', $genres);
            }

            // Critères : catégorie
            if (null !== $categories) {
                $this->setCriteria($select, 'categorie.ID_CATEGORIE', $categories);
            }

            // Critères : classe
            if (null !== $classes) {
                $this->setCriteria($select, 'etablissementinformations.ID_CLASSE', $classes);
            }

            // Critères : famille
            if (null !== $familles) {
                $this->setCriteria($select, 'etablissementinformations.ID_FAMILLE', $familles);
            }

            // Critères : type
            if (null !== $types_activites) {
                $this->setCriteria($select, 'typeactivite.ID_TYPEACTIVITE', $types_activites);
            }

            // Critères : avis favorable
            if (null !== $avis_favorable) {
                $this->setCriteria($select, 'avis.ID_AVIS', $avis_favorable ? 1 : 2);
            }

            // Critères : statuts
            if (null !== $statuts) {
                $this->setCriteria($select, 'etablissementinformations.ID_STATUT', $statuts);
            }

            // Critères : statuts
            if (null !== $local_sommeil) {
                $this->setCriteria($select, 'etablissementinformations.LOCALSOMMEIL_ETABLISSEMENTINFORMATIONS', $local_sommeil);
            }

            // Critères : numéro de rue
            if (null !== $number) {
                $clauses = [];
                $clauses[] = 'etablissementadresse.NUMERO_ADRESSE = '.$select->getAdapter()->quote($number);
                if (null == $genres || in_array('1', $genres)) {
                    $clauses[] = 'etablissementadressesite.NUMERO_ADRESSE = '.$select->getAdapter()->quote($number);
                }

                if (null == $genres || in_array('3', $genres)) {
                    $clauses[] = 'etablissementadressecell.NUMERO_ADRESSE = '.$select->getAdapter()->quote($number);
                }

                $select->where('('.implode(' OR ', $clauses).')');
            }

            // Critère : commune et rue
            if (null !== $street_id) {
                $clauses = [];
                $clauses[] = 'etablissementadresse.ID_RUE = '.$select->getAdapter()->quote($street_id);
                if (null == $genres || in_array('1', $genres)) {
                    $clauses[] = 'etablissementadressesite.ID_RUE = '.$select->getAdapter()->quote($street_id);
                }

                if (null == $genres || in_array('3', $genres)) {
                    $clauses[] = 'etablissementadressecell.ID_RUE = '.$select->getAdapter()->quote($street_id);
                }

                $select->where('('.implode(' OR ', $clauses).')');
            } elseif (null !== $city) {
                $clauses = [];
                $clauses[] = 'etablissementadresse.NUMINSEE_COMMUNE = '.$select->getAdapter()->quote($city);
                if (null == $genres || in_array('1', $genres)) {
                    $clauses[] = 'etablissementadressesite.NUMINSEE_COMMUNE = '.$select->getAdapter()->quote($city);
                }

                if (null == $genres || in_array('3', $genres)) {
                    $clauses[] = 'etablissementadressecell.NUMINSEE_COMMUNE = '.$select->getAdapter()->quote($city);
                }

                $select->where('('.implode(' OR ', $clauses).')');
            }

            // Critère : commission
            if (null !== $commissions) {
                $this->setCriteria($select, 'commission.ID_COMMISSION', $commissions);
            }

            // Critère : groupement territorial
            if (null !== $groupements_territoriaux) {
                $this->setCriteria($select, 'groupement.ID_GROUPEMENT', $groupements_territoriaux);
            }

            // Critères : géolocalisation
            if (null !== $lon && null !== $lat) {
                $this->setCriteria($select, 'etablissementadresse.LON_ETABLISSEMENTADRESSE', $lon);
                $this->setCriteria($select, 'etablissementadresse.LAT_ETABLISSEMENTADRESSE', $lat);
            }

            // Critères : parent
            if (null !== $parent) {
                $select->where(0 == $parent ? 'etablissementlie.ID_ETABLISSEMENT IS NULL' : 'etablissementlie.ID_ETABLISSEMENT = ?', $parent);
            }

            // Critère : preventionniste
            if (null !== $preventionniste) {
                $select->where('etablissementinformationspreventionniste.ID_UTILISATEUR = '.$preventionniste);
            }

            // Performance optimisation : avoid sorting on big queries, and sort only if
            // there is at least one where part
            if (count($select->getPart(Zend_Db_Select::WHERE)) > 0) {
                $select->order('etablissementinformations.LIBELLE_ETABLISSEMENTINFORMATIONS ASC');
            }

            // Construction du résultat
            $rows_counter = new Zend_Paginator_Adapter_DbSelect($select);
            $results = [
                'results' => $select->query()->fetchAll(),
                'search_metadata' => [
                    'search_id' => $search_id,
                    'count' => count($rows_counter),
                ],
            ];

            $cache->save(serialize($results));
        }

        return $results;
    }

    /**
     * Recherche des dossiers.
     *
     * @param array      $types
     * @param string     $objet
     * @param string     $num_doc_urba
     * @param int        $parent       Id d'un dossier parent
     * @param bool       $avis_differe Avis différé
     * @param int        $count        Par défaut 10, max 100
     * @param int        $page         par défaut = 1
     * @param null|mixed $criterias
     *
     * @return array
     */
    public function dossiers($types = null, $objet = null, $num_doc_urba = null, $parent = null, $avis_differe = null, $count = 10, $page = 1, $criterias = null)
    {
        // Récupération de la ressource cache à partir du bootstrap
        $cache = Zend_Controller_Front::getInstance()->getParam('bootstrap')->getResource('cacheSearch');

        // Identifiant de la recherche
        $search_id = 'search_dossiers_'.md5(serialize(func_get_args()));

        if (($results = unserialize($cache->load($search_id))) === false) {
            // Création de l'objet recherche
            $select = new Zend_Db_Select(Zend_Controller_Front::getInstance()->getParam('bootstrap')->getResource('db'));

            // Requête principale
            $select->from(['d' => 'dossier'])
                ->columns([
                    'NB_DOSS_LIES' => new Zend_Db_Expr('(SELECT COUNT(dossierlie.ID_DOSSIER2)
                        FROM dossier
                        INNER JOIN dossierlie ON dossier.ID_DOSSIER = dossierlie.ID_DOSSIER1
                        WHERE dossier.ID_DOSSIER = d.ID_DOSSIER)'),
                    'NB_URBA' => new Zend_Db_Expr("( SELECT group_concat(dossierdocurba.NUM_DOCURBA, ', ')
                        FROM dossier
                        INNER JOIN dossierdocurba ON dossierdocurba.ID_DOSSIER = dossier.ID_DOSSIER
                        WHERE dossier.ID_DOSSIER = d.ID_DOSSIER
                        LIMIT 1)"),
                    'ALERTE_RECEPTION_TRAVAUX' => new Zend_Db_Expr('(SELECT COUNT(dossierlie.ID_DOSSIER2)
                        FROM dossier
                        INNER JOIN dossierlie ON dossier.ID_DOSSIER = dossierlie.ID_DOSSIER1
                        INNER JOIN dossiernature ON dossierlie.ID_DOSSIER1 = dossiernature.ID_DOSSIER
                        WHERE (dossiernature.ID_NATURE = 2 OR dossiernature.ID_NATURE = 1 OR dossiernature.ID_NATURE = 13 OR dossiernature.ID_NATURE = 12) AND dossier.ID_DOSSIER = d.ID_DOSSIER)'),
                    'ECHEANCIER_TRAVAUX' => new Zend_Db_Expr('(SELECT COUNT(dossierlie.ID_DOSSIER1)
                        FROM dossier
                        INNER JOIN dossierlie ON dossier.ID_DOSSIER = dossierlie.ID_DOSSIER2
                        INNER JOIN dossiernature ON dossierlie.ID_DOSSIER1 = dossiernature.ID_DOSSIER
                        WHERE dossiernature.ID_NATURE = 46 AND dossier.ID_DOSSIER = d.ID_DOSSIER)'), ])
                ->joinLeft('dossierlie', 'd.ID_DOSSIER = dossierlie.ID_DOSSIER2')
                ->joinLeft('commission', 'd.COMMISSION_DOSSIER = commission.ID_COMMISSION', 'LIBELLE_COMMISSION')
                ->join('dossiernature', 'dossiernature.ID_DOSSIER = d.ID_DOSSIER', [])
                ->join('dossiernatureliste', 'dossiernatureliste.ID_DOSSIERNATURE = dossiernature.ID_NATURE', ['LIBELLE_DOSSIERNATURE', 'ID_DOSSIERNATURE'])
                ->join('dossiertype', 'dossiertype.ID_DOSSIERTYPE = dossiernatureliste.ID_DOSSIERTYPE', 'LIBELLE_DOSSIERTYPE')
                ->joinLeft(['e' => 'etablissementdossier'], 'd.ID_DOSSIER = e.ID_DOSSIER', [])
                ->joinLeft(['ei' => new Zend_Db_Expr('(SELECT MAX(etablissementinformations.DATE_ETABLISSEMENTINFORMATIONS), etablissementinformations.* FROM etablissementinformations group by ID_ETABLISSEMENT)')], 'e.ID_ETABLISSEMENT = ei.ID_ETABLISSEMENT', ['LIBELLE_ETABLISSEMENTINFORMATIONS', 'ID_ETABLISSEMENT'])
                ->joinLeft('type', 'type.ID_TYPE = ei.ID_TYPE', ['ID_TYPE', 'LIBELLE_TYPE'])
                ->joinLeft('genre', 'genre.ID_GENRE = ei.ID_GENRE', 'LIBELLE_GENRE')
                ->joinLeft('avis', 'd.AVIS_DOSSIER_COMMISSION = avis.ID_AVIS')
                ->joinLeft('dossierdocurba', 'dossierdocurba.ID_DOSSIER = d.ID_DOSSIER', [])
                ->joinLeft('dossieraffectation', 'dossieraffectation.ID_DOSSIER_AFFECT = d.ID_DOSSIER', [])
                ->joinLeft('datecommission', 'datecommission.ID_DATECOMMISSION = dossieraffectation.ID_DATECOMMISSION_AFFECT', [])
                ->joinLeft('dossierpreventionniste', 'dossierpreventionniste.ID_DOSSIER = d.ID_DOSSIER', [])
                ->joinLeft(['ea' => 'etablissementadresse'], 'ea.ID_ETABLISSEMENT = e.ID_ETABLISSEMENT', [])
                ->joinLeft('adressecommune', 'ea.NUMINSEE_COMMUNE = adressecommune.NUMINSEE_COMMUNE', ['CODEPOSTAL_COMMUNE', 'LIBELLE_COMMUNE'])
                ->joinLeft('groupementcommune', 'groupementcommune.NUMINSEE_COMMUNE = adressecommune.NUMINSEE_COMMUNE', [])
                ->joinLeft('groupement', 'groupement.ID_GROUPEMENT = groupementcommune.ID_GROUPEMENT', 'LIBELLE_GROUPEMENT')
                ->joinLeft('platauconsultation', 'd.ID_PLATAU = platauconsultation.ID_PLATAU', 'DATE_REPONSE_ATTENDUE')
                ->where('d.DATESUPPRESSION_DOSSIER IS NULL')
                ->group('d.ID_DOSSIER')
            ;

            // Critères : numéro de doc urba
            if (null !== $num_doc_urba) {
                $select->having('NB_URBA like ?', sprintf('%%%s%%', $num_doc_urba));
            }

            // Critères : objet
            if (null !== $objet) {
                $cleanObjet = trim($objet);

                // recherche par id
                if ('#' === substr($cleanObjet, 0, 1)) {
                    $select->having('NB_URBA like ?', '%'.substr($cleanObjet, 1).'%');
                // on test si la chaine contient uniquement des caractères de type identifiant sans espace
                } elseif (1 === preg_match('/^[0-9A-Z\.]+$/', $cleanObjet)) {
                    $select->having('NB_URBA like ?', '%'.$cleanObjet.'%');
                // cas par défaut
                } else {
                    $this->setCriteria($select, 'OBJET_DOSSIER', $cleanObjet, false);
                }
            }

            // Critères : parent
            if (null !== $parent) {
                $select->where(0 == $parent ? 'dossierlie.ID_DOSSIER1 IS NULL' : 'dossierlie.ID_DOSSIER1 = ?', $parent);
            }

            // Critères : type
            if (null !== $types) {
                $this->setCriteria($select, 'dossiertype.ID_DOSSIERTYPE', $types);
            }

            // Critères : avis différé
            if (null !== $avis_differe) {
                $this->setCriteria($select, 'd.DIFFEREAVIS_DOSSIER', $avis_differe);
            }

            // Critères : commissions
            if (isset($criterias['commissions']) && null !== $criterias['commissions']) {
                $this->setCriteria($select, 'datecommission.COMMISSION_CONCERNE', $criterias['commissions']);
            }

            // Critères : avis commission
            if (isset($criterias['avisCommission']) && null !== $criterias['avisCommission']) {
                $this->setCriteria($select, 'd.AVIS_DOSSIER_COMMISSION', $criterias['avisCommission']);
            }

            // Critères : avis rapporteur
            if (isset($criterias['avisRapporteur']) && null !== $criterias['avisRapporteur']) {
                $this->setCriteria($select, 'd.AVIS_DOSSIER', $criterias['avisRapporteur']);
            }

            // Critères : permis
            if (isset($criterias['permis']) && null !== $criterias['permis']) {
                $this->setCriteria($select, 'dossierdocurba.NUM_DOCURBA', $criterias['permis']);
            }

            // Critères : permis
            if (isset($criterias['preventionniste']) && null !== $criterias['preventionniste']) {
                $this->setCriteria($select, 'dossierpreventionniste.ID_PREVENTIONNISTE', $criterias['preventionniste']);
            }

            if (isset($criterias['commune']) && null !== $criterias['commune']) {
                $this->setCriteria($select, 'ea.NUMINSEE_COMMUNE', $criterias['commune']);
            }

            if (isset($criterias['voie']) && null !== $criterias['voie']) {
                $this->setCriteria($select, 'ea.ID_RUE', $criterias['voie']);
            }

            if (isset($criterias['numero']) && null !== $criterias['numero']) {
                $this->setCriteria($select, 'ea.NUMERO_ADRESSE', $criterias['numero']);
            }

            // Critère : groupement territorial
            if (isset($criterias['groupements_territoriaux']) && null !== $criterias['groupements_territoriaux']) {
                $this->setCriteria($select, 'groupement.ID_GROUPEMENT', $criterias['groupements_territoriaux']);
            }

            if (isset($criterias['dateCreationStart']) && null !== $criterias['dateCreationStart']) {
                $select->where("d.DATEINSERT_DOSSIER >= STR_TO_DATE (? , '%d/%m/%Y')", $criterias['dateCreationStart']);
            }

            if (isset($criterias['dateCreationEnd']) && null !== $criterias['dateCreationEnd']) {
                $select->where("d.DATEINSERT_DOSSIER <= STR_TO_DATE (? , '%d/%m/%Y')", $criterias['dateCreationEnd']);
            }

            if (isset($criterias['dateReceptionStart']) && null !== $criterias['dateReceptionStart']) {
                $select->where("d.DATESDIS_DOSSIER >= STR_TO_DATE (? , '%d/%m/%Y')", $criterias['dateReceptionStart']);
            }

            if (isset($criterias['dateReceptionEnd']) && null !== $criterias['dateReceptionEnd']) {
                $select->where("d.DATESDIS_DOSSIER <= STR_TO_DATE (? , '%d/%m/%Y')", $criterias['dateReceptionEnd']);
            }

            if (isset($criterias['dateReponseStart']) && null !== $criterias['dateReponseStart']) {
                $select->where("d.DATEREP_DOSSIER >= STR_TO_DATE (? , '%d/%m/%Y')", $criterias['dateReponseStart']);
            }

            if (isset($criterias['dateReponseEnd']) && null !== $criterias['dateReponseEnd']) {
                $select->where("d.DATEREP_DOSSIER <= STR_TO_DATE (? , '%d/%m/%Y')", $criterias['dateReponseEnd']);
            }

            if (isset($criterias['provenance']) && null !== $criterias['provenance']) {
                if ('1' === $criterias['provenance']) {
                    $select->where('d.ID_PLATAU IS NOT NULL');
                } elseif ('2' === $criterias['provenance']) {
                    $select->where('d.ID_PLATAU IS NULL');
                }
            }

            // Performance optimisation : avoid sorting on big queries, and sort only if
            // there is at least one where part
            if (count($select->getPart(Zend_Db_Select::WHERE)) > 1) {
                $select->order('d.DATEINSERT_DOSSIER DESC');
            }

            // Gestion des pages et du count
            $select->limitPage($page, $count > self::MAX_LIMIT_PAGES_DOSSIERS ? self::MAX_LIMIT_PAGES_DOSSIERS : $count);

            // Construction du résultat
            $rows_counter = new Zend_Paginator_Adapter_DbSelect($select);
            $results = [
                'results' => $select->query()->fetchAll(),
                'search_metadata' => [
                    'search_id' => $search_id,
                    'current_page' => $page,
                    'count' => count($rows_counter),
                ],
            ];

            $newResults = [];
            foreach ($results['results'] as $row) {
                $newResults[$row['ID_DOSSIER']] = $row;
            }

            $results['results'] = $newResults;

            $sIDsTable = [];
            foreach ($results['results'] as $row) {
                $sIDsTable[] = $row['ID_DOSSIER'];
            }

            // Si pas de dossier, pas de recherche
            if ([] !== $sIDsTable) {
                // Recherche des préventionnistes associés aux dossiers
                $selectPrev = new Zend_Db_Select(Zend_Controller_Front::getInstance()->getParam('bootstrap')->getResource('db'));
                $selectPrev->from(['u' => 'utilisateur'], 'ID_UTILISATEUR')
                    ->join(['ui' => 'utilisateurinformations'], 'u.ID_UTILISATEURINFORMATIONS = ui.ID_UTILISATEURINFORMATIONS', ['PRENOM_UTILISATEURINFORMATIONS', 'NOM_UTILISATEURINFORMATIONS'])
                    ->join('dossierpreventionniste', 'dossierpreventionniste.ID_PREVENTIONNISTE = u.ID_UTILISATEUR', 'ID_DOSSIER')
                ;

                $selectPrev->Where('dossierpreventionniste.ID_DOSSIER IN (?)', $sIDsTable);

                $preventionnistes = $selectPrev->query()->fetchAll();
                foreach ($preventionnistes as $prev) {
                    if (null != $prev['ID_DOSSIER']) {
                        if (!isset($results['results'][$prev['ID_DOSSIER']]['PREVENTIONNISTES'])) {
                            $results['results'][$prev['ID_DOSSIER']]['PREVENTIONNISTES'] = [];
                        }

                        $results['results'][$prev['ID_DOSSIER']]['PREVENTIONNISTES'][] = $prev;
                    }
                }

                // Recherche des pièces jointes associés aux dossiers
                $selectPj = new Zend_Db_Select(Zend_Controller_Front::getInstance()->getParam('bootstrap')->getResource('db'));
                $selectPj->from(['pj' => 'piecejointe'], ['NOM_PIECEJOINTE', 'EXTENSION_PIECEJOINTE'])
                    ->join('dossierpj', 'dossierpj.ID_PIECEJOINTE = pj.ID_PIECEJOINTE', 'ID_DOSSIER')
                ;

                $selectPj->Where('dossierpj.ID_DOSSIER IN (?)', $sIDsTable);

                $piecesjointes = $selectPj->query()->fetchAll();
                foreach ($piecesjointes as $pj) {
                    if (null != $pj['ID_DOSSIER']) {
                        if (!isset($results['results'][$pj['ID_DOSSIER']]['PIECESJOINTES'])) {
                            $results['results'][$pj['ID_DOSSIER']]['PIECESJOINTES'] = [];
                        }

                        $results['results'][$pj['ID_DOSSIER']]['PIECESJOINTES'][] = $pj;
                    }
                }
            }

            $cache->save(serialize($results));
        }

        return $results;
    }

    /**
     * Recherche des dossiers pour l'extraction Calc.
     *
     * @param array      $types
     * @param string     $objet
     * @param string     $num_doc_urba
     * @param int        $parent       Id d'un dossier parent
     * @param bool       $avis_differe Avis différé
     * @param null|mixed $criterias
     *
     * @return array
     */
    public function extractionDossiers($types = null, $objet = null, $num_doc_urba = null, $parent = null, $avis_differe = null, $criterias = null)
    {
        // Récupération de la ressource cache à partir du bootstrap
        $cache = Zend_Controller_Front::getInstance()->getParam('bootstrap')->getResource('cacheSearch');

        // Identifiant de la recherche
        $search_id = 'search_dossiers_'.md5(serialize(func_get_args()));

        if (($results = unserialize($cache->load($search_id))) === false) {
            // Création de l'objet recherche
            $select = new Zend_Db_Select(Zend_Controller_Front::getInstance()->getParam('bootstrap')->getResource('db'));

            // Requête principale
            $select->from(['d' => 'dossier'])
                ->columns([
                    'NB_DOSS_LIES' => new Zend_Db_Expr('(SELECT COUNT(dossierlie.ID_DOSSIER2)
                        FROM dossier
                        INNER JOIN dossierlie ON dossier.ID_DOSSIER = dossierlie.ID_DOSSIER1
                        WHERE dossier.ID_DOSSIER = d.ID_DOSSIER)'),
                    'NB_URBA' => new Zend_Db_Expr("( SELECT group_concat(dossierdocurba.NUM_DOCURBA, ', ')
                        FROM dossier
                        INNER JOIN dossierdocurba ON dossierdocurba.ID_DOSSIER = dossier.ID_DOSSIER
                        WHERE dossier.ID_DOSSIER = d.ID_DOSSIER
                        LIMIT 1)"),
                    'ALERTE_RECEPTION_TRAVAUX' => new Zend_Db_Expr('(SELECT COUNT(dossierlie.ID_DOSSIER2)
                        FROM dossier
                        INNER JOIN dossierlie ON dossier.ID_DOSSIER = dossierlie.ID_DOSSIER1
                        INNER JOIN dossiernature ON dossierlie.ID_DOSSIER1 = dossiernature.ID_DOSSIER
                        WHERE (dossiernature.ID_NATURE = 2 OR dossiernature.ID_NATURE = 1 OR dossiernature.ID_NATURE = 13 OR dossiernature.ID_NATURE = 12) AND dossier.ID_DOSSIER = d.ID_DOSSIER)'),
                    'ECHEANCIER_TRAVAUX' => new Zend_Db_Expr('(SELECT COUNT(dossierlie.ID_DOSSIER1)
                        FROM dossier
                        INNER JOIN dossierlie ON dossier.ID_DOSSIER = dossierlie.ID_DOSSIER2
                        INNER JOIN dossiernature ON dossierlie.ID_DOSSIER1 = dossiernature.ID_DOSSIER
                        WHERE dossiernature.ID_NATURE = 46 AND dossier.ID_DOSSIER = d.ID_DOSSIER)'), ])
                ->joinLeft('dossierlie', 'd.ID_DOSSIER = dossierlie.ID_DOSSIER2')
                ->joinLeft('commission', 'd.COMMISSION_DOSSIER = commission.ID_COMMISSION', 'LIBELLE_COMMISSION')
                ->join('dossiernature', 'dossiernature.ID_DOSSIER = d.ID_DOSSIER', [])
                ->join('dossiernatureliste', 'dossiernatureliste.ID_DOSSIERNATURE = dossiernature.ID_NATURE', ['LIBELLE_DOSSIERNATURE', 'ID_DOSSIERNATURE'])
                ->join('dossiertype', 'dossiertype.ID_DOSSIERTYPE = dossiernatureliste.ID_DOSSIERTYPE', 'LIBELLE_DOSSIERTYPE')
                ->join(['ed' => 'etablissementdossier'], 'd.ID_DOSSIER = ed.ID_DOSSIER', [])
                ->join(['e' => 'etablissement'], 'ed.ID_ETABLISSEMENT = e.ID_ETABLISSEMENT', 'NUMEROID_ETABLISSEMENT')
                ->join(['ei' => 'etablissementinformations'], 'e.ID_ETABLISSEMENT = ei.ID_ETABLISSEMENT AND ei.DATE_ETABLISSEMENTINFORMATIONS = ( SELECT MAX(etablissementinformations.DATE_ETABLISSEMENTINFORMATIONS) FROM etablissementinformations WHERE etablissementinformations.ID_ETABLISSEMENT = e.ID_ETABLISSEMENT )', ['LIBELLE_ETABLISSEMENTINFORMATIONS', 'ID_ETABLISSEMENT', 'ID_CATEGORIE'])
                ->joinLeft('categorie', 'categorie.ID_CATEGORIE = ei.ID_CATEGORIE', 'LIBELLE_CATEGORIE')
                ->joinLeft('type', 'type.ID_TYPE = ei.ID_TYPE', ['ID_TYPE', 'LIBELLE_TYPE AS LIBELLE_TYPE_ETABLISSEMENT'])
                ->joinLeft('typeactivite', 'ei.ID_TYPEACTIVITE = typeactivite.ID_TYPEACTIVITE', 'LIBELLE_ACTIVITE')
                ->joinLeft('statut', 'ei.ID_STATUT = statut.ID_STATUT', 'LIBELLE_STATUT')
                ->join('genre', 'genre.ID_GENRE = ei.ID_GENRE', 'LIBELLE_GENRE')
                ->joinLeft(['ar' => 'avis'], 'd.AVIS_DOSSIER = ar.ID_AVIS', 'LIBELLE_AVIS AS LIBELLE_AVIS_RAPPORTEUR')
                ->joinLeft(['ac' => 'avis'], 'd.AVIS_DOSSIER_COMMISSION = ac.ID_AVIS', 'LIBELLE_AVIS AS LIBELLE_AVIS_COMMISSION')
                ->joinLeft('dossierdocurba', 'dossierdocurba.ID_DOSSIER = d.ID_DOSSIER', 'NUM_DOCURBA')
                ->joinLeft('dossieraffectation', 'dossieraffectation.ID_DOSSIER_AFFECT = d.ID_DOSSIER', [])
                ->joinLeft('datecommission', 'datecommission.ID_DATECOMMISSION = dossieraffectation.ID_DATECOMMISSION_AFFECT', [])
                ->joinLeft('dossierpreventionniste', 'dossierpreventionniste.ID_DOSSIER = d.ID_DOSSIER', [])
                ->joinLeft('utilisateur', 'dossierpreventionniste.ID_PREVENTIONNISTE = utilisateur.ID_UTILISATEUR')
                ->joinLeft('utilisateurinformations', 'utilisateurinformations.ID_UTILISATEURINFORMATIONS = utilisateur.ID_UTILISATEURINFORMATIONS', ['NOM_UTILISATEURINFORMATIONS', 'PRENOM_UTILISATEURINFORMATIONS'])
                ->joinLeft(['ea' => 'etablissementadresse'], 'ea.ID_ETABLISSEMENT = ei.ID_ETABLISSEMENT', [])
                ->joinLeft('adressecommune', 'ea.NUMINSEE_COMMUNE = adressecommune.NUMINSEE_COMMUNE', ['CODEPOSTAL_COMMUNE', 'LIBELLE_COMMUNE'])
                ->joinLeft('groupementcommune', 'groupementcommune.NUMINSEE_COMMUNE = adressecommune.NUMINSEE_COMMUNE', [])
                ->joinLeft('groupement', 'groupement.ID_GROUPEMENT = groupementcommune.ID_GROUPEMENT', 'LIBELLE_GROUPEMENT')
                ->joinLeft('dossierpj', 'dossierpj.ID_DOSSIER = d.ID_DOSSIER', 'ID_PIECEJOINTE')
                ->where('d.DATESUPPRESSION_DOSSIER IS NULL')
                ->group('d.ID_DOSSIER')
            ;

            // Critères : numéro de doc urba
            if (null !== $num_doc_urba) {
                $select->having('NB_URBA like ?', sprintf('%%%s%%', $num_doc_urba));
            }

            // Critères : objet
            if (null !== $objet) {
                $cleanObjet = trim($objet);

                // recherche par id
                if ('#' === substr($cleanObjet, 0, 1)) {
                    $select->having('NB_URBA like ?', '%'.substr($cleanObjet, 1).'%');
                // on test si la chaine contient uniquement des caractères de type identifiant sans espace
                } elseif (1 === preg_match('/^[0-9A-Z\.]+$/', $cleanObjet)) {
                    $select->having('NB_URBA like ?', '%'.$cleanObjet.'%');
                // cas par défaut
                } else {
                    $this->setCriteria($select, 'OBJET_DOSSIER', $cleanObjet, false);
                }
            }

            // Critères : parent
            if (null !== $parent) {
                $select->where(0 == $parent ? 'dossierlie.ID_DOSSIER1 IS NULL' : 'dossierlie.ID_DOSSIER1 = ?', $parent);
            }

            // Critères : type
            if (null !== $types) {
                $this->setCriteria($select, 'dossiertype.ID_DOSSIERTYPE', $types);
            }

            // Critères : avis différé
            if (null !== $avis_differe) {
                $this->setCriteria($select, 'd.DIFFEREAVIS_DOSSIER', $avis_differe);
            }

            // Critères : commissions
            if (isset($criterias['commissions']) && null !== $criterias['commissions']) {
                $this->setCriteria($select, 'datecommission.COMMISSION_CONCERNE', $criterias['commissions']);
            }

            // Critères : avis commission
            if (isset($criterias['avisCommission']) && null !== $criterias['avisCommission']) {
                $this->setCriteria($select, 'd.AVIS_DOSSIER_COMMISSION', $criterias['avisCommission']);
            }

            // Critères : avis rapporteur
            if (isset($criterias['avisRapporteur']) && null !== $criterias['avisRapporteur']) {
                $this->setCriteria($select, 'd.AVIS_DOSSIER', $criterias['avisRapporteur']);
            }

            // Critères : avis différé
            if (isset($criterias['avisDiffere']) && null !== $criterias['avisDiffere']) {
                $this->setCriteria($select, 'd.DIFFEREAVIS_DOSSIER', $criterias['avisDiffere']);
            }

            // Critères : permis
            if (isset($criterias['permis']) && null !== $criterias['permis']) {
                $this->setCriteria($select, 'dossierdocurba.NUM_DOCURBA', $criterias['permis']);
            }

            // Critères : courrier
            if (isset($criterias['courrier']) && null !== $criterias['courrier']) {
                $this->setCriteria($select, 'd.REFCOURRIER_DOSSIER', $criterias['courrier'], false);
            }

            // Critères : preventionniste
            if (isset($criterias['preventionniste']) && null !== $criterias['preventionniste']) {
                $this->setCriteria($select, 'dossierpreventionniste.ID_PREVENTIONNISTE', $criterias['preventionniste']);
            }

            if (isset($criterias['commune']) && null !== $criterias['commune']) {
                $this->setCriteria($select, 'ea.NUMINSEE_COMMUNE', $criterias['commune']);
            }

            if (isset($criterias['voie']) && null !== $criterias['voie']) {
                $this->setCriteria($select, 'ea.ID_RUE', $criterias['voie']);
            }

            if (isset($criterias['numero']) && null !== $criterias['numero']) {
                $this->setCriteria($select, 'ea.NUMERO_ADRESSE', $criterias['numero']);
            }

            // Critère : groupement territorial
            if (isset($criterias['groupements_territoriaux']) && null !== $criterias['groupements_territoriaux']) {
                $this->setCriteria($select, 'groupement.ID_GROUPEMENT', $criterias['groupements_territoriaux']);
            }

            // Critères : nom de l'établissement
            if (isset($criterias['label']) && null !== $criterias['label']) {
                $cleanLabel = trim($criterias['label']);

                // recherche par id
                if ('#' === substr($cleanLabel, 0, 1)) {
                    $this->setCriteria($select, 'NUMEROID_ETABLISSEMENT', substr($cleanLabel, 1), false);

                // on test si la chaine contient uniquement des caractères de type identifiant sans espace
                } elseif (1 === preg_match('/^[E0-9\/\-\.]+([0-9A-Z]{1,2})?$/', $cleanLabel)) {
                    $this->setCriteria($select, 'NUMEROID_ETABLISSEMENT', $cleanLabel, false);

                // cas par défaut
                } else {
                    $this->setCriteria($select, 'LIBELLE_ETABLISSEMENTINFORMATIONS', $cleanLabel, false);
                }
            }

            // Critères : identifiant
            if (isset($criterias['identifiant']) && null !== $criterias['identifiant']) {
                $this->setCriteria($select, 'NUMEROID_ETABLISSEMENT', $criterias['identifiant']);
            }

            if (isset($criterias['dateCreationStart']) && null !== $criterias['dateCreationStart']) {
                $select->where("d.DATEINSERT_DOSSIER >= STR_TO_DATE (? , '%d/%m/%Y')", $criterias['dateCreationStart']);
            }

            if (isset($criterias['dateCreationEnd']) && null !== $criterias['dateCreationEnd']) {
                $select->where("d.DATEINSERT_DOSSIER <= STR_TO_DATE (? , '%d/%m/%Y')", $criterias['dateCreationEnd']);
            }

            if (isset($criterias['dateReceptionStart']) && null !== $criterias['dateReceptionStart']) {
                $select->where("d.DATESDIS_DOSSIER >= STR_TO_DATE (? , '%d/%m/%Y')", $criterias['dateReceptionStart']);
            }

            if (isset($criterias['dateReceptionEnd']) && null !== $criterias['dateReceptionEnd']) {
                $select->where("d.DATESDIS_DOSSIER <= STR_TO_DATE (? , '%d/%m/%Y')", $criterias['dateReceptionEnd']);
            }

            if (isset($criterias['dateReponseStart']) && null !== $criterias['dateReponseStart']) {
                $select->where("d.DATEREP_DOSSIER >= STR_TO_DATE (? , '%d/%m/%Y')", $criterias['dateReponseStart']);
            }

            if (isset($criterias['dateReponseEnd']) && null !== $criterias['dateReponseEnd']) {
                $select->where("d.DATEREP_DOSSIER <= STR_TO_DATE (? , '%d/%m/%Y')", $criterias['dateReponseEnd']);
            }

            if (isset($criterias['dateCommissionStart']) && null !== $criterias['dateCommissionStart']) {
                $select->where("d.DATECOMM_DOSSIER >= STR_TO_DATE (? , '%d/%m/%Y')", $criterias['dateCommissionStart']);
            }

            if (isset($criterias['dateCommissionEnd']) && null !== $criterias['dateCommissionEnd']) {
                $select->where("d.DATECOMM_DOSSIER <= STR_TO_DATE (? , '%d/%m/%Y')", $criterias['dateCommissionEnd']);
            }

            if (isset($criterias['dateVisiteStart']) && null !== $criterias['dateVisiteStart']) {
                $select->where("d.DATEVISITE_DOSSIER >= STR_TO_DATE (? , '%d/%m/%Y')", $criterias['dateVisiteStart']);
            }

            if (isset($criterias['dateVisiteEnd']) && null !== $criterias['dateVisiteEnd']) {
                $select->where("d.DATEVISITE_DOSSIER <= STR_TO_DATE (? , '%d/%m/%Y')", $criterias['dateVisiteEnd']);
            }

            if (isset($criterias['provenance']) && null !== $criterias['provenance']) {
                if ('1' === $criterias['provenance']) {
                    $select->where('d.ID_PLATAU IS NOT NULL');
                } elseif ('2' === $criterias['provenance']) {
                    $select->where('d.ID_PLATAU IS NULL');
                }
            }

            $select->order('adressecommune.LIBELLE_COMMUNE ASC')
                ->order('categorie.LIBELLE_CATEGORIE ASC')
                ->order('type.LIBELLE_TYPE ASC')
                ->order('ei.LIBELLE_ETABLISSEMENTINFORMATIONS ASC')
                ->order('d.DATEINSERT_DOSSIER DESC')
            ;

            // Construction du résultat
            $rows_counter = new Zend_Paginator_Adapter_DbSelect($select);
            $results = [
                'results' => $select->query()->fetchAll(),
                'search_metadata' => [
                    'search_id' => $search_id,
                    'count' => count($rows_counter),
                ],
            ];

            $cache->save(serialize($results));
        }

        return $results;
    }

    /**
     * Recherche des courriers.
     *
     * @param string $objet
     * @param string $num_doc_urba
     * @param int    $parent       Id d'un dossier parent
     * @param int    $count        Par défaut 10, max 100
     * @param int    $page         par défaut = 1
     *
     * @return array
     */
    public function courriers($objet = null, $num_doc_urba = null, $parent = null, $count = 10, $page = 1)
    {
        // Récupération de la ressource cache à partir du bootstrap
        $cache = Zend_Controller_Front::getInstance()->getParam('bootstrap')->getResource('cacheSearch');

        // Identifiant de la recherche
        $search_id = 'search_dossiers_'.md5(serialize(func_get_args()));

        if (($results = unserialize($cache->load($search_id))) === false) {
            // Création de l'objet recherche
            $select = new Zend_Db_Select(Zend_Controller_Front::getInstance()->getParam('bootstrap')->getResource('db'));

            // Requête principale
            $select->from(['d' => 'dossier'])
                ->columns([
                    'NB_DOSS_LIES' => new Zend_Db_Expr('(SELECT COUNT(dossierlie.ID_DOSSIER2)
                        FROM dossier
                        INNER JOIN dossierlie ON dossier.ID_DOSSIER = dossierlie.ID_DOSSIER1
                        WHERE dossier.ID_DOSSIER = d.ID_DOSSIER)'),
                    'NB_URBA' => new Zend_Db_Expr("( SELECT group_concat(dossierdocurba.NUM_DOCURBA, ', ')
                        FROM dossier
                        INNER JOIN dossierdocurba ON dossierdocurba.ID_DOSSIER = dossier.ID_DOSSIER
                        WHERE dossier.ID_DOSSIER = d.ID_DOSSIER
                        LIMIT 1)"),
                    'ALERTE_RECEPTION_TRAVAUX' => new Zend_Db_Expr('(SELECT COUNT(dossierlie.ID_DOSSIER2)
                        FROM dossier
                        INNER JOIN dossierlie ON dossier.ID_DOSSIER = dossierlie.ID_DOSSIER1
                        INNER JOIN dossiernature ON dossierlie.ID_DOSSIER1 = dossiernature.ID_DOSSIER
                        WHERE (dossiernature.ID_NATURE = 2 OR dossiernature.ID_NATURE = 1 OR dossiernature.ID_NATURE = 13 OR dossiernature.ID_NATURE = 12) AND dossier.ID_DOSSIER = d.ID_DOSSIER)'),
                    'ECHEANCIER_TRAVAUX' => new Zend_Db_Expr('(SELECT COUNT(dossierlie.ID_DOSSIER1)
                        FROM dossier
                        INNER JOIN dossierlie ON dossier.ID_DOSSIER = dossierlie.ID_DOSSIER2
                        INNER JOIN dossiernature ON dossierlie.ID_DOSSIER1 = dossiernature.ID_DOSSIER
                        WHERE dossiernature.ID_NATURE = 46 AND dossier.ID_DOSSIER = d.ID_DOSSIER)'), ])
                ->joinLeft('dossierlie', 'd.ID_DOSSIER = dossierlie.ID_DOSSIER2')
                ->join('dossiernature', 'dossiernature.ID_DOSSIER = d.ID_DOSSIER', [])
                ->join('dossiernatureliste', 'dossiernatureliste.ID_DOSSIERNATURE = dossiernature.ID_NATURE', ['LIBELLE_DOSSIERNATURE', 'ID_DOSSIERNATURE'])
                ->join('dossiertype', 'dossiertype.ID_DOSSIERTYPE = dossiernatureliste.ID_DOSSIERTYPE', 'LIBELLE_DOSSIERTYPE')
                ->joinLeft(['e' => 'etablissementdossier'], 'd.ID_DOSSIER = e.ID_DOSSIER', [])
                ->joinLeft('avis', 'd.AVIS_DOSSIER_COMMISSION = avis.ID_AVIS')
                ->group('d.ID_DOSSIER')
            ;

            // Critères : numéro de doc urba
            if (null !== $num_doc_urba) {
                $this->setCriteria($select, 'NUM_DOCURBA', $num_doc_urba);
            }

            if (null !== $objet) {
                $select->where(sprintf("DEMANDEUR_DOSSIER LIKE '%%%s%%' OR OBJET_DOSSIER LIKE '%%%s%%'", $objet, $objet));
            }

            // Critères : parent
            if (null !== $parent) {
                $select->where(0 == $parent ? 'dossierlie.ID_DOSSIER1 IS NULL' : 'dossierlie.ID_DOSSIER1 = ?', $parent);
            }

            // Critères : type
            $this->setCriteria($select, 'dossiertype.ID_DOSSIERTYPE', 5);

            // Gestion des pages et du count
            $select->limitPage($page, $count > self::MAX_LIMIT_PAGES_DOSSIERS ? self::MAX_LIMIT_PAGES_DOSSIERS : $count);
            // Construction du résultat
            $rows_counter = new Zend_Paginator_Adapter_DbSelect($select);
            $results = [
                'results' => $select->query()->fetchAll(),
                'search_metadata' => [
                    'search_id' => $search_id,
                    'current_page' => $page,
                    'count' => count($rows_counter),
                ],
            ];
        }

        return $results;
    }

    /**
     * Recherche des utilisateurs.
     *
     * @param array|string $fonctions
     * @param string       $name
     * @param array|int    $groups
     * @param bool         $actif     Optionnel
     * @param int          $count     Par défaut 10, max 100
     * @param int          $page      par défaut = 1
     *
     * @return array
     */
    public function users($fonctions = null, $name = null, $groups = null, $actif = true, $count = 10, $page = 1)
    {
        // Récupération de la ressource cache à partir du bootstrap
        $cache = Zend_Controller_Front::getInstance()->getParam('bootstrap')->getResource('cacheSearch');

        // Identifiant de la recherche
        $search_id = 'search_users_'.md5(serialize(func_get_args()));

        if (($results = unserialize($cache->load($search_id))) === false) {
            // Création de l'objet recherche
            $select = new Zend_Db_Select(Zend_Controller_Front::getInstance()->getParam('bootstrap')->getResource('db'));

            // Requête principale
            $select->from(['u' => 'utilisateur'], ['uid' => 'ID_UTILISATEUR', '*'])
                ->join('utilisateurinformations', 'u.ID_UTILISATEURINFORMATIONS = utilisateurinformations.ID_UTILISATEURINFORMATIONS')
                ->join('fonction', 'utilisateurinformations.ID_FONCTION = fonction.ID_FONCTION', 'LIBELLE_FONCTION')
                ->join('groupe', 'u.ID_GROUPE = groupe.ID_GROUPE', 'LIBELLE_GROUPE')
                ->group('u.ID_UTILISATEUR')
                ->order('utilisateurinformations.NOM_UTILISATEURINFORMATIONS')
            ;

            // Critères : activité
            if ($actif) {
                $this->setCriteria($select, 'u.ACTIF_UTILISATEUR', 1);
            } elseif (!$actif) {
                $this->setCriteria($select, 'u.ACTIF_UTILISATEUR', 0);
            }

            // Critères : groupe
            if (null !== $groups) {
                $this->setCriteria($select, 'groupe.ID_GROUPE', $groups);
            }

            // Critères : nom
            if (null !== $name) {
                $this->setCriteria($select, '(NOM_UTILISATEURINFORMATIONS', $name, false);
                $this->setCriteria($select, 'PRENOM_UTILISATEURINFORMATIONS)', $name, false, 'orWhere');
            }

            // Critères : fonctions
            if (null !== $fonctions) {
                $this->setCriteria($select, 'fonction.ID_FONCTION', $fonctions);
            }

            // Gestion des pages et du count
            $select->limitPage($page, $count);

            // Construction du résultat
            $rows_counter = new Zend_Paginator_Adapter_DbSelect($select);
            $results = [
                'results' => $select->query()->fetchAll(),
                'search_metadata' => [
                    'search_id' => $search_id,
                    'current_page' => $page,
                    'count' => count($rows_counter),
                ],
            ];

            $cache->save(serialize($results));
        }

        return $results;
    }

    /**
     * Recherche des préventionnistes actifs sur au moins un dossier.
     *
     * @return array
     */
    public function listePrevActifs()
    {
        // Liste des préventionnistes pour les critères de recherche
        $selectListePrev = new Zend_Db_Select(Zend_Controller_Front::getInstance()->getParam('bootstrap')->getResource('db'));
        $selectListePrev->from(['ui' => 'utilisateurinformations'], ['NOM_UTILISATEURINFORMATIONS', 'PRENOM_UTILISATEURINFORMATIONS'])
            ->join('utilisateur', 'ui.ID_UTILISATEURINFORMATIONS = utilisateur.ID_UTILISATEURINFORMATIONS', [])
            ->join('dossierpreventionniste', 'dossierpreventionniste.ID_PREVENTIONNISTE = utilisateur.ID_UTILISATEUR', 'ID_PREVENTIONNISTE')
            ->order('NOM_UTILISATEURINFORMATIONS')
            ->distinct()
        ;

        return $selectListePrev->query()->fetchAll();
    }

    /**
     * Méthode pour aider à placer des conditions sur la requête.
     *
     * @param array|bool|float|int|string $value
     */
    private function setCriteria(Zend_Db_Select &$select, string $key, $value, bool $exact = true, string $clause = 'where'): self
    {
        $string = null;

        if (is_array($value)) {
            foreach ($value as $i => $singleValue) {
                $string .= $key.(($exact) ? '=' : ' LIKE ').$select->getAdapter()->quote((($exact) ? '' : '%').$singleValue.(($exact) ? '' : '%'));
                if ($i < count($value) - 1) {
                    $string .= ' OR ';
                }
            }
        } else {
            $string = $key.(($exact) ? '=' : ' LIKE ').$select->getAdapter()->quote((($exact) ? '' : '%').$value.(($exact) ? '' : '%'));
        }

        $select->{$clause}($string);

        return $this;
    }
}
