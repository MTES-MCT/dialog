<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\Security;

use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;

final class ConfirmAccountControllerTest extends AbstractWebTestCase
{
    public function testConfirmAccount(): void
    {
        $client = static::createClient();
        $client->request('GET', '/register/confirmAccountToken/confirm-account');

        $this->assertResponseStatusCodeSame(302);

        $this->assertEmailCount(1);
        $email = $this->getMailerMessage();
        $this->assertEmailHtmlBodyContains($email, 'Bonjour,');

        $crawler = $client->followRedirect();
        $this->assertResponseStatusCodeSame(200);
        $this->assertRouteSame('app_login');
        $this->assertEquals(['success' => ['Votre compte est maintenant activé. Vous pouvez dès à présent vous connecter avec vos identifiants.']], $this->getFlashes($crawler));
    }

    public function testExpiredToken(): void
    {
        $client = static::createClient();
        $client->request('GET', '/register/expiredConfirmAccountToken/confirm-account');

        $this->assertResponseStatusCodeSame(400);
    }

    public function testNotFoundToken(): void
    {
        $client = static::createClient();
        $client->request('GET', '/register/notFoundToken/confirm-account');

        $this->assertResponseStatusCodeSame(404);
    }
}
