<?php

class Model_DbTable_PieceJointe extends Zend_Db_Table_Abstract
{
    protected $_name = 'piecejointe'; // Nom de la base
    protected $_primary = 'ID_PIECEJOINTE'; // Clé primaire

    /**
     * @param array|float|int|string|Zend_Db_Expr $table
     * @param int|string                          $champ
     * @param int|string                          $identifiant
     *
     * @return null|array
     */
    public function affichagePieceJointe($table, $champ, $identifiant)
    {
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from('piecejointe')
            ->join($table, "piecejointe.ID_PIECEJOINTE = {$table}.ID_PIECEJOINTE")
            ->where($champ.' = '.$identifiant)
            ->order('piecejointe.ID_PIECEJOINTE DESC')
        ;

        return (null != $this->fetchAll($select)) ? $this->fetchAll($select)->toArray() : null;
    }

    public function affichagePieceJointePLATAU(int $idDossier)
    {
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from('piecejointe')
            ->join('dossierpj', "piecejointe.ID_PIECEJOINTE = dossierpj.ID_PIECEJOINTE", [])
            ->join('dossier', "dossierpj.ID_DOSSIER = dossier.ID_DOSSIER", ['ID_DOSSIER', 'ID_PLATAU'])
            ->where('dossier.ID_DOSSIER = ?', $idDossier)
            ->order('piecejointe.ID_PIECEJOINTE DESC')
        ;

        return $this->fetchAll($select)->toArray();
    }

    public function maxPieceJointe()
    {
        $select = 'SELECT MAX(ID_PIECEJOINTE)
        FROM piecejointe
        ;';

        return $this->getAdapter()->fetchRow($select);
    }
}
