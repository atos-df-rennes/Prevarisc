<?php

require_once '../models/DbTable/EtablissementAdresse.php';
require_once '../models/DbTable/EtablissementAdresseApi.php';

$etablissementRead =  new Model_DbTable_EtablissementAdresse();
$etablissementWrite= new Model_DbTable_EtablissementAdresseAPi();

$service_api = new Service_AdresseApi();

$adresses = $etablissementRead->getAllAdresses();

foreach ($adresses as $adresse) {
    $etablissement_id = $adresse['ID_ETABLISSEMENT'];
    $adresse_api = $service_api->getAdresseApi(
        $adresse['NUMERO_ADRESSE'] ?? '' . ' ' . $adresse['LIBELLE_RUE'] . ' ' . $adresse['COMPLEMENT_ADRESSE'] . ' ' . $adresse['CODEPOSTAL_COMMUNE']. ' ' . $adresse['LIBELLE_COMMUNE'],
        $adresse['NUMERO_ADRESSE'] ? 'housenumber' : 'street', 1
    );

    if ($adresse_api) {
        $etablissementWrite->save($adresse_api, $etablissement_id);
    } else {
        echo "Aucune adresse trouvée pour l'adresse \n";
    }
}
