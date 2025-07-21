<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\MyArea\Organization\Fragments;

use App\Infrastructure\Persistence\Doctrine\Fixtures\OrganizationFixture;
use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;

final class GetOrganizationDetailFragmentControllerTest extends AbstractWebTestCase
{
    public function testGet(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/mon-espace/_fragment/organizations/' . OrganizationFixture::SEINE_SAINT_DENIS_ID . '/preview');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $this->assertSame('Département de Seine-Saint-Denis', $crawler->filter('[data-testid="organization_name"]')->text());
        $this->assertSame('22930008201453', $crawler->filter('[data-testid="organization_siret"]')->text());
    }

    public function testOrganizationNotOwned(): void
    {
        $client = $this->login();
        $client->request('GET', '/mon-espace/_fragment/organizations/' . OrganizationFixture::REGION_IDF_ID . '/preview');
        $this->assertResponseStatusCodeSame(403);
    }

    public function testOrganizationOrUserNotFound(): void
    {
        $client = $this->login();
        $client->request('GET', '/mon-espace/_fragment/organizations/f5c1cea8-a61d-43a7-9b5d-4b8c9557c673/preview');
        $this->assertResponseStatusCodeSame(404);
    }

    public function testWithoutAuthenticatedUser(): void
    {
        $client = static::createClient();
        $client->request('GET', '/mon-espace/_fragment/organizations/' . OrganizationFixture::SEINE_SAINT_DENIS_ID . '/preview');
        $this->assertResponseRedirects('http://localhost/login', 302);
    }
}
