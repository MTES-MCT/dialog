<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\Regulation\Fragments;

use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;

final class GetRegulationLocationControllerTest extends AbstractWebTestCase
{
    public function testGet(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/regulations/location/e413a47e-5928-4353-a8b2-8b7dda27f9a5');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $this->assertSame('Route du Grand Brossais', $crawler->filter('h3')->text());
        $this->assertSame('Savenay (44260)', $crawler->filter('li')->eq(0)->text());
        $this->assertSame('Route du Grand Brossais - du n° 15 au n° 37bis', $crawler->filter('li')->eq(1)->text());
        $this->assertSame('Circulation interdite pour tous les véhicules', $crawler->filter('li')->eq(2)->text());
        $this->assertSame('http://localhost/_fragment/regulations/location/form/e413a47e-5928-4353-a8b2-8b7dda27f9a5', $crawler->filter('a')->link()->getUri());
    }

    public function testGetPublished(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/regulations/location/3ede8b1a-1816-4788-8510-e08f45511cb5');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $this->assertSame(0, $crawler->filter('a')->count()); // Cannot edit
    }

    public function testRegulationDoesNotExist(): void
    {
        $client = $this->login();
        $client->request('GET', '/_fragment/regulations/location/3ede8b1a-1816-4788-8510-e08f45511aaa');
        $this->assertResponseStatusCodeSame(404);
    }

    public function testCannotAccessBecauseDifferentOrganization(): void
    {
        $client = $this->login('florimond.manca@beta.gouv.fr');
        $client->request('GET', '/_fragment/regulations/location/3ede8b1a-1816-4788-8510-e08f45511cb5');
        $this->assertResponseStatusCodeSame(403);
    }

    public function testWithoutAuthenticatedUser(): void
    {
        $client = static::createClient();
        $client->request('GET', '/_fragment/regulations/location/4ce75a1f-82f3-40ee-8f95-48d0f04446aa');
        $this->assertResponseRedirects('http://localhost/login', 302);
    }
}
