<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\Security;

use App\Domain\User\Repository\UserRepositoryInterface;
use App\Infrastructure\Persistence\Doctrine\Fixtures\UserFixture;
use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

final class LoginControllerTest extends AbstractWebTestCase
{
    private function submitCredentials(KernelBrowser $client, string $email): void
    {
        $crawler = $client->request('GET', '/login');
        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();
        $this->assertSame('Connexion à DiaLog', $crawler->filter('h1')->text());
        $this->assertMetaTitle('Connexion - DiaLog', $crawler);

        $form = $crawler->selectButton('Se connecter')->form();
        $form['email'] = $email;
        $form['password'] = UserFixture::PASSWORD;
        $client->submit($form);

        // Le mot de passe est validé mais le jeton est mis « en attente 2FA » :
        // l'authentification classique redirige vers la cible, et l'accès au formulaire /2fa
        // déclenche la génération et l'envoi du code.
        $this->assertResponseRedirects('http://localhost/', 302);
        $client->request('GET', '/2fa');
        $this->assertResponseIsSuccessful();
        $this->assertSame('Double authentification', $client->getCrawler()->filter('h1')->text());
    }

    private function submitTwoFactorCode(KernelBrowser $client, string $email): void
    {
        /** @var UserRepositoryInterface $userRepository */
        $userRepository = static::getContainer()->get(UserRepositoryInterface::class);
        $code = $userRepository->findOneByEmail($email)->getEmailAuthCode();
        $this->assertNotNull($code);

        $form = $client->getCrawler()->selectButton('Vérifier')->form();
        $form['_auth_code'] = $code;
        $client->submit($form);
    }

    public function testLoginSuccessfully(): void
    {
        $client = static::createClient();
        $this->submitCredentials($client, UserFixture::DEPARTMENT_93_USER_EMAIL);
        $this->submitTwoFactorCode($client, UserFixture::DEPARTMENT_93_USER_EMAIL);

        // Après la double authentification, l'utilisateur arrive sur le tableau de bord.
        $this->assertResponseRedirects('http://localhost/', 302);
        $crawler = $client->followRedirect();
        $this->assertSame('Mes organisations', $crawler->filter('h1')->text());
        $this->assertSame('Nouveautés Aide Mathieu MARCHOIS Mathieu MARCHOIS mathieu.marchois@beta.gouv.fr Mon compte Mes organisations Se déconnecter', $crawler->filter('[data-testid="user-links"]')->text());
    }

    public function testLoginAsAdminSuccessfully(): void
    {
        $client = static::createClient();
        $this->submitCredentials($client, UserFixture::DEPARTMENT_93_ADMIN_EMAIL);
        $this->submitTwoFactorCode($client, UserFixture::DEPARTMENT_93_ADMIN_EMAIL);

        $this->assertResponseRedirects('http://localhost/', 302);
        $crawler = $client->followRedirect();
        $this->assertSame('Nouveautés Aide Mathieu FERNANDEZ Mathieu FERNANDEZ mathieu.fernandez@beta.gouv.fr Mon compte Mes organisations Administration Se déconnecter', $crawler->filter('[data-testid="user-links"]')->text());
    }

    public function testLoginWithUnknownAccount(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/login');

        $this->assertResponseStatusCodeSame(200);

        $saveButton = $crawler->selectButton('Se connecter');
        $form = $saveButton->form();
        $form['email'] = 'mathieu@fairness.coop';
        $form['password'] = 'password';

        $client->submit($form);
        $this->assertResponseStatusCodeSame(302);
        $crawler = $client->followRedirect();

        $this->assertSame('Identifiants invalides.', $crawler->filter('p.fr-message--error')->text());
    }

    public function testLoginWithUnverifiedAcccount(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/login');

        $saveButton = $crawler->selectButton('Se connecter');
        $form = $saveButton->form();
        $form['email'] = UserFixture::OTHER_ORG_USER_EMAIL;
        $form['password'] = UserFixture::PASSWORD;

        $client->submit($form);
        $crawler = $client->followRedirect();

        $this->assertSame('Vous devez valider votre compte grâce à l\'e-mail de confirmation reçu.', $crawler->filter('p.fr-message--error')->text());
    }
}
