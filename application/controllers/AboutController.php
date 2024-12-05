<?php

use Michelf\Markdown;

class AboutController extends Zend_Controller_Action
{
    public function postDispatch(): void
    {
        // on rend la vue générique
        $this->render('display-text');
    }

    public function indexAction(): void
    {
        $text = file_get_contents(APPLICATION_PATH.DS.'..'.DS.'docs'.DS.'about.md');
        $this->view->assign('text', Markdown::defaultTransform($text));
    }

    public function tosAction(): void
    {
        $text = file_get_contents(APPLICATION_PATH.DS.'..'.DS.'docs'.DS.'tos.md');
        $this->view->assign('text', Markdown::defaultTransform($text));
    }

    public function supportAction(): void
    {
        $text = file_get_contents(APPLICATION_PATH.DS.'..'.DS.'docs'.DS.'support.md');
        $this->view->assign('text', Markdown::defaultTransform($text));
    }

    public function devAction(): void
    {
        $text = file_get_contents(APPLICATION_PATH.DS.'..'.DS.'docs'.DS.'dev.md');
        $this->view->assign('text', Markdown::defaultTransform($text));
    }
}
