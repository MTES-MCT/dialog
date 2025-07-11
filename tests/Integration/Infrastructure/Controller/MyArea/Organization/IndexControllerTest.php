<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\MyArea\Organization;

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

        $organizations = $crawler->filter('[data-testid="organization-list"]');
        $this->assertCount(2, $organizations->filter('[data-testid="organization-detail"]'));
        $this->assertSame('Complété Département de Seine-Saint-Denis Contributeur', $organizations->filter('[data-testid="organization-detail"]')->text());
        $this->assertCount(0, $crawler->filter('[data-testid="admin-link"]'));
    }

    public function testWithoutAuthenticatedUser(): void
    {
        $client = static::createClient();
        $client->request('GET', '/mon-espace/organizations');
        $this->assertResponseRedirects('http://localhost/login', 302);
    }
}
