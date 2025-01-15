<?php

class Service_Adresse
{
    public const LONGUEUR_CODE_POSTAL = 5;

    /**
     * Récupération de l'ensemble des communes.
     *
     * @return array
     */
    public function getAllCommunes()
    {
        $model_commune = new Model_DbTable_AdresseCommune();

        return $model_commune->fetchAll()->toArray();
    }

    /**
     * Récupération des communes via le nom ou le code postal.
     *
     * @param string $q Code postal ou nom d'une commune
     *
     * @return array
     */
    public function get($q)
    {
        if (self::LONGUEUR_CODE_POSTAL == strlen($q) && is_numeric($q)) {
            $DB_adresse = new Model_DbTable_EtablissementAdresse();

            return $DB_adresse->getVilleByCP($q);
        }

        $model_adresse = new Model_DbTable_AdresseCommune();

        return $model_adresse->get($q);
    }

    /**
     * Retourne les types de voie d'une commune identifiée par son code insee.
     *
     * @param int $code_insee
     *
     * @return array
     */
    public function getTypesVoieParVille($code_insee)
    {
        $DB_adresse = new Model_DbTable_EtablissementAdresse();

        return $DB_adresse->getTypesVoieByVille($code_insee);
    }

    /**
     * Retourne les voies par rapport à une ville.
     */
    public function getVoies(int $code_insee, string $q = ''): array
    {
        $DB_adresse = new Model_DbTable_EtablissementAdresse();

        return $DB_adresse->getVoies($code_insee, $q);
    }

    /**
     * Retourne les numéros par rapport à une voie.
     */
    public function getNumeros(int $id_rue): array
    {
        $DB_adresse = new Model_DbTable_EtablissementAdresse();

        return $DB_adresse->getNumeros($id_rue);
    }

    /**
     * Retourne le maire de la commune concernée.
     *
     * @param int $numinsee le numéro insee de la commune
     *
     * @return array les informations de la fiche contact du maire
     */
    public function getMaire($numinsee)
    {
        $select = new Zend_Db_Select(Zend_Controller_Front::getInstance()->getParam('bootstrap')->getResource('db'));

        $select->from(['ac' => 'adressecommune'])
            ->join(['ui' => 'utilisateurinformations'], 'ui.ID_UTILISATEURINFORMATIONS = ac.ID_UTILISATEURINFORMATIONS')
            ->join(['f' => 'fonction'], 'ui.ID_FONCTION = f.ID_FONCTION')
            ->where('ac.NUMINSEE_COMMUNE = ?', $numinsee)
            ->limit(1)
        ;

        return $select->query()->fetch();
    }

    public function getAdresseById($idAdresse)
    {
        $dbEtablissementAdresse = new Model_DbTable_EtablissementAdresse();

        return $dbEtablissementAdresse->getAdresse($idAdresse);
    }

    public function getLibelleCommune($code_insee)
    {
        $dbAdresseCommune = new Model_DbTable_AdresseCommune();

        return $dbAdresseCommune->getLibelleCommune($code_insee);
    }

    public function getLibelleRue($idRue)
    {
        $dbAdresseRue = new Model_DbTable_AdresseRue();

        return $dbAdresseRue->getLibelleRue($idRue);
    }
}
