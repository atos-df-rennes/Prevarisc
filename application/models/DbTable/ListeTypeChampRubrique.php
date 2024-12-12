<?php

class Model_DbTable_ListeTypeChampRubrique extends Zend_Db_Table_Abstract
{
    // Nom de la base
    protected $_name = 'listetypechamprubrique';

    // Clé primaire
    protected $_primary = 'ID_TYPECHAMP';

    public function getIdTypeChampByName(string $name): array
    {
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from('listetypechamprubrique', ['ID_TYPECHAMP'])
            ->where('TYPE = ?', $name)
        ;

        return $this->fetchRow($select)->toArray();
    }

    public function getTypeWithoutParent(): array
    {
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(['t' => 'listetypechamprubrique'], ['value' => 't.ID_TYPECHAMP', 'id' => 't.TYPE'])
            ->where("t.TYPE != 'Parent'")
        ;

        return $this->fetchAll($select)->toArray();
    }

    public function getTypeWithoutParentToSelectForm(): array
    {
        $results = $this->getTypeWithoutParent();

        $types = [];
        foreach ($results as $type) {
            $types[$type['value']] = $type['id'];
        }

        return $types;
    }
}
