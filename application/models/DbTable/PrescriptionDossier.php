<?php

class Model_DbTable_PrescriptionDossier extends Zend_Db_Table_Abstract
{
    // Nom de la base
    protected $_name = 'prescriptiondossier';

    // Clé primaire
    protected $_primary = 'ID_PRESCRIPTION_DOSSIER';

    public function recupMaxNumPrescDossier($idDossier, $type)
    {
        // retourne la liste des catégories de prescriptions par ordre
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(['pd' => 'prescriptiondossier'], 'max(pd.NUM_PRESCRIPTION_DOSSIER) as maxnum')
            ->where('ID_DOSSIER = ?', $idDossier)
            ->where('TYPE_PRESCRIPTION_DOSSIER = ?', $type)
        ;

        return $this->getAdapter()->fetchRow($select);
    }

    /**
     * @param mixed $idDossier
     * @param mixed $type
     *
     * @return array
     */
    public function recupPrescDossier($idDossier, $type)
    {
        // retourne la liste des catégories de prescriptions par ordre
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(['pd' => 'prescriptiondossier'])
            ->joinLeft(['d' => 'dossier'], 'pd.ID_DOSSIER_REPRISE = d.ID_DOSSIER', ['OBJET_DOSSIER', 'DATEINSERT_DOSSIER'])
            ->joinLeft(['dt' => 'dossiertype'], 'd.TYPE_DOSSIER = dt.ID_DOSSIERTYPE', 'LIBELLE_DOSSIERTYPE')
            ->joinLeft(['dn' => 'dossiernature'], 'd.ID_DOSSIER = dn.ID_DOSSIER', [])
            ->joinLeft(['dnl' => 'dossiernatureliste'], 'dn.ID_NATURE = dnl.ID_DOSSIERNATURE', 'LIBELLE_DOSSIERNATURE')
            ->where('pd.ID_DOSSIER = ?', $idDossier)
            ->where('pd.TYPE_PRESCRIPTION_DOSSIER = ?', $type)
            ->order('pd.NUM_PRESCRIPTION_DOSSIER')
        ;

        return $this->getAdapter()->fetchAll($select);
    }

    public function recupPrescInfos($id_prescription)
    {
        // retourne la liste des catégories de prescriptions par ordre
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(['pd' => 'prescriptiondossier'])
            ->where('pd.ID_PRESCRIPTION_DOSSIER = ?', $id_prescription)
        ;

        return $this->getAdapter()->fetchRow($select);
    }
}
