<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\MyArea\Profile;

use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;

final class EditProfileControllerTest extends AbstractWebTestCase
{
    public function testEdit(): void
    {
        $client = $this->login('mathieu.fernandez@beta.gouv.fr');
        $crawler = $client->request('GET', '/mon-espace/profile');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();
        $this->assertSame('Mon compte', $crawler->filter('h2')->text());
        $this->assertMetaTitle('Mathieu FERNANDEZ - DiaLog', $crawler);

        $saveButton = $crawler->selectButton('Sauvegarder');
        $form = $saveButton->form();

        // Get the raw values.
        $values = $form->getPhpValues();
        $values['profile_form']['fullName'] = 'Léa LEFOULON';
        $values['profile_form']['email'] = 'lala.lefoulon@beta.gouv.fr';

        $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());
        $client->followRedirect();

        $this->assertResponseStatusCodeSame(200);
        $this->assertRouteSame('app_profile');
    }

    public function testEditWithTooLongFullName(): void
    {
        $client = $this->login('mathieu.fernandez@beta.gouv.fr');
        $crawler = $client->request('GET', '/mon-espace/profile');

        $saveButton = $crawler->selectButton('Sauvegarder');
        $form = $saveButton->form();

        // Get the raw values.
        $values = $form->getPhpValues();
        $values['profile_form']['fullName'] = str_repeat('a', 256);
        $values['profile_form']['email'] = 'mathieu.fernandez@beta.gouv.fr';

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());

        $this->assertResponseStatusCodeSame(422);
        $this->assertSame('Cette chaîne est trop longue. Elle doit avoir au maximum 255 caractères.', $crawler->filter('#profile_form_fullName_error')->text());
    }

    public function testEditWithEmailAlreadyExists(): void
    {
        $client = $this->login('mathieu.fernandez@beta.gouv.fr');
        $crawler = $client->request('GET', '/mon-espace/profile');

        $saveButton = $crawler->selectButton('Sauvegarder');
        $form = $saveButton->form();

        // Get the raw values.
        $values = $form->getPhpValues();
        $values['profile_form']['fullName'] = 'Mathieu FERNANDEZ';
        $values['profile_form']['email'] = 'mathieu.marchois@beta.gouv.fr';

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());

        $this->assertResponseStatusCodeSame(422);
        $this->assertSame('Cette adresse email est déjà associée à un autre compte.', $crawler->filter('#profile_form_email_error')->text());
    }

    public function testBadFormValues(): void
    {
        $client = $this->login('mathieu.fernandez@beta.gouv.fr');
        $crawler = $client->request('GET', '/mon-espace/profile');

        $saveButton = $crawler->selectButton('Sauvegarder');
        $form = $saveButton->form();

        // Get the raw values.
        $values = $form->getPhpValues();
        $values['profile_form']['fullName'] = '';
        $values['profile_form']['email'] = '';

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());

        $this->assertResponseStatusCodeSame(422);
        $this->assertSame('Cette valeur ne doit pas être vide.', $crawler->filter('#profile_form_fullName_error')->text());
        $this->assertSame('Cette valeur ne doit pas être vide.', $crawler->filter('#profile_form_email_error')->text());
    }

    public function testBadEmail(): void
    {
        $client = $this->login('mathieu.fernandez@beta.gouv.fr');
        $crawler = $client->request('GET', '/mon-espace/profile');

        $saveButton = $crawler->selectButton('Sauvegarder');
        $form = $saveButton->form();

        $values = $form->getPhpValues();
        $values['profile_form']['fullName'] = 'Lea';
        $values['profile_form']['email'] = 'lea.l@beta';

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());

        $this->assertResponseStatusCodeSame(422);
        $this->assertSame('Cette valeur n\'est pas une adresse email valide.', $crawler->filter('#profile_form_email_error')->text());
    }
}
