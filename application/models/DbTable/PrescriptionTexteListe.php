<?php

class Model_DbTable_PrescriptionTexteListe extends Zend_Db_Table_Abstract
{
    // Nom de la base
    protected $_name = 'prescriptiontexteliste';

    // Clé primaire
    protected $_primary = 'ID_TEXTE';

    /**
     * @param null|mixed $visible
     *
     * @return array
     */
    public function getAllTextes($visible = null)
    {
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(['ptl' => 'prescriptiontexteliste'])
        ;

        if (null != $visible) {
            $select->where('VISIBLE_TEXTE = ?', $visible);
        }

        $select->order('ptl.LIBELLE_TEXTE');

        return $this->getAdapter()->fetchAll($select);
    }

    public function getTexte($idTexte)
    {
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->where('ID_TEXTE = ?', $idTexte)
        ;

        return $this->getAdapter()->fetchRow($select);
    }

    /**
     * @param int|string $idOldTexte
     * @param mixed      $idNewTexte
     */
    public function replace($idOldTexte, $idNewTexte): void
    {
        $where = [];
        $data = ['ID_TEXTE' => $idNewTexte];
        $where[] = 'ID_TEXTE = '.$idOldTexte;
        // MAJ des id des textes dans les tables : prescriptiondossierassoc, prescriptiontypeassoc
        $this->getAdapter()->update('prescriptiondossierassoc', $data, $where);
        $this->getAdapter()->update('prescriptiontypeassoc', $data, $where);
        $this->getAdapter()->update('prescriptionreglassoc', $data, $where);
        // Suppression du texte
        $this->delete('ID_TEXTE = '.$idOldTexte);
    }
}
