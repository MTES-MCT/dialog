<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\MyArea\Organization\User;

use App\Domain\User\Enum\OrganizationRolesEnum;
use App\Infrastructure\Persistence\Doctrine\Fixtures\OrganizationFixture;
use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;

final class CreateInvitationControllerTest extends AbstractWebTestCase
{
    public function testInvite(): void
    {
        $client = $this->login('mathieu.fernandez@beta.gouv.fr');
        $crawler = $client->request('GET', '/mon-espace/organizations/' . OrganizationFixture::MAIN_ORG_ID . '/users/invite');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();
        $this->assertSame('Inviter un utilisateur', $crawler->filter('h2')->text());
        $this->assertMetaTitle('Inviter un utilisateur - DiaLog', $crawler);

        $saveButton = $crawler->selectButton('Inviter');
        $form = $saveButton->form();

        // Get the raw values.
        $values = $form->getPhpValues();
        $values['invitation_form']['fullName'] = 'Test';
        $values['invitation_form']['email'] = 'test@beta.gouv.fr';
        $values['invitation_form']['role'] = OrganizationRolesEnum::ROLE_ORGA_CONTRIBUTOR->value;
        $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());

        $this->assertEmailCount(1);
        $email = $this->getMailerMessage();
        $this->assertEmailHtmlBodyContains($email, 'Mathieu FERNANDEZ vous invite à rejoindre l&#039;organisation Main Org.');

        $client->followRedirect();

        $this->assertResponseStatusCodeSame(200);
        $this->assertRouteSame('app_users_list');
    }

    public function testAlreadyInOrganization(): void
    {
        $client = $this->login('mathieu.fernandez@beta.gouv.fr');
        $crawler = $client->request('GET', '/mon-espace/organizations/' . OrganizationFixture::MAIN_ORG_ID . '/users/invite');

        $saveButton = $crawler->selectButton('Inviter');
        $form = $saveButton->form();

        // Get the raw values.
        $values = $form->getPhpValues();
        $values['invitation_form']['fullName'] = 'Mathieu Fernandez';
        $values['invitation_form']['email'] = 'mathieu.fernandez@beta.gouv.fr';
        $values['invitation_form']['role'] = OrganizationRolesEnum::ROLE_ORGA_CONTRIBUTOR->value;

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());
        $this->assertResponseStatusCodeSame(422);
        $this->assertSame('Cette adresse email est déjà associée à votre organisation.', $crawler->filter('#invitation_form_email_error')->text());
    }

    public function testAlreadyInvitedInOrganization(): void
    {
        $client = $this->login('mathieu.fernandez@beta.gouv.fr');
        $crawler = $client->request('GET', '/mon-espace/organizations/' . OrganizationFixture::MAIN_ORG_ID . '/users/invite');

        $saveButton = $crawler->selectButton('Inviter');
        $form = $saveButton->form();

        // Get the raw values.
        $values = $form->getPhpValues();
        $values['invitation_form']['fullName'] = 'Mathieu MARCHOIS';
        $values['invitation_form']['email'] = 'mathieu.marchois@beta.gouv.fr';
        $values['invitation_form']['role'] = OrganizationRolesEnum::ROLE_ORGA_CONTRIBUTOR->value;

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());
        $this->assertResponseStatusCodeSame(422);
        $this->assertSame('Une invitation a déjà été envoyé à cette adresse email.', $crawler->filter('#invitation_form_email_error')->text());
    }

    public function testBadFormValues(): void
    {
        $client = $this->login('mathieu.fernandez@beta.gouv.fr');
        $crawler = $client->request('GET', '/mon-espace/organizations/' . OrganizationFixture::MAIN_ORG_ID . '/users/invite');

        $saveButton = $crawler->selectButton('Inviter');
        $form = $saveButton->form();

        // Get the raw values.
        $values = $form->getPhpValues();
        $values['invitation_form']['fullName'] = '';
        $values['invitation_form']['email'] = '';
        $values['invitation_form']['role'] = '';

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());

        $this->assertResponseStatusCodeSame(422);
        $this->assertSame('Cette valeur ne doit pas être vide.', $crawler->filter('#invitation_form_role_error')->text());
        $this->assertSame('Cette valeur ne doit pas être vide.', $crawler->filter('#invitation_form_email_error')->text());
        $this->assertSame('Cette valeur ne doit pas être vide.', $crawler->filter('#invitation_form_fullName_error')->text());

        $values['invitation_form']['email'] = 'abc';
        $crawler = $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());
        $this->assertResponseStatusCodeSame(422);
        $this->assertSame('Cette valeur n\'est pas une adresse email valide.', $crawler->filter('#invitation_form_email_error')->text());
    }

    public function testNotAdministrator(): void
    {
        $client = $this->login();
        $client->request('GET', '/mon-espace/organizations/' . OrganizationFixture::MAIN_ORG_ID . '/users/invite');
        $this->assertResponseStatusCodeSame(403);
    }

    public function testOrganizationNotOwned(): void
    {
        $client = $this->login();
        $client->request('GET', '/mon-espace/organizations/' . OrganizationFixture::OTHER_ORG_ID . '/users/invite');
        $this->assertResponseStatusCodeSame(403);
    }

    public function testOrganizationOrUserNotFound(): void
    {
        $client = $this->login();
        $client->request('GET', '/mon-espace/organizations/f5c1cea8-a61d-43a7-9b5d-4b8c9557c673/users/invite');
        $this->assertResponseStatusCodeSame(404);
    }

    public function testWithoutAuthenticatedUser(): void
    {
        $client = static::createClient();
        $client->request('GET', '/mon-espace/organizations/' . OrganizationFixture::MAIN_ORG_ID . '/users/invite');
        $this->assertResponseRedirects('http://localhost/login', 302);
    }
}
