<?php

class Model_DbTable_GroupementPreventionniste extends Zend_Db_Table_Abstract
{
    // Nom de la base
    protected $_name = 'groupementpreventionniste';

    // Clé primaire
    protected $_primary = ['ID_GROUPEMENT', 'DATEDEBUT_GROUPEMENTPREVENTIONNISTE', 'ID_UTILISATEUR'];

    protected $_referenceMap = [
        'groupement' => [
            'columns' => 'ID_GROUPEMENT',
            'refTableClass' => 'Model_DbTable_Groupement',
            'refColumns' => 'ID_GROUPEMENT',
            'onDelete' => self::CASCADE,
        ],
        'utilisateurinformations' => [
            'columns' => 'ID_UTILISATEUR',
            'refTableClass' => 'Model_DbTable_UtilisateurInformations',
            'refColumns' => 'ID_UTILISATEUR',
        ],
    ];
}
