<?php

class Model_DbTable_NewsGroupe extends Zend_Db_Table_Abstract
{
    protected $_name = 'newsgroupe';

    protected $_primary = ['ID_NEWS', 'ID_GROUPE'];
}
