<?php

class Model_DbTable_EtablissementPj extends Zend_Db_Table_Abstract
{
    // Nom de la base
    protected $_name = 'etablissementpj';

    // Clé primaire
    protected $_primary = ['ID_ETABLISSEMENT', 'ID_PIECEJOINTE'];
}
