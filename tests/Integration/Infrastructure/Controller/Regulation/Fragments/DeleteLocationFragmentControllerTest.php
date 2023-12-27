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
        $client->request('DELETE', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/location/' . LocationFixture::UUID_PUBLISHED . '/delete', [
            'token' => $this->generateCsrfToken($client, 'delete-location'),
        ]);
        $this->assertResponseStatusCodeSame(403);
    }

    public function testLocationNotFound(): void
    {
        $client = $this->login();
        $client->request('DELETE', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/location/' . LocationFixture::UUID_DOES_NOT_EXIST . '/delete', [
            'token' => $this->generateCsrfToken($client, 'delete-location'),
        ]);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testLocationCannotBeDeletedBecauseItIsTheOnlyOne(): void
    {
        $client = $this->login();
        $client->request('DELETE', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_ONLY_ONE_LOCATION . '/location/' . LocationFixture::UUID_PERMANENT_ONLY_ONE . '/delete', [
            'token' => $this->generateCsrfToken($client, 'delete-location'),
        ]);
        $this->assertResponseStatusCodeSame(400);
    }

    public function testRegulationOrderRecordNotFound(): void
    {
        $client = $this->login();
        $client->request('DELETE', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_DOES_NOT_EXIST . '/location/' . LocationFixture::UUID_TYPICAL . '/delete', [
            'token' => $this->generateCsrfToken($client, 'delete-location'),
        ]);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testInvalidCsrfToken(): void
    {
        $client = $this->login();
        $client->request('DELETE', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/location/' . LocationFixture::UUID_TYPICAL . '/delete');
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
        $client->request('DELETE', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/location/aaa/delete', [
            'token' => $this->generateCsrfToken($client, 'delete-location'),
        ]);
        $this->assertResponseStatusCodeSame(400);
    }

    public function testWithoutAuthenticatedUser(): void
    {
        $client = static::createClient();
        $client->request('DELETE', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/location/' . LocationFixture::UUID_TYPICAL . '/delete');
        $this->assertResponseRedirects('http://localhost/login', 302);
    }
}
