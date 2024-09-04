<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\Profile;

use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;

final class EditProfileControllerTest extends AbstractWebTestCase
{
    public function testEdit(): void
    {
        $client = $this->login('mathieu.fernandez@beta.gouv.fr');
        $crawler = $client->request('GET', '/profile');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();
        $this->assertSame('Mon profil', $crawler->filter('h2')->text());
        $this->assertMetaTitle('Mathieu FERNANDEZ - DiaLog', $crawler);

        $saveButton = $crawler->selectButton('Sauvegarder');
        $form = $saveButton->form();

        // Get the raw values.
        $values = $form->getPhpValues();
        $values['profile_form']['fullName'] = 'Léa LEFOULON';
        $values['profile_form']['email'] = 'léa.lefoulon@beta.gouv.fr';

        $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());
        $client->followRedirect();

        $this->assertResponseStatusCodeSame(200);
        $this->assertRouteSame('app_profile');
    }
}
