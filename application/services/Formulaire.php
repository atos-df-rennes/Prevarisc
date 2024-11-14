<?php

class Service_Formulaire
{
    private $modelChamp;

    public function __construct()
    {
        $this->modelChamp = new Model_DbTable_Champ();
    }

    public function getAllCapsuleRubrique(): array
    {
        $modelCapsuleRubrique = new Model_DbTable_CapsuleRubrique();

        return $modelCapsuleRubrique->fetchAll()->toArray();
    }

    public function getAllListeTypeChampRubrique(): array
    {
        $modelListeTypeChampRubrique = new Model_DbTable_ListeTypeChampRubrique();

        return $modelListeTypeChampRubrique->fetchAll()->toArray();
    }

    public function insertRubrique(array $rubrique): int
    {
        $modelCapsuleRubrique = new Model_DbTable_CapsuleRubrique();
        $modelRubrique = new Model_DbTable_Rubrique();

        $idCapsuleRubriqueArray = $modelCapsuleRubrique->getCapsuleRubriqueIdByName($rubrique['capsule_rubrique']);
        $idCapsuleRubrique = $idCapsuleRubriqueArray['ID_CAPSULERUBRIQUE'];

        $idRubrique = $modelRubrique->insert([
            'NOM' => $rubrique['nom_rubrique'],
            'DEFAULT_DISPLAY' => (int) $rubrique['afficher_rubrique'],
            'ID_CAPSULERUBRIQUE' => $idCapsuleRubrique,
            'idx' => $rubrique['idx'],
        ]);

        return (int) $idRubrique;
    }

    public function insertChamp(array $champ, array $rubrique, bool $isParent = false): array
    {
        $modelChampValeurListe = new Model_DbTable_ChampValeurListe();
        $modelListeTypeChampRubrique = new Model_DbTable_ListeTypeChampRubrique();

        $typeChamp = 'type_champ';
        $nomChamp = 'nom_champ';

        if ($isParent) {
            $typeChamp = 'type_champ_enfant';
            $nomChamp = 'nom_champ_enfant';
        }

        $idTypeChamp = (int) $champ[$typeChamp];
        $idListe = $modelListeTypeChampRubrique->getIdTypeChampByName('Liste')['ID_TYPECHAMP'];

        $dataToInsert = [
            'NOM' => $champ[$nomChamp],
            'ID_TYPECHAMP' => $idTypeChamp,
            'ID_RUBRIQUE' => $rubrique['ID_RUBRIQUE'],
            'idx' => $champ['idx'],
        ];

        if (!empty($champ['ID_CHAMP_PARENT'])) {
            $dataToInsert['ID_PARENT'] = $champ['ID_CHAMP_PARENT'];
        }

        $idChamp = $this->modelChamp->insert($dataToInsert);

        if ($idTypeChamp === $idListe) {
            // On récupère les valeurs de la liste séparément des autres champs
            $listValueArray = array_filter($champ, function ($key): bool {
                return 0 === strpos($key, 'valeur-ajout-');
            }, ARRAY_FILTER_USE_KEY);

            foreach ($listValueArray as $listValue) {
                $modelChampValeurListe->insert([
                    'VALEUR' => $listValue,
                    'ID_CHAMP' => $idChamp,
                ]);
            }
        }

        return $this->modelChamp->find($idChamp)->current()->toArray();
    }

    /**
     * Retourne la liste des pattern des champs fils d un champ parent.
     */
    public function getInputs(array $champParent): array
    {
        $listChampPattern = [];

        foreach (array_column($champParent['FILS'], 'ID_CHAMP') as $IdChamp) {
            $champDb = $this->modelChamp->getTypeChamp($IdChamp);
            $patternParam = [
                'VALEUR' => null,
                'ID_VALEUR' => null,
                'IDX_VALEUR' => null,
                'ID_PARENT' => $champParent['ID_CHAMP'],
                'ID_TYPECHAMP' => $champDb['ID_TYPECHAMP'],
                'ID_CHAMP' => $champDb['ID_CHAMP'],
            ];
            $listChampPattern[$IdChamp] = $patternParam;
        }

        return $listChampPattern;
    }

    public function getArrayValuesWithPattern(array $listValeurs, array $listChampPattern): array
    {
        $arrayReturn = [];

        foreach ($listValeurs as $idChampFils => $valeurs) {
            foreach ($valeurs as $valeur) {
                if (empty($arrayReturn[$valeur['IDX_VALEUR']])) {
                    foreach ($listChampPattern as $idChampPattern => $pattern) {
                        $arrayReturn[$valeur['IDX_VALEUR']][$idChampPattern] = $pattern;
                    }
                }

                $arrayReturn[$valeur['IDX_VALEUR']][$idChampFils] = $valeur;
            }
        }

        foreach ($arrayReturn as $idxLigne => $inputs) {
            foreach ($inputs as $key => $input) {
                if (null === $input['ID_VALEUR']) {
                    $inputs[$key]['IDX_VALEUR'] = $idxLigne;
                    $inputs[$key]['STR_DATA'] = 'valeur-'.$idxLigne.'-'.$input['ID_PARENT'].'-'.$input['ID_CHAMP'].'-NULL';
                }
            }
        }

        ksort($arrayReturn);

        return $arrayReturn;
    }
}
