<?php

class Api_Service_Search
{
    /**
     * Recherche des établissements.
     *
     * @param string       $label
     * @param string       $identifiant
     * @param array|string $genres
     * @param array|string $categories
     * @param array|string $classes
     * @param array|string $familles
     * @param array|string $types_activites
     * @param bool         $avis_favorable
     * @param array|string $statuts
     * @param bool         $local_sommeil
     * @param float        $lon
     * @param float        $lat
     * @param int          $parent
     * @param null|mixed   $commissions
     * @param null|mixed   $groupements_territoriaux
     * @param null|mixed   $preventionniste
     * @param int          $count                    Par défaut 10, max 1000
     * @param int          $page                     par défaut = 1
     * @param null|mixed   $type
     * @param null|mixed   $adresse
     *
     * @return array
     */
    public function etablissements($label = null, $identifiant = null, $genres = null, $categories = null, $classes = null, $familles = null, $types_activites = null, $avis_favorable = null, $statuts = null, $type = null, $adresse = null, $local_sommeil = null, $lon = null, $lat = null, $parent = null, $commissions = null, $groupements_territoriaux = null, $preventionniste = null, $count = 10, $page = 1)
    {
        $service_search = new Service_Search();

        return $service_search->etablissements($label, $identifiant, $genres, $categories, $classes, $familles, $types_activites, $avis_favorable, $statuts, $type, $adresse, $local_sommeil, $lon, $lat, $parent, null, null, null, $commissions, $groupements_territoriaux, $preventionniste, $count, $page);
    }

    /**
     * Recherche des dossiers.
     *
     * @param array  $types
     * @param string $objet
     * @param string $num_doc_urba
     * @param int    $parent       Id d'un dossier parent
     * @param bool   $avis_differe Avis différé
     * @param int    $count        Par défaut 10, max 100
     * @param int    $page         par défaut = 1
     *
     * @return array
     */
    public function dossiers($types = null, $objet = null, $num_doc_urba = null, $parent = null, $avis_differe = null, $count = 10, $page = 1)
    {
        $service_search = new Service_Search();

        return $service_search->dossiers($types, $objet, $num_doc_urba, $parent, $avis_differe, $count, $page);
    }

    /**
     * Recherche des utilisateurs.
     *
     * @param array|string $fonctions
     * @param string       $name
     * @param array|int    $groups
     * @param bool         $actif     Optionnel
     * @param int          $count     Par défaut 10, max 100
     * @param int          $page      par défaut = 1
     *
     * @return array
     */
    public function users($fonctions = null, $name = null, $groups = null, $actif = true, $count = 10, $page = 1)
    {
        $service_search = new Service_Search();

        return $service_search->users($fonctions, $name, $groups, $actif, $count, $page);
    }
}
