<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\MyArea\Organization;

use App\Infrastructure\Persistence\Doctrine\Fixtures\UserFixture;
use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;

final class IndexControllerTest extends AbstractWebTestCase
{
    public function testIndex(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/mon-espace/organizations');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();
        $this->assertSame('Mes organisations', $crawler->filter('h2')->text());
        $this->assertMetaTitle('Mes organisations - DiaLog', $crawler);

        $breadcrumbItems = $crawler->filter('.fr-breadcrumb li');
        $this->assertCount(2, $breadcrumbItems);
        $this->assertSame('Accueil', $breadcrumbItems->eq(0)->text());
        $this->assertSame('/', $breadcrumbItems->eq(0)->filter('a')->attr('href'));
        $this->assertSame('Mes organisations', $breadcrumbItems->eq(1)->text());

        $organizations = $crawler->filter('[data-testid="organization-list"]');
        $this->assertCount(2, $organizations->filter('[data-testid="organization-detail"]'));
        $this->assertSame('Complété Département de Seine-Saint-Denis', $organizations->filter('[data-testid="organization-detail"]')->text());
        $this->assertCount(0, $crawler->filter('[data-testid="admin-link"]'));

        // Le bandeau nouveautés ne doit pas s'afficher hors de la page d'accueil.
        $this->assertCount(0, $crawler->filter('[data-testid="notice-news"]'));
    }

    public function testWithOrganizationCompleted(): void
    {
        $client = $this->login(UserFixture::DEPARTMENT_93_ADMIN_EMAIL);
        $crawler = $client->request('GET', '/regulations');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $this->assertEmpty($crawler->filter('[data-testid="notice-warning"]'));
    }

    public function testWithoutAuthenticatedUser(): void
    {
        $client = static::createClient();
        $client->request('GET', '/mon-espace/organizations');
        $this->assertResponseRedirects('http://localhost/login', 302);
    }
}
