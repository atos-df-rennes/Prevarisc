<?php

class Model_DbTable_TypeTextesAppl extends Zend_Db_Table_Abstract
{
    // Nom de la base
    protected $_name = 'typetextesappl';

    // Clé primaire
    protected $_primary = 'ID_TYPETEXTEAPPL';

    // récupération de la liste des types

    /**
     * @return array
     */
    public function getType()
    {
        $select = $this->select()
            ->from(['ty' => 'typetextesappl'])
            ->order('ty.ID_TYPETEXTEAPPL')
        ;

        return $this->getAdapter()->fetchAll($select);
    }
}
