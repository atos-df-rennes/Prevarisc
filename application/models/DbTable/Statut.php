<?php

/*
    Statut

    Cette classe sert pour récupérer les Statuts, et les administrer

*/

class Model_DbTable_Statut extends Zend_Db_Table_Abstract
{
    // Nom de la base
    protected $_name = 'statut';

    // Clé primaire
    protected $_primary = 'ID_STATUT';

    // Donne la liste des catégories

    /**
     * @param float|int|string $id
     *
     * @return array
     */
    public function getStatuts($id = null)
    {
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from('statut')
        ;

        if (null != $id) {
            $select->where('ID_STATUT = '.$id);

            return $this->fetchRow($select)->toArray();
        }

        return $this->fetchAll($select)->toArray();
    }
}
