<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\MyArea\Organization\VisaModel;

use App\Infrastructure\Persistence\Doctrine\Fixtures\OrganizationFixture;
use App\Infrastructure\Persistence\Doctrine\Fixtures\UserFixture;
use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;

final class ListVisaModelsControllerTest extends AbstractWebTestCase
{
    public function testIndex(): void
    {
        $client = $this->login(UserFixture::MAIN_ORG_ADMIN_EMAIL);
        $crawler = $client->request('GET', '/mon-espace/organizations/' . OrganizationFixture::MAIN_ORG_ID . '/visa_models');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();
        $this->assertSame('Modèles de visas', $crawler->filter('h2')->text());
        $this->assertMetaTitle('Modèles de visas - DiaLog', $crawler);

        $users = $crawler->filter('[data-testid="visa-list"]');
        $tr0 = $users->filter('tr')->eq(0)->filter('td');
        $tr1 = $users->filter('tr')->eq(1)->filter('td');
        $this->assertCount(2, $users->filter('tr'));

        $this->assertSame('Réglementation de vitesse en agglomération DiaLog', $tr0->eq(0)->text());
        $this->assertSame('Limitation de vitesse dans toute la commune', $tr0->eq(1)->text());
        $this->assertSame('Interdiction de circulation', $tr1->eq(0)->text());
        $this->assertSame('Interdiction pour tous les véhicules', $tr1->eq(1)->text());
    }

    public function testOrganizationNotOwned(): void
    {
        $client = $this->login();
        $client->request('GET', '/mon-espace/organizations/' . OrganizationFixture::OTHER_ORG_ID . '/visa_models');
        $this->assertResponseStatusCodeSame(403);
    }

    public function testOrganizationNotFound(): void
    {
        $client = $this->login();
        $client->request('GET', '/mon-espace/organizations/f5c1cea8-a61d-43a7-9b5d-4b8c9557c673/visa_models');
        $this->assertResponseStatusCodeSame(404);
    }

    public function testWithoutAuthenticatedUser(): void
    {
        $client = static::createClient();
        $client->request('GET', '/mon-espace/organizations/' . OrganizationFixture::MAIN_ORG_ID . '/visa_models');
        $this->assertResponseRedirects('http://localhost/login', 302);
    }
}
