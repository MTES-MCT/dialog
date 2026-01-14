<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\Admin;

use App\Infrastructure\Controller\Admin\UserCrudController;
use App\Infrastructure\Persistence\Doctrine\Fixtures\UserFixture;
use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;

final class UserCrudControllerTest extends AbstractWebTestCase
{
    public function testIndex(): void
    {
        $client = $this->login(UserFixture::DEPARTMENT_93_ADMIN_EMAIL);
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

    public function testExportCsvAsAdmin(): void
    {
        $client = $this->login(UserFixture::DEPARTMENT_93_ADMIN_EMAIL);

        $client->request('GET', '/admin', [
            'crudAction' => 'exportCsv',
            'crudControllerFqcn' => UserCrudController::class,
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('Content-Type', 'text/csv; charset=utf-8');
        $this->assertStringContainsString('attachment; filename="Utilisateurs_DiaLog_', $client->getResponse()->headers->get('Content-Disposition'));
        $this->assertStringContainsString('.csv"', $client->getResponse()->headers->get('Content-Disposition'));

        $content = $client->getResponse()->getContent();
        $this->assertStringContainsString('"Mathieu FERNANDEZ",mathieu.fernandez@beta.gouv.fr', $content);

        $request = $client->getRequest();
        $this->assertSame('exportCsv', $request->query->get('crudAction'));
        $this->assertSame(UserCrudController::class, $request->query->get('crudControllerFqcn'));
    }

    public function testExportCsvWithoutAuthentication(): void
    {
        $client = static::createClient();

        $client->request('GET', '/admin', [
            'crudAction' => 'exportCsv',
            'crudControllerFqcn' => UserCrudController::class,
        ]);

        $this->assertResponseRedirects('http://localhost/login', 302);
    }

    public function testExportCsvWithRoleUser(): void
    {
        $client = $this->login();

        $client->request('GET', '/admin', [
            'crudAction' => 'exportCsv',
            'crudControllerFqcn' => UserCrudController::class,
        ]);

        $this->assertResponseStatusCodeSame(403);

        $request = $client->getRequest();
        $this->assertTrue($request->query->has('crudAction'));
        $this->assertTrue($request->query->has('crudControllerFqcn'));
    }
}
