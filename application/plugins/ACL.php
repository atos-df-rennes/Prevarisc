<?php

class Plugin_ACL extends Zend_Controller_Plugin_Abstract
{
    private static $acl;

    public static function getAcl()
    {
        if (null == self::$acl) {
            // Chargement du cache
            $cache = Zend_Controller_Front::getInstance()->getParam('bootstrap')->getResource('cache');

            // Chargement des ACL
            if (($acl = unserialize($cache->load('acl'))) === false) {
                // Liste des ressources
                $resources_dbtable = new Model_DbTable_Resource();
                $privileges_dbtable = new Model_DbTable_Privilege();
                $groupes_dbtable = new Model_DbTable_Groupe();

                // Création de l'ACL (sans les établissements et les dossiers)
                $acl = new Zend_Acl();

                // On assigne les roles, ressources et privilèges à l'ACL
                foreach ($groupes_dbtable->fetchAll() as $role) {
                    if (!$acl->hasRole($role->LIBELLE_GROUPE)) {
                        $acl->addRole($role->LIBELLE_GROUPE);
                    }

                    $privileges_role = $role->findModel_DbTable_PrivilegeViaModel_DbTable_GroupePrivilege()->toArray();
                    array_walk($privileges_role, function (&$val, $key) use (&$privileges_role): void {
                        $val = $privileges_role[$key]['id_privilege'];
                    });

                    foreach ($resources_dbtable->fetchAll()->toArray() as $resource) {
                        if ('etablissement' === explode('_', $resource['name'])[0]) {
                            continue;
                        }

                        if (!$acl->has($resource['name'])) {
                            $acl->add(new Zend_Acl_Resource($resource['name']));
                        }

                        $privileges = $privileges_dbtable->fetchAll('id_resource = '.$resource['id_resource'])->toArray();

                        foreach ($privileges as $privilege) {
                            if (in_array($privilege['id_privilege'], $privileges_role)) {
                                $acl->allow($role->LIBELLE_GROUPE, $resource['name'], $privilege['name']);
                            } else {
                                $acl->deny($role->LIBELLE_GROUPE, $resource['name'], $privilege['name']);
                            }
                        }
                    }
                }

                // Sauvegarde en cache
                $cache->save(serialize($acl));
            }

            self::$acl = $acl;
        }

        return self::$acl;
    }

