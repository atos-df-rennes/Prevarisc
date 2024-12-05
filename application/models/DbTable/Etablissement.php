<?php

class Model_DbTable_Etablissement extends Zend_Db_Table_Abstract
{
    protected $_name = 'etablissement';

    protected $_primary = 'ID_ETABLISSEMENT';

    /**
     * @param mixed $id_ets_info
     *
     * @return null|array
     */
    public function getTypesActivitesSecondaires($id_ets_info)
    {
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from('etablissementinformationstypesactivitessecondaires')
            ->join('type', 'ID_TYPE_SECONDAIRE = ID_TYPE', 'LIBELLE_TYPE')
            ->join('typeactivite', 'ID_TYPEACTIVITE_SECONDAIRE = ID_TYPEACTIVITE', 'LIBELLE_ACTIVITE')
            ->where('ID_ETABLISSEMENTINFORMATIONS = ?', $id_ets_info)
        ;

        $result = $this->fetchAll($select);

        return null == $result ? null : $result->toArray();
    }

    // NOTE : à faire après enregistrement d'un établissement

    /**
     * @param mixed $id
     *
     * @return false|string
     */
    public function getIDWinprev($id)
    {
        $model_adresse = new Model_DbTable_EtablissementAdresse();
        // Variables
        $genre = null;
        $codecommune = null;
        $nbetscommune = null;
        $rangcell = null;
        $commission = null;

        // Récupération des infos de l'établissement
        $infos = $this->getInformations($id);
        $adresses = $model_adresse->get($id);
        $parent = $this->getParent($id);

        // Vérifications
        if (null == $infos) {
            return false;
        }

        if (null == $infos->ID_GENRE) {
            return false;
        }

        if (empty($adresses)) {
            return false;
        }

        // Etape 1 : genre
        switch ($infos->ID_GENRE) {
            case 1: $genre = 'S';

                break;

            case 2: $genre = 'E';

                break;

            case 3: $genre = 'B';

                break;

            case 4: $genre = 'H';

                break;

            case 5: $genre = 'G';

                break;

            case 6: $genre = 'I';

                break;

            default: break;
        }

        // Etape 2 : Code commune
        if ('S' != $genre || 'C' != $genre) {
            $codecommune = str_pad($adresses[0]['NUMINSEE_COMMUNE'], 6, '0', STR_PAD_LEFT);
        } else {
            $codecommune = '000000';
        }

        // Etape 3 : Ordre sur la commune
        if ('S' != $genre || 'C' != $genre) {
            $select = $this->select()
                ->setIntegrityCheck(false)
                ->distinct()
                ->from('adressecommune', [])
                ->join('etablissementadresse', 'etablissementadresse.NUMINSEE_COMMUNE =adressecommune.NUMINSEE_COMMUNE', 'etablissementadresse.ID_ETABLISSEMENT')
                ->join('etablissement', 'etablissementadresse.ID_ETABLISSEMENT = etablissement.ID_ETABLISSEMENT', [])
                ->where('adressecommune.NUMINSEE_COMMUNE = ?', $adresses[0]['NUMINSEE_COMMUNE'])
                ->where("etablissement.DATEENREGISTREMENT_ETABLISSEMENT  <= ( SELECT etablissement.DATEENREGISTREMENT_ETABLISSEMENT FROM etablissement WHERE etablissement.ID_ETABLISSEMENT = '".('B' == $genre ? $parent['ID_ETABLISSEMENT'] : $id)."')")
            ;
            $nbetscommune = str_pad((string) count($this->fetchAll($select)), 5, '0', STR_PAD_LEFT);
        } else {
            $nbetscommune = '00000';
        }

        // Etape 4 : Rang de la cellule
        if ('B' == $genre) {
            $select = $this->select()
                ->setIntegrityCheck(false)
                ->from('etablissementlie')
                ->join('etablissement', 'etablissement.ID_ETABLISSEMENT = etablissementlie.ID_FILS_ETABLISSEMENT', [])
                ->where('etablissementlie.ID_ETABLISSEMENT = ?', $parent['ID_ETABLISSEMENT'])
                ->where('etablissement.DATEENREGISTREMENT_ETABLISSEMENT  <= ( SELECT etablissement.DATEENREGISTREMENT_ETABLISSEMENT FROM etablissement WHERE etablissement.ID_ETABLISSEMENT = ?)', $id)
            ;
            $result = $this->fetchAll($select);
            $rangcell = str_pad(null == $result ? '0' : (string) count($result), 3, '0', STR_PAD_LEFT);
        } else {
            $rangcell = '000';
        }

        // Etape 5 : ID de la commission
        $commission = null == $infos->ID_COMMISSION ? '0' : $infos->ID_COMMISSION;

        return $genre.$codecommune.$nbetscommune.'-'.$rangcell.'-'.$commission;
    }

