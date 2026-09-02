<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\MyArea\Organization\User;

use App\Infrastructure\Persistence\Doctrine\Fixtures\OrganizationFixture;
use App\Infrastructure\Persistence\Doctrine\Fixtures\UserFixture;
use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;

final class ListUsersControllerTest extends AbstractWebTestCase
{
    public function testIndexAsOwner(): void
    {
        $client = $this->login(UserFixture::DEPARTMENT_93_ADMIN_EMAIL);
        $crawler = $client->request('GET', '/mon-espace/organizations/' . OrganizationFixture::SEINE_SAINT_DENIS_ID . '/users');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();
        $this->assertSame('Utilisateurs', $crawler->filter('h3')->text());
        $this->assertMetaTitle('Utilisateurs - DiaLog', $crawler);

        $users = $crawler->filter('[data-testid="user-list"]');
        $tr0 = $users->filter('tr')->eq(0)->filter('td');
        $tr1 = $users->filter('tr')->eq(1)->filter('td');
        $tr2 = $users->filter('tr')->eq(2)->filter('td');
        $tr3 = $users->filter('tr')->eq(3)->filter('td');
        $tr4 = $users->filter('tr')->eq(4)->filter('td');
        $this->assertCount(5, $users->filter('tr'));

        $this->assertSame('Mathieu MARCHOIS En attente d\'activation', $tr0->eq(0)->text());
        $this->assertSame('mathieu.marchois@beta.gouv.fr', $tr0->eq(1)->text());

        $this->assertSame('Marc PRESTATAIRE En attente d\'activation Mandataire', $tr1->eq(0)->text());
        $this->assertSame('marc.prestataire@example.com', $tr1->eq(1)->text());

        $this->assertSame('Mathieu FERNANDEZ Propriétaire', $tr2->eq(0)->text());
        $this->assertSame('mathieu.fernandez@beta.gouv.fr', $tr2->eq(1)->text());

        $this->assertSame('Mathieu MANDATAIRE Mandataire', $tr3->eq(0)->text());
        $this->assertSame('mathieu.mandataire@beta.gouv.fr', $tr3->eq(1)->text());
        $this->assertSame('Modifier', $tr3->eq(2)->filter('a')->text());

        $this->assertSame('Mathieu MARCHOIS', $tr4->eq(0)->text());
        $this->assertSame('mathieu.marchois@beta.gouv.fr', $tr4->eq(1)->text());
        $this->assertSame('Modifier', $tr4->eq(2)->filter('a')->text());
        $this->assertSame('http://localhost/mon-espace/organizations/8f9164ed-dc0f-4c98-ac18-2f590a1cfd22/users/0b507871-8b5e-4575-b297-a630310fc06e/edit', $tr4->eq(2)->filter('a')->link()->getUri());
    }

    public function testIndexAsMember(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/mon-espace/organizations/' . OrganizationFixture::SEINE_SAINT_DENIS_ID . '/users');

        $users = $crawler->filter('[data-testid="user-list"]');
        $tr0 = $users->filter('tr')->eq(0)->filter('td');
        $tr1 = $users->filter('tr')->eq(1)->filter('td');
        $tr2 = $users->filter('tr')->eq(2)->filter('td');
        $tr3 = $users->filter('tr')->eq(3)->filter('td');
        $tr4 = $users->filter('tr')->eq(4)->filter('td');
        $this->assertCount(5, $users->filter('tr'));

        $this->assertSame('Mathieu MARCHOIS En attente d\'activation', $tr0->eq(0)->text());
        $this->assertSame('mathieu.marchois@beta.gouv.fr', $tr0->eq(1)->text());

        $this->assertSame('Marc PRESTATAIRE En attente d\'activation Mandataire', $tr1->eq(0)->text());
        $this->assertSame('marc.prestataire@example.com', $tr1->eq(1)->text());

        $this->assertSame('Mathieu FERNANDEZ Propriétaire', $tr2->eq(0)->text());
        $this->assertSame('mathieu.fernandez@beta.gouv.fr', $tr2->eq(1)->text());
        $this->assertEmpty($tr2->eq(2)->text());

        // Un membre "normal" peut modifier et supprimer un mandataire
        $this->assertSame('Mathieu MANDATAIRE Mandataire', $tr3->eq(0)->text());
        $this->assertSame('mathieu.mandataire@beta.gouv.fr', $tr3->eq(1)->text());
        $this->assertSame('Modifier', $tr3->eq(2)->filter('a')->text());

        $this->assertSame('Mathieu MARCHOIS', $tr4->eq(0)->text());
        $this->assertSame('mathieu.marchois@beta.gouv.fr', $tr4->eq(1)->text());
        $this->assertEmpty($tr4->eq(2)->text());
    }

    public function testIndexAsMandataire(): void
    {
        $client = $this->login(UserFixture::MANDATAIRE_USER_EMAIL);
        $crawler = $client->request('GET', '/mon-espace/organizations/' . OrganizationFixture::SEINE_SAINT_DENIS_ID . '/users');

        $this->assertResponseStatusCodeSame(200);

        $users = $crawler->filter('[data-testid="user-list"]');
        $tr2 = $users->filter('tr')->eq(2)->filter('td');
        $tr3 = $users->filter('tr')->eq(3)->filter('td');
        $this->assertCount(5, $users->filter('tr'));

        // Un mandataire ne peut gérer que les autres mandataires
        $this->assertSame('Mathieu MANDATAIRE Mandataire', $tr3->eq(0)->text());
        $this->assertSame('Modifier', $tr3->eq(2)->filter('a')->text());
        $this->assertEmpty($tr2->eq(2)->text());
    }

    public function testOrganizationNotOwned(): void
    {
        $client = $this->login();
        $client->request('GET', '/mon-espace/organizations/' . OrganizationFixture::REGION_IDF_ID . '/users');
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
        $client->request('GET', '/mon-espace/organizations/' . OrganizationFixture::SEINE_SAINT_DENIS_ID . '/users');
        $this->assertResponseRedirects('http://localhost/login', 302);
    }
}
