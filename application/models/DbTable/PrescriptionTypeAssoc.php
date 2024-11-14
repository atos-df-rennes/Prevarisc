<?php

class Model_DbTable_PrescriptionTypeAssoc extends Zend_Db_Table_Abstract
{
    // Nom de la base
    protected $_name = 'prescriptiontypeassoc';

    // Clé primaire
    protected $_primary = ['ID_PRESCRIPTIONTYPE', 'NUM_PRESCRIPTIONASSOC'];

    /**
     * @param mixed $idPrescriptionType
     *
     * @return array
     */
    public function getPrescriptionAssoc($idPrescriptionType)
    {
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(['pt' => 'prescriptiontype'])
            ->join(['pta' => 'prescriptiontypeassoc'], 'pt.ID_PRESCRIPTIONTYPE = pta.ID_PRESCRIPTIONTYPE')
            ->join(['pal' => 'prescriptionarticleliste'], 'pal.ID_ARTICLE = pta.ID_ARTICLE')
            ->join(['ptl' => 'prescriptiontexteliste'], 'ptl.ID_TEXTE = pta.ID_TEXTE')
            ->where('pta.ID_PRESCRIPTIONTYPE = ?', $idPrescriptionType)
            ->order('pta.NUM_PRESCRIPTIONASSOC')
        ;

        return $this->getAdapter()->fetchAll($select);
    }
}
