<?php

class Model_DbTable_DossierAffectation extends Zend_Db_Table_Abstract
{
    // Nom de la base
    protected $_name = 'dossieraffectation';

    // Clé primaire
    protected $_primary = ['ID_DATECOMMISSION_AFFECT', 'ID_DOSSIER_AFFECT'];

    /**
     * @param mixed $idDateCom
     *
     * @return array
     */
    public function getDossierNonAffect($idDateCom)
    {
        // retourne l'ensemble des dossiers programés à la date de comm passée en param et dont les horaires N'ONT PAS été précisés
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(['doss' => 'dossier'])
            ->join(['dossAffect' => 'dossieraffectation'], 'doss.ID_DOSSIER = dossAffect.ID_DOSSIER_AFFECT')
            ->join(['dateComm' => 'datecommission'], 'dossAffect.ID_DATECOMMISSION_AFFECT = dateComm.ID_DATECOMMISSION')
            ->join(['dossNat' => 'dossiernature'], 'dossNat.ID_DOSSIER = doss.ID_DOSSIER')
            ->join(['dossNatListe' => 'dossiernatureliste'], 'dossNat.ID_NATURE = dossNatListe.ID_DOSSIERNATURE')
            ->join(['dossType' => 'dossiertype'], 'doss.TYPE_DOSSIER = dossType.ID_DOSSIERTYPE', 'LIBELLE_DOSSIERTYPE')
            ->joinLeft(['e' => 'etablissementdossier'], 'doss.ID_DOSSIER = e.ID_DOSSIER', [])
            ->joinLeft('etablissementinformations', 'e.ID_ETABLISSEMENT = etablissementinformations.ID_ETABLISSEMENT AND etablissementinformations.DATE_ETABLISSEMENTINFORMATIONS = ( SELECT MAX(etablissementinformations.DATE_ETABLISSEMENTINFORMATIONS) FROM etablissementinformations WHERE etablissementinformations.ID_ETABLISSEMENT = e.ID_ETABLISSEMENT )', 'LIBELLE_ETABLISSEMENTINFORMATIONS')
            ->where('dateComm.ID_DATECOMMISSION = ?', $idDateCom)
            ->where('dossAffect.HEURE_DEB_AFFECT IS NULL')
            ->where('dossAffect.HEURE_FIN_AFFECT IS NULL')
            ->order('dossAffect.NUM_DOSSIER')
            ->group('doss.ID_DOSSIER')
        ;

        return $this->getAdapter()->fetchAll($select);
    }

    /**
     * @param mixed $idDateCom
     *
     * @return array
     */
    public function getDossierAffect($idDateCom)
    {
        // retourne l'ensemble des dossiers programés à la date de comm passée en param et dont les horaires ONT été précisés
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(['doss' => 'dossier'])
            ->join(['dossAffect' => 'dossieraffectation'], 'doss.ID_DOSSIER = dossAffect.ID_DOSSIER_AFFECT')
            ->join(['dateComm' => 'datecommission'], 'dossAffect.ID_DATECOMMISSION_AFFECT = dateComm.ID_DATECOMMISSION')
            ->join(['dossNat' => 'dossiernature'], 'dossNat.ID_DOSSIER = doss.ID_DOSSIER')
            ->join(['dossNatListe' => 'dossiernatureliste'], 'dossNat.ID_NATURE = dossNatListe.ID_DOSSIERNATURE')
            ->join(['dossType' => 'dossiertype'], 'doss.TYPE_DOSSIER = dossType.ID_DOSSIERTYPE', 'LIBELLE_DOSSIERTYPE')
            ->joinLeft(['e' => 'etablissementdossier'], 'doss.ID_DOSSIER = e.ID_DOSSIER', [])
            ->joinLeft('etablissementinformations', 'e.ID_ETABLISSEMENT = etablissementinformations.ID_ETABLISSEMENT AND etablissementinformations.DATE_ETABLISSEMENTINFORMATIONS = ( SELECT MAX(etablissementinformations.DATE_ETABLISSEMENTINFORMATIONS) FROM etablissementinformations WHERE etablissementinformations.ID_ETABLISSEMENT = e.ID_ETABLISSEMENT )', 'LIBELLE_ETABLISSEMENTINFORMATIONS')
            ->where('dateComm.ID_DATECOMMISSION = ?', $idDateCom)
            ->where('dossAffect.HEURE_DEB_AFFECT IS NOT NULL')
            ->where('dossAffect.HEURE_FIN_AFFECT IS NOT NULL')
            ->group('doss.ID_DOSSIER')
        ;

        return $this->getAdapter()->fetchAll($select);
    }

    /**
     * @param int|string $idDateCom
     *
     * @return array
     */
    public function getAllDossierAffect($idDateCom)
    {
        $select = 'SELECT '.$this->_name.'.*
            FROM '.$this->_name.', dossier
            WHERE dossier.ID_DOSSIER = '.$this->_name.'.ID_DOSSIER_AFFECT
            AND '.$this->_name.".ID_DATECOMMISSION_AFFECT = '".$idDateCom."';
        ";

        return $this->getAdapter()->fetchAll($select);
    }

    /**
     * @param int|string $idDateCom
     *
     * @return array
     */
    public function getListDossierAffect($idDateCom)
    {
        $select = "SELECT ID_DOSSIER,OBJET_DOSSIER, VERROU_DOSSIER
            FROM dossieraffectation , dossier
            WHERE dossier.ID_DOSSIER = dossieraffectation.ID_DOSSIER_AFFECT
            AND dossieraffectation.ID_DATECOMMISSION_AFFECT = '".$idDateCom."'";

        return $this->getAdapter()->fetchAll($select);
    }

    /**
     * @param int|string $idDossier
     *
     * @return array
     */
    public function recupDateDossierAffect($idDossier)
    {
        $select = 'SELECT *
            FROM '.$this->_name.', datecommission
            WHERE  datecommission.ID_DATECOMMISSION = '.$this->_name.'.ID_DATECOMMISSION_AFFECT
            AND '.$this->_name.".ID_DOSSIER_AFFECT = '".$idDossier."'
            ORDER BY DATE_COMMISSION
        ";

        return $this->getAdapter()->fetchAll($select);
    }

    /**
     * @param int|string $idDossier
     */
    public function deleteDateDossierAffect($idDossier): void
    {
        $this->delete("ID_DOSSIER_AFFECT = '".$idDossier."'");
    }

    public function deleteDateDossierModifDateAffect($idDossier, $idDateComm): void
    {
        $this->delete([
            'ID_DOSSIER_AFFECT = ?' => $idDossier,
            'ID_DATECOMMISSION_AFFECT <> ?' => $idDateComm,
        ]);
    }

    /**
     * @param mixed $idDossier
     *
     * @return array
     */
    public function getDossierAffectAndType($idDossier)
    {
        // récupèration des affectations du dossier ainsi que le type d'affectation (salle / visite / visite de comm)
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(['doss' => 'dossier'])
            ->join(['dossAffect' => 'dossieraffectation'], 'doss.ID_DOSSIER = dossAffect.ID_DOSSIER_AFFECT')
            ->join(['dateComm' => 'datecommission'], 'dossAffect.ID_DATECOMMISSION_AFFECT = dateComm.ID_DATECOMMISSION')
            ->where('doss.ID_DOSSIER = ?', $idDossier)
        ;

        return $this->getAdapter()->fetchAll($select);
    }
}
