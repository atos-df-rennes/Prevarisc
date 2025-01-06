<?php

class Model_DbTable_CoucheCarto extends Zend_Db_Table_Abstract
{
    // Nom de la base
    protected $_name = 'couchecarto';

    // Nom de la base
    protected $_primary = 'ID_COUCHECARTO';

    public function getAll(): array
    {
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(['cc' => 'couchecarto'])
            ->order('ORDRE_COUCHECARTO')
        ;

        return $this->fetchAll($select)->toArray();
    }
}
