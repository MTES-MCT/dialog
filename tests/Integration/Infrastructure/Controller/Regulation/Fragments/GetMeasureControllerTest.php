<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\Regulation\Fragments;

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
        $this->assertSame('Rue Victor Hugo Savenay (44260)', $measure1Content->filter('li')->eq(3)->text());
        $this->assertSame('Route du Grand Brossais du n° 15 au n° 37bis Savenay (44260)', $measure1Content->filter('.app-card__content li')->eq(4)->text());

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
        $this->assertSame('pour les véhicules de plus de 3,5 tonnes, 12 mètres de long ou 2,4 mètres de haut, Crit\'Air 4 et Crit\'Air 5, sauf piétons, véhicules d\'urgence et convois exceptionnels', $measure1Content->filter('li')->eq(0)->text());
        $this->assertSame('tous les jours', $measure1Content->filter('li')->eq(1)->text());
        $this->assertSame('Rue de l\'Hôtel de Ville du n° 30 au n° 12 Montauban (82000)', $measure1Content->filter('li')->eq(3)->text());
        $this->assertSame('Rue Gamot Montauban (82000)', $measure1Content->filter('li')->eq(4)->text());
        $this->assertSame('D322 (Ardennes)', $measure1Content->filter('li')->eq(5)->text());
        $this->assertSame('Avenue de Fonneuve du n° 695 au n° 253 Montauban (82000)', $measure1Content->filter('li')->eq(6)->text());
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

        $this->assertSame('Paris 18e Arrondissement (75018)', $crawler->filter('li')->eq(3)->text());
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
