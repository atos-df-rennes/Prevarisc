<?php

class Service_Utils
{
    public function getFullFusionName(string $capsuleRubrique, array $options): string
    {
        $params = [];
        $params[] = $capsuleRubrique;

        $options = array_map(function (string $option): string {
            return $this->formatFusionName($option);
        }, $options);

        $params = array_merge($params, $options);

        return implode('-', $params);
    }

    public function getFusionNameMagicalCase(string $name): string
    {
        $fusionName = '';
        $strings = preg_split("/[\\s'\"\\/]+/", $name);

        foreach ($strings as $string) {
            $fusionName .= ucfirst(strtolower($string));
        }

        return $fusionName;
    }

    public static function getPjPath(array $pjData): string
    {
        if (null !== $pjData['ID_PLATAU']) {
            return
                implode(DS, [
                    REAL_DATA_PATH,
                    'uploads',
                    'pieces-jointes',
                    $pjData['ID_PIECEJOINTE'].$pjData['EXTENSION_PIECEJOINTE'],
                ]);
        }

        $store = Zend_Controller_Front::getInstance()->getParam('bootstrap')->getResource('dataStore');

        return $store->getFilePath($pjData, 'dossier', $pjData['ID_DOSSIER']);
    }

    private function formatFusionName(string $name): string
    {
        $trimmedName = trim($name);
        $loweredName = strtolower($trimmedName);

        return preg_replace(['/\s+/', "/'+/", '/\"+/'], '_', $loweredName);
    }
}
