<?php

class Model_DbTable_Dossier extends Zend_Db_Table_Abstract
{
    // Nom de la base
    protected $_name = 'dossier';

    // Clé primaire
    protected $_primary = 'ID_DOSSIER';

    // Fonction qui récupére toutes les infos générales d'un dossier

    /**
     * @param float|int|string $id
     */
    public function getGeneral($id)
    {
        $select = "SELECT *
            from dossier d
            join dossiertype dt ON d.TYPE_DOSSIER = dt.ID_DOSSIERTYPE
            join dossiernature dn ON d.ID_DOSSIER = dn.ID_DOSSIER
            join commission c ON d.COMMISSION_DOSSIER = c.ID_COMMISSION
            join commissiontype ct ON c.ID_COMMISSIONTYPE = ct.ID_COMMISSIONTYPE
            where d.ID_DOSSIER = '{$id}'
            and d.DATESUPPRESSION_DOSSIER IS NULL;
        ";

        return $this->getAdapter()->fetchRow($select);
    }

    // Fonction qui récupére tous les établissements concernés par le dossier
    // PAS CERTAIN QU'ELLE SOIT ENCORE UTILISÉE

    /**
     * @param int|string $id_etablissement
     *
     * @return array
     */
    public function getEtablissementLibelleListe($id_etablissement)
    {
        $select = "SELECT etablissementlibelle.*
            FROM etablissementlibelle
            WHERE etablissementlibelle.id_etablissement = '".$id_etablissement."'
            AND etablissementlibelle.date_etablissementlibelle = (
                SELECT MAX(etablissementlibelle.date_etablissementlibelle)
                FROM etablissementlibelle
                WHERE etablissementlibelle.id_etablissement = '".$id_etablissement."'
            );
        ";

        return $this->getAdapter()->fetchAll($select);
    }

    // Fonction qui récup tous les établissements liés au dossier LAST VERSION

    /**
     * @param int|string $id_dossier
     *
     * @return array
     */
    public function getEtablissementDossier($id_dossier)
    {
        // retourne la liste des catégories de prescriptions par ordre
        $select = "SELECT etablissementdossier.ID_ETABLISSEMENTDOSSIER ,t1.ID_ETABLISSEMENT, LIBELLE_ETABLISSEMENTINFORMATIONS, LIBELLE_GENRE
            FROM etablissementdossier, etablissement e, etablissementinformations t1, genre
            WHERE etablissementdossier.ID_ETABLISSEMENT = t1.ID_ETABLISSEMENT
            AND etablissementdossier.ID_ETABLISSEMENT = e.ID_ETABLISSEMENT
            AND e.DATESUPPRESSION_ETABLISSEMENT IS NULL
            AND t1.ID_GENRE = genre.ID_GENRE
            AND etablissementdossier.ID_DOSSIER = '".$id_dossier."'
            AND t1.DATE_ETABLISSEMENTINFORMATIONS = (
                SELECT MAX(etablissementinformations.DATE_ETABLISSEMENTINFORMATIONS)
                FROM etablissementdossier, etablissementinformations
                WHERE etablissementinformations.ID_ETABLISSEMENT = t1.ID_ETABLISSEMENT
            )
			GROUP BY ID_ETABLISSEMENT;
        ";

        return $this->getAdapter()->fetchAll($select);
    }

    // Fonction optimisée pour les ACL

    /**
     * @param mixed $id_dossier
     *
     * @return array
     */
    public function getEtablissementDossier2($id_dossier)
    {
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from('etablissementdossier', ['etablissementdossier.ID_ETABLISSEMENT'])
            ->joinLeftUsing(['e' => 'etablissement'], 'ID_ETABLISSEMENT')
            ->where('etablissementdossier.ID_DOSSIER = ?', $id_dossier)
            ->where('e.DATESUPPRESSION_ETABLISSEMENT IS NULL')
        ;

        return $this->fetchAll($select)->toArray();
    }

    // autocompletion utilisé dans la partie dossier - Recherche etablissement LAST VERSION

