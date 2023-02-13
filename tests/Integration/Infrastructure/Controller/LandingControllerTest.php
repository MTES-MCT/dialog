<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller;

final class LandingControllerTest extends AbstractWebTestCase
{
    public function testLanding(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();
        $this->assertSame('Faciliter et sécuriser la circulation', $crawler->filter('h1')->text());
        $enterLink = $crawler->filter('[data-testid="enter-link"]');
        $this->assertSame('Se connecter', $enterLink->text());
        $this->assertSame('/login', $enterLink->attr('href'));
    }

    public function testLandingWithLoggedUser(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSame('Faciliter et sécuriser la circulation', $crawler->filter('h1')->text());
        $this->assertSame('Mathieu MARCHOIS', $crawler->filter('div.user ul')->filter('li')->eq(0)->text());

        $enterLink = $crawler->filter('[data-testid="enter-link"]');
        $this->assertSame('Accéder aux réglementations', $enterLink->text());
        $this->assertSame('/regulations', $enterLink->attr('href'));
    }
}
