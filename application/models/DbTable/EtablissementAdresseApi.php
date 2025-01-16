<?php

class Model_DbTable_EtablissementAdresseApi extends Zend_Db_Table_Abstract implements Service_Interface_EtablissementAdresse
{
    protected $_name = 'etablissementadresseapi';

    // Nom de la base
    protected $_primary = 'ID_ADRESSE'; // Clé primaire

    /**
     * @param float|int|string $id_etablissement
     */
    public function get($id_etablissement)
    {
        $model_etablissement = new Model_DbTable_Etablissement();
        $informations = $model_etablissement->getInformations($id_etablissement);

        switch ($informations->ID_GENRE) {
            // Adresse d'un site
            case 1:
                $search = new Model_DbTable_Search();

                $etablissement_enfants = $search->setItem('etablissement')
                    ->setCriteria('etablissementlie.ID_ETABLISSEMENT', $id_etablissement)
                    ->run()
                    ->getAdapter()
                    ->getItems(0, 99999999999)
                ;

                if (!empty($etablissement_enfants)) {
                    $i = 0;
                    foreach ($etablissement_enfants as $key => $ets) {
                        if (
                            ($ets['EFFECTIFPUBLIC_ETABLISSEMENTINFORMATIONS'] + $ets['EFFECTIFPERSONNEL_ETABLISSEMENTINFORMATIONS']) >
                            ($etablissement_enfants[$i]['EFFECTIFPUBLIC_ETABLISSEMENTINFORMATIONS'] + $etablissement_enfants[$i]['EFFECTIFPERSONNEL_ETABLISSEMENTINFORMATIONS'])
                        ) {
                            $i = $key;
                        }
                    }

                    return $this->get($etablissement_enfants[$i]['ID_ETABLISSEMENT']);
                }

                return [];

                // Adresse d'une cellule
            case 3:
                // Récupération des parents de l'établissement
                $results = [];
                $id_enfant = $id_etablissement;
                do {
                    $parent = $model_etablissement->getParent($id_enfant);
                    if (null != $parent) {
                        $results[] = $parent;
                        $id_enfant = $parent['ID_ETABLISSEMENT'];
                    }
                } while (null != $parent);

                $etablissement_parents = [] === $results ? [] : array_reverse($results);
                $pere = end($etablissement_parents);

                if ($pere) {
                    return $this->get($pere['ID_ETABLISSEMENT']);
                }

                return [];

                // Adresse par défaut
            default:
                $select = $this->select()
                    ->setIntegrityCheck(false)
                    ->from('etablissementadresseapi')
                    ->where('etablissementadresseapi.ID_ETABLISSEMENT = ?', $id_etablissement)
                ;

                return $this->fetchAll($select)->toArray();
        }
    }

    public function save($adresse, $etablissementID)
    {
        if ('' !== $adresse['ADRESSE']) {
            $row = $this->createRow([
                'ADRESSE' => $adresse['ADRESSE'],
                'LON_ETABLISSEMENTADRESSE' => empty($adresse['LON_ETABLISSEMENTADRESSE']) ? null : $adresse['LON_ETABLISSEMENTADRESSE'],
                'LAT_ETABLISSEMENTADRESSE' => empty($adresse['LAT_ETABLISSEMENTADRESSE']) ? null : $adresse['LAT_ETABLISSEMENTADRESSE'],
                'ID_ETABLISSEMENT' => $etablissementID,
                'NUMINSEE_COMMUNE' => $adresse['NUMINSEE_COMMUNE'],
                'CODEPOSTAL_COMMUNE' => $adresse['CODEPOSTAL_COMMUNE'],
                'LIBELLE_COMMUNE' => $adresse['LIBELLE_COMMUNE'],
                'LIBELLE_RUE' => $adresse['LIBELLE_RUE'],
            ]);

            return $row->save();
        }

        return null;
    }

    public function exists(array $data): bool
    {
        $select = $this->select()
            ->where('ADRESSE = ?', $data['ADRESSE'])
        ;

        return null !== $this->fetchRow($select);
    }
}
