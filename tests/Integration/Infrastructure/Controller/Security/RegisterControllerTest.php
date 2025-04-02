<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\Security;

use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;

final class RegisterControllerTest extends AbstractWebTestCase
{
    public function testRegister(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/register');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();
        $this->assertSame('Créer mon compte', $crawler->filter('h2')->text());
        $this->assertMetaTitle('Créer mon compte - DiaLog', $crawler);

        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();
        $form['register_form[fullName]'] = 'Lucie Dutest';
        $form['register_form[organizationSiret]'] = '22930008201453';
        $form['register_form[password][first]'] = 'password12345';
        $form['register_form[password][second]'] = 'password12345';
        $form['register_form[email]'] = 'lucie@example.com';
        $form['register_form[cgu]'] = '1';
        $client->submit($form);
        $this->assertResponseStatusCodeSame(303);

        $this->assertEmailCount(1);
        $email = $this->getMailerMessage();
        $this->assertEmailHtmlBodyContains($email, 'Pour finaliser la création de votre compte, merci de cliquer sur le lien ci-dessous :');

        $crawler = $client->followRedirect();
        $this->assertResponseStatusCodeSame(200);
        $this->assertRouteSame('app_login');
    }

    public function testRegisterSiretNotExists(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/register');

        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();
        $form['register_form[fullName]'] = 'Lucie Dutest';
        $form['register_form[organizationSiret]'] = '11111111111111';
        $form['register_form[password][first]'] = 'password12345';
        $form['register_form[password][second]'] = 'password12345';
        $form['register_form[email]'] = 'lucie@example.com';
        $form['register_form[cgu]'] = '1';
        $crawler = $client->submit($form);
        $this->assertResponseStatusCodeSame(422);
        $this->assertSame('Votre SIRET est invalide : numéro inexistant ou entité juridique non compatible avec DiaLog.', $crawler->filter('#register_form_organizationSiret_error')->text());
    }

    public function testRegisterWithAccountAlreadyExists(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/register');

        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();
        $form['register_form[fullName]'] = 'Mathieu Marchois';
        $form['register_form[organizationSiret]'] = '82050375300015';
        $form['register_form[password][first]'] = 'password12345';
        $form['register_form[password][second]'] = 'password12345';
        $form['register_form[email]'] = 'mathieu.marchois@beta.gouv.fr';
        $form['register_form[cgu]'] = '1';
        $crawler = $client->submit($form);
        $this->assertResponseStatusCodeSame(422);
        $this->assertSame('Un compte utilisant cette adresse e-mail existe déjà.', $crawler->filter('#register_form_email_error')->text());
    }

    public function testEmptyData(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/register');

        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();

        $crawler = $client->submit($form);

        $this->assertResponseStatusCodeSame(422);
        $this->assertSame('Cette valeur ne doit pas être vide.', $crawler->filter('#register_form_fullName_error')->text());
        $this->assertSame('Cette valeur ne doit pas être vide.', $crawler->filter('#register_form_email_error')->text());
        $this->assertSame('Cette valeur ne doit pas être vide.', $crawler->filter('#register_form_organizationSiret_error')->text());
        $this->assertSame('Cette valeur ne doit pas être vide.', $crawler->filter('#register_form_password_first_error')->text());
    }

    public function testBadValues(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/register');

        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();
        $form['register_form[fullName]'] = str_repeat('a', 256);
        $form['register_form[email]'] = 'helene';
        $form['register_form[organizationSiret]'] = 'aaaa';
        $form['register_form[password][first]'] = 'aaaa';
        $form['register_form[password][second]'] = 'bbbbb';

        $crawler = $client->submit($form);

        $this->assertResponseStatusCodeSame(422);
        $this->assertSame('Les caractères doivent être des chiffres. Cette chaîne doit avoir exactement 14 caractères.', $crawler->filter('#register_form_organizationSiret_error')->text());
        $this->assertSame('Cette chaîne est trop longue. Elle doit avoir au maximum 255 caractères.', $crawler->filter('#register_form_fullName_error')->text());
        $this->assertSame('Les valeurs ne correspondent pas.', $crawler->filter('#register_form_password_first_error')->text());
        $this->assertSame('Cette valeur n\'est pas une adresse email valide.', $crawler->filter('#register_form_email_error')->text());

        // Email too long
        $form['register_form[email]'] = str_repeat('a', 256) . '@gmail.com';
        $crawler = $client->submit($form);
        $this->assertResponseStatusCodeSame(422);
        $this->assertSame('Cette chaîne est trop longue. Elle doit avoir au maximum 255 caractères.', $crawler->filter('#register_form_email_error')->text());
    }
}
