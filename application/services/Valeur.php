<?php

class Service_Valeur
{
    private $modelValeur;

    public function __construct()
    {
        $this->modelValeur = new Model_DbTable_Valeur();
    }

    public function get(int $idChamp, int $idObject, string $classObject): array
    {
        $valeur = $this->modelValeur->getByChampAndObject($idChamp, $idObject, $classObject);

        $idValeur = null;
        $idxValeur = null;

        if ($valeur instanceof Zend_Db_Table_Row_Abstract) {
            $typeValeur = $this->getTypeValeur($idChamp);
            $idValeur = $valeur['ID_VALEUR'];
            $idxValeur = $valeur['idx'];
            $valeur = $valeur[$typeValeur];

            if ('VALEUR_DATE' === $typeValeur) {
                $valeur = Service_Utils_Date::convertFromMySQL($valeur);
            }
        }

        return ['ID_VALEUR' => $idValeur, 'VALEUR' => $valeur, 'IDX_VALEUR' => $idxValeur];
    }

    /**
     * @return array<mixed, array<'ID_CHAMP'|'ID_PARENT'|'ID_TYPECHAMP'|'ID_VALEUR'|'IDX_VALEUR'|'STR_DATA'|'TYPE'|'VALEUR', mixed>>
     */
    public function getAll(int $idChamp, int $idObject, string $classObject): array
    {
        $modelChamp = new Model_DbTable_Champ();

        $typeChamp = $modelChamp->getTypeChamp($idChamp)['TYPE'];
        $typeValeur = $this->getTypeValeur($idChamp);
        $valeurs = $this->modelValeur->getAllByChampAndObject($idChamp, $idObject, $classObject);

        $retourValeurs = [];
        if (!empty($valeurs)) {
            foreach ($valeurs as $valeur) {
                $strDataValues = [
                    'valeur',
                    $valeur['idx'],
                    $valeur['ID_PARENT'],
                    $valeur['ID_CHAMP'],
                    $valeur['ID_VALEUR'] ?? 'NULL',
                ];
                $strData = implode('-', $strDataValues);

                $value = $valeur[$typeValeur];
                if ('VALEUR_DATE' === $typeValeur) {
                    $value = Service_Utils_Date::convertFromMySQL($value);
                }

                $retourValeurs[] =
                    [
                        'VALEUR' => $value,
                        'ID_VALEUR' => $valeur['ID_VALEUR'],
                        'IDX_VALEUR' => $valeur['idx'],
                        'ID_PARENT' => $valeur['ID_PARENT'],
                        'ID_TYPECHAMP' => $valeur['ID_TYPECHAMP'],
                        'TYPE' => $typeChamp,
                        'ID_CHAMP' => $valeur['ID_CHAMP'],
                        'STR_DATA' => $strData,
                    ];
            }
        }

        return $retourValeurs;
    }

    public function insert(int $idChamp, int $idObject, string $classObject, $value, ?int $idx = null): void
    {
        if ('' !== $value) {
            $typeValeur = $this->getTypeValeur($idChamp);

            if ('VALEUR_DATE' === $typeValeur) {
                $value = Service_Utils_Date::convertToMySQL($value);
            }

            $idValeurInsert = $this->modelValeur->insert([
                $typeValeur => $value,
                'ID_CHAMP' => $idChamp,
                'idx' => $idx,
            ]);

            if (false !== strpos($classObject, 'Dossier')) {
                $modelDossierValeur = new Model_DbTable_DossierValeur();

                $modelDossierValeur->insert(
                    [
                        'ID_DOSSIER' => $idObject,
                        'ID_VALEUR' => $idValeurInsert,
                    ]
                );
            }

            if (false !== strpos($classObject, 'Etablissement')) {
                $modelEtablissementValeur = new Model_DbTable_EtablissementValeur();

                $modelEtablissementValeur->insert(
                    [
                        'ID_ETABLISSEMENT' => $idObject,
                        'ID_VALEUR' => $idValeurInsert,
                    ]
                );
            }
        }
    }

    public function update(int $idChamp, $valueInDB, $newValue, ?int $idx = null): void
    {
        if ('' === $newValue) {
            $valueInDB->delete();
        } else {
            $typeValeur = $this->getTypeValeur($idChamp);

            if ('VALEUR_DATE' === $typeValeur) {
                $newValue = Service_Utils_Date::convertToMySQL($newValue);
            }

            $valueInDB->{$typeValeur} = $newValue;
            $valueInDB->idx = $idx;
            $valueInDB->save();
        }
    }

    private function getTypeValeur(int $idChamp): string
    {
        $modelChamp = new Model_DbTable_Champ();
        $typeValeur = '';

        $champ = $modelChamp->find($idChamp)->current();

        switch ($champ['ID_TYPECHAMP']) {
            case 1:
            case 3:
                $typeValeur = 'VALEUR_STR';

                break;

            case 2:
                $typeValeur = 'VALEUR_LONG_STR';

                break;

            case 4:
                $typeValeur = 'VALEUR_INT';

                break;

            case 5:
                $typeValeur = 'VALEUR_CHECKBOX';

                break;

            case 7:
                $typeValeur = 'VALEUR_DATE';

                break;

            default:
                throw new Exception('Type de champ non supporté.');
        }

        return $typeValeur;
    }
}