    /**
     * @param int|string $id_user
     *
     * @return null|array
     */
    public function getByUser($id_user)
    {
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(['e' => 'etablissement'], 'ID_ETABLISSEMENT')
            ->joinLeft('etablissementinformations', 'e.ID_ETABLISSEMENT = etablissementinformations.ID_ETABLISSEMENT', ['LIBELLE_ETABLISSEMENTINFORMATIONS', 'PERIODICITE_ETABLISSEMENTINFORMATIONS'])
            ->joinLeft('etablissementinformationspreventionniste', 'etablissementinformationspreventionniste.ID_ETABLISSEMENTINFORMATIONS = etablissementinformations.ID_ETABLISSEMENTINFORMATIONS', [])
            ->where('DATE_ETABLISSEMENTINFORMATIONS = (select max(DATE_ETABLISSEMENTINFORMATIONS) from etablissementinformations where ID_ETABLISSEMENT = e.ID_ETABLISSEMENT ) ')
            ->where('etablissementinformationspreventionniste.ID_UTILISATEUR = '.$id_user)
            ->where('etablissementinformations.DATE_ETABLISSEMENTINFORMATIONS = ( SELECT MAX(DATE_ETABLISSEMENTINFORMATIONS) FROM etablissementinformations WHERE etablissementinformations.ID_ETABLISSEMENT = e.ID_ETABLISSEMENT ) OR etablissementinformations.DATE_ETABLISSEMENTINFORMATIONS IS NULL')
            ->where('e.DATESUPPRESSION_ETABLISSEMENT IS NULL')
        ;

        return (null != $this->fetchAll($select)) ? $this->fetchAll($select)->toArray() : null;
    }

    /**
     * @param float|int|string $id_etablissement
     *
     * @return null|Zend_Db_Table_Row_Abstract
     */
    public function getInformations($id_etablissement)
    {
        $DB_information = new Model_DbTable_EtablissementInformations();

        $select = $DB_information->select()
            ->setIntegrityCheck(false)
            ->from('etablissementinformations')
            ->joinLeft('etablissement', 'etablissement.ID_ETABLISSEMENT = etablissementinformations.ID_ETABLISSEMENT')
            ->where(sprintf("etablissementinformations.ID_ETABLISSEMENT = '%s'", $id_etablissement))
            ->where(sprintf("DATE_ETABLISSEMENTINFORMATIONS = (select max(DATE_ETABLISSEMENTINFORMATIONS) from etablissementinformations where ID_ETABLISSEMENT = '%s' ) ", $id_etablissement))
            ->where('etablissement.DATESUPPRESSION_ETABLISSEMENT IS NULL')
        ;

        return $DB_information->fetchRow($select);
    }

    /**
     * @param mixed $id_etablissement
     *
     * @return null|array
     */
    public function getLibelle($id_etablissement)
    {
        $select = $this->select()->setIntegrityCheck(false);

        $select->from(['e' => 'etablissement'], [])
            ->join('etablissementinformations', 'e.ID_ETABLISSEMENT = etablissementinformations.ID_ETABLISSEMENT', 'LIBELLE_ETABLISSEMENTINFORMATIONS')
            ->where('etablissementinformations.DATE_ETABLISSEMENTINFORMATIONS = ( SELECT MAX(etablissementinformations.DATE_ETABLISSEMENTINFORMATIONS) FROM etablissementinformations WHERE etablissementinformations.ID_ETABLISSEMENT = e.ID_ETABLISSEMENT )')
            ->where('e.DATESUPPRESSION_ETABLISSEMENT IS NULL')
            ->order('etablissementinformations.LIBELLE_ETABLISSEMENTINFORMATIONS ASC')
            ->where('e.ID_ETABLISSEMENT = ?', $id_etablissement)
        ;

        return (null != $this->fetchRow($select)) ? $this->fetchRow($select)->toArray() : null;
    }