    /**
     * @param int|string $etablissementLibelle
     *
     * @return array
     */
    public function searchLibelleEtab($etablissementLibelle)
    {
        $select = "SELECT ID_ETABLISSEMENT, LIBELLE_ETABLISSEMENTINFORMATIONS, LIBELLE_GENRE
            FROM etablissementinformations t1,genre
            WHERE genre.ID_GENRE = t1.ID_GENRE
            AND LIBELLE_ETABLISSEMENTINFORMATIONS LIKE '%".$etablissementLibelle."%'
            AND t1.DATE_ETABLISSEMENTINFORMATIONS = (
                SELECT MAX(etablissementinformations.DATE_ETABLISSEMENTINFORMATIONS)
                FROM etablissementinformations
                WHERE t1.ID_ETABLISSEMENT = etablissementinformations.ID_ETABLISSEMENT
            )
        ";

        return $this->getAdapter()->fetchAll($select);
    }

    // Fonction qui récupère toutes les cellules concernées par le dossier

    /**
     * @param int|string $id_dossier
     *
     * @return array
     */
    public function getCelluleListe($id_dossier)
    {
        $select = "SELECT cellulelibelle.*, MAX(cellulelibelle.date_cellulelibelle)
            FROM celluledossier, cellulelibelle
            WHERE cellulelibelle.id_cellule = celluledossier.id_cellule
            AND celluledossier.id_dossier = '".$id_dossier."'
            GROUP BY cellulelibelle.id_cellule;
        ";

        return $this->getAdapter()->fetchAll($select);
    }

    // retourne 1 si dossier Etude - 0 si Visite
    public function getTypeDossier($id_dossier)
    {
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from('dossier', 'TYPE_DOSSIER')
            ->where('dossier.ID_DOSSIER = ?', $id_dossier)
        ;

        return $this->getAdapter()->fetchRow($select);
    }

    /**
     * @param int|string $id_dossier
     */
    public function getNatureDossier($id_dossier)
    {
        $select = "SELECT ID_NATURE
            FROM dossiernature
            WHERE id_dossier = '".$id_dossier."';
        ";

        return $this->getAdapter()->fetchRow($select);
    }

    /**
     * @param int|string $id_dossier
     */
    public function getCommissionDossier($id_dossier)
    {
        $select = "SELECT commission_dossier
            FROM dossier
            WHERE id_dossier = '".$id_dossier."';
        ";

        return $this->getAdapter()->fetchRow($select);
    }

    public function getCommissionV2($idDossier)
    {
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(['d' => 'dossier'], 'd.ID_DOSSIER')
            ->join(['c' => 'commission'], 'd.COMMISSION_DOSSIER = c.ID_COMMISSION')
            ->join(['ct' => 'commissiontype'], 'c.ID_COMMISSIONTYPE = ct.ID_COMMISSIONTYPE')
            ->where('d.ID_DOSSIER = ?', $idDossier)
        ;

        return $this->getAdapter()->fetchRow($select);
    }

    /**
     * @param int|string $id_dossier
     */
    public function getGenerationInfos($id_dossier)
    {
        $select = "SELECT dossier.*, dossiertype.*, commission.*, commissiontype.*
            FROM dossier, dossiertype, commission, commissiontype
            WHERE dossier.commission_dossier =	commission.id_commission
            AND commission.id_commissiontype = commissiontype.id_commissiontype
            AND dossier.TYPE_DOSSIER = dossiertype.id_dossiertype
            AND dossier.id_dossier = '".$id_dossier."';
        ";

        return $this->getAdapter()->fetchRow($select);
    }

    // Retourne la liste de tout les dossiers (études et/ou visite) d'un établissement
    // Si type vaut 1 : visites ; 0 : études

    /**
     * @param float|int|string $etablissement
     * @param null|mixed       $type
     *
     * @return array
     */
    public function getDossiersEtablissement($etablissement, $type = null)
    {
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from('etablissementdossier', [])
            ->join('dossier', 'etablissementdossier.ID_DOSSIER = dossier.ID_DOSSIER', ['ID_DOSSIER', 'LIBELLE_DOSSIER', 'OBJET_DOSSIER', 'DESCRIPTIFGEN_DOSSIER', 'DATESECRETARIAT_DOSSIER'])
            ->join('dossiertype', 'dossier.TYPE_DOSSIER = dossiertype.ID_DOSSIERTYPE', 'VISITEBOOL_DOSSIERTYPE')
            ->where('etablissementdossier.ID_ETABLISSEMENT = '.$etablissement)
            ->where('dossier.DATESUPPRESSION_DOSSIER IS NULL')
            ->order('dossier.DATESECRETARIAT_DOSSIER DESC')
        ;

        if ('1' == $type || '0' == $type) {
            $select->where('dossiertype.VISITEBOOL_DOSSIERTYPE = '.$type);
        }

        return $this->fetchAll($select)->toArray();
    }

