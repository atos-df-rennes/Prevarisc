<?php

class Model_DbTable_PieceJointe extends Zend_Db_Table_Abstract
{
    protected $_name = 'piecejointe'; // Nom de la base
    protected $_primary = 'ID_PIECEJOINTE'; // Clé primaire

    /**
     * @param array|string|int|float|Zend_Db_Expr $table
     * @param string|int                          $champ
     * @param string|int                          $identifiant
     *
     * @return array|null
     */
    public function affichagePieceJointe($table, $champ, $identifiant)
    {
        // Création de l'objet recherche
        $select = new Zend_Db_Select(Zend_Controller_Front::getInstance()->getParam('bootstrap')->getResource('db'));
        
        // Requête principale
        $select = $this->select()
            // ->from(array('p' => 'piecejointe'))
            ->from('piecejointe')
            ->columns(array(
                'NB_ENFANTS' => new Zend_Db_Expr('( SELECT COUNT(piecejointelie.ID_FILS_PIECEJOINTE)
                    FROM piecejointe p
                    INNER JOIN piecejointelie ON p.ID_PIECEJOINTE = piecejointelie.ID_PIECEJOINTE
                    WHERE p.ID_PIECEJOINTE = piecejointe.ID_PIECEJOINTE)') ))
            ->setIntegrityCheck(false)
            ->where($champ.' = '.$identifiant)
            //->where('piecejointe.SIGNE_PIECEJOINTE IS NULL')
            //->where('piecejointe.SIGNE_PIECEJOINTE = 1')

            ->order('piecejointe.ID_PIECEJOINTE DESC');
        if ($table != null) {
            $select->join($table, "piecejointe.ID_PIECEJOINTE = $table.ID_PIECEJOINTE");
        };

        return ($this->fetchAll($select) != null) ? $this->fetchAll($select)->toArray() : null;
    }

    public function maxPieceJointe()
    {
        $select = 'SELECT MAX(ID_PIECEJOINTE)
        FROM piecejointe
        ;';

        return $this->getAdapter()->fetchRow($select);
    }
}
