<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller;

final class StatisticsControllerTest extends AbstractWebTestCase
{
    public function testStatistics(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/stats');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();
        $this->assertMetaTitle('Statistiques - DiaLog', $crawler);
        $this->assertSame('Statistiques', $crawler->filter('h1')->text());

        $stats = $crawler->filter('[data-testid=stat]');
        $this->assertSame(7, $stats->count());

        $this->assertSame('Tableau de bord des indicateurs', $stats->eq(0)->attr('title'));
        $this->assertSame("Nombre total d'utilisateurs 3", $stats->eq(1)->text());
        $this->assertSame("Nombre total d'organisations 2", $stats->eq(2)->text());
        $this->assertSame("Nombre total d'arrếtés 1", $stats->eq(3)->text());
        $this->assertSame("Nombre total d'arrếtés publiés 1", $stats->eq(4)->text());
        $this->assertSame("Nombre total d'arrếtés permanents 0", $stats->eq(5)->text());
        $this->assertSame("Nombre total d'arrếtés temporaires 1", $stats->eq(6)->text());
    }
}
