<?php

class Model_DbTable_CapsuleRubrique extends Zend_Db_Table_Abstract
{
    // Nom de la base
    protected $_name = 'capsulerubrique';

    // Clé primaire
    protected $_primary = 'ID_CAPSULERUBRIQUE';

    public function getCapsuleRubriqueIdByName(string $name): array
    {
        $select = $this->select()
            ->from(['cr' => 'capsulerubrique'], ['ID_CAPSULERUBRIQUE'])
            ->where('cr.NOM_INTERNE = ?', $name)
        ;

        return $this->fetchRow($select)->toArray();
    }
}
