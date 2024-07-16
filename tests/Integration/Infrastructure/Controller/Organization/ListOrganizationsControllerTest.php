<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\Organization;

use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;

final class ListOrganizationsControllerTest extends AbstractWebTestCase
{
    public function testPageAndTabs(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/organizations');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();
        $this->assertSame('Mes organisations', $crawler->filter('h3')->text());
        $this->assertMetaTitle('Mes organisations - DiaLog', $crawler);

        $organizations = $crawler->filter('[data-testid="organization-list"]');
        $this->assertCount(1, $organizations->filter('tr'));
        $this->assertSame('Main Org Contributeur', $organizations->filter('tr')->eq(0)->text());
    }

    public function testWithoutAuthenticatedUser(): void
    {
        $client = static::createClient();
        $client->request('GET', '/organizations');
        $this->assertResponseRedirects('http://localhost/login', 302);
    }
}
