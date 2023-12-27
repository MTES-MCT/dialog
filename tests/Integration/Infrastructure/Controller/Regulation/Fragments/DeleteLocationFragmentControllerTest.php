<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\Regulation\Fragments;

use App\Infrastructure\Persistence\Doctrine\Fixtures\LocationFixture;
use App\Infrastructure\Persistence\Doctrine\Fixtures\RegulationOrderRecordFixture;
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

        $crawler = $client->request('GET', '/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL);
        $this->assertSame(3, $this->countRows($crawler));

        $crawler = $client->request('DELETE', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/location/' . LocationFixture::UUID_TYPICAL . '/delete', [
            'token' => $this->generateCsrfToken($client, 'delete-location'),
        ]);
        $streams = $crawler->filter('turbo-stream');
        $this->assertSame($streams->eq(0)->attr('target'), 'block_location_' . LocationFixture::UUID_TYPICAL);
        $this->assertSame($streams->eq(0)->attr('action'), 'remove');

        $crawler = $client->request('GET', sprintf('/regulations/%s', RegulationOrderRecordFixture::UUID_TYPICAL));
        $this->assertSame(2, $this->countRows($crawler));
    }

    public function testLocationDoesntBelongsToRegulationOrder(): void
    {
        $client = $this->login();
        $client->request('DELETE', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/location/2d79e1ff-c991-4767-b8c0-36b644038d0f/delete', [
            'token' => $this->generateCsrfToken($client, 'delete-location'),
        ]);
        $this->assertResponseStatusCodeSame(403);
    }

    public function testLocationNotFound(): void
    {
        $client = $this->login();
        $client->request('DELETE', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/location/3c79e1ff-b000-0000-b8c0-00b644038d0f/delete', [
            'token' => $this->generateCsrfToken($client, 'delete-location'),
        ]);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testLocationCannotBeDeleted(): void
    {
        $client = $this->login();
        $client->request('DELETE', '/_fragment/regulations/4ce75a1f-82f3-40ee-8f95-48d0f04446aa/location/f15ed802-fa9b-4d75-ab04-d62ea46597e9/delete', [
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
        $client->request('DELETE', '/_fragment/regulations/aaa/location/' . LocationFixture::UUID_TYPICAL . '/delete', [
            'token' => $this->generateCsrfToken($client, 'delete-location'),
        ]);
        $this->assertResponseStatusCodeSame(400);
    }

    public function testBadLocationUuid(): void
    {
        $client = $this->login();
        $crawler = $client->request('DELETE', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/location/aaa/delete', [
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
