<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\Admin;

use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;

final class OrganizationCrudControllerTest extends AbstractWebTestCase
{
    public function testIndex(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/admin?crudAction=index&crudControllerFqcn=App%5CInfrastructure%5CController%5CAdmin%5COrganizationCrudController');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();
        $this->assertSame('Organisations', $crawler->filter('h1')->text());
    }

    public function testIndexWithoutAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/admin?crudAction=index&crudControllerFqcn=App%5CInfrastructure%5CController%5CAdmin%5COrganizationCrudController');

        $this->assertResponseRedirects('http://localhost/login', 302);
    }

    public function testIndexWithRoleUser(): void
    {
        $client = $this->login('florimond.manca@beta.gouv.fr');
        $client->request('GET', '/admin?crudAction=index&crudControllerFqcn=App%5CInfrastructure%5CController%5CAdmin%5COrganizationCrudController');

        $this->assertResponseStatusCodeSame(403);
    }
}
