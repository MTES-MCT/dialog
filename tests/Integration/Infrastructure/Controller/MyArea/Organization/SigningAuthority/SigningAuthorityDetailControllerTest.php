<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\MyArea\Organization\SigningAuthority;

use App\Infrastructure\Persistence\Doctrine\Fixtures\OrganizationFixture;
use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;

final class SigningAuthorityDetailControllerTest extends AbstractWebTestCase
{
    public function testDetail(): void
    {
        $client = $this->login('mathieu.fernandez@beta.gouv.fr');
        $crawler = $client->request('GET', '/mon-espace/organizations/' . OrganizationFixture::MAIN_ORG_ID . '/signing_authority');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();
        $this->assertSame('Autorité signataire', $crawler->filter('h2')->text());
        $this->assertMetaTitle('Autorité signataire - DiaLog', $crawler);

        $this->assertSame($crawler->filter('[data-testid="signing_authority"] ul')->text(), 'Intitulé du signataire : Monsieur le maire de Savenay Adresse de l\'établissement : 3 rue de la Concertation 75018 Paris Fait à : Savenay Nom du signataire : Monsieur X, Maire de Savenay');
    }

    public function testNotAdministrator(): void
    {
        $client = $this->login();
        $client->request('GET', '/mon-espace/organizations/' . OrganizationFixture::MAIN_ORG_ID . '/signing_authority');
        $this->assertResponseStatusCodeSame(200);
    }

    public function testOrganizationNotOwned(): void
    {
        $client = $this->login();
        $client->request('GET', '/mon-espace/organizations/' . OrganizationFixture::OTHER_ORG_ID . '/signing_authority');
        $this->assertResponseStatusCodeSame(403);
    }

    public function testOrganizationOrUserNotFound(): void
    {
        $client = $this->login();
        $client->request('GET', '/mon-espace/organizations/f5c1cea8-a61d-43a7-9b5d-4b8c9557c673/signing_authority');
        $this->assertResponseStatusCodeSame(404);
    }

    public function testWithoutAuthenticatedUser(): void
    {
        $client = static::createClient();
        $client->request('GET', '/mon-espace/organizations/' . OrganizationFixture::MAIN_ORG_ID . '/signing_authority');
        $this->assertResponseRedirects('http://localhost/login', 302);
    }
}
