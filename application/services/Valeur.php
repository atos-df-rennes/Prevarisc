<?php

class Service_Valeur
{
    public function get(int $idChamp, int $idObject, $classObject)
    {
        $modelValeur = new Model_DbTable_Valeur();
        $valeur = $modelValeur->getByChampAndObject($idChamp, $idObject, $classObject);

        if (null !== $valeur) {
            $typeValeur = $this->getTypeValeur($idChamp);
            $valeur = $valeur[$typeValeur];
        }

        return $valeur;
    }

    public function insert(int $idChamp, int $idObject, $classObject, $value): void
    {
        if ('' !== $value) {
            $modelValeur = new Model_DbTable_Valeur();
            
            $typeValeur = $this->getTypeValeur($idChamp);  
            $idValeurInsert = $modelValeur->insert([
            $typeValeur => $value,
            'ID_CHAMP' => $idChamp,
            ]);

            if(strpos(strtolower($classObject),"dossier") !== false){
                
                $modelDossierValeur = new Model_DbTable_DossierValeur();
                try{
                    $modelDossierValeur->insert(
                        [
                            'ID_DOSSIER'    => $idObject,
                            'ID_VALEUR'     => $idValeurInsert
                        ]
                    );
                }
                catch (Exception $e) {
                    echo $e;
                }

            }
        }
    }

    public function update(int $idChamp, $valueInDB, $newValue): void
    {
        if ('' === $newValue) {
            $valueInDB->delete();
        } else {
            $typeValeur = $this->getTypeValeur($idChamp);

            $valueInDB->{$typeValeur} = $newValue;
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

            default:
                throw new Exception('Type de champ non supporté.');

                break;
        }

        return $typeValeur;
    }
}
