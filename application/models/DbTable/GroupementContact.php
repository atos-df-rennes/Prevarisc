<?php

class Model_DbTable_GroupementContact extends Zend_Db_Table_Abstract
{
    // Nom de la base
    protected $_name = 'groupementcontact';

    // Clé primaire
    protected $_primary = ['ID_GROUPEMENT', 'ID_UTILISATEURINFORMATIONS'];
}
