<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\MyArea\Organization\User;

use App\Infrastructure\Persistence\Doctrine\Fixtures\InvitationFixture;
use App\Infrastructure\Persistence\Doctrine\Fixtures\UserFixture;
use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;

final class AcceptInvitationControllerTest extends AbstractWebTestCase
{
    public function testJoin(): void
    {
        $client = $this->login();
        $client->request('GET', '/mon-espace/invitations/' . InvitationFixture::UUID . '/join');
        $crawler = $client->followRedirect();

        $this->assertResponseStatusCodeSame(200);
        $this->assertEquals(['success' => ['Vous faites désormais partie de l\'organisation.']], $this->getFlashes($crawler));
        $this->assertRouteSame('app_my_organizations');
    }

    public function testInvitationNotOwned(): void
    {
        $client = $this->login(UserFixture::DEPARTMENT_93_ADMIN_EMAIL);
        $client->request('GET', '/mon-espace/invitations/' . InvitationFixture::UUID . '/join');
        $crawler = $client->followRedirect();

        $this->assertResponseStatusCodeSame(200);
        $this->assertEquals(['error' => ['Cette adresse email ne correspond pas à l\'invitation reçue.']], $this->getFlashes($crawler));
        $this->assertRouteSame('app_my_organizations');
    }

    public function testAlreadyInOrganization(): void
    {
        $client = $this->login();
        $client->request('GET', '/mon-espace/invitations/' . InvitationFixture::INVITATION_ALREADY_JOINED_UUID . '/join');
        $crawler = $client->followRedirect();

        $this->assertResponseStatusCodeSame(200);
        $this->assertEquals(['error' => ['Vous disposez déjà d\'un compte dans cette organisation.']], $this->getFlashes($crawler));
        $this->assertRouteSame('app_my_organizations');
    }

    public function testInvitationNotFound(): void
    {
        $client = $this->login();
        $client->request('GET', '/mon-espace/invitations/3d3c57f0-7754-4553-a9f4-4efd45f665e1/join');
        $this->assertResponseStatusCodeSame(404);
    }

    public function testWithoutAuthenticatedUser(): void
    {
        $client = static::createClient();
        $client->request('GET', '/mon-espace/invitations/' . InvitationFixture::INVITATION_ALREADY_JOINED_UUID . '/join');
        $this->assertResponseRedirects('http://localhost/login', 302);
    }
}
