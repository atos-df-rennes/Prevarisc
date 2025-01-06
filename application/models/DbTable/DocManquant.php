<?php

class Model_DbTable_DocManquant extends Zend_Db_Table_Abstract
{
    // Nom de la base
    protected $_name = 'docmanquant';

    // Clé primaire
    protected $_primary = 'ID_DOCMANQUANT';

    /**
     * @return array
     */
    public function getDocManquant()
    {
        // retourne la liste des catégories de prescriptions par ordre
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(['dm' => 'docmanquant'])
        ;

        return $this->getAdapter()->fetchAll($select);
    }
}
