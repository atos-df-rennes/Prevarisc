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
    public function testConvertToMySQL(string $date, ?string $expected): void
    {
        $this->assertSame($expected, Service_Utils_Date::convertToMySQL($date));
    }

    public function convertToMySQLProvider(): array
    {
        return [
            'valid date' => ['25/12/2020', '2020-12-25'],
            'no value' => ['', null],
        ];
    }

    /**
     * @dataProvider convertFromMySQLProvider
     */
    public function testConvertFromMySQL(?string $date, ?string $expected): void
    {
        $this->assertSame($expected, Service_Utils_Date::convertFromMySQL($date));
    }

    public function convertFromMySQLProvider(): array
    {
        return [
            'valid date' => ['2020-12-25', '25/12/2020'],
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
            'valid date' => ['01/12/2020', 'mardi 1 déc. 2020'],
            'no value' => [null, null],
        ];
    }
}
