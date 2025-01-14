<?php

class MigrationAdressesController extends Zend_Controller_Action
{
    public function indexAction()
    {
        $this->_helper->layout->setLayout('menu_admin');
    
    }

   
    
    public function executeMigrationAction()
{
    $this->_helper->layout->disableLayout();
    $this->_helper->viewRenderer->setNoRender(true);

    $etablissementAdresseModel = new Model_DbTable_EtablissementAdresse();
    $etablissementAdresseApiModel = new Model_DbTable_EtablissementAdresseApi();
    $adresseApiService = new Service_AdresseApi();

    
        $oldAddresses = $etablissementAdresseModel->getAllAdresses();
        $totalAddresses = count($oldAddresses); 
        $migratedAddresses = 0;

        
        foreach ($oldAddresses as $oldAddress) {
            $query =  trim(($oldAddress['NUMERO_ADRESSE'] ?? '') . ' ' . 
                           ($oldAddress['LIBELLE_RUE'] ?? '') . ' ' . 
                           ($oldAddress['COMPLEMENT_ADRESSE'] ?? '') . ' ' . 
                           ($oldAddress['CODEPOSTAL_COMMUNE'] ?? '') . ' ' . 
                           ($oldAddress['LIBELLE_COMMUNE'] ?? ''));
            $apiResult = $adresseApiService->getAdresseApi($query, 'housenumber', 1);


            if ($apiResult && count($apiResult) > 0) {
                $apiAddress = $apiResult[0];


                    $dataToSave = [
                        'ADRESSE' => $apiAddress['ADRESSE'],
                        'LON_ETABLISSEMENTADRESSE' => $apiAddress['longitude'],
                        'LAT_ETABLISSEMENTADRESSE' => $apiAddress['latitude'],
                        'NUMINSEE_COMMUNE' => $apiAddress['insee_code'],
                        'CODEPOSTAL_COMMUNE' => $apiAddress['postal_code'],
                        'LIBELLE_COMMUNE' => $apiAddress['city'],
                        'LIBELLE_RUE' => $apiAddress['street'],
                    ];


               $addressExists = $etablissementAdresseApiModel->exists($dataToSave);
                if (!$addressExists) {
                    $etablissementAdresseApiModel->save($dataToSave, $oldAddress['ID_ETABLISSEMENT']);
                    $migratedAddresses++;
                }else {
                    error_log("Migration échouée pour l'adresse : " . $query . " - L'adresse existe déjà dans la base de données.");
                }

            } else {
                error_log("Aucun résultat de l'API pour la requête: " . $query);
            }
        }

        echo json_encode([
            'total' => $totalAddresses,
            'migrated' => $migratedAddresses,
        ]);
    
}

}