<?php

class Service_Commission
{
    /**
     * Récupération de l'ensemble des commissions.
     */
    public function getAll(): array
    {
        $DB_commission = new Model_DbTable_Commission();

        return $DB_commission->fetchAllPK();
    }

    /**
     * @return (array|mixed)[][]
     *
     * @psalm-return array<mixed, array{LIBELLE:mixed, ARRAY:array}>
     */
    public function getCommissionsAndTypes(): array
    {
        // Modèle de données
        $model_typesDesCommissions = new Model_DbTable_CommissionType();
        $model_commission = new Model_DbTable_Commission();

        // On cherche tous les types de commissions
        $rowset_typesDesCommissions = $model_typesDesCommissions->fetchAll();

        // Tableau de résultats
        $array_commissions = [];

        // Pour tous les types, on cherche leur commission
        foreach ($rowset_typesDesCommissions as $row_typeDeCommission) {
            $array_commissions[$row_typeDeCommission->ID_COMMISSIONTYPE] = [
                'LIBELLE' => $row_typeDeCommission->LIBELLE_COMMISSIONTYPE,
                'ARRAY' => $model_commission->fetchAll('ID_COMMISSIONTYPE = '.$row_typeDeCommission->ID_COMMISSIONTYPE)->toArray(),
            ];
        }

        return $array_commissions;
    }
}
