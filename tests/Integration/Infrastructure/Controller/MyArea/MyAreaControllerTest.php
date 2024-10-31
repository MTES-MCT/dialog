<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\MyArea;

use App\Infrastructure\Persistence\Doctrine\Fixtures\UserFixture;
use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;

final class MyAreaControllerTest extends AbstractWebTestCase
{
    public function testIndex(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/mon-espace');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();
        $this->assertSame('Mon espace', $crawler->filter('h2')->text());
        $this->assertMetaTitle('Mon espace - DiaLog', $crawler);

        $organizations = $crawler->filter('[data-testid="organization-list"]');
        $this->assertCount(1, $organizations->filter('[data-testid="organization-detail"]'));
        $this->assertSame('Main Org Contributeur', $organizations->filter('[data-testid="organization-detail"]')->text());
        $this->assertCount(0, $crawler->filter('[data-testid="admin-link"]'));
    }

    public function testIndexAsAdmin(): void
    {
        $client = $this->login(UserFixture::MAIN_ORG_ADMIN_EMAIL);
        $crawler = $client->request('GET', '/mon-espace');

        $this->assertResponseStatusCodeSame(200);
        $this->assertCount(1, $crawler->filter('[data-testid="admin-link"]'));
    }

    public function testWithoutAuthenticatedUser(): void
    {
        $client = static::createClient();
        $client->request('GET', '/mon-espace');
        $this->assertResponseRedirects('http://localhost/login', 302);
    }
}
