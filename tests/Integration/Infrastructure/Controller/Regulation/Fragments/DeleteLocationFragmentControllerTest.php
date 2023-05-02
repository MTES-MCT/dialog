<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\Regulation\Fragments;

use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;
use App\Tests\SessionHelper;

final class DeleteLocationFragmentControllerTest extends AbstractWebTestCase
{
    use SessionHelper;

    private function countRows($crawler): int
    {
        return $crawler->filter('#location_list > li')->count();
    }

    public function testDelete(): void
    {
        $client = $this->login();

        $crawler = $client->request('GET', '/regulations/e413a47e-5928-4353-a8b2-8b7dda27f9a5');
        $this->assertSame(3, $this->countRows($crawler));

        $crawler = $client->request('DELETE', '/_fragment/regulations/e413a47e-5928-4353-a8b2-8b7dda27f9a5/location/34247125-38f4-4e69-b5d7-5516a577d149/delete', [
            'token' => $this->generateCsrfToken($client, 'delete-location'),
        ]);
        $streams = $crawler->filter('turbo-stream');
        $this->assertSame($streams->eq(0)->attr('target'), 'block_location_34247125-38f4-4e69-b5d7-5516a577d149');
        $this->assertSame($streams->eq(0)->attr('action'), 'remove');

        $crawler = $client->request('GET', '/regulations/e413a47e-5928-4353-a8b2-8b7dda27f9a5');
        $this->assertSame(2, $this->countRows($crawler));
    }

    public function testLocationDoesntBelongsToRegulationOrder(): void
    {
        $client = $this->login();
        $client->request('DELETE', '/_fragment/regulations/e413a47e-5928-4353-a8b2-8b7dda27f9a5/location/2d79e1ff-c991-4767-b8c0-36b644038d0f/delete', [
            'token' => $this->generateCsrfToken($client, 'delete-location'),
        ]);
        $this->assertResponseStatusCodeSame(403);
    }

    public function testLocationNotFound(): void
    {
        $client = $this->login();
        $client->request('DELETE', '/_fragment/regulations/e413a47e-5928-4353-a8b2-8b7dda27f9a5/location/3c79e1ff-b000-0000-b8c0-00b644038d0f/delete', [
            'token' => $this->generateCsrfToken($client, 'delete-location'),
        ]);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testLocationCannotBeDeleted(): void
    {
        $client = $this->login();
        $client->request('DELETE', '/_fragment/regulations/3ede8b1a-1816-4788-8510-e08f45511cb5/location/2d79e1ff-c991-4767-b8c0-36b644038d0f/delete', [
            'token' => $this->generateCsrfToken($client, 'delete-location'),
        ]);
        $this->assertResponseStatusCodeSame(400);
    }

    public function testRegulationOrderRecordNotFound(): void
    {
        $client = $this->login();
        $client->request('DELETE', '/_fragment/regulations/aede8b1a-2020-5788-8510-f08f45511cb5/location/2d79e1ff-c991-4767-b8c0-36b644038d0f/delete', [
            'token' => $this->generateCsrfToken($client, 'delete-location'),
        ]);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testInvalidCsrfToken(): void
    {
        $client = $this->login();
        $client->request('DELETE', '/_fragment/regulations/3ede8b1a-1816-4788-8510-e08f45511cb5/location/2d79e1ff-c991-4767-b8c0-36b644038d0f/delete');
        $this->assertResponseStatusCodeSame(400);
    }

    public function testBadRegulationOrderUuid(): void
    {
        $client = $this->login();
        $client->request('DELETE', '/_fragment/regulations/aaa/location/34247125-38f4-4e69-b5d7-5516a577d149/delete', [
            'token' => $this->generateCsrfToken($client, 'delete-location'),
        ]);
        $this->assertResponseStatusCodeSame(400);
    }

    public function testBadLocationUuid(): void
    {
        $client = $this->login();
        $crawler = $client->request('DELETE', '/_fragment/regulations/e413a47e-5928-4353-a8b2-8b7dda27f9a5/location/aaa/delete', [
            'token' => $this->generateCsrfToken($client, 'delete-location'),
        ]);
        $this->assertResponseStatusCodeSame(400);
    }

    public function testWithoutAuthenticatedUser(): void
    {
        $client = static::createClient();
        $client->request('DELETE', '/_fragment/regulations/3ede8b1a-1816-4788-8510-e08f45511cb5/location/2d79e1ff-c991-4767-b8c0-36b644038d0f/delete');
        $this->assertResponseRedirects('http://localhost/login', 302);
    }
}
