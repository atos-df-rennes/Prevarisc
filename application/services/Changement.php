<?php

class Service_Changement
{
    /**
     * Définition des balises.
     */
    public const BALISES = [
        '{activitePrincipaleEtablissement}' => [
            'description' => "L'activité principale de l'établissement",
            'model' => 'informations',
            'champ' => 'LIBELLE_TYPEACTIVITE_PRINCIPAL',
        ],
        '{categorieEtablissement}' => [
            'description' => "La catégorie de l'etablissement",
            'model' => 'informations',
            'champ' => 'LIBELLE_CATEGORIE',
        ],
        '{etablissementAvis}' => [
            'description' => "L'avis de l'établissement",
            'model' => 'avis',
            'champ' => '',
        ],
        '{etablissementLibelle}' => [
            'description' => "Le libelle de l'établissement",
            'model' => 'informations',
            'champ' => 'LIBELLE_ETABLISSEMENTINFORMATIONS',
        ],
        '{etablissementNumeroId}' => [
            'description' => "Le numéro Id de l'établissement",
            'model' => 'general',
            'champ' => 'NUMEROID_ETABLISSEMENT',
        ],
        '{etablissementStatut}' => [
            'description' => "Le statut (Ouvert ou Fermé) de l'établissement",
            'model' => 'informations',
            'champ' => 'LIBELLE_STATUT',
        ],
        '{typePrincipalEtablissement}' => [
            'description' => "Le type principal de l'établissement",
            'model' => 'informations',
            'champ' => 'LIBELLE_TYPE_PRINCIPAL',
        ],
    ];

    public const ID_GENRE_CELLULE = 3;

    public const ID_AVIS_DEFAVORABLE = 2;

    /**
     * Retourne tous les enregistrement contenus dans la table changement.
     *
     * @return array Le résultat
     */
    public function getAll()
    {
        $dbChangement = new Model_DbTable_Changement();

        return $dbChangement->findAll();
    }

    /**
     *  Retourne un changement via son Id précisé en argument.
     *
     * @param int $idChangement L'id du changement à retourner
     *
     * @return Zend_Db_Table_Row_Abstract Le résultat
     */
    public function get($idChangement)
    {
        $dbChangement = new Model_DbTable_Changement();

        return $dbChangement->find($idChangement)->current();
    }

    /**
     * Sauvegarde les modifications apportées aux messages d'alerte
     * par défaut.
     *
     * @param array $data Les données envoyés en post
     */
    public function save($data): void
    {
        if (is_array($data)) {
            foreach ($data as $key => $message) {
                $idChangement = filter_var(explode('_', $key)[0], FILTER_VALIDATE_INT);
                $changement = $this->get($idChangement);
                $changement->MESSAGE_CHANGEMENT = $message;
                $changement->save();
            }
        }
    }

    /**
     *  Retourne le tableau de balises.
     *
     * @return string[][] Les balises définies dans cette classe
     *
     * @psalm-return array{{activitePrincipaleEtablissement}:array{description:string, model:string, champ:string}, {categorieEtablissement}:array{description:string, model:string, champ:string}, {etablissementAvis}:array{description:string, model:string, champ:string}, {etablissementLibelle}:array{description:string, model:string, champ:string}, {etablissementNumeroId}:array{description:string, model:string, champ:string}, {etablissementStatut}:array{description:string, model:string, champ:string}, {typePrincipalEtablissement}:array{description:string, model:string, champ:string}}
     */
    public function getBalises(): array
    {
        return self::BALISES;
    }

    /**
     * Retourne l'objet du mail de changement formaté.
     *
     * @param int   $idChangement Id du changement
     * @param array $ets          Etablissement concerné
     *
     * @return string L'objet formaté
     */
    public function getObjet($idChangement, array $ets): string
    {
        switch ($idChangement) {
            case '1':
                $objet = sprintf(
                    'Passage au statut "%s"',
                    $ets['informations']['LIBELLE_STATUT']
                );

                break;

            case '2':
                $objet = sprintf(
                    'Passage en avis "%s"',
                    $this->getAvis($ets)
                );

                break;

            case '3':
                $objet = sprintf(
                    'Changement de classement "%s - %s %s"',
                    $ets['informations']['LIBELLE_CATEGORIE'],
                    $ets['informations']['LIBELLE_TYPE_PRINCIPAL'],
                    $ets['informations']['LIBELLE_TYPEACTIVITE_PRINCIPAL']
                );

                break;

            default:
                $objet = '';
        }

        $commune = '';
        if (count($ets['adresses']) > 0) {
            $commune = $ets['adresses'][0]['LIBELLE_COMMUNE'];
        }

        $libelleInfos = $ets['informations']['LIBELLE_ETABLISSEMENTINFORMATIONS'];
        if (count($ets['parents']) > 0) {
            $libelleInfos = sprintf(
                '%s %s',
                $ets['parents'][0]['LIBELLE_ETABLISSEMENTINFORMATIONS'],
                $libelleInfos
            );
        }

        return sprintf(
            '%s (%s) - %s',
            $libelleInfos,
            $commune,
            $objet
        );
    }

    /**
     * Convertit les balises dans le message avec les bonnes valeurs.
     *
     * @param string $message Le message a envoyer avec des balises
     *
     * @return string Le message convertit
     */
    public function convertMessage($message, array $ets): string
    {
        $params = [];
        foreach (self::BALISES as $balise => $content) {
            $replacementstr = '';
            if ('avis' === $content['model']) {
                $replacementstr = $this->getAvis($ets);
            } elseif (array_key_exists($content['model'], $ets)
                && array_key_exists($content['champ'], $ets[$content['model']])) {
                $replacementstr = $ets[$content['model']][$content['champ']];
            }

            $params[$balise] = $replacementstr;
        }

        return strtr($message, $params);
    }

    /**
     * Retourne l'avis d'un établissement formaté.
     *
     * @param array $ets L'établissement
     *
     * @return string L'avis de l'établissement
     */
    public function getAvis(array $ets): string
    {
        $avis = '';
        $serviceEts = new Service_Etablissement();
        $avisType = $serviceEts->getAvisEtablissement(
            $ets['general']['ID_ETABLISSEMENT'],
            $ets['general']['ID_DOSSIER_DONNANT_AVIS']
        );

        if (true == $ets['presence_avis_differe'] && 'avisDiff' === $avisType) {
            $avis = "Présence d'un dossier avec avis differé";
        } elseif (null != $ets['avis']) {
            if (1 == $ets['avis'] && 'avisDoss' === $avisType) {
                $avis = 'Favorable'.(self::ID_GENRE_CELLULE == $ets['informations']['ID_GENRE'] ? '' : " à l'exploitation");
            } elseif (self::ID_AVIS_DEFAVORABLE == $ets['avis'] && 'avisDoss' === $avisType) {
                $avis = 'Défavorable'.(self::ID_GENRE_CELLULE == $ets['informations']['ID_GENRE'] ? '' : " à l'exploitation");
            }
        } else {
            $avis = "Avis d'exploitation indisponible";
        }

        return $avis;
    }
}