    public function getLastInfosEtab($idEtablissement)
    {
        $select = 'SELECT ID_ETABLISSEMENT, LIBELLE_ETABLISSEMENTINFORMATIONS, LIBELLE_GENRE
            FROM etablissementinformations,genre
            WHERE genre.ID_GENRE = etablissementinformations.ID_GENRE;
        ';

        return $this->getAdapter()->fetchAll($select);
    }

    /**
     * @param int|string $idEtablissement
     * @param int|string $idDossier
     *
     * @return array
     */
    public function getDossierEtab($idEtablissement, $idDossier)
    {
        $select = "SELECT *
            FROM dossier, etablissementdossier, dossiertype
            WHERE dossier.ID_DOSSIER = etablissementdossier.ID_DOSSIER
            AND etablissementdossier.ID_ETABLISSEMENT = '".$idEtablissement."'
            AND dossiertype.ID_DOSSIERTYPE = dossier.TYPE_DOSSIER

            AND dossier.ID_DOSSIER NOT IN (
                SELECT ID_DOSSIER1
                FROM dossierlie
                WHERE ID_DOSSIER2 = '".$idDossier."'
            )
            AND dossier.ID_DOSSIER NOT IN (
                SELECT ID_DOSSIER2
                FROM dossierlie
                WHERE ID_DOSSIER1 = '".$idDossier."'
            )
            AND dossier.DATESUPPRESSION_DOSSIER IS NULL
            ORDER BY dossier.DATEINSERT_DOSSIER;
        ";

        return $this->getAdapter()->fetchAll($select);
    }

    /**
     * @param int|string $idDossier
     *
     * @return array
     */
    public function getDossierTypeNature($idDossier)
    {
        $select = "SELECT *
            FROM dossier, dossiertype, dossiernature, dossiernatureliste
            WHERE dossier.TYPE_DOSSIER = dossiertype.ID_DOSSIERTYPE
            AND dossier.ID_DOSSIER = dossiernature.ID_DOSSIER
            AND dossiernatureliste.ID_DOSSIERNATURE = dossiernature.ID_NATURE
            AND dossier.id_dossier = '".$idDossier."';
        ";

        return $this->getAdapter()->fetchAll($select);
    }

    public function findLastVp($idEtab)
    {
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(['d' => 'dossier'])
            ->join(['ed' => 'etablissementdossier'], 'ed.ID_DOSSIER = d.ID_DOSSIER')
            ->join(['dn' => 'dossiernature'], 'd.ID_DOSSIER = dn.ID_DOSSIER')
            ->where('ed.ID_ETABLISSEMENT = ?', $idEtab)
            ->where('dn.ID_NATURE = 21 OR dn.ID_NATURE = 26')
            ->where('d.DATEVISITE_DOSSIER IS NOT NULL')
            ->order('d.DATEVISITE_DOSSIER desc')
            ->limit(1)
        ;

        return $this->getAdapter()->fetchRow($select);
    }

    public function findLastVpCreationDoc($idEtab, $idDossier, $dateVisite)
    {
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(['d' => 'dossier'])
            ->join(['ed' => 'etablissementdossier'], 'ed.ID_DOSSIER = d.ID_DOSSIER')
            ->join(['dn' => 'dossiernature'], 'd.ID_DOSSIER = dn.ID_DOSSIER')
            ->where('ed.ID_ETABLISSEMENT = ?', $idEtab)
            ->where('ed.ID_DOSSIER <> ?', $idDossier)
            ->where('dn.ID_NATURE = 21 OR dn.ID_NATURE = 26')
            ->where('d.DATEVISITE_DOSSIER IS NOT NULL')
            ->where('d.DATEVISITE_DOSSIER < ?', $dateVisite)
            ->order('d.DATEVISITE_DOSSIER desc')
            ->limit(1)
        ;

        return $this->getAdapter()->fetchRow($select);
    }

