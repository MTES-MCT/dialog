<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\MyArea\Organization\User;

use App\Infrastructure\Persistence\Doctrine\Fixtures\OrganizationFixture;
use App\Infrastructure\Persistence\Doctrine\Fixtures\UserFixture;
use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;

final class ListUsersControllerTest extends AbstractWebTestCase
{
    public function testIndexAsAdmin(): void
    {
        $client = $this->login(UserFixture::MAIN_ORG_ADMIN_EMAIL);
        $crawler = $client->request('GET', '/mon-espace/organizations/' . OrganizationFixture::MAIN_ORG_ID . '/users');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();
        $this->assertSame('Utilisateurs', $crawler->filter('h2')->text());
        $this->assertMetaTitle('Utilisateurs - DiaLog', $crawler);

        $users = $crawler->filter('[data-testid="user-list"]');
        $tr0 = $users->filter('tr')->eq(0)->filter('td');
        $tr1 = $users->filter('tr')->eq(1)->filter('td');
        $tr2 = $users->filter('tr')->eq(2)->filter('td');
        $this->assertCount(3, $users->filter('tr'));

        // Invitation qui va permettre de valider le fait qu'un compte existe déjà
        $this->assertSame('Mathieu MARCHOIS En attente d\'activation', $tr0->eq(0)->text());
        $this->assertSame('mathieu.marchois@beta.gouv.fr', $tr0->eq(1)->text());
        $this->assertSame('Contributeur', $tr0->eq(2)->text());

        $this->assertSame('Mathieu FERNANDEZ', $tr1->eq(0)->text());
        $this->assertSame('mathieu.fernandez@beta.gouv.fr', $tr1->eq(1)->text());
        $this->assertSame('Administrateur', $tr1->eq(2)->text());

        $this->assertSame('Mathieu MARCHOIS', $tr2->eq(0)->text());
        $this->assertSame('mathieu.marchois@beta.gouv.fr', $tr2->eq(1)->text());
        $this->assertSame('Contributeur', $tr2->eq(2)->text());
        $this->assertSame('Modifier', $tr2->eq(3)->filter('a')->text());
        $this->assertSame('http://localhost/mon-espace/organizations/e0d93630-acf7-4722-81e8-ff7d5fa64b66/users/0b507871-8b5e-4575-b297-a630310fc06e/edit', $tr2->eq(3)->filter('a')->link()->getUri());
    }

    public function testIndexAsContributor(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/mon-espace/organizations/' . OrganizationFixture::MAIN_ORG_ID . '/users');

        $users = $crawler->filter('[data-testid="user-list"]');
        $tr0 = $users->filter('tr')->eq(0)->filter('td');
        $tr1 = $users->filter('tr')->eq(1)->filter('td');
        $tr2 = $users->filter('tr')->eq(2)->filter('td');
        $this->assertCount(3, $users->filter('tr'));

        $this->assertSame('Mathieu MARCHOIS En attente d\'activation', $tr0->eq(0)->text());
        $this->assertEmpty($tr0->eq(3)->text());

        $this->assertSame('Mathieu FERNANDEZ', $tr1->eq(0)->text());
        $this->assertEmpty($tr1->eq(3)->text());

        $this->assertSame('Mathieu MARCHOIS', $tr2->eq(0)->text());
        $this->assertEmpty($tr2->eq(3)->text());
    }

    public function testOrganizationNotOwned(): void
    {
        $client = $this->login();
        $client->request('GET', '/mon-espace/organizations/' . OrganizationFixture::OTHER_ORG_ID . '/users');
        $this->assertResponseStatusCodeSame(403);
    }

    public function testOrganizationNotFound(): void
    {
        $client = $this->login();
        $client->request('GET', '/mon-espace/organizations/f5c1cea8-a61d-43a7-9b5d-4b8c9557c673/users');
        $this->assertResponseStatusCodeSame(404);
    }

    public function testWithoutAuthenticatedUser(): void
    {
        $client = static::createClient();
        $client->request('GET', '/mon-espace/organizations/' . OrganizationFixture::MAIN_ORG_ID . '/users');
        $this->assertResponseRedirects('http://localhost/login', 302);
    }
}
