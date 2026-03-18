<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\MyArea\Organization\ApiClient;

use App\Infrastructure\Persistence\Doctrine\Fixtures\OrganizationFixture;
use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;

final class ListApiClientsControllerTest extends AbstractWebTestCase
{
    public function testListApiClientsPage(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/mon-espace/organizations/' . OrganizationFixture::SEINE_SAINT_DENIS_ID . '/api-clients');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();
        $this->assertSame('Clés API', $crawler->filter('h3')->text());
        $this->assertMetaTitle('Clés API - DiaLog', $crawler);
        $this->assertSelectorExists('a[href*="/api-clients/create"]');
        $this->assertSelectorTextContains('table', 'clientId');
    }

    public function testOrganizationNotOwned(): void
    {
        $client = $this->login();
        $client->request('GET', '/mon-espace/organizations/' . OrganizationFixture::REGION_IDF_ID . '/api-clients');
        $this->assertResponseStatusCodeSame(403);
    }

    public function testWithoutAuthenticatedUser(): void
    {
        $client = static::createClient();
        $client->request('GET', '/mon-espace/organizations/' . OrganizationFixture::SEINE_SAINT_DENIS_ID . '/api-clients');
        $this->assertResponseRedirects('http://localhost/login', 302);
    }
}
