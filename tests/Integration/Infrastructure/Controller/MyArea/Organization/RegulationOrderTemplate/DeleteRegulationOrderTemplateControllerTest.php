<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\MyArea\Organization\RegulationOrderTemplate;

use App\Infrastructure\Persistence\Doctrine\Fixtures\OrganizationFixture;
use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;
use App\Tests\SessionHelper;

final class DeleteRegulationOrderTemplateControllerTest extends AbstractWebTestCase
{
    use SessionHelper;

    public function testDelete(): void
    {
        $client = $this->login('mathieu.fernandez@beta.gouv.fr');
        $client->request('DELETE', '/mon-espace/organizations/' . OrganizationFixture::SEINE_SAINT_DENIS_ID . '/regulation_order_templates/54eacea0-e1e0-4823-828d-3eae72b76da8', [
            '_token' => $this->generateCsrfToken($client, 'delete-regulation-order-template'),
        ]);

        $this->assertResponseStatusCodeSame(303);
        $client->followRedirect();

        $this->assertResponseStatusCodeSame(200);
        $this->assertRouteSame('app_config_regulation_order_templates_list');
    }

    public function testNotFound(): void
    {
        $client = $this->login('mathieu.fernandez@beta.gouv.fr');
        $client->request('DELETE', '/mon-espace/organizations/' . OrganizationFixture::SEINE_SAINT_DENIS_ID . '/regulation_order_templates/e18d61be-1797-4d6b-aa58-cd75e623a821', [
            '_token' => $this->generateCsrfToken($client, 'delete-regulation-order-template'),
        ]);

        $this->assertResponseStatusCodeSame(404);
    }

    public function testCannotDeleteGlobalRegulationOrderTemplate(): void
    {
        $client = $this->login('mathieu.fernandez@beta.gouv.fr');
        $client->request('DELETE', '/mon-espace/organizations/' . OrganizationFixture::SEINE_SAINT_DENIS_ID . '/regulation_order_templates/ba023736-35f6-49f4-a118-dc94f90ef42e', [
            '_token' => $this->generateCsrfToken($client, 'delete-regulation-order-template'),
        ]);

        $this->assertResponseStatusCodeSame(404);
    }

    public function testOrganizationNotOwned(): void
    {
        $client = $this->login();
        $client->request('DELETE', '/mon-espace/organizations/' . OrganizationFixture::SEINE_SAINT_DENIS_ID . '/regulation_order_templates/54eacea0-e1e0-4823-828d-3eae72b76da8', [
            '_token' => $this->generateCsrfToken($client, 'delete-regulation-order-template'),
        ]);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testBadAccessToken(): void
    {
        $client = $this->login('mathieu.fernandez@beta.gouv.fr');
        $client->request('DELETE', '/mon-espace/organizations/' . OrganizationFixture::SEINE_SAINT_DENIS_ID . '/regulation_order_templates/54eacea0-e1e0-4823-828d-3eae72b76da8', [
            '_token' => 'abc',
        ]);

        $this->assertResponseRedirects('http://localhost/login', 302);
    }

    public function testOrganizationOrUserNotFound(): void
    {
        $client = $this->login();
        $client->request('DELETE', '/mon-espace/organizations/f5c1cea8-a61d-43a7-9b5d-4b8c9557c673/regulation_order_templates/54eacea0-e1e0-4823-828d-3eae72b76da8', [
            '_token' => $this->generateCsrfToken($client, 'delete-regulation-order-template'),
        ]);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testWithoutAuthenticatedUser(): void
    {
        $client = static::createClient();
        $client->request('DELETE', '/mon-espace/organizations/' . OrganizationFixture::SEINE_SAINT_DENIS_ID . '/regulation_order_templates/54eacea0-e1e0-4823-828d-3eae72b76da8');
        $this->assertResponseRedirects('http://localhost/login', 302);
    }
}
