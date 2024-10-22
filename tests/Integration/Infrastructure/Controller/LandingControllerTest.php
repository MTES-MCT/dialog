<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller;

use App\Infrastructure\Persistence\Doctrine\Fixtures\UserFixture;

final class LandingControllerTest extends AbstractWebTestCase
{
    public function testLanding(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();
        $this->assertSkipLinks(
            [
                ['Contenu', '#content'],
                ['Menu', '#header-navigation'],
                ['Pied de page', '#footer'],
            ],
            $crawler,
        );
        $this->assertSame('Numériser la réglementation de circulation routière avec DiaLog', $crawler->filter('h1')->text());
        $this->assertSame('/collectivites', $crawler->selectLink('Pour les collectivités')->attr('href'));
        $this->assertSame('/services-numeriques', $crawler->selectLink('Pour les services numériques')->attr('href'));
        $this->assertSame('/usagers', $crawler->selectLink('Pour les usagers de la route')->attr('href'));
        $joinLink = $crawler->selectLink("Participer à l'expérimentation");
        $this->assertSame('Participer à l\'expérimentation', $joinLink->text());
        $this->assertSame('/collectivites', $joinLink->attr('href'));
        $this->assertMetaTitle('DiaLog', $crawler);
        $contactLink = $crawler->filter('[data-testid="contact-link"]');
        $this->assertSame('Nous contacter', $contactLink->text());
        $this->assertSame('mailto:dialog@beta.gouv.fr', $contactLink->attr('href'));
    }

    public function testLandingWithLoggedUser(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSame('Numériser la réglementation de circulation routière avec DiaLog', $crawler->filter('h1')->text());
        $userLinks = $crawler->filter(selector: '[data-testid="user-links"]')->filter('li');
        $this->assertCount(3, $userLinks);
        $this->assertSame('Arrêtés de circulation', $userLinks->eq(0)->text());

        $enterLink = $crawler->selectLink("Participer à l'expérimentation");
        $this->assertSame('/collectivites', $enterLink->attr('href'));
    }

    public function testLandingWithRoleAdmin(): void
    {
        $client = $this->login(UserFixture::MAIN_ORG_ADMIN_EMAIL);
        $crawler = $client->request('GET', '/');

        $this->assertResponseStatusCodeSame(200);
        $userLinks = $crawler->filter('[data-testid="user-links"]')->filter('li');
        $this->assertCount(3, $userLinks);
        $this->assertSame('Votre avis', $userLinks->eq(1)->text());
        $this->assertSame('Mon espace', $userLinks->eq(2)->text());
    }

    public function testNavigationLink(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/');

        $this->assertNavStructure([
            ['Accueil', ['href' => '/', 'aria-current' => 'page']],
            ['Collectivités', ['href' => '/collectivites', 'aria-current' => null]],
            ['Services numériques', ['href' => '/services-numeriques', 'aria-current' => null]],
            ['Usagers de la route', ['href' => '/usagers', 'aria-current' => null]],
            ['Carte des restrictions', ['href' => '/carte', 'aria-current' => null]],
            ['Blog', ['href' => '/blog/fr/', 'aria-current' => null]],
        ], $crawler);

        $crawler = $client->request('GET', '/collectivites');

        $this->assertNavStructure([
            ['Accueil', ['href' => '/', 'aria-current' => null]],
            ['Collectivités', ['href' => '/collectivites', 'aria-current' => 'page']],
            ['Services numériques', ['href' => '/services-numeriques', 'aria-current' => null]],
            ['Usagers de la route', ['href' => '/usagers', 'aria-current' => null]],
            ['Carte des restrictions', ['href' => '/carte', 'aria-current' => null]],
            ['Blog', ['href' => '/blog/fr/', 'aria-current' => null]],
        ], $crawler);

        $crawler = $client->request('GET', '/services-numeriques');

        $this->assertNavStructure([
            ['Accueil', ['href' => '/', 'aria-current' => null]],
            ['Collectivités', ['href' => '/collectivites', 'aria-current' => null]],
            ['Services numériques', ['href' => '/services-numeriques', 'aria-current' => 'page']],
            ['Usagers de la route', ['href' => '/usagers', 'aria-current' => null]],
            ['Carte des restrictions', ['href' => '/carte', 'aria-current' => null]],
            ['Blog', ['href' => '/blog/fr/', 'aria-current' => null]],
        ], $crawler);

        $crawler = $client->request('GET', '/usagers');

        $this->assertNavStructure([
            ['Accueil', ['href' => '/', 'aria-current' => null]],
            ['Collectivités', ['href' => '/collectivites', 'aria-current' => null]],
            ['Services numériques', ['href' => '/services-numeriques', 'aria-current' => null]],
            ['Usagers de la route', ['href' => '/usagers', 'aria-current' => 'page']],
            ['Carte des restrictions', ['href' => '/carte', 'aria-current' => null]],
            ['Blog', ['href' => '/blog/fr/', 'aria-current' => null]],
        ], $crawler);
    }
}
