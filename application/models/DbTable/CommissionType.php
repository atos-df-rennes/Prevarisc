<?php

class Model_DbTable_CommissionType extends Zend_Db_Table_Abstract
{
    // Nom de la base
    protected $_name = 'commissiontype';

    // Clé primaire
    protected $_primary = 'ID_COMMISSIONTYPE';

    protected $_referenceMap = [
        'commission' => [
            'columns' => 'ID_COMMISSIONTYPE',
            'refTableClass' => 'Model_DbTable_Commission',
            'refColumns' => 'ID_COMMISSIONTYPE',
        ],
    ];
}
