<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class Step5ControllerTest extends WebTestCase
{
    public function testSummary(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/regulations/form/e413a47e-5928-4353-a8b2-8b7dda27f9a5/5');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSame('Étape 5 sur 5 Récapitulatif', $crawler->filter('h2')->text());
    }
}
