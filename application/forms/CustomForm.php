<?php

class Form_CustomForm extends Zend_Form
{
    public function init(): void
    {
        $this->setMethod('post');

        $this->addElement('text', 'nom_rubrique', [
            'label' => 'Nom de la rubrique',
            'required' => true,
            'filters' => [new Zend_Filter_HtmlEntities(), new Zend_Filter_StripTags()],
            'validators' => [new Zend_Validate_StringLength(1, 255)],
        ]);

        $this->addElement('checkbox', 'afficher_rubrique', [
            'label' => 'Afficher la rubrique par défaut',
        ]);

        $submit = new Zend_Form_Element_Button('save');
        $submit->class = 'btn btn-success pull-right add-rubrique';
        $submit->setLabel('Ajouter la rubrique');
        $this->addElement($submit);

        $this->setDecorators([
            'FormElements',
            'Form',
        ]);

        $this->setElementDecorators(
            [
                'ViewHelper',
                'Label',
            ],
            [
                'save',
            ],
            false
        );
    }
}
