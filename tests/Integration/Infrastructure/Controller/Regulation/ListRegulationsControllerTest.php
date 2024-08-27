<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\Regulation;

use App\Domain\Regulation\Enum\RegulationOrderRecordStatusEnum;
use App\Domain\Regulation\Enum\RegulationOrderTypeEnum;
use App\Infrastructure\Persistence\Doctrine\Fixtures\OrganizationFixture;
use App\Infrastructure\Persistence\Doctrine\Fixtures\RegulationOrderRecordFixture;
use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;

final class ListRegulationsControllerTest extends AbstractWebTestCase
{
    public function testNavAndPagination(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/regulations?pageSize=1&page=1');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();
        $this->assertSame('Arrêtés de circulation', $crawler->filter('h3')->text());
        $this->assertMetaTitle('Arrêtés de circulation - DiaLog', $crawler);
        $this->assertSkipLinks(
            [
                ['Contenu', '#content'],
                ['Pied de page', '#footer'],
            ],
            $crawler,
        );

        $navLi = $crawler->filter('nav.fr-pagination')->filter('li');
        $this->assertSame('Première page', $navLi->eq(0)->filter('a')->text());
        $this->assertSame('Page précédente', $navLi->eq(1)->filter('a')->text());
        $this->assertSame('1', $navLi->eq(2)->filter('a')->text());
        $this->assertSame('2', $navLi->eq(3)->filter('a')->text());
        $this->assertSame('3', $navLi->eq(4)->filter('a')->text());
        $this->assertSame('...', $navLi->eq(5)->text());
        $this->assertSame('11', $navLi->eq(6)->filter('a')->text());
        $this->assertSame('Page suivante', $navLi->eq(7)->filter('a')->text());
        $this->assertSame('Dernière page', $navLi->eq(8)->filter('a')->text());

        $client->clickLink('Ajouter un arrêté');
        $this->assertRouteSame('app_regulation_add');
    }

    public function testRegulationRendering(): void
    {
        $client = $this->login();

        // First item
        $pageOne = $client->request('GET', '/regulations?pageSize=1&page=2');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $pageOneRows = $pageOne->filter('.app-regulation-table tbody > tr');
        $this->assertSame(1, $pageOneRows->count());

        $pageOneRow0 = $pageOneRows->eq(0)->filter('td');
        $this->assertSame('F2023/no-locations', $pageOneRow0->eq(0)->text());
        $this->assertSame('Main Org', $pageOneRow0->eq(1)->text());
        $this->assertEmpty($pageOneRow0->eq(2)->text()); // No location set
        $this->assertSame('du 13/07/2023 au 15/07/2023 passé', $pageOneRow0->eq(3)->text());
        $this->assertSame('Brouillon', $pageOneRow0->eq(4)->text());

        $links = $pageOneRow0->eq(5)->filter('a');
        $this->assertSame('Modifier', $links->eq(0)->text());
        $this->assertSame('http://localhost/regulations/' . RegulationOrderRecordFixture::UUID_NO_LOCATIONS, $links->eq(0)->link()->getUri());

        // Second item
        $pageTwo = $client->request('GET', '/regulations?pageSize=1&page=3');
        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $pageTwoRows = $pageTwo->filter('.app-regulation-table tbody > tr');
        $this->assertSame(1, $pageTwoRows->count()); // One item per page

        $pageTwoRow0 = $pageTwoRows->eq(0)->filter('td');
        $this->assertSame('F/CIFS/2023', $pageTwoRow0->eq(0)->text());
        $this->assertSame('Main Org', $pageTwoRow0->eq(1)->text());
        $this->assertSame('Montauban (82000) Rue de la République + 1 localisation', $pageTwoRow0->eq(2)->text());
        $this->assertSame('du 02/06/2023 au 10/06/2023 passé', $pageTwoRow0->eq(3)->text());
        $this->assertSame('Publié', $pageTwoRow0->eq(4)->text());
    }

    public function testPublishedRegulationRendering(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/regulations?pageSize=1&page=7');
        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $rows = $crawler->filter('.app-regulation-table tbody > tr');
        $this->assertSame(1, $rows->count());

        $row0 = $rows->eq(0)->filter('td');
        $this->assertSame('FO2/2023', $row0->eq(0)->text());
        $this->assertSame('Main Org', $row0->eq(1)->text());
        $this->assertSame('Montauban (82000) Avenue de Fonneuve + 3 localisations', $row0->eq(2)->text());
        $this->assertSame('du 10/03/2023 au 20/03/2023 passé', $row0->eq(3)->text());
        $this->assertSame('Publié', $row0->eq(4)->text());

        $links = $row0->eq(5)->filter('a');
        $this->assertSame('Voir le détail', $links->eq(0)->text());
        $this->assertSame('http://localhost/regulations/' . RegulationOrderRecordFixture::UUID_PUBLISHED, $links->eq(0)->link()->getUri());
    }