    public function preDispatch(Zend_Controller_Request_Abstract $request): void
    {
        // Si l'utilisateur est connecté avec l'application mobile, on utilise le partage d'un token
        if ($request->getParam('key') === getenv('PREVARISC_SECURITY_KEY')) {
            return;
        }

        if (1 == getenv('PREVARISC_CAS_ENABLED')) {
            if (1 == getenv('PREVARISC_DEBUG_ENABLED')) {
                // Enable debugging
                phpCAS::setDebug();
                // Enable verbose error messages. Disable in production!
                phpCAS::setVerbose(true);
            }

            // Initialize phpCAS
            if (!phpCAS::isInitialized()) {
                phpCAS::client(getenv('PREVARISC_CAS_VERSION') ?: CAS_VERSION_2_0, getenv('PREVARISC_CAS_HOST'), (int) getenv('PREVARISC_CAS_PORT'), getenv('PREVARISC_CAS_CONTEXT'), false);

                phpCAS::setLang(PHPCAS_LANG_FRENCH);
            }

            if (1 == getenv('PREVARISC_CAS_NO_SERVER_VALIDATION')) {
                phpCAS::setNoCasServerValidation();
            }

            // force CAS authentication
            phpCAS::forceAuthentication();
        }

        // Si l'utilisateur n'est pas connecté, alors on le redirige vers la page de login (si il ne s'y trouve pas encore)
        if (!Zend_Auth::getInstance()->hasIdentity() && !in_array($request->getActionName(), ['login', 'error'])) {
            $redirect = '/' == $_SERVER['REQUEST_URI'] ? [] : ['redirect' => urlencode($_SERVER['REQUEST_URI'])];
            $redirector = Zend_Controller_Action_HelperBroker::getStaticHelper('redirector')->gotoSimple('login', 'session', 'default', $redirect);
        } elseif (Zend_Auth::getInstance()->hasIdentity()) {
            $service_user = new Service_User();

            // On update la dernière action effectuée par l'utilisateur
            $utilisateur = Zend_Auth::getInstance()->getIdentity();
            if (!$utilisateur) {
                $request->setControllerName('error');
                $request->setActionName('error');
                $error = new ArrayObject([], ArrayObject::ARRAY_AS_PROPS);
                $error->type = Zend_Controller_Plugin_ErrorHandler::EXCEPTION_OTHER;
                $error->request = clone $request;
                $error->exception = new Zend_Controller_Dispatcher_Exception('Accès non autorisé', 401);
                $request->setParam('error_handler', $error);

                return;
            }

            $service_user->updateLastActionDate($utilisateur['ID_UTILISATEUR']);

            $acl = self::getAcl();

            // On adapte les ressources en fonction de l'utilisateur pour la page établissement
            if ('etablissement' == $request->getControllerName() || 'dossier' == $request->getControllerName()) {
                // Liste des ressources
                $resources_dbtable = new Model_DbTable_Resource();
                $privileges_dbtable = new Model_DbTable_Privilege();
                $groupes_dbtable = new Model_DbTable_Groupe();

                $groupements = (array) $utilisateur['groupements'];
                array_walk($groupements, function (&$val, $key) use (&$groupements): void {
                    $val = $groupements[$key]['ID_GROUPEMENT'];
                });
                $groupements = implode('-', $groupements);

                $commissions = (array) $utilisateur['commissions'];
                array_walk($commissions, function (&$val, $key) use (&$commissions): void {
                    $val = $commissions[$key]['ID_COMMISSION'];
                });
                $commissions = implode('-', $commissions);

                $privileges_role = $groupes_dbtable->find($utilisateur['ID_GROUPE'])->current()->findModel_DbTable_PrivilegeViaModel_DbTable_GroupePrivilege()->toArray();
                array_walk($privileges_role, function (&$val, $key) use (&$privileges_role): void {
                    $val = $privileges_role[$key]['id_privilege'];
                });

                // On ajoute les ressources spécialisées
                foreach ($resources_dbtable->fetchAll()->toArray() as $resource) {
                    if ('etablissement' === explode('_', $resource['name'])[0]) {
                        $resource_exploded = explode('_', $resource['name']);

                        switch ($resource_exploded[1]) {
                            case 'erp':
                                if ('1' === $resource_exploded[4]) {
                                    $resource_exploded[4] = $commissions;
                                }

                                if ('1' === $resource_exploded[5]) {
                                    $resource_exploded[5] = $groupements;
                                }

                                if ('1' === $resource_exploded[6]) {
                                    $resource_exploded[6] = $utilisateur['NUMINSEE_COMMUNE'];
                                }

                                break;

                            case 'hab':
                            case 'zone':
                                if ('1' === $resource_exploded[3]) {
                                    $resource_exploded[3] = $groupements;
                                }

                                if ('1' === $resource_exploded[4]) {
                                    $resource_exploded[4] = $utilisateur['NUMINSEE_COMMUNE'];
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
                                    $resource_exploded[5] = $utilisateur['NUMINSEE_COMMUNE'];
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
                                    $resource_exploded[3] = $utilisateur['NUMINSEE_COMMUNE'];
                                }

                                break;
                        }

                        $resource_imploded = implode('_', $resource_exploded);
                        $list_resources_finale = [$resource_imploded];

                        $resources = new ResourceContainer($list_resources_finale);
                        foreach ($resources as $r) {
                            if (!$acl->has($r)) {
                                $acl->add(new Zend_Acl_Resource($r));
                            }
                        }
                    } else {
                        continue;
                    }

                    $privileges = $privileges_dbtable->fetchAll('id_resource = '.$resource['id_resource'])->toArray();

                    foreach ($resources as $resource_finale) {
                        foreach ($privileges as $privilege) {
                            if ($acl->has($resource_finale)) {
                                if (in_array($privilege['id_privilege'], $privileges_role)) {
                                    $acl->allow($utilisateur['group']['LIBELLE_GROUPE'], $resource_finale, $privilege['name']);
                                } else {
                                    $acl->deny($utilisateur['group']['LIBELLE_GROUPE'], $resource_finale, $privilege['name']);
                                }
                            }
                        }
                    }
                }
            }

            $role = $utilisateur['group']['LIBELLE_GROUPE'];

            // Récupération de la vue
            $view = Zend_Controller_Front::getInstance()->getParam('bootstrap')->getResource('view');

            // Récupération de la page active
            $page = $view->navigation($view->nav)->findActive($view->navigation($view->nav)->getContainer());

            try {
                // Si on trouve une page active
                if (null != $page) {
                    $page = $page['page'];

                    if (null == $page->getAction()) {
                        $page = $view->navigation($view->nav)->findOneBy('active', true)->findByAction($request->getActionName());
                    }

                    // Si la page correspond bien, on check l'ACL
                    if (null !== $page) {
                        // Récupération de la resource demandée par la page active
                        $resources = new ResourceContainer($this->getPageResources($page, $request));

                        // Récupération du privilège demandé par la page active
                        $privilege = $this->getPagePrivilege($page);

                        // Si il n'y a pas de privilèges associés à la page, on skip la procédure de controle
                        if (null != $privilege) {
                            // Pour chaque ressources de la page, on check les permissions
                            $access_granted = false;

                            if ('etablissement' == $page->get('controller')) {
                                foreach ($resources as $resource) {
                                    if ($acl->has($resource) && $acl->isAllowed($role, $resource, $privilege)) {
                                        $access_granted = true;

                                        break;
                                    }
                                }

                                if ($resources->hasNonDeveloppedResource('editsite') && 'edit' == $page->get('action') && ($acl->has('creations') && $acl->isAllowed($role, 'creations', 'add_etablissement'))) {
                                    $access_granted = true;
                                }
                            } elseif ('dossier' == $page->get('controller')) {
                                if ('add' !== $page->get('action') && 'savenew' !== $page->get('action')) {
                                    $access_granted_ets = false;
                                    $i = 0;
                                    foreach ($resources as $resource) {
                                        if ('etablissement' === explode('_', $resource)[0]) {
                                            if ($acl->has($resource) && $acl->isAllowed($role, $resource, 'view_ets')) {
                                                $access_granted_ets = true;

                                                break;
                                            }

                                            ++$i;
                                        }
                                    }

                                    if ($access_granted_ets || 0 == $i) {
                                        foreach ($resources as $resource) {
                                            if (('dossier' === explode('_', $resource)[0] || 'creations' === explode('_', $resource)[0]) && $acl->has($resource) && $acl->isAllowed($role, $resource, $privilege)) {
                                                $access_granted = true;

                                                break;
                                            }
                                        }
                                    }
                                } elseif ($acl->isAllowed($role, 'creations', 'add_dossier')) {
                                    $access_granted = true;
                                }
                            } else {
                                foreach ($resources as $resource) {
                                    if ($acl->has($resource)) {
                                        if ($acl->isAllowed($role, $resource, $privilege)) {
                                            $access_granted = true;

                                            break;
                                        }
                                    } else {
                                        $access_granted = true;
                                    }
                                }
                            }

                            // Redirection vers la page d'erreur si l'accès est non autorisée
                            if (!$access_granted) {
                                if ('error' == $request->getControllerName()) {
                                    return;
                                }

                                $request->setControllerName('error');
                                $request->setActionName('error');
                                $error = new ArrayObject([], ArrayObject::ARRAY_AS_PROPS);
                                $error->type = Zend_Controller_Plugin_ErrorHandler::EXCEPTION_OTHER;
                                $error->request = clone $request;
                                $error->exception = new Zend_Controller_Dispatcher_Exception('Accès non autorisé', 401);
                                $request->setParam('error_handler', $error);
                            }
                        }
                    }
                }
            } catch (Zend_Acl_Role_Registry_Exception $acle) {
                Zend_Auth::getInstance()->clearIdentity();
                $redirector = Zend_Controller_Action_HelperBroker::getStaticHelper('redirector')->gotoSimple('login', 'session', 'default');
            }
        }
    }

