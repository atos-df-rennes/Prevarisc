<?php

class PlatauController extends Zend_Controller_Action
{
    /**
     * @var Model_PlatauConsultationMapper
     */
    private $platauConsultationMapper;

    /**
     * @var Zend_Layout
     */
    private $layout;

    /**
     * @var Zend_Controller_Action_Helper_ViewRenderer
     */
    private $viewRenderer;

    public function init(): void
    {
        /** @var Zend_Controller_Action_Helper_ContextSwitch $ajaxContext */
        $ajaxContext = $this->_helper->getHelper('AjaxContext');
        $ajaxContext->addActionContext('selectionabreviation', 'json')
            ->addActionContext('retryExportPec', 'json')
            ->addActionContext('retryExportAvis', 'json')
            ->initContext()
        ;

        $this->platauConsultationMapper = new Model_PlatauConsultationMapper();
        $this->layout = Zend_Layout::getMvcInstance();
        $this->viewRenderer = $this->getHelper('viewRenderer');
    }

    public function retryExportPecAction(): void
    {
        $this->layout->disableLayout();
        $this->viewRenderer->setNoRender();

        $platauConsultation = $this->platauConsultationMapper->find($this->getParam('id'), new Model_PlatauConsultation());
        $platauConsultation->setStatutPec('to_export');

        $this->platauConsultationMapper->save($platauConsultation);
    }

    public function retryExportAvisAction(): void
    {
        $this->layout->disableLayout();
        $this->viewRenderer->setNoRender();

        $platauConsultation = $this->platauConsultationMapper->find($this->getParam('id'), new Model_PlatauConsultation());
        $platauConsultation->setStatutAvis('to_export');

        $this->platauConsultationMapper->save($platauConsultation);
    }
}
