<?php

class Model_DbTable_Commission extends Zend_Db_Table_Abstract
{
    // Nom de la base
    protected $_name = 'commission';

    // Clé primaire
    protected $_primary = 'ID_COMMISSION';

    protected $_referenceMap = [
        'commissiontype' => [
            'columns' => 'ID_COMMISSIONTYPE',
            'refTableClass' => 'Model_DbTable_CommissionType',
            'refColumns' => 'ID_COMMISSIONTYPE',
        ],
    ];

    public function fetchAllPK(): array
    {
        $all = $this->getCommissions();
        $result = [];
        foreach ($all as $row) {
            $result[$row['ID_COMMISSION']] = $row;
        }

        return $result;
    }

    // Donne la liste des catégories

    /**
     * @param float|int|string $id
     *
     * @return array
     */
    public function getCommissions($id = null)
    {
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from('commission')
            ->join('commissiontype', 'commission.ID_COMMISSIONTYPE = commissiontype.ID_COMMISSIONTYPE')
        ;

        if (null != $id) {
            $select->where('ID_COMMISSION = '.$id);

            return $this->fetchRow($select)->toArray();
        }

        return $this->fetchAll($select)->toArray();
    }

    // Donne la liste des catégories

    /**
     * @param float|int|string $type
     *
     * @return array
     */
    public function getCommissionsByType($type)
    {
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from('commission')
            ->join('commissiontype', 'commissiontype.ID_COMMISSIONTYPE = commission.ID_COMMISSIONTYPE', [])
            ->where('commission.ID_COMMISSIONTYPE = '.$type)
        ;

        return $this->fetchAll($select)->toArray();
    }

    /**
     * @param int|string $crit
     *
     * @return array
     */
    public function commissionListe($crit)
    {
        // Autocomplétion sur la liste des commission
        $select = "SELECT ID_COMMISSION, LIBELLE_COMMISSION
            FROM commission
            WHERE LIBELLE_COMMISSION LIKE '%".$crit."%';
        ";

        return $this->getAdapter()->fetchAll($select);
    }

    /**
     * @return array
     */
    public function getAllCommissions()
    {
        // Récupération de l'ensemble des commissions
        $select = 'SELECT ID_COMMISSION, LIBELLE_COMMISSION
            FROM commission
            ORDER BY LIBELLE_COMMISSION
        ';

        return $this->getAdapter()->fetchAll($select);
    }

    /**
     * @param int|string $id
     *
     * @return array
     */
    public function getLibelleCommissions($id)
    {
        // Récupération de l'ensemble des commissions
        $select = "SELECT LIBELLE_COMMISSION
            FROM commission
            WHERE ID_COMMISSION = '".$id."'";

        return $this->getAdapter()->fetchAll($select);
    }

    /**
     * @param int|string $idCommission
     */
    public function commissionPeriodicite($idCommission)
    {
        $select = "SELECT commissiontype.FREQUENCE_COMMISSIONTYPE
            FROM commissiontype, commission
            WHERE commission.ID_COMMISSIONTYPE = commissiontype.ID_COMMISSIONTYPE
            AND commission.ID_COMMISSION = '".$idCommission."';
        ";

        return $this->getAdapter()->fetchRow($select);
    }

    /**
     * @param int|string $commune
     * @param mixed      $categorie
     * @param mixed      $type
     * @param mixed      $localsommeil
     *
     * @return null|array
     */
    public function getCommission($commune, $categorie, $type, $localsommeil)
    {
        // Check de la sous commission / comunale / interco / arrondissement
        // Récupération des types de commission
        $model_types = new Model_DbTable_CommissionType();
        $array_typesCommission = $model_types->fetchAll(null, 'ID_COMMISSIONTYPE DESC')->toArray();

        foreach ($array_typesCommission as $row_typeCommission) {
            $select = $this->select()
                ->setIntegrityCheck(false)
                ->from('commissionregle', ['ID_GROUPEMENT', 'NUMINSEE_COMMUNE', 'ID_COMMISSION'])
                ->joinLeft('commission', 'commission.ID_COMMISSION = commissionregle.ID_COMMISSION', [])
                ->joinLeft('commissionregletype', 'commissionregle.ID_REGLE = commissionregletype.ID_REGLE', [])
                ->joinLeft('commissionreglecategorie', 'commissionregle.ID_REGLE = commissionreglecategorie.ID_REGLE', [])
                ->joinLeft('commissionreglelocalsommeil', 'commissionregle.ID_REGLE = commissionreglelocalsommeil.ID_REGLE', [])
                ->joinLeft('adressecommune', 'adressecommune.NUMINSEE_COMMUNE = commissionregle.NUMINSEE_COMMUNE', [])
                ->where('commissionreglecategorie.ID_CATEGORIE = ?', $categorie)
                ->where('commissionregletype.ID_TYPE = ?', $type)
                ->where('commissionreglelocalsommeil.LOCALSOMMEIL = ?', $localsommeil)
                ->where('commission.ID_COMMISSIONTYPE = ?', $row_typeCommission['ID_COMMISSIONTYPE'])
            ;

            $results = $this->fetchAll($select);

            if (null != $results) {
                foreach ($results as $result) {
                    if (null != $result->NUMINSEE_COMMUNE) {
                        if ($result->NUMINSEE_COMMUNE == $commune) {
                            return $this->find($result->ID_COMMISSION)->toArray();
                        }
                    } elseif ($result->ID_GROUPEMENT) {
                        $model_groupementCommune = new Model_DbTable_GroupementCommune();
                        $row_groupement = $model_groupementCommune->fetchRow("ID_GROUPEMENT = '".$result->ID_GROUPEMENT."' AND NUMINSEE_COMMUNE = '".$commune."'");

                        if ($row_groupement instanceof Zend_Db_Table_Row_Abstract) {
                            return $this->find($result->ID_COMMISSION)->toArray();
                        }
                    }
                }
            }
        }

        return null;
    }

