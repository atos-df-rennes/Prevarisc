<?php

class Service_FusionCommand
{

    public function mergeArrayCommune($objectJson){
        foreach ($objectJson as $nouvelleFusion) {
            $this->setNewNumINSEE($nouvelleFusion->NUMINSEE, $nouvelleFusion->listeCommune);           
            $this->setAdresseRueFk($nouvelleFusion->NUMINSEE, $nouvelleFusion->listeCommune);
            $this->deleteArrayGroupementCommune($nouvelleFusion->listeCommune);
            $this->deleteArrayCommissionRegle($nouvelleFusion->listeCommune);
        }
    }

    public function setNewNumINSEE($newNumINSEE, $arrayOldCommune){
        $modelEtablissementAdresse = new Model_DbTable_EtablissementAdresse();
        foreach ($arrayOldCommune as $oldCommune) {
            $select = $modelEtablissementAdresse->select()
                ->from('etablissementadresse')
                ->where("etablissementadresse.NUMINSEE_COMMUNE = '$oldCommune->NUMINSEE'");
            foreach ($modelEtablissementAdresse->fetchAll($select) as $oldNumINSEE) {
                $oldNumINSEE->NUMINSEE_COMMUNE = $newNumINSEE;
                $oldNumINSEE->save();
            }
        }     
    }

    //TODO reset les valeur des fk au niveau d adresse etablissement sinon impossible se delete les adresses commune si une clef est renseignee dans adresse rue
    //TODO voir s il faut faire une table a part 
    public function setAdresseRueFk($newNumINSEE, $arrayOldCommune){
        $modelAdresseRue = new Model_DbTable_AdresseRue();
        foreach ($arrayOldCommune as $oldCommune) {
            $select = 
                $modelAdresseRue->select()
                    ->setIntegrityCheck(false)
                    ->from('adresserue')
                    ->where("adresserue.NUMINSEE_COMMUNE = '$oldCommune->NUMINSEE'");
            foreach ($modelAdresseRue->fetchAll($select) as $oldNumINSEE) {
                $oldNumINSEE->NUMINSEE_COMMUNE = $newNumINSEE;
                $oldNumINSEE->save();
            }
        }    
    }

    public function deleteArrayGroupementCommune($arrayCommuneToDelete){
        $modelGroupementCommune = new Model_DbTable_GroupementCommune();
        foreach ($arrayCommuneToDelete as $communeToDelete) {
            $select = $modelGroupementCommune->select()
            ->from('groupementcommune')
            ->where("groupementcommune.NUMINSEE_COMMUNE = '$communeToDelete->NUMINSEE'");
            $toDelete = $modelGroupementCommune->fetchAll($select);        
            $modelGroupementCommune->delete('NUMINSEE_COMMUNE = '.$communeToDelete->NUMINSEE);
        }
    }

    public function deleteArrayCommissionRegle($arrayCommissionRegleToDelete){
        $modelCommissionRegle = new Model_DbTable_CommissionRegle();
        foreach ($arrayCommissionRegleToDelete as $commissionRegleToDelete) {
            $select = $modelCommissionRegle->select()
            ->from('commissionregle')
            ->where("commissionregle.NUMINSEE_COMMUNE = '$commissionRegleToDelete->NUMINSEE'");
            $toDelete = $modelCommissionRegle->fetchAll($select);        
            $modelCommissionRegle->delete('NUMINSEE_COMMUNE = '.$commissionRegleToDelete->NUMINSEE);
        }
    }
}
?>