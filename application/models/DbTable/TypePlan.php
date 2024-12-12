<?php

/*
    Type de plan

    Cette classe sert pour r�cup�rer les cat�gories, et les administrer

*/

class Model_DbTable_TypePlan extends Zend_Db_Table_Abstract
{
    // Nom de la base
    protected $_name = 'typeplan';

    // Clé primaire
    protected $_primary = 'ID_TYPEPLAN';

    public function fetchAllPK(): array
    {
        $all = $this->fetchAll()->toArray();
        $result = [];
        foreach ($all as $row) {
            $result[$row['ID_TYPEPLAN']] = $row;
        }

        return $result;
    }
}
