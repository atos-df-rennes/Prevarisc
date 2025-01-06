<?php

class Model_DbTable_PrescriptionType extends Zend_Db_Table_Abstract
{
    // Nom de la base
    protected $_name = 'prescriptiontype';

    // Clé primaire
    protected $_primary = 'ID_PRESCRIPTIONTYPE';

    /**
     * @param mixed $categorie
     * @param mixed $texte
     * @param mixed $article
     *
     * @return array
     */
    public function getPrescriptionType($categorie, $texte, $article)
    {
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(['pt' => 'prescriptiontype'])
            ->where('pt.PRESCRIPTIONTYPE_CATEGORIE = ?', $categorie)
            ->where('pt.PRESCRIPTIONTYPE_TEXTE = ?', $texte)
            ->where('pt.PRESCRIPTIONTYPE_ARTICLE = ?', $article)
            ->order('pt.PRESCRIPTIONTYPE_NUM')
        ;

        return $this->getAdapter()->fetchAll($select);
    }

    /**
     * @param mixed $tabMotCles
     *
     * @return array
     */
    public function getPrescriptionTypeByWords($tabMotCles)
    {
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(['pt' => 'prescriptiontype'])
            ->join(['pta' => 'prescriptiontypeassoc'], 'pt.ID_PRESCRIPTIONTYPE = pta.ID_PRESCRIPTIONTYPE')
            ->join(['pal' => 'prescriptionarticleliste'], 'pal.ID_ARTICLE = pta.ID_ARTICLE')
            ->join(['ptl' => 'prescriptiontexteliste'], 'ptl.ID_TEXTE = pta.ID_TEXTE')
        ;

        foreach ($tabMotCles as $ue) {
            $select->orWhere('pt.PRESCRIPTIONTYPE_LIBELLE like ?', '%'.$ue.'%');
            $select->orWhere('ptl.LIBELLE_TEXTE like ?', '%'.$ue.'%');
            $select->orWhere('pal.LIBELLE_ARTICLE like ?', '%'.$ue.'%');
        }

        return $this->getAdapter()->fetchAll($select);
    }

    /**
     * @param int|string $idOldType
     * @param mixed      $idNewType
     */
    public function replaceId($idOldType, $idNewType): void
    {
        $where = [];
        $data = ['ID_PRESCRIPTION_TYPE' => $idNewType];
        $where[] = 'ID_PRESCRIPTION_TYPE = '.$idOldType;
        // MAJ des id des textes dans les tables : prescriptiondossierassoc, prescriptiontypeassoc
        $this->getAdapter()->update('prescriptiondossier', $data, $where);
    }
}