    /**
     * getPageResources.
     *
     * @param null|mixed $request
     * @param mixed      $page
     *
     * @return null|array
     */
    private function getPageResources($page, $request = null)
    {
        $resources = null;
        if (null !== $page) {
            if ('etablissement' == $page->get('controller')) {
                if (null === $page->getResource() && null != $request) {
                    return $this->getEtablissementPageResourses($request->getParam('id'));
                }

                return [$page->getResource()];
            }

            if ('dossier' == $page->get('controller')) {
                if (null === $page->getResource() && null != $request) {
                    if ($id_dossier = $request->getParam('id')) {
                        $model_dossier = new Model_DbTable_Dossier();
                        $dossier_nature = $model_dossier->getNatureDossier($id_dossier);
                        $etablissements = $model_dossier->getEtablissementDossier2($id_dossier);
                        $resources = [];
                        foreach ($etablissements as $etablissement) {
                            $resources = array_merge($resources, $this->getEtablissementPageResourses($etablissement['ID_ETABLISSEMENT']));
                        }

                        $resources[] = 'dossier_'.$dossier_nature['ID_NATURE'];
                    }

                    $resources[] = 'dossier_0';

                    return $resources;
                }

                return [$page->getResource()];
            }

            return null === $page->getResource() ? $page->getParent() instanceof Zend_Navigation_Page ? $this->getPageResources($page->getParent(), $request) : [null] : [$page->getResource()];
        }

        return [null];
    }

