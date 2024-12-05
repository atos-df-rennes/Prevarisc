<?php

class Api_Bootstrap extends Zend_Application_Module_Bootstrap
{
    public function run(): void
    {
        // Chargement et activation des plugins
        // Contrôle du referer pour les requêtes aux api
        Zend_Controller_Front::getInstance()->registerPlugin(new Api_Plugin_RefererCheck());
        Zend_Controller_Front::getInstance()->registerPlugin(new Plugin_ACL());
        // On continue le chargement par défaut
        parent::run();
    }

    /**
     * Initialisation du routeur pour les URL du webservice.
     *
     * @return Zend_Controller_Router_Interface
     */
    protected function _initRoutes()
    {
        $router = Zend_Controller_Front::getInstance()->getRouter();
        $router->addConfig(new Zend_Config_Xml(__DIR__.DS.'configs'.DS.'routes.xml'));

        return $router;
    }
}
