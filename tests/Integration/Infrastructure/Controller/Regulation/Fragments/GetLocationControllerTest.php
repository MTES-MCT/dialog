<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\Regulation\Fragments;

use App\Infrastructure\Persistence\Doctrine\Fixtures\LocationFixture;
use App\Infrastructure\Persistence\Doctrine\Fixtures\RegulationOrderRecordFixture;
use App\Infrastructure\Persistence\Doctrine\Fixtures\UserFixture;
use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;

final class GetLocationControllerTest extends AbstractWebTestCase
{
    public function testGet(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/location/' . LocationFixture::UUID_TYPICAL);

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $this->assertSame('Route du Grand Brossais', $crawler->filter('h3')->text());
        $this->assertSame('Savenay (44260)', $crawler->filter('li')->eq(0)->text());
        $this->assertSame('Route du Grand Brossais du n° 15 au n° 37bis', $crawler->filter('li')->eq(1)->text());
        $this->assertSame('Circulation interdite du 31/10/2023 à 09h00 au 31/10/2023 à 23h00 pour tous les véhicules', $crawler->filter('li')->eq(2)->text());
        $editForm = $crawler->selectButton('Modifier')->form();
        $this->assertSame('http://localhost/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/location/' . LocationFixture::UUID_TYPICAL . '/form', $editForm->getUri());
        $this->assertSame('GET', $editForm->getMethod());
    }

    public function testGetWithComplexVehicles(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_COMPLEX_VEHICLES . '/location/' . LocationFixture::UUID_COMPLEX_VEHICLES);

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $this->assertSame(
            'Circulation interdite du 31/10/2023 à 09h00 au 31/10/2023 à 23h00, le lundi et le jeudi pour les véhicules de plus de 3,5 tonnes, 12 mètres de long ou 2,4 mètres de haut, Crit\'Air 4 et Crit\'Air 5, sauf piétons, véhicules d\'urgence et convois exceptionnels',
            $crawler->filter('li')->eq(2)->text(),
        );
    }

    public function testGetLocationFromOtherRegulationOrderRecord(): void
    {
        $client = $this->login();
        $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_PERMANENT . '/location/' . LocationFixture::UUID_TYPICAL);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testGetCityOnly(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_FULL_CITY . '/location/' . LocationFixture::UUID_FULL_CITY);

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $this->assertSame('Paris 18e Arrondissement (75018)', $crawler->filter('li')->eq(0)->text());
    }

    public function testIfPublishedThenCannotEdit(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_PUBLISHED . '/location/' . LocationFixture::UUID_PUBLISHED);

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $this->assertSame(0, $crawler->filter('a')->count());
    }

    public function testRegulationDoesNotExist(): void
    {
        $client = $this->login();
        $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_DOES_NOT_EXIST . '/location/' . LocationFixture::UUID_TYPICAL);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testLocationDoesNotExist(): void
    {
        $client = $this->login();
        $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/location/' . LocationFixture::UUID_DOES_NOT_EXIST);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testCannotAccessBecauseDifferentOrganization(): void
    {
        $client = $this->login(UserFixture::OTHER_ORG_USER_EMAIL);
        $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/location/' . LocationFixture::UUID_TYPICAL);
        $this->assertResponseStatusCodeSame(403);
    }

    public function testWithoutAuthenticatedUser(): void
    {
        $client = static::createClient();
        $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/location/' . LocationFixture::UUID_TYPICAL);
        $this->assertResponseRedirects('http://localhost/login', 302);
    }

    public function testBadUuid(): void
    {
        $client = static::createClient();
        $client->request('GET', '/_fragment/regulations/aaa/location/bbb');
        $this->assertResponseStatusCodeSame(404);
    }
}
