<?php

use PHPUnit\Framework\TestCase;

/**
 * @covers \Service_PassAuthAdapater
 *
 * @internal
 */
final class Service_PassAuthAdapterTest extends TestCase
{
    public function testAuthenticationSuccess(): void
    {
        $this->assertSame(1, (new Service_PassAuthAdapater('username'))->authenticate()->getCode());
    }
}
