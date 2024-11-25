<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\MyArea\Organization;

use App\Infrastructure\Persistence\Doctrine\Fixtures\OrganizationFixture;
use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;
use App\Tests\SessionHelper;

final class DeleteOrganizationLogoControllerTest extends AbstractWebTestCase
{
    use SessionHelper;

    public function testDeleteLogo(): void
    {
        $client = $this->login('florimond.manca@beta.gouv.fr');
        $client->request('DELETE', '/mon-espace/organizations/' . OrganizationFixture::OTHER_ORG_ID_2 . '/logo/delete', [
            '_token' => $this->generateCsrfToken($client, 'delete-logo'),
        ]);

        $this->assertResponseStatusCodeSame(303);
        $client->followRedirect();

        $this->assertResponseStatusCodeSame(200);
        $this->assertRouteSame('app_config_organization_edit_logo');
    }

    public function testNotAdministrator(): void
    {
        $client = $this->login();
        $client->request('DELETE', '/mon-espace/organizations/' . OrganizationFixture::MAIN_ORG_ID . '/logo/delete', [
            '_token' => $this->generateCsrfToken($client, 'delete-logo'),
        ]);
        $this->assertResponseStatusCodeSame(403);
    }

    public function testOrganizationOrUserNotFound(): void
    {
        $client = $this->login();
        $client->request('DELETE', '/mon-espace/organizations/f5c1cea8-a61d-43a7-9b5d-4b8c9557c673/logo/delete', [
            '_token' => $this->generateCsrfToken($client, 'delete-logo'),
        ]);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testWithoutAuthenticatedUser(): void
    {
        $client = static::createClient();
        $client->request('DELETE', '/mon-espace/organizations/' . OrganizationFixture::MAIN_ORG_ID . '/logo/delete', [
            '_token' => $this->generateCsrfToken($client, 'delete-logo'),
        ]);
        $this->assertResponseRedirects('http://localhost/login', 302);
    }
}
