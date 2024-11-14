<?php

class Service_Descriptif
{
    private $modelChamp;

    private $modelChampValeurListe;

    private $modelRubrique;

    private $modelValeur;

    private $serviceValeur;

    private $serviceFormulaire;

    private $capsuleRubrique;

    private $modelDisplayRubrique;

    private $serviceRubrique;

    public function __construct(string $capsuleRubrique, Zend_Db_Table_Abstract $modelDisplayRubrique, $serviceRubrique)
    {
        // Services communs
        $this->modelChamp = new Model_DbTable_Champ();
        $this->modelChampValeurListe = new Model_DbTable_ChampValeurListe();
        $this->modelRubrique = new Model_DbTable_Rubrique();
        $this->modelValeur = new Model_DbTable_Valeur();

        $this->serviceValeur = new Service_Valeur();
        $this->serviceFormulaire = new Service_Formulaire();

        // Services spécifiques à l'objet, setter dans le Service correspondant
        $this->capsuleRubrique = $capsuleRubrique;
        $this->modelDisplayRubrique = $modelDisplayRubrique;
        $this->serviceRubrique = $serviceRubrique;
    }

    public function getRubriques(int $idObject, string $classObject): array
    {
        $rubriques = $this->modelRubrique->getRubriquesByCapsuleRubrique($this->capsuleRubrique);

        foreach ($rubriques as &$rubrique) {
            $userDisplay = $this->modelDisplayRubrique->getUserDisplay($idObject, $rubrique['ID_RUBRIQUE']);

            if (!empty($userDisplay)) {
                $rubrique['DISPLAY'] = $userDisplay['USER_DISPLAY'];
            }

            $rubrique['CHAMPS'] = $this->modelChamp->getChampsByRubrique($rubrique['ID_RUBRIQUE']);

            foreach ($rubrique['CHAMPS'] as &$champ) {
                if ('Parent' === $champ['TYPE']) {
                    $champ['FILS'] = $this->modelChamp->getChampsFromParent($champ['ID_CHAMP']);

                    if (1 === $champ['tableau']) {
                        $listValeurs = [];

                        foreach ($champ['FILS'] as $champFils) {
                            $listValeurs[$champFils['ID_CHAMP']] = $this->serviceValeur->getAll($champFils['ID_CHAMP'], $idObject, $classObject);
                        }

                        $inputs = $this->serviceFormulaire->getInputs($champ);

                        // Affectation des valeurs
                        $champ['FILS']['VALEURS'] = $this->serviceFormulaire->getArrayValuesWithPattern($listValeurs, $inputs);

                        // Affectation des modeles type des inputs sans valeurs
                        $champ['FILS']['INPUTS'] = $inputs;
                    } else {
                        foreach ($champ['FILS'] as &$champFils) {
                            $valeur = $this->serviceValeur->get($champFils['ID_CHAMP'], $idObject, $classObject);

                            $champFils['VALEUR'] = $valeur['VALEUR'];
                            $champFils['ID_VALEUR'] = $valeur['ID_VALEUR'];
                        }

                        unset($champFils);
                    }
                } else {
                    $champ['VALEUR'] = $this->serviceValeur->get($champ['ID_CHAMP'], $idObject, $classObject)['VALEUR'];
                    $champ['ID_VALEUR'] = $this->serviceValeur->get($champ['ID_CHAMP'], $idObject, $classObject)['ID_VALEUR'];
                }
            }

            unset($champ);
        }

        unset($rubrique);

        return $rubriques;
    }

    public function getValeursListe(): array
    {
        $champsValeurListe = $this->modelChampValeurListe->findAll();
        $sortedChampValeurListe = [];

        foreach ($champsValeurListe as $champValeurListe) {
            $sortedChampValeurListe[$champValeurListe['ID_CHAMP']][] = $champValeurListe;
        }

        return $sortedChampValeurListe;
    }

