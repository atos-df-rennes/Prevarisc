<?php

class Model_DbTable_PrescriptionRegl extends Zend_Db_Table_Abstract
{
    // Nom de la base
    protected $_name = 'prescriptionregl';

    // Clé primaire
    protected $_primary = 'ID_PRESCRIPTIONREGL';

    /**
     * @param mixed      $type
     * @param null|mixed $mode
     *
     * @return array
     */
    public function recupPrescRegl($type, $mode = null)
    {
        // retourne la liste des catégories de prescriptions par ordre
        $typePresc = null;
        if ('etude' == $type) {
            $typePresc = 1;
        } elseif ('visite' == $type) {
            $typePresc = 2;
        }

        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(['pre' => 'prescriptionregl'])
            ->where('pre.PRESCRIPTIONREGL_TYPE = ?', $typePresc)
        ;

        if (null != $mode) {
            $select->where('pre.PRESCRIPTIONREGL_VISIBLE = 1');
        }

        return $this->getAdapter()->fetchAll($select);
    }

    public function getPrescription($idPrescription)
    {
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(['pre' => 'prescriptionregl'])
            ->where('pre.ID_PRESCRIPTIONREGL = ?', $idPrescription)
        ;

        return $this->getAdapter()->fetchRow($select);
    }
}
