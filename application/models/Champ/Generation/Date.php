<?php

final class Model_Champ_Generation_Date implements Model_Champ_Generation_Interface_Champ
{
    public function getGeneratedValue(?string $value): string
    {
        if ($value !== null) {
            $zendDate = new Zend_Date($value, 'dd/MM/yyyy');

            return $zendDate->get(Zend_Date::DAY . ' ' . Zend_Date::MONTH_NAME . ' ' . Zend_Date::YEAR);
        }
        
        return '';
    }
}