<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\MyArea\Organization\VisaModel;

use App\Infrastructure\Persistence\Doctrine\Fixtures\OrganizationFixture;
use App\Infrastructure\Persistence\Doctrine\Fixtures\UserFixture;
use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;

final class VisaDetailControllerTest extends AbstractWebTestCase
{
    public function testIndex(): void
    {
        $client = $this->login(UserFixture::DEPARTMENT_93_ADMIN_EMAIL);
        $crawler = $client->request('GET', '/mon-espace/organizations/' . OrganizationFixture::SEINE_SAINT_DENIS_ID . '/visa_models/65c12316-e210-445d-9169-0298b13b3b30/detail');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();
        $this->assertSame('Interdiction de circulation', $crawler->filter('h2')->text());
        $this->assertMetaTitle('Modèles de visas - DiaLog', $crawler);

        $users = $crawler->filter('[data-testid="visa-list"]');
        $li0 = $users->filter('li')->eq(0);
        $this->assertCount(1, $users->filter('li'));

        $this->assertSame('vu que 3', $li0->eq(0)->text());
    }

    public function testOrganizationNotOwned(): void
    {
        $client = $this->login();
        $client->request('GET', '/mon-espace/organizations/' . OrganizationFixture::REGION_IDF_ID . '/visa_models/65c12316-e210-445d-9169-0298b13b3b30/detail');
        $this->assertResponseStatusCodeSame(403);
    }

    public function testOrganizationNotFound(): void
    {
        $client = $this->login();
        $client->request('GET', '/mon-espace/organizations/f5c1cea8-a61d-43a7-9b5d-4b8c9557c673/visa_models/65c12316-e210-445d-9169-0298b13b3b30/detail');
        $this->assertResponseStatusCodeSame(404);
    }

    public function testWithoutAuthenticatedUser(): void
    {
        $client = static::createClient();
        $client->request('GET', '/mon-espace/organizations/' . OrganizationFixture::SEINE_SAINT_DENIS_ID . '/visa_models/65c12316-e210-445d-9169-0298b13b3b30/detail');
        $this->assertResponseRedirects('http://localhost/login', 302);
    }
}
