<?php

class Service_TypePlan
{
    /**
     * Récupération de l'ensemble des types de plan.
     */
    public function getAll(): array
    {
        $DB_typesplan = new Model_DbTable_TypePlan();

        return $DB_typesplan->fetchAllPK();
    }
}
