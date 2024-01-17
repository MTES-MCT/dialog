<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\Admin;

use App\Infrastructure\Persistence\Doctrine\Fixtures\UserFixture;
use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;

final class UserCrudControllerTest extends AbstractWebTestCase
{
    public function testIndex(): void
    {
        $client = $this->login(UserFixture::MAIN_ORG_ADMIN_EMAIL);
        $crawler = $client->request('GET', '/admin?crudAction=index&crudControllerFqcn=App%5CInfrastructure%5CController%5CAdmin%5CUserCrudController');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();
        $this->assertSame('Utilisateurs', $crawler->filter('h1')->text());
    }

    public function testIndexWithoutAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/admin?crudAction=index&crudControllerFqcn=App%5CInfrastructure%5CController%5CAdmin%5CUserCrudController');

        $this->assertResponseRedirects('http://localhost/login', 302);
    }

    public function testIndexWithRoleUser(): void
    {
        $client = $this->login();
        $client->request('GET', '/admin?crudAction=index&crudControllerFqcn=App%5CInfrastructure%5CController%5CAdmin%5CUserCrudController');

        $this->assertResponseStatusCodeSame(403);
    }
}
