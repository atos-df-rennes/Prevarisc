<?php

class Model_DbTable_DossierDocConsulte extends Zend_Db_Table_Abstract
{
    // Nom de la base
    protected $_name = 'dossierdocconsulte';

    // Clé primaire
    protected $_primary = ['ID_DOSSIERDOCCONSULTE'];

    public function getGeneral($idDossier, $idDoc)
    {
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(['ddc' => 'dossierdocconsulte'])
            ->where('ddc.ID_DOSSIER = ?', $idDossier)
            ->where('ddc.ID_DOC = ?', $idDoc)
        ;

        return $this->getAdapter()->fetchRow($select);
    }

    /**
     * @param mixed $idDossier
     *
     * @return array
     */
    public function getDocRenseigne($idDossier)
    {
        // retourne la liste des catégories de prescriptions par ordre
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(['ddc' => 'dossierdocconsulte'])
            ->join(['ldc' => 'listedocconsulte'], 'ddc.ID_DOC = ldc.ID_DOC')
            ->where('ddc.ID_DOSSIER = ?', $idDossier)
        ;

        return $this->getAdapter()->fetchAll($select);
    }

    /**
     * @param mixed $idDossier
     * @param mixed $idNature
     *
     * @return array
     */
    public function getDocOtheNature($idDossier, $idNature)
    {
        $arrayVR = [20, 25];
        $arrayVAO = [47, 48];

        $column1 = null;
        $column2 = null;
        if (in_array($idNature, $arrayVR)) {
            $column1 = 'VISITERT_DOC';
            $column2 = 'VISITEVAO_DOC';
        } elseif (in_array($idNature, $arrayVAO)) {
            $column1 = 'VISITEVAO_DOC';
            $column2 = 'VISITERT_DOC';
        }

        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(['ddc' => 'dossierdocconsulte'])
            ->join(['ldc' => 'listedocconsulte'], 'ddc.ID_DOC = ldc.ID_DOC')
            ->where('ddc.ID_DOSSIER = ?', $idDossier)
            ->where('ldc.'.$column1.' = 1')
            ->where('ldc.'.$column2.' = 0')
        ;

        return $this->getAdapter()->fetchAll($select);
    }
}
