<?php

class Model_DbTable_CommissionRegle extends Zend_Db_Table_Abstract
{
    // Nom de la base
    protected $_name = 'commissionregle';

    // Clé primaire
    protected $_primary = ['ID_REGLE'];

    /**
     * @param int|string $id_commission
     *
     * @return (null|mixed|Zend_Db_Table_Row_Abstract)[][]
     *
     * @psalm-return array<int, array{id_regle:string, commune:null|Zend_Db_Table_Row_Abstract, groupement:string, categories:mixed, classes:mixed, types:mixed, local_sommeil:mixed, etude_visite:mixed, infos:null|Zend_Db_Table_Row_Abstract}>
     */
    public function get($id_commission): array
    {
        // Modèle de la commission
        $model_commission = new Model_DbTable_Commission();
        $model_commune = new Model_DbTable_AdresseCommune();

        // On récupére les règles de la commission
        $rowset_reglesDeLaCommission = $this->fetchAll('ID_COMMISSION = '.$id_commission);

        // On initialise le tableau qui contiendra l'ensemble des critères
        $array_regles = [];

        // Pour chaque règle, on va chercher les critères
        foreach ($rowset_reglesDeLaCommission as $row_regleDeLaCommission) {
            $array_regles[] = [
                'id_regle' => $row_regleDeLaCommission['ID_REGLE'],
                'commune' => ('' !== $row_regleDeLaCommission['NUMINSEE_COMMUNE'] && null !== $row_regleDeLaCommission['NUMINSEE_COMMUNE']) ? $model_commune->fetchRow('NUMINSEE_COMMUNE = '.$row_regleDeLaCommission['NUMINSEE_COMMUNE']) : null,
                'groupement' => $row_regleDeLaCommission['ID_GROUPEMENT'],
                'categories' => $this->fullJoinRegle('categorie', 'commissionreglecategorie', 'ID_CATEGORIE', $row_regleDeLaCommission['ID_REGLE']),
                'classes' => $this->fullJoinRegle('classe', 'commissionregleclasse', 'ID_CLASSE', $row_regleDeLaCommission['ID_REGLE']),
                'types' => $this->fullJoinRegle('type', 'commissionregletype', 'ID_TYPE', $row_regleDeLaCommission['ID_REGLE']),
                'local_sommeil' => $this->mapResult($this->fetchAll($this->select()->setIntegrityCheck(false)->from('commissionreglelocalsommeil')->where('ID_REGLE = '.$row_regleDeLaCommission['ID_REGLE']))->toArray(), 'LOCALSOMMEIL'),
                'etude_visite' => $this->mapResult($this->fetchAll($this->select()->setIntegrityCheck(false)->from('commissionregleetudevisite')->where('ID_REGLE = '.$row_regleDeLaCommission['ID_REGLE']))->toArray(), 'ETUDEVISITE'),
                'infos' => $model_commission->fetchRow('ID_COMMISSION = '.$id_commission),
            ];
        }

        return $array_regles;
    }

    /**
     * @param float|int|string $id_regle
     */
    private function fullJoinRegle(string $first_table, string $second_table, string $key, $id_regle): array
    {
        // On fait une union entre ce qu'il y a dans la base et les critères enregistrés
        $return = $this->fetchAll($this->select()->union([
            $this->select()->setIntegrityCheck(false)->from($first_table)->joinLeft($second_table, sprintf('%s.%s = %s.%s AND ID_REGLE = %s', $first_table, $key, $second_table, $key, $id_regle)),
            $this->select()->setIntegrityCheck(false)->from($first_table)->joinRight($second_table, sprintf('%s.%s = %s.%s AND ID_REGLE = %s', $first_table, $key, $second_table, $key, $id_regle)),
        ]))->toArray();

        // Requete sur la table finale
        $primary = $this->fetchAll($this->select()->setIntegrityCheck(false)->from($first_table))->toArray();

        // On limite les resultats
        $return = array_slice($return, 0, count($primary));

        // On rajoute les valeurs de toutes les clé primaires
        foreach (array_keys($return) as $pos) {
            $return[$pos][$key] = $primary[$pos][$key];
        }

        // On envoi le tout
        return $return;
    }

    // Formaliser les resultats envoyés

    /**
     * @param mixed $array
     */
    private function mapResult($array, string $key): array
    {
        $result = [];

        // On parcours le tableau
        foreach ($array as $value) {
            $result[] = $value[$key];
        }

        return $result;
    }
}
