<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\Organization;

use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;

final class ListOrganizationsControllerTest extends AbstractWebTestCase
{
    public function testIndex(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/organizations');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();
        $this->assertSame('Mes organisations', $crawler->filter('h3')->text());
        $this->assertMetaTitle('Mes organisations - DiaLog', $crawler);

        $organizations = $crawler->filter('[data-testid="organization-list"]');
        $td = $organizations->filter('tr')->eq(0)->filter('td');
        $this->assertCount(1, $organizations->filter('tr'));
        $this->assertSame('Main Org', $td->eq(0)->text());
        $this->assertSame('Contributeur', $td->eq(1)->text());
        $this->assertSame('Voir le détail', $td->eq(2)->text());
    }

    public function testWithoutAuthenticatedUser(): void
    {
        $client = static::createClient();
        $client->request('GET', '/organizations');
        $this->assertResponseRedirects('http://localhost/login', 302);
    }
}
