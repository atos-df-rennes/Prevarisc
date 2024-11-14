<?php

class Model_DbTable_EtablissementContact extends Zend_Db_Table_Abstract
{
    // Nom de la base
    protected $_name = 'etablissementcontact';

    // Clé primaire
    protected $_primary = ['ID_ETABLISSEMENT', 'ID_UTILISATEURINFORMATIONS'];
}
