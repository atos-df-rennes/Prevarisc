<?php

class Model_DbTable_Classe extends Zend_Db_Table_Abstract
{
    // Nom de la base
    protected $_name = 'classe';

    // Clé primaire
    protected $_primary = 'ID_CLASSE';

    public function fetchAllPK(): array
    {
        $all = $this->fetchAll()->toArray();
        $result = [];
        foreach ($all as $row) {
            $result[$row['ID_CLASSE']] = $row;
        }

        return $result;
    }
}
