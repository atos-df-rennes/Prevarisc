<?php

class Form_FusionCommunes extends Zend_Form
{
    public function init(): void
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
                'savefusioncommunes',
                [
                    'class' => 'btn btn-primary',
                    'label' => 'Fusionner les communes',
                ]
            ),
            'submit'
        );
    }
}
