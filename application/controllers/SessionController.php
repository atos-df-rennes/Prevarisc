<?php

class SessionController extends Zend_Controller_Action
{
    public const MAX_LOGIN_ATTEMPTS = 2;

    public function loginAction(): void
    {
        $this->_helper->layout->setLayout('login');

        $form = new Form_Login();
        $service_user = new Service_User();
        $username = null;
        $password = '';
        $user = null;
        $this->view->assign('form', $form);

        try {
            // Adaptateur CAS
            if (1 == getenv('PREVARISC_CAS_ENABLED')) {
                $username = phpCAS::getUser();
            } elseif (1 == getenv('PREVARISC_NTLM_ENABLED')) { // Adapter NTLM
                if (!isset($_SERVER['REMOTE_USER'])) {
                    error_log('ntlm auth with no REMOTE_USER set in server variables');
                } else {
                    $cred = explode('\\', $_SERVER['REMOTE_USER']);
                    if (1 == count($cred)) {
                        array_unshift($cred, null);
                    }

                    [$domain, $username] = $cred;
                }
            }

            if ($this->_request->isPost()) { // Cas par défaut
                if (!$form->isValid($this->_request->getPost())) {
                    error_log('Auth: formulaire classique invalide');

                    throw new Zend_Auth_Exception('Authentification invalide.');
                }

                // Identifiants
                $username = $this->_request->prevarisc_login_username;
                $password = $this->_request->prevarisc_login_passwd;
            }

            if ($username) {
                // Récupération de l'utilisateur
                $user = $service_user->findByUsername($username);

                // Si l'utilisateur n'est pas actif, on renvoie false
                if (
                    null === $user
                    || (null !== $user && !$user['ACTIF_UTILISATEUR'])
                ) {
                    error_log(sprintf("Auth: utilisateur inexistant ou inactif '%s'", $username));

                    throw new Zend_Auth_Exception('Authentification invalide.');
                }

                // Authentification adapters
                $adapters = [];

                // Adaptateur SSO noauth
                if (
                    1 == getenv('PREVARISC_CAS_ENABLED')
                    || 1 == getenv('PREVARISC_NTLM_ENABLED')
                ) {
                    $adapters['sso'] = new Service_PassAuthAdapater($username);
                } elseif (
                    1 == getenv('PREVARISC_ENFORCE_SECURITY')
                    && isset($user['FAILED_LOGIN_ATTEMPTS_UTILISATEUR'])
                    && $user['FAILED_LOGIN_ATTEMPTS_UTILISATEUR'] >= self::MAX_LOGIN_ATTEMPTS
                    && isset($user['IP_UTILISATEUR'])
                    && $user['IP_UTILISATEUR']
                ) {
                    // Cas classique s'il y a déjà eu des login infructueux
                    // Système anti-dos, anti-bruteforce : si pas l'ip habituelle, on drop la requête
                    if ($user['IP_UTILISATEUR'] != $_SERVER['REMOTE_ADDR']) {
                        error_log("Auth: trop d'essais infructeurs, denying IP ".$_SERVER['REMOTE_ADDR'].' which does not match last login IP '.$user['IP_UTILISATEUR']);

                        throw new Zend_Auth_Exception('Authentification invalide.');
                    }
                }

                // Adaptateur principal (dbtable)
                $adapters['dbtable'] = new Zend_Auth_Adapter_DbTable(null, 'utilisateur', 'USERNAME_UTILISATEUR', 'PASSWD_UTILISATEUR');
                $adapters['dbtable']->setIdentity($username)->setCredential(md5($username.getenv('PREVARISC_SECURITY_SALT').$password));

                // Adaptateur LDAP
                if (1 == getenv('PREVARISC_LDAP_ENABLED')) {
                    $ldap = new Zend_Ldap(['host' => getenv('PREVARISC_LDAP_HOST'), 'port' => getenv('PREVARISC_LDAP_PORT') ?: 389, 'username' => getenv('PREVARISC_LDAP_USERNAME'), 'password' => getenv('PREVARISC_LDAP_PASSWORD'), 'baseDn' => getenv('PREVARISC_LDAP_BASEDN')]);

                    try {
                        $accountForm = getenv('PREVARISC_LDAP_ACCOUNT_FORM') ?: Zend_Ldap::ACCTNAME_FORM_DN;
                        $adapters['ldap'] = new Zend_Auth_Adapter_Ldap();
                        $adapters['ldap']->setLdap($ldap);
                        $adapters['ldap']->setUsername($ldap->getCanonicalAccountName($username, $accountForm));
                        $adapters['ldap']->setPassword($password);
                    } catch (Exception $e) {
                        error_log('Auth: ldap exception: '.$e->getMessage());
                    }
                }

                // On lance le process d'identification avec les différents adaptateurs
                foreach ($adapters as $adapter) {
                    if ($adapter->authenticate()->isValid()) {
                        $service_user->resetFailedLogin($user);
                        Zend_Auth::getInstance()->getStorage()->write($user);
                        $this->_helper->redirector->gotoUrl(empty($this->_request->getParams()['redirect']) ? '/' : urldecode($this->_request->getParams()['redirect']));
                    }
                }

                error_log(sprintf("Auth: password incorrect pour '%s'", $username));

                throw new Zend_Auth_Exception('Authentification invalide.');
            }
        } catch (Exception $exception) {
            $service_user->logFailedLogin($user);
            $this->_helper->flashMessenger(['context' => 'danger', 'title' => "Erreur d'authentification", 'message' => $exception->getMessage()]);
        }
    }

    public function logoutAction(): void
    {
        $auth = Zend_Auth::getInstance();

        $user = $auth->getIdentity();

        if ($auth->hasIdentity()) {
            $service_user = new Service_User();
            $service_notification = new Service_Notification();

            $service_user->updateLastActionDate($auth->getIdentity()['ID_UTILISATEUR']);
            $service_notification->updateUserLastPageVisitDates();

            $auth->clearIdentity();
        }

        if (1 == getenv('PREVARISC_CAS_ENABLED')) {
            phpCAS::logout();
        } elseif (
            1 == getenv('PREVARISC_NTLM_ENABLED')
            && $user
            && null == $user['PASSWD_UTILISATEUR']
        ) { // On test si l'utilisateur est connecté en NTLM
            $this->_helper->layout->setLayout('error');
        } else {
            $this->_helper->redirector->gotoUrl($this->view->url(['controller' => null, 'action' => null]));
        }
    }
}
