<?php
    class Model_DbTable_DateCommission extends Zend_Db_Table_Abstract
    {
        protected $_name = 'datecommission'; // Nom de la base
        protected $_primary = 'ID_DATECOMMISSION'; // Cl� primaire

        public function addDateComm($date, $heureD, $heureF, $idComm, $type, $libelle)
        {
            $new = $this->createRow();
            $new->DATE_COMMISSION = $date;
            $new->HEUREDEB_COMMISSION = $heureD;
            $new->HEUREFIN_COMMISSION = $heureF;
            $new->COMMISSION_CONCERNE = $idComm;
            $new->ID_COMMISSIONTYPEEVENEMENT = $type;
            $new->LIBELLE_DATECOMMISSION = $libelle;
            $new->save();

            return $new->ID_DATECOMMISSION;
        }

        public function addDateCommLiee($date, $heureD, $heureF, $idCommOrigine, $type, $idComm, $libelle)
        {
            $new = $this->createRow();
            $new->DATE_COMMISSION = $date;
            $new->HEUREDEB_COMMISSION = $heureD;
            $new->HEUREFIN_COMMISSION = $heureF;
            $new->DATECOMMISSION_LIEES = $idCommOrigine;
            $new->ID_COMMISSIONTYPEEVENEMENT = $type;
            $new->COMMISSION_CONCERNE = $idComm;
            $new->LIBELLE_DATECOMMISSION = $libelle;
            $new->save();

            return $new->ID_DATECOMMISSION;
        }

        /**
         * @param string|int $idCommission
         * @param string|int $debut
         * @param string|int $fin
         *
         * @return array
         */
        public function getFirstCommission($idCommission, $debut, $fin)
        {
            $select = "SELECT *
                FROM datecommission
                WHERE COMMISSION_CONCERNE = '".$idCommission."'
                AND DATE_COMMISSION BETWEEN '".$debut."'	AND '".$fin."'
            ";
            //echo $select;

            return $this->getAdapter()->fetchAll($select);
        }
        /**
         * @param int $date
         * @param int $next_date
         *
         * @return array
         */
        public function getNextCommission($idsCommission, $date, $next_date)
        {
            $ids = (array) $idsCommission;
            $select = "SELECT *
                FROM datecommission d
                LEFT JOIN commission c ON d.COMMISSION_CONCERNE = c.ID_COMMISSION
                WHERE DATE_COMMISSION BETWEEN '".date('Y-m-d', $date)."' AND '".date('Y-m-d', $next_date)."'
                ".(count($ids) > 0 ? 'AND d.COMMISSION_CONCERNE IN ('.implode(',', $ids).')' : '').'
                ORDER BY DATE_COMMISSION, HEUREDEB_COMMISSION';

            return $this->getAdapter()->fetchAll($select);
        }

        /**
         * @param string|int $mois
         * @param string|int $annee
         * @param string|int $idcom
         *
         * @return array
         */
        public function getMonthCommission($mois, $annee, $idcom)
        {
            $select = "SELECT *
                FROM datecommission
                WHERE MONTH(DATE_COMMISSION) = '".$mois."'  AND   YEAR(DATE_COMMISSION) = '".$annee."'
                AND COMMISSION_CONCERNE = '".$idcom."'";

            return $this->getAdapter()->fetchAll($select);
        }

        /**
         * @param string|int $idCommissionOrigine
         * @param string|int $debut
         * @param string|int $fin
         *
         * @return array
         */
        public function getCommissionsLiees($idCommissionOrigine, $debut, $fin)
        {
            $select = "SELECT *
                FROM datecommission
                WHERE DATECOMMISSION_LIEES = '".$idCommissionOrigine."'
                AND DATE_COMMISSION BETWEEN '".$debut."'	AND '".$fin."'
            ";
            //echo $select;
            return $this->getAdapter()->fetchAll($select);
        }

        /**
         * @param string|int $idComm
         *
         * @return array
         */
        public function getCommissionsQtypListing($idComm)
        {
            $select = "SELECT *
                FROM datecommission
                WHERE ( ID_DATECOMMISSION = '".$idComm."'
                OR DATECOMMISSION_LIEES = '".$idComm."' )
                ORDER BY DATE_COMMISSION
            ";
            //echo $select;

            return $this->getAdapter()->fetchAll($select);
        }

        /**
         * @param string|int $idComm
         * @param string|int $libelle
         *
         * @return Zend_Db_Statement_Interface
         */
        public function dateCommUpdateLibelle($idComm, $libelle)
        {
            $select = "UPDATE datecommission
                SET LIBELLE_DATECOMMISSION = '".$libelle."'
                WHERE ( ID_DATECOMMISSION = '".$idComm."' OR DATECOMMISSION_LIEES = '".$idComm."' )
            ";
            //echo $select;
            return $this->getAdapter()->query($select);
        }

        /**
         * @param string|int $idComm
         * @param string|int $idNewType
         *
         * @return Zend_Db_Statement_Interface
         */
        public function dateCommUpdateType($idComm, $idNewType)
        {
            $select = "UPDATE datecommission
                SET ID_COMMISSIONTYPEEVENEMENT = '".$idNewType."'
                WHERE ( ID_DATECOMMISSION = '".$idComm."' OR DATECOMMISSION_LIEES = '".$idComm."' )
            ";
            //echo $select;
            return $this->getAdapter()->query($select);
        }

        /**
         * @param string|int $oldComm
         * @param string|int $newComm
         *
         * @return Zend_Db_Statement_Interface
         */
        public function changeMasterDateComm($oldComm, $newComm)
        {
            $select = "UPDATE datecommission
                SET DATECOMMISSION_LIEES = '".$newComm."'
                WHERE ( ID_DATECOMMISSION = '".$oldComm."' OR DATECOMMISSION_LIEES = '".$oldComm."' )
            ";
            //echo $select;
            return $this->getAdapter()->query($select);
        }

        //pour la gestion des ordres du jour r�cup des date li�es
        /**
         * @param string|int $idComm
         *
         * @return array
         */
        public function getCommissionsDateLieesMaster($idComm)
        {
            $select = "SELECT *
                FROM datecommission
                WHERE ( ID_DATECOMMISSION = '".$idComm."'
                OR DATECOMMISSION_LIEES = '".$idComm."' )
                ORDER BY DATE_COMMISSION
            ";
            //echo $select;
            return $this->getAdapter()->fetchAll($select);
        }

        public function getInfosVisite($idDossier)
        {
            //retourne la liste des catégories de prescriptions par ordre
            $select = $this->select()
                 ->setIntegrityCheck(false)
                 ->from(array('da' => 'dossieraffectation'))
                 ->join(array('dc' => 'datecommission'), 'da.ID_DATECOMMISSION_AFFECT = dc.ID_DATECOMMISSION')
                 ->where('da.ID_DOSSIER_AFFECT = ?', $idDossier)
                 ->where('dc.ID_COMMISSIONTYPEEVENEMENT = 2 OR dc.ID_COMMISSIONTYPEEVENEMENT = 3');

            return $this->getAdapter()->fetchRow($select);
        }

        /**
         * @return array
         */
        public function getDateLieesv2($idDateComm)
        {
            //retourne la liste des catégories de prescriptions par ordre
            $select = $this->select()
                 ->setIntegrityCheck(false)
                 ->from(array('dc' => 'datecommission'))
                 ->where('dc.ID_DATECOMMISSION = ?', $idDateComm)
                 ->orWhere('dc.DATECOMMISSION_LIEES = ?', $idDateComm)
                 ->order('DATE_COMMISSION');

            return $this->getAdapter()->fetchAll($select);
        }

        public function updateDependingDossierDates($datecommission)
        {
            $dbAffectDossier = new Model_DbTable_DossierAffectation();
            $dbDossier = new Model_DbTable_Dossier();

            // on récupère les dossiers liés à la commission
            $dossiersAffecte = $dbAffectDossier->fetchAll('ID_DATECOMMISSION_AFFECT = '.$datecommission->ID_DATECOMMISSION);
            $dossiersAffecteIds = array();
            foreach ($dossiersAffecte as $dossierAffecte) {
                $dossiersAffecteIds[] = $dossierAffecte['ID_DOSSIER_AFFECT'];
            }

            // si des dossiers sont liés, en fonction du type,
            // on update les dates en text dans les différents fields du dossiers pour
            // des cohérences de données
            if ($dossiersAffecteIds) {
                if (in_array($datecommission->ID_COMMISSIONTYPEEVENEMENT, array(1))) {
                    //COMMISSION EN SALLE
                    $dbDossier->update(array('DATECOMM_DOSSIER' => $datecommission->DATE_COMMISSION), 'ID_DOSSIER IN('.implode(',', $dossiersAffecteIds).')');
                } elseif (in_array($datecommission->ID_COMMISSIONTYPEEVENEMENT, array(2, 3))) {
                    //VISITE OU GROUPE DE VISITE
                    $dbDossier->update(array('DATEVISITE_DOSSIER' => $datecommission->DATE_COMMISSION), 'ID_DOSSIER IN ('.implode(',', $dossiersAffecteIds).')');
                }
            }
        }

        /**
         * @return array
         */
        public function getEventInCommission(
            $idUtilisateur = null,
            $idCommission = null,
            $start = null,
            $end = null,
            $ignoreEts = false)
        {
            $select = $this->select()
                           ->setIntegrityCheck(false)
                           ->from(array('d' => 'dossier'))
                           ->join(array('da' => 'dossieraffectation'), 'd.ID_DOSSIER = da.ID_DOSSIER_AFFECT')
                           ->join(array('dc' => 'datecommission'), 'da.ID_DATECOMMISSION_AFFECT = dc.ID_DATECOMMISSION')
                           ->join(array('c' => 'commission'), 'dc.COMMISSION_CONCERNE = c.ID_COMMISSION');
            if ($ignoreEts) {
                $select->joinLeft(array('ed' => 'etablissementdossier'), 'd.ID_DOSSIER = ed.ID_DOSSIER');
            } else {
                $select->join(array('ed' => 'etablissementdossier'), 'd.ID_DOSSIER = ed.ID_DOSSIER');
            }
            $select->join(array('dn' => 'dossiernature'), 'd.ID_DOSSIER = dn.ID_DOSSIER')
                   ->join(array('dnl' => 'dossiernatureliste'), 'dn.ID_NATURE = dnl.ID_DOSSIERNATURE')
                   ->join(array('dt' => 'dossiertype'), 'd.TYPE_DOSSIER = dt.ID_DOSSIERTYPE')
                   ->join(array('dp' => 'dossierpreventionniste'), 'd.ID_DOSSIER = dp.ID_DOSSIER')
                   ->join(array('u' => 'utilisateur'), 'dp.ID_PREVENTIONNISTE = u.ID_UTILISATEUR');
            if ($idUtilisateur !== null) {
                $select->where('u.ID_UTILISATEUR =  ?', $idUtilisateur);
            }
            if ($idCommission !== null) {
                $select->where('dc.COMMISSION_CONCERNE =  ?', $idCommission);
            }
            if ($start !== null) {
                $select->where('YEAR(dc.DATE_COMMISSION) >= ?', $start);
            }
            if ($end !== null) {
                $select->where('YEAR(dc.DATE_COMMISSION) <= ?', $end);
            }

            return $this->getAdapter()->fetchAll($select);
        }
    }
