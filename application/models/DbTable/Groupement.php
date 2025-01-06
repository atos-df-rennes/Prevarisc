<?php

class Model_DbTable_Groupement extends Zend_Db_Table_Abstract
{
    // Nom de la base
    protected $_name = 'groupement';

    // Clé primaire
    protected $_primary = 'ID_GROUPEMENT';

    protected $_referenceMap = [
        'groupementtype' => [
            'columns' => 'ID_GROUPEMENT',
            'refTableClass' => 'Model_DbTable_GroupementType',
            'refColumns' => 'ID_GROUPEMENTTYPE',
        ],
        'groupementcommune' => [
            'columns' => 'ID_GROUPEMENT',
            'refTableClass' => 'Model_DbTable_GroupementCommune',
            'refColumns' => 'ID_GROUPEMENT',
            'onDelete' => self::CASCADE,
        ],
        'groupementpreventionniste' => [
            'columns' => 'ID_GROUPEMENT',
            'refTableClass' => 'Model_DbTable_GroupementPreventionniste',
            'refColumns' => 'ID_GROUPEMENT',
            'onDelete' => self::CASCADE,
        ],
    ];

    /**
     * @param float|int|string $id
     *
     * @return null|Zend_Db_Table_Row_Abstract
     */
    public function get($id)
    {
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from('groupement', 'LIBELLE_GROUPEMENT')
            ->joinInner('groupementtype', 'groupement.ID_GROUPEMENTTYPE = groupementtype.ID_GROUPEMENTTYPE', 'LIBELLE_GROUPEMENTTYPE')
            ->joinLeft('utilisateurinformations', 'utilisateurinformations.ID_UTILISATEURINFORMATIONS = groupement.ID_UTILISATEURINFORMATIONS')
            ->where(sprintf("groupement.ID_GROUPEMENT = '%s'", $id))
        ;

        return (null != $this->fetchRow($select)) ? $this->fetchRow($select) : null;
    }

    /**
     * @param mixed $libelle
     *
     * @return array
     */
    public function getByLibelle($libelle)
    {
        $expLibelle = $this->getAdapter()->quote($libelle);
        $select = 'SELECT groupement.*, groupementtype.LIBELLE_GROUPEMENTTYPE, utilisateurinformations.*
                    FROM groupement
                    INNER JOIN groupementtype ON groupement.ID_GROUPEMENTTYPE = groupementtype.ID_GROUPEMENTTYPE
                    LEFT JOIN utilisateurinformations ON utilisateurinformations.ID_UTILISATEURINFORMATIONS = groupement.ID_UTILISATEURINFORMATIONS
                    WHERE (groupement.LIBELLE_GROUPEMENT = '.$expLibelle.');';

        return $this->getAdapter()->fetchAll($select);
    }

    /**
     * @param mixed $libelle
     * @param mixed $libelleGroupementType
     *
     * @return array
     */
    public function getByLibelle2($libelle, $libelleGroupementType)
    {
        $expLibelle = $this->getAdapter()->quote($libelle);
        $expLibelleGroupementType = $this->getAdapter()->quote($libelleGroupementType);
        $select = 'SELECT groupement.*, groupementtype.LIBELLE_GROUPEMENTTYPE, utilisateurinformations.*
                    FROM groupement
                    INNER JOIN groupementtype ON groupement.ID_GROUPEMENTTYPE = groupementtype.ID_GROUPEMENTTYPE
                    LEFT JOIN utilisateurinformations ON utilisateurinformations.ID_UTILISATEURINFORMATIONS = groupement.ID_UTILISATEURINFORMATIONS
                    WHERE (groupement.LIBELLE_GROUPEMENT = '.$expLibelle.' AND groupementtype.LIBELLE_GROUPEMENTTYPE = '.$expLibelleGroupementType.');';

        return $this->getAdapter()->fetchAll($select);
    }

    /**
     * @param float|int|string $id
     */
    public function deleteGroupement($id): void
    {
        $this->getAdapter()->query(sprintf('DELETE FROM `groupementcommune` WHERE `groupementcommune`.`ID_GROUPEMENT` = %s;', $id));
        $this->getAdapter()->query(sprintf('DELETE FROM `groupementpreventionniste` WHERE `groupementpreventionniste`.`ID_GROUPEMENT` = %s;', $id));
        $this->getAdapter()->query(sprintf('DELETE FROM `groupement` WHERE `groupement`.`ID_GROUPEMENT` = %s;', $id));
    }

