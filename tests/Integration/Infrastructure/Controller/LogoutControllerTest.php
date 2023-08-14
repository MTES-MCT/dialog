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

        $enterLink = $crawler->filter('[data-testid="enter-link"]');
        $this->assertSame('Participer à l\'expérimentation', $enterLink->text());
        $this->assertSame('mailto:dialog@beta.gouv.fr', $enterLink->attr('href'));
    }
}