    /**
     * getPagePrivilege.
     *
     * @param mixed $page
     *
     * @return null|string
     */
    private function getPagePrivilege($page)
    {
        if (null !== $page) {
            return $page->getPrivilege() ?? ($page->getParent() instanceof Zend_Navigation_Page ? $this->getPagePrivilege($page->getParent()) : null);
        }

        return null;
    }

    /**
     * getEtablissementPageResourses.
     *
     * @param int $id_etablissement
     *
     * @return string[]
     *
     * @psalm-return array<int, string>
     */
    private function getEtablissementPageResourses($id_etablissement): array
    {
        $service_etablissement = new Service_Etablissement();
        $service_groupement_communes = new Service_GroupementCommunes();

        $etablissement = $service_etablissement->get($id_etablissement);

        $groupements = [];
        $communes = [];
        $etablissements = [$id_etablissement => $etablissement];

        switch ($etablissement['informations']['ID_GENRE']) {
            case '1':
                foreach ($etablissement['etablissement_lies'] as $etablissements_enfant) {
                    $etablissements[$etablissements_enfant['ID_ETABLISSEMENT']]['informations'] = $etablissements_enfant;
                }

                // no break
            default:
                break;
        }

        // on récupère les communes des adresses puis les groupements rattachés aux communes de ces adresses
        // pour chacun des établissement : 1 pour un ERP, 1+N pour un site (+ ses enfants)
        $ids_etablissement = array_keys($etablissements);
        $groupement_adresses = $service_groupement_communes->findGroupementForEtablissement($ids_etablissement);

        foreach ($groupement_adresses as $groupement_adresse) {
            if ($groupement_adresse['ID_GROUPEMENT']) {
                $groupements[$groupement_adresse['ID_ETABLISSEMENT']][] = $groupement_adresse['ID_GROUPEMENT'];
            }

            if ($groupement_adresse['NUMINSEE_COMMUNE'] && !isset($communes[$groupement_adresse['ID_ETABLISSEMENT']])) {
                $communes[$groupement_adresse['ID_ETABLISSEMENT']][] = $groupement_adresse['NUMINSEE_COMMUNE'];
            }
        }

        foreach ($ids_etablissement as $id) {
            $groupements[$id][] = '0';
            $groupements[$id] = implode('-', $groupements[$id]);

            $communes[$id][] = '0';
            $communes[$id] = implode('-', $communes[$id]);
        }

        $resource = [];

        switch ($etablissement['informations']['ID_GENRE']) {
            case '1':
                $resource = ['editsite'];

                foreach ($ids_etablissement as $id) {
                    $new_resource = 'etablissement_erp_';
                    $new_resource .= (null == $etablissements[$id]['informations']['ID_TYPEACTIVITE'] ? '0' : $etablissements[$id]['informations']['ID_TYPEACTIVITE'].'-0').'_';
                    $new_resource .= (null == $etablissements[$id]['informations']['ID_CATEGORIE'] ? '0' : $etablissements[$id]['informations']['ID_CATEGORIE'].'-0').'_';
                    $new_resource .= (null == $etablissements[$id]['informations']['ID_COMMISSION'] ? '0' : $etablissements[$id]['informations']['ID_COMMISSION'].'-0').'_';
                    $new_resource .= $groupements[$id].'_';
                    $new_resource .= $communes[$id];

                    $resource[] = $new_resource;
                }

                $resource = array_unique($resource);

                break;

            case '2':
                $resource = 'etablissement_erp_';
                $resource .= (null == $etablissement['informations']['ID_TYPEACTIVITE'] ? '0' : $etablissement['informations']['ID_TYPEACTIVITE'].'-0').'_';
                $resource .= (null == $etablissement['informations']['ID_CATEGORIE'] ? '0' : $etablissement['informations']['ID_CATEGORIE'].'-0').'_';
                $resource .= (null == $etablissement['informations']['ID_COMMISSION'] ? '0' : $etablissement['informations']['ID_COMMISSION'].'-0').'_';
                $resource .= $groupements[$id_etablissement].'_';
                $resource .= $communes[$id_etablissement];

                break;

            case '3':
                $resource = 'etablissement_cell_';
                $resource .= (null == $etablissement['informations']['ID_TYPEACTIVITE'] ? '0' : $etablissement['informations']['ID_TYPEACTIVITE'].'-0').'_';
                $resource .= (null == $etablissement['informations']['ID_CATEGORIE'] ? '0' : $etablissement['informations']['ID_CATEGORIE'].'-0');

                break;

            case '4':
                $resource = 'etablissement_hab_';
                $resource .= (null == $etablissement['informations']['ID_FAMILLE'] ? '0' : $etablissement['informations']['ID_FAMILLE'].'-0').'_';
                $resource .= $groupements[$id_etablissement].'_';
                $resource .= $communes[$id_etablissement];

                break;

            case '5':
                $resource = 'etablissement_igh_';
                $resource .= (null == $etablissement['informations']['ID_CLASSE'] ? '0' : $etablissement['informations']['ID_CLASSE'].'-0').'_';
                $resource .= (null == $etablissement['informations']['ID_COMMISSION'] ? '0' : $etablissement['informations']['ID_COMMISSION'].'-0').'_';
                $resource .= $groupements[$id_etablissement].'_';
                $resource .= $communes[$id_etablissement];

                break;

            case '6':
                $resource = 'etablissement_eic_';
                $resource .= $groupements[$id_etablissement].'_';
                $resource .= $communes[$id_etablissement];

                break;

            case '7':
                $resource = 'etablissement_camp_';
                $resource .= $groupements[$id_etablissement].'_';
                $resource .= $communes[$id_etablissement];

                break;

            case '8':
                $resource = 'etablissement_temp_';
                $resource .= $groupements[$id_etablissement].'_';
                $resource .= $communes[$id_etablissement];

                break;

            case '9':
                $resource = 'etablissement_iop_';
                $resource .= $groupements[$id_etablissement].'_';
                $resource .= $communes[$id_etablissement];

                break;

            case '10':
                $resource = 'etablissement_zone_';
                $resource .= (null == $etablissement['informations']['ID_CLASSEMENT'] ? '0' : $etablissement['informations']['ID_CLASSEMENT'].'-0').'_';
                $resource .= $groupements[$id_etablissement].'_';
                $resource .= $communes[$id_etablissement];

                break;
        }

        return is_array($resource) ? $resource : [$resource];
    }
}

