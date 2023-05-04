<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\Regulation\Fragments;

use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;

final class GetLocationControllerTest extends AbstractWebTestCase
{
    public function testGet(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/regulations/e413a47e-5928-4353-a8b2-8b7dda27f9a5/location/51449b82-5032-43c8-a427-46b9ddb44762');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $this->assertSame('Route du Grand Brossais', $crawler->filter('h3')->text());
        $this->assertSame('Savenay (44260)', $crawler->filter('li')->eq(0)->text());
        $this->assertSame('Route du Grand Brossais - du n° 15 au n° 37bis', $crawler->filter('li')->eq(1)->text());
        $this->assertSame('Circulation interdite', $crawler->filter('li')->eq(2)->text());
        $this->assertSame('http://localhost/_fragment/regulations/e413a47e-5928-4353-a8b2-8b7dda27f9a5/location/51449b82-5032-43c8-a427-46b9ddb44762/form', $crawler->filter('a')->link()->getUri());
    }

    public function testGetLocationFromOtherRegulationOrderRecord(): void
    {
        $client = $this->login();
        $client->request('GET', '/_fragment/regulations/4ce75a1f-82f3-40ee-8f95-48d0f04446aa/location/51449b82-5032-43c8-a427-46b9ddb44762');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testGetCityOnly(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/regulations/4ce75a1f-82f3-40ee-8f95-48d0f04446aa/location/f15ed802-fa9b-4d75-ab04-d62ea46597e9');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $this->assertSame('Paris 18e Arrondissement', $crawler->filter('h3')->text());
        $this->assertSame('Paris 18e Arrondissement (75018)', $crawler->filter('li')->eq(0)->text());
        $this->assertSame('Circulation interdite', $crawler->filter('li')->eq(1)->text());
        $this->assertSame('Circulation alternée', $crawler->filter('li')->eq(2)->text());
        $this->assertSame('http://localhost/_fragment/regulations/4ce75a1f-82f3-40ee-8f95-48d0f04446aa/location/f15ed802-fa9b-4d75-ab04-d62ea46597e9/form', $crawler->filter('a')->link()->getUri());
    }

    public function testIfPublishedThenCannotEdit(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/regulations/3ede8b1a-1816-4788-8510-e08f45511cb5/location/2d79e1ff-c991-4767-b8c0-36b644038d0f');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $this->assertSame(0, $crawler->filter('a')->count());
    }

    public function testRegulationDoesNotExist(): void
    {
        $client = $this->login();
        $client->request('GET', '/_fragment/regulations/3ede8b1a-1816-4788-8510-e08f45511aaa/location/f15ed802-fa9b-4d75-ab04-d62ea46597e9');
        $this->assertResponseStatusCodeSame(404);
    }

    public function testLocationDoesNotExist(): void
    {
        $client = $this->login();
        $client->request('GET', '/_fragment/regulations/e413a47e-5928-4353-a8b2-8b7dda27f9a5/location/3ede8b1a-1816-4788-8510-e08f45511aaa');
        $this->assertResponseStatusCodeSame(404);
    }

    public function testCannotAccessBecauseDifferentOrganization(): void
    {
        $client = $this->login('florimond.manca@beta.gouv.fr');
        $client->request('GET', '/_fragment/regulations/4ce75a1f-82f3-40ee-8f95-48d0f04446aa/location/f15ed802-fa9b-4d75-ab04-d62ea46597e9');
        $this->assertResponseStatusCodeSame(403);
    }

    public function testWithoutAuthenticatedUser(): void
    {
        $client = static::createClient();
        $client->request('GET', '/_fragment/regulations/4ce75a1f-82f3-40ee-8f95-48d0f04446aa/location/f15ed802-fa9b-4d75-ab04-d62ea46597e9');
        $this->assertResponseRedirects('http://localhost/login', 302);
    }

    public function testBadUuid(): void
    {
        $client = static::createClient();
        $client->request('GET', '/_fragment/regulations/aaa/location/bbb');
        $this->assertResponseStatusCodeSame(404);
    }
}
