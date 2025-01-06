<?php

class Model_DbTable_DossierContact extends Zend_Db_Table_Abstract
{
    // Nom de la base
    protected $_name = 'dossiercontact';

    // Clé primaire
    protected $_primary = ['ID_DOSSIER', 'ID_UTILISATEURINFORMATIONS'];

    /**
     * @param mixed $idDossier
     * @param mixed $idFct
     *
     * @return array
     */
    public function recupInfoContact($idDossier, $idFct)
    {
        // Permet de récuperer les informations concernant le directeur unique de sécurité
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(['dc' => 'dossiercontact'])
            ->join(['ui' => 'utilisateurinformations'], 'dc.ID_UTILISATEURINFORMATIONS = ui.ID_UTILISATEURINFORMATIONS')
            ->where('dc.ID_DOSSIER = ?', $idDossier)
            ->where('ui.ID_FONCTION = ?', $idFct)
            ->limit(1)
        ;

        return $this->getAdapter()->fetchAll($select);
    }

    /**
     * @param mixed      $idEtablissement
     * @param null|mixed $idFct
     *
     * @return array
     */
    public function recupContactEtablissement($idEtablissement, $idFct = null)
    {
        // Permet de récuperer les informations concernant le directeur unique de sécurité
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(['ec' => 'etablissementcontact'])
            ->join(['ui' => 'utilisateurinformations'], 'ec.ID_UTILISATEURINFORMATIONS = ui.ID_UTILISATEURINFORMATIONS')
            ->where('ec.ID_ETABLISSEMENT = ?', $idEtablissement)
        ;

        if ($idFct) {
            $select->where('ui.ID_FONCTION = ?', $idFct);
        }

        return $this->getAdapter()->fetchAll($select);
    }
}
