<?php

class Model_DbTable_Valeur extends Zend_Db_Table_Abstract
{
    // Nom de la base
    protected $_name = 'valeur';

    // Clé primaire
    protected $_primary = 'ID_VALEUR';

    /**
     * Retourne l'unique valeur d'un champ n'étant pas un enfant d'un champ `tableau`
     *  OU
     * Retourne la valeur à l'index spécifié d'un champ étant un enfant d'un champ `tableau`.
     */
    public function getByChampAndObject(int $idChamp, int $idObject, string $classObject, ?int $idx = null): ?Zend_Db_Table_Row_Abstract
    {
        $select = $this->getSelect($idChamp, $idObject, $classObject);
        null === $idx ? $select->where('v.idx IS NULL') : $select->where('v.idx = ?', $idx);

        return $this->fetchRow($select);
    }

    /**
     * Retourne toutes les valeurs d'un champ étant un enfant d'un champ `tableau`.
     */
    public function getAllByChampAndObject(int $idChamp, int $idObject, string $classObject): Zend_Db_Table_Rowset_Abstract
    {
        $select = $this->getSelect($idChamp, $idObject, $classObject);

        return $this->fetchAll($select);
    }

    private function getAllOfParent(int $idObject, string $classObject)
    {
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(['v' => 'valeur'])
            ->join(['c' => 'champ'], 'v.ID_CHAMP = c.ID_CHAMP', ['c.ID_PARENT', 'c.ID_TYPECHAMP', 'c.ID_CHAMP', 'c.NOM'])
            ->order('v.idx')
        ;

        if (false !== strpos($classObject, 'Dossier')) {
            $select->join(['dv' => 'dossiervaleur'], 'dv.ID_VALEUR = v.ID_VALEUR', ['v.ID_VALEUR'])
                ->where('dv.ID_DOSSIER = ?', $idObject)
            ;
        }

        if (false !== strpos($classObject, 'Etablissement')) {
            $select->join(['ev' => 'etablissementvaleur'], 'ev.ID_VALEUR = v.ID_VALEUR', ['v.ID_VALEUR'])
                ->where('ev.ID_ETABLISSEMENT = ?', $idObject)
            ;
        }

        return $select;
    }

    private function getSelect(int $idChamp, int $idObject, string $classObject)
    {
        return $this->getAllOfParent($idObject, $classObject)->where('c.ID_CHAMP = ?', $idChamp);
    }
}
