<?php

class Service_Utils_Descriptif
{
    private $modelValeur;

    private $serviceValeur;

    public function __construct()
    {
        $this->modelValeur = new Model_DbTable_Valeur();
        $this->serviceValeur = new Service_Valeur();
    }

    public function initTableValues(array $initArrayValue): array
    {
        $tableauDeComparaison = [];

        foreach ($initArrayValue as $rubrique) {
            foreach ($rubrique['CHAMPS'] as $champ) {
                if (1 === $champ['tableau']) {
                    foreach ($champ['FILS']['VALEURS'] as $valeursFils) {
                        foreach ($valeursFils as $valeur) {
                            if (null !== $valeur['ID_VALEUR']) {
                                $tableauDeComparaison[$valeur['ID_VALEUR']] = $valeur;
                                $tableauDeComparaison[$valeur['ID_VALEUR']]['CHECKED'] = false;
                            }
                        }
                    }
                }
            }
        }

        return $tableauDeComparaison;
    }

    public function updateTableValues(array $tableauDeComparaison, array $newArrayValue, string $classObject, int $idObject): array
    {
        foreach ($newArrayValue as $champParent) {
            foreach ($champParent as $newIdxValeur => $valeurs) {
                foreach ($valeurs as $idChamp => $valeur) {
                    // Si la valeur vient d'être ajoutée alors on insert en DB, sinon on met à jour
                    if ('NULL' === $valeur['ID_VALEUR']) {
                        $this->serviceValeur->insert($idChamp, $idObject, $classObject, $valeur['VALEUR'], $newIdxValeur);
                    } elseif (
                        !isset($tableauDeComparaison[$valeur['ID_VALEUR']]['IDX_VALEUR'])
                        || $newIdxValeur !== $tableauDeComparaison[$valeur['ID_VALEUR']]['IDX_VALEUR']
                        || $valeur['VALEUR'] !== $tableauDeComparaison[$valeur['ID_VALEUR']]['VALEUR']
                    ) {
                        $valueInDB = $this->modelValeur->find($valeur['ID_VALEUR'])->current();

                        if (null === $valueInDB) {
                            continue;
                        }

                        $this->serviceValeur->update($idChamp, $valueInDB, $valeur['VALEUR'], $newIdxValeur);
                    }

                    $tableauDeComparaison[$valeur['ID_VALEUR']]['CHECKED'] = true;
                }
            }
        }

        return $tableauDeComparaison;
    }

    public function deleteTableValues(array $tableauDeComparaison): void
    {
        foreach ($tableauDeComparaison as $champ) {
            if (false === $champ['CHECKED']) {
                $this->modelValeur->delete('ID_VALEUR  = '.$champ['ID_VALEUR']);
            }
        }
    }
}
