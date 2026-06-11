<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\Map\Fragment;

use App\Infrastructure\Persistence\Doctrine\Fixtures\LocationFixture;
use App\Infrastructure\Persistence\Doctrine\Fixtures\UserFixture;
use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;

final class MapDraftsDataControllerTest extends AbstractWebTestCase
{
    // noEntry measures of the test drafts, over a date range covering the 2023 fixture periods.
    private const QUERY = '/carte/drafts.geojson'
        . '?map_filter_form[measureTypes][]=noEntry'
        . '&map_filter_form[displayPermanentRegulations]=yes'
        . '&map_filter_form[displayTemporaryRegulations]=yes'
        . '&map_filter_form[startDate]=2023-01-01'
        . '&map_filter_form[endDate]=2023-12-31';

    public function testAnonymousIsRedirectedToLogin(): void
    {
        // The drafts endpoint lives behind the authenticated firewall (ROLE_USER): an anonymous
        // request is redirected to the login page rather than served any (private) draft data.
        $client = static::createClient();
        $client->request('GET', self::QUERY);

        $this->assertResponseStatusCodeSame(302);
    }

    public function testOwnerSeesOwnOrganizationDrafts(): void
    {
        $client = $this->login(UserFixture::DEPARTMENT_93_USER_EMAIL);
        $client->request('GET', self::QUERY);

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();
        $this->assertResponseHeaderSame('Content-Type', 'application/json');

        // Private and never stored by a shared cache.
        $response = $client->getResponse();
        $this->assertTrue($response->headers->hasCacheControlDirective('no-store'));
        $this->assertTrue($response->headers->hasCacheControlDirective('private'));

        $data = json_decode($response->getContent(), true);
        $locationUuids = array_map(fn ($f) => $f['properties']['location_uuid'], $data['features']);

        // The user's own draft (seineSaintDenisOrg) is returned...
        $this->assertContains(LocationFixture::UUID_TYPICAL, $locationUuids);
        // ...while published locations are never part of the drafts layer.
        $this->assertNotContains(LocationFixture::UUID_PUBLISHED, $locationUuids);
    }

    public function testOtherOrganizationDoesNotSeeForeignDrafts(): void
    {
        // otherOrgUser belongs to regionIdfOrg / saintOuenOrg, not to the draft's organization:
        // the seineSaintDenisOrg draft must not leak into their response.
        $client = $this->login(UserFixture::OTHER_ORG_USER_EMAIL);
        $client->request('GET', self::QUERY);

        $this->assertResponseStatusCodeSame(200);

        $data = json_decode($client->getResponse()->getContent(), true);
        $locationUuids = array_map(fn ($f) => $f['properties']['location_uuid'], $data['features']);

        $this->assertNotContains(LocationFixture::UUID_TYPICAL, $locationUuids);
    }
}