    public function testIdentifierFilter(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/regulations');
        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        // Inspect filter field
        $searchButton = $crawler->selectButton('Rechercher');
        $form = $searchButton->form();
        $field = $form->get('identifier');
        $this->assertSame("Identifiant de l'arrêté", trim($field->getLabel()->nodeValue));
        $this->assertSame('Rechercher un identifiant', $crawler->filter('form[role="search"] input[name="identifier"]')->attr('placeholder'));
        $this->assertSame('', $field->getValue());

        // Submit filter
        $form['identifier'] = '2024';
        $crawler = $client->submit($form);

        $rows = $crawler->filter('.app-regulation-table tbody > tr');
        $this->assertSame(1, $rows->count());

        $identifiers = $rows->filter('td:nth-child(1)')->each(fn ($node) => $node->text());
        $this->assertSame('F2024/RAWGEOJSON', implode(', ', $identifiers));
    }

    public function testFiltersForm(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/regulations');
        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        // Inspect form
        $searchButton = $crawler->selectButton('Rechercher');
        $form = $searchButton->form();
        $this->assertSame('search', $form->getFormNode()->getAttribute('role'));
    }

    public function testOrganizationFilter(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/regulations');
        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        // Inspect filter field
        $searchButton = $crawler->selectButton('Rechercher');
        $form = $searchButton->form();
        $field = $form->get('organization');
        $this->assertSame('Organisation', trim($field->getLabel()->nodeValue));
        $choices = $crawler->filter('form[role="search"] select[name="organization"] > option')->each(fn ($node) => [$node->attr('value'), $node->text()]);
        $this->assertEquals([
            ['', 'Toutes les organisations'],
            [OrganizationFixture::MAIN_ORG_ID, 'Main Org'],
            [OrganizationFixture::OTHER_ORG_ID, 'Mairie de Savenay'],
        ], $choices);
        $this->assertSame('', $field->getValue());

        // Submit filter
        $form['organization'] = OrganizationFixture::OTHER_ORG_ID;
        $crawler = $client->submit($form);

        $rows = $crawler->filter('.app-regulation-table tbody > tr');
        $this->assertSame(2, $rows->count());

        $organizations = $rows->filter('td:nth-child(2)')->each(fn ($node) => $node->text());
        $this->assertSame('Mairie de Savenay, Mairie de Savenay', implode(', ', $organizations));
    }

    public function testRegulationOrderTypeFilterPermanent(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/regulations');
        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        // Inspect filter field
        $searchButton = $crawler->selectButton('Rechercher');
        $form = $searchButton->form();
        $field = $form->get('regulationOrderType');
        $this->assertSame("Type d'arrêté", trim($field->getLabel()->nodeValue));
        $choices = $crawler->filter('form[role="search"] select[name="regulationOrderType"] > option')->each(fn ($node) => [$node->attr('value'), $node->text()]);
        $this->assertEquals([
            ['', 'Tous les arrêtés'],
            [RegulationOrderTypeEnum::PERMANENT->value, 'Arrêtés permanents'],
            [RegulationOrderTypeEnum::TEMPORARY->value, 'Arrêtés temporaires'],
        ], $choices);
        $this->assertSame('', $field->getValue());

        // Submit filter
        $form['regulationOrderType'] = RegulationOrderTypeEnum::PERMANENT->value;
        $crawler = $client->submit($form);

        $rows = $crawler->filter('.app-regulation-table tbody > tr');
        $this->assertSame(2, $rows->count());

        $statuses = $rows->filter('td:nth-child(4)')->each(fn ($node) => $node->text());
        $this->assertSame(', à partir du 11/03/2023 en cours', implode(', ', $statuses));
    }

    public function testRegulationOrderTypeFilterTemporary(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/regulations');
        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $searchButton = $crawler->selectButton('Rechercher');
        $form = $searchButton->form();
        $form['regulationOrderType'] = RegulationOrderTypeEnum::TEMPORARY->value;
        $crawler = $client->submit($form);

        $rows = $crawler->filter('.app-regulation-table tbody > tr');
        $this->assertSame(9, $rows->count());
    }

