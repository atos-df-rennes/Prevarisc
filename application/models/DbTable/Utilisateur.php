<?php

class Model_DbTable_Utilisateur extends Zend_Db_Table_Abstract
{
    /**
     * @var mixed|Zend_Db_Select
     */
    public $select;

    protected $_name = 'utilisateur';

    protected $_primary = 'ID_UTILISATEUR';

    public function getDroits($id_user)
    {
        $auth = Zend_Auth::getInstance()->getIdentity();

        // modèle
        $model_groupes = new Model_DbTable_Groupe();

        // Récupération du groupe de l'user
        if ((is_array($auth) && $auth['ID_UTILISATEUR'] != $id_user)
            || (is_object($auth) && $auth->ID_UTILISATEUR != $id_user)) {
            $id_groupe = $this->find($id_user)->current()->ID_GROUPE;
        } else {
            $id_groupe = is_array($auth) ? $auth['ID_GROUPE'] : $auth->ID_GROUPE;
        }

        // On retourne les droits de l'user
        return $model_groupes->getDroits($id_groupe);
    }

    /**
     * @param null|mixed $group
     *
     * @return array
     */
    public function getUsersWithInformations($group = null)
    {
        $this->select = $this->select()->setIntegrityCheck(false);
        $select = $this->select
            ->from(['u' => 'utilisateur'], [
                'uid' => 'ID_UTILISATEUR',
                'ID_UTILISATEUR',
                'USERNAME_UTILISATEUR',
                'PASSWD_UTILISATEUR',
                'ID_UTILISATEURINFORMATIONS',
                'ACTIF_UTILISATEUR',
                'ID_GROUPE',
                'LASTACTION_UTILISATEUR',
            ])
            ->join('utilisateurinformations', 'u.ID_UTILISATEURINFORMATIONS = utilisateurinformations.ID_UTILISATEURINFORMATIONS')
            ->join('fonction', 'utilisateurinformations.ID_FONCTION = fonction.ID_FONCTION')
            ->order('utilisateurinformations.NOM_UTILISATEURINFORMATIONS ASC')
        ;

        if (!empty($group)) {
            $select->where('ID_GROUPE = '.$group);
        }

        return $this->fetchAll($select)->toArray();
    }

    public function isRegistered($id_user, $login): bool
    {
        $select = $this->select()
            ->from('utilisateur')
            ->where('USERNAME_UTILISATEUR = ?', $login)
        ;
        if (null !== $id_user) {
            $select->where('ID_UTILISATEUR <> ?', $id_user);
        }

        $select->limit(1);

        $result = $this->fetchRow($select);

        return null != $result;
    }

    public function getId($login)
    {
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from('utilisateur', 'ID_UTILISATEUR')
            ->where('USERNAME_UTILISATEUR = ?', $login)
            ->limit(1)
        ;

        $result = $this->fetchRow($select);

        return (null != $result) ? $result->ID_UTILISATEUR : null;
    }

    /**
     * @param mixed $id
     *
     * @return Zend_Db_Table_Rowset_Abstract
     */
    public function getCommissions($id)
    {
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from('utilisateurcommission', [])
            ->join('commission', 'commission.ID_COMMISSION = utilisateurcommission.ID_COMMISSION')
            ->join('commissiontype', 'commission.ID_COMMISSIONTYPE = commissiontype.ID_COMMISSIONTYPE')
            ->where('ID_UTILISATEUR = ?', $id)
        ;

        return $this->fetchAll($select);
    }

    /**
     * @psalm-return array<int, mixed>
     *
     * @param mixed $id
     */
    public function getCommissionsArray($id): array
    {
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from('utilisateurcommission', 'ID_COMMISSION')
            ->where('ID_UTILISATEUR = ?', $id)
        ;

        $all = $this->fetchAll($select);

        if (null == $all) {
            return [];
        }

        $all = $all->toArray();
        $result = [];
        foreach ($all as $row) {
            $result[] = $row['ID_COMMISSION'];
        }

        return $result;
    }

    /**
     * @param mixed $id
     *
     * @return Zend_Db_Table_Rowset_Abstract
     */
    public function getGroupements($id)
    {
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from('utilisateurgroupement', [])
            ->join('groupement', 'groupement.ID_GROUPEMENT = utilisateurgroupement.ID_GROUPEMENT')
            ->where('ID_UTILISATEUR = ?', $id)
        ;

        return $this->fetchAll($select);
    }

