<?php

// Famille

class Model_DbTable_Famille extends Zend_Db_Table_Abstract
{
    // Nom de la base
    protected $_name = 'famille';

    // Clé primaire
    protected $_primary = 'ID_FAMILLE';

    /**
     * @psalm-return array<mixed|int, mixed>
     */
    public function fetchAllPK(): array
    {
        $all = $this->fetchAll(null, 'LIBELLE_FAMILLE')->toArray();
        $result = [];
        foreach ($all as $row) {
            $result[$row['ID_FAMILLE']] = $row;
        }

        $aucuneFamille = $result[1];
        unset($result[1]);
        array_unshift($result, $aucuneFamille);

        return $result;
    }
}
