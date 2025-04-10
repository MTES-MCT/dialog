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

    public function testOrdering(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/regulations?pageSize=5&page=1');

        $rows = $crawler->filter('[data-testid="app-regulation-table"] tbody > tr');
        $this->assertSame(5, $rows->count());
        // Les arrêtés sont triés par date de début décroissante
        // Les 2 premiers n'en ont pas pour des raisons de test
        $this->assertSame('', $rows->eq(0)->filter('td')->eq(3)->text());
        $this->assertSame('', $rows->eq(1)->filter('td')->eq(3)->text());
        $this->assertSame('du 15/01/2025 au 30/01/2025 passé', $rows->eq(2)->filter('td')->eq(3)->text());
        $this->assertSame('du 15/01/2025 au 30/01/2025 passé', $rows->eq(3)->filter('td')->eq(3)->text());
        $this->assertSame('du 31/10/2023 au 31/10/2023 passé', $rows->eq(4)->filter('td')->eq(3)->text());
    }

    public function testRegulationRendering(): void
    {
        $client = $this->login();

        // First item
        $pageOne = $client->request('GET', '/regulations?identifier=FO1%2F2023');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $pageOneRows = $pageOne->filter('[data-testid="app-regulation-table"] tbody > tr');
        $this->assertSame(1, $pageOneRows->count());

        $pageOneRow0 = $pageOneRows->eq(0)->filter('td');
        $this->assertSame('FO1/2023', $pageOneRow0->eq(0)->text());
        $this->assertSame('Département de Seine-Saint-Denis', $pageOneRow0->eq(1)->text());
        $this->assertSame('Saint-Ouen-sur-Seine Rue Eugène Berthoud + 3 localisations', $pageOneRow0->eq(2)->text());
        $this->assertSame('du 31/10/2023 au 31/10/2023 passé', $pageOneRow0->eq(3)->text());
        $this->assertSame('Brouillon', $pageOneRow0->eq(4)->text());

        $links = $pageOneRow0->eq(5)->filter('a');
        $this->assertSame('Modifier', $links->eq(0)->text());
        $this->assertSame('http://localhost/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL, $links->eq(0)->link()->getUri());

        // Second item
        $otherPage = $client->request('GET', '/regulations?identifier=F%2FCIFS%2F2023');
        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $pageTwoRows = $otherPage->filter('[data-testid="app-regulation-table"] tbody > tr');
        $this->assertSame(1, $pageTwoRows->count()); // One item per page

        $pageTwoRow0 = $pageTwoRows->eq(0)->filter('td');
        $this->assertSame('F/CIFS/2023', $pageTwoRow0->eq(0)->text());
        $this->assertSame('Département de Seine-Saint-Denis', $pageTwoRow0->eq(1)->text());
        $this->assertSame('Saint-Ouen-sur-Seine Rue Claude Monet + 1 localisation', $pageTwoRow0->eq(2)->text());
        $this->assertSame('du 02/06/2023 au 10/06/2023 passé', $pageTwoRow0->eq(3)->text());
        $this->assertSame('Publié', $pageTwoRow0->eq(4)->text());
    }

    public function testPublishedRegulationRendering(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/regulations?identifier=FO2%2F2023');
        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $rows = $crawler->filter('[data-testid="app-regulation-table"] tbody > tr');
        $this->assertSame(1, $rows->count());

        $row0 = $rows->eq(0)->filter('td');
        $this->assertSame('FO2/2023', $row0->eq(0)->text());
        $this->assertSame('Département de Seine-Saint-Denis', $row0->eq(1)->text());
        $this->assertSame('Saint-Ouen-sur-Seine Rue Albert Dhalenne + 3 localisations', $row0->eq(2)->text());
        $this->assertSame('du 10/03/2023 au 28/03/2023 passé', $row0->eq(3)->text());
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
        $this->assertSame('Identifiant', trim($field->getLabel()->nodeValue));
        $this->assertSame('Rechercher un identifiant', $crawler->filter('form[role="search"] input[name="identifier"]')->attr('placeholder'));
        $this->assertSame('', $field->getValue());

        // Submit filter
        $form['identifier'] = '2024';
        $crawler = $client->submit($form);

        $rows = $crawler->filter('[data-testid="app-regulation-table"] tbody > tr');
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
        $field = $form->get('organizationUuid');
        $this->assertSame('Organisation', trim($field->getLabel()->nodeValue));
        $choices = $crawler->filter('form[role="search"] select[name="organizationUuid"] > option')->each(fn ($node) => [$node->attr('value'), $node->text()]);

        $this->assertEquals([
            ['', 'Mes organisations'],
            [OrganizationFixture::SAINT_OUEN_ID, 'Commune de Saint Ouen sur Seine'],
            [OrganizationFixture::SEINE_SAINT_DENIS_ID, 'Département de Seine-Saint-Denis'],
            [OrganizationFixture::REGION_IDF_ID, 'Région Ile de France'],
        ], $choices);
        $this->assertSame('', $field->getValue());

        // Submit filter
        $form['organizationUuid'] = OrganizationFixture::REGION_IDF_ID;
        $crawler = $client->submit($form);

        $organizations = $crawler->filter('[data-testid="app-regulation-table"] tbody > tr td:nth-child(2)')->each(fn ($node) => $node->text());
        $this->assertCount(1, $organizations);
        foreach ($organizations as $org) {
            $this->assertSame('Région Ile de France', $org);
        }
    }

    public function testOrganizationFilterWithoutAuthenticatedUser(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/regulations');
        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        // Inspect filter field
        $searchButton = $crawler->selectButton('Rechercher');
        $form = $searchButton->form();
        $field = $form->get('organizationUuid');
        $this->assertSame('Organisation', trim($field->getLabel()->nodeValue));
        $choices = $crawler->filter('form[role="search"] select[name="organizationUuid"] > option')->each(fn ($node) => [$node->attr('value'), $node->text()]);
        $this->assertEquals([
            ['', 'Toutes les organisations'],
            [OrganizationFixture::SAINT_OUEN_ID, 'Commune de Saint Ouen sur Seine'],
            [OrganizationFixture::SEINE_SAINT_DENIS_ID, 'Département de Seine-Saint-Denis'],
            [OrganizationFixture::REGION_IDF_ID, 'Région Ile de France'],
        ], $choices);
        $this->assertSame('', $field->getValue());
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

        $rows = $crawler->filter('[data-testid="app-regulation-table"] tbody > tr');
        $this->assertSame(1, $rows->count());

        $statuses = $rows->filter('td:nth-child(4)')->each(fn ($node) => $node->text());
        $this->assertSame('à partir du 11/03/2023 en cours', implode(', ', $statuses));
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

        $rows = $crawler->filter('[data-testid="app-regulation-table"] tbody > tr');
        $this->assertSame(10, $rows->count());
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
            [RegulationOrderRecordStatusEnum::DRAFT->value, 'Brouillon'],
            [RegulationOrderRecordStatusEnum::PUBLISHED->value, 'Publié'],
        ], $choices);
        $this->assertSame('', $field->getValue());

        // Submit filter
        $form['status'] = RegulationOrderRecordStatusEnum::PUBLISHED->value;
        $crawler = $client->submit($form);

        $statuses = $crawler->filter('[data-testid="app-regulation-table"] tbody > tr td:nth-child(5)')->each(fn ($node) => $node->text());
        $this->assertCount(4, $statuses);
        foreach ($statuses as $status) {
            $this->assertSame('Publié', $status);
        }
    }

    public function testStatusFilterDraft(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/regulations');
        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $searchButton = $crawler->selectButton('Rechercher');
        $form = $searchButton->form();
        $form['status'] = RegulationOrderRecordStatusEnum::DRAFT->value;
        $crawler = $client->submit($form);

        $statuses = $crawler->filter('[data-testid="app-regulation-table"] tbody > tr td:nth-child(5)')->each(fn ($node) => $node->text());
        $this->assertCount(7, $statuses);
        foreach ($statuses as $status) {
            $this->assertSame('Brouillon', $status);
        }
    }

    public function testStatusFilterAsAnonymousUser(): void
    {
        $client = static::createClient();
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

        // Only published regulations are shown
        $statuses = $crawler->filter('[data-testid="app-regulation-table"] tbody > tr td:nth-child(5)')->each(fn ($node) => $node->text());
        $this->assertGreaterThan(0, \count($statuses));
        foreach ($statuses as $status) {
            $this->assertSame('Publié', $status);
        }
    }

    public function testStatusFilterAsAnonymousUserForceDraft(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/regulations?status=draft'); // Try to force with query parameter

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $searchButton = $crawler->selectButton('Rechercher');
        $form = $searchButton->form();
        $this->assertSame('published', $form->get('status')->getValue());

        // Still only published regulations are shown
        $statuses = $crawler->filter('[data-testid="app-regulation-table"] tbody > tr td:nth-child(5)')->each(fn ($node) => $node->text());
        $this->assertGreaterThan(0, \count($statuses));
        foreach ($statuses as $status) {
            $this->assertSame('Publié', $status);
        }
    }

    public function testFilterCombinationViaUrl(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', \sprintf(
            '/regulations?identifier=FO2&organizationUuid=%s&regulationOrderType=%s&status=%s',
            OrganizationFixture::SEINE_SAINT_DENIS_ID,
            RegulationOrderTypeEnum::TEMPORARY->value,
            RegulationOrderRecordStatusEnum::PUBLISHED->value,
        ));
        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $rows = $crawler->filter('[data-testid="app-regulation-table"] tbody > tr');
        $this->assertSame(1, $rows->count());

        $identifiers = $rows->filter('td:nth-child(1)')->each(fn ($node) => $node->text());
        $this->assertSame('FO2/2023', implode(', ', $identifiers));
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
        $client = static::createClient();
        $pageOne = $client->request('GET', '/regulations');

        $this->assertResponseStatusCodeSame(200);

        // check that the only link of the first row is to view the regulation
        $rows = $pageOne->filter('[data-testid="app-regulation-table"] tbody > tr');
        $row = $rows->eq(0)->filter('td');
        $links = $row->filter('a');
        $this->assertCount(1, $links);
        $this->assertSame('Voir le détail', $links->eq(0)->text());
    }
}
