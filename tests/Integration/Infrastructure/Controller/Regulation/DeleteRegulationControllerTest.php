<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\Regulation;

use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;

final class DeleteRegulationControllerTest extends AbstractWebTestCase
{
    private function countRows($crawler) {
        $numDrafts = $crawler->filter("#draft-panel tbody > tr:not([data-testid=empty-row])")->count();
        $numPublished = $crawler->filter("#published-panel tbody > tr:not([data-testid=empty-row])")->count();
        return [$numDrafts, $numPublished];
    }

    public function testDeleteDraft(): void
    {
        $client = $this->login();

        $crawler = $client->request('GET', '/regulations');
        [$numDrafts, $numPublished] = $this->countRows($crawler);

        $client->request('DELETE', '/regulations/e413a47e-5928-4353-a8b2-8b7dda27f9a5');
        $this->assertResponseRedirects('/regulations?tab=draft', 303);
        $crawler = $client->followRedirect();
        // Doesn't appear in list of drafts anymore.
        $this->assertEquals([$numDrafts - 1, $numPublished], $this->countRows($crawler));
    }

    public function testDeletePublished(): void
    {
        $client = $this->login();

        $client->request('GET', '/regulations/3ede8b1a-1816-4788-8510-e08f45511cb5');
        $this->assertResponseStatusCodeSame(200);

        $crawler = $client->request('GET', '/regulations');
        [$numDrafts, $numPublished] = $this->countRows($crawler);

        $client->request('DELETE', '/regulations/3ede8b1a-1816-4788-8510-e08f45511cb5');
        $this->assertResponseRedirects('/regulations?tab=published', 303);
        $crawler = $client->followRedirect();

        // Doesn't appear in list of published regulations anymore.
        $this->assertSame([$numDrafts, $numPublished - 1], $this->countRows($crawler), $crawler->html());

        // Detail page doesn't exist anymore.
        $client->request('GET', '/regulations/3ede8b1a-1816-4788-8510-e08f45511cb5');
        $this->assertResponseStatusCodeSame(404);
    }

    public function testCannotDeleteBecauseDifferentOrganization(): void
    {
        $client = $this->login('florimond.manca@beta.gouv.fr');
        $client->request('DELETE', '/regulations/e413a47e-5928-4353-a8b2-8b7dda27f9a5');
        $this->assertResponseStatusCodeSame(403);
    }

    public function testRegulationOrderRecordNotFound(): void
    {
        $client = $this->login();
        $client->request('DELETE', '/regulations/547a5639-655a-41c3-9428-a5256b5a9e38');
        $this->assertResponseRedirects('/regulations?tab=draft', 303);
    }

    public function testWithoutAuthenticatedUser(): void
    {
        $client = static::createClient();
        $client->request('GET', '/regulations/3ede8b1a-1816-4788-8510-e08f45511cb5');
        $this->assertResponseRedirects('http://localhost/login', 302);
    }
}
