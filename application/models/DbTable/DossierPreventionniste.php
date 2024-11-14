<?php

class Model_DbTable_DossierPreventionniste extends Zend_Db_Table_Abstract
{
    // Nom de la base
    protected $_name = 'dossierpreventionniste';

    // Clé primaire
    protected $_primary = ['ID_DOSSIER', 'ID_PREVENTIONNISTE'];

    /**
     * @param int|string $idDossier
     *
     * @return array
     */
    public function getPrevDossier($idDossier)
    {
        $select = "SELECT *, ID_UTILISATEUR as uid
            FROM dossierpreventionniste, utilisateur, utilisateurinformations
            WHERE dossierpreventionniste.ID_PREVENTIONNISTE = utilisateur.ID_UTILISATEUR
            AND utilisateur.ID_UTILISATEURINFORMATIONS = utilisateurinformations.ID_UTILISATEURINFORMATIONS
            AND dossierpreventionniste.ID_DOSSIER = '".$idDossier."'
        ;";

        return $this->getAdapter()->fetchAll($select);
    }

    /**
     * @param int|string $idDossier
     */
    public function delPrevsDossier($idDossier)
    {
        $select = "DELETE FROM dossierpreventionniste WHERE ID_DOSSIER = '".$idDossier."';";

        return $this->getAdapter()->exec($select);
    }
}