    public function getAvisDossier($id_dossier)
    {
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(['a' => 'avis'], 'LIBELLE_AVIS')
            ->join(['d' => 'dossier'], 'd.AVIS_DOSSIER_COMMISSION = a.ID_AVIS')
            ->where('d.ID_DOSSIER = ?', $id_dossier)
        ;

        return $this->getAdapter()->fetchRow($select);
    }

    /**
     * @param int|string $id_dossier
     *
     * @return array
     */
    public function getEtablissementDossierGenConvoc($id_dossier)
    {
        $select = "SELECT etablissementdossier.ID_ETABLISSEMENTDOSSIER ,t1.ID_ETABLISSEMENT, LIBELLE_ETABLISSEMENTINFORMATIONS, LIBELLE_GENRE
            FROM etablissementdossier, etablissementinformations t1, genre
            WHERE etablissementdossier.ID_ETABLISSEMENT = t1.ID_ETABLISSEMENT
            AND t1.ID_GENRE = genre.ID_GENRE
            AND etablissementdossier.ID_DOSSIER = '".$id_dossier."'
            AND t1.DATE_ETABLISSEMENTINFORMATIONS = (
                SELECT MAX(etablissementinformations.DATE_ETABLISSEMENTINFORMATIONS)
                FROM etablissementdossier, etablissementinformations
                WHERE etablissementinformations.ID_ETABLISSEMENT = t1.ID_ETABLISSEMENT
            )
            GROUP BY ID_ETABLISSEMENT;
        ";

        return $this->getAdapter()->fetchAll($select);
    }

    /**
     * @return array|int
     */
    public function listeDesDossierDateCommissionEchu(array $idsCommission, int $sinceDays = 10, int $untilDays = 100, bool $getCount = false)
    {
        $select = $this->select()->setIntegrityCheck(false);

        if ($getCount) {
            $select->from(['d' => 'dossier'], ['COUNT(*) as count']);
        } else {
            $select->from(['d' => 'dossier']);
        }

        $select
            ->joinLeft('dossierlie', 'd.ID_DOSSIER = dossierlie.ID_DOSSIER2')
            ->join('dossiernature', 'dossiernature.ID_DOSSIER = d.ID_DOSSIER', [])
            ->join('dossiernatureliste', 'dossiernatureliste.ID_DOSSIERNATURE = dossiernature.ID_NATURE', ['LIBELLE_DOSSIERNATURE', 'ID_DOSSIERNATURE'])
            ->join('dossiertype', 'dossiertype.ID_DOSSIERTYPE = dossiernatureliste.ID_DOSSIERTYPE', 'LIBELLE_DOSSIERTYPE')
            ->joinLeft('dossierdocurba', 'd.ID_DOSSIER = dossierdocurba.ID_DOSSIER', 'NUM_DOCURBA')
            ->joinLeft(['e' => 'etablissementdossier'], 'd.ID_DOSSIER = e.ID_DOSSIER', [])
            ->joinLeft('avis', 'd.AVIS_DOSSIER_COMMISSION = avis.ID_AVIS')
            ->joinLeft('dossierpreventionniste', 'dossierpreventionniste.ID_DOSSIER = d.ID_DOSSIER', [])
            ->joinLeft('utilisateur', 'utilisateur.ID_UTILISATEUR = dossierpreventionniste.ID_PREVENTIONNISTE', 'ID_UTILISATEUR')
            ->joinLeft('etablissementinformations', 'e.ID_ETABLISSEMENT = etablissementinformations.ID_ETABLISSEMENT AND etablissementinformations.DATE_ETABLISSEMENTINFORMATIONS = ( SELECT MAX(etablissementinformations.DATE_ETABLISSEMENTINFORMATIONS) FROM etablissementinformations WHERE etablissementinformations.ID_ETABLISSEMENT = e.ID_ETABLISSEMENT )', 'LIBELLE_ETABLISSEMENTINFORMATIONS')
            ->joinLeft('dossieraffectation', 'dossieraffectation.ID_DOSSIER_AFFECT = d.ID_DOSSIER')
            ->joinLeft('datecommission', 'dossieraffectation.ID_DATECOMMISSION_AFFECT = datecommission.ID_DATECOMMISSION ')
            ->where('DATEDIFF(CURDATE(), datecommission.DATE_COMMISSION) >= '.$sinceDays)
            ->where('DATEDIFF(CURDATE(), datecommission.DATE_COMMISSION) <= '.$untilDays)
            ->where('d.AVIS_DOSSIER_COMMISSION IS NULL or d.AVIS_DOSSIER_COMMISSION = 0')
        ;

        if ([] !== $idsCommission) {
            $select->where('datecommission.COMMISSION_CONCERNE IN ('.implode(',', $idsCommission).')');
        }

        if ($getCount) {
            return $this->getAdapter()->fetchRow($select)['count'];
        }

        $select
            ->group('d.ID_DOSSIER')
            ->order('datecommission.DATE_COMMISSION desc')
        ;

        return $this->getAdapter()->fetchAll($select);
    }

