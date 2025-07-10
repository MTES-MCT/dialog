<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\MyArea\Organization\SigningAuthority\Fragments;

use App\Infrastructure\Persistence\Doctrine\Fixtures\OrganizationFixture;
use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;

final class GetSigningAuthorityFragmentControllerTest extends AbstractWebTestCase
{
    public function testGet(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/mon-espace/_fragment/organizations/' . OrganizationFixture::SEINE_SAINT_DENIS_ID . '/signing_authority/preview');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $this->assertSame('Monsieur le maire de Savenay', $crawler->filter('[data-testid="signing_authority_name"]')->text());
        $this->assertSame('Monsieur X (Adjoint au maire)', $crawler->filter('[data-testid="signing_authority_signatory_name"]')->text());
    }

    public function testOrganizationNotOwned(): void
    {
        $client = $this->login();
        $client->request('GET', '/mon-espace/_fragment/organizations/' . OrganizationFixture::REGION_IDF_ID . '/signing_authority/preview');
        $this->assertResponseStatusCodeSame(403);
    }

    public function testOrganizationOrUserNotFound(): void
    {
        $client = $this->login();
        $client->request('GET', '/mon-espace/_fragment/organizations/f5c1cea8-a61d-43a7-9b5d-4b8c9557c673/signing_authority/preview');
        $this->assertResponseStatusCodeSame(404);
    }

    public function testWithoutAuthenticatedUser(): void
    {
        $client = static::createClient();
        $client->request('GET', '/mon-espace/_fragment/organizations/' . OrganizationFixture::SEINE_SAINT_DENIS_ID . '/signing_authority/preview');
        $this->assertResponseRedirects('http://localhost/login', 302);
    }
}
