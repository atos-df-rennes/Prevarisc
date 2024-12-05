<?php

class Service_Famille
{
    /**
     * Récupération de l'ensemble des familles.
     */
    public function getAll(): array
    {
        $DB_famille = new Model_DbTable_Famille();

        return $DB_famille->fetchAllPK();
    }
}
