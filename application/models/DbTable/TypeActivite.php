<?php

class Model_DbTable_TypeActivite extends Zend_Db_Table_Abstract
{
    // Nom de la base
    protected $_name = 'typeactivite';

    // Clé primaire
    protected $_primary = 'ID_TYPEACTIVITE';

    /**
     * @return null|array
     */
    public function myfetchAll()
    {
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from('typeactivite')
            ->join('type', 'type.ID_TYPE = typeactivite.ID_TYPE')
            ->order('type.LIBELLE_TYPE')
        ;

        $result = $this->fetchAll($select);

        return null == $result ? null : $result->toArray();
    }
}