    public function getPeriodicite($id_etablissement)
    {
        $select = $this->select()->setIntegrityCheck(false);

        $select->from(['e' => 'etablissement'], [])
            ->join('etablissementinformations', 'e.ID_ETABLISSEMENT = etablissementinformations.ID_ETABLISSEMENT', 'PERIODICITE_ETABLISSEMENTINFORMATIONS')
            ->where('etablissementinformations.DATE_ETABLISSEMENTINFORMATIONS = ( SELECT MAX(etablissementinformations.DATE_ETABLISSEMENTINFORMATIONS) FROM etablissementinformations WHERE etablissementinformations.ID_ETABLISSEMENT = e.ID_ETABLISSEMENT )')
            ->where('e.DATESUPPRESSION_ETABLISSEMENT IS NULL')
            ->order('etablissementinformations.LIBELLE_ETABLISSEMENTINFORMATIONS ASC')
            ->where('e.ID_ETABLISSEMENT = ?', $id_etablissement)
        ;

        if (null == ($row = $this->getAdapter()->fetchRow($select))) {
            return null;
        }

        return $row['PERIODICITE_ETABLISSEMENTINFORMATIONS'];
    }

    /**
     * @param mixed $id_etablissement
     *
     * @return null|array
     */
    public function getGenre($id_etablissement)
    {
        $select = $this->select()->setIntegrityCheck(false);

        $select->from(['e' => 'etablissement'], [])
            ->join('etablissementinformations', 'e.ID_ETABLISSEMENT = etablissementinformations.ID_ETABLISSEMENT', 'ID_GENRE')
            ->join('genre', 'etablissementinformations.ID_GENRE = genre.ID_GENRE', 'LIBELLE_GENRE')
            ->where('etablissementinformations.DATE_ETABLISSEMENTINFORMATIONS = ( SELECT MAX(etablissementinformations.DATE_ETABLISSEMENTINFORMATIONS) FROM etablissementinformations WHERE etablissementinformations.ID_ETABLISSEMENT = e.ID_ETABLISSEMENT )')
            ->where('e.DATESUPPRESSION_ETABLISSEMENT IS NULL')
            ->where('e.ID_ETABLISSEMENT = ?', $id_etablissement)
        ;

        return (null != $this->fetchRow($select)) ? $this->fetchRow($select)->toArray() : null;
    }

    /**
     * @param float|int|string $id_etablissement
     *
     * @return null|array
     */
    public function getParent($id_etablissement)
    {
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from('etablissementlie', [])
            ->joinLeft('etablissementinformations', 'etablissementinformations.ID_ETABLISSEMENT = etablissementlie.ID_ETABLISSEMENT')
            ->joinLeft('categorie', 'categorie.ID_CATEGORIE = etablissementinformations.ID_CATEGORIE')
            ->joinLeft('etablissement', 'etablissement.ID_ETABLISSEMENT = etablissementinformations.ID_ETABLISSEMENT')
            ->where(sprintf("etablissementlie.ID_FILS_ETABLISSEMENT = '%s'", $id_etablissement))
            ->where('DATE_ETABLISSEMENTINFORMATIONS = (select max(DATE_ETABLISSEMENTINFORMATIONS) from etablissementinformations where ID_ETABLISSEMENT = etablissementlie.ID_ETABLISSEMENT ) ')
            ->where('etablissement.DATESUPPRESSION_ETABLISSEMENT IS NULL')
        ;

        return (null != $this->fetchRow($select)) ? $this->fetchRow($select)->toArray() : null;
    }

    public function getAllParents($id_etablissement)
    {
        $result = $this->getParent($id_etablissement);

        if (null != $result) {
            return [$result, $this->getParent($result['ID_ETABLISSEMENT'])];
        }

        return $result;
    }

    /**
     * @param int|string $id_etablissement
     *
     * @return null|array
     */
    public function getDiaporama($id_etablissement)
    {
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from('etablissementpj', [])
            ->joinLeft('piecejointe', 'piecejointe.ID_PIECEJOINTE = etablissementpj.ID_PIECEJOINTE')
            ->where("EXTENSION_PIECEJOINTE = '.jpg' OR EXTENSION_PIECEJOINTE = '.JPG' OR EXTENSION_PIECEJOINTE = '.jpeg' OR EXTENSION_PIECEJOINTE = '.png'")
            ->where('PLACEMENT_ETABLISSEMENTPJ = 1')
            ->where('etablissementpj.ID_ETABLISSEMENT = '.$id_etablissement)
        ;

        return (null != $this->fetchAll($select)) ? $this->fetchAll($select)->toArray() : null;
    }

