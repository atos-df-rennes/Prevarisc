<?php

class Model_DbTable_GroupementCommune extends Zend_Db_Table_Abstract
{
    // Nom de la base
    protected $_name = 'groupementcommune';

    // Clé primaire
    protected $_primary = 'ID_GROUPEMENT';

    protected $_referenceMap = [
        'groupement' => [
            'columns' => 'ID_GROUPEMENT',
            'refTableClass' => 'Model_DbTable_Groupement',
            'refColumns' => 'ID_GROUPEMENT',
            'onDelete' => self::CASCADE,
        ],
        'utilisateur' => [
            'columns' => 'NUMINSEE_COMMUNE',
            'refTableClass' => 'Model_DbTable_AdresseCommune',
            'refColumns' => 'NUMINSEE_COMMUNE',
        ],
    ];
}