    /**
     * @param int|string $commune
     * @param mixed      $classe
     * @param mixed      $localsommeil
     *
     * @return null|array
     */
    public function getCommissionIGH($commune, $classe, $localsommeil)
    {
        // Check de la sous commission / comunale / interco / arrondissement
        // Récupération des types de commission
        $model_types = new Model_DbTable_CommissionType();
        $array_typesCommission = $model_types->fetchAll('ID_COMMISSIONTYPE != 5')->toArray();

        foreach ($array_typesCommission as $row_typeCommission) {
            $select = $this->select()
                ->setIntegrityCheck(false)
                ->from('commissionregle', ['ID_GROUPEMENT', 'NUMINSEE_COMMUNE', 'ID_COMMISSION'])
                ->joinLeft('commission', 'commission.ID_COMMISSION = commissionregle.ID_COMMISSION', [])
                ->joinLeft('commissionregleclasse', 'commissionregle.ID_REGLE = commissionregleclasse.ID_REGLE', [])
                ->joinLeft('commissionreglelocalsommeil', 'commissionregle.ID_REGLE = commissionreglelocalsommeil.ID_REGLE', [])
                ->joinLeft('adressecommune', 'adressecommune.NUMINSEE_COMMUNE = commissionregle.NUMINSEE_COMMUNE', [])
                ->where('commissionregleclasse.ID_CLASSE = ?', $classe)
                ->where('commissionreglelocalsommeil.LOCALSOMMEIL = ?', $localsommeil)
                ->where('commission.ID_COMMISSIONTYPE = ?', $row_typeCommission['ID_COMMISSIONTYPE'])
            ;

            $results = $this->fetchAll($select);

            if (null != $results) {
                foreach ($results as $result) {
                    if (null != $result->NUMINSEE_COMMUNE) {
                        if ($result->NUMINSEE_COMMUNE == $commune) {
                            return $this->find($result->ID_COMMISSION)->toArray();
                        }
                    } elseif ($result->ID_GROUPEMENT) {
                        $model_groupementCommune = new Model_DbTable_GroupementCommune();
                        $row_groupement = $model_groupementCommune->fetchRow("ID_GROUPEMENT = '".$result->ID_GROUPEMENT."' AND NUMINSEE_COMMUNE = '".$commune."'");

                        if ($row_groupement instanceof Zend_Db_Table_Row_Abstract) {
                            return $this->find($result->ID_COMMISSION)->toArray();
                        }
                    }
                }
            }
        }

        return null;
    }

    /**
     * @psalm-return array<int, array{NUMINSEE_COMMUNE:array<int, string|mixed>, LOCALSOMMEIL:mixed, ID_TYPE:mixed, ID_CLASSE:mixed, ID_CATEGORIE:mixed}>
     *
     * @param mixed $id_commission
     *
     * @return array[]
     */
    public function getRegles($id_commission): array
    {
        $model_groupementCommune = new Model_DbTable_GroupementCommune();

        $regles = [];

        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from('commissionregle', ['ID_GROUPEMENT', 'NUMINSEE_COMMUNE'])
            ->joinLeft('commission', 'commission.ID_COMMISSION = commissionregle.ID_COMMISSION', [])
            ->joinLeft('commissionregletype', 'commissionregle.ID_REGLE = commissionregletype.ID_REGLE', ['ID_TYPE'])
            ->joinLeft('commissionreglecategorie', 'commissionregle.ID_REGLE = commissionreglecategorie.ID_REGLE', ['ID_CATEGORIE'])
            ->joinLeft('commissionregleclasse', 'commissionregle.ID_REGLE = commissionregleclasse.ID_REGLE', ['ID_CLASSE'])
            ->joinLeft('commissionreglelocalsommeil', 'commissionregle.ID_REGLE = commissionreglelocalsommeil.ID_REGLE', ['LOCALSOMMEIL'])
            ->joinLeft('adressecommune', 'adressecommune.NUMINSEE_COMMUNE = commissionregle.NUMINSEE_COMMUNE', [])
            ->where('commission.ID_COMMISSION = ?', $id_commission)
        ;

        $results = $this->fetchAll($select);

        if (null == $results) {
            return $regles;
        }

        $groupement_cache = [];
        foreach ($results as $result) {
            $communes = [];
            if (null != $result->NUMINSEE_COMMUNE) {
                $communes = [$result->NUMINSEE_COMMUNE];
            } elseif (null != $result->ID_GROUPEMENT) {
                if (!isset($groupement_cache[$result->ID_GROUPEMENT])) {
                    $groupement_cache[$result->ID_GROUPEMENT] = [];
                    $row_groupement = $model_groupementCommune->fetchAll("ID_GROUPEMENT = '".$result->ID_GROUPEMENT."'");
                    foreach ($row_groupement as $row) {
                        $groupement_cache[$result->ID_GROUPEMENT][] = $row['NUMINSEE_COMMUNE'];
                    }
                }

                $communes = $groupement_cache[$result->ID_GROUPEMENT];
            }

            $regles[] = [
                'NUMINSEE_COMMUNE' => $communes,
                'LOCALSOMMEIL' => $result->LOCALSOMMEIL,
                'ID_TYPE' => $result->ID_TYPE,
                'ID_CLASSE' => $result->ID_CLASSE,
                'ID_CATEGORIE' => $result->ID_CATEGORIE,
            ];
        }

        return $regles;
    }
}
