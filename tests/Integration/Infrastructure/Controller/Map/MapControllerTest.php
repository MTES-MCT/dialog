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
        $this->assertSecurityHeadersWithoutFraming();
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

    public function testShareButtonAndModalHiddenForAnonymousUser(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/carte');

        $this->assertResponseStatusCodeSame(200);
        $this->assertCount(0, $crawler->filter('d-map-share'));
    }

    public function testShareButtonAndModalVisibleForAuthenticatedUser(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/carte');

        $this->assertResponseStatusCodeSame(200);

        $share = $crawler->filter('d-map-share');
        $this->assertCount(1, $share);

        // The organization select must contain the user's organizations.
        $options = $crawler->filter('#map-share-org-select option');
        $this->assertGreaterThan(0, $options->count());

        // The link/embed tabs are present.
        $this->assertCount(1, $crawler->filter('#map-share-tab-link'));
        $this->assertCount(1, $crawler->filter('#map-share-tab-embed'));
    }

    public function testGetWithOrganizationUuidUsesOrganizationBbox(): void
    {
        $client = static::createClient();
        // seineSaintDenisOrg has a geometry — use its UUID directly (no login needed).
        $crawler = $client->request('GET', '/carte?organizationUuid=8f9164ed-dc0f-4c98-ac18-2f590a1cfd22');

        $this->assertResponseStatusCodeSame(200);

        $initialBbox = $crawler->filter('d-map')->attr('initialbbox');
        $this->assertNotNull($initialBbox);
        $decoded = json_decode($initialBbox, true);
        // Seine-Saint-Denis bbox: lon ~[2.28, 2.60], lat ~[48.80, 49.01].
        $this->assertGreaterThan(2.0, $decoded['minLon']);
        $this->assertLessThan(3.0, $decoded['maxLon']);
        $this->assertGreaterThan(48.0, $decoded['minLat']);
        $this->assertLessThan(49.5, $decoded['maxLat']);
    }

    public function testGetWithUnknownOrganizationUuidReturnsNoBbox(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/carte?organizationUuid=00000000-0000-0000-0000-000000000000');

        $this->assertResponseStatusCodeSame(200);
        $this->assertNull($crawler->filter('d-map')->attr('initialbbox'));
    }

    public function testGetEmbedHidesHeaderAndFooter(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/carte?embed=1');

        $this->assertResponseStatusCodeSame(200);
        // Embed mode: neither the global header nor the footer are rendered.
        $this->assertCount(0, $crawler->filter('header.fr-header'));
        $this->assertCount(0, $crawler->filter('footer'));
        // The map itself is still rendered.
        $this->assertCount(1, $crawler->filter('d-map'));
        // The "share" button is also hidden inside the iframe.
        $this->assertCount(0, $crawler->filter('d-map-share'));
    }
}
