<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\Regulation;

use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;

final class RegulationDetailControllerTest extends AbstractWebTestCase
{
    public function testRegulationDetail(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/regulations/3ede8b1a-1816-4788-8510-e08f45511cb5');

        $this->assertSecurityHeaders();
        $this->assertResponseStatusCodeSame(200);
        $this->assertSame('Réglementation - Montauban', $crawler->filter('h2')->text());

        $step1 = $crawler->filter('div.for-what');
        $step2 = $crawler->filter('div.where');
        $step3 = $crawler->filter('div.when');
        $step4 = $crawler->filter('div.vehicles');

        // Step 1
        $this->assertSame('Description 2', $step1->filter('li')->eq(0)->text());
        $this->assertSame('Circulation interdite', $step1->filter('li')->eq(1)->text());

        // Step 2
        $this->assertSame('Ville : 82000 Montauban', $step2->filter('li')->eq(0)->text());
        $this->assertSame('Rue : du 695 au 253, Avenue de Fonneuve', $step2->filter('li')->eq(1)->text());

        // Step 3
        $this->assertSame('à partir du 08/10/2022', $step3->filter('li')->eq(0)->text());

        // Step 4
        $this->assertCount(0, $step4->filter('li'));
    }

    public function testRegulationOrderRecordNotFound(): void
    {
        $client = $this->login();
        $client->request('GET', '/regulations/c1beed9a-6ec1-417a-abfd-0b5bd245616b');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testWithoutAuthenticatedUser(): void
    {
        $client = static::createClient();
        $client->request('GET', '/regulations/c1beed9a-6ec1-417a-abfd-0b5bd245616b');

        $this->assertResponseRedirects('http://localhost/login', 302);
    }
}
