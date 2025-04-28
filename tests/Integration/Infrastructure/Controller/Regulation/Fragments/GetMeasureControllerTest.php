<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\Regulation\Fragments;

use App\Infrastructure\Persistence\Doctrine\Fixtures\LocationFixture;
use App\Infrastructure\Persistence\Doctrine\Fixtures\MeasureFixture;
use App\Infrastructure\Persistence\Doctrine\Fixtures\RegulationOrderRecordFixture;
use App\Infrastructure\Persistence\Doctrine\Fixtures\UserFixture;
use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;

final class GetMeasureControllerTest extends AbstractWebTestCase
{
    public function testGet(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/measure/' . MeasureFixture::UUID_TYPICAL);

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $measure1Header = $crawler->filter('[data-testid="measure"]')->eq(0);
        $measure1Content = $crawler->filter('[data-testid="measure-content"]')->eq(0);

        $this->assertSame('Circulation interdite', $measure1Header->filter('h3')->text());
        $this->assertSame('pour tous les véhicules', $measure1Content->filter('li')->eq(0)->text());
        $this->assertSame('du 31/10/2023 à 09h00 au 31/10/2023 à 23h00', $measure1Content->filter('li')->eq(1)->text());
        $this->assertSame('Rue Adrien Lesesne à Saint-Ouen-sur-Seine', $measure1Content->filter('li')->eq(3)->text());
        $this->assertSame(LocationFixture::UUID_TYPICAL, $measure1Content->filter('li')->eq(4)->attr('data-location-uuid'));
        $this->assertSame('Rue Eugène Berthoud du n° 47 au n° 65 à Saint-Ouen-sur-Seine', $measure1Content->filter('.app-card__content li')->eq(4)->text());

        $editForm = $crawler->selectButton('Modifier')->form();
        $this->assertSame('http://localhost/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/measure/' . MeasureFixture::UUID_TYPICAL . '/form', $editForm->getUri());
        $this->assertSame('GET', $editForm->getMethod());
    }

    public function testGetWithComplexVehicles(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_COMPLEX_VEHICLES . '/measure/' . MeasureFixture::UUID_COMPLEX_VEHICLES);

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $measure1Header = $crawler->filter('[data-testid="measure"]')->eq(0);
        $measure1Content = $crawler->filter('[data-testid="measure-content"]')->eq(0);

        $this->assertSame('Circulation interdite', $measure1Header->filter('h3')->text());
        $this->assertSame('pour les véhicules de plus de 3,5 tonnes, 12 mètres de long ou 2,4 mètres de haut, matières dangereuses, Crit\'Air 4 et Crit\'Air 5, sauf piétons, véhicules d\'urgence et convois exceptionnels', $measure1Content->filter('li')->eq(0)->text());
        $this->assertSame('du 10/03/2023 à 00h00 au 20/03/2023 à 23h59du 28/03/2023 à 08h00 au 28/03/2023 à 22h00', $measure1Content->filter('li')->eq(1)->text());
        $this->assertSame('Rue Ardoin du n° 87 au n° 63 à Saint-Ouen-sur-Seine', $measure1Content->filter('li')->eq(3)->text());
        $this->assertSame('Rue La Clef Des Champs à Saint-Ouen-sur-Seine', $measure1Content->filter('li')->eq(4)->text());
        $this->assertSame('D322 (Ardennes) du PR 1+0 (côté U) au PR 4+0 (côté U)', $measure1Content->filter('li')->eq(5)->text());
        $this->assertSame('Rue Albert Dhalenne du n° 12 au n° 34 à Saint-Ouen-sur-Seine', $measure1Content->filter('li')->eq(6)->text());
    }

    public function testGetLocationFromOtherRegulationOrderRecord(): void
    {
        $client = $this->login();
        $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_PERMANENT . '/measure/' . MeasureFixture::UUID_TYPICAL);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testGetCityOnly(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_FULL_CITY . '/measure/' . MeasureFixture::UUID_FULL_CITY);

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $this->assertSame('à Paris 18e Arrondissement (75018)', $crawler->filter('li')->eq(3)->text());
    }

    public function testIfPublishedThenCannotEdit(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_PUBLISHED . '/measure/' . MeasureFixture::UUID_PUBLISHED);

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $this->assertSame(0, $crawler->filter('a')->count());
    }

    public function testRegulationDoesNotExist(): void
    {
        $client = $this->login();
        $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_DOES_NOT_EXIST . '/measure/' . MeasureFixture::UUID_TYPICAL);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testMeasureDoesNotExist(): void
    {
        $client = $this->login();
        $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/measure/' . MeasureFixture::UUID_DOES_NOT_EXIST);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testCannotAccessBecauseDifferentOrganization(): void
    {
        $client = $this->login(UserFixture::OTHER_ORG_USER_EMAIL);
        $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/measure/' . MeasureFixture::UUID_TYPICAL);
        $this->assertResponseStatusCodeSame(403);
    }

    public function testWithoutAuthenticatedUser(): void
    {
        $client = static::createClient();
        $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/measure/' . MeasureFixture::UUID_TYPICAL);
        $this->assertResponseRedirects('http://localhost/login', 302);
    }

    public function testBadUuid(): void
    {
        $client = static::createClient();
        $client->request('GET', '/_fragment/regulations/aaa/measure/bbb');
        $this->assertResponseStatusCodeSame(404);
    }
}
