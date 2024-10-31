<?php 
class AdresseAPIService
{
    private $baseUrl = 'https://api-adresse.data.gouv.fr/search/';

    public function getAdresse_api(string $query, string $type, int $limit)
    {
        $client = new Zend_Http_Client();
        $client->setUri($this->baseUrl);
        $client->setParameterGet('q', $query);
        $client->setParameterGet('type', $type);
        $client->setParameterGet('limit', $limit);

        try {
            $response = $client->request('GET');
        } catch (Zend_Http_Client_Exception $e) {
            error_log("Erreur lors de l'accès à l'API : " . $e->getMessage());
            return null;
        }

        if ($response->isSuccessful()) {
            $data = json_decode($response->getBody(), true);

            if (isset($data['features']) && count($data['features']) > 0) {
                if ($limit === 1) {
                    $firstFeature = $data['features'][0];
                    return [
                        'ADRESSE' => $firstFeature['properties']['label'] ?? '',
                        'longitude' => $firstFeature['geometry']['coordinates'][0] ?? '',
                        'latitude' => $firstFeature['geometry']['coordinates'][1] ?? '',
                        'insee_code' => $firstFeature['properties']['citycode'] ?? '',
                        'postal_code' => $firstFeature['properties']['postcode'] ?? '',
                        'city' => $firstFeature['properties']['city'] ?? ''
                    ];
                } else {
                    $addresses = [];
                    foreach ($data['features'] as $feature) {
                        $addresses[] = [
                            'ADRESSE' => $feature['properties']['label'] ?? '',
                            'longitude' => $feature['geometry']['coordinates'][0] ?? '',
                            'latitude' => $feature['geometry']['coordinates'][1] ?? '',
                            'insee_code' => $feature['properties']['citycode'] ?? '',
                            'postal_code' => $feature['properties']['postcode'] ?? '',
                            'city' => $feature['properties']['city'] ?? ''
                        ];
                    }
                    return $addresses;
                }
            }
        } else {
            error_log("Erreur dans la réponse de l'API : " . $response->getStatus() . ' ' . $response->getMessage());
        }

        return null;
    }
}
