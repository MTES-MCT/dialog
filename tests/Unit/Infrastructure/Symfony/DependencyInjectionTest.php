<?php

declare(strict_types=1);

namespace App\Test\Unit\Infrastructure\Symfony;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class DependencyInjectionTest extends KernelTestCase
{
    public function testTimezoneParameters(): void
    {
        $this->assertSame('UTC', $this->getContainer()->getParameter('server_timezone'));
        $this->assertSame('Etc/GMT-1', $this->getContainer()->getParameter('client_timezone'));
    }
}
