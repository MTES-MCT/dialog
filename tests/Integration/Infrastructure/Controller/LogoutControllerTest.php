<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller;

final class LogoutControllerTest extends AbstractWebTestCase
{
    public function testLogout(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/regulations');

        $logoutLink = $crawler->filter('[data-testid="logout-link"]');
        $this->assertSame('Se déconnecter', $logoutLink->text());
        $this->assertSame('/logout', $logoutLink->attr('href'));

        $client->clickLink('Se déconnecter');

        $this->assertResponseStatusCodeSame(302);
        $crawler = $client->followRedirect();
        $this->assertRouteSame('app_landing');

        $enterLink = $crawler->selectLink("Participer à l'expérimentation");
        $this->assertSame('/collectivites', $enterLink->attr('href'));
    }
}
