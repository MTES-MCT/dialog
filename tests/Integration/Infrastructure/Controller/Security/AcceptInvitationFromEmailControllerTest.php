<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\Security;

use App\Infrastructure\Persistence\Doctrine\Fixtures\InvitationFixture;
use App\Infrastructure\Persistence\Doctrine\Fixtures\UserFixture;
use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;

final class AcceptInvitationFromEmailControllerTest extends AbstractWebTestCase
{
    public function testInvitationNotFound(): void
    {
        $client = static::createClient();
        $client->request('GET', '/invitations/3d3c57f0-7754-4553-a9f4-4efd45f665e1/accept');
        $this->assertResponseStatusCodeSame(404);
    }

    public function testShowRegistrationFormForNewUser(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/invitations/' . InvitationFixture::INVITATION_FOR_NEW_USER_UUID . '/accept');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();
        $this->assertSame('Rejoindre une organisation', $crawler->filter('h1')->text());
        // Callout shows the inviter name and organization
        $this->assertStringContainsString('Mathieu FERNANDEZ', $crawler->filter('.fr-callout__text')->text());
        $this->assertStringContainsString('DiaLog', $crawler->filter('.fr-callout__text')->text());
        // Registration info shows the invitee name
        $this->assertStringContainsString('Nouveau Utilisateur', $crawler->filter('.fr-text--lg')->text());
        $this->assertStringContainsString('Créer un compte', $crawler->filter('h2')->text());
        // Password form is displayed
        $this->assertSelectorExists('input[name="create_account_from_invitation_form[password][first]"]');
    }

    public function testShowLoginOptionsForExistingUser(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/invitations/' . InvitationFixture::UUID . '/accept');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();
        $this->assertSame('Rejoindre une organisation', $crawler->filter('h1')->text());
        $this->assertStringContainsString('Un compte existe déjà', $crawler->filter('.fr-alert--info p')->text());
        $this->assertSelectorExists('a[href="/login"]');
    }

    public function testRedirectToJoinWhenAuthenticatedAndEmailMatches(): void
    {
        $client = $this->login();
        $client->request('GET', '/invitations/' . InvitationFixture::UUID . '/accept');

        $this->assertResponseRedirects('/mon-espace/invitations/' . InvitationFixture::UUID . '/join', 303);
    }

    public function testErrorWhenAuthenticatedButEmailDoesNotMatch(): void
    {
        $client = $this->login(UserFixture::DEPARTMENT_93_ADMIN_EMAIL);
        $client->request('GET', '/invitations/' . InvitationFixture::UUID . '/accept');
        $crawler = $client->followRedirect();

        $this->assertResponseStatusCodeSame(200);
        $this->assertEquals(['error' => ['Cette adresse email ne correspond pas à l\'invitation reçue.']], $this->getFlashes($crawler));
    }

    public function testTargetPathIsStoredInSessionForNewUser(): void
    {
        $client = static::createClient();
        $client->request('GET', '/invitations/' . InvitationFixture::INVITATION_FOR_NEW_USER_UUID . '/accept');

        $this->assertResponseStatusCodeSame(200);

        // Verify target path is stored in session (for ProConnect flow)
        $session = $client->getRequest()->getSession();
        $this->assertSame(
            '/invitations/' . InvitationFixture::INVITATION_FOR_NEW_USER_UUID . '/accept',
            $session->get('_security.main.target_path'),
        );
    }

    public function testTargetPathIsStoredInSessionForExistingUser(): void
    {
        $client = static::createClient();
        $client->request('GET', '/invitations/' . InvitationFixture::UUID . '/accept');

        $this->assertResponseStatusCodeSame(200);

        // Verify target path is stored in session
        $session = $client->getRequest()->getSession();
        $this->assertSame(
            '/invitations/' . InvitationFixture::UUID . '/accept',
            $session->get('_security.main.target_path'),
        );
    }

    public function testCreateAccountFromInvitation(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/invitations/' . InvitationFixture::INVITATION_FOR_NEW_USER_UUID . '/accept');

        $this->assertResponseStatusCodeSame(200);

        $form = $crawler->selectButton('Créer mon compte et rejoindre l\'organisation')->form();
        $form['create_account_from_invitation_form[password][first]'] = 'securePassword123';
        $form['create_account_from_invitation_form[password][second]'] = 'securePassword123';
        $form['create_account_from_invitation_form[cgu]'] = true;
        $client->submit($form);

        $this->assertResponseRedirects('/login', 303);

        $crawler = $client->followRedirect();
        $this->assertResponseStatusCodeSame(200);
        $this->assertEquals(
            ['success' => ['Votre compte a été créé et vous avez rejoint l\'organisation. Connectez-vous pour continuer.']],
            $this->getFlashes($crawler),
        );
    }
}
