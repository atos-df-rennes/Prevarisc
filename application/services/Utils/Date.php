<?php

class Service_Utils_Date
{
    public static function convertToMySQL(string $date): ?string
    {
        if ('' === $date) {
            return null;
        }

        [$jour, $mois, $annee] = explode('/', $date);

        return implode('-', [$annee, $mois, $jour]);
    }

    public static function convertFromMySQL(?string $date): ?string
    {
        if (null === $date) {
            return null;
        }

        [$annee, $mois, $jour] = explode('-', $date);

        return implode('/', [$jour, $mois, $annee]);
    }

    public static function formatDateWithDayName(?string $date, string $dateFormat = 'dd/MM/yyyy'): ?string
    {
        if (null === $date) {
            return null;
        }

        $zendDate = new Zend_Date($date, $dateFormat, 'fr');

        return $zendDate->get(Zend_Date::WEEKDAY.' '.Zend_Date::DAY_SHORT.' '.Zend_Date::MONTH_NAME_SHORT.' '.Zend_Date::YEAR, 'fr');
    }
}
