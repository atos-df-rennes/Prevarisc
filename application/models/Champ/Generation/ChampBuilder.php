<?php

final class Model_Champ_Generation_ChampBuilder
{
    private $value;
    private $type;

    public function setValue(string $value): self
    {
        $this->value = $value;

        return $this;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function build(): Model_Champ_Generation_Champ
    {
        return new Model_Champ_Generation_Champ(
            $this->value,
            $this->createType()
        );
    }

    private function createType(): Model_Champ_Generation_Interface_Champ
    {
        switch ($this->type) {
            case 'Case à cocher':
                return new Model_Champ_Generation_CaseACocher();
            case 'Date':
                return new Model_Champ_Generation_Date();
            default:
                return new Model_Champ_Generation_Base();
        }
    }
}