<?php

class Model_DbTable_Periodicite extends Zend_Db_Table_Abstract
{
    public const ID_GENRE_ETABLISSEMENT = 2;

    // Nom de la base
    protected $_name = 'periodicite';

    // Clé primaire
    protected $_primary = ['ID_CATEGORIE', 'ID_TYPE', 'LOCALSOMMEIL_PERIODICITE'];

    public function gn4($categorie, $type, $local_sommeil): string
    {
        // On check la périodicité par rapport au GE4
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from('periodicite', 'PERIODICITE_PERIODICITE')
            ->where('ID_CATEGORIE = ?', (int) $categorie)
            ->where('ID_TYPE = ?', (int) $type)
            ->where('LOCALSOMMEIL_PERIODICITE = ?', $local_sommeil)
        ;

        // Retourne le résultat
        return $this->getAdapter()->fetchOne($select);
    }

    public function gn4ForEtablissement(array $etablissement): ?string
    {
        $informations = $etablissement['informations'];
        if (!in_array($informations['ID_GENRE'], [2, 5])) {
            return null;
        }

        $type = self::ID_GENRE_ETABLISSEMENT == $informations['ID_GENRE'] ? $informations['ID_TYPE'] : $informations['ID_CLASSE'];

        return $this->gn4($informations['ID_CATEGORIE'], $type, $informations['LOCALSOMMEIL_ETABLISSEMENTINFORMATIONS'] ? 1 : 0);
    }

    public function apply(): void
    {
        $sql = 'UPDATE etablissementinformations
                INNER JOIN periodicite ON periodicite.ID_CATEGORIE = etablissementinformations.ID_CATEGORIE AND periodicite.ID_TYPE = etablissementinformations.ID_TYPE AND periodicite.LOCALSOMMEIL_PERIODICITE = etablissementinformations.LOCALSOMMEIL_ETABLISSEMENTINFORMATIONS
                SET PERIODICITE_ETABLISSEMENTINFORMATIONS = periodicite.PERIODICITE_PERIODICITE
                WHERE etablissementinformations.ID_ETABLISSEMENTINFORMATIONS IN (
                    SELECT * FROM (
                        SELECT ID_ETABLISSEMENTINFORMATIONS
                        FROM etablissementinformations
                        INNER JOIN etablissement ON etablissementinformations.ID_ETABLISSEMENT = etablissement.ID_ETABLISSEMENT
                        WHERE etablissementinformations.DATE_ETABLISSEMENTINFORMATIONS = ( SELECT MAX(DATE_ETABLISSEMENTINFORMATIONS) FROM etablissementinformations WHERE etablissementinformations.ID_ETABLISSEMENT = etablissement.ID_ETABLISSEMENT)
                    ) AS X
                )';

        $db = Zend_Db_Table::getDefaultAdapter();
        $db->beginTransaction();

        try {
            $db->getConnection()->query($sql);
            $db->getConnection()->commit();
        } catch (Exception $exception) {
            $db->rollBack();

            throw $exception;
        }
    }
}
