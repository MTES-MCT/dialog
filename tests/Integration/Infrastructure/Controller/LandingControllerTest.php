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
        $joinLink = $crawler->selectLink("Découvrir l'équipe");
        $this->assertSame('Découvrir l\'équipe', $joinLink->text());
        $this->assertMetaTitle('DiaLog', $crawler);
    }

    public function testLandingWithLoggedUser(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSame('Numériser la réglementation de circulation routière avec DiaLog', $crawler->filter('h1')->text());
        $userLinks = $crawler->filter(selector: '[data-testid="user-links"]')->filter('li');
        $this->assertCount(2, $userLinks);
        $this->assertSame('Votre avis', $userLinks->eq(0)->text());

        $joinLink = $crawler->selectLink("Découvrir l'équipe");
        $this->assertSame('Découvrir l\'équipe', $joinLink->text());
    }

    public function testNavigationLink(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/');

        $this->assertNavStructure([
            ['Arrêtés de circulation', ['href' => '/regulations', 'aria-current' => null]],
            ['Carte des restrictions', ['href' => '/carte', 'aria-current' => null]],
            ['Aide', ['href' => 'https://fabrique-numerique.gitbook.io/doc.dialog.beta.gouv.fr', 'aria-current' => null]],
        ], $crawler);

        $crawler = $client->request('GET', '/regulations');

        $this->assertNavStructure([
            ['Arrêtés de circulation', ['href' => '/regulations', 'aria-current' => 'page']],
            ['Carte des restrictions', ['href' => '/carte', 'aria-current' => null]],
            ['Aide', ['href' => 'https://fabrique-numerique.gitbook.io/doc.dialog.beta.gouv.fr', 'aria-current' => null]],
        ], $crawler);

        $crawler = $client->request('GET', '/carte');

        $this->assertNavStructure([
            ['Arrêtés de circulation', ['href' => '/regulations', 'aria-current' => null]],
            ['Carte des restrictions', ['href' => '/carte', 'aria-current' => 'page']],
            ['Aide', ['href' => 'https://fabrique-numerique.gitbook.io/doc.dialog.beta.gouv.fr', 'aria-current' => null]],
        ], $crawler);
    }

    public function testLogoutNavigationLink(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');

        $this->assertNavStructure([
            ['Accueil', ['href' => '/', 'aria-current' => 'page']],
            ['Collectivités', ['href' => '/collectivites', 'aria-current' => null]],
            ['Services numériques', ['href' => '/services-numeriques', 'aria-current' => null]],
            ['Usagers de la route', ['href' => '/usagers', 'aria-current' => null]],
            ['Arrêtés de circulation', ['href' => '/regulations', 'aria-current' => null]],
            ['Carte des restrictions', ['href' => '/carte', 'aria-current' => null]],
            ['Blog', ['href' => '/blog/fr/', 'aria-current' => null]],
            ['Aide', ['href' => 'https://fabrique-numerique.gitbook.io/doc.dialog.beta.gouv.fr', 'aria-current' => null]],
        ], $crawler);

        $crawler = $client->request('GET', '/collectivites');

        $this->assertNavStructure([
            ['Accueil', ['href' => '/', 'aria-current' => null]],
            ['Collectivités', ['href' => '/collectivites', 'aria-current' => 'page']],
            ['Services numériques', ['href' => '/services-numeriques', 'aria-current' => null]],
            ['Usagers de la route', ['href' => '/usagers', 'aria-current' => null]],
            ['Arrêtés de circulation', ['href' => '/regulations', 'aria-current' => null]],
            ['Carte des restrictions', ['href' => '/carte', 'aria-current' => null]],
            ['Blog', ['href' => '/blog/fr/', 'aria-current' => null]],
            ['Aide', ['href' => 'https://fabrique-numerique.gitbook.io/doc.dialog.beta.gouv.fr', 'aria-current' => null]],
        ], $crawler);

        $crawler = $client->request('GET', '/services-numeriques');

        $this->assertNavStructure([
            ['Accueil', ['href' => '/', 'aria-current' => null]],
            ['Collectivités', ['href' => '/collectivites', 'aria-current' => null]],
            ['Services numériques', ['href' => '/services-numeriques', 'aria-current' => 'page']],
            ['Usagers de la route', ['href' => '/usagers', 'aria-current' => null]],
            ['Arrêtés de circulation', ['href' => '/regulations', 'aria-current' => null]],
            ['Carte des restrictions', ['href' => '/carte', 'aria-current' => null]],
            ['Blog', ['href' => '/blog/fr/', 'aria-current' => null]],
            ['Aide', ['href' => 'https://fabrique-numerique.gitbook.io/doc.dialog.beta.gouv.fr', 'aria-current' => null]],
        ], $crawler);

        $crawler = $client->request('GET', '/usagers');

        $this->assertNavStructure([
            ['Accueil', ['href' => '/', 'aria-current' => null]],
            ['Collectivités', ['href' => '/collectivites', 'aria-current' => null]],
            ['Services numériques', ['href' => '/services-numeriques', 'aria-current' => null]],
            ['Usagers de la route', ['href' => '/usagers', 'aria-current' => 'page']],
            ['Arrêtés de circulation', ['href' => '/regulations', 'aria-current' => null]],
            ['Carte des restrictions', ['href' => '/carte', 'aria-current' => null]],
            ['Blog', ['href' => '/blog/fr/', 'aria-current' => null]],
            ['Aide', ['href' => 'https://fabrique-numerique.gitbook.io/doc.dialog.beta.gouv.fr', 'aria-current' => null]],
        ], $crawler);
    }
}
