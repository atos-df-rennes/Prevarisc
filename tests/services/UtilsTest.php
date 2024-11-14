<?php

use PHPUnit\Framework\TestCase;

/**
 * @covers \Service_Utils
 *
 * @internal
 */
final class Service_UtilsTest extends TestCase
{
    /** @var Service_Utils */
    private $utils;

    protected function setUp(): void
    {
        $this->utils = new Service_Utils();
    }

    /**
     * @dataProvider fusionNameProvider
     */
    public function testGetDescriptifFieldFusionName(string $baseObject, array $options, string $expected): void
    {
        $this->assertSame($expected, $this->utils->getFullFusionName($baseObject, $options));
    }

    /**
     * @dataProvider magicalNameProvider
     */
    public function testGetDescriptifFieldMagicalName(string $initialName, string $expected): void
    {
        $this->assertSame($expected, $this->utils->getFusionNameMagicalCase($initialName));
    }

    /**
     * @dataProvider pjProvider
     */
    public function testGetPjPath(array $pjData, string $expected): void
    {
        $this->assertSame($expected, $this->utils::getPjPath($pjData));
    }

    public function fusionNameProvider(): array
    {
        return [
            'simple field with rubrique' => ['descriptifEtablissement', ['Ma Rubrique', 'Mon Champ'], 'descriptifEtablissement-ma_rubrique-mon_champ'],
            'formatted field without rubrique' => ['descriptifEtablissement', ['Mon Champ d\'enfer "Personnalisé"'], 'descriptifEtablissement-mon_champ_d_enfer__personnalisé_'],
            'field with leading and trailing spaces' => ['descriptifEtablissement', ['  Mon champ avec des espaces '], 'descriptifEtablissement-mon_champ_avec_des_espaces'],
        ];
    }

    public function magicalNameProvider(): array
    {
        return [
            'simple field' => ['descriptif Rubrique Un champ', 'DescriptifRubriqueUnChamp'],
            'formatted field' => ['descriptif rUbrique un Champ d\'enfer "Personnalisé"', 'DescriptifRubriqueUnChampDEnferPersonnalisé'],
            'field with leading and trailing spaces' => ['descriptif Rubrique   Un champ avec des espaces ', 'DescriptifRubriqueUnChampAvecDesEspaces'],
        ];
    }

    public function pjProvider(): array
    {
        return [
            'pj platau' => [
                [
                    'ID_PIECEJOINTE' => 10,
                    'EXTENSION_PIECEJOINTE' => '.odt',
                    'ID_DOSSIER' => 5,
                    'ID_PLATAU' => '7WO-QPQ-680',
                ],
                REAL_DATA_PATH.DS.'uploads/pieces-jointes/10.odt',
            ],
        ];
    }
}
