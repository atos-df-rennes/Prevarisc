<?php

class Model_DbTable_CapsuleRubrique extends Zend_Db_Table_Abstract
{
    // Nom de la base
    protected $_name = 'capsulerubrique';

    // Clé primaire
    protected $_primary = 'ID_CAPSULERUBRIQUE';

    public function getCapsuleRubriqueByInternalName(string $name): array
    {
        $select = $this->select()
            ->from(['cr' => 'capsulerubrique'])
            ->where('cr.NOM_INTERNE = ?', $name)
        ;

        return $this->fetchRow($select)->toArray();
    }

    public function updateCapsuleRubriqueName(int $idCapsuleRubrique, string $newName): void
    {
        $capsuleRubrique = $this->find($idCapsuleRubrique)->current();

        if (null === $capsuleRubrique) {
            throw new Exception(sprintf("La capsule rubrique %s n'existe pas", $capsuleRubrique));
        }

        $capsuleRubrique->NOM = $newName;
        $capsuleRubrique->save();
    }
}
