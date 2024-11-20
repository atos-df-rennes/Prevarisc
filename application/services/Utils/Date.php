<?php

class Service_Utils_Date
{
    public static function convertToMySQL(?string $date): ?string
    {
        $zendDate = self::convertStringToDate($date);

        if (null === $zendDate) {
            return null;
        }

        return $zendDate->get(Zend_Date::YEAR.'-'.Zend_Date::MONTH.'-'.Zend_Date::DAY);
    }

    public static function convertFromMySQL(?string $date): ?string
    {
        $zendDate = self::convertStringToDate($date);

        if (null === $zendDate) {
            return null;
        }

        return $zendDate->get(Zend_Date::DAY.'/'.Zend_Date::MONTH.'/'.Zend_Date::YEAR);
    }

    public static function formatDateWithDayName(?string $date): ?string
    {
        $zendDate = self::convertStringToDate($date);

        if (null === $zendDate) {
            return null;
        }

        return $zendDate->get(Zend_Date::WEEKDAY.' '.Zend_Date::DAY_SHORT.' '.Zend_Date::MONTH_NAME_SHORT.' '.Zend_Date::YEAR, 'fr');
    }

    private static function convertStringToDate(?string $date): ?Zend_Date
    {
        if (
            null === $date
            || '' === $date
        ) {
            return null;
        }

        return new Zend_Date($date, Zend_Date::DATES, 'fr');
    }
}
