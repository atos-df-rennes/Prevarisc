<?php

class Model_DbTable_DateCommissionPj extends Zend_Db_Table_Abstract
{
    // Nom de la base
    protected $_name = 'datecommissionpj';

    // Clé primaire
    protected $_primary = 'ID_DATECOMMISSION';

    /**
     * @param mixed $dateCommId
     *
     * @return array
     */
    public function getDossiersInfos($dateCommId)
    {
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(['doss' => 'dossier'])
            ->join(['dossAffect' => 'dossieraffectation'], 'doss.ID_DOSSIER = dossAffect.ID_DOSSIER_AFFECT')
            ->join(['dateComm' => 'datecommission'], 'dossAffect.ID_DATECOMMISSION_AFFECT = dateComm.ID_DATECOMMISSION')
            ->join(['dossNat' => 'dossiernature'], 'dossNat.ID_DOSSIER = doss.ID_DOSSIER')
            ->join(['dossNatListe' => 'dossiernatureliste'], 'dossNat.ID_NATURE = dossNatListe.ID_DOSSIERNATURE')
            ->where('dateComm.ID_DATECOMMISSION = ?', $dateCommId)
            ->group('doss.ID_DOSSIER')
        ;

        return $this->getAdapter()->fetchAll($select);
    }

    /**
     * @param mixed $dateCommId
     *
     * @return array
     */
    public function getDossiersInfosByHour($dateCommId)
    {
        // Retourne les dossiers avec toutes les informations le concernant class�s par heure
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(['doss' => 'dossier'])
            ->join(['dossAffect' => 'dossieraffectation'], 'doss.ID_DOSSIER = dossAffect.ID_DOSSIER_AFFECT')
            ->join(['dateComm' => 'datecommission'], 'dossAffect.ID_DATECOMMISSION_AFFECT = dateComm.ID_DATECOMMISSION')
            ->join(['dossNat' => 'dossiernature'], 'dossNat.ID_DOSSIER = doss.ID_DOSSIER')
            ->join(['dossNatListe' => 'dossiernatureliste'], 'dossNat.ID_NATURE = dossNatListe.ID_DOSSIERNATURE')
            ->where('dateComm.ID_DATECOMMISSION = ?', $dateCommId)
            ->group('doss.ID_DOSSIER')
            ->order('dossAffect.HEURE_DEB_AFFECT')
        ;

        return $this->getAdapter()->fetchAll($select);
    }

    /**
     * @param mixed $dateCommId
     *
     * @return array
     */
    public function getDossiersInfosByOrder($dateCommId)
    {
        // Retourne les dossiers avec toutes les informations le concernant class�s par ordre
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(['doss' => 'dossier'])
            ->join(['dossAffect' => 'dossieraffectation'], 'doss.ID_DOSSIER = dossAffect.ID_DOSSIER_AFFECT')
            ->join(['dateComm' => 'datecommission'], 'dossAffect.ID_DATECOMMISSION_AFFECT = dateComm.ID_DATECOMMISSION')
            ->join(['dossNat' => 'dossiernature'], 'dossNat.ID_DOSSIER = doss.ID_DOSSIER')
            ->join(['dossNatListe' => 'dossiernatureliste'], 'dossNat.ID_NATURE = dossNatListe.ID_DOSSIERNATURE')
            ->where('dateComm.ID_DATECOMMISSION = ?', $dateCommId)
            ->group('doss.ID_DOSSIER')
            ->order('dossAffect.NUM_DOSSIER')
        ;

        return $this->getAdapter()->fetchAll($select);
    }

    /**
     * @param mixed $dateCommId
     *
     * @return array
     */
    public function TESTRECUPDOSS($dateCommId)
    {
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(['doss' => 'dossier'])
            ->join(['dossAffect' => 'dossieraffectation'], 'doss.ID_DOSSIER = dossAffect.ID_DOSSIER_AFFECT')
            ->join(['dateComm' => 'datecommission'], 'dossAffect.ID_DATECOMMISSION_AFFECT = dateComm.ID_DATECOMMISSION')
            ->join(['dossNat' => 'dossiernature'], 'dossNat.ID_DOSSIER = doss.ID_DOSSIER')
            ->join(['dossNatListe' => 'dossiernatureliste'], 'dossNat.ID_NATURE = dossNatListe.ID_DOSSIERNATURE')
            ->where('dateComm.ID_DATECOMMISSION = ?', $dateCommId)
            ->group('doss.ID_DOSSIER')
            ->order('dossAffect.NUM_DOSSIER')
        ;

        return $this->getAdapter()->fetchAll($select);
    }

    /**
     * @param mixed $dateCommId
     *
     * @return array
     */
    public function TESTRECUPDOSSHEURE($dateCommId)
    {
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(['doss' => 'dossier'])
            ->join(['dossAffect' => 'dossieraffectation'], 'doss.ID_DOSSIER = dossAffect.ID_DOSSIER_AFFECT')
            ->join(['dateComm' => 'datecommission'], 'dossAffect.ID_DATECOMMISSION_AFFECT = dateComm.ID_DATECOMMISSION')
            ->join(['dossNat' => 'dossiernature'], 'dossNat.ID_DOSSIER = doss.ID_DOSSIER')
            ->join(['dossNatListe' => 'dossiernatureliste'], 'dossNat.ID_NATURE = dossNatListe.ID_DOSSIERNATURE')
            ->where('dateComm.ID_DATECOMMISSION = ?', $dateCommId)
            ->where('dossAffect.HEURE_DEB_AFFECT IS NOT NULL')
            ->group('doss.ID_DOSSIER')
            ->order('dossAffect.HEURE_DEB_AFFECT')
        ;

        return $this->getAdapter()->fetchAll($select);
    }

    /**
     * @param int|string $idComm
     *
     * @return null|array
     */
    public function getPjInfos($idComm)
    {
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from('piecejointe')
            ->join('datecommissionpj', 'datecommissionpj.ID_PIECEJOINTE = piecejointe.ID_PIECEJOINTE')
            ->where('datecommissionpj.ID_DATECOMMISSION = '.$idComm)
        ;

        return (null != $this->fetchAll($select)) ? $this->fetchAll($select)->toArray() : null;
    }
}
