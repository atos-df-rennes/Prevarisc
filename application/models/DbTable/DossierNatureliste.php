<?php

class Model_DbTable_DossierNatureliste extends Zend_Db_Table_Abstract
{
    protected $_name = 'dossiernatureliste'; // Nom de la base
    protected $_primary = 'ID_DOSSIERNATURE'; // Clé primaire

    /**
     * @param mixed $type
     *
     * @return array
     */
    public function getDossierNature($type)
    {
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(['dnl' => 'dossiernatureliste'])
            ->where('ID_DOSSIERTYPE = ?', $type)
            ->where('ORDRE IS NOT NULL')
            ->order('dnl.ORDRE')
        ;

        return $this->getAdapter()->fetchAll($select);
    }
}
