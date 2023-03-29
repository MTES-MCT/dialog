<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller;

final class LoginControllerTest extends AbstractWebTestCase
{
    public function testLoginSuccessfully(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/login');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();
        $this->assertSame('Connexion à DiaLog', $crawler->filter('h1')->text());
        $this->assertMetaTitle("Connexion - DiaLog", $crawler);
        $saveButton = $crawler->selectButton('Se connecter');
        $form = $saveButton->form();

        $form["email"] = "mathieu.marchois@beta.gouv.fr";
        $form["password"] = "password";
        $client->submit($form);
        $this->assertResponseStatusCodeSame(302);
        $crawler = $client->followRedirect();
        $this->assertSame('Mathieu MARCHOIS Se déconnecter', $crawler->filter('div.user')->text());
    }

    public function testLoginWithUnknownAccount(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/login');

        $this->assertResponseStatusCodeSame(200);

        $saveButton = $crawler->selectButton('Se connecter');
        $form = $saveButton->form();
        $form["email"] = "mathieu@fairness.coop";
        $form["password"] = "password";

        $client->submit($form);
        $this->assertResponseStatusCodeSame(302);
        $crawler = $client->followRedirect();

        $this->assertSame('Identifiants invalides.', $crawler->filter('p.fr-message--error')->text());
    }
}
