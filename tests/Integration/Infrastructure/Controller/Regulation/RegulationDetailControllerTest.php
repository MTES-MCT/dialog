<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\Regulation;

use App\Infrastructure\Persistence\Doctrine\Fixtures\LocationFixture;
use App\Infrastructure\Persistence\Doctrine\Fixtures\OrganizationFixture;
use App\Infrastructure\Persistence\Doctrine\Fixtures\RegulationOrderRecordFixture;
use App\Infrastructure\Persistence\Doctrine\Fixtures\UserFixture;
use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;

final class RegulationDetailControllerTest extends AbstractWebTestCase
{
    public function testDraftRegulationDetail(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL);

        $this->assertSecurityHeaders();
        $this->assertResponseStatusCodeSame(200);
        $this->assertSame('Arrêté temporaire FO1/2023', $crawler->filter('h2')->text());
        $this->assertMetaTitle('Arrêté temporaire FO1/2023 - DiaLog', $crawler);
        $this->assertSame('Brouillon', $crawler->filter('[data-testid="status-badge"]')->text());

        $generalInfo = $crawler->filter('[data-testid="general_info"]');
        $location = $crawler->filter('[data-testid="location"]');

        // General info
        $this->assertSame('Description 1', $generalInfo->filter('h3')->text());
        $this->assertSame(OrganizationFixture::MAIN_ORG_NAME, $generalInfo->filter('li')->eq(0)->text());
        $this->assertSame('Évènement', $generalInfo->filter('li')->eq(1)->text());
        $this->assertSame('Description 1', $generalInfo->filter('li')->eq(2)->text());
        $this->assertSame('Du 13/03/2023 au 15/03/2023', $generalInfo->filter('li')->eq(3)->text());
        $editGeneralInfoForm = $generalInfo->selectButton('Modifier')->form();
        $this->assertSame('http://localhost/_fragment/regulations/general_info/form/' . RegulationOrderRecordFixture::UUID_TYPICAL, $editGeneralInfoForm->getUri());
        $this->assertSame('GET', $editGeneralInfoForm->getMethod());

        // Location
        $this->assertSame('Route du Grand Brossais', $location->filter('h3')->text());
        $this->assertSame('Savenay (44260)', $location->filter('li')->eq(0)->text());
        $this->assertSame('Route du Grand Brossais du n° 15 au n° 37bis', $location->filter('li')->eq(1)->text());
        $this->assertSame('Circulation interdite du 31/10/2023 - 08h00 au 31/10/2023 - 22h00 pour tous les véhicules', $location->filter('li')->eq(2)->text());
        $editLocationForm = $location->selectButton('Modifier')->form();
        $this->assertSame(
            'http://localhost/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/location/' . LocationFixture::UUID_TYPICAL . '/form',
            $editLocationForm->getUri(),
        );
        $this->assertSame('GET', $editLocationForm->getMethod());

        // Actions
        $duplicateForm = $crawler->selectButton('Dupliquer')->form();
        $this->assertSame($duplicateForm->getUri(), 'http://localhost/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/duplicate');
        $this->assertSame($duplicateForm->getMethod(), 'POST');

        $formDelete = $crawler->filter('aside')->selectButton('Supprimer')->form();
        $this->assertSame($formDelete->getUri(), 'http://localhost/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL);
        $this->assertSame($formDelete->getMethod(), 'DELETE');

        $publishBtn = $crawler->selectButton('Publier');
        $this->assertSame(0, $crawler->selectButton('Valider')->count()); // Location form
        $this->assertSame('http://localhost/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/publish', $publishBtn->form()->getUri());
        $this->assertSame('POST', $publishBtn->form()->getMethod());
        $this->assertCount(1, $publishBtn->siblings()->filter('input[name="token"]'));

        // Go back link
        $goBackLink = $crawler->selectLink('Revenir aux arrêtés');
        $this->assertSame('/regulations?tab=temporary', $goBackLink->extract(['href'])[0]);
    }

    public function testDraftRegulationWithMeasuresDetail(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '?feature_loc_inversion=true');

        $this->assertSecurityHeaders();
        $this->assertResponseStatusCodeSame(200);
        $this->assertSame('Arrêté temporaire FO1/2023', $crawler->filter('h2')->text());
        $this->assertMetaTitle('Arrêté temporaire FO1/2023 - DiaLog', $crawler);
        $this->assertSame('Brouillon', $crawler->filter('[data-testid="status-badge"]')->text());

