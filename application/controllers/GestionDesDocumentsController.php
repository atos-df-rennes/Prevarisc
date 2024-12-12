<?php

class GestionDesDocumentsController extends Zend_Controller_Action
{
    public $path;

    public function init(): void
    {
        $this->path = REAL_DATA_PATH.DS.'uploads'.DS.'documents';

        // Actions à effectuées en AJAX
        $ajaxContext = $this->_helper->getHelper('AjaxContext');
        $ajaxContext->addActionContext('check', 'json')
            ->initContext()
        ;
    }

    public function indexAction(): void
    {
        $this->_helper->layout->setLayout('menu_admin');

        $service_commission = new Service_Commission();

        $liste_commission = $service_commission->getAll();

        // Récupération des documents présents dans le dossier 0. Documents visibles après vérrouillage
        $pathVer = $this->path.'/0';
        ($dirVer = opendir($pathVer)) || exit('Erreur de listage : le répertoire n\'existe pas');
        $fichierVer = [];
        $dossierVer = [];
        while ($elementVer = readdir($dirVer)) {
            if (
                '.' !== $elementVer
                && '..' !== $elementVer
                && '.gitignore' !== $elementVer
            ) {
                if (!is_dir($pathVer.DS.$elementVer)) {
                    $fichierVer[] = $elementVer;
                } else {
                    $dossierVer[] = $elementVer;
                }
            }
        }

        closedir($dirVer);
        sort($fichierVer);

        $this->view->assign('fichierVer', $fichierVer);

        // Récupération de l'ensemble des documents des différentes commissions
        foreach ($liste_commission as $var => $commission) {
            $path = $this->path.DS.$commission['ID_COMMISSION'];
            ($dir = opendir($path)) || exit('Erreur de listage : le répertoire n\'existe pas'); // on ouvre le contenu du dossier courant
            $fichier = []; // on déclare le tableau contenant le nom des fichiers
            $dossier = []; // on déclare le tableau contenant le nom des dossiers

            while ($element = readdir($dir)) {
                if (
                    '.' !== $element
                    && '..' !== $element
                    && '.gitignore' !== $element
                ) {
                    if (!is_dir($path.DS.$element)) {
                        $fichier[] = $element;
                    } else {
                        $dossier[] = $element;
                    }
                }
            }

            closedir($dir);
            sort($fichier);

            $liste_commission[$var]['listeFichier'] = $fichier;
        }

        $this->view->assign('path', DATA_PATH.'/uploads/documents');
        $this->view->assign('liste_commission', $liste_commission);
    }

    public function formAction(): void
    {
        $service_commission = new Service_Commission();

        $this->view->assign('liste_commission', $service_commission->getAll());
    }

    public function addAction(): void
    {
        try {
            $this->_helper->layout->disableLayout();
            $this->_helper->viewRenderer->setNoRender(true);

            // Extension du fichier
            $filename = str_replace(DS, '', $_FILES['fichier']['name']);
            $extension = strtolower(strrchr($filename, '.'));
            if ('.odt' !== $extension) {
                throw new Exception('Seuls les fichiers .odt sont autorisés en upload.');
            }

            // Si besoin verificaiton de l'extension du fichier (uniquement odt)
            if (!move_uploaded_file($_FILES['fichier']['tmp_name'], $this->path.DS.$this->getRequest()->getParam('commission').DS.$filename)) {
                throw new Exception('Impossible de déplacer le fichier uploadé');
            }

            $this->_helper->flashMessenger([
                'context' => 'success',
                'title' => 'Le document a bien été ajouté',
                'message' => '',
            ]);
        } catch (Exception $exception) {
            $this->_helper->flashMessenger([
                'context' => getenv('PREVARISC_BOOTSTRAP_3') ? 'danger' : 'error',
                'title' => "Erreur lors de l'ajout du document",
                'message' => $exception->getMessage(),
            ]);
        }

        echo '
            <script type="text/javascript">
                window.top.window.location.reload();
            </script>
        ';
    }

    public function suppdocAction(): void
    {
        try {
            $this->_helper->layout->disableLayout();
            $this->_helper->viewRenderer->setNoRender();

            $path = $this->path.DS.$this->getRequest()->getParam('idCommission');

            $filename = str_replace(DS, '', $this->getRequest()->getParam('name'));

            // On verifie si le fichier existe
            $exist = file_exists($path.DS.$filename);
            unlink($path.DS.$filename);
            $exist2 = file_exists($path.DS.$filename);

            if ($exist === $exist2) {
                throw new Exception('Impossible de supprimer le fichier '.$filename);
            }
        } catch (Exception $exception) {
            $this->_helper->flashMessenger([
                'context' => getenv('PREVARISC_BOOTSTRAP_3') ? 'danger' : 'error',
                'title' => 'Erreur lors de la suppression du document',
                'message' => $exception->getMessage(),
            ]);
            echo $exception->getMessage();
        }
    }
}
