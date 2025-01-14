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

        $dashboard = $crawler->filter('[data-testid=dashboard]')->eq(0);
        $this->assertSame('Tableau de bord des indicateurs', $dashboard->attr('title'));
    }
}