        $measureTitle = $crawler->filter('[data-testid="measure"]');
        $measureDetail = $crawler->filter('[data-testid="measure-detail-items"]');

        // Measures
        $this->assertSame('Circulation interdite', $measureTitle->filter('h3')->text());
        $this->assertSame('pour tous les véhicules', $measureDetail->filter('li')->eq(0)->text());
        $this->assertSame('du 31/10/2023 - 08h00 au 31/10/2023 - 22h00', $measureDetail->filter('li')->eq(1)->text());
        $this->assertSame('Route du Grand Brossais du n° 15 au n° 37bis Savenay (44260)', $measureDetail->filter('li')->eq(3)->text());
    }

    public function testPermanentRegulationDetail(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/regulations/' . RegulationOrderRecordFixture::UUID_PERMANENT);

        $this->assertSecurityHeaders();
        $this->assertResponseStatusCodeSame(200);
        $this->assertSame('Arrêté permanent FO3/2023', $crawler->filter('h2')->text());
        $this->assertMetaTitle('Arrêté permanent FO3/2023 - DiaLog', $crawler);

        $goBackLink = $crawler->selectLink('Revenir aux arrêtés');
        $this->assertSame('/regulations?tab=permanent', $goBackLink->extract(['href'])[0]);
    }

    public function testDraftRegulationDetailWithoutLocations(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/regulations/' . RegulationOrderRecordFixture::UUID_NO_LOCATIONS);
        $this->assertSecurityHeaders();
        $this->assertResponseStatusCodeSame(200);

        // Actions
        $saveButton = $crawler->selectButton('Valider');
        $this->assertSame('Indiquez la voie concernée, par exemple Rue de la République.', $crawler->filter('#location_form_roadName_help')->text());
        $this->assertSame(1, $saveButton->count()); // Location form

        $duplicateButton = $crawler->selectButton('Dupliquer')->form();
        $this->assertSame($duplicateButton->getUri(), 'http://localhost/regulations/' . RegulationOrderRecordFixture::UUID_NO_LOCATIONS . '/duplicate');
        $this->assertSame($duplicateButton->getMethod(), 'POST');
    }

    public function testPublishedRegulationDetail(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/regulations/' . RegulationOrderRecordFixture::UUID_PUBLISHED);

        $this->assertSecurityHeaders();
        $this->assertResponseStatusCodeSame(200);

        $this->assertSame('Publié', $crawler->filter('[data-testid="status-badge"]')->text());
        $this->assertSame(0, $crawler->selectButton('Modifier')->count()); // No edit buttons
        $this->assertSame(0, $crawler->selectButton('Publier')->count());

        $formDelete = $crawler->filter('aside')->selectButton('Supprimer')->form();
        $this->assertSame($formDelete->getUri(), 'http://localhost/regulations/' . RegulationOrderRecordFixture::UUID_PUBLISHED);
        $this->assertSame($formDelete->getMethod(), 'DELETE');
    }

    public function testSeeAll(): void
    {
        $client = $this->login();
        $client->request('GET', '/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL);

        $this->assertSecurityHeaders();
        $this->assertResponseStatusCodeSame(200);

        $crawler = $client->clickLink('Arrêtés de circulation');
        $this->assertRouteSame('app_regulations_list');

        // Temporary regulation order list is shown.
        $temporaryPanel = $crawler->filter('#temporary-panel');
        $this->assertStringContainsString('fr-tabs__panel--selected', $temporaryPanel->attr('class'));
    }

    public function testCannotAccessBecauseDifferentOrganization(): void
    {
        $client = $this->login(UserFixture::OTHER_ORG_USER_EMAIL);
        $client->request('GET', '/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testRegulationOrderRecordNotFound(): void
    {
        $client = $this->login();
        $client->request('GET', '/regulations/' . RegulationOrderRecordFixture::UUID_DOES_NOT_EXIST);

        $this->assertResponseStatusCodeSame(404);
    }

    public function testWithoutAuthenticatedUser(): void
    {
        $client = static::createClient();
        $client->request('GET', '/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL);

        $this->assertResponseRedirects('http://localhost/login', 302);
    }
}
