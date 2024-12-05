<?php

class Model_DbTable_DossierListeDoc extends Zend_Db_Table_Abstract
{
    // Nom de la base
    protected $_name = 'listedocconsulte';

    // Clé primaire
    protected $_primary = 'ID_DOC';

    // Fonction qui récupère tous les doc de viste

    /**
     * @return array
     */
    public function getDocVisite()
    {
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(['ldc' => 'listedocconsulte'])
            ->where('ldc.VISITE_DOC = 1')
            ->order('ldc.ORDRE_DOC')
        ;

        return $this->getAdapter()->fetchAll($select);
    }

    // Fonction qui récupère tous les doc d'etude

    /**
     * @return array
     */
    public function getDocEtude()
    {
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(['ldc' => 'listedocconsulte'])
            ->where('ldc.ETUDE_DOC = 1')
            ->order('ldc.ORDRE_DOC')
        ;

        return $this->getAdapter()->fetchAll($select);
    }

    /**
     * @return array
     */
    public function getDocVisiteRT()
    {
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(['ldc' => 'listedocconsulte'])
            ->where('ldc.VISITERT_DOC = 1')
            ->order('ldc.ORDRE_DOC')
        ;

        return $this->getAdapter()->fetchAll($select);
    }

    /**
     * @return array
     */
    public function getDocVisiteVAO()
    {
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(['ldc' => 'listedocconsulte'])
            ->where('ldc.VISITEVAO_DOC = 1')
            ->order('ldc.ORDRE_DOC')
        ;

        return $this->getAdapter()->fetchAll($select);
    }

    // récupere les dossier qui ont été selection pour le dossier

    /**
     * @param mixed $id_dossier
     *
     * @return array
     */
    public function recupDocDossier($id_dossier)
    {
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(['ddc' => 'dossierdocconsulte'])
            ->where('ddc.ID_DOSSIER = ?', $id_dossier)
        ;

        return $this->getAdapter()->fetchAll($select);
    }

    // récupération des docconsulte après un changement de nature

    /**
     * @param int|string $id_nature
     * @param mixed      $id_dossier
     *
     * @return array
     */
    public function recupDocDifNature($id_dossier, $id_nature)
    {
        echo $id_nature.' ';

        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(['ddc' => 'dossierdocconsulte'])
            ->where('ddc.ID_DOSSIER = ?', $id_dossier)
            ->where('ddc.ID_NATURE')
        ;

        return $this->getAdapter()->fetchAll($select);
    }
}
