<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller;

final class AccessRequestControllerTest extends AbstractWebTestCase
{
    public function testAdd(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/access-request');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();
        $this->assertSame('Demande de création de compte', $crawler->filter('h2')->text());
        $this->assertMetaTitle('Demande de création de compte - DiaLog', $crawler);

        $saveButton = $crawler->selectButton('Envoyer');
        $form = $saveButton->form();
        $form['access_request_form[fullName]'] = 'Lucie Dutest';
        $form['access_request_form[organizationName]'] = 'Fairness';
        $form['access_request_form[organizationSiret]'] = '52435841300143';
        $form['access_request_form[password]'] = 'password12345';
        $form['access_request_form[email]'] = 'lucie@example.com';
        $form['access_request_form[comment]'] = 'Ceci est un test';
        $form['access_request_form[consentToBeContacted]'] = '1';
        $client->submit($form);
        $this->assertResponseStatusCodeSame(303);

        $crawler = $client->followRedirect();
        $this->assertSame('Votre demande de création de compte a bien été prise en compte. Nous reviendrons vers vous dans les plus brefs délais.', $crawler->filter('div.fr-alert--success')->text());
        $this->assertResponseStatusCodeSame(200);
        $this->assertRouteSame('app_access_request');
    }

    public function testAddWithoutSiret(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/access-request');

        $saveButton = $crawler->selectButton('Envoyer');
        $form = $saveButton->form();
        $form['access_request_form[fullName]'] = 'Hélène Maitre-Marchois';
        $form['access_request_form[organizationName]'] = 'Fairness';
        $form['access_request_form[password]'] = 'password12345';
        $form['access_request_form[email]'] = 'helene@fairness.coop';
        $form['access_request_form[comment]'] = 'Ceci est un test';
        $form['access_request_form[consentToBeContacted]'] = '1';
        $client->submit($form);
        $this->assertResponseStatusCodeSame(303);

        $crawler = $client->followRedirect();
        $this->assertSame('Votre demande de création de compte a bien été prise en compte. Nous reviendrons vers vous dans les plus brefs délais.', $crawler->filter('div.fr-alert--success')->text());
        $this->assertResponseStatusCodeSame(200);
        $this->assertRouteSame('app_access_request');
    }

    public function testAccessAlreadyRequested(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/access-request');

        $saveButton = $crawler->selectButton('Envoyer');
        $form = $saveButton->form();
        $form['access_request_form[fullName]'] = 'Mathieu Marchois';
        $form['access_request_form[organizationName]'] = 'Fairness';
        $form['access_request_form[organizationSiret]'] = '82050375300015';
        $form['access_request_form[password]'] = 'password12345';
        $form['access_request_form[email]'] = 'mathieu@fairness.coop';
        $form['access_request_form[comment]'] = 'Ceci est un test';
        $form['access_request_form[consentToBeContacted]'] = '1';
        $client->submit($form);
        $this->assertResponseStatusCodeSame(303);

        $crawler = $client->followRedirect();
        $this->assertSame('Une demande de création de compte a déjà été créée avec cette adresse e-mail.', $crawler->filter('div.fr-alert--error')->text());
        $this->assertResponseStatusCodeSame(200);
        $this->assertRouteSame('app_access_request');
    }

    public function testEmptyData(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/access-request');

        $saveButton = $crawler->selectButton('Envoyer');
        $form = $saveButton->form();

        $crawler = $client->submit($form);
        $this->assertResponseStatusCodeSame(422);
        $this->assertSame('Cette valeur ne doit pas être vide.', $crawler->filter('#access_request_form_fullName_error')->text());
        $this->assertSame('Cette valeur ne doit pas être vide.', $crawler->filter('#access_request_form_email_error')->text());
        $this->assertSame('Cette valeur ne doit pas être vide.', $crawler->filter('#access_request_form_organizationName_error')->text());
        $this->assertSame('Cette valeur ne doit pas être vide.', $crawler->filter('#access_request_form_password_error')->text());
    }

    public function testBadValues(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/access-request');

        $saveButton = $crawler->selectButton('Envoyer');
        $form = $saveButton->form();
        $form['access_request_form[fullName]'] = str_repeat('a', 256);
        $form['access_request_form[email]'] = 'helene';
        $form['access_request_form[organizationName]'] = str_repeat('a', 256);
        $form['access_request_form[organizationSiret]'] = 'aaaa';
        $form['access_request_form[password]'] = 'aaaa';

        $crawler = $client->submit($form);

        $this->assertResponseStatusCodeSame(422);
        $this->assertSame('Les caractères doivent être des chiffres. Cette chaîne doit avoir exactement 14 caractères.', $crawler->filter('#access_request_form_organizationSiret_error')->text());
        $this->assertSame('Cette chaîne est trop longue. Elle doit avoir au maximum 255 caractères.', $crawler->filter('#access_request_form_fullName_error')->text());
        $this->assertSame('Cette chaîne est trop courte. Elle doit avoir au minimum 12 caractères.', $crawler->filter('#access_request_form_password_error')->text());
        $this->assertSame('Cette valeur n\'est pas une adresse email valide.', $crawler->filter('#access_request_form_email_error')->text());

        // Email too long
        $form['access_request_form[email]'] = str_repeat('a', 256) . '@gmail.com';
        $crawler = $client->submit($form);
        $this->assertResponseStatusCodeSame(422);
        $this->assertSame('Cette chaîne est trop longue. Elle doit avoir au maximum 255 caractères.', $crawler->filter('#access_request_form_email_error')->text());
    }
}
