<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\MyArea\Organization\RegulationOrderTemplate;

use App\Infrastructure\Persistence\Doctrine\Fixtures\OrganizationFixture;
use App\Infrastructure\Persistence\Doctrine\Fixtures\UserFixture;
use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;
use App\Tests\SessionHelper;

final class DuplicateRegulationOrderTemplateControllerTest extends AbstractWebTestCase
{
    use SessionHelper;

    public function testDuplicate(): void
    {
        $client = $this->login(UserFixture::DEPARTMENT_93_ADMIN_EMAIL);
        $client->request('POST', '/mon-espace/organizations/' . OrganizationFixture::SEINE_SAINT_DENIS_ID . '/regulation_order_templates/54eacea0-e1e0-4823-828d-3eae72b76da8/duplicate', [
            '_token' => $this->generateCsrfToken($client, 'duplicate-regulation-order-template'),
        ]);

        $this->assertResponseStatusCodeSame(303);
        $client->followRedirect();

        $this->assertResponseStatusCodeSame(200);
        $this->assertRouteSame('app_config_regulation_order_templates_list');
    }

    public function testNotFound(): void
    {
        $client = $this->login(UserFixture::DEPARTMENT_93_ADMIN_EMAIL);
        $client->request('POST', '/mon-espace/organizations/' . OrganizationFixture::SEINE_SAINT_DENIS_ID . '/regulation_order_templates/e18d61be-1797-4d6b-aa58-cd75e623a821/duplicate', [
            '_token' => $this->generateCsrfToken($client, 'duplicate-regulation-order-template'),
        ]);

        $this->assertResponseStatusCodeSame(404);
    }

    public function testOrganizationNotOwned(): void
    {
        $client = $this->login();
        $client->request('POST', '/mon-espace/organizations/' . OrganizationFixture::SEINE_SAINT_DENIS_ID . '/regulation_order_templates/65c12316-e210-445d-9169-0298b13b3b30/duplicate', [
            '_token' => $this->generateCsrfToken($client, 'duplicate-regulation-order-template'),
        ]);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testBadAccessToken(): void
    {
        $client = $this->login(UserFixture::DEPARTMENT_93_ADMIN_EMAIL);
        $client->request('POST', '/mon-espace/organizations/' . OrganizationFixture::SEINE_SAINT_DENIS_ID . '/regulation_order_templates/65c12316-e210-445d-9169-0298b13b3b30/duplicate', [
            '_token' => 'abc',
        ]);

        $this->assertResponseRedirects('http://localhost/login', 302);
    }

    public function testOrganizationNotFound(): void
    {
        $client = $this->login();
        $client->request('POST', '/mon-espace/organizations/f5c1cea8-a61d-43a7-9b5d-4b8c9557c673/regulation_order_templates/65c12316-e210-445d-9169-0298b13b3b30/duplicate', [
            '_token' => $this->generateCsrfToken($client, 'duplicate-regulation-order-template'),
        ]);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testWithoutAuthenticatedUser(): void
    {
        $client = static::createClient();
        $client->request('POST', '/mon-espace/organizations/' . OrganizationFixture::SEINE_SAINT_DENIS_ID . '/regulation_order_templates/65c12316-e210-445d-9169-0298b13b3b30/duplicate');
        $this->assertResponseRedirects('http://localhost/login', 302);
    }
}
