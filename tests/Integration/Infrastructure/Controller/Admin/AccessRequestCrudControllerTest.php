<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\Admin;

use App\Infrastructure\Persistence\Doctrine\Fixtures\AccessRequestFixture;
use App\Infrastructure\Persistence\Doctrine\Fixtures\UserFixture;
use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;

final class AccessRequestCrudControllerTest extends AbstractWebTestCase
{
    public function testIndex(): void
    {
        $client = $this->login(UserFixture::MAIN_ORG_ADMIN_EMAIL);
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
        $client = $this->login();
        $client->request('GET', '/admin?crudAction=index&crudControllerFqcn=App%5CInfrastructure%5CController%5CAdmin%5CAccessRequestCrudController');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testConvertWithAnUnknownOrganization(): void
    {
        $client = $this->login(UserFixture::MAIN_ORG_ADMIN_EMAIL);
        $client->request('GET', '/admin?crudAction=convertAccessRequest&crudControllerFqcn=App%5CInfrastructure%5CController%5CAdmin%5CAccessRequestCrudController&entityId=' . AccessRequestFixture::UUID);
        $crawler = $client->followRedirect();

        $this->assertSame($crawler->filter('div.alert-success')->text(), 'Le compte utilisateur a bien été créé.');
    }
}
