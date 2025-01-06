<?php

/*
    Genre

    Cette classe sert pour récupérer les genre, et les administrer

*/

class Model_DbTable_Genre extends Zend_Db_Table_Abstract
{
    // Nom de la base
    protected $_name = 'genre';

    // Clé primaire
    protected $_primary = 'ID_GENRE';

    // Donne la liste des genres

    /**
     * @param float|int|string $id
     *
     * @return array
     */
    public function getGenre($id = null)
    {
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from('genre')
        ;

        if (null != $id) {
            $select->where('ID_GENRE = '.$id);

            return $this->fetchRow($select)->toArray();
        }

        return $this->fetchAll($select)->toArray();
    }

    /**
     * @return Zend_Db_Table_Rowset_Abstract
     */
    public function fetchAllSaufSite()
    {
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from('genre')
            ->where('ID_GENRE != 1')
        ;

        return $this->fetchAll($select);
    }
}
