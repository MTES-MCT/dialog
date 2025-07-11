<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\Regulation;

use App\Infrastructure\Persistence\Doctrine\Fixtures\MeasureFixture;
use App\Infrastructure\Persistence\Doctrine\Fixtures\OrganizationFixture;
use App\Infrastructure\Persistence\Doctrine\Fixtures\RegulationOrderRecordFixture;
use App\Infrastructure\Persistence\Doctrine\Fixtures\UserFixture;
use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;

final class RegulationDetailControllerTest extends AbstractWebTestCase
{
    public function testDraftRegulationDetailAsAdmin(): void
    {
        $client = $this->login(UserFixture::DEPARTMENT_93_ADMIN_EMAIL);
        $crawler = $client->request('GET', '/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL);

        $this->assertSecurityHeaders();
        $this->assertResponseStatusCodeSame(200);
        $this->assertSame('Arrêté temporaire FO1/2023', $crawler->filter('h2')->text());
        $this->assertMetaTitle('Arrêté temporaire FO1/2023 - DiaLog', $crawler);
        $this->assertSame('Brouillon', $crawler->filter('[data-testid="status-badge"]')->text());

        $generalInfo = $crawler->filter('[data-testid="general_info"]');
        $measures = $crawler->filter('[data-testid="measure"]');

        // General info
        $this->assertSame('Title 1', $generalInfo->filter('h3')->text());
        $this->assertSame(OrganizationFixture::SEINE_SAINT_DENIS_NAME, $generalInfo->filter('li')->eq(0)->text());
        $this->assertSame('Évènement', $generalInfo->filter('li')->eq(1)->text());
        $this->assertSame('Title 1', $generalInfo->filter('li')->eq(2)->text());
        $editGeneralInfoForm = $generalInfo->selectButton('Modifier')->form();
        $this->assertSame('http://localhost/_fragment/regulations/general_info/form/' . RegulationOrderRecordFixture::UUID_TYPICAL, $editGeneralInfoForm->getUri());
        $this->assertSame('GET', $editGeneralInfoForm->getMethod());

        // Measure 1
        $measure1Header = $crawler->filter('[data-testid="measure"]')->eq(0);
        $measure1Content = $crawler->filter('[data-testid="measure-content"]')->eq(0);

        $this->assertSame('Vitesse limitée à 50 km/h', $measure1Header->filter('h3')->text());
        $this->assertSame('pour tous les véhicules', $measure1Content->filter('li')->eq(0)->text());
        $this->assertSame('tous les jours', $measure1Content->filter('li')->eq(1)->text());
        $this->assertSame('Rue du Docteur Bauer à Saint-Ouen-sur-Seine', $measure1Content->filter('li')->eq(3)->text());

        // Measure 2
        $measure2Header = $crawler->filter('[data-testid="measure"]')->eq(1);
        $measure2Content = $crawler->filter('[data-testid="measure-content"]')->eq(1);

        $this->assertSame('Circulation interdite', $measure2Header->filter('h3')->text());
        $this->assertSame('pour tous les véhicules', $measure2Content->filter('li')->eq(0)->text());
        $this->assertSame('du 31/10/2023 à 09h00 au 31/10/2023 à 23h00', $measure2Content->filter('li')->eq(1)->text());
        $this->assertSame('Rue Adrien Lesesne à Saint-Ouen-sur-Seine', $measure2Content->filter('li')->eq(3)->text());
        $this->assertSame('Rue Eugène Berthoud du n° 47 au n° 65 à Saint-Ouen-sur-Seine', $measure2Content->filter('li')->eq(4)->text());

        $editLocationForm = $measures->eq(1)->selectButton('Modifier')->form();
        $this->assertSame(
            'http://localhost/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/measure/' . MeasureFixture::UUID_TYPICAL . '/form',
            $editLocationForm->getUri(),
        );
        $this->assertSame('GET', $editLocationForm->getMethod());

        // Actions
        $duplicateForm = $crawler->selectButton('Dupliquer')->form();
        $this->assertSame($duplicateForm->getUri(), 'http://localhost/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/duplicate');
        $this->assertSame($duplicateForm->getMethod(), 'POST');

        $btnsDelete = $crawler->filter('aside')->selectButton('Supprimer');
        $formDelete = $btnsDelete->eq(0)->form();
        $this->assertSame($formDelete->getUri(), 'http://localhost/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL);
        $this->assertSame($formDelete->getMethod(), 'POST');
        $this->assertSame($formDelete->get('_method')->getValue(), 'DELETE');

        $publishBtn = $crawler->selectButton('Publier');
        $this->assertSame(0, $crawler->selectButton('Valider')->count()); // Location form
        $this->assertSame('http://localhost/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/publish', $publishBtn->form()->getUri());
        $this->assertSame('POST', $publishBtn->form()->getMethod());
        $this->assertCount(1, $publishBtn->siblings()->filter('input[name="_token"]'));

        // Go back link
        $goBackLink = $crawler->selectLink('Revenir aux arrêtés');
        $this->assertSame('/regulations', $goBackLink->extract(['href'])[0]);

        // Ressources
        $btnDelete = $crawler->filter('[data-testid="delete"]');
        $deleteStorageForm = $btnDelete->form();
        $this->assertSame($deleteStorageForm->getUri(), 'http://localhost/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/storage/delete');
        $this->assertSame($deleteStorageForm->getMethod(), 'POST');
        $this->assertSame($deleteStorageForm->get('_method')->getValue(), 'DELETE');
    }

    public function testDraftRegulationDetailAsContributor(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL);

        $this->assertEmpty($crawler->filter('[data-testid="history"]')); // No history fixture
        $this->assertSame(0, $crawler->selectButton('Publier')->count());
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
        $this->assertSame('/regulations', $goBackLink->extract(['href'])[0]);
    }

    public function testDraftRegulationDetailWithoutLocations(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/regulations/' . RegulationOrderRecordFixture::UUID_NO_LOCATIONS);
        $this->assertSecurityHeaders();
        $this->assertResponseStatusCodeSame(200);

        // Actions
        $saveButton = $crawler->selectButton('Valider');
        $this->assertSame('Indiquez le type de restriction et ses localisations', $crawler->filter('.app-card__content p')->text());
        $this->assertSame(1, $saveButton->count()); // Measure form

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
        $this->assertSame($formDelete->getMethod(), 'POST');
        $this->assertSame($formDelete->get('_method')->getValue(), 'DELETE');
    }

    public function testCannotAccessBecauseDifferentOrganization(): void
    {
        $client = $this->login(UserFixture::OTHER_ORG_USER_EMAIL);
        $crawler = $client->request('GET', '/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL);

        $this->assertSame(0, $crawler->selectButton('Publier')->count());
        $this->assertSame(0, $crawler->selectButton('Supprimer')->count());
        $this->assertSame(0, $crawler->selectButton('Dupliquer')->count());
        $this->assertSame(0, $crawler->selectButton('Modifier')->count());
        $this->assertSame(0, $crawler->selectButton('Partager')->count());

        $this->assertResponseStatusCodeSame(200);
    }

    public function testRegulationOrderRecordNotFound(): void
    {
        $client = $this->login();
        $client->request('GET', '/regulations/' . RegulationOrderRecordFixture::UUID_DOES_NOT_EXIST);

        $this->assertResponseStatusCodeSame(404);
    }

    public function testPublishedWithoutAuthenticatedUser(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/regulations/' . RegulationOrderRecordFixture::UUID_PUBLISHED);

        $this->assertResponseStatusCodeSame(200);

        $this->assertSame(0, $crawler->selectButton('Publier')->count());
        $this->assertSame(0, $crawler->selectButton('Supprimer')->count());
        $this->assertSame(0, $crawler->selectButton('Dupliquer')->count());
        $this->assertSame(0, $crawler->selectButton('Modifier')->count());
    }

    public function testDraftWithoutAuthenticatedUser(): void
    {
        $client = static::createClient();
        $client->request('GET', '/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL);

        $this->assertResponseStatusCodeSame(403);
    }
}
