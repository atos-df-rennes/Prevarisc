<?php

class Model_DbTable_Rubrique extends Zend_Db_Table_Abstract
{
    protected $_name = 'rubrique'; // Nom de la base
    protected $_primary = 'ID_RUBRIQUE'; // Clé primaire

    public function getRubriquesByCapsuleRubrique(string $capsuleRubrique, bool $adminView = false): array
    {
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(array('r' => 'rubrique'), array('ID_RUBRIQUE', 'NOM'))
            ->join(array('cr' => 'capsulerubrique'), 'r.ID_CAPSULERUBRIQUE = cr.ID_CAPSULERUBRIQUE', array())
            ->joinLeft(array('dre' => 'displayrubriqueetablissement'), 'r.ID_RUBRIQUE = dre.ID_RUBRIQUE', array())
            ->where('cr.NOM_INTERNE = ?', $capsuleRubrique)
            ->order('r.Id_RUBRIQUE');

        if ($adminView === true) {
            $select->columns(
                array('DEFAULT_DISPLAY' => 'r.DEFAULT_DISPLAY')
            );
        } else {
            $select->columns(
                array(
                    'DISPLAY' => new Zend_Db_Expr(
                        'CASE WHEN
                            dre.USER_DISPLAY IS NULL THEN r.DEFAULT_DISPLAY
                        ELSE
                            dre.USER_DISPLAY
                        END'
                    )
                )
            );
        }

        return $this->fetchAll($select)->toArray();
    }
}
