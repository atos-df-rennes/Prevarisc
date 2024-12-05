<?php

class Model_DbTable_Rubrique extends Zend_Db_Table_Abstract
{
    // Nom de la base
    protected $_name = 'rubrique';

    // Clé primaire
    protected $_primary = 'ID_RUBRIQUE';

    public function getRubriquesByCapsuleRubrique(string $capsuleRubrique): array
    {
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(['r' => 'rubrique'], ['ID_RUBRIQUE', 'NOM', 'idx'])
            ->columns(
                ['DISPLAY' => 'r.DEFAULT_DISPLAY']
            )
            ->join(['cr' => 'capsulerubrique'], 'r.ID_CAPSULERUBRIQUE = cr.ID_CAPSULERUBRIQUE', [])
            ->where('cr.NOM_INTERNE = ?', $capsuleRubrique)
            ->order('ISNULL(r.idx)')
            ->order('r.idx')
            ->order('r.NOM')
        ;

        return $this->fetchAll($select)->toArray();
    }

    // postParam => ['idx' = nouvelle idx champ, 'ID_CHAMP' => ID du champ]
    public function updateNewIdx(array $postParam): void
    {
        $rubrique = $this->find($postParam['ID'])->current();
        $rubrique->idx = $postParam['idx'];
        $rubrique->save();
    }

    public function getNbRubriqueOfDesc(string $nomCaps): int
    {
        $select = $this->select();

        $select
            ->setIntegrityCheck(false)
            ->from(['r' => 'rubrique'], ['r.ID_RUBRIQUE', 'r.ID_CAPSULERUBRIQUE', 'r.NOM'])
            ->join(['cr' => 'capsulerubrique'], 'r.ID_CAPSULERUBRIQUE = cr.ID_CAPSULERUBRIQUE')
            ->where('cr.NOM_INTERNE = ?', $nomCaps)
        ;

        return count($this->fetchAll($select)->toArray());
    }
}
