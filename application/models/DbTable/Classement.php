<?php

/*
    Classement

    Cette classe sert pour recuperer les classement, et les administrer

*/
class Model_DbTable_Classement extends Zend_Db_Table_Abstract
{
    // Nom de la base
    protected $_name = 'classement';

    // Cle primaire
    protected $_primary = 'ID_CLASSEMENT';

    public function fetchAllPK(): array
    {
        $all = $this->fetchAll()->toArray();
        $result = [];
        foreach ($all as $row) {
            $result[$row['ID_CLASSEMENT']] = $row;
        }

        return $result;
    }
}