    /**
     * @psalm-return array<int, mixed>
     *
     * @param mixed $id
     */
    public function getVillesDeSesGroupements($id): array
    {
        $model_groupementCommune = new Model_DbTable_GroupementCommune();

        $rowset_groupements = $this->getGroupements($id);

        $villes = [];

        // pr chq gpt on prend ses ville qu'on met ds un tableau
        foreach ($rowset_groupements as $row_groupement) {
            foreach ($model_groupementCommune->find($row_groupement->ID_GROUPEMENT) as $row) {
                $villes[] = $row->NUMINSEE_COMMUNE;
            }
        }

        // on enlève les doublons
        return array_unique($villes);
    }

    /**
     * @return array
     */
    public function getGroupPrivileges(array $user)
    {
        // Récupération des données utilisateur

        $group = $user['group']['ID_GROUPE'];

        $groupements = (array) $user['groupements'];
        array_walk($groupements, function (&$val, $key) use (&$groupements): void {
            $val = $groupements[$key]['ID_GROUPEMENT'];
        });
        $groupements = implode('-', $groupements);

        $commissions = (array) $user['commissions'];
        array_walk($commissions, function (&$val, $key) use (&$commissions): void {
            $val = $commissions[$key]['ID_COMMISSION'];
        });
        $commissions = implode('-', $commissions);

        $commune = $user['NUMINSEE_COMMUNE'];

        // Récupération depuis la base des ressources / privileges du groupe de l'utilisateur

        $this->select = $this->select()->setIntegrityCheck(false);
        $select = $this->select->from('groupe-privileges', [])
            ->join('privileges', '`groupe-privileges`.id_privilege = privileges.id_privilege', ['name_privilege' => 'name'])
            ->join('resources', 'privileges.id_resource = resources.id_resource', ['name_resource' => 'name'])
            ->where('`groupe-privileges`.ID_GROUPE = ?', $group)
        ;

        $privileges = $this->fetchAll($select)->toArray();

        // On créé une fonction spéciale pour convertir les ressources retravaillées

        $develop_resources =
        function (&$list_resources_finale) use (&$develop_resources): array {
            $list_resources_finaleCount = count($list_resources_finale);
            for ($i = 0; $i < $list_resources_finaleCount; ++$i) {
                $resource_exploded = explode('_', $list_resources_finale[$i]);
                $resource_explodedCount = count($resource_exploded);
                for ($j = 0; $j < $resource_explodedCount; ++$j) {
                    if (count(explode('-', $resource_exploded[$j])) > 1) {
                        $resource_exploded2 = explode('-', $resource_exploded[$j]);
                        foreach ($resource_exploded2 as $singleResource_exploded2) {
                            $name = explode('_', $list_resources_finale[$i]);
                            $name[$j] = $singleResource_exploded2;
                            $list_resources_finale[] = implode('_', $name);
                        }

                        unset($list_resources_finale[$i]);
                        $list_resources_finale = array_unique($list_resources_finale);
                        $list_resources_finale = array_values($list_resources_finale);
                        $develop_resources($list_resources_finale);
                    }
                }
            }

            return array_unique($list_resources_finale);
        };

        // Spécialisation des ressources pour l'utilisateur

        foreach ($privileges as $key => $resource) {
            if ('etablissement' === explode('_', $resource['name_resource'])[0]) {
                $resource_exploded = explode('_', $resource['name_resource']);

                switch ($resource_exploded[1]) {
                    case 'erp':
                        if ('1' === $resource_exploded[4]) {
                            $resource_exploded[4] = $commissions;
                        }

                        if ('1' === $resource_exploded[5]) {
                            $resource_exploded[5] = $groupements;
                        }

                        if ('1' === $resource_exploded[6]) {
                            $resource_exploded[6] = $commune;
                        }

                        break;

                    case 'hab':
                    case 'zone':
                        if ('1' === $resource_exploded[3]) {
                            $resource_exploded[3] = $groupements;
                        }

                        if ('1' === $resource_exploded[4]) {
                            $resource_exploded[4] = $commune;
                        }

                        break;

                    case 'igh':
                        if ('1' === $resource_exploded[3]) {
                            $resource_exploded[3] = $commissions;
                        }

                        if ('1' === $resource_exploded[4]) {
                            $resource_exploded[4] = $groupements;
                        }

                        if ('1' === $resource_exploded[5]) {
                            $resource_exploded[5] = $commune;
                        }

                        break;

                    case 'eic':
                    case 'camp':
                    case 'temp':
                    case 'iop':
                        if ('1' === $resource_exploded[2]) {
                            $resource_exploded[2] = $groupements;
                        }

                        if ('1' === $resource_exploded[3]) {
                            $resource_exploded[3] = $commune;
                        }

                        break;

                    default:
                        break;
                }

                $resource_imploded = implode('_', $resource_exploded);
                $resource_tmp = [$resource_imploded];
                $develop_resources($resource_tmp);

                $privileges[] = ['name_privilege' => $resource['name_privilege'], 'name_resource' => $resource_imploded];

                unset($privileges[$key]);
            }
        }

        return $privileges;
    }

