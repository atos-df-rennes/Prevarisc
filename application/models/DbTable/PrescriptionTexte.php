<?php

class Model_DbTable_PrescriptionTexte extends Zend_Db_Table_Abstract
{
    // Nom de la base
    protected $_name = 'prescriptiontexte';

    // Clé primaire
    protected $_primary = 'ID_PRESCRIPTIONTEXTE';

    /**
     * @param mixed $idCategorie
     *
     * @return array
     */
    public function recupPrescriptionTexte($idCategorie)
    {
        // retourne la liste des catégories de prescriptions par ordre
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(['pt' => 'prescriptiontexte'])
            ->where('ID_PRESCRIPTIONCAT = ?', $idCategorie)
            ->order('pt.NUM_PRESCRIPTIONTEXTE')
        ;

        return $this->getAdapter()->fetchAll($select);
    }

    public function recupMaxNumTexte($idCategorie)
    {
        // retourne la liste des catégories de prescriptions par ordre
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(['pt' => 'prescriptiontexte'], 'max(pt.NUM_PRESCRIPTIONTEXTE) as maxnum')
            ->where('ID_PRESCRIPTIONCAT = ?', $idCategorie)
        ;

        return $this->getAdapter()->fetchRow($select);
    }
}
