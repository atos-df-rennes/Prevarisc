<?php

use PHPUnit\Framework\TestCase;

/**
 * @covers \Service_TextesApplicables
 *
 * @internal
 */
final class Service_TextesApplicablesTest extends TestCase
{
    /** @var Service_TextesApplicables */
    private $textesApplicables;

    protected function setUp(): void
    {
        $this->textesApplicables = new Service_TextesApplicables();
    }

    /**
     * @dataProvider textesApplicablesProvider
     */
    public function testReorganizeApplicableTexts(array $unorganizedTexts, array $expected): void
    {
        $this->assertSame($expected, $this->textesApplicables->organize($unorganizedTexts));
    }

    public function textesApplicablesProvider(): array
    {
        return [
            'existing values' => [
                [
                    0 => [
                        'ID_TEXTESAPPL' => 2,
                        'LIBELLE_TEXTESAPPL' => 'Décret n°95 du 23 mars 1974',
                        'ID_TYPETEXTEAPPL' => 1,
                        'LIBELLE_TYPETEXTEAPPL' => 'Dispositions générales',
                    ],
                    1 => [
                        'ID_TEXTESAPPL' => 4,
                        'LIBELLE_TEXTESAPPL' => 'Décret n°105 du 15 avril 1945',
                        'ID_TYPETEXTEAPPL' => 2,
                        'LIBELLE_TYPETEXTEAPPL' => 'Rappel Réglementaire',
                    ],
                    2 => [
                        'ID_TEXTESAPPL' => 6,
                        'LIBELLE_TEXTESAPPL' => 'Décret n°61 du 09 janvier 1984',
                        'ID_TYPETEXTEAPPL' => 3,
                        'LIBELLE_TYPETEXTEAPPL' => "Liés à l'amélioration",
                    ],
                    3 => [
                        'ID_TEXTESAPPL' => 8,
                        'LIBELLE_TEXTESAPPL' => 'Décret n°17 du 31 juillet 2010',
                        'ID_TYPETEXTEAPPL' => 1,
                        'LIBELLE_TYPETEXTEAPPL' => 'Dispositions générales',
                    ],
                    4 => [
                        'ID_TEXTESAPPL' => 10,
                        'LIBELLE_TEXTESAPPL' => 'Décret n°04 du 27 février 1967',
                        'ID_TYPETEXTEAPPL' => 3,
                        'LIBELLE_TYPETEXTEAPPL' => "Liés à l'amélioration",
                    ],
                ],
                [
                    'Dispositions générales' => [
                        2 => [
                            'ID_TEXTESAPPL' => 2,
                            'LIBELLE_TEXTESAPPL' => 'Décret n°95 du 23 mars 1974',
                        ],
                        8 => [
                            'ID_TEXTESAPPL' => 8,
                            'LIBELLE_TEXTESAPPL' => 'Décret n°17 du 31 juillet 2010',
                        ],
                    ],
                    'Rappel Réglementaire' => [
                        4 => [
                            'ID_TEXTESAPPL' => 4,
                            'LIBELLE_TEXTESAPPL' => 'Décret n°105 du 15 avril 1945',
                        ],
                    ],
                    "Liés à l'amélioration" => [
                        6 => [
                            'ID_TEXTESAPPL' => 6,
                            'LIBELLE_TEXTESAPPL' => 'Décret n°61 du 09 janvier 1984',
                        ],
                        10 => [
                            'ID_TEXTESAPPL' => 10,
                            'LIBELLE_TEXTESAPPL' => 'Décret n°04 du 27 février 1967',
                        ],
                    ],
                ],
            ],
        ];
    }
}
