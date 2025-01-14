<?php

class Service_AdresseApi
{
    private $baseUrl = 'https://data.geopf.fr/geocodage/search';

    public function getAdresseApi(string $query, string $type, int $limit): ?array
    {
        $client = new Zend_Http_Client();
        $client->setUri($this->baseUrl);
        $client->setParameterGet('q', $query);
        $client->setParameterGet('type', $type);
        $client->setParameterGet('limit', $limit);
        $client->setConfig([
            'adapter' => Zend_Http_Client_Adapter_Curl::class,
        ]);

        try {
            $response = $client->request(Zend_Http_Client::GET);
        } catch (Zend_Http_Client_Exception $e) {
            error_log("Erreur lors de l'accès à l'API : " . $e->getMessage());

            return null;
        }

        if ($response->isSuccessful()) {
            $data = json_decode($response->getBody(), true);

            if (isset($data['features']) && count($data['features']) > 0) {
                    $addresses = [];
                    foreach ($data['features'] as $feature) {
                        $addresses[] = [
                            'ADRESSE' => $feature['properties']['label'] ?? '',
                            'longitude' => $feature['geometry']['coordinates'][0] ?? '',
                            'latitude' => $feature['geometry']['coordinates'][1] ?? '',
                            'insee_code' => $feature['properties']['citycode'] ?? '',
                            'postal_code' => $feature['properties']['postcode'] ?? '',
                            'city' => $feature['properties']['city'] ?? '',
                            'street' =>$feature['properties']['street'] ?? ''
                        ];
                    }
                    return $addresses;
            }
            
        } else {
            error_log("Erreur dans la réponse de l'API : " . $response->getStatus() . ' ' . $response->getMessage());
        }

        return null;
    }
}