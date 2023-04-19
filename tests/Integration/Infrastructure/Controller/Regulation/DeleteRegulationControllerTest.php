<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\Regulation;

use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;
use App\Tests\SessionHelper;

final class DeleteRegulationControllerTest extends AbstractWebTestCase
{
    use SessionHelper;

    private function countRows($crawler)
    {
        $numTemporary = $crawler->filter('#temporary-panel tbody > tr:not([data-testid=empty-row])')->count();
        $numPermanent = $crawler->filter('#permanent-panel tbody > tr:not([data-testid=empty-row])')->count();

        return [$numTemporary, $numPermanent];
    }

    public function testDeleteTemporary(): void
    {
        $client = $this->login();

        $crawler = $client->request('GET', '/regulations');
        [$numTemporary, $numPermanent] = $this->countRows($crawler);

        $client->request('DELETE', '/regulations/3ede8b1a-1816-4788-8510-e08f45511cb5', [
            'token' => $this->generateCsrfToken($client, 'delete-regulation'),
        ]);
        $this->assertResponseRedirects('/regulations?tab=temporary', 303);
        $crawler = $client->followRedirect();
        // Doesn't appear in list of temporary regulations anymore.
        $this->assertEquals([$numTemporary - 1, $numPermanent], $this->countRows($crawler));
    }

    public function testDeletePermanent(): void
    {
        $client = $this->login();

        $client->request('GET', '/regulations/4ce75a1f-82f3-40ee-8f95-48d0f04446aa');
        $this->assertResponseStatusCodeSame(200);

        $crawler = $client->request('GET', '/regulations');
        [$numTemporary, $numPermanent] = $this->countRows($crawler);

        $client->request('DELETE', '/regulations/4ce75a1f-82f3-40ee-8f95-48d0f04446aa', [
            'token' => $this->generateCsrfToken($client, 'delete-regulation'),
        ]);
        $this->assertResponseRedirects('/regulations?tab=permanent', 303);
        $crawler = $client->followRedirect();

        // Doesn't appear in list of permanent regulations anymore.
        $this->assertSame([$numTemporary, $numPermanent - 1], $this->countRows($crawler), $crawler->html());

        // Detail page doesn't exist anymore.
        $client->request('GET', '/regulations/4ce75a1f-82f3-40ee-8f95-48d0f04446aa');
        $this->assertResponseStatusCodeSame(404);
    }

    public function testCannotDeleteBecauseDifferentOrganization(): void
    {
        $client = $this->login('florimond.manca@beta.gouv.fr');
        $client->request('DELETE', '/regulations/e413a47e-5928-4353-a8b2-8b7dda27f9a5', [
            'token' => $this->generateCsrfToken($client, 'delete-regulation'),
        ]);
        $this->assertResponseStatusCodeSame(403);
    }

    public function testRegulationOrderRecordNotFound(): void
    {
        $client = $this->login();
        $client->request('DELETE', '/regulations/547a5639-655a-41c3-9428-a5256b5a9e38', [
            'token' => $this->generateCsrfToken($client, 'delete-regulation'),
        ]);
        $this->assertResponseRedirects('/regulations?tab=temporary', 303);
    }

    public function testInvalidCsrfToken(): void
    {
        $client = $this->login();
        $client->request('DELETE', '/regulations/e413a47e-5928-4353-a8b2-8b7dda27f9a5');
        $this->assertResponseStatusCodeSame(400);
    }

    public function testWithoutAuthenticatedUser(): void
    {
        $client = static::createClient();
        $client->request('GET', '/regulations/3ede8b1a-1816-4788-8510-e08f45511cb5');
        $this->assertResponseRedirects('http://localhost/login', 302);
    }
}
