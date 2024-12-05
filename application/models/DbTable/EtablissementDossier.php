<?php

class Model_DbTable_EtablissementDossier extends Zend_Db_Table_Abstract
{
    protected $_name = 'etablissementdossier';

    protected $_primary = 'ID_ETABLISSEMENTDOSSIER';

    /**
     * @param mixed $idDossier
     *
     * @return array
     */
    public function getEtablissementListe($idDossier)
    {
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(['ed' => 'etablissementdossier'])
            ->joinLeftUsing(['e' => 'etablissement'], 'ID_ETABLISSEMENT')
            ->where('ID_DOSSIER = ?', $idDossier)
            ->where('e.DATESUPPRESSION_ETABLISSEMENT IS NULL')
        ;

        return $this->getAdapter()->fetchAll($select);
    }
}
