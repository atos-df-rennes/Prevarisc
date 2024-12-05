<?php

class Service_Categorie
{
    /**
     * Récupération de l'ensemble des catégories.
     */
    public function getAll(): array
    {
        $DB_categorie = new Model_DbTable_Categorie();

        return $DB_categorie->fetchAllPK();
    }
}