/**
 * Container de ressource non developpées
 * qui ne les développent qu'à la demande, cette fonction coûtant cher en exécution.
 */
class ResourceContainer implements Iterator
{
    protected $resources = [];

    protected $developped_resources = [];

    protected $resources_index = 0;

    protected $developped_resources_index = 0;

    public function __construct(array $resources = [])
    {
        $this->resources = $resources;
    }

    public function current()
    {
        return $this->developped_resources[$this->developped_resources_index] ?? null;
    }

    public function key()
    {
        return 0;
    }

    public function next(): void
    {
        ++$this->developped_resources_index;
        if (!isset($this->developped_resources[$this->developped_resources_index])) {
            ++$this->resources_index;
            if (isset($this->resources[$this->resources_index])) {
                $this->developped_resources_index = 0;
                $this->developped_resources = [$this->resources[$this->resources_index]];
                $this->develop_resources($this->developped_resources);
            }
        }
    }

    public function rewind(): void
    {
        $this->developped_resources = [];
        $this->resources_index = 0;
        $this->developped_resources_index = 0;

        if (count($this->resources) > 0) {
            $this->developped_resources = [$this->resources[$this->resources_index]];
            $this->develop_resources($this->developped_resources);
        }
    }

    public function valid()
    {
        return isset($this->developped_resources[$this->developped_resources_index])
        && isset($this->resources[$this->resources_index]);
    }

    public function hasNonDeveloppedResource($resource): bool
    {
        return in_array($resource, $this->resources);
    }

    /**
     * @param array $list_resources_finale
     */
    public function develop_resources(&$list_resources_finale): array
    {
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
                    $this->develop_resources($list_resources_finale);
                }
            }
        }

        return array_unique($list_resources_finale);
    }
}
