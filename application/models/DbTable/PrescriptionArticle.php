<?php

class Model_DbTable_PrescriptionArticle extends Zend_Db_Table_Abstract
{
    // Nom de la base
    protected $_name = 'prescriptionarticle';

    // Clé primaire
    protected $_primary = 'ID_PRESCRIPTIONARTICLE';

    /**
     * @param mixed $idTexte
     *
     * @return array
     */
    public function recupPrescriptionArticle($idTexte)
    {
        // retourne la liste des catégories de prescriptions par ordre
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(['pa' => 'prescriptionarticle'])
            ->where('ID_PRESCRIPTIONTEXTE = ?', $idTexte)
            ->order('pa.NUM_PRESCRIPTIONARTICLE')
        ;

        return $this->getAdapter()->fetchAll($select);
    }

    public function recupMaxNumArticle($idTexte)
    {
        // retourne la liste des catégories de prescriptions par ordre
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(['pa' => 'prescriptionarticle'], 'max(pa.NUM_PRESCRIPTIONARTICLE) as maxnum')
            ->where('ID_PRESCRIPTIONTEXTE = ?', $idTexte)
        ;

        return $this->getAdapter()->fetchRow($select);
    }
}
