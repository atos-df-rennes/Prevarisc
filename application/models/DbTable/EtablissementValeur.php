<?php

class Model_DbTable_EtablissementValeur extends Zend_Db_Table_Abstract
{
    protected $_name = 'etablissementvaleur';

    protected $_primary = ['ID_ETABLISSEMENT', 'ID_VALEUR']; // Clé primaire
}
