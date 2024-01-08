<?php

declare(strict_types=1);

namespace App\Tests\Mock;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class IgnWfsMockClientTest extends KernelTestCase
{
    public function testDecoration(): void
    {
        $id = 'ign.wfs.client';
        self::assertTrue(self::getContainer()->has($id));
        self::assertTrue(self::getContainer()->has(IgnWfsMockClient::class . '::' . $id));
    }
}
