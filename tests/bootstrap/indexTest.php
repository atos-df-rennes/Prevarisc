<?php

date_default_timezone_set('Europe/Paris');

// Création d'une constante plus courte qui est égale à DIRECTORY_SEPARATOR
defined('DS') || define('DS', DIRECTORY_SEPARATOR);

// Définition du chemin vers le dossier application/
defined('APPLICATION_PATH') || define('APPLICATION_PATH', getenv('PREVARISC_APPLICATION_PATH') ?: dirname(__FILE__, 3).DS.'application');

// Définition du chemin vers le dossier command/
defined('COMMAND_PATH') || define('COMMAND_PATH', getenv('PREVARISC_COMMAND_PATH') ?: APPLICATION_PATH.DS.'command');

// Define path to application directory
defined('DATA_PATH') || define('DATA_PATH', getenv('PREVARISC_DATA_PATH') ?: DS.'data');

// Define path to application directory
defined('REAL_DATA_PATH') || define('REAL_DATA_PATH', getenv('PREVARISC_REAL_DATA_PATH') ?: __DIR__.DS.'..'.DS.'public'.DS.'data');

// Define path to plat'au directory
defined('PLATAU_PATH') || define('PLATAU_PATH', getenv('PREVARISC_PLATAU_PATH') ?: __DIR__.DS.'..'.DS.'..'.DS.'prevarisc-passerelle-platau');

// Chargements des librairies
require APPLICATION_PATH.DS.'..'.DS.'vendor'.DS.'autoload.php';

// Création de l'application avec les fichiers config
$application = new Zend_Application('production', [
    'bootstrap' => [
        'path' => dirname(APPLICATION_PATH).DS.'tests'.DS.'bootstrap'.DS.'BootstrapTest.php',
        'class' => 'BootstrapTest',
    ],
    'resources' => [
        'frontController' => [
            'controllerDirectory' => APPLICATION_PATH.DS.'controllers',
            'moduleDirectory' => APPLICATION_PATH.DS.'modules',
            'params' => [
                'displayExceptions' => getenv('PREVARISC_DEBUG_ENABLED'),
            ],
        ],
        'db' => [
            'adapter' => 'mysqli',
            'params' => [
                'host' => getenv('PREVARISC_DB_HOST'),
                'charset' => getenv('PREVARISC_DB_CHARSET'),
                'username' => getenv('PREVARISC_DB_USERNAME'),
                'password' => getenv('PREVARISC_DB_PASSWORD'),
                'dbname' => getenv('PREVARISC_DB_DBNAME'),
            ],
        ],
        'dataStore' => [
            'adapter' => 'Plugin_SimpleFileDataStore',
        ],
        'modules' => '',
    ],
    'ldap' => [
        'enabled' => getenv('PREVARISC_LDAP_ENABLED'),
        'host' => getenv('PREVARISC_LDAP_ENABLED') ? getenv('PREVARISC_LDAP_HOST') : '',
        'username' => getenv('PREVARISC_LDAP_ENABLED') ? getenv('PREVARISC_LDAP_USERNAME') : '',
        'password' => getenv('PREVARISC_LDAP_ENABLED') ? getenv('PREVARISC_LDAP_PASSWORD') : '',
        'baseDn' => getenv('PREVARISC_LDAP_ENABLED') ? getenv('PREVARISC_LDAP_BASEDN') : '',
    ],
    'cache' => [
        'adapter' => getenv('PREVARISC_CACHE_ADAPTER') ?: 'File',
        'customAdapter' => false !== getenv('PREVARISC_CACHE_ADAPTER'),
        'enabled' => ((int) getenv('PREVARISC_CACHE_LIFETIME')) > 0,
        'lifetime' => (int) getenv('PREVARISC_CACHE_LIFETIME'),
        'host' => getenv('PREVARISC_CACHE_HOST'),
        'port' => (int) getenv('PREVARISC_CACHE_PORT'),
        'write_control' => false,
        'compression' => false,
        'cache_dir' => getenv('PREVARISC_CACHE_DIR') ?: APPLICATION_PATH.DS.'..'.DS.'cache',
        'read_control' => false,
    ],
    'security' => [
        'salt' => getenv('PREVARISC_SECURITY_SALT'),
        'session_max_lifetime' => getenv('PREVARISC_SESSION_MAX_LIFETIME'),
    ],
    'phpSettings' => [
        'display_startup_errors' => getenv('PREVARISC_DEBUG_ENABLED'),
        'display_errors' => getenv('PREVARISC_DEBUG_ENABLED'),
    ],
    'mail' => [
        'enabled' => getenv('PREVARISC_MAIL_ENABLED'),
        'transport' => getenv('PREVARISC_MAIL_ENABLED') ? getenv('PREVARISC_MAIL_TRANSPORT') : '',
        'host' => getenv('PREVARISC_MAIL_ENABLED') ? getenv('PREVARISC_MAIL_HOST') : '',
        'port' => getenv('PREVARISC_MAIL_ENABLED') ? getenv('PREVARISC_MAIL_PORT') : '',
        'authentication' => getenv('PREVARISC_MAIL_ENABLED') && '' !== getenv('PREVARISC_MAIL_USERNAME'),
        'username' => getenv('PREVARISC_MAIL_ENABLED') ? getenv('PREVARISC_MAIL_USERNAME') : '',
        'password' => getenv('PREVARISC_MAIL_ENABLED') ? getenv('PREVARISC_MAIL_PASSWORD') : '',
        'sender' => getenv('PREVARISC_MAIL_ENABLED') ? getenv('PREVARISC_MAIL_SENDER') : '',
        'sender_name' => getenv('PREVARISC_MAIL_ENABLED') ? getenv('PREVARISC_MAIL_SENDER_NAME') : '',
    ],
]);

// Bootstrap et gooooo !
$application->bootstrap();
