<?php

class Model_DbTable_Categorie extends Zend_Db_Table_Abstract
{
    // Nom de la base
    protected $_name = 'categorie';

    // Clé primaire
    protected $_primary = 'ID_CATEGORIE';

    // Donne la liste des catégories

    /**
     * @param float|int|string $id
     *
     * @return array
     */
    public function getCategories($id = null)
    {
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from('categorie')
        ;

        if (null != $id) {
            $select->where('ID_CATEGORIE = '.$id);

            return $this->fetchRow($select)->toArray();
        }

        return $this->fetchAll($select)->toArray();
    }

    public function fetchAllPK(): array
    {
        $all = $this->fetchAll()->toArray();
        $result = [];
        foreach ($all as $row) {
            $result[$row['ID_CATEGORIE']] = $row;
        }

        return $result;
    }
}
