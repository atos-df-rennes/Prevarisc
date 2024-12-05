<?php

class Model_DbTable_DossierValeur extends Zend_Db_Table_Abstract
{
    protected $_name = 'dossiervaleur';

    protected $_primary = ['ID_DOSSIER', 'ID_VALEUR']; // Clé primaire
}