    /**
     * @param int|string $id_etablissement
     *
     * @return null|array
     */
    public function getPlans($id_etablissement)
    {
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from('etablissementpj', [])
            ->joinLeft('piecejointe', 'piecejointe.ID_PIECEJOINTE = etablissementpj.ID_PIECEJOINTE')
            ->where("EXTENSION_PIECEJOINTE = '.jpg' OR EXTENSION_PIECEJOINTE = '.JPG' OR EXTENSION_PIECEJOINTE = '.png'")
            ->where('PLACEMENT_ETABLISSEMENTPJ = 2')
            ->where('etablissementpj.ID_ETABLISSEMENT = '.$id_etablissement)
        ;

        return (null != $this->fetchAll($select)) ? $this->fetchAll($select)->toArray() : null;
    }

    /**
     * @param mixed $id_etablissement_informations
     *
     * @return null|array
     */
    public function getPlansInformations($id_etablissement_informations)
    {
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from('etablissementinformationsplan')
            ->join('typeplan', 'etablissementinformationsplan.ID_TYPEPLAN = typeplan.ID_TYPEPLAN')
            ->where('etablissementinformationsplan.ID_ETABLISSEMENTINFORMATIONS = ?', $id_etablissement_informations)
        ;

        return (null != $this->fetchAll($select)) ? $this->fetchAll($select)->toArray() : null;
    }

    // Recalcule les périod et cat des enfants d'un ets

    public function recalcEnfants($id_ets, $id_info, $historique): void
    {
        $search = new Model_DbTable_Search();
        $etablissement_enfants = $search->setItem('etablissement')->setCriteria('etablissementlie.ID_ETABLISSEMENT', $id_ets)->run();
        $model_etablissementInformations = new Model_DbTable_EtablissementInformations();
        $etablissement = $model_etablissementInformations->find($id_info)->current();

        foreach ($etablissement_enfants as $ets) {
            // On récupère la fiche de l'établissement enfant
            $row_etablissement = $this->getInformations($ets['ID_ETABLISSEMENT']);

            // Periodicité
            if ($etablissement->PERIODICITE_ETABLISSEMENTINFORMATIONS != $row_etablissement->PERIODICITE_ETABLISSEMENTINFORMATIONS) {
                $row_etablissement->PERIODICITE_ETABLISSEMENTINFORMATIONS = $etablissement->PERIODICITE_ETABLISSEMENTINFORMATIONS;
            }

            // Catégorie
            if ($etablissement->ID_CATEGORIE != $row_etablissement->ID_CATEGORIE) {
                $row_etablissement->ID_CATEGORIE = $etablissement->ID_CATEGORIE;
            }

            if ($historique) {
                $row_etablissement->ID_ETABLISSEMENTINFORMATIONS = null;
                $row_etablissement->DATE_ETABLISSEMENTINFORMATIONS = $etablissement->DATE_ETABLISSEMENTINFORMATIONS;

                $new_row = $model_etablissementInformations->createRow();
                $new_row->setFromArray($row_etablissement->toArray());
                $new_row->save();
            } else {
                $row_etablissement->save();
            }
        }
    }

    /**
     * @return array|int
     */
    public function listeDesERPOuvertsSousAvisDefavorable(?array $idsCommission = null, ?string $numInseeCommune = null, ?int $idUtilisateur = null, bool $getCount = false)
    {
        $search = new Model_DbTable_Search();
        $search->setItem('etablissement', $getCount);
        $search->setCriteria('avis.ID_AVIS', 2);
        $search->setCriteria('etablissementinformations.ID_GENRE', [2]);
        $search->setCriteria('etablissementinformations.ID_STATUT', 2);

        if ($numInseeCommune) {
            $search->setCriteria('etablissementadresse.NUMINSEE_COMMUNE', $numInseeCommune);
        }

        if ($idsCommission) {
            $search->setCriteria('etablissementinformations.ID_COMMISSION', $idsCommission);
        }

        if ($idUtilisateur) {
            $search->setCriteria('utilisateur.ID_UTILISATEUR', $idUtilisateur);
        }

        if ($getCount) {
            return $search->run(false, null, false, true);
        }

        return $search->run(false, null, false)->toArray();
    }

