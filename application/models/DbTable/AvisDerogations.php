<?php

class Model_DbTable_AvisDerogations extends Zend_Db_Table_Abstract
{
    // Nom de la base
    protected $_name = 'avisderogations';

    // Clé primaire
    protected $_primary = 'ID_AVIS_DEROGATION';

    /**
     * Retourne l avis derogation associe a l id passe en param.
     *
     * @param mixed $idAvisDerogations
     */
    public function getByIdAvisDerogation($idAvisDerogations)
    {
        $select = $this->select()
            ->from('avisderogations')
            ->where('ID_AVIS_DEROGATION = ?', $idAvisDerogations)
        ;

        return $this->fetchRow($select);
    }
}
