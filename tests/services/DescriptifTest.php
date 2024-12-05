<?php

use PHPUnit\Framework\TestCase;

/**
 * @covers \Service_Descriptif
 *
 * @internal
 */
final class Service_DescriptifTest extends TestCase
{
    /** @var Service_Descriptif */
    private $descriptif;

    protected function setUp(): void
    {
        $this->descriptif = new Service_Descriptif(
            'descriptifEtablissement',
            new Model_DbTable_DisplayRubriqueEtablissement(),
            new Service_RubriqueEtablissement()
        );
    }

    /**
     * @dataProvider initialListProvider
     */
    public function testGroupInputByOrder(array $initialList, array $expected): void
    {
        $this->assertSame($expected, $this->descriptif->groupInputByOrder($initialList, 10, 'Etablissement'));
    }

    /** TODO Cas à faire :
     * - ID_VALEUR à null
     * - Mauvais nombre d'arguments
     * - Idx à 0
     * - ID_PARENT à null.
     */
    public function initialListProvider(): array
    {
        return [
            'correct number of arguments and values' => [
                [
                    'valeur-3-155-197-90' => 'Texte 1',
                    'valeur-1-155-197-91' => 'Texte 2',
                    'valeur-2-155-197-92' => 'Texte 3',
                ],
                [
                    '155' => [
                        '1' => [
                            '197' => [
                                'VALEUR' => 'Texte 1',
                                'ID_VALEUR' => '90',
                            ],
                        ],
                        '2' => [
                            '197' => [
                                'VALEUR' => 'Texte 2',
                                'ID_VALEUR' => '91',
                            ],
                        ],
                        '3' => [
                            '197' => [
                                'VALEUR' => 'Texte 3',
                                'ID_VALEUR' => '92',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