    /**
     * Retourne une liste d'utilisateur ayant les droits
     * de recevoir les type d'alerte (changement de statut, avis, catégorie)
     * et étant concerné par la commune ou le groupement de commune de l'établissement.
     *
     * @param int   $idChangement L'id du type de changement
     * @param array $ets          L'établissement concerné par le changement
     *
     * @return array Liste d'utilisateur
     */
    public function findUtilisateursForAlerte($idChangement, array $ets)
    {
        switch ($idChangement) {
            case '1':
                $privilege = 'alerte_statut';

                break;

            case '2':
                $privilege = 'alerte_avis';

                break;

            case '3':
                $privilege = 'alerte_classement';

                break;

            default:
                $privilege = 'alerte_statut';
        }

        $numinsee = '';
        if (count($ets['adresses']) > 0) {
            $numinsee = $ets['adresses'][0]['NUMINSEE_COMMUNE'];
        }

        $selectPrivilegeQuery = $this->select()->setIntegrityCheck(false)
            ->from(['p' => 'privileges'], ['p.id_privilege'])
            ->where('name = ?', $privilege)
            ->limit(1)
        ;

        $selectCommune = $this->select()->setIntegrityCheck(false)
            ->from(['u' => 'utilisateur'], ['ID_UTILISATEUR'])
            ->join(
                ['ui' => 'utilisateurinformations'],
                'ui.ID_UTILISATEURINFORMATIONS = u.ID_UTILISATEURINFORMATIONS',
                ['ui.NOM_UTILISATEURINFORMATIONS', 'ui.PRENOM_UTILISATEURINFORMATIONS',
                    'ui.MAIL_UTILISATEURINFORMATIONS', ]
            )
            ->join(['g' => 'groupe'], 'g.ID_GROUPE = u.ID_GROUPE', [])
            ->join(['gp' => 'groupe-privileges'], 'gp.ID_GROUPE = g.ID_GROUPE', [])
            ->where('ui.MAIL_UTILISATEURINFORMATIONS IS NOT NULL')
            ->where('ui.MAIL_UTILISATEURINFORMATIONS <> ?', '')
            ->where('gp.id_privilege = ('.$selectPrivilegeQuery.')')
            ->where('u.NUMINSEE_COMMUNE = ?', $numinsee)
            ->group('u.ID_UTILISATEUR')
        ;

        $selectGroupement = $this->select()->setIntegrityCheck(false)
            ->from(['u' => 'utilisateur'], ['ID_UTILISATEUR'])
            ->join(
                ['ui' => 'utilisateurinformations'],
                'ui.ID_UTILISATEURINFORMATIONS = u.ID_UTILISATEURINFORMATIONS',
                ['ui.NOM_UTILISATEURINFORMATIONS', 'ui.PRENOM_UTILISATEURINFORMATIONS',
                    'ui.MAIL_UTILISATEURINFORMATIONS', ]
            )
            ->join(['g' => 'groupe'], 'g.ID_GROUPE = u.ID_GROUPE', [])
            ->join(['gp' => 'groupe-privileges'], 'gp.ID_GROUPE = g.ID_GROUPE', [])
            ->join(['ug' => 'utilisateurgroupement'], 'ug.ID_UTILISATEUR = u.ID_UTILISATEUR', [])
            ->join(['gc' => 'groupementcommune'], 'gc.ID_GROUPEMENT = ug.ID_GROUPEMENT', [])
            ->where('ui.MAIL_UTILISATEURINFORMATIONS IS NOT NULL')
            ->where('ui.MAIL_UTILISATEURINFORMATIONS <> ?', '')
            ->where('gp.id_privilege = ('.$selectPrivilegeQuery.')')
            ->where('gc.NUMINSEE_COMMUNE = ?', $numinsee)
            ->group('u.ID_UTILISATEUR')
        ;

        $selectUnion = $this->select()
            ->union([$selectCommune, $selectGroupement])
        ;

        return $this->fetchAll($selectUnion)->toArray();
    }
}
