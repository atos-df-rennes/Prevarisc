<?php

class Plugin_FacteurCriticite extends Zend_Controller_Plugin_Abstract
{
    public function postDispatch(Zend_Controller_Request_Abstract $request): void
    {
        if (
            'etablissement' !== $request->getControllerName()
            && (
                'dossier' !== $request->getControllerName()
                || 'index' !== $request->getActionName()
            )
        ) {
            return;
        }

        $view = Zend_Controller_Front::getInstance()
            ->getParam('bootstrap')
            ->getResource('view')
        ;

        $view->inlineScript()->appendScript(
            <<<'EOS'
$(document).ready(function() {
    $('#FACTDANGE .libelle').text('Signalement')
})
EOS
        );
    }
}
