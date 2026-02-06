<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\Security;

use App\Infrastructure\Persistence\Doctrine\Fixtures\UserFixture;
use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;

final class LoginControllerTest extends AbstractWebTestCase
{
    public function testLoginSuccessfully(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/login');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();
        $this->assertSame('Connexion à DiaLog', $crawler->filter('h1')->text());
        $this->assertMetaTitle('Connexion - DiaLog', $crawler);
        $saveButton = $crawler->selectButton('Se connecter');
        $form = $saveButton->form();

        $form['email'] = UserFixture::DEPARTMENT_93_USER_EMAIL;
        $form['password'] = UserFixture::PASSWORD;
        $client->submit($form);
        $this->assertResponseStatusCodeSame(302);
        $crawler = $client->followRedirect();
        $this->assertSame('Votre avis Mes organisations Mathieu MARCHOIS Mathieu MARCHOIS mathieu.marchois@beta.gouv.fr Mon compte Se déconnecter', $crawler->filter('[data-testid="user-links"]')->text());
    }

    public function testLoginAsAdminSuccessfully(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/login');

        $this->assertResponseStatusCodeSame(200);
        $saveButton = $crawler->selectButton('Se connecter');
        $form = $saveButton->form();

        $form['email'] = UserFixture::DEPARTMENT_93_ADMIN_EMAIL;
        $form['password'] = UserFixture::PASSWORD;
        $client->submit($form);
        $this->assertResponseStatusCodeSame(302);
        $crawler = $client->followRedirect();
        $this->assertSame('Votre avis Mes organisations Mathieu FERNANDEZ Mathieu FERNANDEZ mathieu.fernandez@beta.gouv.fr Mon compte Administration Se déconnecter', $crawler->filter('[data-testid="user-links"]')->text());
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
}
