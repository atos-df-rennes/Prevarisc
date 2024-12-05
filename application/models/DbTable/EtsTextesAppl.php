<?php

class Model_DbTable_EtsTextesAppl extends Zend_Db_Table_Abstract
{
    protected $_name = 'etablissementtextapp';

    protected $_primary = ['ID_TEXTESAPPL', 'ID_ETABLISSEMENT'];

    /**
     * @param mixed $id_etablissement
     */
    public function recupTextes($id_etablissement): array
    {
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from('etablissementtextapp', [])
            ->join('textesappl', 'etablissementtextapp.ID_TEXTESAPPL = textesappl.ID_TEXTESAPPL', ['ID_TEXTESAPPL', 'LIBELLE_TEXTESAPPL'])
            ->join('typetextesappl', 'textesappl.ID_TYPETEXTEAPPL = typetextesappl.ID_TYPETEXTEAPPL')
            ->where('ID_ETABLISSEMENT = ?', $id_etablissement)
        ;

        return $this->fetchAll($select)->toArray();
    }
}
