<?php

class Api_Service_Etablissement
{
    /**
     * Retourne un seul établissement identifié par le paramètre id.
     *
     * @param int $id
     *
     * @return array
     */
    public function get($id)
    {
        $service_etablissement = new Service_Etablissement();

        return $service_etablissement->get($id);
    }

    /**
     * Retourne l'historique complet d'un établissement identifié par le paramètre id.
     *
     * @param int $id
     */
    public function getHistorique($id): array
    {
        $service_etablissement = new Service_Etablissement();

        return $service_etablissement->getHistorique($id);
    }

    /**
     * Retourne les descriptifs d'un établissement identifié par le paramètre id.
     *
     * @param int $id
     */
    public function getDescriptifs($id): array
    {
        $service_etablissement = new Service_Etablissement();

        return $service_etablissement->getDescriptifs($id);
    }

    /**
     * Retourne les textes applicables d'un établissement identifié par le paramètre id.
     */
    public function getTextesApplicables(int $id): array
    {
        $service_etablissement = new Service_Etablissement();

        return $service_etablissement->getAllTextesApplicables($id);
    }

    /**
     * Retourne les pièces jointes d'un établissement identifié par le paramètre id.
     *
     * @param int $id
     *
     * @return array
     */
    public function getPiecesJointes($id)
    {
        $service_etablissement = new Service_Etablissement();

        return $service_etablissement->getAllPJ($id);
    }

    /**
     * Retourne les pièces jointes d'un établissement identifié par le paramètre id.
     *
     * @param int $id
     *
     * @return (mixed|string)[][]
     *
     * @psalm-return array<int, array{ID_PIECE_JOINTE:mixed, IMAGE:string}>
     */
    public function getPiecesJointesContent($id): array
    {
        $service_etablissement = new Service_Etablissement();
        $pieces_jointes = $service_etablissement->getAllPJ($id);

        $store = Zend_Controller_Front::getInstance()->getParam('bootstrap')->getResource('dataStore');
        $pieces_jointes_content = [];

        foreach ($pieces_jointes as $pieces_jointe) {
            $path = $store->getFilePath($pieces_jointe, 'etablissement', $id);
            $pieces_jointes_content[] = [
                'ID_PIECE_JOINTE' => $pieces_jointe['ID_PIECEJOINTE'],
                'IMAGE' => base64_encode(file_get_contents($path)),
            ];
        }

        return $pieces_jointes_content;
    }

    /**
     * Retourne lles contacts d'un établissement identifié par le paramètre id.
     *
     * @param int $id
     *
     * @return array
     */
    public function getContacts($id)
    {
        $service_etablissement = new Service_Etablissement();

        return $service_etablissement->getAllContacts($id);
    }

    /**
     * Retourne les dossiers d'un établissement identifié par le paramètre id.
     *
     * @param int $id
     */
    public function getDossiers($id): array
    {
        $service_etablissement = new Service_Etablissement();

        return $service_etablissement->getDossiers($id);
    }

    /**
     * Retourne les valeurs par défauts (périodicité, commission, préventionnistes) pour un établissement en fonction des paramètres données.
     *
     * @param int   $genre
     * @param int   $numinsee
     * @param int   $type
     * @param int   $categorie
     * @param bool  $local_sommeil
     * @param int   $classe
     * @param int   $id_etablissement_pere
     * @param array $ids_etablissements_enfants
     */
    public function getDefaultValues($genre, $numinsee = null, $type = null, $categorie = null, $local_sommeil = null, $classe = null, $id_etablissement_pere = null, $ids_etablissements_enfants = null): array
    {
        $service_etablissement = new Service_Etablissement();

        return $service_etablissement->getDefaultValues($genre, $numinsee, $type, $categorie, $local_sommeil, $classe, $id_etablissement_pere, $ids_etablissements_enfants);
    }

    public function getAdresse(int $id)
    {
        $serviceAdresse = new Service_Adresse();

        return $serviceAdresse->getAdresseById($id);
    }
}
