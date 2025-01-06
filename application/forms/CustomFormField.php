<?php

class Form_CustomFormField extends Zend_Form
{
    public function init(): void
    {
        $this->setMethod('post');
        $this->setAttrib('class', 'form-inline');

        $this->addElement('text', 'nom_champ', [
            'label' => 'Nom du champ',
            'required' => true,
            'filters' => [new Zend_Filter_HtmlEntities(), new Zend_Filter_StripTags()],
            'validators' => [new Zend_Validate_StringLength(1, 255)],
        ]);

        $this->addElement('select', 'type_champ', [
            'label' => 'Type du champ',
            'required' => true,
            'multiOptions' => $this->getAllListeTypeChampRubrique(),
        ]);

        $submit = new Zend_Form_Element_Button('save');
        $submit->class = 'btn btn-success pull-right';
        $submit->id = 'add-champ';
        $submit->setLabel('Ajouter le champ');
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

    public function getAllListeTypeChampRubrique(): array
    {
        $selectValues = [];
        $serviceFormulaire = new Service_Formulaire();

        $typesChampRubrique = $serviceFormulaire->getAllListeTypeChampRubrique();
        foreach ($typesChampRubrique as $typeChampRubrique) {
            $selectValues[$typeChampRubrique['ID_TYPECHAMP']] = $typeChampRubrique['TYPE'];
        }

        return $selectValues;
    }
}
