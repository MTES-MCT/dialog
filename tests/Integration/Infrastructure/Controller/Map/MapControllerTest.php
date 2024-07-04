<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\Map;

use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;

final class MapControllerTest extends AbstractWebTestCase
{
    public function testGet(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/carte');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();
        $this->assertMetaTitle('Carte - DiaLog', $crawler);
    }
}
