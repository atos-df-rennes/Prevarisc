<?php

class Model_DbTable_DossierDocUrba extends Zend_Db_Table_Abstract
{
    // Nom de la base
    protected $_name = 'dossierdocurba';

    // Clé primaire
    protected $_primary = 'ID_DOCURBA';

    // prend en parametre un type et retourne toutes les natures associées à ce dossier

    /**
     * @param int|string $idDossier
     *
     * @return array
     */
    public function getDossierDocUrba($idDossier)
    {
        $select = "SELECT *
            FROM dossierdocurba
            WHERE ID_DOSSIER = '".$idDossier."'
        ;";

        return $this->getAdapter()->fetchAll($select);
    }
}
