<?php

use SebastianBergmann\Git\Git;

class AdminController extends Zend_Controller_Action
{
    public function indexAction(): void
    {
        $cache_config = $this->getInvokeArg('bootstrap')->getOption('cache');

        $this->_helper->layout->setLayout('menu_admin');

        if (!getenv('PREVARISC_BRANCH')) {
            try {
                $git = new Git(APPLICATION_PATH.DS.'..');
                $revisions = $git->getRevisions();
                $last_revision = end($revisions);
                $revision_prevarisc_local = $last_revision['sha1'];
                $client = new Zend_Http_Client();
                $client->setUri('https://api.github.com/repos/SDIS62/prevarisc/git/refs/heads/2.x');
                $client->setConfig(['maxredirects' => 0, 'timeout' => 3]);
                $response = json_decode($client->request()->getBody());
                $revision_prevarisc_github = $response->object->sha;
                $this->view->assign('is_uptodate', $revision_prevarisc_github == $revision_prevarisc_local);
            } catch (Exception $e) {
            }
        }

        $this->view->assign([
            'key_ign' => getenv('PREVARISC_PLUGIN_IGNKEY'),
            'key_googlemap' => getenv('PREVARISC_PLUGIN_GOOGLEMAPKEY'),
            'geoconcept_url' => getenv('PREVARISC_PLUGIN_GEOCONCEPT_URL'),
            'geoconcept_infos' => [
                'Url' => getenv('PREVARISC_PLUGIN_GEOCONCEPT_URL'),
                'Layer' => getenv('PREVARISC_PLUGIN_GEOCONCEPT_LAYER'),
                'App ID' => getenv('PREVARISC_PLUGIN_GEOCONCEPT_APP_ID'),
                'Projection' => getenv('PREVARISC_PLUGIN_GEOCONCEPT_PROJECTION') ?: 'Non paramétrée',
                'Token' => getenv('PREVARISC_PLUGIN_GEOCONCEPT_TOKEN'),
                'Geocoder Url' => getenv('PREVARISC_PLUGIN_GEOCONCEPT_GEOCODER'),
            ],
            'dbname' => getenv('PREVARISC_DB_DBNAME'),
            'db_url' => getenv('PREVARISC_DB_HOST').(getenv('PREVARISC_DB_PORT') ? ':'.getenv('PREVARISC_DB_PORT') : ''),
            'api_enabled' => '' != getenv('PREVARISC_SECURITY_KEY'),
            'proxy_enabled' => getenv('PREVARISC_PROXY_ENABLED'),
            'third_party_plugins' => implode(', ', explode(';', getenv('PREVARISC_THIRDPARTY_PLUGINS'))),
        ]);

        if (getenv('PREVARISC_CAS_ENABLED')) {
            $this->view->assign('authentification', 'CAS');
        } elseif (getenv('PREVARISC_NTLM_ENABLED')) {
            $this->view->assign('authentification', 'NTLM + BDD');
        } elseif (getenv('PREVARISC_LDAP_ENABLED')) {
            $this->view->assign('authentification', sprintf(
                'LDAP + BDD : %s:%d/%s',
                getenv('PREVARISC_LDAP_HOST'),
                getenv('PREVARISC_LDAP_PORT') ?: '389',
                getenv('PREVARISC_LDAP_BASEDN')
            ));
        } else {
            $this->view->assign('authentification', 'BDD');
        }

        $this->view->assign([
            'cache_adapter' => $cache_config['adapter'],
            'cache_url' => $cache_config['host'].($cache_config['port'] ? ':'.$cache_config['port'] : ''),
            'cache_lifetime' => $cache_config['lifetime'],
            'cache_enabled' => $cache_config['enabled'],
            'enforce_security' => 1 == getenv('PREVARISC_ENFORCE_SECURITY'),
        ]);

        $service_search = new Service_Search();
        $users = $service_search->users(null, null, null, true, 1000)['results'];
        $this->view->assign('users', []);

        foreach ($users as $user) {
            if (time() - strtotime($user['LASTACTION_UTILISATEUR']) < ini_get('session.gc_maxlifetime')) {
                $this->view->users[] = $user;
            }
        }
    }
}
