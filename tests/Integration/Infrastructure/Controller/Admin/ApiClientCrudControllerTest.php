<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\Admin;

use App\Infrastructure\Persistence\Doctrine\Fixtures\UserFixture;
use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;

final class ApiClientCrudControllerTest extends AbstractWebTestCase
{
    public function testIndex(): void
    {
        $client = $this->login(UserFixture::DEPARTMENT_93_ADMIN_EMAIL);
        $crawler = $client->request('GET', '/admin?crudAction=index&crudControllerFqcn=App%5CInfrastructure%5CController%5CAdmin%5CApiClientCrudController');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();
        $this->assertSame('Accès API', $crawler->filter('h1')->text());
    }

    public function testIndexWithoutAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/admin?crudAction=index&crudControllerFqcn=App%5CInfrastructure%5CController%5CAdmin%5CApiClientCrudController');

        $this->assertResponseRedirects('http://localhost/login', 302);
    }

    public function testIndexWithRoleUser(): void
    {
        $client = $this->login();
        $client->request('GET', '/admin?crudAction=index&crudControllerFqcn=App%5CInfrastructure%5CController%5CAdmin%5CApiClientCrudController');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testRegenerateApiAccess(): void
    {
        $client = $this->login(UserFixture::DEPARTMENT_93_ADMIN_EMAIL);

        $crawler = $client->request('GET', '/admin?crudAction=index&crudControllerFqcn=App%5CInfrastructure%5CController%5CAdmin%5CApiClientCrudController');
        $this->assertResponseIsSuccessful();

        $uuid = '0b507871-8b5e-4575-b297-a630310fc06e';
        $linkNode = $crawler->filter(\sprintf('a[href*="crudAction=regenerateApiAccess"][href*="entityId=%s"]', $uuid))->first();

        $link = $linkNode->link();
        $client->click($link);
        $crawler = $client->followRedirect();
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Accès modifié avec succès', $crawler->html());
    }
}
