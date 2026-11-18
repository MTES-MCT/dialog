<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\Admin;

use App\Domain\User\Enum\UserAccountStatusEnum;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Infrastructure\Controller\Admin\UserCrudController;
use App\Infrastructure\Persistence\Doctrine\Fixtures\UserFixture;
use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

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
        $needleContent = mb_convert_encoding(
            '2024-04-02T00:00:00+00:00;2024-06-08T00:00:00+00:00;Oui;"Mathieu FERNANDEZ";mathieu.fernandez@beta.gouv.fr;"Département de Seine-Saint-Denis"',
            'ISO-8859-1',
            'UTF-8',
        );

        $client = $this->login(UserFixture::DEPARTMENT_93_ADMIN_EMAIL);
        $client->request('GET', '/admin', [
            'crudAction' => 'exportCsv',
            'crudControllerFqcn' => UserCrudController::class,
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('Content-Type', 'text/csv; charset=ISO-8859-1');
        $this->assertStringContainsString('attachment; filename="Utilisateurs_DiaLog_', $client->getResponse()->headers->get('Content-Disposition'));
        $this->assertStringContainsString('.csv"', $client->getResponse()->headers->get('Content-Disposition'));

        $content = $client->getResponse()->getContent();
        $this->assertStringContainsString(
            $needleContent,
            $content,
        );

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

    public function testValidateUserAccount(): void
    {
        $userUuid = 'd47badd9-989e-472b-a80e-9df642e93880';

        $client = $this->login(UserFixture::DEPARTMENT_93_ADMIN_EMAIL);

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $userRepository = static::getContainer()->get(UserRepositoryInterface::class);
        $userRepository->findOneByUuid($userUuid)->setAccountStatus(UserAccountStatusEnum::PENDING->value);
        $em->flush();

        $client->request('POST', $this->findAccountStatusActionUrl($client, 'validateUserAccount', $userUuid));

        $this->assertResponseStatusCodeSame(302);

        $this->assertEmailCount(1);
        $email = $this->getMailerMessage();
        $this->assertEmailHtmlBodyContains($email, 'Votre compte a été validé par notre équipe.');

        $em->clear();
        $this->assertSame(
            UserAccountStatusEnum::VALIDATED->value,
            $userRepository->findOneByUuid($userUuid)->getAccountStatus(),
        );
    }

    public function testRefuseUserAccount(): void
    {
        $userUuid = 'd47badd9-989e-472b-a80e-9df642e93880';

        $client = $this->login(UserFixture::DEPARTMENT_93_ADMIN_EMAIL);

        $client->request('POST', $this->findAccountStatusActionUrl($client, 'refuseUserAccount', $userUuid));

        $this->assertResponseStatusCodeSame(302);

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->clear();
        $userRepository = static::getContainer()->get(UserRepositoryInterface::class);
        $this->assertSame(
            UserAccountStatusEnum::REFUSED->value,
            $userRepository->findOneByUuid($userUuid)->getAccountStatus(),
        );
    }

    public function testValidateUserAccountWithRoleUser(): void
    {
        $client = $this->login();

        $client->request('GET', '/admin', [
            'crudAction' => 'validateUserAccount',
            'crudControllerFqcn' => UserCrudController::class,
            'entityId' => 'd47badd9-989e-472b-a80e-9df642e93880',
        ]);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testRefuseUserAccountRejectsGetRequest(): void
    {
        $userUuid = 'd47badd9-989e-472b-a80e-9df642e93880';
        $client = $this->login(UserFixture::DEPARTMENT_93_ADMIN_EMAIL);

        $client->request('GET', $this->findAccountStatusActionUrl($client, 'refuseUserAccount', $userUuid));

        $this->assertResponseStatusCodeSame(405);
        $this->assertAccountStatusUnchanged($userUuid);
    }

    public function testRefuseUserAccountRejectsInvalidCsrfToken(): void
    {
        $userUuid = 'd47badd9-989e-472b-a80e-9df642e93880';
        $client = $this->login(UserFixture::DEPARTMENT_93_ADMIN_EMAIL);

        $client->request('POST', '/admin?' . http_build_query([
            'crudAction' => 'refuseUserAccount',
            'crudControllerFqcn' => UserCrudController::class,
            'entityId' => $userUuid,
            '_token' => 'invalid',
        ]));

        $this->assertResponseStatusCodeSame(403);
        $this->assertAccountStatusUnchanged($userUuid);
    }

    private function findAccountStatusActionUrl(KernelBrowser $client, string $crudAction, string $userUuid): string
    {
        $crawler = $client->request('GET', '/admin?' . http_build_query([
            'crudAction' => 'index',
            'crudControllerFqcn' => UserCrudController::class,
        ]));
        $this->assertResponseStatusCodeSame(200);

        foreach ($crawler->filter('form[method="POST"]') as $form) {
            $action = html_entity_decode($form->getAttribute('action'));
            parse_str((string) parse_url($action, \PHP_URL_QUERY), $query);

            if (($query['crudAction'] ?? null) === $crudAction && ($query['entityId'] ?? null) === $userUuid) {
                $this->assertNotEmpty($query['_token'] ?? null);

                return $action;
            }
        }

        $this->fail(\sprintf('No "%s" action form found for user %s.', $crudAction, $userUuid));
    }

    private function assertAccountStatusUnchanged(string $userUuid): void
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->clear();
        $userRepository = static::getContainer()->get(UserRepositoryInterface::class);
        $this->assertSame(
            UserAccountStatusEnum::VALIDATED->value,
            $userRepository->findOneByUuid($userUuid)->getAccountStatus(),
        );
    }
}
