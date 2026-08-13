<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller;

use App\Infrastructure\Persistence\Doctrine\Fixtures\OrganizationFixture;
use App\Infrastructure\Persistence\Doctrine\Fixtures\UserFixture;

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
                ['h2', 'Ouvrir mes réglementations de circulation avec DiaLog'],
                ['h3', 'Saisir un arrêté avec le formulaire DiaLog'],
                ['a', 'Saisir un arrêté avec le formulaire DiaLog', ['href' => '/details#input']],
                ['h3', 'Importer les données via l’API DiaLog'],
                ['a', 'Importer les données via l’API DiaLog', ['href' => '/details#api']],
                ['h3', 'Intégrer les données d’un service tiers'],
                ['a', 'Intégrer les données d’un service tiers', ['href' => '/details#integration']],
                ['h2', 'Publier les données de circulation pour les diffuser aux GPS'],
                ['h2', 'Qui sommes-nous ?'],
                ['a', 'Découvrir l\'équipe', ['href' => 'https://beta.gouv.fr/startups/dialogue.html']],
            ],
            $crawler);
    }

    public function testDashboardWithLoggedUser(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();
        $this->assertMetaTitle('Accueil - DiaLog', $crawler);

        // L'utilisateur appartient à plusieurs organisations.
        $this->assertSame('Mes organisations', $crawler->filter('h1')->text());
        $this->assertSame('/mon-espace/organizations', $crawler->selectLink('Gérer mes organisations')->attr('href'));
        $this->assertSame('/regulations/add', $crawler->selectLink('Ajouter un arrêté')->attr('href'));

        // Onboarding fermable.
        $onboarding = $crawler->filter('[data-testid="dashboard-onboarding"]');
        $this->assertCount(1, $onboarding);
        $this->assertStringContainsString('Bienvenue sur DiaLog !', $onboarding->text());
        $this->assertSame('notice', $onboarding->attr('data-controller'));

        // Chiffres clés au 09/06/2023 (date figée par DateUtilsMock) :
        // en vigueur = CIFS (01/06 → 10/06/2023), à venir = Litteralis (03/07/2023) et 2025-01 (15/01/2025).
        $this->assertSame('7 arrêtés en brouillon', $crawler->filter('[data-testid="draft-count"]')->text());
        $this->assertSame('1 arrêtés publiés en vigueur', $crawler->filter('[data-testid="current-count"]')->text());
        $this->assertSame('2 arrêtés publiés à venir', $crawler->filter('[data-testid="upcoming-count"]')->text());

        // Derniers arrêtés.
        $this->assertCount(4, $crawler->filter('[data-testid="dashboard-regulation-table"] tbody tr'));

        // Carte zoomée sur le périmètre géographique des organisations de l'utilisateur.
        $map = $crawler->filter('d-map');
        $this->assertCount(1, $map);
        $bbox = json_decode($map->attr('initialbbox'), true);
        // Bbox de la Seine-Saint-Denis (dialogOrg n'a pas de géométrie) : lon ~[2.28, 2.60], lat ~[48.80, 49.01].
        $this->assertEqualsWithDelta(2.28, $bbox['minLon'], 0.2);
        $this->assertEqualsWithDelta(2.60, $bbox['maxLon'], 0.2);
        $this->assertEqualsWithDelta(48.80, $bbox['minLat'], 0.2);
        $this->assertEqualsWithDelta(49.01, $bbox['maxLat'], 0.2);
        // Position de repli sur la France entière si aucune organisation n'a de périmètre.
        $this->assertSame('[2.725, 47.16]', $map->attr('mappos'));
        $this->assertSame('5', $map->attr('mapzoom'));

        $this->assertSame('/carte', $crawler->selectLink('Voir toute la carte')->attr('href'));
        $this->assertSame('/regulations', $crawler->selectLink('Voir la liste des arrêtés')->attr('href'));
    }

    public function testDashboardWithSingleOrganizationUser(): void
    {
        $client = $this->login(UserFixture::DEPARTMENT_93_ADMIN_EMAIL);
        $crawler = $client->request('GET', '/');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSame('Mon organisation', $crawler->filter('h1')->text());
        $this->assertSame(
            '/mon-espace/organizations/' . OrganizationFixture::SEINE_SAINT_DENIS_ID,
            $crawler->selectLink('Gérer mon organisation')->attr('href'),
        );

        $this->assertSame('6 arrêtés en brouillon', $crawler->filter('[data-testid="draft-count"]')->text());
    }

    public function testNavigationLink(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/');

        $this->assertNavStructure([
            ['Accueil', ['href' => '/', 'aria-current' => 'page']],
            ['Liste des arrêtés', ['href' => '/regulations', 'aria-current' => null]],
            ['Carte des restrictions', ['href' => '/carte', 'aria-current' => null]],
        ], $crawler);

        $crawler = $client->request('GET', '/regulations');

        $this->assertNavStructure([
            ['Accueil', ['href' => '/', 'aria-current' => null]],
            ['Liste des arrêtés', ['href' => '/regulations', 'aria-current' => 'page']],
            ['Carte des restrictions', ['href' => '/carte', 'aria-current' => null]],
        ], $crawler);

        $crawler = $client->request('GET', '/carte');

        $this->assertNavStructure([
            ['Accueil', ['href' => '/', 'aria-current' => null]],
            ['Liste des arrêtés', ['href' => '/regulations', 'aria-current' => null]],
            ['Carte des restrictions', ['href' => '/carte', 'aria-current' => 'page']],
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
        ], $crawler);
    }
}
