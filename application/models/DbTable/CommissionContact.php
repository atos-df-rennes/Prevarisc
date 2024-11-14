<?php

class Model_DbTable_CommissionContact extends Zend_Db_Table_Abstract
{
    // Nom de la base
    protected $_name = 'commissioncontact';

    // Clé primaire
    protected $_primary = ['ID_COMMISSION', 'ID_UTILISATEURINFORMATIONS'];
}
