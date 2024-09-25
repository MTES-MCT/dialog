<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\MyArea\Organization\User;

use App\Domain\User\Enum\OrganizationRolesEnum;
use App\Infrastructure\Persistence\Doctrine\Fixtures\OrganizationFixture;
use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;

final class AddUserControllerTest extends AbstractWebTestCase
{
    public function testAdd(): void
    {
        $client = $this->login('mathieu.fernandez@beta.gouv.fr');
        $crawler = $client->request('GET', '/mon-espace/organizations/' . OrganizationFixture::MAIN_ORG_ID . '/users/add');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();
        $this->assertSame('Nouvel utilisateur', $crawler->filter('h2')->text());
        $this->assertMetaTitle('Nouvel utilisateur - DiaLog', $crawler);

        $saveButton = $crawler->selectButton('Sauvegarder');
        $form = $saveButton->form();

        // Get the raw values.
        $values = $form->getPhpValues();
        $values['user_form']['fullName'] = 'Test';
        $values['user_form']['email'] = 'test@beta.gouv.fr';
        $values['user_form']['password']['first'] = 'password';
        $values['user_form']['password']['second'] = 'password';
        $values['user_form']['role'] = OrganizationRolesEnum::ROLE_ORGA_CONTRIBUTOR->value;
        $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());
        $client->followRedirect();

        $this->assertResponseStatusCodeSame(200);
        $this->assertRouteSame('app_users_list');
    }

    public function testAccountAlreadyInOrganization(): void
    {
        $client = $this->login('mathieu.fernandez@beta.gouv.fr');
        $crawler = $client->request('GET', '/mon-espace/organizations/' . OrganizationFixture::MAIN_ORG_ID . '/users/add');

        $saveButton = $crawler->selectButton('Sauvegarder');
        $form = $saveButton->form();

        // Get the raw values.
        $values = $form->getPhpValues();
        $values['user_form']['fullName'] = 'Mathieu Fernandez';
        $values['user_form']['password']['first'] = 'password';
        $values['user_form']['password']['second'] = 'password';
        $values['user_form']['email'] = 'mathieu.fernandez@beta.gouv.fr';
        $values['user_form']['role'] = OrganizationRolesEnum::ROLE_ORGA_CONTRIBUTOR->value;

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());
        $this->assertResponseStatusCodeSame(422);
        $this->assertSame('Cette adresse email est déjà associée à l\'organisation.', $crawler->filter('#user_form_email_error')->text());
    }

    public function testBadFormValues(): void
    {
        $client = $this->login('mathieu.fernandez@beta.gouv.fr');
        $crawler = $client->request('GET', '/mon-espace/organizations/' . OrganizationFixture::MAIN_ORG_ID . '/users/add');

        $saveButton = $crawler->selectButton('Sauvegarder');
        $form = $saveButton->form();

        // Get the raw values.
        $values = $form->getPhpValues();
        $values['user_form']['fullName'] = '';
        $values['user_form']['email'] = '';
        $values['user_form']['role'] = '';
        $values['user_form']['password']['first'] = 'abc';
        $values['user_form']['password']['second'] = 'def';

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());

        $this->assertResponseStatusCodeSame(422);
        $this->assertSame('Cette valeur ne doit pas être vide.', $crawler->filter('#user_form_role_error')->text());
        $this->assertSame('Cette valeur ne doit pas être vide.', $crawler->filter('#user_form_email_error')->text());
        $this->assertSame('Cette valeur ne doit pas être vide.', $crawler->filter('#user_form_fullName_error')->text());
        $this->assertSame('Les valeurs ne correspondent pas.', $crawler->filter('#user_form_password_first_error')->text());

        $values['user_form']['email'] = 'abc';
        $values['user_form']['password']['first'] = '';
        $values['user_form']['password']['second'] = '';
        $crawler = $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());
        $this->assertResponseStatusCodeSame(422);
        $this->assertSame('Cette valeur n\'est pas une adresse email valide.', $crawler->filter('#user_form_email_error')->text());
        $this->assertSame('Cette valeur ne doit pas être vide.', $crawler->filter('#user_form_password_first_error')->text());
    }

    public function testNotAdministrator(): void
    {
        $client = $this->login();
        $client->request('GET', '/mon-espace/organizations/' . OrganizationFixture::MAIN_ORG_ID . '/users/add');
        $this->assertResponseStatusCodeSame(403);
    }

    public function testOrganizationNotOwned(): void
    {
        $client = $this->login();
        $client->request('GET', '/mon-espace/organizations/' . OrganizationFixture::OTHER_ORG_ID . '/users/add');
        $this->assertResponseStatusCodeSame(403);
    }

    public function testOrganizationOrUserNotFound(): void
    {
        $client = $this->login();
        $client->request('GET', '/mon-espace/organizations/f5c1cea8-a61d-43a7-9b5d-4b8c9557c673/users/add');
        $this->assertResponseStatusCodeSame(404);
    }

    public function testWithoutAuthenticatedUser(): void
    {
        $client = static::createClient();
        $client->request('GET', '/mon-espace/organizations/' . OrganizationFixture::MAIN_ORG_ID . '/users/add');
        $this->assertResponseRedirects('http://localhost/login', 302);
    }
}
