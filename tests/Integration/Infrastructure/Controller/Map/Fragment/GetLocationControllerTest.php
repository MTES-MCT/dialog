<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\Map\Fragment;

use App\Infrastructure\Persistence\Doctrine\Fixtures\LocationFixture;
use App\Infrastructure\Persistence\Doctrine\Fixtures\UserFixture;
use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;

final class GetLocationControllerTest extends AbstractWebTestCase
{
    public function testGet(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/map/fragments/location/' . LocationFixture::UUID_PUBLISHED);

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $this->assertNotNull($crawler->selectButton('Fermer'));

        $li = $crawler->filter('ul > li');
        $this->assertCount(4, $li);
        $this->assertSame('Circulation interdite', $li->eq(0)->text());
        $this->assertSame('Rue Albert Dhalenne du n° 12 au n° 34 à Saint-Ouen-sur-Seine', $li->eq(1)->text());
        $this->assertSame('Pour les véhicules de plus de 3,5 tonnes, 12 mètres de long ou 2,4 mètres de haut, matières dangereuses, Crit\'Air 4 et Crit\'Air 5, sauf piétons, véhicules d\'urgence et convois exceptionnels', $li->eq(2)->text());
        $this->assertSame('Du 10/03/2023 à 00h00 au 20/03/2023 à 23h59du 28/03/2023 à 08h00 au 28/03/2023 à 22h00', $li->eq(3)->text());

        $detailsLink = $crawler->selectLink('Voir les détails');
        $this->assertSame('_blank', $detailsLink->attr('target'));
    }

    public function testLocationNotFound(): void
    {
        $client = static::createClient();
        $client->request('GET', '/map/fragments/location/' . LocationFixture::UUID_DOES_NOT_EXIST);

        $this->assertResponseStatusCodeSame(404);
    }

    public function testDraftLocationPopupHiddenFromAnonymous(): void
    {
        // UUID_TYPICAL belongs to a DRAFT regulation order (seineSaintDenisOrg). Drafts are private:
        // an anonymous visitor must not be able to read the draft's details, even with the UUID.
        $client = static::createClient();
        $client->request('GET', '/map/fragments/location/' . LocationFixture::UUID_TYPICAL);

        $this->assertResponseStatusCodeSame(404);
    }

    public function testDraftLocationPopupVisibleToOwningOrganization(): void
    {
        // department93User belongs to seineSaintDenisOrg, the owner of the draft.
        $client = $this->login(UserFixture::DEPARTMENT_93_USER_EMAIL);
        $client->request('GET', '/map/fragments/location/' . LocationFixture::UUID_TYPICAL);

        $this->assertResponseStatusCodeSame(200);
    }

    public function testDraftLocationPopupHiddenFromOtherOrganization(): void
    {
        // otherOrgUser (regionIdfOrg / saintOuenOrg) is not a member of the draft's organization.
        $client = $this->login(UserFixture::OTHER_ORG_USER_EMAIL);
        $client->request('GET', '/map/fragments/location/' . LocationFixture::UUID_TYPICAL);

        $this->assertResponseStatusCodeSame(404);
    }
}
