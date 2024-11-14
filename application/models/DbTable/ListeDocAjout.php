<?php

class Model_DbTable_ListeDocAjout extends Zend_Db_Table_Abstract
{
    // Nom de la base
    protected $_name = 'listedocajout';

    // Clé primaire
    protected $_primary = 'ID_DOCAJOUT';

    // récupere les éventuels documents qui auraient été ajoutés

    /**
     * @param int|string $id_dossier
     *
     * @return array
     */
    public function getDocAjout($id_dossier)
    {
        $select = "SELECT *
        FROM listedocajout
        WHERE id_dossier = '".$id_dossier."'
        ORDER BY ID_DOCAJOUT;";

        return $this->getAdapter()->fetchAll($select);
    }

    /**
     * @param int|string $id_dossier
     * @param int|string $id_nature
     *
     * @return array
     */
    public function getDocToDelete($id_dossier, $id_nature)
    {
        $select = "SELECT *
        FROM listedocajout
        WHERE id_dossier = '".$id_dossier."'
        AND id_nature = '".$id_nature."'
        ORDER BY ID_DOCAJOUT;";

        return $this->getAdapter()->fetchAll($select);
    }

    public function getLastId()
    {
        $select = 'SELECT MAX(ID_DOCAJOUT)
        FROM listedocajout;';

        return $this->getAdapter()->fetchRow($select);
    }
}
