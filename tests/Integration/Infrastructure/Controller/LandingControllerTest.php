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
        $this->assertSame('Numériser la réglementation de circulation routière avec Dialog', $crawler->filter('h1')->text());
        $joinLink = $crawler->selectLink("Participer à l'expérimentation");
        $this->assertSame('Participer à l\'expérimentation', $joinLink->text());
        $this->assertSame('/collectivites', $joinLink->attr('href'));
        $this->assertMetaTitle('Numériser la réglementation de circulation routière avec Dialog - DiaLog', $crawler);
        $contactLink = $crawler->filter('[data-testid="contact-link"]');
        $this->assertSame('Nous contacter', $contactLink->text());
        $this->assertSame('mailto:dialog@beta.gouv.fr', $contactLink->attr('href'));
    }

    public function testLandingWithLoggedUser(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSame('Numériser la réglementation de circulation routière avec Dialog', $crawler->filter('h1')->text());
        $userLinks = $crawler->filter('[data-testid="user-links"]')->filter('li');
        $this->assertCount(2, $userLinks);
        $this->assertSame('Mathieu MARCHOIS', $userLinks->eq(0)->text());

        $enterLink = $crawler->selectLink("Participer à l'expérimentation");
        $this->assertSame('/collectivites', $enterLink->attr('href'));
    }

    public function testLandingWithRoleAdmin(): void
    {
        $client = $this->login('mathieu.fernandez@beta.gouv.fr');
        $crawler = $client->request('GET', '/');

        $this->assertResponseStatusCodeSame(200);
        $userLinks = $crawler->filter('[data-testid="user-links"]')->filter('li');
        $this->assertCount(3, $userLinks);
        $this->assertSame('Mathieu FERNANDEZ', $userLinks->eq(0)->text());
        $this->assertSame('Administration', $userLinks->eq(1)->text());
    }
}
