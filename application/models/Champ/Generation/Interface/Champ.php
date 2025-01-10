<?php

interface Model_Champ_Generation_Interface_Champ
{
    public function getGeneratedValue(string $value): string;
}