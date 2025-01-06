<?php

class Service_GroupementCommunes
{
    /**
     * Récupération de tous les groupements.
     *
     * @param null|mixed $num_insee Optionnel
     *
     * @return array
     */
    public function findAll($num_insee = null)
    {
        $model_groupement = new Model_DbTable_Groupement();

        if (null !== $num_insee) {
            return $model_groupement->getGroupementParVille($num_insee);
        }

        $select = $model_groupement->select()->order('LIBELLE_GROUPEMENT ASC');

        return $model_groupement->fetchAll($select)->toArray();
    }

    public function findGroupementAndGroupementType($num_insee = null)
    {
        $model_groupement = new Model_DbTable_Groupement();

        if (null !== $num_insee) {
            return $model_groupement->getGroupementParVille($num_insee);
        }

        return $model_groupement->getAllWithTypes();
    }

    public function findGroupementForEtablissement(array $ids_etablissement = [])
    {
        $model_groupement = new Model_DbTable_Groupement();

        return $model_groupement->getByEtablissement($ids_etablissement);
    }

    public function findGroupementForGroupementType(array $types_groupement = [])
    {
        $model_groupement = new Model_DbTable_Groupement();

        return $model_groupement->getByGroupementType($types_groupement);
    }

    public function reaffectationPreventioniste(string $groupement): void
    {
        $model_etsinfopreventioniste = new Model_DbTable_EtablissementInformationsPreventionniste();

        $model_etsinfopreventioniste->deleteEtablissementsPreventioniste($groupement);
        $model_etsinfopreventioniste->deleteCellulesPreventioniste($groupement);
        $model_etsinfopreventioniste->deleteSitesPreventioniste($groupement);

        $etablissementsPreventioniste = $model_etsinfopreventioniste->getEtablissementsPreventioniste($groupement);
        $model_etsinfopreventioniste->addPreventioniste($etablissementsPreventioniste);

        $cellulesPreventioniste = $model_etsinfopreventioniste->getCellulesPreventioniste($groupement);
        $model_etsinfopreventioniste->addPreventioniste($cellulesPreventioniste);

        $sitesPreventioniste = $model_etsinfopreventioniste->getSitesPreventioniste($groupement);
        $model_etsinfopreventioniste->addPreventioniste($sitesPreventioniste);
    }
}
