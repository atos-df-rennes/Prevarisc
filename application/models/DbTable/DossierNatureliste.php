<?php

class Model_DbTable_DossierNatureliste extends Zend_Db_Table_Abstract
{
    // Nom de la base
    protected $_name = 'dossiernatureliste';

    // Clé primaire
    protected $_primary = 'ID_DOSSIERNATURE';

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
