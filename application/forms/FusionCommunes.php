<?php

class Form_FusionCommunes extends Zend_Form
{
    /**
     * {@inheritdoc}
     */
    public function init()
    {
        $this->setMethod('post');
        $this->setAttrib('enctype', 'multipart/form-data');

        $file = new Zend_Form_Element_File('fusioncommunes');
        $file->setDestination(COMMAND_PATH);

        $file->addValidator('Count', false, 1)
            ->addValidator('Extension', false, 'json')
        ;

        $this->addElement($file);
        $this->addElement(
            new Zend_Form_Element_Submit(
                'fusioncommunes',
                [
                    'class' => 'btn btn-primary',
                    'label' => 'Fusionner les communes',
                ]
            ),
            'submit'
        );

        // FIXME Voir pour utiliser les decorators
    }
}
