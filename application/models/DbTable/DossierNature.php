<?php

class Model_DbTable_DossierNature extends Zend_Db_Table_Abstract
{
    // Nom de la base
    protected $_name = 'dossiernature';

    // Clé primaire
    protected $_primary = 'ID_DOSSIERNATURE';

    /**
     * @param int|string $idDossierType
     *
     * @return array
     */
    public function getDossierNaturesLibelle($idDossierType)
    {
        $select = "SELECT dossiernatureliste.LIBELLE_DOSSIERNATURE as LIBELLE_DOSSIERNATURE, dossiernature.ID_DOSSIERNATURE as ID_DOSSIERNATURE, dossiernature.ID_NATURE as ID_NATURE
            FROM dossiernature, dossiernatureliste
            WHERE dossiernature.ID_NATURE = dossiernatureliste.ID_DOSSIERNATURE
            AND dossiernature.ID_DOSSIER = '".$idDossierType."'
        ;";

        return $this->getAdapter()->fetchAll($select);
    }

    /**
     * @param int|string $idDossier
     */
    public function getDossierNaturesId($idDossier)
    {
        $select = "SELECT ID_DOSSIERNATURE,ID_NATURE
            FROM dossiernature
            WHERE ID_DOSSIER = '".$idDossier."'
        ;";

        return $this->getAdapter()->fetchRow($select);
    }

    /**
     * @param int|string $idDossier
     */
    public function getDossierNatureLibelle($idDossier)
    {
        $select = "SELECT dossiernatureliste.LIBELLE_DOSSIERNATURE
            FROM dossiernature, dossiernatureliste
			WHERE dossiernature.ID_NATURE = dossiernatureliste.ID_DOSSIERNATURE
            AND dossiernature.ID_DOSSIER = '".$idDossier."'
        ;";

        return $this->getAdapter()->fetchRow($select);
    }
}
