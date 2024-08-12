<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\Regulation;

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
        $this->assertSame('10', $navLi->eq(6)->filter('a')->text());
        $this->assertSame('Page suivante', $navLi->eq(7)->filter('a')->text());
        $this->assertSame('Dernière page', $navLi->eq(8)->filter('a')->text());

        $client->clickLink('Ajouter un arrêté');
        $this->assertRouteSame('app_regulation_add');
    }

    public function testRegulationRendering(): void
    {
        $client = $this->login();

        // First item
        $pageOne = $client->request('GET', '/regulations?pageSize=1&page=1');

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
        $pageTwo = $client->request('GET', '/regulations?pageSize=1&page=2');
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
        $crawler = $client->request('GET', '/regulations?pageSize=1&page=6');
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
        $rows = $pageOne->filter('.app-regulation-table tbody > tr');
        $row = $rows->eq(0)->filter('td');
        $links = $row->filter('a');
        $this->assertCount(1, $links);
        $this->assertSame('Voir le détail', $links->eq(0)->text());
    }
}