    public function saveRubriqueDisplay(string $key, int $idElement, int $value): void
    {
        $explodedRubrique = explode('-', $key);
        $idRubrique = end($explodedRubrique);

        $this->serviceRubrique->updateRubriqueDisplay($idRubrique, $idElement, $value);
    }

    public function saveValeurChamp(string $key, int $idObject, string $classObject, $value): void
    {
        [, $idChamp, $idx] = explode('-', $key);

        if (null !== $idx) {
            $idx = (int) $idx;
        }

        $this->saveValeur((int) $idChamp, $idObject, $classObject, $value, $idx);
    }

    /**
     * Compare l'array initial et celui final dans le post :
     * - Si une valeur est présente dans l'array final alors qu'elle ne l'était pas dans l'array initial alors on insert
     * - Si une valeur a changé de valeur ou d'index alors on update
     * - Si une valeur était dans l'array initial et ne l'est plus dans l'array final alors on le supprime.
     */
    public function saveChangeTable(array $initArrayValue, array $newArrayValue, string $classObject, int $idObject): void
    {
        $serviceUtilsDescriptif = new Service_Utils_Descriptif();

        $tableauDeComparaison = $serviceUtilsDescriptif->initTableValues($initArrayValue);
        $tableauDeComparaison = $serviceUtilsDescriptif->updateTableValues($tableauDeComparaison, $newArrayValue, $classObject, $idObject);

        $serviceUtilsDescriptif->deleteTableValues($tableauDeComparaison);
    }

    public function groupInputByOrder(array $initialList, int $idObject, string $classObject): array
    {
        $newList = [];
        $expectedValeurNumberOfArguments = 5;
        $expectedChampNumberOfArguments = 4;

        foreach ($initialList as $inputName => $value) {
            $explodedInput = explode('-', $inputName);
            $numberOfArguments = count($explodedInput);

            if (
                $expectedValeurNumberOfArguments !== $numberOfArguments
                && $expectedChampNumberOfArguments !== $numberOfArguments
            ) {
                continue;
            }

            if ($expectedValeurNumberOfArguments === $numberOfArguments && (isset(explode('-', $inputName)[2]) && '' !== explode('-', $inputName)[2]) && '0' !== explode('-', $inputName)[1]) {
                $idxInput = explode('-', $inputName)[1];
                $idParent = explode('-', $inputName)[2];
                $idInput = explode('-', $inputName)[3];
                $idValeur = explode('-', $inputName)[4];
            } else {
                [, $idInput, $idxInput, $idParent] = $explodedInput;
                $idValeur = $this->modelValeur->getByChampAndObject((int) $idInput, $idObject, $classObject, (int) $idxInput)['ID_VALEUR'];
            }

            if (!array_key_exists($idParent, $newList)) {
                $newList[$idParent] = [];
            }

            if (!array_key_exists($idxInput, $newList[$idParent])) {
                $newList[$idParent][$idxInput] = [];
            }

            $newList[$idParent][$idxInput][$idInput]['VALEUR'] = $value;
            $newList[$idParent][$idxInput][$idInput]['ID_VALEUR'] = $idValeur;
        }

        $tmpList = [];
        foreach ($newList as $parent => $listIdx) {
            foreach ($listIdx as $idx => $input) {
                foreach ($input as $idChamp => $valeur) {
                    $tmpList[$parent][array_search($idx, array_keys($listIdx), true) + 1][$idChamp] = $valeur;
                }
            }
        }

        return $tmpList;
    }

    private function saveValeur(int $idChamp, int $idObject, string $classObject, $value, ?int $idx = null): void
    {
        $valueInDB = $this->modelValeur->getByChampAndObject($idChamp, $idObject, $classObject, $idx);
        $valueInDB = $this->modelValeur->find($valueInDB['ID_VALEUR'])->current();

        if (!$valueInDB instanceof Zend_Db_Table_Row_Abstract) {
            $this->serviceValeur->insert($idChamp, $idObject, $classObject, $value, $idx);
        } else {
            $this->serviceValeur->update($idChamp, $valueInDB, $value, $idx);
        }
    }
}
