<?php

    /*
        Genre

        Cette classe sert pour r�cup�rer les genre, et les administrer

    */

    class Model_DbTable_Genre extends Zend_Db_Table_Abstract
    {
        protected $_name = 'genre'; // Nom de la base
        protected $_primary = 'ID_GENRE'; // Cl� primaire

        // Donne la liste des genres
        /**
         * @param string|int|float $id
         *
         * @return array
         */
        public function getGenre($id = null)
        {
            $select = $this->select()
                ->setIntegrityCheck(false)
                ->from('genre');

            if ($id != null) {
                $select->where("ID_GENRE = $id");

                return $this->fetchRow($select)->toArray();
            } else {
                return $this->fetchAll($select)->toArray();
            }
        }

        /**
         * @return Zend_Db_Table_Rowset_Abstract
         */
        public function fetchAllSaufSite()
        {
            $select = $this->select()
                ->setIntegrityCheck(false)
                ->from('genre')
                ->where('ID_GENRE != 1');

            return $this->fetchAll($select);
        }
    }
