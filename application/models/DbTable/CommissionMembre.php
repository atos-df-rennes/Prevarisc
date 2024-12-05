<?php

class Model_DbTable_CommissionMembre extends Zend_Db_Table_Abstract
{
    // Nom de la base
    protected $_name = 'commissionmembre';

    // Clé primaire
    protected $_primary = ['ID_COMMISSIONMEMBRE'];

    /**
     * @param int|string $id_commission
     *
     * @return (null|mixed|Zend_Db_Table_Row_Abstract)[][]
     *
     * @psalm-return array<int, array{id_membre:string, presence:string, groupement:string, contacts:mixed, libelle:string, categories:mixed, classes:mixed, types:mixed, dossiertypes:mixed, dossiernatures:mixed, infos:null|Zend_Db_Table_Row_Abstract}>
     */
    public function get($id_commission): array
    {
        // Modéle de la commission
        $model_commission = new Model_DbTable_Commission();
        $model_utilisateurInformations = new Model_DbTable_UtilisateurInformations();

        // On récupére les membres de la commission
        $rowset_membresDeLaCommission = $this->fetchAll('ID_COMMISSION = '.$id_commission);

        // On initialise le tableau qui contiendra l'ensemble des critères
        $array_membres = [];

        // On récupère les informations de la commission
        $infos_commission = $model_commission->fetchRow('ID_COMMISSION = '.$id_commission);

        // Pour chaques régles, on va chercher les critéres
        foreach ($rowset_membresDeLaCommission as $row_membreDeLaCommission) {
            $array_membres[] = [
                'id_membre' => $row_membreDeLaCommission['ID_COMMISSIONMEMBRE'],
                'presence' => $row_membreDeLaCommission['PRESENCE_COMMISSIONMEMBRE'],
                'groupement' => $row_membreDeLaCommission['ID_GROUPEMENT'],
                'contacts' => $model_utilisateurInformations->getContact('commission', $id_commission),
                'libelle' => $row_membreDeLaCommission['LIBELLE_COMMISSIONMEMBRE'],
                'categories' => $this->fullJoinRegle('categorie', 'commissionmembrecategorie', 'ID_CATEGORIE', $row_membreDeLaCommission['ID_COMMISSIONMEMBRE']),
                'classes' => $this->fullJoinRegle('classe', 'commissionmembreclasse', 'ID_CLASSE', $row_membreDeLaCommission['ID_COMMISSIONMEMBRE']),
                'types' => $this->fullJoinRegle('typeactivite', 'commissionmembretypeactivite', 'ID_TYPEACTIVITE', $row_membreDeLaCommission['ID_COMMISSIONMEMBRE']),
                'dossiertypes' => $this->fullJoinRegle('dossiertype', 'commissionmembredossiertype', 'ID_DOSSIERTYPE', $row_membreDeLaCommission['ID_COMMISSIONMEMBRE']),
                'dossiernatures' => $this->fullJoinRegle('dossiernatureliste', 'commissionmembredossiernature', 'ID_DOSSIERNATURE', $row_membreDeLaCommission['ID_COMMISSIONMEMBRE']),
                'infos' => $infos_commission,
            ];
        }

        return $array_membres;
    }

    /**
     * @param float|int|string $id_membre
     */
    private function fullJoinRegle(string $first_table, string $second_table, string $key, $id_membre): array
    {
        // On fait une union entre ce qu'il y a dans la base et les critères enregistré
        $return = $this->fetchAll($this->select()->union([
            $this->select()->setIntegrityCheck(false)->from($first_table)->joinLeft($second_table, sprintf('%s.%s = %s.%s AND ID_COMMISSIONMEMBRE = %s', $first_table, $key, $second_table, $key, $id_membre)),
            $this->select()->setIntegrityCheck(false)->from($first_table)->joinRight($second_table, sprintf('%s.%s = %s.%s AND ID_COMMISSIONMEMBRE = %s', $first_table, $key, $second_table, $key, $id_membre)),
        ]))->toArray();

        // Requete sur la table finale
        $primary = $this->fetchAll($this->select()->setIntegrityCheck(false)->from($first_table))->toArray();

        // On limite les resultats
        $return = array_slice($return, 0, count($primary));

        // On rajoute les valeurs de toutes les clé primaires
        foreach (array_keys($return) as $pos) {
            $return[$pos][$key] = $primary[$pos][$key];
        }

        // Clé primaire en clé du tableau associatif
        return $this->mapResult($return, $key);
    }

    // Formaliser les resultats envoyés
    private function mapResult(array $array, string $key): array
    {
        $result = [];

        // On parcours le tableau
        foreach ($array as $value) {
            $result[$value[$key]] = $value;
        }

        return $result;
    }
}
