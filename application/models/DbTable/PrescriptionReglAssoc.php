<?php

class Model_DbTable_PrescriptionReglAssoc extends Zend_Db_Table_Abstract
{
    // Nom de la base
    protected $_name = 'prescriptionreglassoc';

    // Clé primaire
    protected $_primary = ['ID_PRESCRIPTIONREGL', 'NUM_PRESCRIPTIONASSOC'];

    /**
     * @param mixed $idPrescriptionRegl
     *
     * @return array
     */
    public function getPrescriptionReglAssoc($idPrescriptionRegl)
    {
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(['pr' => 'prescriptionregl'])
            ->join(['pra' => 'prescriptionreglassoc'], 'pr.ID_PRESCRIPTIONREGL = pra.ID_PRESCRIPTIONREGL')
            ->join(['pal' => 'prescriptionarticleliste'], 'pal.ID_ARTICLE = pra.ID_ARTICLE')
            ->join(['ptl' => 'prescriptiontexteliste'], 'ptl.ID_TEXTE = pra.ID_TEXTE')
            ->where('pr.ID_PRESCRIPTIONREGL = ?', $idPrescriptionRegl)
            ->order('pra.NUM_PRESCRIPTIONASSOC')
        ;

        return $this->getAdapter()->fetchAll($select);
    }

    /**
     * @param mixed $idPrescription
     *
     * @return array
     */
    public function getPrescriptionListeAssoc($idPrescription)
    {
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(['pr' => 'prescriptionregl'])
            ->join(['pra' => 'prescriptionreglassoc'], 'pr.ID_PRESCRIPTIONREGL = pra.ID_PRESCRIPTIONREGL')
            ->join(['pal' => 'prescriptionarticleliste'], 'pal.ID_ARTICLE = pra.ID_ARTICLE')
            ->join(['ptl' => 'prescriptiontexteliste'], 'ptl.ID_TEXTE = pra.ID_TEXTE')
            ->where('pr.ID_PRESCRIPTIONREGL = ?', $idPrescription)
        ;

        return $this->getAdapter()->fetchAll($select);
    }
}