    public function testStatusFilterPublished(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/regulations');
        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        // Inspect filter field
        $searchButton = $crawler->selectButton('Rechercher');
        $form = $searchButton->form();
        $field = $form->get('status');
        $this->assertSame('Statut des arrêtés', trim($field->getLabel()->nodeValue));
        $choices = $crawler->filter('form[role="search"] select[name="status"] > option')->each(fn ($node) => [$node->attr('value'), $node->text()]);
        $this->assertEquals([
            ['', 'Tous les statuts'],
            [RegulationOrderRecordStatusEnum::DRAFT, 'Brouillon'],
            [RegulationOrderRecordStatusEnum::PUBLISHED, 'Publié'],
        ], $choices);
        $this->assertSame('', $field->getValue());

        // Submit filter
        $form['status'] = RegulationOrderRecordStatusEnum::PUBLISHED;
        $crawler = $client->submit($form);

        $rows = $crawler->filter('.app-regulation-table tbody > tr');
        $this->assertSame(4, $rows->count());

        $statuses = $rows->filter('td:nth-child(5)')->each(fn ($node) => $node->text());
        $this->assertSame('Publié, Publié, Publié, Publié', implode(', ', $statuses));
    }

    public function testStatusFilterDraft(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/regulations');
        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $searchButton = $crawler->selectButton('Rechercher');
        $form = $searchButton->form();
        $form['status'] = RegulationOrderRecordStatusEnum::DRAFT;
        $crawler = $client->submit($form);

        $rows = $crawler->filter('.app-regulation-table tbody > tr');
        $this->assertSame(7, $rows->count());

        $rows->filter('td:nth-child(5)')->each(function ($node) {
            $this->assertSame('Brouillon', $node->text());
        });
    }

    public function testStatusFilterAsAnonymousUser(): void
    {
        $client = $this->login(null);
        $crawler = $client->request('GET', '/regulations');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        // Filter is hidden in form
        $searchButton = $crawler->selectButton('Rechercher');
        $form = $searchButton->form();
        $field = $form->get('status');
        $this->assertSame('published', $field->getValue());
        $node = $crawler->filter('form[role="search"] select[name="status"]')->first();
        $this->assertNotNull($node->closest('[class*="fr-hidden"]'));

        $rows = $crawler->filter('.app-regulation-table tbody > tr');
        $this->assertSame(4, $rows->count());

        $statuses = $rows->filter('td:nth-child(5)')->each(fn ($node) => $node->text());
        $this->assertSame('Publié, Publié, Publié, Publié', implode(', ', $statuses));
    }

    public function testStatusFilterAsAnonymousUserForceDraft(): void
    {
        $client = $this->login(null);
        $crawler = $client->request('GET', '/regulations?status=draft'); // Try to force with query parameter

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $searchButton = $crawler->selectButton('Rechercher');
        $form = $searchButton->form();
        $this->assertSame('published', $form->get('status')->getValue());

        $rows = $crawler->filter('.app-regulation-table tbody > tr');
        $this->assertSame(4, $rows->count());

        $statuses = $rows->filter('td:nth-child(5)')->each(fn ($node) => $node->text());
        $this->assertSame('Publié, Publié, Publié, Publié', implode(', ', $statuses));
    }

    public function testFilterCombinationViaUrl(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', \sprintf(
            '/regulations?identifier=FO2&organization=%s&regulationOrderType=%s&status=%s',
            OrganizationFixture::MAIN_ORG_ID,
            RegulationOrderTypeEnum::TEMPORARY->value,
            RegulationOrderRecordStatusEnum::PUBLISHED,
        ));
        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $rows = $crawler->filter('.app-regulation-table tbody > tr');
        $this->assertSame(2, $rows->count());

        $identifiers = $rows->filter('td:nth-child(1)')->each(fn ($node) => $node->text());
        $this->assertSame('FO2/2023, FO2/2023-1', implode(', ', $identifiers));
    }

    public function testInvalidPageSize(): void
    {
        $client = $this->login();
        $client->request('GET', '/regulations?pageSize=0');
        $this->assertResponseStatusCodeSame(400);

        $client->request('GET', '/regulations?pageSize=-1');
        $this->assertResponseStatusCodeSame(400);

        $client->request('GET', '/regulations?pageSize=abc');
        $this->assertResponseStatusCodeSame(400);
    }

    public function testInvalidPageNumber(): void
    {
        $client = $this->login();
        $client->request('GET', '/regulations?page=0');
        $this->assertResponseStatusCodeSame(400);

        $client->request('GET', '/regulations?page=-1');
        $this->assertResponseStatusCodeSame(400);

        $client->request('GET', '/regulations?page=abc');
        $this->assertResponseStatusCodeSame(400);
    }

    public function testWithoutAuthenticatedUser(): void
    {
        $client = $this->login(null);
        $pageOne = $client->request('GET', '/regulations');

        $this->assertResponseStatusCodeSame(200);

        // check that the only link of the first row is to view the regulation
        $rows = $pageOne->filter('.app-regulation-table tbody > tr');
        $row = $rows->eq(0)->filter('td');
        $links = $row->filter('a');
        $this->assertCount(1, $links);
        $this->assertSame('Voir le détail', $links->eq(0)->text());
    }
}
