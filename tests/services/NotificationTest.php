<?php

use PHPUnit\Framework\TestCase;

/**
 * @covers \Service_Notification
 *
 * @internal
 */
final class Service_NotificationTest extends TestCase
{
    /** @var Service_Notification */
    private $notification;

    public function setUp(): void
    {
        $this->notification = new Service_Notification();
    }

    /**
     * @dataProvider sessionGetProvider
     */
    public function testGetLastPageVisitDate(string $sessionNamespace, string $expected): void
    {
        $session = new Zend_Session_Namespace('test');
        $session->date = '2024-09-19 16:40:30';

        $this->assertSame($expected, $this->notification->getLastPageVisitDate($sessionNamespace));
    }

    /**
     * @dataProvider isNewProvider
     */
    public function testIsNew(array $element, string $sessionNamespace, bool $expected): void
    {
        $session = new Zend_Session_Namespace('new');
        $session->date = '2024-09-19 16:40:30';

        $this->assertSame($expected, $this->notification->isNew($element, $sessionNamespace));
    }

    public function sessionGetProvider(): array
    {
        return [
            'with value set in session' => [
                'test',
                '2024-09-19 16:40:30',
            ],
        ];
    }

    public function isNewProvider(): array
    {
        return [
            'is new' => [
                ['DATE_NOTIFICATION' => (new DateTime('2024-09-19 17:00:00'))->format('Y-m-d H:i:s')],
                'new',
                true,
            ],
            'is not new' => [
                ['DATE_NOTIFICATION' => (new DateTime('2024-09-19 16:00:00'))->format('Y-m-d H:i:s')],
                'new',
                false,
            ],
            'has no notification date' => [
                ['DATE_NOTIFICATION' => null],
                'new',
                false,
            ],
        ];
    }
}
