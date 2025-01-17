<?php

use PHPUnit\Framework\TestCase;

/**
 * @covers \Service_Dossier
 *
 * @internal
 */
final class Service_DossierTest extends TestCase
{
    /** @var Service_Dossier */
    private $dossier;

    protected function setUp(): void
    {
        $this->dossier = new Service_Dossier();
    }

    /**
     * @dataProvider prescriptionsProvider
     */
    public function testGetPrescriptionsWithoutLevees(array $prescriptions, array $expected): void
    {
        $this->assertSame($expected, $this->dossier->withoutLevees($prescriptions));
    }

    /**
     * @dataProvider prescriptionsActualsProvider
     */
    public function testGetPrescriptionsWithoutActuals(array $prescriptions, array $expected): void
    {
        $this->assertSame($expected, $this->dossier->withoutActuals($prescriptions));
    }

    /**
     * @dataProvider prescriptionsPreviousProvider
     */
    public function testGetPrescriptionsWithoutPrevious(array $prescriptions, array $expected): void
    {
        $this->assertSame($expected, $this->dossier->withoutPrevious($prescriptions));
    }

    public function prescriptionsProvider(): array
    {
        return [
            'no levees' => [
                [
                    [
                        ['ID_PRESCRIPTION' => 10, 'DATE_LEVEE' => null],
                        ['ID_PRESCRIPTION' => 15, 'DATE_LEVEE' => null],
                    ],
                ],
                [
                    [
                        ['ID_PRESCRIPTION' => 10, 'DATE_LEVEE' => null],
                        ['ID_PRESCRIPTION' => 15, 'DATE_LEVEE' => null],
                    ],
                ],
            ],
            'some levees' => [
                [
                    [
                        ['ID_PRESCRIPTION' => 10, 'DATE_LEVEE' => null],
                        ['ID_PRESCRIPTION' => 15, 'DATE_LEVEE' => '2025-01-17'],
                    ],
                ],
                [
                    [
                        ['ID_PRESCRIPTION' => 10, 'DATE_LEVEE' => null],
                    ],
                ],
            ],
            'all levees' => [
                [
                    [
                        ['ID_PRESCRIPTION' => 10, 'DATE_LEVEE' => '2025-01-17'],
                        ['ID_PRESCRIPTION' => 15, 'DATE_LEVEE' => '2025-01-17'],
                    ],
                ],
                [],
            ],
        ];
    }

    public function prescriptionsActualsProvider(): array
    {
        return [
            'no actuals' => [
                [
                    [
                        ['ID_PRESCRIPTION' => 10, 'ID_DOSSIER_REPRISE' => 5],
                        ['ID_PRESCRIPTION' => 15, 'ID_DOSSIER_REPRISE' => 10],
                    ],
                ],
                [
                    [
                        ['ID_PRESCRIPTION' => 10, 'ID_DOSSIER_REPRISE' => 5],
                        ['ID_PRESCRIPTION' => 15, 'ID_DOSSIER_REPRISE' => 10],
                    ],
                ],
            ],
            'some actuals' => [
                [
                    [
                        ['ID_PRESCRIPTION' => 10, 'ID_DOSSIER_REPRISE' => null],
                        ['ID_PRESCRIPTION' => 15, 'ID_DOSSIER_REPRISE' => 10],
                    ],
                ],
                [
                    [
                        1 => ['ID_PRESCRIPTION' => 15, 'ID_DOSSIER_REPRISE' => 10],
                    ],
                ],
            ],
            'all actuals' => [
                [
                    [
                        ['ID_PRESCRIPTION' => 10, 'ID_DOSSIER_REPRISE' => null],
                        ['ID_PRESCRIPTION' => 15, 'ID_DOSSIER_REPRISE' => null],
                    ],
                ],
                [],
            ],
        ];
    }

    public function prescriptionsPreviousProvider(): array
    {
        return [
            'no previous' => [
                [
                    [
                        ['ID_PRESCRIPTION' => 10, 'ID_DOSSIER_REPRISE' => null],
                        ['ID_PRESCRIPTION' => 15, 'ID_DOSSIER_REPRISE' => null],
                    ],
                ],
                [
                    [
                        ['ID_PRESCRIPTION' => 10, 'ID_DOSSIER_REPRISE' => null],
                        ['ID_PRESCRIPTION' => 15, 'ID_DOSSIER_REPRISE' => null],
                    ],
                ],
            ],
            'some previous' => [
                [
                    [
                        ['ID_PRESCRIPTION' => 10, 'ID_DOSSIER_REPRISE' => null],
                        ['ID_PRESCRIPTION' => 15, 'ID_DOSSIER_REPRISE' => 10],
                    ],
                ],
                [
                    [
                        ['ID_PRESCRIPTION' => 10, 'ID_DOSSIER_REPRISE' => null],
                    ],
                ],
            ],
            'all previous' => [
                [
                    [
                        ['ID_PRESCRIPTION' => 10, 'ID_DOSSIER_REPRISE' => 5],
                        ['ID_PRESCRIPTION' => 15, 'ID_DOSSIER_REPRISE' => 10],
                    ],
                ],
                [],
            ],
        ];
    }
}
