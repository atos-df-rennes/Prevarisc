<?php

use PHPUnit\Framework\TestCase;

/**
 * @covers \Service_Alerte
 *
 * @internal
 */
final class Service_AlerteTest extends TestCase
{
    /** @var Service_Alerte */
    private $alerte;

    protected function setUp(): void
    {
        $this->alerte = new Service_Alerte();
    }

    /**
     * @dataProvider linkProvider
     */
    public function testGetLink(int $idTypeChangement, ?int $idEtablissement, string $expected): void
    {
        $this->assertSame($expected, $this->alerte->getLink($idTypeChangement, $idEtablissement));
    }

    public function linkProvider(): array
    {
        return [
            'with establishment id' => [
                5,
                18450,
                '<a data-value="5" data-ets="18450" class="pull-right alerte-link"><i class="icon-bell icon-black"></i>Alerter</a>',
            ],
            'without establishment id' => [
                5,
                null,
                '<a data-value="5" class="pull-right alerte-link"><i class="icon-bell icon-black"></i>Alerter</a>',
            ],
        ];
    }
}
