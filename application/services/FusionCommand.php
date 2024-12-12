<?php

class Service_FusionCommand
{
    public function mergeArrayCommune(array $objectJson): bool
    {
        foreach ($objectJson as $nouvelleFusion) {
            if ($this->checkExists($nouvelleFusion)) {
                return true;
            }

            $this->setNewNumINSEE($nouvelleFusion->NUMINSEE, $nouvelleFusion->listeCommune);
            $this->setAdresseRueFk($nouvelleFusion->NUMINSEE, $nouvelleFusion->listeCommune);

            $this->deleteArrayGroupementCommune($nouvelleFusion->listeCommune);
            $this->deleteArrayCommissionRegle($nouvelleFusion->listeCommune);

            $this->deleteArrayAdresseCommune($nouvelleFusion->listeCommune);
        }

        return false;
    }

    public function checkExists(stdClass $nouvelleFusion): bool
    {
        $modelAdresseCommune = new Model_DbTable_AdresseCommune();

        $numinseeQuery = $modelAdresseCommune->select()
            ->where(sprintf("NUMINSEE_COMMUNE = '%s'", $nouvelleFusion->NUMINSEE))
        ;
        $numinseeResult = $modelAdresseCommune->fetchAll($numinseeQuery)->toArray();
        $numinseeCount = count($numinseeResult);

        if (0 === $numinseeCount) {
            error_log(sprintf("Le numero INSEE %s n'existe pas dans la base de donnees, veuillez l'ajouter", $nouvelleFusion->NUMINSEE));

            return true;
        }

        if ($numinseeCount > 1) {
            error_log(sprintf('Il existe plusieurs lignes avec le numero INSEE %s, veuillez en supprimer', $nouvelleFusion->NUMINSEE));

            return true;
        }

        $libelleCommune = $numinseeResult['0']['LIBELLE_COMMUNE'];
        if (0 !== strcmp($nouvelleFusion->nomCommune, $libelleCommune)) {
            error_log(sprintf("Le numero INSEE %s n'a pas pour libelle %s, veuillez faire la mise a jour", $nouvelleFusion->NUMINSEE, $nouvelleFusion->nomCommune));

            return true;
        }

        return false;
    }

    public function setNewNumINSEE(string $newNumINSEE, array $arrayOldCommune): void
    {
        $modelEtablissementAdresse = new Model_DbTable_EtablissementAdresse();

        foreach ($arrayOldCommune as $oldCommune) {
            $select = $modelEtablissementAdresse->select()
                ->from('etablissementadresse')
                ->where(sprintf("etablissementadresse.NUMINSEE_COMMUNE = '%s'", $oldCommune->NUMINSEE))
            ;

            $oldCommunes = $modelEtablissementAdresse->fetchAll($select);

            foreach ($oldCommunes as $oldNumINSEE) {
                $oldNumINSEE->NUMINSEE_COMMUNE = $newNumINSEE;
                $oldNumINSEE->save();
            }
        }
    }

    public function setAdresseRueFk(string $newNumINSEE, array $arrayOldCommune): void
    {
        $modelAdresseRue = new Model_DbTable_AdresseRue();

        foreach ($arrayOldCommune as $oldCommune) {
            $select = $modelAdresseRue->select()
                ->from('adresserue')
                ->where(sprintf("adresserue.NUMINSEE_COMMUNE = '%s'", $oldCommune->NUMINSEE))
            ;

            $oldCommunes = $modelAdresseRue->fetchAll($select);

            foreach ($oldCommunes as $oldNumINSEE) {
                $oldNumINSEE->NUMINSEE_COMMUNE = $newNumINSEE;
                $oldNumINSEE->save();
            }
        }
    }

    public function deleteArrayGroupementCommune(array $arrayCommuneToDelete): void
    {
        $modelGroupementCommune = new Model_DbTable_GroupementCommune();

        foreach ($arrayCommuneToDelete as $communeToDelete) {
            $modelGroupementCommune->delete('NUMINSEE_COMMUNE = '.$communeToDelete->NUMINSEE);
        }
    }

    public function deleteArrayCommissionRegle(array $arrayCommissionRegleToDelete): void
    {
        $modelCommissionRegle = new Model_DbTable_CommissionRegle();

        foreach ($arrayCommissionRegleToDelete as $commissionRegleToDelete) {
            $modelCommissionRegle->delete('NUMINSEE_COMMUNE = '.$commissionRegleToDelete->NUMINSEE);
        }
    }

    public function deleteArrayAdresseCommune(array $arrayAdresseCommuneToDelete): void
    {
        $modelAdresseCommune = new Model_DbTable_AdresseCommune();

        foreach ($arrayAdresseCommuneToDelete as $adresseCommuneToDelete) {
            $modelAdresseCommune->delete('NUMINSEE_COMMUNE = '.$adresseCommuneToDelete->NUMINSEE);
        }
    }
}
