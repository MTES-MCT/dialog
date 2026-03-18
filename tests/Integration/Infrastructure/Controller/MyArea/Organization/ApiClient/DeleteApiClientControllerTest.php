<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\MyArea\Organization\ApiClient;

use App\Infrastructure\Persistence\Doctrine\Fixtures\ApiClientFixture;
use App\Infrastructure\Persistence\Doctrine\Fixtures\OrganizationFixture;
use App\Infrastructure\Persistence\Doctrine\Fixtures\UserFixture;
use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;
use App\Tests\SessionHelper;

final class DeleteApiClientControllerTest extends AbstractWebTestCase
{
    use SessionHelper;

    public function testDeleteRedirectsToList(): void
    {
        $client = $this->login(UserFixture::DEPARTMENT_93_ADMIN_EMAIL);
        $client->request(
            'POST',
            '/mon-espace/organizations/' . OrganizationFixture::SEINE_SAINT_DENIS_ID . '/api-clients/' . ApiClientFixture::SEINE_SAINT_DENIS_API_CLIENT_UUID,
            [
                '_method' => 'DELETE',
                '_token' => $this->generateCsrfToken($client, 'delete-api-client'),
            ],
        );
        $client->followRedirect();

        $this->assertResponseStatusCodeSame(200);
        $this->assertRouteSame('app_config_api_clients_list');
    }

    public function testDeleteWhenNotEditDenied(): void
    {
        $client = $this->login();
        $client->request(
            'POST',
            '/mon-espace/organizations/' . OrganizationFixture::REGION_IDF_ID . '/api-clients/' . ApiClientFixture::SAINT_OUEN_API_CLIENT_UUID,
            [
                '_method' => 'DELETE',
                '_token' => $this->generateCsrfToken($client, 'delete-api-client'),
            ],
        );
        $this->assertResponseStatusCodeSame(403);
    }
}
