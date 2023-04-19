<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\Regulation\Fragments;

use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;
use App\Tests\SessionHelper;

final class DeleteRegulationFragmentControllerTest extends AbstractWebTestCase
{
    use SessionHelper;

    public function testDeleteTemporary(): void
    {
        $client = $this->login();

        $crawler = $client->request('GET', '/regulations');
        $temporaryBeforeDelete = $crawler->filter('#total_temporary_regulation')->text();

        $crawler = $client->request('DELETE', '/_fragment/regulations/3ede8b1a-1816-4788-8510-e08f45511cb5/delete', [
            'token' => $this->generateCsrfToken($client, 'delete-regulation'),
            'tab' => 'temporary',
        ]);

        $streams = $crawler->filter('turbo-stream');

        $this->assertSame($streams->eq(0)->attr('target'), 'regulation_3ede8b1a-1816-4788-8510-e08f45511cb5');
        $this->assertSame($streams->eq(1)->attr('target'), 'total_temporary_regulation');

        $this->assertSame('Temporaires (2)', $streams->eq(1)->text());
        $this->assertSame('Temporaires (3)', $temporaryBeforeDelete);
    }

    public function testDeletePermanent(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/regulations');
        $permanentBeforeDelete = $crawler->filter('#total_permanent_regulation')->text();

        $crawler = $client->request('DELETE', '/_fragment/regulations/4ce75a1f-82f3-40ee-8f95-48d0f04446aa/delete', [
            'token' => $this->generateCsrfToken($client, 'delete-regulation'),
            'tab' => 'permanent',
        ]);

        $streams = $crawler->filter('turbo-stream');
        $this->assertSame($streams->eq(0)->attr('target'), 'regulation_4ce75a1f-82f3-40ee-8f95-48d0f04446aa');
        $this->assertSame($streams->eq(1)->attr('target'), 'total_permanent_regulation');

        $this->assertSame('Permanents (0)', $streams->eq(1)->text());
        $this->assertSame('Permanents (1)', $permanentBeforeDelete);
    }

    public function testCannotDeleteBecauseDifferentOrganization(): void
    {
        $client = $this->login('florimond.manca@beta.gouv.fr');
        $client->request('DELETE', '/_fragment/regulations/e413a47e-5928-4353-a8b2-8b7dda27f9a5/delete', [
            'token' => $this->generateCsrfToken($client, 'delete-regulation'),
        ]);
        $this->assertResponseStatusCodeSame(403);
    }

    public function testRegulationOrderRecordNotFound(): void
    {
        $client = $this->login();
        $client->request('DELETE', '/_fragment/regulations/547a5639-655a-41c3-9428-a5256b5a9e38/delete', [
            'token' => $this->generateCsrfToken($client, 'delete-regulation'),
        ]);
        $this->assertResponseRedirects('/regulations', 303);
    }

    public function testInvalidCsrfToken(): void
    {
        $client = $this->login();
        $client->request('DELETE', '/_fragment/regulations/e413a47e-5928-4353-a8b2-8b7dda27f9a5/delete');
        $this->assertResponseStatusCodeSame(400);
    }

    public function testWithoutAuthenticatedUser(): void
    {
        $client = static::createClient();
        $client->request('DELETE', '/_fragment/regulations/3ede8b1a-1816-4788-8510-e08f45511cb5/delete');
        $this->assertResponseRedirects('http://localhost/login', 302);
    }
}
