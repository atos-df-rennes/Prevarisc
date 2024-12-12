<?php

class Model_DbTable_PrescriptionCat extends Zend_Db_Table_Abstract
{
    // Nom de la base
    protected $_name = 'prescriptioncat';

    // Clé primaire
    protected $_primary = 'ID_PRESCRIPTION_CAT';

    /**
     * @return array
     */
    public function recupPrescriptionCat()
    {
        // retourne la liste des catégories de prescriptions par ordre
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(['pc' => 'prescriptioncat'])
            ->order('pc.NUM_PRESCRIPTION_CAT')
        ;

        return $this->getAdapter()->fetchAll($select);
    }

    public function recupMaxNumCat()
    {
        // retourne la liste des catégories de prescriptions par ordre
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(['pc' => 'prescriptioncat'], 'max(pc.NUM_PRESCRIPTION_CAT) as maxnum')
        ;

        return $this->getAdapter()->fetchRow($select);
    }
}
