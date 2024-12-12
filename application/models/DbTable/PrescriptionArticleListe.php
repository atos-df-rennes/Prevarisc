<?php

class Model_DbTable_PrescriptionArticleListe extends Zend_Db_Table_Abstract
{
    // Nom de la base
    protected $_name = 'prescriptionarticleliste';

    // Clé primaire
    protected $_primary = 'ID_ARTICLE';

    /**
     * @param null|mixed $visible
     *
     * @return array
     */
    public function getAllArticles($visible = null)
    {
        // retourne la liste des catégories de prescriptions par ordre
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(['ptl' => 'prescriptionarticleliste'])
        ;

        if (null != $visible) {
            $select->where('ptl.VISIBLE_ARTICLE = ?', $visible);
        }

        $select->order('ptl.LIBELLE_ARTICLE');

        return $this->getAdapter()->fetchAll($select);
    }

    public function getArticle($idArticle)
    {
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->where('ID_ARTICLE = ?', $idArticle)
        ;

        return $this->getAdapter()->fetchRow($select);
    }

    /**
     * @param int|string $idOldArticle
     * @param mixed      $idNewArticle
     */
    public function replace($idOldArticle, $idNewArticle): void
    {
        $where = [];
        $data = ['ID_ARTICLE' => $idNewArticle];
        $where[] = 'ID_ARTICLE = '.$idOldArticle;
        // MAJ des id des textes dans les tables : prescriptiondossierassoc, prescriptiontypeassoc
        $this->getAdapter()->update('prescriptiondossierassoc', $data, $where);
        $this->getAdapter()->update('prescriptiontypeassoc', $data, $where);
        $this->getAdapter()->update('prescriptionreglassoc', $data, $where);
        // Suppression du texte
        $this->delete('ID_ARTICLE = '.$idOldArticle);
    }
}