    /**
     * @param float|int|string $id
     *
     * @return array
     */
    public function getPreventionnistes($id)
    {
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from('groupementpreventionniste')
            ->join('utilisateur', 'utilisateur.ID_UTILISATEUR = groupementpreventionniste.ID_UTILISATEUR')
            ->join('utilisateurinformations', 'utilisateurinformations.ID_UTILISATEURINFORMATIONS = utilisateur.ID_UTILISATEURINFORMATIONS')
            ->where(sprintf("groupementpreventionniste.ID_GROUPEMENT = '%s'", $id))
            ->order('utilisateurinformations.NOM_UTILISATEURINFORMATIONS ASC')
        ;

        return $this->fetchAll($select)->toArray();
    }

    /**
     * @psalm-return array<mixed, array>
     *
     * @param mixed $groupements
     *
     * @return array[]
     */
    public function getPreventionnistesByGpt($groupements): array
    {
        $preventionnistes_par_gpt = [];

        foreach ($groupements as $groupement) {
            $select = $this->select()
                ->setIntegrityCheck(false)
                ->from('groupementpreventionniste', [])
                ->join('utilisateur', 'utilisateur.ID_UTILISATEUR = groupementpreventionniste.ID_UTILISATEUR')
                ->join('utilisateurinformations', 'utilisateurinformations.ID_UTILISATEURINFORMATIONS = utilisateur.ID_UTILISATEURINFORMATIONS')
                ->where('groupementpreventionniste.ID_GROUPEMENT = ?', $groupement['ID_GROUPEMENT'])
                ->order('utilisateurinformations.NOM_UTILISATEURINFORMATIONS ASC')
            ;

            $rowset = $this->fetchAll($select);

            if (null == $rowset) {
                continue;
            }

            $preventionnistes_par_gpt[$groupement['ID_GROUPEMENT']] = $rowset->toArray();
        }

        return $preventionnistes_par_gpt;
    }

    /**
     * @param float|int|string $code_insee
     *
     * @return array
     */
    public function getGroupementParVille($code_insee)
    {
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from('groupement')
            ->joinInner('groupementcommune', 'groupementcommune.ID_GROUPEMENT = groupement.ID_GROUPEMENT', [])
            ->joinInner('groupementtype', 'groupementtype.ID_GROUPEMENTTYPE = groupement.ID_GROUPEMENTTYPE', 'LIBELLE_GROUPEMENTTYPE')
            ->where(sprintf("groupementcommune.NUMINSEE_COMMUNE = '%s'", $code_insee))
            ->order('groupementtype.ID_GROUPEMENTTYPE ASC')
            ->order('LIBELLE_GROUPEMENT ASC')
        ;

        return $this->fetchAll($select)->toArray();
    }

    /**
     * @return array
     */
    public function getAllWithTypes()
    {
        $select = $this->select()
            ->distinct()
            ->setIntegrityCheck(false)
            ->from('groupement')
            ->joinLeft('groupementcommune', 'groupementcommune.ID_GROUPEMENT = groupement.ID_GROUPEMENT', [])
            ->joinLeft('groupementtype', 'groupementtype.ID_GROUPEMENTTYPE = groupement.ID_GROUPEMENTTYPE', 'LIBELLE_GROUPEMENTTYPE')
            ->order('groupementtype.ID_GROUPEMENTTYPE ASC')
            ->order('LIBELLE_GROUPEMENT ASC')
        ;

        return $this->fetchAll($select)->toArray();
    }

    /**
     * @return array
     */
    public function getByEtablissement(array $ids_etablissement = [])
    {
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from('etablissementadresse', ['etablissementadresse.ID_ETABLISSEMENT'])
            ->joinLeft('groupementcommune', 'etablissementadresse.NUMINSEE_COMMUNE = groupementcommune.NUMINSEE_COMMUNE', [
                'groupementcommune.ID_GROUPEMENT',
                'groupementcommune.NUMINSEE_COMMUNE',
            ])
            ->where('etablissementadresse.ID_ETABLISSEMENT IN(?)', $ids_etablissement)
            ->group(['etablissementadresse.ID_ETABLISSEMENT', 'groupementcommune.ID_GROUPEMENT'])
        ;

        return $this->fetchAll($select)->toArray();
    }

    public function getByGroupementType(array $types_groupement = [])
    {
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from('groupement')
            ->where('groupement.ID_GROUPEMENTTYPE IN(?)', $types_groupement)
        ;

        return $this->fetchAll($select)->toArray();
    }
}