    /**
     * @return array|int
     */
    public function listeDossierAvecAvisDiffere(array $idsCommission, bool $getCount = false)
    {
        // Dossiers avec avis différé
        $search = new Model_DbTable_Search();
        $search->setItem('dossier', $getCount);

        if ([] !== $idsCommission) {
            $search->setCriteria('d.COMMISSION_DOSSIER', $idsCommission);
        }

        $search->setCriteria('d.DIFFEREAVIS_DOSSIER', 1);

        if ($getCount) {
            return $search->run(false, null, false, true);
        }

        return $search->run(false, null, false)->toArray();
    }

    /**
     * @return array|int
     */
    public function listeDesCourrierSansReponse(int $duree_en_jour = 5, bool $getCount = false)
    {
        $search = new Model_DbTable_Search();
        $search->setItem('dossier', $getCount);
        $search->setCriteria('d.TYPE_DOSSIER', 5);
        $search->setCriteria('d.DATEREP_DOSSIER IS NULL');
        $search->setCriteria('d.OBJET_DOSSIER IS NOT NULL');
        $search->sup('DATEDIFF(CURDATE(), d.DATEINSERT_DOSSIER)', $duree_en_jour);
        $search->order('d.DATEINSERT_DOSSIER desc');

        if ($getCount) {
            return $search->run(false, null, false, true);
        }

        return $search->run(false, null, false)->toArray();
    }

    // Fonction qui récup tous les établissements liés au dossier LAST VERSION

    /**
     * @param int|string $id_dossier
     *
     * @return array
     */
    public function getPreventionnistesDossier($id_dossier)
    {
        // retourne la liste des catégories de prescriptions par ordre
        $select = "SELECT usrinfos.*
            FROM dossierpreventionniste, utilisateur usr, utilisateurinformations usrinfos
            WHERE dossierpreventionniste.ID_PREVENTIONNISTE = usr.ID_UTILISATEUR
            AND usr.ID_UTILISATEURINFORMATIONS = usrinfos.ID_UTILISATEURINFORMATIONS
            AND dossierpreventionniste.ID_DOSSIER = '".$id_dossier."'
	        GROUP BY usr.ID_UTILISATEUR;
        ";

        return $this->getAdapter()->fetchAll($select);
    }

    // Retourne la liste de tout les dossiers d'un Etablissement

    /**
     * @param int|string $etablissement
     *
     * @return array
     */
    public function getDossiersEtab($etablissement)
    {
        $select = "SELECT dossier.ID_DOSSIER
            from dossier
            join etablissementdossier ON etablissementdossier.ID_DOSSIER = dossier.ID_DOSSIER
            where etablissementdossier.ID_ETABLISSEMENT = '".$etablissement."'
            and dossier.DATESUPPRESSION_DOSSIER IS NULL;
        ";

        return $this->getAdapter()->fetchAll($select);
    }

    // Retourne l'ID du dernier dossier donnant avis pour un établissement donné

