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
        $this->assertMetaTitle('Faciliter et sécuriser la circulation - DiaLog', $crawler);
        $contactLink = $crawler->filter('[data-testid="contact-link"]');
        $this->assertSame('Nous contacter', $contactLink->text());
        $this->assertSame('mailto:dialog@beta.gouv.fr', $contactLink->attr('href'));
    }

    public function testLandingWithLoggedUser(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSame('Faciliter et sécuriser la circulation', $crawler->filter('h1')->text());
        $userLinks = $crawler->filter('[data-testid="user-links"]')->filter('li');
        $this->assertCount(3, $userLinks);
        $this->assertSame('Mathieu MARCHOIS', $userLinks->eq(0)->text());
        $this->assertSame('Administration', $userLinks->eq(1)->text());

        $enterLink = $crawler->filter('[data-testid="enter-link"]');
        $this->assertSame('Accéder aux arrếtés', $enterLink->text());
        $this->assertSame('/regulations', $enterLink->attr('href'));
    }

    public function testLandingWithoutRoleAdmin(): void
    {
        $client = $this->login('florimond.manca@beta.gouv.fr');
        $crawler = $client->request('GET', '/');

        $this->assertResponseStatusCodeSame(200);
        $userLinks = $crawler->filter('[data-testid="user-links"]')->filter('li');
        $this->assertCount(2, $userLinks);
        $this->assertSame('Florimond MANCA', $userLinks->eq(0)->text());
    }
}
