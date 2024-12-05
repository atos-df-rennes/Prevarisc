<?php

class Model_DbTable_DossierType extends Zend_Db_Table_Abstract
{
    // Nom de la base
    protected $_name = 'dossiertype';

    // Clé primaire
    protected $_primary = 'ID_DOSSIERTYPE';

    /**
     * @return array
     */
    public function getDossierType()
    {
        $select = 'SELECT *
            FROM dossiertype
        ;';

        return $this->getAdapter()->fetchAll($select);
    }
}
