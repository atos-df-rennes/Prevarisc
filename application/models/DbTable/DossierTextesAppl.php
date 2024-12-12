<?php

class Model_DbTable_DossierTextesAppl extends Zend_Db_Table_Abstract
{
    // Nom de la base
    protected $_name = 'dossiertextesappl';

    // Clé primaire
    protected $_primary = ['ID_TEXTESAPPL', 'ID_DOSSIER'];

    /**
     * @param mixed $idDossier
     *
     * @return array
     */
    public function recupTextesDossier($idDossier)
    {
        $select = $this->select()
            ->from('dossiertextesappl', 'ID_TEXTESAPPL')
            ->where('ID_DOSSIER = ?', $idDossier)
        ;

        return $this->getAdapter()->fetchAll($select);
    }

    /**
     * @param mixed $idDossier
     *
     * @return array
     */
    public function recupTextesDossierGenDoc($idDossier)
    {
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(['dta' => 'dossiertextesappl'])
            ->join(['ta' => 'textesappl'], 'dta.ID_TEXTESAPPL = ta.ID_TEXTESAPPL')
            ->join(['tta' => 'typetextesappl'], 'tta.ID_TYPETEXTEAPPL = ta.ID_TYPETEXTEAPPL')
            ->where('dta.ID_DOSSIER = ?', $idDossier)
            ->order('ta.ID_TYPETEXTEAPPL')
        ;

        return $this->getAdapter()->fetchAll($select);
    }

    /**
     * @param mixed $idDossier
     */
    public function recupTextes($idDossier): array
    {
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(['dta' => 'dossiertextesappl'])
            ->join(['ta' => 'textesappl'], 'dta.ID_TEXTESAPPL = ta.ID_TEXTESAPPL')
            ->join(['tta' => 'typetextesappl'], 'tta.ID_TYPETEXTEAPPL = ta.ID_TYPETEXTEAPPL')
            ->where('dta.ID_DOSSIER = ?', $idDossier)
        ;

        return $this->fetchAll($select)->toArray();
    }
}