    /**
     * @return array|int
     */
    public function listeERPSansPreventionniste(?bool $getCount = null)
    {
        $search = new Model_DbTable_Search();
        $search->setItem('etablissement', $getCount);
        $search->setCriteria('etablissementinformations.ID_STATUT', 2);
        $search->setCriteria('utilisateur.ID_UTILISATEUR IS NULL');
        $search->sup('etablissementinformations.PERIODICITE_ETABLISSEMENTINFORMATIONS', 0);

        if ($getCount) {
            return $search->run(false, null, false, true);
        }

        return $search->run(false, null, false)->toArray();
    }

    /**
     * @return array|int
     */
    public function listeErpOuvertsSansProchainesVisitePeriodiques(array $idsCommission, bool $getCount = false)
    {
        $search = new Model_DbTable_Search();
        $search->setItem('etablissement', $getCount);

        $use_date_commission_for_periodicity = filter_var(getenv('PREVARISC_DATE_COMMISSION_RELANCE_PERIODICITE'), FILTER_VALIDATE_BOOLEAN);
        if ($use_date_commission_for_periodicity) {
            $search->columns([
                'nextvisiteyearmonth' => new Zend_Db_Expr(
                    "CASE WHEN
                            dossiers.DATECOMM_DOSSIER >= dossiers.DATEVISITE_DOSSIER
                        THEN
                            DATE_FORMAT(DATE_ADD(dossiers.DATECOMM_DOSSIER, INTERVAL etablissementinformations.PERIODICITE_ETABLISSEMENTINFORMATIONS MONTH), '%Y-%m')
                        ELSE
                            DATE_FORMAT(DATE_ADD(dossiers.DATEVISITE_DOSSIER, INTERVAL etablissementinformations.PERIODICITE_ETABLISSEMENTINFORMATIONS MONTH), '%Y-%m')
                    END"
                ),
            ]);
        } else {
            $search->columns([
                'nextvisiteyearmonth' => "DATE_FORMAT(DATE_ADD(dossiers.DATEVISITE_DOSSIER, INTERVAL etablissementinformations.PERIODICITE_ETABLISSEMENTINFORMATIONS MONTH), '%Y-%m')",
            ]);
        }

        $search->joinEtablissementDossier();
        $search->setCriteria('dossiers.DATEVISITE_DOSSIER = ( SELECT MAX(dos.DATEVISITE_DOSSIER) FROM dossier as dos '
                .'LEFT JOIN etablissementdossier etabdoss ON etabdoss.ID_DOSSIER = dos.ID_DOSSIER '
                .'LEFT JOIN dossiernature dn ON dn.ID_DOSSIER = dos.ID_DOSSIER '
                .'WHERE etabdoss.ID_ETABLISSEMENT = e.ID_ETABLISSEMENT '
                .'AND dos.TYPE_DOSSIER IN(2,3) '
                .'AND dn.ID_NATURE IN (21,23,24,26,28,29,47,48))');
        $search->setCriteria('etablissementinformations.ID_STATUT', 2);
        $search->setCriteria('etablissementinformations.ID_GENRE', 2);
        $search->sup('etablissementinformations.PERIODICITE_ETABLISSEMENTINFORMATIONS', 0);

        if ([] !== $idsCommission) {
            $search->setCriteria('etablissementinformations.ID_COMMISSION', $idsCommission);
        }

        $search->having("nextvisiteyearmonth < DATE_FORMAT(CURDATE(), '%Y-%m')");

        if ($getCount) {
            return $search->run(false, null, false, true);
        }

        return $search->run(false, null, false)->toArray();
    }

    /**
     * @param mixed $id_etablissement
     *
     * @return null|Zend_Db_Table_Row_Abstract
     */
    public function getDossierDonnantAvis($id_etablissement)
    {
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from('dossier', ['ID_DOSSIER', 'DATECOMM_DOSSIER', 'DATEVISITE_DOSSIER', 'AVIS_DOSSIER_COMMISSION'])
            ->join('etablissementdossier', 'etablissementdossier.ID_DOSSIER = dossier.ID_DOSSIER')
            ->join('dossiernature', 'dossiernature.ID_DOSSIER = etablissementdossier.ID_DOSSIER', [])
            ->where('etablissementdossier.ID_ETABLISSEMENT = ?', $id_etablissement)
            ->where('dossiernature.ID_NATURE in (?)', [19, 7, 17, 16, 21, 23, 24, 47, 26, 28, 29, 48])
            ->where('dossier.AVIS_DOSSIER_COMMISSION IS NOT NULL')
            ->where('dossier.AVIS_DOSSIER_COMMISSION > 0')
            ->order('IFNULL(dossier.DATECOMM_DOSSIER, dossier.DATEVISITE_DOSSIER) DESC')
        ;

        return $this->fetchRow($select);
    }

