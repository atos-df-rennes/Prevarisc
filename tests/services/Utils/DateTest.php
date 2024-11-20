<?php

use PHPUnit\Framework\TestCase;

/**
 * @covers \Service_Utils_Date
 *
 * @internal
 */
final class Service_Utils_DateTest extends TestCase
{
    /**
     * @dataProvider convertToMySQLProvider
     */
    public function testConvertToMysql(?string $date, ?string $expected): void
    {
        $this->assertSame($expected, Service_Utils_Date::convertToMySQL($date));
    }

    public function convertToMySQLProvider(): array
    {
        return [
            'valid date dd/mm/yyyy' => ['25/12/2020', '2020-12-25'],
            'valid date yyyy-mm-dd' => ['2020-12-25', '2020-12-25'],
            'empty value' => ['', null],
            'no value' => [null, null],
        ];
    }

    /**
     * @dataProvider convertFromMySQLProvider
     */
    public function testConvertFromMysql(?string $date, ?string $expected): void
    {
        $this->assertSame($expected, Service_Utils_Date::convertFromMySQL($date));
    }

    public function convertFromMySQLProvider(): array
    {
        return [
            'valid date dd/mm/yyyy' => ['25/12/2020', '25/12/2020'],
            'valid date yyyy-mm-dd' => ['2020-12-25', '25/12/2020'],
            'empty value' => ['', null],
            'no value' => [null, null],
        ];
    }

    /**
     * @dataProvider formatDateWithDayNameProvider
     */
    public function testFormatDateWithDayName(?string $date, ?string $expected): void
    {
        $this->assertSame($expected, Service_Utils_Date::formatDateWithDayName($date));
    }

    public function formatDateWithDayNameProvider(): array
    {
        return [
            'valid date dd/mm/yyyy' => ['01/12/2020', 'mardi 1 déc. 2020'],
            'valid date yyyy-mm-dd' => ['2020-12-01', 'mardi 1 déc. 2020'],
            'empty value' => ['', null],
            'no value' => [null, null],
        ];
    }
}
