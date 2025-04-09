<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\MyArea\Organization\RegulationOrderTemplate;

use App\Infrastructure\Persistence\Doctrine\Fixtures\OrganizationFixture;
use App\Infrastructure\Persistence\Doctrine\Fixtures\UserFixture;
use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;

final class ListRegulationOrderTemplatesControllerTest extends AbstractWebTestCase
{
    public function testIndex(): void
    {
        $client = $this->login(UserFixture::DEPARTMENT_93_ADMIN_EMAIL);
        $crawler = $client->request('GET', '/mon-espace/organizations/' . OrganizationFixture::SEINE_SAINT_DENIS_ID . '/regulation_order_templates');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();
        $this->assertSame('Modèles d\'arrêtés', $crawler->filter('h2')->text());
        $this->assertMetaTitle('Modèles d\'arrêtés - DiaLog', $crawler);

        $rows = $crawler->filter('[data-testid="regulation-order-template-list"]');
        $tr0 = $rows->filter('tr')->eq(0)->filter('td');
        $tr1 = $rows->filter('tr')->eq(1)->filter('td');
        $this->assertCount(2, $rows->filter('tr'));

        $this->assertSame('Réglementation de vitesse en agglomération', $tr0->eq(0)->text());
        $this->assertSame('Restriction de vitesse sur route nationale DiaLog', $tr1->eq(0)->text());
    }

    public function testOrganizationNotOwned(): void
    {
        $client = $this->login();
        $client->request('GET', '/mon-espace/organizations/' . OrganizationFixture::REGION_IDF_ID . '/regulation_order_templates');
        $this->assertResponseStatusCodeSame(403);
    }

    public function testOrganizationNotFound(): void
    {
        $client = $this->login();
        $client->request('GET', '/mon-espace/organizations/f5c1cea8-a61d-43a7-9b5d-4b8c9557c673/regulation_order_templates');
        $this->assertResponseStatusCodeSame(404);
    }

    public function testWithoutAuthenticatedUser(): void
    {
        $client = static::createClient();
        $client->request('GET', '/mon-espace/organizations/' . OrganizationFixture::SEINE_SAINT_DENIS_ID . '/regulation_order_templates');
        $this->assertResponseRedirects('http://localhost/login', 302);
    }
}
