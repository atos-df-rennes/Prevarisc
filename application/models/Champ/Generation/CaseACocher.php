<?php

final class Model_Champ_Generation_CaseACocher implements Model_Champ_Generation_Interface_Champ
{
    public function getGeneratedValue(?string $value): string
    {
        return true === boolval($value) ? 'Oui' : 'Non';
    }
}