<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\Admin;

use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;

final class AccessRequestCrudControllerTest extends AbstractWebTestCase
{
    public function testIndex(): void
    {
        $client = $this->login('mathieu.fernandez@beta.gouv.fr');
        $crawler = $client->request('GET', '/admin?crudAction=index&crudControllerFqcn=App%5CInfrastructure%5CController%5CAdmin%5CAccessRequestCrudController');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();
        $this->assertSame('Création de comptes', $crawler->filter('h1')->text());
    }

    public function testIndexWithoutAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/admin?crudAction=index&crudControllerFqcn=App%5CInfrastructure%5CController%5CAdmin%5CAccessRequestCrudController');

        $this->assertResponseRedirects('http://localhost/login', 302);
    }

    public function testIndexWithRoleAccessRequest(): void
    {
        $client = $this->login('florimond.manca@beta.gouv.fr');
        $client->request('GET', '/admin?crudAction=index&crudControllerFqcn=App%5CInfrastructure%5CController%5CAdmin%5CAccessRequestCrudController');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testConvertWithAnUnknownOrganization(): void
    {
        $client = $this->login('mathieu.fernandez@beta.gouv.fr');
        $client->request('GET', '/admin?crudAction=convertAccessRequest&crudControllerFqcn=App%5CInfrastructure%5CController%5CAdmin%5CAccessRequestCrudController&entityId=970f851a-566c-4d7c-89bb-f114efdc5f5e');
        $crawler = $client->followRedirect();

        $this->assertSame($crawler->filter('div.alert-success')->text(), 'Le compte utilisateur a bien été créé.');
    }
}