    public function getEffectifEtDegagement(int $idEtab)
    {
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(['e' => 'etablissement'], [])
            ->join(['eed' => 'etablissementeffectifdegagement'], 'e.ID_ETABLISSEMENT = eed.ID_ETABLISSEMENT', [])
            ->join(['ed' => 'effectifdegagement'], 'ed.ID_EFFECTIF_DEGAGEMENT = eed.ID_EFFECTIF_DEGAGEMENT')
            ->where('e.ID_ETABLISSEMENT = ?', $idEtab)
        ;

        return $this->fetchRow($select);
    }

    public function getIdEffectifDegagement(int $idEtab)
    {
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(['ed' => 'effectifdegagement'], ['ID_EFFECTIF_DEGAGEMENT'])
            ->join(['eed' => 'etablissementeffectifdegagement'], 'ed.ID_EFFECTIF_DEGAGEMENT = eed.ID_EFFECTIF_DEGAGEMENT', [])
            ->join(['e' => 'etablissement'], 'eed.ID_ETABLISSEMENT = e.ID_ETABLISSEMENT', [])
            ->where('e.ID_ETABLISSEMENT = ?', $idEtab)
        ;

        return $this->fetchRow($select);
    }

    /**
     * Retourne la liste des avis et derogations d un etablissement.
     *
     * @param mixed $idEtablissement
     */
    public function getListAvisDerogationsEtablissement($idEtablissement)
    {
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(['d' => 'dossier'], ['ID_DOSSIER', 'DATECOMM_DOSSIER', 'DATEVISITE_DOSSIER'])
            ->join(['ed' => 'etablissementdossier'], 'd.ID_DOSSIER = ed.ID_DOSSIER', [])
            ->join(['e' => 'etablissement'], 'ed.ID_ETABLISSEMENT = e.ID_ETABLISSEMENT', [])
            ->join(['ad' => 'avisderogations'], 'd.ID_DOSSIER = ad.ID_DOSSIER')
            ->join(['a' => 'avis'], 'a.ID_AVIS = ad.AVIS', 'LIBELLE_AVIS')
            ->joinLeft(['d2' => 'dossier'], 'd2.ID_DOSSIER = ad.ID_DOSSIER_LIE', ['TYPE_DOSSIER'])
            ->where('e.ID_ETABLISSEMENT = ?', $idEtablissement)
            ->where('ad.DISPLAY_HISTORIQUE = ?', 1)
        ;

        return $this->fetchAll($select)->toArray();
    }

    public function getDeleteEtablissement(): array
    {
        $select = $this->select()->setIntegrityCheck(false)
            ->from(['e' => 'etablissement'])
            ->join(['ei' => 'etablissementinformations'], 'e.ID_ETABLISSEMENT = ei.ID_ETABLISSEMENT')
            ->join(['ea' => 'etablissementadresse'], 'e.ID_ETABLISSEMENT = ea.ID_ETABLISSEMENT', [])
            ->join(['ac' => 'adressecommune'], 'ea.NUMINSEE_COMMUNE = ac.NUMINSEE_COMMUNE', 'LIBELLE_COMMUNE')
            ->joinLeft('utilisateur', 'utilisateur.ID_UTILISATEUR = e.DELETED_BY', 'USERNAME_UTILISATEUR')
            ->where('ei.DATE_ETABLISSEMENTINFORMATIONS = (SELECT MAX(DATE_ETABLISSEMENTINFORMATIONS) FROM etablissementinformations WHERE etablissementinformations.ID_ETABLISSEMENT = e.ID_ETABLISSEMENT) OR ei.DATE_ETABLISSEMENTINFORMATIONS IS NULL')
            ->where('e.DATESUPPRESSION_ETABLISSEMENT IS NOT NULL')
            ->group('e.ID_ETABLISSEMENT')
            ->order('e.DATESUPPRESSION_ETABLISSEMENT DESC')
        ;

        return $this->fetchAll($select)->toArray();
    }
}
