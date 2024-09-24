<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\MyArea\Organization\VisaModel;

use App\Infrastructure\Persistence\Doctrine\Fixtures\OrganizationFixture;
use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;
use App\Tests\SessionHelper;

/**
 *  @group only
 */
final class DuplicateVisaModelControllerTest extends AbstractWebTestCase
{
    use SessionHelper;

    public function testDuplicate(): void
    {
        $client = $this->login('mathieu.fernandez@beta.gouv.fr');
        $client->request('POST', '/mon-espace/organizations/' . OrganizationFixture::MAIN_ORG_ID . '/visa_models/7eca6579-c07e-4e8e-8f10-fda610d7ee73/duplicate', [
            '_token' => $this->generateCsrfToken($client, 'duplicate-visa-model'),
        ]);

        $this->assertResponseStatusCodeSame(303);
        $client->followRedirect();

        $this->assertResponseStatusCodeSame(200);
        $this->assertRouteSame('app_config_visa_models_list');
    }

    public function testNotFound(): void
    {
        $client = $this->login('mathieu.fernandez@beta.gouv.fr');
        $client->request('POST', '/mon-espace/organizations/' . OrganizationFixture::MAIN_ORG_ID . '/visa_models/e18d61be-1797-4d6b-aa58-cd75e623a821/duplicate', [
            '_token' => $this->generateCsrfToken($client, 'duplicate-visa-model'),
        ]);

        $this->assertResponseStatusCodeSame(404);
    }

    public function testOrganizationNotOwned(): void
    {
        $client = $this->login();
        $client->request('POST', '/mon-espace/organizations/' . OrganizationFixture::MAIN_ORG_ID . '/visa_models/7eca6579-c07e-4e8e-8f10-fda610d7ee73/duplicate', [
            '_token' => $this->generateCsrfToken($client, 'duplicate-visa-model'),
        ]);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testBadAccessToken(): void
    {
        $client = $this->login('mathieu.fernandez@beta.gouv.fr');
        $client->request('POST', '/mon-espace/organizations/' . OrganizationFixture::MAIN_ORG_ID . '/visa_models/7eca6579-c07e-4e8e-8f10-fda610d7ee73/duplicate', [
            '_token' => 'abc',
        ]);

        $this->assertResponseRedirects('http://localhost/login', 302);
    }

    public function testOrganizationNotFound(): void
    {
        $client = $this->login();
        $client->request('POST', '/mon-espace/organizations/f5c1cea8-a61d-43a7-9b5d-4b8c9557c673/visa_models/7eca6579-c07e-4e8e-8f10-fda610d7ee73/duplicate', [
            '_token' => $this->generateCsrfToken($client, 'duplicate-visa-model'),
        ]);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testWithoutAuthenticatedUser(): void
    {
        $client = static::createClient();
        $client->request('POST', '/mon-espace/organizations/' . OrganizationFixture::MAIN_ORG_ID . '/visa_models/7eca6579-c07e-4e8e-8f10-fda610d7ee73/duplicate');
        $this->assertResponseRedirects('http://localhost/login', 302);
    }
}
