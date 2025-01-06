<?php

class Model_DbTable_AdresseRue extends Zend_Db_Table_Abstract
{
    // Nom de la base
    protected $_name = 'adresserue';

    // Clé primaire
    protected $_primary = 'ID_RUE';

    public function getLibelleRue($idRue)
    {
        $select = $this->select();

        $select->from('adresserue')
            ->where('ID_RUE = ?', $idRue)
        ;

        return $this->getAdapter()->fetchRow($select);
    }
}
