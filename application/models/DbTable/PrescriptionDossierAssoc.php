<?php

class Model_DbTable_PrescriptionDossierAssoc extends Zend_Db_Table_Abstract
{
    // Nom de la base
    protected $_name = 'prescriptiondossierassoc';

    // Clé primaire
    protected $_primary = ['ID_PRESCRIPTION_DOSSIER', 'NUM_PRESCRIPTION_DOSSIERASSOC'];

    /**
     * @param mixed $idPrescriptionDossier
     *
     * @return array
     */
    public function getPrescriptionDossierAssoc($idPrescriptionDossier)
    {
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(['pd' => 'prescriptiondossier'])
            ->join(['pda' => 'prescriptiondossierassoc'], 'pd.ID_PRESCRIPTION_DOSSIER = pda.ID_PRESCRIPTION_DOSSIER')
            ->join(['pal' => 'prescriptionarticleliste'], 'pal.ID_ARTICLE = pda.ID_ARTICLE')
            ->join(['ptl' => 'prescriptiontexteliste'], 'ptl.ID_TEXTE = pda.ID_TEXTE')
            ->where('pda.ID_PRESCRIPTION_DOSSIER = ?', $idPrescriptionDossier)
            ->order('pda.NUM_PRESCRIPTION_DOSSIERASSOC')
        ;

        return $this->getAdapter()->fetchAll($select);
    }

    /**
     * @param mixed $idPrescriptionType
     * @param mixed $idPrescriptionDossier
     *
     * @return array
     */
    public function getPrescriptionTypeAssoc($idPrescriptionType, $idPrescriptionDossier)
    {
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(['pt' => 'prescriptiontype'])
            ->join(['pta' => 'prescriptiontypeassoc'], 'pt.ID_PRESCRIPTIONTYPE = pta.ID_PRESCRIPTIONTYPE')
            ->join(['pal' => 'prescriptionarticleliste'], 'pal.ID_ARTICLE = pta.ID_ARTICLE')
            ->join(['ptl' => 'prescriptiontexteliste'], 'ptl.ID_TEXTE = pta.ID_TEXTE')
            ->join(['pd' => 'prescriptiondossier'], 'pd.ID_PRESCRIPTION_TYPE = pt.ID_PRESCRIPTIONTYPE')
            ->where('pt.ID_PRESCRIPTIONTYPE = ?', $idPrescriptionType)
            ->where('pd.ID_PRESCRIPTION_DOSSIER = ?', $idPrescriptionDossier)
            ->order('pta.NUM_PRESCRIPTIONASSOC')
        ;

        return $this->getAdapter()->fetchAll($select);
    }

    public function deletePrescrionAssoc($idPrescriptionDossier)
    {
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(['pd' => 'prescriptiondossierassoc'])
            ->where('pda.ID_PRESCRIPTION_DOSSIER = ?', $idPrescriptionDossier)
        ;

        return $this->getAdapter()->fetchAll($select)->delete();
    }
}
