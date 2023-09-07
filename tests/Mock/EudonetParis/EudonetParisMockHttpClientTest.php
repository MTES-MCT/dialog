<?php

declare(strict_types=1);

namespace App\Tests\Mock\EudonetParis;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class EudonetParisMockHttpClientTest extends KernelTestCase
{
    public function testDecoration(): void
    {
        $id = 'eudonet_paris.http.client';
        self::assertTrue(self::getContainer()->has($id));
        self::assertTrue(self::getContainer()->has(EudonetParisMockHttpClient::class . '::' . $id));
    }
}
