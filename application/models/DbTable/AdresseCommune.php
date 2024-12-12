<?php

class Model_DbTable_AdresseCommune extends Zend_Db_Table_Abstract
{
    // Nom de la base
    protected $_name = 'adressecommune';

    // Clé primaire
    protected $_primary = 'NUMINSEE_COMMUNE';

    /**
     * @param int|string $q
     *
     * @return array
     */
    public function get($q)
    {
        $select = $this->select()->setIntegrityCheck(false);

        $select->from('adressecommune')
            ->where('LIBELLE_COMMUNE LIKE ?', '%'.$q.'%')
            ->order('LENGTH(LIBELLE_COMMUNE)')
        ;

        return $this->fetchAll($select)->toArray();
    }

    /**
     * @param int|string $numinsee
     */
    public function getMairieInformation($numinsee)
    {
        $select = 'SELECT * FROM adressecommune as commune INNER JOIN utilisateurinformations as user '
                .'ON commune.ID_UTILISATEURINFORMATIONS = '
                .'user.ID_UTILISATEURINFORMATIONS '
                ."WHERE commune.NUMINSEE_COMMUNE = '".$numinsee."'";

        $result = $this->getAdapter()->fetchAll($select);
        if ([] !== $result) {
            return $result[0];
        }

        return null;
    }

    public function getLibelleCommune($code_insee)
    {
        $select = $this->select();

        $select->from('adressecommune')
            ->where('NUMINSEE_COMMUNE = ?', $code_insee)
        ;

        return $this->getAdapter()->fetchRow($select);
    }
}
