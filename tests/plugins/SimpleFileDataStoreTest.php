<?php

use PHPUnit\Framework\TestCase;

/**
 * @covers \Plugin_SimpleFileDataStore
 *
 * @internal
 */
final class Plugin_SimpleFileDataStoreTest extends TestCase
{
    /** @var Plugin_SimpleFileDataStore */
    private $dataStore;

    protected function setUp(): void
    {
        $this->dataStore = new Plugin_SimpleFileDataStore();
    }

    /**
     * @dataProvider basePathProvider
     */
    public function testGetBasePath(string $type, string $expected): void
    {
        $this->assertSame($expected, $this->dataStore->getBasePath(null, $type, null));
    }

    /**
     * @dataProvider filePathProvider
     */
    public function testGetFilePath(array $pieceJointe, string $type, string $id, string $expected): void
    {
        $this->assertSame($expected, $this->dataStore->getFilePath($pieceJointe, $type, $id));
    }

    public function basePathProvider(): array
    {
        return [
            'internal type' => ['avatar', REAL_DATA_PATH.DS.'uploads'.DS.'avatars'],
            'external type' => ['test', REAL_DATA_PATH.DS.'uploads'.DS.'test'],
        ];
    }

    public function filePathProvider(): array
    {
        return [
            'internal type' => [
                ['ID_PIECEJOINTE' => 10, 'EXTENSION_PIECEJOINTE' => '.odt'],
                'avatar',
                5,
                REAL_DATA_PATH.DS.'uploads'.DS.'avatars'.DS.'10.odt',
            ],
            'external type' => [
                ['ID_PIECEJOINTE' => 10, 'EXTENSION_PIECEJOINTE' => '.odt'],
                'test',
                5,
                REAL_DATA_PATH.DS.'uploads'.DS.'test'.DS.'10.odt',
            ],
        ];
    }
}
