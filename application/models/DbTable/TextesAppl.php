<?php

class Model_DbTable_TextesAppl extends Zend_Db_Table_Abstract
{
    // Nom de la base
    protected $_name = 'textesappl';

    // Clé primaire
    protected $_primary = 'ID_TEXTESAPPL';

    // récupération des textes applicables et de leurs type associé

    /**
     * @return array
     */
    public function recupTextesAppl()
    {
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(['ta' => 'textesappl'])
            ->join(['ty' => 'typetextesappl'], 'ta.ID_TYPETEXTEAPPL = ty.ID_TYPETEXTEAPPL')
            ->order('ta.ID_TYPETEXTEAPPL')
            ->order('ta.NUM_TEXTESAPPL')
        ;

        return $this->getAdapter()->fetchAll($select);
    }

    /**
     * @return array
     */
    public function recupTextesApplVisible()
    {
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(['ta' => 'textesappl'])
            ->join(['ty' => 'typetextesappl'], 'ta.ID_TYPETEXTEAPPL = ty.ID_TYPETEXTEAPPL')
            ->where('VISIBLE_TEXTESAPPL = 1')
            ->order('ta.ID_TYPETEXTEAPPL')
            ->order('ta.NUM_TEXTESAPPL')
        ;

        return $this->getAdapter()->fetchAll($select);
    }
}
