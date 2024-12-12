<?php

class Model_DbTable_UtilisateurInformations extends Zend_Db_Table_Abstract
{
    // Nom de la base
    protected $_name = 'utilisateurinformations';

    // Clé primaire
    protected $_primary = 'ID_UTILISATEURINFORMATIONS';

    /**
     * @param mixed $item
     * @param mixed $id
     *
     * @return array
     */
    public function getContact($item, $id)
    {
        $select = $this->select()->setIntegrityCheck(false);

        // Initalisation des modèles
        switch ($item) {
            case 'etablissement':
                $select->from('etablissementcontact', [])
                    ->join('utilisateurinformations', 'utilisateurinformations.ID_UTILISATEURINFORMATIONS = etablissementcontact.ID_UTILISATEURINFORMATIONS')
                    ->join('fonction', 'utilisateurinformations.ID_FONCTION = fonction.ID_FONCTION', 'LIBELLE_FONCTION')
                    ->where(sprintf("etablissementcontact.ID_ETABLISSEMENT = '%s'", $id))
                    ->order('utilisateurinformations.NOM_UTILISATEURINFORMATIONS ASC')
                ;

                break;

            case 'dossier':
                $select->from('dossiercontact', [])
                    ->join('utilisateurinformations', 'utilisateurinformations.ID_UTILISATEURINFORMATIONS = dossiercontact.ID_UTILISATEURINFORMATIONS')
                    ->join('fonction', 'utilisateurinformations.ID_FONCTION = fonction.ID_FONCTION', 'LIBELLE_FONCTION')
                    ->where(sprintf("dossiercontact.ID_DOSSIER = '%s'", $id))
                    ->order('utilisateurinformations.NOM_UTILISATEURINFORMATIONS ASC')
                ;

                break;

            case 'groupement':
                $select->from('groupementcontact', [])
                    ->join('utilisateurinformations', 'utilisateurinformations.ID_UTILISATEURINFORMATIONS = groupementcontact.ID_UTILISATEURINFORMATIONS')
                    ->join('fonction', 'utilisateurinformations.ID_FONCTION = fonction.ID_FONCTION', 'LIBELLE_FONCTION')
                    ->where(sprintf("groupementcontact.ID_GROUPEMENT = '%s'", $id))
                    ->order('utilisateurinformations.NOM_UTILISATEURINFORMATIONS ASC')
                ;

                break;

            case 'commission':
                $select->from('commissioncontact', [])
                    ->join('utilisateurinformations', 'utilisateurinformations.ID_UTILISATEURINFORMATIONS = commissioncontact.ID_UTILISATEURINFORMATIONS')
                    ->join('fonction', 'utilisateurinformations.ID_FONCTION = fonction.ID_FONCTION', 'LIBELLE_FONCTION')
                    ->where(sprintf("commissioncontact.ID_COMMISSION = '%s'", $id))
                    ->order('utilisateurinformations.NOM_UTILISATEURINFORMATIONS ASC')
                ;

                break;

            default:
                break;
        }

        return $this->fetchAll($select)->toArray();
    }

    /**
     * @param float|int|string $name
     *
     * @return null|array
     */
    public function getAllContacts($name)
    {
        $q = sprintf('%%%s%%', $name);
        $select = $this->select()->setIntegrityCheck(false);
        $select->from('utilisateurinformations')
            ->where('NOM_UTILISATEURINFORMATIONS LIKE ? OR PRENOM_UTILISATEURINFORMATIONS LIKE ? OR SOCIETE_UTILISATEURINFORMATIONS LIKE ?', $q)
            ->where('ID_UTILISATEURINFORMATIONS NOT IN (SELECT ID_UTILISATEURINFORMATIONS FROM utilisateur)')
        ;

        return (null != $this->fetchAll($select)) ? $this->fetchAll($select)->toArray() : null;
    }
}
