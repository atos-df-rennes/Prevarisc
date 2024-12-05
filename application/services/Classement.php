<?php

class Service_Classement
{
    /**
     * Récupération de l'ensemble des classes.
     */
    public function getAll(): array
    {
        $DB_classement = new Model_DbTable_Classement();

        return $DB_classement->fetchAllPK();
    }
}
