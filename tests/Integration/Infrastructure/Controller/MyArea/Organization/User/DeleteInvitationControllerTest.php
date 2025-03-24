<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\MyArea\Organization\User;

use App\Infrastructure\Persistence\Doctrine\Fixtures\InvitationFixture;
use App\Infrastructure\Persistence\Doctrine\Fixtures\UserFixture;
use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;
use App\Tests\SessionHelper;

final class DeleteInvitationControllerTest extends AbstractWebTestCase
{
    use SessionHelper;

    public function testDelete(): void
    {
        $client = $this->login(UserFixture::DEPARTMENT_93_ADMIN_EMAIL);
        $client->request('DELETE', '/mon-espace/invitations/' . InvitationFixture::INVITATION_ALREADY_JOINED_UUID . '/delete', [
            '_token' => $this->generateCsrfToken($client, 'delete-invitation'),
        ]);

        $this->assertResponseStatusCodeSame(303);
        $crawler = $client->followRedirect();

        $this->assertResponseStatusCodeSame(200);
        $this->assertEquals(['success' => ['Invitation supprimée.']], $this->getFlashes($crawler));
        $this->assertRouteSame('app_users_list');
    }

    public function testInvitationNotOwned(): void
    {
        $client = $this->login('mathieu.fernandez@beta.gouv.fr');
        $client->request('DELETE', '/mon-espace/invitations/' . InvitationFixture::UUID . '/delete', [
            '_token' => $this->generateCsrfToken($client, 'delete-invitation'),
        ]);
        $crawler = $client->followRedirect();

        $this->assertEquals(['error' => ['Vous n\'avez pas les droits pour supprimer cette invitation.']], $this->getFlashes($crawler));
        $this->assertResponseStatusCodeSame(200);
        $this->assertRouteSame('app_my_area');
    }

    public function testOrganizationOrUserNotFound(): void
    {
        $client = $this->login();
        $client->request('DELETE', '/mon-espace/invitations/d68fba17-fb22-490d-ae52-2a371f14ceb1/delete', [
            '_token' => $this->generateCsrfToken($client, 'delete-invitation'),
        ]);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testWithoutAuthenticatedUser(): void
    {
        $client = static::createClient();
        $client->request('DELETE', '/mon-espace/invitations/' . InvitationFixture::UUID . '/delete');
        $this->assertResponseRedirects('http://localhost/login', 302);
    }
}
