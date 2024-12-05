<?php

class Form_FormChampFromParent extends Zend_Form
{
    protected $champParentID;

    protected $rubriqueID;

    public function setChampParentID($myParameters): void
    {
        $this->champParentID = $myParameters;
    }

    public function setRubriqueID($myParameters): void
    {
        $this->rubriqueID = $myParameters;
    }

    public function init(): void
    {
        $dbType = new Model_DbTable_ListeTypeChampRubrique();
        $this->setMethod('post');
        $this->setAttrib('class', 'form-inline');

        $this->addElement('text', 'nom_champ_enfant', [
            'label' => 'Nom du champ',
            'required' => true,
            'filters' => [new Zend_Filter_HtmlEntities(), new Zend_Filter_StripTags()],
            'validators' => [new Zend_Validate_StringLength(1, 255)],
        ]);
        $this->addElement(
            'select',
            'type_champ_enfant',
            [
                'label' => 'Type du champ',
                'required' => true,
                'multiOptions' => $dbType->getTypeWithoutParentToSelectForm(),
            ]
        );

        $this->addElement('hidden', 'ID_CHAMP_PARENT', [
            'required' => true,
            'value' => $this->champParentID,
        ]);
        $this->addElement('hidden', 'rubrique', [
            'required' => true,
            'value' => $this->rubriqueID,
        ]);

        $submit = new Zend_Form_Element_Button('save');
        $submit->id = 'add-champ';
        $submit->class = 'btn btn-success pull-right';
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
}