    /**
     * @param float|int|string $idEtab
     */
    public function getDernierIdDossierDonnantAvis($idEtab)
    {
        $select = "SELECT ID_DOSSIER from (
                (SELECT d.ID_DOSSIER, d.DATECOMM_DOSSIER
                from etablissement e
                join etablissementdossier ed ON e.ID_ETABLISSEMENT = ed.ID_ETABLISSEMENT
                join dossier d ON ed.ID_DOSSIER = d.ID_DOSSIER
                join dossiernature dn ON d.ID_DOSSIER = dn.ID_DOSSIER
                where e.ID_ETABLISSEMENT = '{$idEtab}'
                and dn.ID_NATURE in (7,16,17,19,21,23,24,26,28,29,47,48)
                and d.AVIS_DOSSIER_COMMISSION in (1,2)
                and d.DATESUPPRESSION_DOSSIER IS NULL
                order by d.DATECOMM_DOSSIER desc
                limit 1)
            UNION
                (SELECT d.ID_DOSSIER, d.DATEVISITE_DOSSIER
                from etablissement e
                join etablissementdossier ed ON e.ID_ETABLISSEMENT = ed.ID_ETABLISSEMENT
                join dossier d ON ed.ID_DOSSIER = d.ID_DOSSIER
                join dossiernature dn ON d.ID_DOSSIER = dn.ID_DOSSIER
                where e.ID_ETABLISSEMENT = '{$idEtab}'
                and dn.ID_NATURE in (7,16,17,19,21,23,24,26,28,29,47,48)
                and d.AVIS_DOSSIER_COMMISSION in (1,2)
                and d.DATESUPPRESSION_DOSSIER IS NULL
                order by d.DATEVISITE_DOSSIER desc
                limit 1)
            order by DATECOMM_DOSSIER desc
            limit 1) as result;
        ";

