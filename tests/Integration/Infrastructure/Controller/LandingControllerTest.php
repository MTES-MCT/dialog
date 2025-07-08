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

        $this->assertMetaTitle('DiaLog', $crawler);

        $this->assertSkipLinks(
            [
                ['Contenu', '#content'],
                ['Menu', '#header-navigation'],
                ['Pied de page', '#footer'],
            ],
            $crawler,
        );

        $this->assertPageStructure(
            [
                ['h1', 'Numériser la réglementation de circulation routière avec DiaLog'],
                ['h2', 'Où sont les restrictions de circulation ?'],
                ['a', 'Voir la carte', ['href' => '/carte']],
                ['h2', 'Comment ça marche ?'],
                ['h2', 'Diffuser mes données avec DiaLog'],
                ['h3', '✏️ Saisir un arrêté'],
                ['a', 'Comment saisir un arrêté dans DiaLog', ['href' => '/details#input']],
                ['a', 'Créer un compte', ['href' => '/register']],
                ['h3', '⚙️ Intégrer mes données'],
                ['a', 'Comment intégrer mes arrêtés dans DiaLog', ['href' => '/details#integration']],
                ['a', 'Nous contacter', ['href' => '/contact']],
                ['h2', 'Qui sommes-nous ?'],
                ['a', 'Découvrir l\'équipe', ['href' => 'https://beta.gouv.fr/startups/dialogue.html']],
            ],
            $crawler,
        );
    }

    public function testLandingWithLoggedUser(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSame('Numériser la réglementation de circulation routière avec DiaLog', $crawler->filter('h1')->text());
        $userLinks = $crawler->filter(selector: '[data-testid="user-links"]')->filter('li');
        $this->assertCount(3, $userLinks);
        $this->assertSame('Votre avis', $userLinks->eq(0)->text());

        $joinLink = $crawler->selectLink("Découvrir l'équipe");
        $this->assertSame('https://beta.gouv.fr/startups/dialogue.html', $joinLink->attr('href'));
    }

    public function testNavigationLink(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/');

        $this->assertNavStructure([
            ['Liste des arrêtés', ['href' => '/regulations', 'aria-current' => null]],
            ['Carte des restrictions', ['href' => '/carte', 'aria-current' => null]],
            ['Blog', ['href' => '/blog/fr/', 'aria-current' => null]],
            ['Aide', ['href' => 'https://fabrique-numerique.gitbook.io/doc.dialog.beta.gouv.fr', 'aria-current' => null]],
        ], $crawler);

        $registerLink = $crawler->filter('main')->selectLink('Créer un compte');
        $this->assertSame('/register', $registerLink->attr('href'));

        $contactLink = $crawler->filter('main')->selectLink('Nous contacter');
        $this->assertSame('/contact', $contactLink->attr('href'));

        $crawler = $client->request('GET', '/regulations');

        $this->assertNavStructure([
            ['Liste des arrêtés', ['href' => '/regulations', 'aria-current' => 'page']],
            ['Carte des restrictions', ['href' => '/carte', 'aria-current' => null]],
            ['Blog', ['href' => '/blog/fr/', 'aria-current' => null]],
            ['Aide', ['href' => 'https://fabrique-numerique.gitbook.io/doc.dialog.beta.gouv.fr', 'aria-current' => null]],
        ], $crawler);

        $crawler = $client->request('GET', '/carte');

        $this->assertNavStructure([
            ['Liste des arrêtés', ['href' => '/regulations', 'aria-current' => null]],
            ['Carte des restrictions', ['href' => '/carte', 'aria-current' => 'page']],
            ['Blog', ['href' => '/blog/fr/', 'aria-current' => null]],
            ['Aide', ['href' => 'https://fabrique-numerique.gitbook.io/doc.dialog.beta.gouv.fr', 'aria-current' => null]],
        ], $crawler);
    }

    public function testLogoutNavigationLink(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');

        $this->assertNavStructure([
            ['Accueil', ['href' => '/', 'aria-current' => 'page']],
            ['Carte des restrictions', ['href' => '/carte', 'aria-current' => null]],
            ['Liste des arrêtés', ['href' => '/regulations', 'aria-current' => null]],
            ['Blog', ['href' => '/blog/fr/', 'aria-current' => null]],
            ['Aide', ['href' => 'https://fabrique-numerique.gitbook.io/doc.dialog.beta.gouv.fr', 'aria-current' => null]],
        ], $crawler);
    }
}
