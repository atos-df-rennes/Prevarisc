<?php

class Model_DbTable_Avis extends Zend_Db_Table_Abstract
{
    // Nom de la base
    protected $_name = 'avis';

    // Clé primaire
    protected $_primary = 'ID_AVIS';

    // Fonction qui récupère tous les avis existant pour créer un select par exemple

    /**
     * @param mixed $tousLesChamps
     *
     * @return array
     */
    public function getAvis($tousLesChamps = 1)
    {
        if (1 == $tousLesChamps) {
            $select = 'SELECT *
                FROM avis
            ;';
        } else {
            $select = 'SELECT *
                FROM avis
                WHERE VISIBLE_DOSSIER = 0;';
        }

        return $this->getAdapter()->fetchAll($select);
    }

    /**
     * @param int|string $idAvis
     * @param mixed      $tousLesChamps
     */
    public function getAvisLibelle($idAvis, $tousLesChamps = 1)
    {
        if (1 == $tousLesChamps) {
            $select = "SELECT *
                FROM avis
                WHERE ID_AVIS = '".$idAvis."'
            ;";
        } else {
            $select = "SELECT *
                FROM avis
                WHERE ID_AVIS = '".$idAvis."'
                    AND VISIBLE_DOSSIER = 0;";
        }

        return $this->getAdapter()->fetchRow($select);
    }
}
