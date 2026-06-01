<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\Map;

use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;

final class MapControllerTest extends AbstractWebTestCase
{
    public function testGet(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/carte');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();
        $this->assertMetaTitle('Carte - DiaLog', $crawler);

        // Search form is present
        $this->assertNotNull($crawler->selectButton('Rechercher'));
        $this->assertNotNull($crawler->filter('#search[name=search][autocomplete=off][spellcheck=false]')->first());

        // Filters form is open by default
        $this->assertStringContainsString('fr-collapse--expanded', $crawler->filter('#map-filters-accordion')->attr('class'));

        // The map exposes an MVT tiles URL template containing literal {z}/{x}/{y} placeholders.
        $tilesUrl = $crawler->filter('d-map')->attr('tilesurl');
        $this->assertNotNull($tilesUrl);
        $this->assertStringContainsString('{z}/{x}/{y}.mvt', $tilesUrl);

        // The form's action mirrors the same template so the client can substitute placeholders.
        $action = $crawler->filter('form')->attr('action');
        $this->assertNotNull($action);
        $this->assertStringContainsString('{z}/{x}/{y}.mvt', $action);

        // No initialBbox attribute when the user is anonymous and the cache table is empty.
        $this->assertNull($crawler->filter('d-map')->attr('initialbbox'));

        // The "Statut des arrêtés" filter (drafts) and the drafts endpoint are reserved to
        // logged-in users: neither the checkboxes nor the draftsUrl attribute are rendered.
        $this->assertCount(0, $crawler->filter('[name="map_filter_form[displayDrafts]"]'));
        $this->assertNull($crawler->filter('d-map')->attr('draftsurl'));
    }

    public function testGetAuthenticatedShowsStatusFilter(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/carte');

        $this->assertResponseStatusCodeSame(200);

        // The "Statut des arrêtés" section exposes the published + drafts checkboxes...
        $this->assertCount(1, $crawler->filter('[name="map_filter_form[displayPublished]"]'));
        $this->assertCount(1, $crawler->filter('[name="map_filter_form[displayDrafts]"]'));

        // ...and the map element is given the authenticated drafts endpoint URL.
        $draftsUrl = $crawler->filter('d-map')->attr('draftsurl');
        $this->assertNotNull($draftsUrl);
        $this->assertStringContainsString('/carte/drafts.geojson', $draftsUrl);
    }

    public function testGetAuthenticatedExposesInitialBbox(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/carte');

        $this->assertResponseStatusCodeSame(200);

        // department93User belongs to seineSaintDenisOrg, which has a geometry:
        // the initial bbox JSON is rendered into the d-map element.
        $initialBbox = $crawler->filter('d-map')->attr('initialbbox');
        $this->assertNotNull($initialBbox);
        $decoded = json_decode($initialBbox, true);
        $this->assertIsArray($decoded);
        $this->assertArrayHasKey('minLon', $decoded);
        $this->assertArrayHasKey('minLat', $decoded);
        $this->assertArrayHasKey('maxLon', $decoded);
        $this->assertArrayHasKey('maxLat', $decoded);
        $this->assertLessThan($decoded['maxLon'], $decoded['minLon']);
        $this->assertLessThan($decoded['maxLat'], $decoded['minLat']);
    }
}
