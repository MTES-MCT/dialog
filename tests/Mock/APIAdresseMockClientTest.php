<?php

declare(strict_types=1);

namespace App\Tests\Mock;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class APIAdresseMockClientTest extends KernelTestCase
{
    public function testDecoration(): void
    {
        $id = 'api.adresse.client';
        self::assertTrue(self::getContainer()->has($id));
        self::assertTrue(self::getContainer()->has(APIAdresseMockClient::class . '::' . $id));
    }
}
