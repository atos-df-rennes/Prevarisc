<?php

class Model_DbTable_PrescriptionRegl extends Zend_Db_Table_Abstract
{
    protected $_name = 'prescriptionregl'; // Nom de la base
    protected $_primary = 'ID_PRESCRIPTIONREGL'; // Clé primaire

    /**
     * @return array
     */
    public function recupPrescRegl($type, $mode = null)
    {
        //retourne la liste des catégories de prescriptions par ordre
        $typePresc = null;
        if ($type == 'etude') {
            $typePresc = 1;
        } elseif ($type == 'visite') {
            $typePresc = 2;
        }

        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(['pre' => 'prescriptionregl'])
            ->where('pre.PRESCRIPTIONREGL_TYPE = ?', $typePresc);

        if ($mode != null) {
            $select->where('pre.PRESCRIPTIONREGL_VISIBLE = 1');
        }

        return $this->getAdapter()->fetchAll($select);
    }

    public function getPrescription($idPrescription)
    {
        $select = $this->select()
             ->setIntegrityCheck(false)
             ->from(['pre' => 'prescriptionregl'])
             ->where('pre.ID_PRESCRIPTIONREGL = ?', $idPrescription);

        return $this->getAdapter()->fetchRow($select);
    }
}
