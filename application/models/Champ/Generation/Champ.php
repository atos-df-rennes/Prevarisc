<?php

final class Model_Champ_Generation_Champ
{
    private $value;
    private $type;

    public function __construct(string $value, Model_Champ_Generation_Interface_Champ $type)
    {
        $this->value = $value;
        $this->type = $type;
    }

    public function getGeneratedValue(): string
    {
        return $this->type->getGeneratedValue($this->value);
    }
}