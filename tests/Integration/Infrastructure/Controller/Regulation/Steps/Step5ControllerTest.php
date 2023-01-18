<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class Step5ControllerTest extends WebTestCase
{
    public function testSave(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/regulations/form/e413a47e-5928-4353-a8b2-8b7dda27f9a5/5');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSame('Étape 5 sur 5 Récapitulatif', $crawler->filter('h2')->text());

        $client->clickLink('Sauvegarder');
        $this->assertResponseStatusCodeSame(200);
        $this->assertRouteSame('app_regulations_list');
    }

    public function testPrevious(): void
    {
        $client = static::createClient();
        $client->request('GET', '/regulations/form/e413a47e-5928-4353-a8b2-8b7dda27f9a5/5');
        $this->assertResponseStatusCodeSame(200);

        $client->clickLink('Précédent');
        $this->assertResponseStatusCodeSame(200);
        $this->assertRouteSame('app_regulations_steps_4', ['uuid' => 'e413a47e-5928-4353-a8b2-8b7dda27f9a5']);
    }

    public function testRegulationOrderRecordNotFound(): void
    {
        $client = static::createClient();
        $client->request('GET', '/regulations/form/c1beed9a-6ec1-417a-abfd-0b5bd245616b/5');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testBadUuid(): void
    {
        $client = static::createClient();
        $client->request('GET', '/regulations/form/aaaaaaaa/5');

        $this->assertResponseStatusCodeSame(400);
    }
}
