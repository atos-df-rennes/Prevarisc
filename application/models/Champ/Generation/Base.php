<?php

final class Model_Champ_Generation_Base implements Model_Champ_Generation_Interface_Champ
{
    public function getGeneratedValue(string $value): string
    {
        return $value;
    }
}