<?php

class Service_Platau
{
    private const HEALTHCHECK_ENDPOINT = 'healthcheck';

    private $datastore;

    private $platauServiceFilePath;

    private $platauConfigFilePath;

    private $pisteClientId;

    private $pisteClientSecret;

    public function __construct()
    {
        $this->datastore = Zend_Controller_Front::getInstance()->getParam('bootstrap')->getResource('dataStore');
        $this->platauServiceFilePath = realpath(PLATAU_PATH.DS.implode(DS, ['src', 'Service', 'PlatauAbstract.php']));
        $this->platauConfigFilePath = realpath(PLATAU_PATH.DS.'config.json');
        [$this->pisteClientId, $this->pisteClientSecret] = $this->getPisteCredentials();
    }

    /**
     * Vérifie la santé de Plat'AU.
     */
    public function executeHealthcheck(): array
    {
        $pisteTokenData = $this->getPisteTokenData();
        $pisteToken = $pisteTokenData['access_token'] ?? null;

        if (null === $pisteToken || !$this->isTokenValid($pisteTokenData)) {
            $pisteData = $this->requestPisteToken();

            if (null === $pisteData) {
                return [
                    'healthcheckOk' => false,
                    'errorOrigin' => 'prevarisc',
                ];
            }

            $this->storePisteToken($pisteData);
            $pisteToken = $pisteData['access_token'];
        }

        $platauHealth = $this->requestPlatauHealthcheck($pisteToken);
        $platauHealth = json_decode($platauHealth);

        if (null === $platauHealth) {
            error_log("Erreur lors de la vérification de la santé de Plat'AU.");

            return [
                'healthcheckOk' => false,
                'errorOrigin' => 'prevarisc',
            ];
        }

        if (true !== $platauHealth->etatGeneral) {
            error_log("L'état général de Plat'AU est en erreur.");

            return [
                'healthcheckOk' => false,
                'errorOrigin' => 'PlatAU',
            ];
        }

        if (true !== $platauHealth->etatBdd) {
            error_log("L'état BDD de Plat'AU est en erreur.");

            return [
                'healthcheckOk' => false,
                'errorOrigin' => 'PlatAU',
            ];
        }

        return [
            'healthcheckOk' => true,
        ];
    }

    private function getPisteCredentials(): array
    {
        $platauConfigContentAsString = file_get_contents($this->platauConfigFilePath);
        $decodedContent = json_decode($platauConfigContentAsString, true);

        $platauOptions = $decodedContent['platau.options'];

        return [$platauOptions['PISTE_CLIENT_ID'], $platauOptions['PISTE_CLIENT_SECRET']];
    }

    /**
     * Récupère le token PISTE.
     */
    private function requestPisteToken(): ?array
    {
        $url = $this->getConstInFile($this->platauServiceFilePath, 'PISTE_ACCESS_TOKEN_URL');

        if (null === $url) {
            error_log("L'URL de PISTE n'a pas pu être récupérée correctement.");

            return null;
        }

        $platauClient = new Service_PlatauClient();
        $platauClient->addOption(CURLOPT_URL, $url);
        $platauClient->addOption(
            CURLOPT_POSTFIELDS,
            sprintf('scope=openid&grant_type=client_credentials&client_id=%s&client_secret=%s', $this->pisteClientId, $this->pisteClientSecret)
        );

        $curlHandle = curl_init();
        curl_setopt_array($curlHandle, $platauClient->getOptions());
        $data = curl_exec($curlHandle);

        if ('' !== ($error = curl_error($curlHandle))) {
            error_log($error);

            return null;
        }

        curl_close($curlHandle);

        $decodedData = json_decode($data, true);

        if (null === $decodedData) {
            error_log("La récupération du token PISTE n'a rien renvoyé. Vérifiez vos identifiants.");

            return null;
        }

        if (array_key_exists('error', $decodedData)) {
            error_log(sprintf('Erreur lors de la récupération du token PISTE : %s', $decodedData['error']));

            return null;
        }

        return $decodedData;
    }

    /**
     * Effectue le healthcheck sur l'API Plat'AU.
     */
    private function requestPlatauHealthcheck(string $pisteToken): ?string
    {
        $url = $this->getConstInFile($this->platauServiceFilePath, 'PLATAU_URL');

        if (null === $url) {
            error_log("L'URL de Plat'AU n'a pas pu être récupérée correctement.");

            return null;
        }

        $url .= self::HEALTHCHECK_ENDPOINT;

        $platauClient = new Service_PlatauClient();
        $platauClient->addOption(CURLOPT_URL, $url);
        $platauClient->addOption(
            CURLOPT_HTTPHEADER,
            [
                'Content-Type: application/json',
                sprintf('Authorization: Bearer %s', $pisteToken),
            ]
        );

        $curlHandle = curl_init();
        curl_setopt_array($curlHandle, $platauClient->getOptions());
        $data = curl_exec($curlHandle);

        if ('' !== ($error = curl_error($curlHandle))) {
            error_log($error);

            return null;
        }

        curl_close($curlHandle);

        if ('' === $data) {
            error_log("Le healthcheck n'a rien renvoyé. Vérifiez que l'URL de Plat'AU est correctement renseignée (environnement, version).");

            return null;
        }

        return $data;
    }

    /**
     * Récupère la valeur d'une constante dans un fichier.
     * e.g. `private const PATH = '/home/user/' renvoie /home/user/`.
     */
    private function getConstInFile(string $filepath, string $const): ?string
    {
        $fileContent = file($filepath);

        foreach ($fileContent as $line) {
            if (false !== strpos($line, $const)) {
                return explode("'", $line)[1];
            }
        }

        return null;
    }

    /**
     * Récupère le token PISTE stocké s'il existe.
     */
    private function getPisteTokenData(): array
    {
        $filepath = $this->datastore->getFilePath(['ID_PIECEJOINTE' => 'piste', 'EXTENSION_PIECEJOINTE' => '.json'], 'pieces-jointes', 1, true);

        if (!file_exists($filepath)) {
            return [];
        }

        $pisteData = file_get_contents($filepath);

        return json_decode($pisteData, true);
    }

    /**
     * Stocke le token PISTE pour le réutiliser tant qu'il n'est pas expiré.
     */
    private function storePisteToken(array $pisteData): void
    {
        $storedData = json_encode(array_merge_recursive($pisteData, ['request_time' => time()]), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        $filepath = $this->datastore->getFilePath(['ID_PIECEJOINTE' => 'piste', 'EXTENSION_PIECEJOINTE' => '.json'], 'pieces-jointes', 1, true);
        file_put_contents($filepath, $storedData);
    }

    /**
     * Détermine si le token est valide avec une marge de 5min.
     */
    private function isTokenValid(array $tokenData): bool
    {
        return time() < $tokenData['request_time'] + ($tokenData['expires_in'] - 300);
    }
}
