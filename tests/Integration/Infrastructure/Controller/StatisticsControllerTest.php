<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller;

final class StatisticsControllerTest extends AbstractWebTestCase
{
    public function testStatistics(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/statistics');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();
        $this->assertMetaTitle('Statistiques - DiaLog', $crawler);
        $this->assertSame('Statistiques', $crawler->filter('h1')->text());

        $stats = $crawler->filter('div.fr-card');
        $this->assertSame(6, $stats->count());

        $this->assertSame("Nombre total d'arrếtés 6", $stats->eq(2)->text());
        $this->assertSame("Nombre total d'arrếtés publiés 1", $stats->eq(3)->text());
        $this->assertSame("Nombre total d'arrếtés permanents 0", $stats->eq(4)->text());
        $this->assertSame("Nombre total d'arrếtés temporaires 1", $stats->eq(5)->text());
    }
}