        return $this->getAdapter()->fetchRow($select);
    }

    public function isPlatau(int $idDossier): bool
    {
        $select = $this->select(self::SELECT_WITH_FROM_PART)
            ->where('ID_DOSSIER = ?', $idDossier)
        ;

        $result = $this->fetchRow($select);

        return null !== $result['ID_PLATAU'];
    }

    // Récupère les dossiers d'un établissement par type
    public function getDossiersEtablissementByType(int $idEtablissement, string $type): array
    {
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(['d' => 'dossier'])
            ->join(['ed' => 'etablissementdossier'], 'd.ID_DOSSIER = ed.ID_DOSSIER')
            ->join(['e' => 'etablissement'], 'ed.ID_ETABLISSEMENT = e.ID_ETABLISSEMENT')
            ->join(['dt' => 'dossiertype'], 'd.TYPE_DOSSIER = dt.ID_DOSSIERTYPE')
            ->where('e.ID_ETABLISSEMENT = ?', $idEtablissement)
        ;

        switch ($type) {
            case 'etudes':
                $select->where('dt.ID_DOSSIERTYPE = 1');

                break;

            case 'visites':
                $select->where('dt.ID_DOSSIERTYPE IN (2, 3)');

                break;

            case 'autres':
                $select->where('dt.ID_DOSSIERTYPE NOT IN (1, 2, 3)');

                break;

            default:
                throw new Exception(sprintf('Type %s non supporté', $type));
        }

        return $this->getAdapter()->fetchAll($select);
    }

    // FIXME Supprimer une des deux fonctions
    public function getEffectifEtDegagement(int $idDossier)
    {
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(['d' => 'dossier'], [])
            ->join(['ded' => 'dossiereffectifdegagement'], 'ded.ID_DOSSIER = d.ID_DOSSIER', [])
            ->join(['ed' => 'effectifdegagement'], 'ed.ID_EFFECTIF_DEGAGEMENT = ded.ID_EFFECTIF_DEGAGEMENT')
            ->where('d.ID_DOSSIER = ?', $idDossier)
        ;

        return $this->fetchRow($select);
    }

    public function getIdEffectifDegagement(int $idDossier)
    {
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(['ed' => 'effectifdegagement'], ['ID_EFFECTIF_DEGAGEMENT'])
            ->join(['ded' => 'dossiereffectifdegagement'], 'ed.ID_EFFECTIF_DEGAGEMENT = ded.ID_EFFECTIF_DEGAGEMENT', [])
            ->join(['d' => 'dossier'], 'ded.ID_DOSSIER = d.ID_DOSSIER', [])
            ->where('d.ID_DOSSIER = ?', $idDossier)
        ;

        return $this->fetchRow($select);
    }

    /**
     * Retourne la liste des dossiers d un etablissement en se basant sur un dossier.
     *
     * @param mixed $idDossier
     */
    public function getListeDossierFromDossier($idDossier): array
    {
        $dossEtab = [];
        $nbdossiermax = Service_Utils_DossiersMaxNumber::value();

        $select = $this->select()->setIntegrityCheck(false)
            ->from(['d' => 'dossier'])
            ->join(['ed' => 'etablissementdossier'], 'ed.ID_DOSSIER = d.ID_DOSSIER')
            ->join(['e' => 'etablissement'], 'e.ID_ETABLISSEMENT = ed.ID_ETABLISSEMENT')
            ->where("e.ID_ETABLISSEMENT = (Select etablissement.ID_ETABLISSEMENT from etablissement
                            inner join etablissementdossier on etablissementdossier.ID_ETABLISSEMENT = etablissement.ID_ETABLISSEMENT
                            inner join dossier on etablissementdossier.ID_DOSSIER = dossier.ID_DOSSIER
                            where dossier.ID_DOSSIER = {$idDossier})")
            ->where('d.ID_DOSSIER != ?', $idDossier)
            ->order('d.ID_DOSSIER DESC')
        ;

        $dossiers = $this->getAdapter()->fetchAll($select);

        $dossEtab['Visites'] = array_slice(array_filter($dossiers, function (array $dossier): bool {
            return Service_Dossier::ID_DOSSIERTYPE_VISITE === $dossier['TYPE_DOSSIER'] || Service_Dossier::ID_DOSSIERTYPE_GRPVISITE === $dossier['TYPE_DOSSIER'];
        }), 0, $nbdossiermax);
        $dossEtab['Etudes'] = array_slice(array_filter($dossiers, function (array $dossier): bool {
            return Service_Dossier::ID_DOSSIERTYPE_ETUDE === $dossier['TYPE_DOSSIER'];
        }), 0, $nbdossiermax);
        $dossEtab['Autres'] = array_slice(array_filter($dossiers, function (array $dossier): bool {
            return !in_array($dossier['TYPE_DOSSIER'], [Service_Dossier::ID_DOSSIERTYPE_ETUDE, Service_Dossier::ID_DOSSIERTYPE_VISITE, Service_Dossier::ID_DOSSIERTYPE_GRPVISITE], true);
        }), 0, $nbdossiermax);

        return $dossEtab;
    }

    public function getListeDossierFromDossierN($idDossier): array
    {
        $dossEtab = [];
        $nbdossiermax = Service_Utils_DossiersMaxNumber::value();

        $select = $this->select()->setIntegrityCheck(false)
            ->from(['d' => 'dossier'])
            ->join(['ed' => 'etablissementdossier'], 'ed.ID_DOSSIER = d.ID_DOSSIER')
            ->join(['e' => 'etablissement'], 'e.ID_ETABLISSEMENT = ed.ID_ETABLISSEMENT')
            ->where("e.ID_ETABLISSEMENT = (Select etablissement.ID_ETABLISSEMENT from etablissement
                            inner join etablissementdossier on etablissementdossier.ID_ETABLISSEMENT = etablissement.ID_ETABLISSEMENT
                            inner join dossier on etablissementdossier.ID_DOSSIER = dossier.ID_DOSSIER
                            where dossier.ID_DOSSIER = {$idDossier})")
            ->where('d.ID_DOSSIER != ?', $idDossier)
            ->order('d.ID_DOSSIER DESC')
        ;

        $dossiers = $this->getAdapter()->fetchAll($select);

        $dossEtab['Visites'] = array_slice(array_filter($dossiers, function (array $dossier): bool {
            return Service_Dossier::ID_DOSSIERTYPE_VISITE === $dossier['TYPE_DOSSIER'] || Service_Dossier::ID_DOSSIERTYPE_GRPVISITE === $dossier['TYPE_DOSSIER'];
        }), $nbdossiermax);
        $dossEtab['Etudes'] = array_slice(array_filter($dossiers, function (array $dossier): bool {
            return Service_Dossier::ID_DOSSIERTYPE_ETUDE === $dossier['TYPE_DOSSIER'];
        }), $nbdossiermax);
        $dossEtab['Autres'] = array_slice(array_filter($dossiers, function (array $dossier): bool {
            return !in_array($dossier['TYPE_DOSSIER'], [Service_Dossier::ID_DOSSIERTYPE_ETUDE, Service_Dossier::ID_DOSSIERTYPE_VISITE, Service_Dossier::ID_DOSSIERTYPE_GRPVISITE], true);
        }), $nbdossiermax);

        return $dossEtab;
    }

    /**
     * Retourne la liste des avis derogations d un dossier en passant l id dossier en param.
     *
     * @param mixed $idDossier
     */
    public function getListAvisDerogationsFromDossier($idDossier)
    {
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(['ad' => 'avisderogations'])
            ->join(['d' => 'dossier'], 'ad.ID_DOSSIER = d.ID_DOSSIER', [])
            ->join(['a' => 'avis'], 'ad.AVIS = a.ID_AVIS', 'LIBELLE_AVIS')
            ->joinLeft(['dl' => 'dossier'], 'ad.ID_DOSSIER_LIE = dl.ID_DOSSIER', 'OBJET_DOSSIER')
            ->where('d.ID_DOSSIER = ?', $idDossier)
        ;

        return $this->fetchAll($select)->toArray();
    }

    public function getDeleteDossier(): array
    {
        $select = $this->select()->setIntegrityCheck(false)
            ->from(['d' => 'dossier'])
            ->join('dossiernature', 'dossiernature.ID_DOSSIER = d.ID_DOSSIER', [])
            ->join('dossiernatureliste', 'dossiernatureliste.ID_DOSSIERNATURE = dossiernature.ID_NATURE', ['LIBELLE_DOSSIERNATURE', 'ID_DOSSIERNATURE'])
            ->join('dossiertype', 'dossiertype.ID_DOSSIERTYPE = dossiernatureliste.ID_DOSSIERTYPE', 'LIBELLE_DOSSIERTYPE')
            ->joinLeft('dossierdocurba', 'd.ID_DOSSIER = dossierdocurba.ID_DOSSIER', 'NUM_DOCURBA')
            ->join(['ed' => 'etablissementdossier'], 'd.ID_DOSSIER = ed.ID_DOSSIER', [])
            ->join(
                ['ei' => 'etablissementinformations'],
                'ed.ID_ETABLISSEMENT = ei.ID_ETABLISSEMENT',
                'LIBELLE_ETABLISSEMENTINFORMATIONS'
            )
            ->joinLeft(['u' => 'utilisateur'], 'u.ID_UTILISATEUR = d.DELETED_BY', 'USERNAME_UTILISATEUR')
            ->where('ei.DATE_ETABLISSEMENTINFORMATIONS = (SELECT MAX(DATE_ETABLISSEMENTINFORMATIONS) FROM etablissementinformations WHERE etablissementinformations.ID_ETABLISSEMENT = ed.ID_ETABLISSEMENT) OR ei.DATE_ETABLISSEMENTINFORMATIONS IS NULL')
            ->where('d.DATESUPPRESSION_DOSSIER IS NOT NULL')
            ->group('d.ID_DOSSIER')
            ->order('d.DATESUPPRESSION_DOSSIER DESC')
        ;

        return $this->fetchAll($select)->toArray();
    }

    public function getNombreNouvellesPiecesJointes(int $idDossier, string $dateVisitePage): int
    {
        $select = $this->select()->setIntegrityCheck(false)
            ->from(['d' => 'dossier'], ['COUNT(*) as count'])
            ->join(['dpj' => 'dossierpj'], 'd.ID_DOSSIER = dpj.ID_DOSSIER', [])
            ->join(['pj' => 'piecejointe'], 'dpj.ID_PIECEJOINTE = pj.ID_PIECEJOINTE', ['DATE_NOTIFICATION'])
            ->where('d.ID_DOSSIER = ?', $idDossier)
            ->where('pj.DATE_NOTIFICATION > ?', $dateVisitePage)
        ;

        return $this->fetchRow($select)['count'];
    }
}
