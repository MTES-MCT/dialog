<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\MyArea\Profile;

use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;

final class EditPasswordControllerTest extends AbstractWebTestCase
{
    public function testEdit(): void
    {
        $client = $this->login('mathieu.fernandez@beta.gouv.fr');
        $crawler = $client->request('GET', '/mon-espace/profile/password');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();
        $this->assertSame('Modification du mot de passe', $crawler->filter('h2')->text());
        $this->assertMetaTitle('Mathieu FERNANDEZ - DiaLog', $crawler);

        $saveButton = $crawler->selectButton('Sauvegarder');
        $form = $saveButton->form();

        $values = $form->getPhpValues();
        $values['edit_password_form']['password']['first'] = 'password1234';
        $values['edit_password_form']['password']['second'] = 'password1234';

        $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());
        $client->followRedirect();

        $this->assertResponseStatusCodeSame(200);
        $this->assertRouteSame('app_profile_password');
    }

    public function testWithTooShortPsw(): void
    {
        $client = $this->login('mathieu.fernandez@beta.gouv.fr');
        $crawler = $client->request('GET', '/mon-espace/profile/password');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();
        $this->assertSame('Modification du mot de passe', $crawler->filter('h2')->text());
        $this->assertMetaTitle('Mathieu FERNANDEZ - DiaLog', $crawler);

        $saveButton = $crawler->selectButton('Sauvegarder');
        $form = $saveButton->form();

        $values = $form->getPhpValues();
        $values['edit_password_form']['password']['first'] = 'password';
        $values['edit_password_form']['password']['second'] = 'password';

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());

        $this->assertResponseStatusCodeSame(422);
        $this->assertSame('Cette chaîne est trop courte. Elle doit avoir au minimum 12 caractères.', $crawler->filter('#edit_password_form_password_first_error')->text());
    }

    public function testEditWithTooLongPsw(): void
    {
        $client = $this->login('mathieu.fernandez@beta.gouv.fr');
        $crawler = $client->request('GET', '/mon-espace/profile/password');

        $saveButton = $crawler->selectButton('Sauvegarder');
        $form = $saveButton->form();

        $values = $form->getPhpValues();
        $values['edit_password_form']['password']['first'] = str_repeat('a', 256);
        $values['edit_password_form']['password']['second'] = str_repeat('a', 256);

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());

        $this->assertResponseStatusCodeSame(422);
        $this->assertSame('Cette chaîne est trop longue. Elle doit avoir au maximum 255 caractères.', $crawler->filter('#edit_password_form_password_first_error')->text());
    }

    public function testEditWithPswDiff(): void
    {
        $client = $this->login('mathieu.fernandez@beta.gouv.fr');
        $crawler = $client->request('GET', '/mon-espace/profile/password');

        $saveButton = $crawler->selectButton('Sauvegarder');
        $form = $saveButton->form();

        $values = $form->getPhpValues();
        $values['edit_password_form']['password']['first'] = 'password1234';
        $values['edit_password_form']['password']['second'] = 'password3412';

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());

        $this->assertResponseStatusCodeSame(422);
        $this->assertSame('Les valeurs ne correspondent pas.', $crawler->filter('#edit_password_form_password_first_error')->text());
    }
}
