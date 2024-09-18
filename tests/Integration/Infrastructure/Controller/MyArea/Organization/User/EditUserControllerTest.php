<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\MyArea\Organization\User;

use App\Domain\User\Enum\OrganizationRolesEnum;
use App\Infrastructure\Persistence\Doctrine\Fixtures\OrganizationFixture;
use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;

final class EditUserControllerTest extends AbstractWebTestCase
{
    public function testEdit(): void
    {
        $client = $this->login('mathieu.fernandez@beta.gouv.fr');
        $crawler = $client->request('GET', '/mon-espace/organizations/' . OrganizationFixture::MAIN_ORG_ID . '/users/0b507871-8b5e-4575-b297-a630310fc06e/edit');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();
        $this->assertSame('Mathieu MARCHOIS', $crawler->filter('h2')->text());
        $this->assertMetaTitle('Mathieu MARCHOIS - DiaLog', $crawler);

        $saveButton = $crawler->selectButton('Sauvegarder');
        $form = $saveButton->form();

        // Get the raw values.
        $values = $form->getPhpValues();
        $values['user_form']['fullName'] = 'Mathieu MARCHOIS';
        $values['user_form']['email'] = 'mathieu.marchois@beta.gouv.fr';
        $values['user_form']['role'] = OrganizationRolesEnum::ROLE_ORGA_CONTRIBUTOR->value;
        $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());
        $client->followRedirect();

        $this->assertResponseStatusCodeSame(200);
        $this->assertRouteSame('app_users_list');
    }

    public function testEditAdmin(): void
    {
        $client = $this->login('mathieu.fernandez@beta.gouv.fr');
        $client->request('GET', '/mon-espace/organizations/' . OrganizationFixture::MAIN_ORG_ID . '/users/5bc831a3-7a09-44e9-aefa-5ce3588dac33/edit');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testBadEmail(): void
    {
        $client = $this->login('mathieu.fernandez@beta.gouv.fr');
        $crawler = $client->request('GET', '/mon-espace/organizations/' . OrganizationFixture::MAIN_ORG_ID . '/users/0b507871-8b5e-4575-b297-a630310fc06e/edit');

        $saveButton = $crawler->selectButton('Sauvegarder');
        $form = $saveButton->form();

        // Get the raw values.
        $values = $form->getPhpValues();
        $values['user_form']['email'] = 'mathieu.fernandez@beta.gouv.fr';

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());

        $this->assertResponseStatusCodeSame(422);
        $this->assertSame('Cette adresse email est déjà associée à un autre compte.', $crawler->filter('#user_form_email_error')->text());
    }

    public function testBadFormValues(): void
    {
        $client = $this->login('mathieu.fernandez@beta.gouv.fr');
        $crawler = $client->request('GET', '/mon-espace/organizations/' . OrganizationFixture::MAIN_ORG_ID . '/users/0b507871-8b5e-4575-b297-a630310fc06e/edit');

        $saveButton = $crawler->selectButton('Sauvegarder');
        $form = $saveButton->form();

        // Get the raw values.
        $values = $form->getPhpValues();
        $values['user_form']['fullName'] = '';
        $values['user_form']['email'] = '';
        $values['user_form']['role'] = '';

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());

        $this->assertResponseStatusCodeSame(422);
        $this->assertSame('Cette valeur ne doit pas être vide.', $crawler->filter('#user_form_role_error')->text());
        $this->assertSame('Cette valeur ne doit pas être vide.', $crawler->filter('#user_form_email_error')->text());
        $this->assertSame('Cette valeur ne doit pas être vide.', $crawler->filter('#user_form_fullName_error')->text());

        $values['user_form']['email'] = 'abc';
        $crawler = $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());
        $this->assertResponseStatusCodeSame(422);
        $this->assertSame('Cette valeur n\'est pas une adresse email valide.', $crawler->filter('#user_form_email_error')->text());
    }

    public function testNotAdministrator(): void
    {
        $client = $this->login();
        $client->request('GET', '/mon-espace/organizations/' . OrganizationFixture::MAIN_ORG_ID . '/users/0b507871-8b5e-4575-b297-a630310fc06e/edit');
        $this->assertResponseStatusCodeSame(403);
    }

    public function testOrganizationNotOwned(): void
    {
        $client = $this->login();
        $client->request('GET', '/mon-espace/organizations/' . OrganizationFixture::OTHER_ORG_ID . '/users/d47badd9-989e-472b-a80e-9df642e93880/edit');
        $this->assertResponseStatusCodeSame(403);
    }

    public function testOrganizationOrUserNotFound(): void
    {
        $client = $this->login();
        $client->request('GET', '/mon-espace/organizations/f5c1cea8-a61d-43a7-9b5d-4b8c9557c673/users/b391100c-f08a-402b-ba2c-c3c09b07275a/edit');
        $this->assertResponseStatusCodeSame(404);
    }

    public function testWithoutAuthenticatedUser(): void
    {
        $client = static::createClient();
        $client->request('GET', '/mon-espace/organizations/' . OrganizationFixture::MAIN_ORG_ID . '/users/0b507871-8b5e-4575-b297-a630310fc06e/edit');
        $this->assertResponseRedirects('http://localhost/login', 302);
    }
}
