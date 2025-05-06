<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\MyArea\Organization\MailingList;

use App\Infrastructure\Persistence\Doctrine\Fixtures\OrganizationFixture;
use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;
use App\Tests\SessionHelper;

final class DeleteRecipientControllerTest extends AbstractWebTestCase
{
    use SessionHelper;

    public function testDelete(): void
    {
        $client = $this->login('mathieu.fernandez@beta.gouv.fr');
        $client->request('DELETE', '/mon-espace/organizations/' . OrganizationFixture::SEINE_SAINT_DENIS_ID . '/recipients/247edaa2-58d1-43de-9d33-9753bf6f4d30', [
            '_token' => $this->generateCsrfToken($client, 'delete-mailing-list'),
        ]);

        $this->assertResponseStatusCodeSame(303);
        $client->followRedirect();

        $this->assertResponseStatusCodeSame(200);
        $this->assertRouteSame('app_config_recipients_list');
    }

    public function testNotFound(): void
    {
        $client = $this->login('mathieu.fernandez@beta.gouv.fr');
        $client->request('DELETE', '/mon-espace/organizations/' . OrganizationFixture::SEINE_SAINT_DENIS_ID . '/recipients/aff9a84f-d968-462d-8d58-90313407f22c', [
            '_token' => $this->generateCsrfToken($client, 'delete-mailing-list'),
        ]);

        $this->assertResponseStatusCodeSame(404);
    }

    public function testOrganizationNotOwned(): void
    {
        $client = $this->login();
        $client->request('DELETE', '/mon-espace/organizations/' . OrganizationFixture::SEINE_SAINT_DENIS_ID . '/recipients/65c12316-e210-445d-9169-0298b13b3b30', [
            '_token' => $this->generateCsrfToken($client, 'delete-mailing-list'),
        ]);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testBadAccessToken(): void
    {
        $client = $this->login('mathieu.fernandez@beta.gouv.fr');
        $client->request('DELETE', '/mon-espace/organizations/' . OrganizationFixture::SEINE_SAINT_DENIS_ID . '/recipients/65c12316-e210-445d-9169-0298b13b3b30', [
            '_token' => 'abc',
        ]);

        $this->assertResponseRedirects('http://localhost/login', 302);
    }

    public function testOrganizationOrUserNotFound(): void
    {
        $client = $this->login();
        $client->request('DELETE', '/mon-espace/organizations/f5c1cea8-a61d-43a7-9b5d-4b8c9557c673/recipients/65c12316-e210-445d-9169-0298b13b3b30', [
            '_token' => $this->generateCsrfToken($client, 'delete-mailing-list'),
        ]);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testWithoutAuthenticatedUser(): void
    {
        $client = static::createClient();
        $client->request('DELETE', '/mon-espace/organizations/' . OrganizationFixture::SEINE_SAINT_DENIS_ID . '/recipients/65c12316-e210-445d-9169-0298b13b3b30');
        $this->assertResponseRedirects('http://localhost/login', 302);
    }

    public function testNotAdministrator(): void
    {
        $client = $this->login();
        $client->request('DELETE', '/mon-espace/organizations/' . OrganizationFixture::SEINE_SAINT_DENIS_ID . '/recipients/247edaa2-58d1-43de-9d33-9753bf6f4d30', [
            '_token' => $this->generateCsrfToken($client, 'delete-mailing-list'),
        ]);
        $this->assertResponseStatusCodeSame(403);
    }
}
