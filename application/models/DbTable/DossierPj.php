<?php

class Model_DbTable_DossierPj extends Zend_Db_Table_Abstract
{
    // Nom de la base
    protected $_name = 'dossierpj';

    // Clé primaire
    protected $_primary = ['ID_DOSSIER', 'ID_PIECEJOINTE'];

    /**
     * @param int|string $idDossier
     * @param int|string $idPj
     */
    public function getdossierpj($idDossier, $idPj)
    {
        $select = "SELECT *
			FROM dossierpj
			WHERE ID_DOSSIER = '".$idDossier."'
			AND ID_PIECEJOINTE = '".$idPj."'
        ;";

        return $this->getAdapter()->fetchRow($select);
    }

    /**
     * @param int|string $idDossier
     */
    public function countcommissionpj($idDossier)
    {
        $select = "SELECT count(*) as nbcommpj
			FROM dossierpj
			WHERE ID_DOSSIER = '".$idDossier."'
			AND PJ_COMMISSION = '1'
        ;";

        return $this->getAdapter()->fetchRow($select);
    }
}
