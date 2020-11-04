<?php

class Model_DbTable_DossierContact extends Zend_Db_Table_Abstract
{
    protected $_name = 'dossiercontact'; // Nom de la base
    protected $_primary = array('ID_DOSSIER', 'ID_UTILISATEURINFORMATIONS'); // Clé primaire

    /**
     * @return array
     */
    public function recupInfoContact($idDossier, $idFct)
    {
        //Permet de récuperer les informations concernant le directeur unique de sécurité
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(array('dc' => 'dossiercontact'))
            ->join(array('ui' => 'utilisateurinformations'), 'dc.ID_UTILISATEURINFORMATIONS = ui.ID_UTILISATEURINFORMATIONS')
            ->where('dc.ID_DOSSIER = ?', $idDossier)
            ->where('ui.ID_FONCTION = ?', $idFct)
            ->limit(1);

        return $this->getAdapter()->fetchAll($select);
    }

    /**
     * @return array
     */
    public function recupContactEtablissement($idEtablissement, $idFct = null)
    {
        //Permet de récuperer les informations concernant le directeur unique de sécurité
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(array('ec' => 'etablissementcontact'))
            ->join(array('ui' => 'utilisateurinformations'), 'ec.ID_UTILISATEURINFORMATIONS = ui.ID_UTILISATEURINFORMATIONS')
            ->where('ec.ID_ETABLISSEMENT = ?', $idEtablissement);

        if ($idFct) {
            $select->where('ui.ID_FONCTION = ?', $idFct);
        }

        return $this->getAdapter()->fetchAll($select);
    }
}
