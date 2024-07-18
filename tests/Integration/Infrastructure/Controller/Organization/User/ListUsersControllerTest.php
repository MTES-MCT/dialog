<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\Organization\User;

use App\Infrastructure\Persistence\Doctrine\Fixtures\OrganizationFixture;
use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;

final class ListUsersControllerTest extends AbstractWebTestCase
{
    public function testIndex(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/organizations/' . OrganizationFixture::MAIN_ORG_ID . '/users');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();
        $this->assertSame('Utilisateurs Main Org', $crawler->filter('h3')->text());
        $this->assertMetaTitle('Utilisateurs Main Org - DiaLog', $crawler);

        $users = $crawler->filter('[data-testid="user-list"]');
        $tr0 = $users->filter('tr')->eq(0)->filter('td');
        $tr1 = $users->filter('tr')->eq(1)->filter('td');
        $this->assertCount(2, $users->filter('tr'));
        $this->assertSame('Mathieu MARCHOIS', $tr0->eq(0)->text());
        $this->assertSame('mathieu.marchois@beta.gouv.fr', $tr0->eq(1)->text());
        $this->assertSame('Contributeur', $tr0->eq(2)->text());

        $this->assertSame('Mathieu FERNANDEZ', $tr1->eq(0)->text());
        $this->assertSame('mathieu.fernandez@beta.gouv.fr', $tr1->eq(1)->text());
        $this->assertSame('Administrateur', $tr1->eq(2)->text());
    }

    public function testOrganizationNotOwned(): void
    {
        $client = $this->login();
        $client->request('GET', '/organizations/' . OrganizationFixture::OTHER_ORG_ID . '/users');
        $this->assertResponseStatusCodeSame(403);
    }

    public function testOrganizationNotFound(): void
    {
        $client = $this->login();
        $client->request('GET', '/organizations/f5c1cea8-a61d-43a7-9b5d-4b8c9557c673/users');
        $this->assertResponseStatusCodeSame(404);
    }

    public function testWithoutAuthenticatedUser(): void
    {
        $client = static::createClient();
        $client->request('GET', '/organizations/' . OrganizationFixture::MAIN_ORG_ID . '/users');
        $this->assertResponseRedirects('http://localhost/login', 302);
    }
}
