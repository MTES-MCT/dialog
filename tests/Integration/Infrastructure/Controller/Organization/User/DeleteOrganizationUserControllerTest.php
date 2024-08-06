<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\Organization\User;

use App\Infrastructure\Persistence\Doctrine\Fixtures\OrganizationFixture;
use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;
use App\Tests\SessionHelper;

final class DeleteOrganizationUserControllerTest extends AbstractWebTestCase
{
    use SessionHelper;

    public function testDelete(): void
    {
        $client = $this->login('mathieu.fernandez@beta.gouv.fr');
        $client->request('DELETE', '/organizations/' . OrganizationFixture::MAIN_ORG_ID . '/users/0b507871-8b5e-4575-b297-a630310fc06e', [
            'token' => $this->generateCsrfToken($client, 'delete-user'),
        ]);

        $this->assertResponseStatusCodeSame(303);
        $client->followRedirect();

        $this->assertResponseStatusCodeSame(200);
        $this->assertRouteSame('app_users_list');
    }

    public function testInvalidCsrfToken(): void
    {
        $client = $this->login();
        $client->request('DELETE', '/organizations/' . OrganizationFixture::MAIN_ORG_ID . '/users/0b507871-8b5e-4575-b297-a630310fc06e');
        $this->assertResponseStatusCodeSame(400);
    }

    public function testOrganizationNotOwned(): void
    {
        $client = $this->login();
        $client->request('DELETE', '/organizations/' . OrganizationFixture::OTHER_ORG_ID . '/users/d47badd9-989e-472b-a80e-9df642e93880', [
            'token' => $this->generateCsrfToken($client, 'delete-user'),
        ]);
        $this->assertResponseStatusCodeSame(403);
    }

    public function testOrganizationOrUserNotFound(): void
    {
        $client = $this->login();
        $client->request('DELETE', '/organizations/f5c1cea8-a61d-43a7-9b5d-4b8c9557c673/users/b391100c-f08a-402b-ba2c-c3c09b07275a', [
            'token' => $this->generateCsrfToken($client, 'delete-user'),
        ]);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testWithoutAuthenticatedUser(): void
    {
        $client = static::createClient();
        $client->request('DELETE', '/organizations/' . OrganizationFixture::MAIN_ORG_ID . '/users/0b507871-8b5e-4575-b297-a630310fc06e');
        $this->assertResponseRedirects('http://localhost/login', 302);
    }
}
